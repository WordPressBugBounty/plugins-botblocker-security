(function ($) {
    "use strict";

    // local flag for loading the table
    var ipv4TableLoading = false;

    // Register loading state for new UI tab switching guard.
    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['IPv4 List'] = function() { return ipv4TableLoading; };
    }

    var lastIpv4UITab = '';
    var ipv4JustInitialized = false;

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
      loading = loading || ipv4TableLoading;
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
    // function showLoadingOverlayForIpv4() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     if ($pane.find('.bbcs-loading-overlay').length) return;
    //     var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //     $pane.css('position','relative'); // ensure positioning
    //     $pane.append(overlay);
    // }
    // function hideLoadingOverlayForIpv4() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     $pane.find('.bbcs-loading-overlay').remove();
    // }
    

    function loadIpv4RulesTable() {
        if (!$.fn.DataTable.isDataTable("#botblocker-ipv4-rules")) {
            var table = $("#botblocker-ipv4-rules").DataTable({
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
                        d.action = "bbcs_get_botblocker_ipv4_rules";
                        d.nonce = botblockerData.nonce;
                    },
                    beforeSend: function(jqXHR, settings) {
                        ipv4TableLoading = true;
                        // showLoadingOverlayForIpv4();
                    },
                    complete: function(jqXHR, textStatus) {
                        ipv4TableLoading = false;
                        // hideLoadingOverlayForIpv4();
                    }
                },
                columns: [
                    { data: "id", visible: false },
                    { data: "priority", width: "50px" },
                    { data: "ip", width: "80px" },
                    { data: "rule", width: "80px" },
                    { data: "expires", width: "100px" },
                    { data: "comment", width: "100px", render: function(data) { return bbcsRenderReasonComment(data); } },
                    {
                        data: null,
                        width: "100px",
                        render: function (data, type, row) {
                            return (
                                '<button class="btn btn-sm btn-default bbcs-actions-b edit-ipv4-rule" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsIpv4L10n.edit + '" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-edit"></i></button> ' +
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-ipv4-rule" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsIpv4L10n.delete + '" data-id="' +
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
                layout: (function () {
                    var isNewUI = !!document.querySelector('.bbcs-app');
                    return isNewUI ? {
                        topStart: {
                            search: {
                                text: '',
                                placeholder: bbcsIpv4L10n.search_placeholder
                            }
                        },
                        topEnd: {
                            buttons: ['csv', 'excel']
                        }
                    } : {
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
                        }
                    };
                })(),
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass(data.rule === "allow" ? "bbcs-rule-row--allow" : "bbcs-rule-row--block");
                },
            });
            table.draw();
            table.columns.adjust();
            ipv4JustInitialized = true;
        }
    }

    function showImportResultModal(result) {
        var modal = $(
            '<div class="modal fade" id="importResultModal" tabindex="-1" aria-labelledby="importResultModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="importResultModalLabel">' + bbcsIpv4L10n.import_result + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">' +
                "<p>" + bbcsIpv4L10n.imported + ": " +
                result.imported +
                "</p>" +
                "<p>" + bbcsIpv4L10n.skipped + ": " +
                result.skipped +
                "</p>" +
                "</div>"
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsIpv4L10n.close + '</button></div>'
        );

        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);

        $("#importResultModal").modal("show");
    }

    function showConfirmClearModal(onConfirm) {
        var modal = $(
            '<div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">' + bbcsIpv4L10n.clear_all_rules + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">' + bbcsIpv4L10n.confirm_clear + '</div>'
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsIpv4L10n.no + '</button><button type="button" class="btn btn-primary" id="confirmClearButton">' + bbcsIpv4L10n.yes + '</button></div>'
        );

        modalContent.append(modalHeader, modalBody, modalFooter);
        modalDialog.append(modalContent);
        modal.append(modalDialog);
        $("body").append(modal);

        $("#confirmClearButton").on("click", function () {
            $("#confirmClearModal").modal("hide");
            onConfirm();
        });

        $("#confirmClearModal").modal("show");
    }

    function readJSONFile(file, callback) {
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = JSON.parse(e.target.result);
                callback(data);
            } catch (err) {
                alert(bbcsIpv4L10n.invalid_json + err.message);
            }
        };
        reader.readAsText(file);
    }

    $(document).ready(function () {
        $('a[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
            var target = $(e.target).attr("href");
            if (target === "#bbcs_IPv4_list") {
                loadIpv4RulesTable();
            }
        });

        $(document).on('bbcs:tab-changed', function (e, data) {
            if (data.tab === 'IPv4 List') {
                var sameTab = (lastIpv4UITab === data.tab);
                lastIpv4UITab = data.tab;
                loadIpv4RulesTable();
                if ($.fn.DataTable.isDataTable('#botblocker-ipv4-rules')) {
                    var dt = $('#botblocker-ipv4-rules').DataTable();
                    dt.columns.adjust();
                    if (!sameTab && !ipv4JustInitialized) {
                        dt.draw(false);
                    }
                    ipv4JustInitialized = false;
                }
            }
        });

        if ($('#bbcs_IPv4_list').hasClass('active')) {
            loadIpv4RulesTable();
        }

        $(document).on("input", "#priority", function () {
            $(this).siblings("#priorityValue").val(this.value);
        });

        $("#botblocker-ipv4-rules").on("click", ".edit-ipv4-rule", function () {
            var id = $(this).data("id");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_get_ipv4_rule_details",
                    id: id,
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var data = response.data;

                        var expiresTimestamp = data.expires;
                        var expiresFormattedDate = new Date(
                            expiresTimestamp * 1000
                        )
                            .toISOString()
                            .slice(0, 16);

                        $("#editIPv4Form").find('[name="id"]').val(data.id);
                        $("#editIPv4Form")
                            .find('[name="priority"]')
                            .val(data.priority);
                        $("#editIPv4Form").find('[name="ip"]').val(data.search);
                        $("#editIPv4Form")
                            .find('[name="comment"]')
                            .val(data.comment);
                        $("#editIPv4Form").find('[name="rule"]').val(data.rule);
                        $("#editIPv4Form")
                            .find('[name="expires"]')
                            .val(expiresFormattedDate);
                        $("#editIPv4Modal").modal("show");
                    } else {
                        alert(
                            bbcsIpv4L10n.failed_load + response.data
                        );
                    }
                },
            });
        });

        $("#editIPv4Form").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_update_ipv4_rule&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#editIPv4Modal").modal("hide");
                        $("#botblocker-ipv4-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    } else {
                        alert(bbcsIpv4L10n.failed_update + response.data);
                    }
                },
            });
        });

        $("#botblocker-ipv4-rules").on(
            "click",
            ".delete-ipv4-rule",
            function () {
                var id = $(this).data("id");
                if (
                    confirm(bbcsIpv4L10n.confirm_delete)
                ) {
                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: {
                            action: "bbcs_delete_ipv4_rule",
                            id: id,
                            nonce: botblockerData.nonce,
                        },
                        success: function (response) {
                            if (response.success) {
                                 $("#botblocker-ipv4-rules")
                                    .DataTable()
                                    .ajax.reload();
                                if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            }
                        },
                    });
                }
            }
        );

        $("#bbcs_ipv4_add").on("click", function () {
            $("#addIPv4Modal").modal("show");

            const form = document.getElementById("addIPv4Form");
            const expiresInput = form.querySelector("#addExpires");
            const now = new Date();
            now.setDate(now.getDate() + 1); 
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");

            const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;

            expiresInput.value = formattedDate;
        });

        $("#addIPv4Form").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_create_ipv4_rule&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#addIPv4Modal").modal("hide");
                        $("#botblocker-ipv4-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    } else {
                        alert(bbcsIpv4L10n.failed_create + response.data);
                    }
                },
            });
        });

        $("#bbcs_ipv4_export").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_ipv4_rules",
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
                        downloadLink.download = "botblocker_ipv4_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        alert(bbcsIpv4L10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_pagehead_export").on("click", function (e) {
            if ($('.bbcs-tab.is-active').data('tab') !== 'IPv4 List') return;
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_ipv4_rules",
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
                        downloadLink.download = "botblocker_ipv4_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        alert(bbcsIpv4L10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_ipv4_import").on("click", function () {
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
                                action: "bbcs_import_ipv4_rules",
                                rules: JSON.stringify(data),
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-ipv4-rules")
                                        .DataTable()
                                        .ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                } else {
                                    alert(
                                        bbcsIpv4L10n.failed_import +
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

        $("#bbcs_ipv4_clear_all").on("click", function () {
            showConfirmClearModal(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_clear_all_ipv4_rules",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-ipv4-rules")
                                .DataTable()
                                .ajax.reload();
                            if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        } else {
                            alert(
                                bbcsIpv4L10n.failed_clear + response.data
                            );
                        }
                    },
                });
            });
        });

        $("#bbcs_ipv4_import_white").on("click", function () {
            importIPv4List("whitelist");
        });

        $("#bbcs_ipv4_import_black").on("click", function () {
            importIPv4List("blacklist");
        });

        function importIPv4List(listType) {
            var fileInput = $("<input>", {
                type: "file",
                accept: ".txt",
            }).on("change", function () {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        var fileContent = e.target.result;
                        $.ajax({
                            url: botblockerData.ajaxurl,
                            type: "POST",
                            data: {
                                action: "import_ipv4_" + listType,
                                file_content: fileContent,
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-ipv4-rules")
                                        .DataTable()
                                        .ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                } else {
                                    alert(
                                        bbcsIpv4L10n['failed_import_' + listType] +
                                            response.data
                                    );
                                }
                            },
                        });
                    };
                    reader.readAsText(file);
                }
            });
            fileInput.click();
        }

        $('#bbcs_ipv4_to_php').on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_ipv4_to_php",
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    alert(response.data);
                },
            });
        });

        // New UI pagehead button wiring - tab-aware
        if (document.querySelector('.bbcs-app')) {
            $(document).on("click", "#bbcs_pagehead_add", function () {
                var activeTab = $('.bbcs-tab.is-active').data('tab');
                if (activeTab === 'IPv4 List') {
                    $("#addIPv4Modal").modal("show");
                    var form = document.getElementById("addIPv4Form");
                    var expiresInput = form.querySelector("#addExpires");
                    var now = new Date();
                    now.setDate(now.getDate() + 1);
                    var year = now.getFullYear();
                    var month = String(now.getMonth() + 1).padStart(2, "0");
                    var day = String(now.getDate()).padStart(2, "0");
                    var hours = String(now.getHours()).padStart(2, "0");
                    var minutes = String(now.getMinutes()).padStart(2, "0");
                    expiresInput.value = year + "-" + month + "-" + day + "T" + hours + ":" + minutes;
                }
            });

            $(document).on("click", "#bbcs_pagehead_import", function () {
                var activeTab = $('.bbcs-tab.is-active').data('tab');
                if (activeTab === 'IPv4 List') {
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
                                        action: "bbcs_import_ipv4_rules",
                                        rules: JSON.stringify(data),
                                        nonce: botblockerData.nonce,
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            showImportResultModal(response.data);
                                            $("#botblocker-ipv4-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                        } else {
                                            alert(bbcsIpv4L10n.failed_import + response.data);
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
