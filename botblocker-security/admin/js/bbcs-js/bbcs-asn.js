(function ($) {
    "use strict";

    var isProcessingAsn = false;
    var asnTable = null;
    var asnTableLoading = false;

    // Register loading state for new UI tab switching guard.
    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['ASN'] = function() { return asnTableLoading; };
    }

    var lastAsnUITab = '';
    var asnJustInitialized = false;

    var switchDebounceMs = 200;
    var _lastSwitchTs = 0;

    $(document).on('show.bs.tab', 'a[data-bs-toggle="tab"]', function(e){
      var now = Date.now();
      if (now - _lastSwitchTs < switchDebounceMs) {
        e.preventDefault();
        return;
      }

      var loading = false;
      if (typeof tables !== 'undefined') {
        loading = Object.keys(tables).some(function(k){ return !!tables[k].isLoading; });
      }
      loading = loading || asnTableLoading;
      if (loading) {
        e.preventDefault();
        var activeTab = $('a[data-bs-toggle="tab"].active');
        activeTab && activeTab.addClass('bbcs-tab-wait');
        setTimeout(function(){ activeTab && activeTab.removeClass('bbcs-tab-wait'); }, 400);
        return;
      }
      _lastSwitchTs = now;
    });

    function initializeAsnTable() {
        if (!$.fn.DataTable.isDataTable("#botblocker-asn-rules")) {
            asnTable = $("#botblocker-asn-rules").DataTable({
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
                        d.action = "bbcs_get_asn";
                        d.nonce = botblockerData.nonce;
                    },
                    beforeSend: function() {
                        asnTableLoading = true;
                    },
                    complete: function() {
                        asnTableLoading = false;
                        setTimeout(function() {
                            if (asnTable) {
                                asnTable.columns.adjust();
                            }
                        }, 200);
                    }
                },

                columns: [
                    { data: "id", visible: false },
                    { data: "priority", width: "80px" },
                    { data: "asnum", width: "100px" },
                    { data: "asname", width: "150px" },
                    { data: "rule", width: "80px" },
                    { data: "comment", width: "150px" },
                    {
                        data: null,
                        width: "100px",
                        render: function (data, type, row) {
                            return (
                                '<button class="btn btn-sm btn-default bbcs-actions-b edit-asn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsAsnL10n.edit + '" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-edit"></i></button> ' +
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-asn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsAsnL10n.delete + '" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-trash-can"></i></button> ' +
                                '<button class="btn btn-sm bbcs-actions-b ' +
                                (row.disable == 0 ? "btn-default" : "btn-warning") +
                                ' toggle-asn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsAsnL10n.toggle + '" data-id="' +
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
                createdRow: function (row, data) {
                    $(row).addClass(data.disable == 0 ? "bbcs-rule-row--active" : "bbcs-rule-row--disabled");
                },
                layout: (function () {
                    var isNewUI = !!document.querySelector('.bbcs-app');
                    return isNewUI ? {
                        topStart: {
                            search: {
                                text: '',
                                placeholder: bbcsAsnL10n.search_placeholder
                            }
                        },
                        topEnd: {
                            buttons: ['csv', 'excel']
                        }
                    } : {
                        topStart: {
                            buttons: [
                                "copy", "csv", "excel", "pdf", "print", "colvis",
                                {
                                    extend: "collection",
                                    text: "Length Menu",
                                    buttons: [
                                        { text: "10", action: function (e, dt) { dt.page.len(10).draw(); } },
                                        { text: "25", action: function (e, dt) { dt.page.len(25).draw(); } },
                                        { text: "50", action: function (e, dt) { dt.page.len(50).draw(); } },
                                        { text: "100", action: function (e, dt) { dt.page.len(100).draw(); } }
                                    ]
                                }
                            ]
                        }
                    };
                })(),
                drawCallback: function () {
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

            asnJustInitialized = true;

            $(document).on("click", "#botblocker-asn-rules .toggle-asn", function (e) {
                e.preventDefault();
                if (isProcessingAsn) return;

                var $button = $(this);
                var id = $button.data("id");

                isProcessingAsn = true;
                $button.prop("disabled", true);

                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_toggle_asn",
                        id: id,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            var rowData = asnTable.row($button.closest("tr")).data();
                            rowData.disable = rowData.disable == 0 ? 1 : 0;
                            asnTable.row($button.closest("tr")).data(rowData).draw(false);
                            if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        }
                    },
                    complete: function () {
                        isProcessingAsn = false;
                        $button.prop("disabled", false);
                    },
                });
            });
        }
        return asnTable;
    }

    function showImportResultModal(result) {
        var modal = $('<div class="modal fade" id="importAsnResultModal" tabindex="-1" aria-labelledby="importAsnResultModalLabel" aria-hidden="true">');
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $('<div class="modal-header"><h5 class="modal-title" id="importAsnResultModalLabel">' + bbcsAsnL10n.import_result + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>');
        var modalBody = $('<div class="modal-body"><p>' + bbcsAsnL10n.imported + ': ' + result.imported + '</p><p>' + bbcsAsnL10n.skipped + ': ' + result.skipped + '</p></div>');
        var modalFooter = $('<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsAsnL10n.close + '</button></div>');

        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);

        $("#importAsnResultModal").modal("show");
    }

    function showConfirmClearModal(onConfirm) {
        var modal = $('<div class="modal fade" id="confirmClearAsnModal" tabindex="-1" aria-labelledby="confirmClearAsnModalLabel" aria-hidden="true">');
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $('<div class="modal-header"><h5 class="modal-title" id="confirmClearAsnModalLabel">' + bbcsAsnL10n.clear_all_rules + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>');
        var modalBody = $('<div class="modal-body">' + bbcsAsnL10n.confirm_clear + '</div>');
        var modalFooter = $('<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsAsnL10n.no + '</button><button type="button" class="btn btn-primary" id="confirmClearAsnButton">' + bbcsAsnL10n.yes + '</button></div>');

        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);

        $("#confirmClearAsnButton").on("click", function() {
            $("#confirmClearAsnModal").modal("hide");
            onConfirm();
        });

        $("#confirmClearAsnModal").modal("show");
    }

    function readJSONFile(file, callback) {
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = JSON.parse(e.target.result);
                callback(data);
            } catch (err) {
                window.bbcsRulesToast('error', bbcsAsnL10n.invalid_json + err.message);
            }
        };
        reader.readAsText(file);
    }

    $(document).ready(function () {
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if (target === '#bbcs_asn_list') {
                initializeAsnTable();
                if (asnTable) {
                    setTimeout(function() {
                        asnTable.columns.adjust();
                    }, 150);
                }
            }
        });

        $(document).on('bbcs:tab-changed', function (e, data) {
            if (data.tab === 'ASN') {
                var sameTab = (lastAsnUITab === data.tab);
                lastAsnUITab = data.tab;
                initializeAsnTable();
                if (asnTable) {
                    asnTable.columns.adjust();
                    if (!sameTab && !asnJustInitialized) {
                        asnTable.draw(false);
                    }
                    asnJustInitialized = false;
                }
            }
        });

        if ($('#bbcs_asn_list').hasClass('active')) {
            initializeAsnTable();
            if (asnTable) {
                setTimeout(function() {
                    asnTable.columns.adjust();
                }, 150);
            }
        }

        $(document).on("input", "#asnPriority", function () {
            $(this).siblings("#asnPriorityValue").val(this.value);
        });
        $(document).on("input", "#editAsnPriority", function () {
            $(this).siblings("#editAsnPriorityValue").val(this.value);
        });

        $("#editAsnForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: $(this).serialize() + "&action=bbcs_update_asn&nonce=" + botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#editAsnModal").modal("hide");
                        $("#botblocker-asn-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        window.bbcsRulesToast('success', bbcsAsnL10n.success_update);
                    } else {
                        window.bbcsRulesToast('error', bbcsAsnL10n.failed_update + response.data);
                    }
                },
            });
        });

        $("#botblocker-asn-rules").on("click", ".edit-asn", function () {
            var id = $(this).data("id");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_get_asn_details",
                    id: id,
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var data = response.data;
                        $("#editAsnForm").find('[name="id"]').val(data.id);
                        $("#editAsnForm").find('[name="priority"]').val(data.priority);
                        $("#editAsnForm").find("#editAsnPriorityValue").val(data.priority);
                        $("#editAsnForm").find('[name="rule"]').val(data.rule);
                        $("#editAsnForm").find('[name="asnum"]').val(data.asnum);
                        $("#editAsnForm").find('[name="asname"]').val(data.asname);
                        $("#editAsnForm").find('[name="comment"]').val(data.comment);
                        $("#editAsnModal").modal("show");
                    } else {
                        window.bbcsRulesToast('error', bbcsAsnL10n.failed_load + response.data);
                    }
                },
            });
        });

        $("#botblocker-asn-rules").on("click", ".delete-asn", function () {
            var id = $(this).data("id");
            if (confirm(bbcsAsnL10n.confirm_delete)) {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_delete_asn",
                        id: id,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-asn-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            window.bbcsRulesToast('success', bbcsAsnL10n.success_delete);
                        } else {
                            window.bbcsRulesToast('error', response.data);
                        }
                    },
                });
            }
        });

        $("#bbcs_asn_add").on("click", function () {
            $("#createAsnModal").modal("show");
        });

        $("#createAsnForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: $(this).serialize() + "&action=bbcs_create_asn&nonce=" + botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#createAsnModal").modal("hide");
                        $("#botblocker-asn-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        window.bbcsRulesToast('success', bbcsAsnL10n.success_create);
                    } else {
                        window.bbcsRulesToast('error', bbcsAsnL10n.failed_create + response.data);
                    }
                },
            });
        });

        $("#bbcs_asn_export").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_asn",
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: "application/json" });
                        var downloadLink = document.createElement("a");
                        downloadLink.href = window.URL.createObjectURL(blob);
                        downloadLink.download = "botblocker_asn_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        window.bbcsRulesToast('success', bbcsAsnL10n.success_export);
                    } else {
                        window.bbcsRulesToast('error', bbcsAsnL10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_pagehead_export").on("click", function (e) {
            if ($('.bbcs-tab.is-active').data('tab') !== 'ASN') return;
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_asn",
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: "application/json" });
                        var downloadLink = document.createElement("a");
                        downloadLink.href = window.URL.createObjectURL(blob);
                        downloadLink.download = "botblocker_asn_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        window.bbcsRulesToast('success', bbcsAsnL10n.success_export);
                    } else {
                        window.bbcsRulesToast('error', bbcsAsnL10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_asn_import").on("click", function () {
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
                                action: "bbcs_import_asn",
                                asn_rules: JSON.stringify(data),
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-asn-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                    window.bbcsRulesToast('success', bbcsAsnL10n.success_import);
                                } else {
                                    window.bbcsRulesToast('error', bbcsAsnL10n.failed_import + response.data);
                                }
                            },
                        });
                    });
                }
            });
            fileInput.click();
        });

        $("#bbcs_asn_clear_all").on("click", function () {
            showConfirmClearModal(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_clear_all_asn",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-asn-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            window.bbcsRulesToast('success', bbcsAsnL10n.success_clear);
                        } else {
                            window.bbcsRulesToast('error', bbcsAsnL10n.failed_clear + response.data);
                        }
                    },
                });
            });
        });

        $("#bbcs_asn_to_php").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_asn_to_php",
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
                if (activeTab === 'ASN') {
                    $("#createAsnModal").modal("show");
                }
            });

            $(document).on("click", "#bbcs_pagehead_import", function () {
                var activeTab = $('.bbcs-tab.is-active').data('tab');
                if (activeTab === 'ASN') {
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
                                        action: "bbcs_import_asn",
                                        asn_rules: JSON.stringify(data),
                                        nonce: botblockerData.nonce,
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            showImportResultModal(response.data);
                                            $("#botblocker-asn-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                            window.bbcsRulesToast('success', bbcsAsnL10n.success_import);
                                        } else {
                                            window.bbcsRulesToast('error', bbcsAsnL10n.failed_import + response.data);
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
