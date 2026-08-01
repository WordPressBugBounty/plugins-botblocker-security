(function ($) {
    'use strict';

    // Cloud API status
    $(document).ready(function () {
        let isAutoUpdated = false;
        let fetching = false;
        function refreshCloudAPI(isAutomaticRefresh) {
            if (fetching) return;
            if (isAutomaticRefresh && isAutoUpdated) return;
            
            fetching = true;
            $('#bbcs_refresh_cloud_api').prop('disabled', true);
            $.ajax({
                url: botblockerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bbcs_refresh_cloud_api',
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        var hits = response.data.remaining_hits;
                        var days = response.data.remaining_days;
                        $('#bbcs_remaining_hits').val(hits);
                        $('#bbcs_remaining_days').val(days);
                        $('#bbcs_stat_hits').text(Number(hits).toLocaleString());
                        $('#bbcs_stat_days').text(days);
                        if (!isAutomaticRefresh) {
                            alert(bbcsCloudApiL10n.refreshed);
                        }
                    } else {
                        if (!isAutomaticRefresh) {
                            alert(response.data.error || bbcsCloudApiL10n.failed_refresh);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    if (!isAutomaticRefresh) {
                        alert(bbcsCloudApiL10n.ajax_error + error);
                    }
                },
                complete: function () {
                    fetching = false;
                    $('#bbcs_refresh_cloud_api').prop('disabled', false);
                    if (isAutomaticRefresh) {
                        isAutoUpdated = true;
                    }
                }
            });
        }

        $('#bbcs_refresh_cloud_api').on('click', function() {
            refreshCloudAPI(false);
        });

        if ($('#bbcs_remaining_hits').attr('data-should-fetch') === "true") {
            function isCloudStatusTabActive() {
                return $('#cloud-status').hasClass('active');
            }

            if (isCloudStatusTabActive()) {
                refreshCloudAPI(true);
            }

            $('a.nav-link[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                if (!isAutoUpdated && e.target.getAttribute('href') === '#cloud-status') {
                    refreshCloudAPI(true);
                }
            });
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
                if (response.success) {
                    location.reload();
                    return;
                }
                alert(response.data.message);
                $btn.prop('disabled', false);
            }).fail(function() {
                $btn.prop('disabled', false);
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
                    if (response.success) {
                        location.reload();
                        return;
                    }
                    alert(response.data.message);
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
                    alert(response.data.message);
                    $btn.prop('disabled', false);
                }).fail(function() {
                    $btn.prop('disabled', false);
                });
            }
        });
    });

})(jQuery);