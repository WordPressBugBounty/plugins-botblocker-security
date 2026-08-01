(function ($) {
    "use strict";

    /**
     * Render diagnostic reason codes in rule comment column.
     * Stored as "Captcha fail [R:TD] PH" → displayed with expanded reason.
     * Codes: TD=Token decrypt fail, TT=Transient expired, DM=Date mismatch,
     * HM=Hash mismatch, RM=reCaptcha mode mismatch, NM=No matching mode.
     */
    window.bbcsRenderReasonComment = function(comment) {
        if (!comment || typeof comment !== 'string') return comment || '';
        var reasonMap = {
            'TD': 'Token decrypt fail',
            'TT': 'Transient expired',
            'DM': 'Date mismatch',
            'HM': 'Hash mismatch',
            'RM': 'reCaptcha mode mismatch',
            'NM': 'No matching mode'
        };
        return comment.replace(/\[R:([A-Z,]+)\]/g, function(match, codes) {
            var parts = codes.split(',');
            var expanded = parts.map(function(c) {
                return reasonMap[c.trim()] || c.trim();
            }).join(', ');
            return '<span title="' + expanded + '" style="cursor:help;border-bottom:1px dotted #888">[' + expanded + ']</span>';
        });
    };
 
    var isProcessingRule = false;
    // local flag for loading the rules table
    var rulesTableLoading = false;

    // Register loading state for new UI tab switching guard.
    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['Rules'] = function() { return rulesTableLoading; };
    }

    var lastRulesUITab = '';
    var rulesJustInitialized = false;

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
      loading = loading || rulesTableLoading;
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
    // function showLoadingOverlayForRules() {
    //     var $pane = $('#botblocker-rules').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     if ($pane.find('.bbcs-loading-overlay').length) return;
    //     var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //     $pane.css('position','relative'); // ensure positioning
    //     $pane.append(overlay);
    // }
    // function hideLoadingOverlayForRules() {
    //     var $pane = $('#botblocker-rules').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     $pane.find('.bbcs-loading-overlay').remove();
    // }

    function initializeRulesTable() {
        if (!$.fn.DataTable.isDataTable("#botblocker-rules")) {
            var table = $("#botblocker-rules").DataTable({
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
                        d.action = "bbcs_get_botblocker_rules";
                        d.nonce = botblockerData.nonce;
                    },
                    beforeSend: function(jqXHR, settings) {
                        rulesTableLoading = true;
                        // showLoadingOverlayForRules();
                    },
                    complete: function(jqXHR, textStatus) {
                        rulesTableLoading = false;
                        // hideLoadingOverlayForRules();
                    }
                },

                columns: [
                    { data: "id", visible: false },
                    { data: "priority", width: "80px" },
                    { data: "type", width: "80px" },
                    { data: "data", width: "100px" },
                    { data: "expires", width: "100px" },
                    { data: "rule", width: "80px" },
                    { data: "comment", width: "100px" },
                    {
                        data: null,
                        width: "100px",
                        render: function (data, type, row) {
                            return (
                                '<button class="btn btn-sm btn-default bbcs-actions-b edit-rule" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsRulesL10n.edit + '" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-edit"></i></button> ' +
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-rule"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsRulesL10n.delete + '" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-trash-can"></i></button> ' +
                                '<button class="btn btn-sm bbcs-actions-b ' +
                                (row.disable == 0
                                    ? "btn-default"
                                    : "btn-warning") +
                                ' toggle-rule"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + bbcsRulesL10n.toggle + '" data-id="' +
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
                                placeholder: bbcsRulesL10n.search_placeholder
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
                drawCallback: function (settings) {
                    var api = this.api();
                    var $tabPanel = $('#botblocker-rules').closest('.bbcs-tabpanel');
                    var isHidden = $tabPanel.length && $tabPanel[0].hasAttribute('hidden');

                    if (!isHidden) {
                        api.columns().every(function () {
                            var column = this;
                            var header = $(column.header());
                            var body = $(column.nodes());

                            if (body.length > 0) {
                                header.css("min-width", body.first().css("width"));
                                header.css("max-width", body.first().css("width"));
                            }
                        });
                    }

                    api.columns.adjust();
                },
            });

            rulesJustInitialized = true;

            // Toggle rule
            $(document).on(
                "click",
                "#botblocker-rules .toggle-rule",
                function (e) {
                    e.preventDefault();
                    if (isProcessingRule) return;

                    var $button = $(this);
                    var id = $button.data("id");

                    isProcessingRule = true;
                    $button.prop("disabled", true);

                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: {
                            action: "bbcs_toggle_rule",
                            id: id,
                            nonce: botblockerData.nonce,
                        },
                        success: function (response) {
                            if (response.success) {
                                var rowData = table
                                    .row($button.closest("tr"))
                                    .data();
                                rowData.disable = rowData.disable == 0 ? 1 : 0;
                                table
                                    .row($button.closest("tr"))
                                    .data(rowData)
                                    .draw(false);
                                if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            }
                        },
                        complete: function () {
                            isProcessingRule = false;
                            $button.prop("disabled", false);
                        },
                    });
                }
            );
        }
    }

    function showImportResultModal(result) {
        var modal = $(
            '<div class="modal fade" id="importResultModal" tabindex="-1" aria-labelledby="importResultModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="importResultModalLabel">' + bbcsRulesL10n.import_result + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">' +
                "<p>" + bbcsRulesL10n.imported + ": " + result.imported + "</p>" +
                "<p>" + bbcsRulesL10n.skipped + ": " + result.skipped + "</p>" +
                "</div>"
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsRulesL10n.close + '</button></div>'
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
            '<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">' + bbcsRulesL10n.clear_all_rules + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">' + bbcsRulesL10n.confirm_clear + '</div>'
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + bbcsRulesL10n.no + '</button><button type="button" class="btn btn-primary" id="confirmClearButton">' + bbcsRulesL10n.yes + '</button></div>'
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
                alert(bbcsRulesL10n.invalid_json + err.message);
            }
        };
        reader.readAsText(file);
    }

    $(document).ready(function () {
        initializeRulesTable();

        $(document).on('bbcs:tab-changed', function (e, data) {
            if (data.tab === 'Rules') {
                var sameTab = (lastRulesUITab === data.tab);
                lastRulesUITab = data.tab;
                initializeRulesTable();
                if ($.fn.DataTable.isDataTable('#botblocker-rules')) {
                    var dt = $('#botblocker-rules').DataTable();
                    dt.columns.adjust();
                    if (!sameTab && !rulesJustInitialized) {
                        dt.draw(false);
                    }
                    rulesJustInitialized = false;
                }
            }
        });

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

        $(document).on("input", "#priority", function () {
            $(this).siblings("#priorityValue").val(this.value);
        });

        $("#editRuleForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_update_rule&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#editRuleModal").modal("hide");
                        $("#botblocker-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    } else {
                        alert(bbcsRulesL10n.failed_update + response.data);
                    }
                },
            });
        });

        $("#botblocker-rules").on("click", ".edit-rule", function () {
            var id = $(this).data("id");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_get_rule_details",
                    id: id,
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var data = response.data;

                        var expiresTimestamp = data.expires;
                        var expiresFormattedDate = new Date(
                            expiresTimestamp * 1000
                        ).toISOString().slice(0, 16);

                        $("#editRuleForm").find('[name="id"]').val(data.id);
                        $("#editRuleForm").find('[name="type"]').val(data.type);
                        $("#editRuleForm")
                            .find('[name="priority"]')
                            .val(data.priority);
                        $("#editRuleForm").find("#priorityValue").val(data.priority);
                        $("#editRuleForm").find('[name="data"]').val(data.data);
                        $("#editRuleForm")
                            .find('[name="comment"]')
                            .val(data.comment);
                        $("#editRuleForm").find('[name="rule"]').val(data.rule);
                        $("#editRuleForm")
                            .find('[name="expires"]')
                            .val(expiresFormattedDate);
                        $("#editRuleModal").modal("show");
                    } else {
                        alert(bbcsRulesL10n.failed_load + response.data);
                    }
                },
            });
        });

        $("#botblocker-rules").on("click", ".delete-rule", function () {
            var id = $(this).data("id");
            if (confirm(bbcsRulesL10n.confirm_delete)) {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_delete_rule",
                        id: id,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        }
                    },
                });
            }
        });

        $("#bbcs_rules_add").on("click", function () {
            $("#createRuleModal").modal("show");

            const form = document.getElementById("createRuleForm");
            const expiresInput = form.querySelector("#expires");
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

        function setDefaultExpiresValue() {}

        $("#createRuleForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_create_rule&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#createRuleModal").modal("hide");
                        $("#botblocker-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    } else {
                        alert(bbcsRulesL10n.failed_create + response.data);
                    }
                },
            });
        });

        $("#bbcs_rules_export").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_rules",
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
                        downloadLink.download = "botblocker_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        alert(bbcsRulesL10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_pagehead_export").on("click", function (e) {
            if ($('.bbcs-tab.is-active').data('tab') !== 'Rules') return;
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_rules",
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
                        downloadLink.download = "botblocker_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        alert(bbcsRulesL10n.failed_export + response.data);
                    }
                },
            });
        });

        $("#bbcs_rules_import").on("click", function () {
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
                                action: "bbcs_import_rules",
                                rules: JSON.stringify(data),
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-rules")
                                        .DataTable()
                                        .ajax.reload();
                                } else {
                                    alert(
                                        bbcsRulesL10n.failed_import +
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

        $("#bbcs_rules_clear_all").on("click", function () {
            showConfirmClearModal(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_clear_all_rules",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                        } else {
                            alert(bbcsRulesL10n.failed_clear + response.data);
                        }
                    },
                });
            });
        });

        $('#bbcs_rules_to_php').on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_rules_to_php",
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
                if (activeTab === 'Rules') {
                    $("#createRuleModal").modal("show");
                    var form = document.getElementById("createRuleForm");
                    var expiresInput = form.querySelector("#expires");
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
                if (activeTab === 'Rules') {
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
                                        action: "bbcs_import_rules",
                                        rules: JSON.stringify(data),
                                        nonce: botblockerData.nonce,
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            showImportResultModal(response.data);
                                            $("#botblocker-rules").DataTable().ajax.reload();
                                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                                        } else {
                                            alert(bbcsRulesL10n.failed_import + response.data);
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
