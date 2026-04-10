(function ($) {
    "use strict";
 
    var isProcessingRule = false;
    // local flag for loading the rules table
    var rulesTableLoading = false;

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
                                '<button class="btn btn-sm btn-default bbcs-actions-b edit-rule" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Edit" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-edit"></i></button> ' +
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-rule"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-trash-can"></i></button> ' +
                                '<button class="btn btn-sm bbcs-actions-b ' +
                                (row.disable == 0
                                    ? "btn-default"
                                    : "btn-warning") +
                                ' toggle-rule"  data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Toggle On/Off" data-id="' +
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
                    $(row).css(
                        "background-color",
                        data.disable == 0
                            ? "rgba(0, 255, 0, 0.1)"
                            : "rgba(255, 0, 0, 0.1)"
                    );
                },
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

                    api.columns.adjust();
                },
            });

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
            '<div class="modal-header"><h5 class="modal-title" id="importResultModalLabel">Import Result</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
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

        $("#importResultModal").modal("show");
    }

    function showConfirmClearModal(onConfirm) {
        var modal = $(
            '<div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">Clear All Rules</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">Are you sure you want to remove all rules?</div>'
        );
        var modalFooter = $(
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button><button type="button" class="btn btn-primary" id="confirmClearButton">Yes</button></div>'
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
                alert("Invalid JSON file: " + err.message);
            }
        };
        reader.readAsText(file);
    }

    $(document).ready(function () {
        initializeRulesTable();

        $("#priority").on("input", function () {
            $("#priorityValue").val(this.value);
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
                    } else {
                        alert("Failed to update rule: " + response.data);
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
                        $("#priorityValue").val(data.priority);
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
                        alert("Failed to load rule details: " + response.data);
                    }
                },
            });
        });

        $("#botblocker-rules").on("click", ".delete-rule", function () {
            var id = $(this).data("id");
            if (confirm("Are you sure you want to delete this rule?")) {
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
                    } else {
                        alert("Failed to create rule: " + response.data);
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
                        alert("Failed to export rules: " + response.data);
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
                                        "Failed to import rules: " +
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
                        } else {
                            alert("Failed to clear rules: " + response.data);
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
    });
})(jQuery);