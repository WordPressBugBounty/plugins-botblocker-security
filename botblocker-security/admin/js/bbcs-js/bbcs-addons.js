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

    $(document).ready(function () {
        var installed = getParam('bbcs_installed');
        var updated = getParam('bbcs_updated');
        var deleted = getParam('bbcs_deleted');
        var error = getParam('bbcs_error');
        var errorMsg = getParam('bbcs_error_msg');

        if (installed) {
            showNotice('success', 'Add-on installed successfully.');
            activateTabById('#bbcs-installed');
        }
        if (updated) {
            showNotice('success', 'Add-on updated successfully.');
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
            if (errorMsg) { msg += ' ' + decodeURIComponent(errorMsg); }
            showNotice('danger', msg);
        }

        removeParams(['bbcs_installed','bbcs_updated','bbcs_deleted','bbcs_error','bbcs_error_msg']);

        $(document).on('submit', 'form[action$="admin-post.php"]', function(){
            var $f = $(this);
            var action = ($f.find('input[name="action"]').val() || '').toString();
            if (/^bbcs_(install|update|delete|toggle)_addon$/.test(action)) {
                attachCardOverlay($f);
            }
        });
    });
})(jQuery);
