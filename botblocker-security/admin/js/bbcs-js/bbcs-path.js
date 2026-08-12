(function ($) {
    "use strict";

    var isProcessingPath = false;
    // local flag for loading the table
    var pathTableLoading = false;

    // Register loading state for new UI tab switching guard.
    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['Paths'] = function() { return pathTableLoading; };
    }

    var lastPathUITab = '';
    var pathJustInitialized = false;

    // debounce / throttle params
    var switchDebounceMs = 200; // minimum interval between switches
    var _lastSwitchTs = 0;

    // Global interception before switching (show.bs.tab) - can be canceled
    $(document).on('show.bs.tab', 'a[data-bs-toggle="tab"]', function(e){
      var now = Date.now();
      if (now - _lastSwitchTs < switchDebounceMs) {
        // Too fast - cancel it
        e.preventDefault();
        return;
      }

      // If any table is currently loading, prevent switching
      // maintain compatibility - if the global variable tables exists, we take it into account
      var loading = false;
      if (typeof tables !== 'undefined') {
        loading = Object.keys(tables).some(function(k){ return !!tables[k].isLoading; });
      }
      // Add a local check for loading the table
      loading = loading || pathTableLoading;
      if (loading) {
        e.preventDefault();
        // You may briefly show a tooltip or indicator - prevent switching
        // Example: quickly highlight the active tab so the user understands what we’re waiting for
        var activeTab = $('a[data-bs-toggle="tab"].active');
        activeTab && activeTab.addClass('bbcs-tab-wait');
        setTimeout(function(){ activeTab && activeTab.removeClass('bbcs-tab-wait'); }, 400);
        return;
      }
      _lastSwitchTs = now;
    });

    // Helper: overlay functions for visually blocking the tab when the table is loading
    // function showLoadingOverlayForPath() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     if ($pane.find('.bbcs-loading-overlay').length) return;
    //     var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //     $pane.css('position','relative'); // ensure positioning
    //     $pane.append(overlay);
    // }
    // function hideLoadingOverlayForPath() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     $pane.find('.bbcs-loading-overlay').remove();
    // }
  
    function initializePathsTable() {
      if (!$.fn.DataTable.isDataTable("#botblocker-paths")) {
        var table = $("#botblocker-paths").DataTable({
          processing: true,
          serverSide: true,
          scrollX: true,
          autoWidth: false,
          fixedHeader: true,
          responsive: true,
          colReorder: true,
          ajax: {
            url: botblockerData.ajaxurl,
            type: "POST",
            data: function (d) {
              d.action = "bbcs_get_botblocker_paths";
              d.nonce = botblockerData.nonce;
            },
              beforeSend: function(jqXHR, settings) {
              pathTableLoading = true;
              // showLoadingOverlayForPath();
            },
            complete: function(jqXHR, textStatus) {
              pathTableLoading = false;
              // hideLoadingOverlayForPath();
            } 
          },
          
          columns: [
            { data: "id", visible: false },
            { data: "priority", width: "80px" },
            { data: "search", width: "100px" },
            { data: "rule", width: "80px"},
            { data: "comment", width: "100px"},
            {
              data: null,
              width: "100px",
              render: function (data, type, row) {
                return (
                  '<button class="btn btn-sm btn-default bbcs-actions-b edit-path" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsPathL10n.edit + '" data-id="' +
                  row.id +
                  '"><i class="fa-regular fa-edit"></i></button> ' +
                  '<button class="btn btn-sm btn-default bbcs-actions-b delete-path"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsPathL10n.delete + '" data-id="' +
                  row.id +
                  '"><i class="fa-regular fa-trash-can"></i></button> ' +
                  '<button class="btn btn-sm bbcs-actions-b ' +
                  (row.disable == 0 ? "btn-default" : "btn-warning") +
                  ' toggle-path"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsPathL10n.toggle + '" data-id="' +
                  row.id +
                  '"><i class="fas ' +
                  (row.disable == 0 ? "fa-stop" : "fa-play") +
                  '"></i></button>'
                );
              },
            },
          ],
          columnDefs: [
            {
              targets: "_all",
              className: "text-wrap",
            },
          ],
          createdRow: function (row, data, dataIndex) {
            $(row).addClass(data.disable == 0 ? "bbcs-rule-row--active" : "bbcs-rule-row--disabled");
          },
          layout: (function () {
            var isNewUI = !!document.querySelector('.bbcs-app');
            return isNewUI ? {
              topStart: {
                search: {
                  text: '',
                  placeholder: bbcsPathL10n.search_placeholder
                }
              },
              topEnd: {
                buttons: ['csv', 'excel']
              }
            } : {
              topStart: {
                buttons: [
                  'copy', 'csv', 'excel', 'pdf', 'print', 'colvis',
                  {
                    extend: 'collection',
                    text: 'Length Menu',
                    buttons: [
                      { text: '10', action: function ( e, dt, node, config ) { dt.page.len(10).draw(); } },
                      { text: '25', action: function ( e, dt, node, config ) { dt.page.len(25).draw(); } },
                      { text: '50', action: function ( e, dt, node, config ) { dt.page.len(50).draw(); } },
                      { text: '100', action: function ( e, dt, node, config ) { dt.page.len(100).draw(); } }
                    ]
                  }
                ]
              }
            };
          })(),
          drawCallback: function (settings) {
            var api = this.api();
            api.columns().every(function () {
              var column = this;
              var header = $(column.header());
              var body = $(column.nodes());
  
              if (body.length > 0) {
                header.css("min-width", body.first().css("width"));
                header.css("max-width", body.first().css("width"));
              }
            });
  
            api.columns.adjust();
          },
        });

        pathJustInitialized = true;

        // Toggle path
        $(document).on("click", "#botblocker-paths .toggle-path", function (e) {
          e.preventDefault();
          if (isProcessingPath) return;
  
          var $button = $(this);
          var id = $button.data("id");
  
          isProcessingPath = true;
          $button.prop("disabled", true);
  
          $.ajax({
            url: botblockerData.ajaxurl,
            type: "POST",
            data: {
              action: "bbcs_toggle_path",
              id: id,
              nonce: botblockerData.nonce,
            },
            success: function (response) {
              if (response.success) {
                var rowData = table.row($button.closest("tr")).data();
                rowData.disable = rowData.disable == 0 ? 1 : 0;
                table.row($button.closest("tr")).data(rowData).draw(false);
                if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
              }
            },
            complete: function () {
              isProcessingPath = false;
              $button.prop("disabled", false);
            },
          });
        });
      }
    }

    function showImportResultModal(result) {
        var modal = $('<div class="modal fade" id="importResultModal" tabindex="-1" aria-labelledby="importResultModalLabel" aria-hidden="true">');
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $('<div class="modal-header"><h5 class="modal-title" id="importResultModalLabel">' + bbcsPathL10n.import_result + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>');
        var modalBody = $('<div class="modal-body">' + 
                          '<p>' + bbcsPathL10n.imported + ': ' + result.imported + '</p>' +
                          '<p>' + bbcsPathL10n.skipped + ': ' + result.skipped + '</p>' +
                          '</div>');
        var modalFooter = $('<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsPathL10n.close + '</button></div>');
    
        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);
    
        $("#importResultModal").modal("show");
    }
    
    function showConfirmClearModal(onConfirm) {
        var modal = $('<div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearModalLabel" aria-hidden="true">');
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $('<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">' + bbcsPathL10n.clear_all_rules + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>');
        var modalBody = $('<div class="modal-body">' + bbcsPathL10n.confirm_clear + '</div>');
        var modalFooter = $('<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsPathL10n.no + '</button><button type="button" class="btn btn-primary" id="confirmClearButton">' + bbcsPathL10n.yes + '</button></div>');
    
        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);
    
        $("#confirmClearButton").on("click", function() {
          $("#confirmClearModal").modal("hide");
          onConfirm();
        });
    
        $("#confirmClearModal").modal("show");
    }

    function readJSONFile(file, callback) {
        var reader = new FileReader();
        reader.onload = function(e) {
          try {
            var data = JSON.parse(e.target.result);
            callback(data);
          } catch (err) {
            window.bbcsRulesToast('error', bbcsPathL10n.invalid_json + err.message);
          }
        };
        reader.readAsText(file);
    }
       
    $(document).ready(function () {

        // Permanently ban → hide date picker + fill BOTBLOCKER_EXP_INF date.
        $(document).on('change', 'select[name="rule"]', function () {
            var $expires = $(this).closest('form').find('[name="expires"]');
            if (!$expires.length) return;
            var $wrapper = $expires.closest('.col-md-6');
            if ($(this).val() === 'permanently_ban') {
                var d = new Date();
                d.setFullYear(d.getFullYear() + 200);
                var pad = function (n) { return String(n).padStart(2, '0'); };
                $expires.val(d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()));
                $wrapper.hide();
                $expires.prop('required', false);
            } else {
                $wrapper.show();
                $expires.prop('required', true);
            }
        });

      $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href');
        if (target === '#bbcs_path') {
          initializePathsTable();
        }
      });

      $(document).on('bbcs:tab-changed', function (e, data) {
        if (data.tab === 'Paths') {
          var sameTab = (lastPathUITab === data.tab);
          lastPathUITab = data.tab;
          initializePathsTable();
          if ($.fn.DataTable.isDataTable('#botblocker-paths')) {
            var dt = $('#botblocker-paths').DataTable();
            dt.columns.adjust();
            if (!sameTab && !pathJustInitialized) {
              dt.draw(false);
            }
            pathJustInitialized = false;
          }
        }
      });

        if ($('#bbcs_path').hasClass('active')) {
            initializePathsTable();
        }

        $(document).on("input", "#priority", function () {
            $(this).siblings("#priorityValue").val(this.value);
        });

        $("#editPathForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data:
                $(this).serialize() +
                "&action=bbcs_update_path&nonce=" +
                botblockerData.nonce,
              success: function (response) {
                if (response.success) {
                  $("#editPathModal").modal("hide");
                  $("#botblocker-paths").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                  window.bbcsRulesToast('success', bbcsPathL10n.success_update);
                } else {
                  window.bbcsRulesToast('error', bbcsPathL10n.failed_update + response.data);
                }
              },
            });
        });

        $("#botblocker-paths").on("click", ".edit-path", function () {
            var id = $(this).data("id");
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: {
                action: "bbcs_get_path_details",
                id: id,
                nonce: botblockerData.nonce,
              },
              success: function (response) {
                if (response.success) {
                  var data = response.data;
                  $("#editPathForm").find('[name="id"]').val(data.id);
                  $("#editPathForm").find('[name="priority"]').val(data.priority);
                  $("#editPathForm").find("#priorityValue").val(data.priority);
                  $("#editPathForm").find('[name="search"]').val(data.search);
                  $("#editPathForm").find('[name="comment"]').val(data.comment);
                  $("#editPathForm").find('[name="rule"]').val(data.rule);
                  $("#editPathModal").modal("show");
                } else {
                  window.bbcsRulesToast('error', bbcsPathL10n.failed_load + response.data);
                }
              },
            });
        });
      
        $("#botblocker-paths").on("click", ".delete-path", function () {
            var id = $(this).data("id");
            if (confirm(bbcsPathL10n.confirm_delete)) {
              $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                  action: "bbcs_delete_path",
                  id: id,
                  nonce: botblockerData.nonce,
                },
                success: function (response) {
                  if (response.success) {
                    $("#botblocker-paths").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    window.bbcsRulesToast('success', bbcsPathL10n.success_delete);
                  } else {
                    window.bbcsRulesToast('error', response.data);
                  }
                },
              });
            }
        });
      
        $("#bbcs_path_add").on("click", function() {
            $("#createPathModal").modal("show");
        });

        $("#createPathForm").on("submit", function(e) {
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: $(this).serialize() + "&action=bbcs_create_path&nonce=" + botblockerData.nonce,
              success: function(response) {
                if (response.success) {
                  $("#createPathModal").modal("hide");
                  $("#botblocker-paths").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                  window.bbcsRulesToast('success', bbcsPathL10n.success_create);
                } else {
                  window.bbcsRulesToast('error', bbcsPathL10n.failed_create + response.data);
                }
              },
            });
        });

        $("#bbcs_path_export").on("click", function(e) {
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: {
                action: "bbcs_export_paths",
                nonce: botblockerData.nonce,
              },
              success: function(response) {
                if (response.success) {
                  var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: "application/json" });
                  var downloadLink = document.createElement("a");
                  downloadLink.href = window.URL.createObjectURL(blob);
                  downloadLink.download = "botblocker_paths.json";
                  document.body.appendChild(downloadLink);
                  downloadLink.click();
                  document.body.removeChild(downloadLink);
                  window.bbcsRulesToast('success', bbcsPathL10n.success_export);
                } else {
                  window.bbcsRulesToast('error', bbcsPathL10n.failed_export + response.data);
                }
              },
            });
        });

        $("#bbcs_pagehead_export").on("click", function(e) {
            if ($('.bbcs-tab.is-active').data('tab') !== 'Paths') return;
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: {
                action: "bbcs_export_paths",
                nonce: botblockerData.nonce,
              },
              success: function(response) {
                if (response.success) {
                  var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: "application/json" });
                  var downloadLink = document.createElement("a");
                  downloadLink.href = window.URL.createObjectURL(blob);
                  downloadLink.download = "botblocker_paths.json";
                  document.body.appendChild(downloadLink);
                  downloadLink.click();
                  document.body.removeChild(downloadLink);
                  window.bbcsRulesToast('success', bbcsPathL10n.success_export);
                } else {
                  window.bbcsRulesToast('error', bbcsPathL10n.failed_export + response.data);
                }
              },
            });
        });

        $("#bbcs_path_import").on("click", function() {
            var fileInput = $("<input>", {
              type: "file",
              accept: "application/json",
            }).on("change", function() {
              var file = this.files[0];
              if (file) {
                readJSONFile(file, function(data) {
                  $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                      action: "bbcs_import_paths",
                      paths: JSON.stringify(data),
                      nonce: botblockerData.nonce,
                    },
                    success: function(response) {
                      if (response.success) {
                        showImportResultModal(response.data);
                        $("#botblocker-paths").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        window.bbcsRulesToast('success', bbcsPathL10n.success_import);
                      } else {
                        window.bbcsRulesToast('error', bbcsPathL10n.failed_import + response.data);
                      }
                    },
                  });
                });
              }
            });
            fileInput.click();
        });

        $("#bbcs_path_clear_all").on("click", function() {
            showConfirmClearModal(function() {
              $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                  action: "bbcs_clear_all_paths",
                  nonce: botblockerData.nonce,
                },
                success: function(response) {
                  if (response.success) {
                    $("#botblocker-paths").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    window.bbcsRulesToast('success', bbcsPathL10n.success_clear);
                  } else {
                    window.bbcsRulesToast('error', bbcsPathL10n.failed_clear + response.data);
                  }
                },
              });
            });
        });          

        $('#bbcs_path_to_php').on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_path_to_php",
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    window.bbcsRulesToast(response.success ? 'success' : 'error', response.data);
                },
            });
        });

        // New UI pagehead button wiring - tab-aware
        if (document.querySelector('.bbcs-app')) {
            $(document).on("click", "#bbcs_pagehead_add", function () {
                var activeTab = $('.bbcs-tab.is-active').data('tab');
                if (activeTab === 'Paths') {
                    $("#createPathModal").modal("show");
                }
            });

            $(document).on("click", "#bbcs_pagehead_import", function () {
                var activeTab = $('.bbcs-tab.is-active').data('tab');
                if (activeTab === 'Paths') {
                    var fileInput = $("<input>", {
                        type: "file",
                        accept: "application/json",
                    }).on("change", function () {
                        var file = this.files[0];
                        if (file) {
                            readJSONFile(file, function (data) {
                                $.ajax({
                                    url: botblockerData.ajaxurl,
                                    type: "POST",
                                    data: {
                                        action: "bbcs_import_paths",
                                        paths: JSON.stringify(data),
                                        nonce: botblockerData.nonce,
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            showImportResultModal(response.data);
                                            $("#botblocker-paths").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                            window.bbcsRulesToast('success', bbcsPathL10n.success_import);
                                        } else {
                                            window.bbcsRulesToast('error', bbcsPathL10n.failed_import + response.data);
                                        }
                                    },
                                });
                            });
                        }
                    });
                    fileInput.click();
                }
            });
        }
    });      
})(jQuery);
