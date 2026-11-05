(function ($) {
    'use strict';

    $(document).on('click', '#bbcs_pro_buy .bbcs-seg-opt', function () {
        var $opt = $(this);
        $opt.addClass('is-active').attr('aria-pressed', 'true')
            .siblings('.bbcs-seg-opt').removeClass('is-active').attr('aria-pressed', 'false');
        $('#bbcs_pro_buy').toggleClass('is-annual', $opt.data('value') === 'annual');
    });

    // Cloud API status
    $(document).ready(function () {
        $('#bbcs_refresh_cloud_api').on('click', function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) return;
            $btn.prop('disabled', true).addClass('is-busy');
            window.bbcsProRemaining.refresh()
                .done(function (response) {
                    if (response && response.success) {
                        bbcsToast('success', bbcsCloudApiL10n.refreshed);
                    } else {
                        bbcsToast('error', (response && response.data && response.data.error) || bbcsCloudApiL10n.failed_refresh);
                    }
                })
                .fail(function (xhr, status, error) {
                    bbcsToast('error', bbcsCloudApiL10n.ajax_error + error);
                })
                .always(function () {
                    $btn.prop('disabled', false).removeClass('is-busy');
                });
        });
    });
 
    jQuery(document).on('click', '#bbcs_request_license_push_btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).addClass('is-busy');
        var nonce = $('[name="bbcs_request_license_push_nonce"]').val();
        $.post(ajaxurl, {
            action: 'bbcs_request_license_push',
            nonce: nonce
        }, function (response) {
            if (response && response.success) {
                location.reload();
                return;
            }
            var data = (response && response.data) || {};
            var message = data.message || bbcsCloudApiL10n.push_failed;
            bbcsToast('error', message);
            $btn.prop('disabled', false).removeClass('is-busy');
        }).fail(function (xhr, status, error) {
            bbcsToast('error', bbcsCloudApiL10n.ajax_error + error);
            $btn.prop('disabled', false).removeClass('is-busy');
        });
    });

    jQuery(document).ready(function ($) {
        $('#bbcs_toggle_cloud_api_btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var isActive = $btn.attr('data-is-active') === '1';
            $btn.prop('disabled', true);

            if (isActive) {
                // Disconnect
                var nonce = $('[name="bbcs_deactivate_cloud_api_nonce"]').val();
                $.post(ajaxurl, {
                    action: 'bbcs_deactivate_cloud_api',
                    nonce: nonce
                }, function (response) {
                    if (response.success) {
                        location.reload();
                        return;
                    }
                    bbcsToast('error', response.data.message);
                    $btn.prop('disabled', false);
                }).fail(function() {
                    $btn.prop('disabled', false);
                });
            } else {
                // Connect
                var apiKey = $('#bbcs_cloud_api_key').val();
                var nonce = $('[name="bbcs_connect_cloud_api_nonce"]').val();
                $.post(ajaxurl, {
                    action: 'bbcs_connect_cloud_api',
                    api_key: apiKey,
                    nonce: nonce
                }, function (response) {
                    if (response.success) {
                        location.reload();
                        return;
                    }
                    bbcsToast('error', response.data.message);
                    $btn.prop('disabled', false);
                }).fail(function() {
                    $btn.prop('disabled', false);
                });
            }
        });
    });

})(jQuery);