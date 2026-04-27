(function ($) {
    "use strict";

    var isProcessingProxy = false;
    var proxyTable = null;
    // local flag for loading the table
    var proxyTableLoading = false;

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
      loading = loading || proxyTableLoading;
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
    // function showLoadingOverlayForProxy() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     if ($pane.find('.bbcs-loading-overlay').length) return;
    //     var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //     $pane.css('position','relative'); // ensure positioning
    //     $pane.append(overlay);
    // }
    // function hideLoadingOverlayForProxy() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     $pane.find('.bbcs-loading-overlay').remove();
    // }
    

    function initializeProxyTable() {
        if (!$.fn.DataTable.isDataTable("#botblocker-proxy-rules")) {
            proxyTable = $("#botblocker-proxy-rules").DataTable({
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
                        d.action = "bbcs_get_botblocker_proxies";
                        d.nonce = botblockerData.nonce;
                    },
                    beforeSend: function(jqXHR, settings) {
                        proxyTableLoading = true;
                        // showLoadingOverlayForProxy();
                    },
                    complete: function() {
                        proxyTableLoading = false;
                        // hideLoadingOverlayForProxy();

                        setTimeout(function() {
                            if (proxyTable) {
                                proxyTable.columns.adjust();
                            }
                        }, 200);
                    }
                },

                columns: [
                    { data: "id", visible: false },
                    { data: "key", width: "150px" },
                    { data: "value", width: "150px" },
                    { data: "comment", width: "150px" },
                    {
                        data: null,
                        width: "100px",
                        render: function (data, type, row) {
                            return (
                                '<button class="btn btn-sm btn-default bbcs-actions-b edit-proxy" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Edit" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-edit"></i></button> ' +
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-proxy" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-trash-can"></i></button>'
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
                layout: {
                    topStart: {
                        buttons: [
                            "copy",
                            "csv",
                            "excel",
                            "pdf",
                            "print",
                            "colvis",
                            {
                                extend: "collection",
                                text: "Length Menu",
                                buttons: [
                                    {
                                        text: "10",
                                        action: function (e, dt, node, config) {
                                            dt.page.len(10).draw();
                                        },
                                    },
                                    {
                                        text: "25",
                                        action: function (e, dt, node, config) {
                                            dt.page.len(25).draw();
                                        },
                                    },
                                    {
                                        text: "50",
                                        action: function (e, dt, node, config) {
                                            dt.page.len(50).draw();
                                        },
                                    },
                                    {
                                        text: "100",
                                        action: function (e, dt, node, config) {
                                            dt.page.len(100).draw();
                                        },
                                    },
                                ],
                            },
                        ],
                    },
                },
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
                    
                    setTimeout(function() {
                        api.columns.adjust();
                    }, 100);
                },
            });
        }
        return proxyTable;
    }

    function showImportResultModal(result) {
        var modal = $(
            '<div class="modal fade" id="importProxyResultModal" tabindex="-1" aria-labelledby="importProxyResultModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="importProxyResultModalLabel">Import Result</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">' +
                "<p>Imported: " +
                result.imported +
                "</p>" +
                "<p>Skipped: " +
                result.skipped +
                "</p>" +
                "</div>"
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>'
        );

        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);

        $("#importProxyResultModal").modal("show");
    }

    function showConfirmClearModal(onConfirm) {
        var modal = $(
            '<div class="modal fade" id="confirmClearProxiesModal" tabindex="-1" aria-labelledby="confirmClearProxiesModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="confirmClearProxiesModalLabel">Clear All Proxies</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">Are you sure you want to remove all proxies?</div>'
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button><button type="button" class="btn btn-primary" id="confirmClearProxiesButton">Yes</button></div>'
        );

        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);

        $("#confirmClearProxiesButton").on("click", function () {
            $("#confirmClearProxiesModal").modal("hide");
            onConfirm();
        });

        $("#confirmClearProxiesModal").modal("show");
    }

    function readJSONFile(file, callback) {
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = JSON.parse(e.target.result);
                callback(data);
            } catch (err) {
                alert(bbcsProxyL10n.invalid_json + err.message);
            }
        };
        reader.readAsText(file);
    }

    $(document).ready(function () {
        initializeProxyTable();
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var targetTab = $(e.target).attr("href");
            if (targetTab === "#bbcs_proxy_list") {
                if (proxyTable) {
                    setTimeout(function() {
                        proxyTable.columns.adjust();
                    }, 150);
                }
            }
        });

        $("#editProxyForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_update_proxy&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#editProxyModal").modal("hide");
                        $("#botblocker-proxy-rules").DataTable().ajax.reload();
                    } else {
                        alert(bbcsProxyL10n.failed_update + response.data);
                    }
                },
            });
        });

        $("#botblocker-proxy-rules").on("click", ".edit-proxy", function () {
            var id = $(this).data("id");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_get_proxy_details",
                    id: id,
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var data = response.data;
                        $("#editProxyForm").find('[name="id"]').val(data.id);
                        $("#editProxyForm").find('[name="key"]').val(data.key);
                        $("#editProxyForm").find('[name="value"]').val(data.value);
                        $("#editProxyForm").find('[name="comment"]').val(data.comment);
                        $("#editProxyModal").modal("show");
                    } else {
                        alert(bbcsProxyL10n.failed_load + response.data);
                    }
                },
            });
        });

        $("#botblocker-proxy-rules").on("click", ".delete-proxy", function () {
            var id = $(this).data("id");
            if (confirm(bbcsProxyL10n.confirm_delete)) {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_delete_proxy",
                        id: id,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-proxy-rules").DataTable().ajax.reload();
                        }
                    },
                });
            }
        });

        $("#bbcs_proxy_add").on("click", function () {
            $("#createProxyModal").modal("show");
        });

        $("#createProxyForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_create_proxy&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#createProxyModal").modal("hide");
                        $("#botblocker-proxy-rules").DataTable().ajax.reload();
                    } else {
                        alert(bbcsProxyL10n.failed_create + response.data);
                    }
                },
            });
        });

        $("#bbcs_proxy_export").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_proxies",
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var blob = new Blob(
                            [JSON.stringify(response.data, null, 2)],
                            { type: "application/json" }
                        );
                        var downloadLink = document.createElement("a");
                        downloadLink.href = window.URL.createObjectURL(blob);
                        downloadLink.download = "botblocker_proxies.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        alert(bbcsProxyL10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_proxy_import").on("click", function () {
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
                                action: "bbcs_import_proxies",
                                proxies: JSON.stringify(data),
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-proxy-rules")
                                        .DataTable()
                                        .ajax.reload();
                                } else {
                                    alert(
                                        bbcsProxyL10n.failed_import +
                                            response.data
                                    );
                                }
                            },
                        });
                    });
                }
            });
            fileInput.click();
        });

        $("#bbcs_proxy_clear_all").on("click", function () {
            showConfirmClearModal(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_clear_all_proxies",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-proxy-rules").DataTable().ajax.reload();
                        } else {
                            alert(bbcsProxyL10n.failed_clear + response.data);
                        }
                    },
                });
            });
        });

        $("#bbcs_proxy_to_php").on("click", function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_render_proxy_file",
                    nonce: botblockerData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(bbcsProxyL10n.proxies_updated);
                    } else {
                        alert(bbcsProxyL10n.failed_update_proxies + response.data);
                    }
                }
            });
        });
    });
})(jQuery);