(function ($) {
    "use strict";

    // local flag for loading the table
    var ipv6TableLoading = false;

    // debounce / throttle params
    var switchDebounceMs = 200; // minimum interval between switches
    var _lastSwitchTs = 0;

    // Global interception before switching (show.bs.tab) — can be canceled
    $(document).on('show.bs.tab', 'a[data-bs-toggle="tab"]', function(e){
      var now = Date.now();
      if (now - _lastSwitchTs < switchDebounceMs) {
        // Too fast — cancel it
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
      loading = loading || ipv6TableLoading;
      if (loading) {
        e.preventDefault();
        // You may briefly show a tooltip or indicator — prevent switching
        // Example: quickly highlight the active tab so the user understands what we’re waiting for
        var activeTab = $('a[data-bs-toggle="tab"].active');
        activeTab && activeTab.addClass('bbcs-tab-wait');
        setTimeout(function(){ activeTab && activeTab.removeClass('bbcs-tab-wait'); }, 400);
        return;
      }
      _lastSwitchTs = now;
    });

    // Helper: overlay functions for visually blocking the tab when the table is loading
    // function showLoadingOverlayForIpv6() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     if ($pane.find('.bbcs-loading-overlay').length) return;
    //     var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //     $pane.css('position','relative'); // ensure positioning
    //     $pane.append(overlay);
    // }
    // function hideLoadingOverlayForIpv6() {
    //     var $pane = $('#botblocker-path').closest('.tab-pane');
    //     if (!$pane.length) return;
    //     $pane.find('.bbcs-loading-overlay').remove();
    // }
    

    function loadIpv6RulesTable() {
        if (!$.fn.DataTable.isDataTable("#botblocker-ipv6-rules")) {
            var table = $("#botblocker-ipv6-rules").DataTable({
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
                        d.action = "bbcs_get_botblocker_ipv6_rules";
                        d.nonce = botblockerData.nonce;
                    },
                    beforeSend: function(jqXHR, settings) {
                        ipv6TableLoading = true;
                        // showLoadingOverlayForIpv6();
                    },
                    complete: function(jqXHR, textStatus) {
                        ipv6TableLoading = false;
                        // hideLoadingOverlayForIpv6();
                    }
                },
                columns: [
                    { data: "id", visible: false },
                    { data: "priority", width: "50px" },
                    { data: "ip", width: "80px" },
                    { data: "rule", width: "80px" },
                    { data: "expires", width: "100px" },
                    { data: "comment", width: "100px" },
                    {
                        data: null,
                        width: "100px",
                        render: function (data, type, row) {
                            return (
                                '<button class="btn btn-sm btn-default bbcs-actions-b edit-ipv6-rule" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Edit" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-edit"></i></button> ' +
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-ipv6-rule" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete" data-id="' +
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
                createdRow: function (row, data, dataIndex) {
                    $(row).css(
                        "background-color",
                        data.rule === "allow"
                            ? "rgba(0, 255, 0, 0.1)"
                            : "rgba(255, 0, 0, 0.1)"
                    );
                },
            });
            table.draw();
            table.columns.adjust();
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
        $('a[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
            var target = $(e.target).attr("href");
            if (target === "#bbcs_IPv6_list") {
                loadIpv6RulesTable();
            }
        });

        $("#priority").on("input", function () {
            $("#priorityValue").val(this.value);
        });

        $("#botblocker-ipv6-rules").on("click", ".edit-ipv6-rule", function () {
            var id = $(this).data("id");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_get_ipv6_rule_details",
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
                    
                        $("#editIPv6Form").find('[name="id"]').val(data.id);
                        $("#editIPv6Form")
                            .find('[name="priority"]')
                            .val(data.priority);
                        $("#editIPv6Form").find('[name="ip"]').val(data.search);
                        $("#editIPv6Form")
                            .find('[name="comment"]')
                            .val(data.comment);
                        $("#editIPv6Form").find('[name="rule"]').val(data.rule);
                        $("#editIPv6Form")
                            .find('[name="expires"]')
                            .val(expiresFormattedDate);
                        $("#editIPv6Modal").modal("show");
                    } else {
                        alert(
                            "Failed to load IPv6 rule details: " + response.data
                        );
                    }
                },
            });
        });

        $("#editIPv6Form").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_update_ipv6_rule&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#editIPv6Modal").modal("hide");
                        $("#botblocker-ipv6-rules").DataTable().ajax.reload();
                    } else {
                        alert("Failed to update IPv6 rule: " + response.data);
                    }
                },
            });
        });

        $("#botblocker-ipv6-rules").on(
            "click",
            ".delete-ipv6-rule",
            function () {
                var id = $(this).data("id");
                if (
                    confirm("Are you sure you want to delete this IPv6 rule?")
                ) {
                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: {
                            action: "bbcs_delete_ipv6_rule",
                            id: id,
                            nonce: botblockerData.nonce,
                        },
                        success: function (response) {
                            if (response.success) {
                                $("#botblocker-ipv6-rules")
                                    .DataTable()
                                    .ajax.reload();
                            }
                        },
                    });
                }
            }
        );

        $("#bbcs_ipv6_add").on("click", function () {
            $("#addIPv6Modal").modal("show");

            const form = document.getElementById("addIPv6Form");
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

        $("#addIPv6Form").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data:
                    $(this).serialize() +
                    "&action=bbcs_create_ipv6_rule&nonce=" +
                    botblockerData.nonce,
                success: function (response) {
                    if (response.success) {
                        $("#addIPv6Modal").modal("hide");
                        $("#botblocker-ipv6-rules").DataTable().ajax.reload();
                    } else {
                        alert("Failed to create IPv6 rule: " + response.data);
                    }
                },
            });
        });

        $("#bbcs_ipv6_export").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_export_ipv6_rules",
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
                        downloadLink.download = "botblocker_ipv6_rules.json";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        alert("Failed to export IPv6 rules: " + response.data);
                    }
                },
            });
        });

        $("#bbcs_ipv6_import").on("click", function () {
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
                                action: "bbcs_import_ipv6_rules",
                                rules: JSON.stringify(data),
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-ipv6-rules")
                                        .DataTable()
                                        .ajax.reload();
                                } else {
                                    alert(
                                        "Failed to import IPv6 rules: " +
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

        $("#bbcs_ipv6_clear_all").on("click", function () {
            showConfirmClearModal(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_clear_all_ipv6_rules",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#botblocker-ipv6-rules")
                                .DataTable()
                                .ajax.reload();
                        } else {
                            alert(
                                "Failed to clear IPv6 rules: " + response.data
                            );
                        }
                    },
                });
            });
        });

        $("#bbcs_ipv6_import_white").on("click", function () {
            importIPv6List("whitelist");
        });

        $("#bbcs_ipv6_import_black").on("click", function () {
            importIPv6List("blacklist");
        });

        function importIPv6List(listType) {
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
                                action: "bbcs_import_ipv6_" + listType,
                                file_content: fileContent,
                                nonce: botblockerData.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    showImportResultModal(response.data);
                                    $("#botblocker-ipv6-rules")
                                        .DataTable()
                                        .ajax.reload();
                                } else {
                                    alert(
                                        "Failed to import IPv6 " +
                                            listType +
                                            ": " +
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

        $('#bbcs_ipv6_to_php').on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_ipv6_to_php",
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    alert(response.data);
                },
            });
        });
    });
})(jQuery);