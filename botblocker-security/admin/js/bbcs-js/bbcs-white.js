(function ($) {
    "use strict";

    var isProcessingWhite = false;
    // local flag for loading the table
    var whiteTableLoading = false;

    // Register loading state for new UI tab switching guard.
    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['Trusted Bots'] = function() { return whiteTableLoading; };
    }

    var lastWhiteUITab = '';
    var whiteJustInitialized = false;

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
      loading = loading || whiteTableLoading;
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
    // function showLoadingOverlayForWhite() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     if ($pane.find('.bbcs-loading-overlay').length) return;
    //     var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //     $pane.css('position','relative'); // ensure positioning
    //     $pane.append(overlay);
    // }
    // function hideLoadingOverlayForWhite() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     $pane.find('.bbcs-loading-overlay').remove();
    // }


    function initializeWhiteTable() {
      if (!$.fn.DataTable.isDataTable("#botblocker-white")) {
        var table = $("#botblocker-white").DataTable({
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
              d.action = "bbcs_get_botblocker_white";
              d.nonce = botblockerData.nonce;
            },
            beforeSend: function(jqXHR, settings) {
              whiteTableLoading = true;
              // showLoadingOverlayForWhite();
            },
            complete: function(jqXHR, textStatus) {
              whiteTableLoading = false;
              // hideLoadingOverlayForWhite();
            }
          },
          
          columns: [
            { data: "id", visible: false },
            { data: "priority", width: "80px" },
            { data: "search", width: "80px" },
            { data: "data", width: "100px" },
            { data: "rule", width: "80px"},
            { data: "comment", width: "100px"},
            {
              data: null,
              width: "100px",
              render: function (data, type, row) {
                return (
                  '<button class="btn btn-sm btn-default bbcs-actions-b edit-white" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsWhiteL10n.edit + '" data-id="' +
                  row.id +
                  '"><i class="fa-regular fa-edit"></i></button> ' +
                  '<button class="btn btn-sm btn-default bbcs-actions-b delete-white"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsWhiteL10n.delete + '" data-id="' +
                  row.id +
                  '"><i class="fa-regular fa-trash-can"></i></button> ' +
                  '<button class="btn btn-sm bbcs-actions-b ' +
                  (row.disable == 0 ? "btn-default" : "btn-warning") +
                  ' toggle-white"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsWhiteL10n.toggle + '" data-id="' +
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
                  placeholder: bbcsWhiteL10n.search_placeholder
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

        whiteJustInitialized = true;

        // Toggle white bot
        $(document).on("click", "#botblocker-white .toggle-white", function (e) {
          e.preventDefault();
          if (isProcessingWhite) return;
  
          var $button = $(this);
          var id = $button.data("id");
  
          isProcessingWhite = true;
          $button.prop("disabled", true);
  
          $.ajax({
            url: botblockerData.ajaxurl,
            type: "POST",
            data: {
              action: "bbcs_toggle_white",
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
              isProcessingWhite = false;
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
        var modalHeader = $('<div class="modal-header"><h5 class="modal-title" id="importResultModalLabel">' + bbcsWhiteL10n.import_result + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>');
        var modalBody = $('<div class="modal-body">' + 
                          '<p>' + bbcsWhiteL10n.imported + ': ' + result.imported + '</p>' +
                          '<p>' + bbcsWhiteL10n.skipped + ': ' + result.skipped + '</p>' +
                          '</div>');
        var modalFooter = $('<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsWhiteL10n.close + '</button></div>');
    
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
        var modalHeader = $('<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">' + bbcsWhiteL10n.clear_all_rules + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>');
        var modalBody = $('<div class="modal-body">' + bbcsWhiteL10n.confirm_clear + '</div>');
        var modalFooter = $('<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsWhiteL10n.no + '</button><button type="button" class="btn btn-primary" id="confirmClearButton">' + bbcsWhiteL10n.yes + '</button></div>');
    
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
            window.bbcsRulesToast('error', bbcsWhiteL10n.invalid_json + err.message);
          }
        };
        reader.readAsText(file);
    }
      
    $(document).ready(function () {
         
      $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href');
        if (target === '#bbcs_white_bots') {
          initializeWhiteTable();
        }
      });

      $(document).on('bbcs:tab-changed', function (e, data) {
        if (data.tab === 'Trusted Bots') {
          var sameTab = (lastWhiteUITab === data.tab);
          lastWhiteUITab = data.tab;
          initializeWhiteTable();
          if ($.fn.DataTable.isDataTable('#botblocker-white')) {
            var dt = $('#botblocker-white').DataTable();
            dt.columns.adjust();
            if (!sameTab && !whiteJustInitialized) {
              dt.draw(false);
            }
            whiteJustInitialized = false;
          }
        }
      });

        if ($('#bbcs_white_bots').hasClass('active')) {
            initializeWhiteTable();
        }

        $(document).on("input", "#priority", function () {
            $(this).siblings("#priorityValue").val(this.value);
        });

        $("#editWhiteForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data:
                $(this).serialize() +
                "&action=bbcs_update_white&nonce=" +
                botblockerData.nonce,
              success: function (response) {
                if (response.success) {
                  $("#editWhiteModal").modal("hide");
                  $("#botblocker-white").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                  window.bbcsRulesToast('success', bbcsWhiteL10n.success_update);
                } else {
                  window.bbcsRulesToast('error', bbcsWhiteL10n.failed_update + response.data);
                }
              },
            });
        });

        $("#botblocker-white").on("click", ".edit-white", function () {
            var id = $(this).data("id");
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: {
                action: "bbcs_get_white_details",
                id: id,
                nonce: botblockerData.nonce,
              },
              success: function (response) {
                if (response.success) {
                  var data = response.data;
                  $("#editWhiteForm").find('[name="id"]').val(data.id);
                  $("#editWhiteForm").find('[name="priority"]').val(data.priority);
                  $("#editWhiteForm").find("#priorityValue").val(data.priority);
                  $("#editWhiteForm").find('[name="search"]').val(data.search);
                  $("#editWhiteForm").find('[name="data"]').val(data.data);
                  $("#editWhiteForm").find('[name="rule"]').val(data.rule);
                  $("#editWhiteForm").find('[name="comment"]').val(data.comment);
                  $("#editWhiteForm").find('[name="distance"]').val(data.distance);
                  $("#editWhiteModal").modal("show");
                } else {
                  window.bbcsRulesToast('error', bbcsWhiteL10n.failed_load + response.data);
                }
              },
            });
        });

        $("#botblocker-white").on("click", ".delete-white", function () {
            var id = $(this).data("id");
            bbcsConfirm(bbcsWhiteL10n.confirm_delete, function () {
              $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                  action: "bbcs_delete_white",
                  id: id,
                  nonce: botblockerData.nonce,
                },
                success: function (response) {
                  if (response.success) {
                    $("#botblocker-white").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    window.bbcsRulesToast('success', bbcsWhiteL10n.success_delete);
                  } else {
                    window.bbcsRulesToast('error', response.data);
                  }
                },
              });
            });
        });

        $("#bbcs_se_add").on("click", function() {
            $("#createWhiteModal").modal("show");
        });

        $("#createWhiteForm").on("submit", function(e) {
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: $(this).serialize() + "&action=bbcs_create_white&nonce=" + botblockerData.nonce,
              success: function(response) {
                if (response.success) {
                  $("#createWhiteModal").modal("hide");
                  $("#botblocker-white").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                  window.bbcsRulesToast('success', bbcsWhiteL10n.success_create);
                } else {
                  window.bbcsRulesToast('error', bbcsWhiteL10n.failed_create + response.data);
                }
              },
            });
        });

        $("#bbcs_se_export").on("click", function(e) {
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: {
                action: "bbcs_export_white",
                nonce: botblockerData.nonce,
              },
              success: function(response) {
                if (response.success) {
                  var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: "application/json" });
                  var downloadLink = document.createElement("a");
                  downloadLink.href = window.URL.createObjectURL(blob);
                  downloadLink.download = "botblocker_white_bots.json";
                  document.body.appendChild(downloadLink);
                  downloadLink.click();
                  document.body.removeChild(downloadLink);
                  window.bbcsRulesToast('success', bbcsWhiteL10n.success_export);
                } else {
                  window.bbcsRulesToast('error', bbcsWhiteL10n.failed_export + response.data);
                }
              },
            });
        });

        $("#bbcs_pagehead_export").on("click", function(e) {
            if ($('.bbcs-tab.is-active').data('tab') !== 'Trusted Bots') return;
            e.preventDefault();
            $.ajax({
              url: botblockerData.ajaxurl,
              type: "POST",
              data: {
                action: "bbcs_export_white",
                nonce: botblockerData.nonce,
              },
              success: function(response) {
                if (response.success) {
                  var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: "application/json" });
                  var downloadLink = document.createElement("a");
                  downloadLink.href = window.URL.createObjectURL(blob);
                  downloadLink.download = "botblocker_white_bots.json";
                  document.body.appendChild(downloadLink);
                  downloadLink.click();
                  document.body.removeChild(downloadLink);
                  window.bbcsRulesToast('success', bbcsWhiteL10n.success_export);
                } else {
                  window.bbcsRulesToast('error', bbcsWhiteL10n.failed_export + response.data);
                }
              },
            });
        });

        $("#bbcs_se_import").on("click", function() {
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
                      action: "bbcs_import_white",
                      white_bots: JSON.stringify(data),
                      nonce: botblockerData.nonce,
                    },
                    success: function(response) {
                      if (response.success) {
                        showImportResultModal(response.data);
                        $("#botblocker-white").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        window.bbcsRulesToast('success', bbcsWhiteL10n.success_import);
                      } else {
                        window.bbcsRulesToast('error', bbcsWhiteL10n.failed_import + response.data);
                      }
                    },
                  });
                });
              }
            });
            fileInput.click();
        });

        $("#bbcs_se_clear_all").on("click", function() {
            showConfirmClearModal(function() {
              $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                  action: "bbcs_clear_all_white",
                  nonce: botblockerData.nonce,
                },
                success: function(response) {
                  if (response.success) {
                    $("#botblocker-white").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    window.bbcsRulesToast('success', bbcsWhiteL10n.success_clear);
                  } else {
                    window.bbcsRulesToast('error', bbcsWhiteL10n.failed_clear + response.data);
                  }
                },
              });
            });
        });          

        $('#bbcs_se_to_php').on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_se_to_php",
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
                if (activeTab === 'Trusted Bots') {
                    $("#createWhiteModal").modal("show");
                }
            });

            $(document).on("click", "#bbcs_pagehead_import", function () {
                var activeTab = $('.bbcs-tab.is-active').data('tab');
                if (activeTab === 'Trusted Bots') {
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
                                        action: "bbcs_import_white",
                                        white_bots: JSON.stringify(data),
                                        nonce: botblockerData.nonce,
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            showImportResultModal(response.data);
                                            $("#botblocker-white").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                            window.bbcsRulesToast('success', bbcsWhiteL10n.success_import);
                                        } else {
                                            window.bbcsRulesToast('error', bbcsWhiteL10n.failed_import + response.data);
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
