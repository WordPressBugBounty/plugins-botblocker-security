(function ($) {
    "use strict";

    function getParam(name) {
        var url = new URL(window.location.href);
        return url.searchParams.get(name);
    }

    function removeParams(keys) {
        var url = new URL(window.location.href);
        var changed = false;
        keys.forEach(function (k) { if (url.searchParams.has(k)) { url.searchParams.delete(k); changed = true; } });
        if (changed) {
            window.history.replaceState({}, document.title, url.toString());
        }
    }

    function showNotice(type, message) {
        var $container = $('.bbcs-settings-content');
        if (!$container.length) { $container = $('.bbcs-wrap'); }
        var cls = type === 'success' ? 'bbcs-green-card' : (type === 'warning' ? 'bbcs-amber-card' : 'bbcs-card');
        var $notice = $('<div/>', {
            'class': 'bbcs-card bbcs-card-pad ' + cls + ' bbcs-mb-3',
            'role': 'alert'
        }).html('<div class="bbcs-row bbcs-g-2"><span class="bbcs-dot"></span><span class="bbcs-fs-sm">' + $('<span>').text(message).html() + '</span></div>');

        $container.prepend($notice);
        setTimeout(function () { $notice.fadeOut(300, function () { $(this).remove(); }); }, 7000);
    }

    function activateTabByName(tabId) {
        // Sidebar nav activation: find matching snav item and click it
        var $item = $('.bbcs-snav-item[data-snav-tab="' + tabId + '"]');
        if ($item.length && !$item.hasClass('is-active')) {
            $item.trigger('click');
        }
    }

    function setUploadPanel(open) {
        var $panel = $('#bbcs-upload-section');
        var $button = $('#bbcs-toggle-upload');
        if (!$panel.length || !$button.length) return;

        $button.attr('aria-expanded', open);

        if (open) {
            $panel.prop('hidden', false).hide().slideDown(160);
        } else {
            $panel.slideUp(140, function () { $panel.prop('hidden', true); });
        }
    }

    $(document).ready(function () {
        var installed = getParam('bbcs_installed');
        var uploaded = getParam('bbcs_uploaded');
        var updated = getParam('bbcs_updated');
        var updatedAll = getParam('bbcs_updated_all');
        var deleted = getParam('bbcs_deleted');
        var error = getParam('bbcs_error');
        var errorMsg = getParam('bbcs_error_msg');
        var requiresCore = getParam('bbcs_requires_core');
        var L = window.bbcsAddonsL10n || {};

        if (installed) {
            showNotice('success', L.installed || 'Add-on installed successfully.');
            activateTabByName('Marketplace');
        }
        if (uploaded) {
            showNotice('success', L.uploaded || 'Add-on package uploaded. Find it below, then activate it when ready.');
            activateTabByName('Marketplace');
        }
        if (updatedAll) {
            showNotice('success', L.updated_all || 'All add-ons have been updated.');
            activateTabByName('Marketplace');
        }
        if (updated && !requiresCore) {
            showNotice('success', L.updated || 'Add-on updated successfully.');
            activateTabByName('Marketplace');
        }
        if (requiresCore) {
            var msg = (L.requires_core_msg || 'Add-on was updated but not reactivated - it requires BotBlocker version %s or higher. Please update the plugin.').replace('%s', requiresCore);
            showNotice('warning', msg);
            activateTabByName('Marketplace');
        }
        if (deleted) {
            showNotice('warning', L.deleted || 'Add-on deleted.');
            activateTabByName('Marketplace');
        }
        if (error) {
            var msg = L.operation_failed || 'Operation failed.';
            if (error === 'invalid') msg = L.invalid || 'The add-on is invalid or broken.';
            if (error === 'install_args') msg = L.install_args || 'Installation arguments are missing.';
            if (error === 'download') msg = L.download || 'Failed to download the add-on package.';
            if (error === 'unzip') msg = L.unzip || 'Failed to unpack the add-on package.';
            if (error === 'fs_unavailable') msg = L.fs_unavailable || 'Filesystem API is not available.';
            if (error === 'url_not_allowed') msg = L.url_not_allowed || 'The add-on download URL is not allowed.';
            if (error === 'upload_missing') msg = L.upload_missing || 'Choose an add-on ZIP package first.';
            if (error === 'upload_failed') msg = L.upload_failed || 'The add-on upload failed.';
            if (error === 'upload_untrusted') msg = L.upload_untrusted || 'The uploaded file was not accepted by WordPress.';
            if (error === 'zip_missing') msg = L.zip_missing || 'Add-on package is missing or unreadable.';
            if (error === 'zip_extension') msg = L.zip_extension || 'The add-on package must be a ZIP file.';
            if (error === 'zip_too_large') msg = L.zip_too_large || 'The add-on package is too large.';
            if (error === 'zip_open') msg = L.zip_open || 'The add-on package cannot be opened.';
            if (error === 'zip_file_count') msg = L.zip_file_count || 'The add-on package has an invalid file count.';
            if (error === 'zip_unsafe_path') msg = L.zip_unsafe_path || 'The package contains an unsafe file path.';
            if (error === 'zip_entry_too_large') msg = L.zip_entry_too_large || 'The package contains an oversized file.';
            if (error === 'extract_missing') msg = L.extract_missing || 'The temporary extraction folder is missing.';
            if (error === 'package_root') msg = L.package_root || 'The package must contain exactly one root folder.';
            if (error === 'package_slug') msg = L.package_slug || 'The package root folder must be a valid slug.';
            if (error === 'package_invalid') msg = L.package_invalid || 'The package does not match the BotBlocker add-on contract.';
            if (error === 'pro_required') msg = L.pro_required || 'Official BotBlocker add-ons require BotBlocker PRO. Custom ZIP add-ons can still be uploaded.';
            if (error === 'requires_core_missing') msg = L.requires_core_missing || 'The package must declare Requires-Core.';
            if (error === 'slug_mismatch') msg = L.slug_mismatch || 'The package slug does not match the requested add-on.';
            if (error === 'requires_php') msg = L.requires_php || 'This add-on requires a newer PHP version.';
            if (error === 'file_mods_disabled') msg = L.file_mods_disabled || 'File modifications are disabled for this WordPress installation.';
            if (error === 'tmp_failed') msg = L.tmp_failed || 'Failed to create a temporary add-on folder.';
            if (error === 'move_source') msg = L.move_source || 'The validated add-on source is missing.';
            if (error === 'backup_failed') msg = L.backup_failed || 'Failed to backup the existing add-on.';
            if (error === 'move_failed') msg = L.move_failed || 'Failed to install the add-on package.';
            if (/^(upload|zip|extract|package|slug_mismatch|requires_php|requires_core_missing|file_mods_disabled|tmp_failed|move_source|backup_failed|move_failed)/.test(error)) {
                setUploadPanel(true);
            }
            if (error === 'requires_core') {
                msg = L.requires_core_newer || 'This add-on requires a newer version of BotBlocker.';
                if (errorMsg) { msg += ' ' + (L.required_version || 'Required:') + ' ' + decodeURIComponent(errorMsg) + '.'; }
                msg += ' ' + (L.update_plugin || 'Please update the plugin first.');
                errorMsg = null;
            }
            if (errorMsg) { msg += ' ' + decodeURIComponent(errorMsg); }
            showNotice('danger', msg);
        }

        removeParams(['bbcs_installed','bbcs_uploaded','bbcs_addon','bbcs_updated','bbcs_updated_all','bbcs_deleted','bbcs_error','bbcs_error_msg','bbcs_requires_core']);

        /* ── Highlight addon card from command palette click ── */
        var highlight = getParam('highlight');
        if (highlight) {
            removeParams(['highlight']);
            var $card = $('.bbcs-addon[data-addon-slug="' + highlight.replace(/[^a-zA-Z0-9_-]/g, '') + '"]');
            if ($card.length) {
                // Activate the right sidebar tab (Marketplace or Installed)
                var $tabpanel = $card.closest('.bbcs-tabpanel');
                if ($tabpanel.length) {
                    var tabId = $tabpanel.data('tabpanel');
                    activateTabByName(tabId);
                }
                $card.addClass('bbcs-addon--highlight');
                setTimeout(function () {
                    $('html, body').animate({ scrollTop: $card.offset().top - 80 }, 300);
                }, 150);
                setTimeout(function () {
                    $card.removeClass('bbcs-addon--highlight');
                }, 5000);
            }
        }

        // Upload panel toggle
        $(document).on('click', '#bbcs-toggle-upload', function () {
            var $panel = $('#bbcs-upload-section');
            setUploadPanel($panel.prop('hidden'));
        });

        // File name display and button toggle
        $(document).on('change', '#bbcs_addon_zip', function () {
            var hasFile = this.files && this.files.length > 0;
            var fileName = hasFile ? this.files[0].name : (L.no_file_selected || 'No file selected');
            var $nameEl = $('#bbcs-zip-name');
            var $btnEl = $('#bbcs-install-package-btn');
            
            if ($nameEl.length) {
                $nameEl.text(fileName);
            }
            if ($btnEl.length) {
                $btnEl.prop('disabled', !hasFile);
            }
        });

        /* ── Back to Marketplace (no page refresh) ── */
        $(document).on('click', '[data-bbcs-back-to-marketplace]', function (e) {
            e.preventDefault();
            // Hide all tabpanels
            $('.bbcs-tabpanel').attr('hidden', true).attr('aria-hidden', 'true');
            // Show marketplace
            var $market = $('[data-tabpanel="Marketplace"]');
            if ($market.length) {
                $market.removeAttr('hidden').removeAttr('aria-hidden');
            }
            // Remove active from snav items
            $('.bbcs-snav-item').removeClass('is-active').attr('aria-current', 'false');
            // Update toggle label
            var $toggle = $('.bbcs-snav-toggle');
            if ($toggle.length) {
                $toggle.find('.bbcs-snav-toggle-label').text(L.marketplace || 'Marketplace');
                $toggle.find('.bbcs-snav-toggle-icon use').attr('href', '#bbcs-i-store');
            }
            // Clear hash
            try { history.replaceState(null, '', window.location.pathname + window.location.search); } catch (_) {}
        });

        // Loading overlay on addon form submit
        $(document).on('submit', 'form[action$="admin-post.php"]', function () {
            var $f = $(this);
            var action = ($f.find('input[name="action"]').val() || '').toString();
            if (/^bbcs_(install|update|delete|toggle|upload)_addon$/.test(action) || action === 'bbcs_update_all_addons') {
                var $card = $f.closest('.bbcs-addon');
                if ($card.length) {
                    $card.css({ opacity: 0.5, pointerEvents: 'none' });
                    $card.find('button').prop('disabled', true);
                }
            }
        });

        loadMarketCatalog();
    });

    function updateUpdatesButton(count) {
        var $btn = $('#bbcs-update-all');
        if (!$btn.length) { return; }
        count = parseInt(count, 10) || 0;
        if (count > 0) {
            var tpl = (window.bbcsAddonsL10n && bbcsAddonsL10n.update_all) || 'Update All (%d)';
            $btn.removeAttr('hidden').text(tpl.replace('%d', String(count)));
        } else {
            $btn.attr('hidden', true);
        }
    }

    function patchInstalledCards(market) {
        if (!market || !market.length) { return; }
        market.forEach(function (item) {
            if (!item || !item.is_installed || !item.slug) { return; }
            var $card = $('.bbcs-addon[data-addon-slug="' + item.slug.replace(/[^a-zA-Z0-9_-]/g, '') + '"]');
            if (!$card.length) { return; }
            if (item.update_avail) {
                if (!$card.find('.bbcs-tag--green').filter(function () {
                    return $(this).hasClass('bbcs-tag--update');
                }).length) {
                    $card.find('.bbcs-addon-head-actions').append(
                        '<span class="bbcs-tag bbcs-tag--green bbcs-tag--update">' + $('<span>').text((window.bbcsAddonsL10n && bbcsAddonsL10n.update_tag) || 'Update').html() + '</span>'
                    );
                }
            }
            if (item.remote_ver) {
                var $ver = $card.find('.bbcs-addon-ver');
                if ($ver.length && !$ver.hasClass('bbcs-ver-patched')) {
                    var localVer = ($ver.text().match(/v([^\s]+)/) || [])[1] || '';
                    if (localVer && localVer !== item.remote_ver) {
                        $ver.addClass('bbcs-ver-patched')
                            .append('<span class="bbcs-dim"> &rarr; v' + $('<span>').text(item.remote_ver).html() + '</span>');
                    }
                }
            }
        });
    }

    function loadMarketCatalog() {
        var $grid = $('#bbcs-market-grid');
        if (!$grid.length || String($grid.attr('data-bbcs-market-lazy')) !== '1') { return; }
        if (typeof botblockerData === 'undefined' || !botblockerData.ajaxurl) { return; }

        $.ajax({
            url: botblockerData.ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'bbcs_load_market',
                nonce: botblockerData.nonce
            }
        }).done(function (res) {
            $grid.find('.bbcs-addon-skeleton').remove();
            $('#bbcs-market-catalog').remove();

            if (!res || !res.success) {
                showNotice('warning', (window.bbcsAddonsL10n && bbcsAddonsL10n.catalog_error) || 'Failed to load add-on catalog.');
                return;
            }

            var data = res.data || {};
            if (data.message) {
                var $notice = $('#bbcs-market-notice');
                if ($notice.length) {
                    $notice.removeAttr('hidden').addClass('bbcs-card bbcs-card-pad bbcs-amber-card bbcs-mb-3').text(data.message);
                } else {
                    showNotice('warning', data.message);
                }
            }

            if (data.catalog_html) {
                $grid.append(data.catalog_html);
            } else if (data.catalog_status === 'unavailable') {
                showNotice('warning', data.message || ((window.bbcsAddonsL10n && bbcsAddonsL10n.catalog_unavailable) || 'Add-on catalog is currently unavailable.'));
            }

            patchInstalledCards(data.market);
            updateUpdatesButton(data.updates_count);
        }).fail(function () {
            $grid.find('.bbcs-addon-skeleton').remove();
            showNotice('warning', (window.bbcsAddonsL10n && bbcsAddonsL10n.catalog_error) || 'Failed to load add-on catalog.');
        });
    }
})(jQuery);
