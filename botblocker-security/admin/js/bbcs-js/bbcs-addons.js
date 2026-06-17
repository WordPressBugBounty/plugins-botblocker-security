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
        var $cardBody = $("#bbcs-market").closest('.card').find('.card-body').first();
        if (!$cardBody.length) { $cardBody = $('.wrap'); }
        var cls = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
        var $notice = $('<div/>', { 'class': 'alert ' + cls + ' bbcs-mb-16', 'role': 'alert' }).text(message);
        $cardBody.prepend($notice);
        setTimeout(function(){ $notice.fadeOut(300, function(){ $(this).remove(); }); }, 7000);
    }

    function activateTabById(id) {
        var $link = $('a[data-bs-toggle="tab"][href="' + id + '"]');
        if ($link.length) {
            if (window.bootstrap && bootstrap.Tab) {
                var tab = new bootstrap.Tab($link.get(0));
                tab.show();
            } else {
                $link.trigger('click');
            }
        }
    }

    function attachCardOverlay($form) {
        var $card = $form.closest('.card');
        if (!$card.length) return;
        var $overlay = $('<div class="bbcs-card-overlay"/>').append(
            '<div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="visually-hidden">Loading...</span></div>'
        );
        $card.append($overlay);
        $card.find('button').prop('disabled', true);
    }

    function setUploadPanel(open) {
        var $panel = $('#bbcs-addon-upload-panel');
        var $button = $('[data-bbcs-toggle-upload]');
        if (!$panel.length || !$button.length) return;

        $button.attr('aria-expanded', open ? 'true' : 'false');
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

        if (installed) {
            showNotice('success', 'Add-on installed successfully.');
            activateTabById('#bbcs-installed');
        }
        if (uploaded) {
            showNotice('success', 'Add-on package uploaded. Review it in the Installed tab, then activate it when ready.');
            activateTabById('#bbcs-installed');
        }
        if (updatedAll) {
            showNotice('success', 'All add-ons have been updated.');
            activateTabById('#bbcs-installed');
        }
        if (updated && !requiresCore) {
            showNotice('success', 'Add-on updated successfully.');
            activateTabById('#bbcs-installed');
        }
        if (requiresCore) {
            showNotice('warning', 'Add-on was updated but not reactivated - it requires BotBlocker version ' + requiresCore + ' or higher. Please update the plugin.');
            activateTabById('#bbcs-installed');
        }
        if (deleted) {
            showNotice('warning', 'Add-on deleted.');
            activateTabById('#bbcs-installed');
        }
        if (error) {
            var msg = 'Operation failed.';
            if (error === 'invalid') msg = 'The add-on is invalid or broken.';
            if (error === 'install_args') msg = 'Installation arguments are missing.';
            if (error === 'download') msg = 'Failed to download the add-on package.';
            if (error === 'unzip') msg = 'Failed to unpack the add-on package.';
            if (error === 'fs_unavailable') msg = 'Filesystem API is not available.';
            if (error === 'url_not_allowed') msg = 'The add-on download URL is not allowed.';
            if (error === 'upload_missing') msg = 'Choose an add-on ZIP package first.';
            if (error === 'upload_failed') msg = 'The add-on upload failed.';
            if (error === 'upload_untrusted') msg = 'The uploaded file was not accepted by WordPress.';
            if (error === 'zip_missing') msg = 'Add-on package is missing or unreadable.';
            if (error === 'zip_extension') msg = 'The add-on package must be a ZIP file.';
            if (error === 'zip_too_large') msg = 'The add-on package is too large.';
            if (error === 'zip_open') msg = 'The add-on package cannot be opened.';
            if (error === 'zip_file_count') msg = 'The add-on package has an invalid file count.';
            if (error === 'zip_unsafe_path') msg = 'The package contains an unsafe file path.';
            if (error === 'zip_entry_too_large') msg = 'The package contains an oversized file.';
            if (error === 'extract_missing') msg = 'The temporary extraction folder is missing.';
            if (error === 'package_root') msg = 'The package must contain exactly one root folder.';
            if (error === 'package_slug') msg = 'The package root folder must be a valid slug.';
            if (error === 'package_invalid') msg = 'The package does not match the BotBlocker add-on contract.';
            if (error === 'pro_required') msg = 'Official BotBlocker add-ons require BotBlocker PRO. Custom ZIP add-ons can still be uploaded.';
            if (error === 'requires_core_missing') msg = 'The package must declare Requires-Core.';
            if (error === 'slug_mismatch') msg = 'The package slug does not match the requested add-on.';
            if (error === 'requires_php') msg = 'This add-on requires a newer PHP version.';
            if (error === 'file_mods_disabled') msg = 'File modifications are disabled for this WordPress installation.';
            if (error === 'tmp_failed') msg = 'Failed to create a temporary add-on folder.';
            if (error === 'move_source') msg = 'The validated add-on source is missing.';
            if (error === 'backup_failed') msg = 'Failed to backup the existing add-on.';
            if (error === 'move_failed') msg = 'Failed to install the add-on package.';
            if (/^(upload|zip|extract|package|slug_mismatch|requires_php|requires_core_missing|file_mods_disabled|tmp_failed|move_source|backup_failed|move_failed)/.test(error)) {
                setUploadPanel(true);
            }
            if (error === 'requires_core') {
                msg = 'This add-on requires a newer version of BotBlocker.';
                if (errorMsg) { msg += ' Required: ' + decodeURIComponent(errorMsg) + '.'; }
                msg += ' Please update the plugin first.';
                errorMsg = null;
            }
            if (errorMsg) { msg += ' ' + decodeURIComponent(errorMsg); }
            showNotice('danger', msg);
        }

        removeParams(['bbcs_installed','bbcs_uploaded','bbcs_addon','bbcs_updated','bbcs_updated_all','bbcs_deleted','bbcs_error','bbcs_error_msg','bbcs_requires_core']);

        $(document).on('click', '[data-bbcs-toggle-upload]', function () {
            var $panel = $('#bbcs-addon-upload-panel');
            setUploadPanel($panel.prop('hidden'));
        });

        $(document).on('change', '#bbcs_addon_zip', function () {
            var fileName = this.files && this.files.length ? this.files[0].name : 'No file selected';
            $('[data-bbcs-upload-file-name]').text(fileName);
        });

        $(document).on('submit', 'form[action$="admin-post.php"]', function(){
            var $f = $(this);
            var action = ($f.find('input[name="action"]').val() || '').toString();
            if (/^bbcs_(install|update|delete|toggle|upload)_addon$/.test(action) || action === 'bbcs_update_all_addons') {
                attachCardOverlay($f);
            }
        });
    });
})(jQuery);
