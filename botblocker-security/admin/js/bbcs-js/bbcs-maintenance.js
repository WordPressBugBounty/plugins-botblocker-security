(function ($) {
    "use strict";

    // ── UI mode detection ───────────────────────────────────────
    // Detect new UI by presence of .bbcs-app wrapper.
    // Legacy UI uses Bootstrap .modal("show") / .modal("hide");
    // New UI uses native bbcs-modal-* show/hide via style.display.
    var isNewUI = !!document.querySelector('.bbcs-app');

    // ── Native new-UI modal helpers ─────────────────────────────
    function modalShow(id) {
        if (isNewUI) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'flex';
        } else {
            $('#' + id).modal('show');
        }
    }

    function modalHide(id) {
        if (isNewUI) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        } else {
            $('#' + id).modal('hide');
        }
    }

    // Wire up data-modal-close buttons inside modal overlays.
    // Only for new UI - legacy uses Bootstrap data-bs-dismiss.
    if (isNewUI) {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-modal-close]');
            if (btn) {
                var overlay = btn.closest('.bbcs-modal-overlay');
                if (overlay) overlay.style.display = 'none';
            }
        });
    }

    // ── Modal callbacks ────────────────────────────────────────
    function showConfirmSaltClearModal(callback) {
        modalShow('confirmSaltClearModal');
        var btn = document.getElementById('confirmSaltClearButton');
        if (isNewUI && btn) {
            var handler = function () {
                btn.removeEventListener('click', handler);
                modalHide('confirmSaltClearModal');
                if (typeof callback === 'function') callback();
            };
            btn.addEventListener('click', handler);
        } else {
            $("#confirmSaltClearButton").off("click").on("click", function () {
                modalHide('confirmSaltClearModal');
                if (typeof callback === "function") callback();
            });
        }
    }

    function showConfirmLogClearModal(callback) {
        modalShow('confirmLogClearModal');
        var btn = document.getElementById('confirmLogClearButton');
        if (isNewUI && btn) {
            var handler = function () {
                btn.removeEventListener('click', handler);
                modalHide('confirmLogClearModal');
                if (typeof callback === 'function') callback();
            };
            btn.addEventListener('click', handler);
        } else {
            $("#confirmLogClearButton").off("click").on("click", function () {
                modalHide('confirmLogClearModal');
                if (typeof callback === "function") callback();
            });
        }
    }

    function showConfirmTransientClearModal(callback) {
        modalShow('confirmTransientClearModal');
        var btn = document.getElementById('confirmTransientClearButton');
        if (isNewUI && btn) {
            var handler = function () {
                btn.removeEventListener('click', handler);
                modalHide('confirmTransientClearModal');
                if (typeof callback === 'function') callback();
            };
            btn.addEventListener('click', handler);
        } else {
            $("#confirmTransientClearButton").off("click").on("click", function () {
                modalHide('confirmTransientClearModal');
                if (typeof callback === "function") callback();
            });
        }
    }

    function showConfirmHitsClearModal(callback) {
        modalShow('confirmHitsClearModal');
        var btn = document.getElementById('confirmHitsClearButton');
        if (isNewUI && btn) {
            var handler = function () {
                btn.removeEventListener('click', handler);
                modalHide('confirmHitsClearModal');
                if (typeof callback === 'function') callback();
            };
            btn.addEventListener('click', handler);
        } else {
            $("#confirmHitsClearButton").off("click").on("click", function () {
                modalHide('confirmHitsClearModal');
                if (typeof callback === "function") callback();
            });
        }
    }

    function showConfirmRewriteRulesModal(callback) {
        modalShow('confirmRewriteRulesModal');
        var btn = document.getElementById('confirmRewriteRulesButton');
        if (isNewUI && btn) {
            var handler = function () {
                btn.removeEventListener('click', handler);
                modalHide('confirmRewriteRulesModal');
                if (typeof callback === 'function') callback();
            };
            btn.addEventListener('click', handler);
        } else {
            $("#confirmRewriteRulesButton").off("click").on("click", function () {
                modalHide('confirmRewriteRulesModal');
                if (typeof callback === "function") callback();
            });
        }
    }

    function showConfirmObjectCacheModal(callback) {
        modalShow('confirmObjectCacheModal');
        var btn = document.getElementById('confirmObjectCacheButton');
        if (isNewUI && btn) {
            var handler = function () {
                btn.removeEventListener('click', handler);
                modalHide('confirmObjectCacheModal');
                if (typeof callback === 'function') callback();
            };
            btn.addEventListener('click', handler);
        } else {
            $("#confirmObjectCacheButton").off("click").on("click", function () {
                modalHide('confirmObjectCacheModal');
                if (typeof callback === "function") callback();
            });
        }
    }

    function showDbRepairInfoModal() {
        modalShow('dbRepairInfoModal');
    }

    // ── Dynamically created modals (reinstall DB, backup) ──────
    function showConfirmClearModalReinstallDB(onConfirm) {
        if (isNewUI) {
            bbcsConfirm('Are you sure you want to re-install Database?', onConfirm);
        } else {
            // Legacy: Bootstrap modal
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
    }

    function showConfirmClearModalBackup(onConfirm) {
        if (isNewUI) {
            bbcsConfirm('Are you sure you want to make a backup?', onConfirm);
        } else {
            // Legacy: Bootstrap modal
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
    }

    // ── Event handlers ─────────────────────────────────────────
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
                            bbcsToast('success', bbcsMaintenanceL10n.db_reinstalled);
                            location.reload();
                        } else {
                            bbcsToast('error', bbcsMaintenanceL10n.failed_reinstall + response.data);
                        }
                    },
                    error: function (xhr, status, error) {
                        bbcsToast('error', bbcsMaintenanceL10n.ajax_error + error);
                    },
                });
            });
        });
    });

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
                            var tempLink = document.createElement('a');
                            tempLink.href = response.data.download_url;
                            tempLink.style.display = 'none';
                            document.body.appendChild(tempLink);
                            tempLink.click();
                            document.body.removeChild(tempLink);
                        } else {
                            bbcsToast('error', bbcsMaintenanceL10n.failed_backup + response.data.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        bbcsToast('error', bbcsMaintenanceL10n.ajax_error + error);
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
                                bbcsToast('success', bbcsMaintenanceL10n.import_success);
                                window.location.reload();
                            } else {
                                bbcsToast('error', bbcsMaintenanceL10n.failed_import + response.data.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            bbcsToast('error', bbcsMaintenanceL10n.ajax_error + error);
                        },
                    });
                }
            });
            fileInput.click();
        });
    });

    $('#bbcs-clear-cookies').on('click', function () {
        showConfirmSaltClearModal(function () {
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
                        bbcsToast('success', response.data.message || bbcsMaintenanceL10n.salt_created);
                    } else {
                        bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_salt);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    bbcsToast('error', bbcsMaintenanceL10n.operation_error);
                }
            });
        });
    });

    $('#bbcs-clear-wp-log').on('click', function () {
        showConfirmLogClearModal(function () {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_clear_wp_debug_log',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        bbcsToast('success', response.data.message || bbcsMaintenanceL10n.log_cleared);
                    } else {
                        bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_clear_log);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    bbcsToast('error', bbcsMaintenanceL10n.operation_error);
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
                    bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_get_log);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                bbcsToast('error', bbcsMaintenanceL10n.operation_error);
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
        showConfirmTransientClearModal(function () {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_clear_transients',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        bbcsToast('success', response.data.message || bbcsMaintenanceL10n.transients_cleared);
                    } else {
                        bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_clear_transients);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    bbcsToast('error', bbcsMaintenanceL10n.operation_error);
                }
            });
        });
    });

    $('#bbcs-clear-hits-database').on('click', function () {
        showConfirmHitsClearModal(function () {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_clear_hits_database',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        bbcsToast('success', response.data.message || bbcsMaintenanceL10n.visitors_cleared);
                        window.location.reload();
                    } else {
                        bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_clear_visitors);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    bbcsToast('error', bbcsMaintenanceL10n.operation_error);
                }
            });
        });
    });

    $('#bbcs-flush-rewrite-rules').on('click', function () {
        showConfirmRewriteRulesModal(function () {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_flush_rewrite_rules',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        bbcsToast('success', response.data.message || bbcsMaintenanceL10n.rewrite_flushed);
                    } else {
                        bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_flush_rewrite);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    bbcsToast('error', bbcsMaintenanceL10n.operation_error);
                }
            });
        });
    });

    $('#bbcs-flush-object-cache').on('click', function () {
        showConfirmObjectCacheModal(function () {
            $.ajax({
                url: botblockerData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bbcs_flush_object_cache',
                    nonce: botblockerData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        bbcsToast('success', response.data.message || bbcsMaintenanceL10n.cache_cleared);
                    } else {
                        bbcsToast('error', response.data.message || bbcsMaintenanceL10n.failed_clear_cache);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    bbcsToast('error', bbcsMaintenanceL10n.operation_error);
                }
            });
        });
    });

    $('#bbcs-update-asn-database').on('click', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);
        $.ajax({
            url: botblockerData.ajaxurl,
            method: 'POST',
            data: {
                action: 'bbcs_update_asn_database',
                nonce: botblockerData.nonce
            },
            success: function (response) {
                if (response && response.success) {
                    bbcsToast('success', (response.data && response.data.message) || bbcsMaintenanceL10n.asn_scheduled);
                } else {
                    bbcsToast('error', (response && response.data && response.data.message) || bbcsMaintenanceL10n.failed_schedule_asn);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                bbcsToast('error', bbcsMaintenanceL10n.asn_error);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $('#bbcs-update-rugov').on('click', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);
        $.ajax({
            url: botblockerData.ajaxurl,
            method: 'POST',
            data: {
                action: 'bbcs_update_rugov',
                nonce: botblockerData.nonce
            },
            success: function (response) {
                if (response && response.success) {
                    bbcsToast('success', (response.data && response.data.message) || response.data || bbcsMaintenanceL10n.rugov_scheduled || 'RU-Gov list update scheduled.');
                } else {
                    bbcsToast('error', (response && response.data) || (bbcsMaintenanceL10n && bbcsMaintenanceL10n.rugov_failed) || 'Failed to schedule RU-Gov list update.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                bbcsToast('error', (bbcsMaintenanceL10n && bbcsMaintenanceL10n.rugov_error) || 'Error scheduling RU-Gov list update.');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $('#bbcs-sync-llm').on('click', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);
        $.ajax({
            url: botblockerData.ajaxurl,
            method: 'POST',
            data: {
                action: 'bbcs_sync_llm_cloud',
                nonce: botblockerData.nonce
            },
            success: function (response) {
                if (response && response.success) {
                    bbcsToast('success', (response.data && response.data.message) || response.data || 'LLM providers synced successfully.');
                } else {
                    bbcsToast('error', (response && response.data) || 'LLM sync failed.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                bbcsToast('error', 'Error syncing LLM providers.');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
})(jQuery);
