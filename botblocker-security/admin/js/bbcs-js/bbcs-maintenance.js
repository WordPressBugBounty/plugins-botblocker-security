(function ($) {
    "use strict";

    function showConfirmSaltClearModal(callback) {
        $("#confirmSaltClearModal").modal("show");
        $("#confirmSaltClearButton").off("click").on("click", function() {
            $("#confirmSaltClearModal").modal("hide");
            if (typeof callback === "function") {
                callback();
            }
        });
    }

    function showConfirmLogClearModal(callback) {
        $("#confirmLogClearModal").modal("show");
        $("#confirmLogClearButton").off("click").on("click", function() {
            $("#confirmLogClearModal").modal("hide");
            if (typeof callback === "function") {
                callback();
            }
        });
    }

    function showConfirmTransientClearModal(callback) {
        $("#confirmTransientClearModal").modal("show");
        $("#confirmTransientClearButton").off("click").on("click", function() {
            $("#confirmTransientClearModal").modal("hide");
            if (typeof callback === "function") {
                callback();
            }
        });
    }

    function showConfirmHitsClearModal(callback) {
        $("#confirmHitsClearModal").modal("show");
        $("#confirmHitsClearButton").off("click").on("click", function() {
            $("#confirmHitsClearModal").modal("hide");
            if (typeof callback === "function") {
                callback();
            }
        });
    }

    function showConfirmRewriteRulesModal(callback) {
        $("#confirmRewriteRulesModal").modal("show");
        $("#confirmRewriteRulesButton").off("click").on("click", function() {
            $("#confirmRewriteRulesModal").modal("hide");
            if (typeof callback === "function") {
                callback();
            }
        });
    }

    function showConfirmObjectCacheModal(callback) {
        $("#confirmObjectCacheModal").modal("show");
        $("#confirmObjectCacheButton").off("click").on("click", function() {
            $("#confirmObjectCacheModal").modal("hide");
            if (typeof callback === "function") {
                callback();
            }
        });
    }

    function showDbRepairInfoModal() {
        $("#dbRepairInfoModal").modal("show");
    }

    function showConfirmClearModalReinstallDB(onConfirm) {

        if ($("#confirmClearModal").length) {
            $("#confirmClearModal").remove();
        }

        var modal = $(
            '<div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">Re-install Database</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">Are you sure you want to re-install Database?</div>'
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

    $(document).ready(function () {

        $("#bbcs-reinstall-database").on("click", function () {
            showConfirmClearModalReinstallDB(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_database_reinstallation",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            alert("Database reinstalled successfully!");
                            location.reload();
                        } else {
                            alert(
                                "Failed to reinstall database: " + response.data
                            );
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("AJAX Error: " + error);
                    },
                });
            });
        });
    });

    function showConfirmClearModalBackup(onConfirm) {

        if ($("#confirmClearModal").length) {
            $("#confirmClearModal").remove();
        }

        var modal = $(
            '<div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearModalLabel" aria-hidden="true">'
        );
        var modalDialog = $('<div class="modal-dialog">');
        var modalContent = $('<div class="modal-content">');
        var modalHeader = $(
            '<div class="modal-header"><h5 class="modal-title" id="confirmClearModalLabel">Backup</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        );
        var modalBody = $(
            '<div class="modal-body">Are you sure you want to make a backup?</div>'
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

    $(document).ready(function () {

        $("#bbcs-backup-data-settings").on("click", function () {
            showConfirmClearModalBackup(function () {
                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_backup_data_settings",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            window.location.href = response.data.download_url;
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert("Failed backup: " + response.data.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("AJAX Error: " + error);
                    },
                });
            });
        });
    });

    $(document).ready(function () {

        $("#bbcs-import-data-settings").on("click", function () {
            var fileInput = $("<input>", {
                type: "file",
                accept: ".zip",
            }).on("change", function () {
                var file = this.files[0];
                if (file) {
                    var formData = new FormData();
                    formData.append("action", "bbcs_import_data_settings");
                    formData.append("nonce", botblockerData.nonce);
                    formData.append("zip_file", file);

                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                alert(
                                    "Import data and settings was successful!"
                                );
                                window.location.reload();
                            } else {
                                alert(
                                    "Failed import: " + response.data.message
                                );
                            }
                        },
                        error: function (xhr, status, error) {
                            alert("AJAX Error: " + error);
                        },
                    });
                }
            });
            fileInput.click();
        });
    });

    $('#bbcs-clear-cookies').on('click', function () {
        showConfirmSaltClearModal(function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_create_salt_file',
                    nonce: botblockerData.nonce,
                    bbcs_start_files: true
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message || 'Salt successfully created!');
                    } else {
                        alert(response.data.message || 'Failed to create salt file.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while performing the operation.');
                }
            });
        });
    });
    
    $('#bbcs-clear-wp-log').on('click', function () {
        showConfirmLogClearModal(function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_clear_wp_debug_log',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message || 'Log file successfully cleared!');
                    } else {
                        alert(response.data.message || 'Failed to clear log file.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while performing the operation.');
                }
            });
        });
    });
    
    $('#bbcs-download-wp-log').on('click', function () {
        $.ajax({
            url: botblockerData.ajaxurl,
            method: 'POST',
            data: {
                action: 'bbcs_download_wp_debug_log',
                nonce: botblockerData.nonce
            },
            success: function (response) {
                if (response.success && response.data.download_url) {
                    var tempLink = document.createElement('a');
                    tempLink.href = response.data.download_url;
                    tempLink.setAttribute('download', 'wordpress_debug.log');
                    tempLink.setAttribute('target', '_blank');
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                } else {
                    alert(response.data.message || 'Failed to get log file.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                alert('An error occurred while performing the operation.');
            }
        });
    });

    $('#bbcs-db-repair-info').on('click', function () {
        showDbRepairInfoModal();
    });

    $('#bbcs-site-health').on('click', function () {
        window.location.href = botblockerData.adminUrl + 'site-health.php'; 
    });

    $('#bbcs-clear-transients').on('click', function () {
        showConfirmTransientClearModal(function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_clear_transients',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message || 'Transients successfully cleared!');
                    } else {
                        alert(response.data.message || 'Failed to clear transients.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while performing the operation.');
                }
            });
        });
    });

    $('#bbcs-clear-hits-database').on('click', function () {
        showConfirmHitsClearModal(function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_clear_hits_database',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message || 'Visitors data successfully cleared!');
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Failed to clear visitors data.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while performing the operation.');
                }
            });
        });
    });
    
    $('#bbcs-flush-rewrite-rules').on('click', function () {
        showConfirmRewriteRulesModal(function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_flush_rewrite_rules',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message || 'Rewrite rules successfully flushed!');
                    } else {
                        alert(response.data.message || 'Failed to flush rewrite rules.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while performing the operation.');
                }
            });
        });
    });

    $('#bbcs-flush-object-cache').on('click', function () {
        showConfirmObjectCacheModal(function() {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_flush_object_cache',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message || 'Object cache successfully cleared!');
                    } else {
                        alert(response.data.message || 'Failed to clear object cache.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while performing the operation.');
                }
            });
        });
    });
})(jQuery);
