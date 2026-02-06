(function ($) {
    'use strict';

    // Cloud API status
    $(document).ready(function () {
        let updated = false;
        function refreshCloudAPI() {
            $.ajax({
                url: botblockerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bbcs_refresh_cloud_api',
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        $('#bbcs_remaining_hits').val(response.data.remaining_hits);
                        $('#bbcs_remaining_days').val(response.data.remaining_days);
                        alert('Cloud API information refreshed successfully!');
                    } else {
                        alert(response.data.error || 'Failed to refresh Cloud API information.');
                    }
                },
                error: function (xhr, status, error) {
                    alert('AJAX Error: ' + error);
                },
                complete: function () {
                    updated = true;
                }
            });
        }

        $('#bbcs_refresh_cloud_api').on('click', refreshCloudAPI);

        if ($('#bbcs_remaining_hits').attr('data-should-fetch') == "true") {
            $('a.nav-link[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                if (!updated && e.target.getAttribute('href') === '#cloud-status') {
                    refreshCloudAPI();
                }
            });

            if (window.location.hash === '#cloud-status') {
                if ($('#bbcs_remaining_hits').attr('data-should-fetch') == "true") {
                    refreshCloudAPI();
                }
            }
        }
    });
 
    jQuery(document).ready(function ($) {
        $('#bbcs_fetch_cloud_api_key_btn').on('click', function (e) {
            e.preventDefault();
            var nonce = $('[name="bbcs_fetch_cloud_api_key_nonce"]').val();
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.post(ajaxurl, {
                action: 'bbcs_fetch_cloud_api_key',
                nonce: nonce
            }, function (response) {
                $btn.prop('disabled', false);
                if (response.success) location.reload();
                else alert(response.data.message);
            });
        });

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
                    if (response.success) location.reload();
                    else alert(response.data.message);
                }).always(function() {
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
                    if (response.success) location.reload();
                    else alert(response.data.message);
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            }
        });
    });

})(jQuery);