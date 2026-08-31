(function ($) {
    "use strict";

    var KPI_SWAP_MS = 5000;

    function initKpiSwap() {
        var $cards = $("[data-bbcs-kpi-swap]");

        if (!$cards.length) {
            return;
        }

        var showingAlt = false;

        setInterval(function () {
            showingAlt = !showingAlt;
            $cards.toggleClass("bbcs-kpi-swap--alt", showingAlt);
        }, KPI_SWAP_MS);
    }

    $(document).ready(function () {
        initKpiSwap();

        $('#bbcs-send-email').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                url: botblockerData.ajaxurl, 
                type: 'POST',
                data: {
                    action: 'bbcs_send_email', 
                    nonce: botblockerData.nonce, 
                },
                success: function (response) {
                    if (response.success) {
                        bbcsToast('success', response.data.message); 
                    } else {
                        bbcsToast('error', bbcsDashL10n.error_prefix + response.data.message); 
                    }
                },
                error: function (xhr, status, error) {
                    bbcsToast('error', bbcsDashL10n.ajax_error + error);
                },
            });
        });

        $('#bbcs-regenerate-secret-links').on('click', function (e) {
            e.preventDefault();
            if (!window.confirm(bbcsDashL10n.regenerate_confirm)) {
                return;
            }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: botblockerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bbcs_regenerate_secret_links',
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $('input[data-url-type]').each(function () {
                            var type = $(this).data('url-type');
                            if (response.data[type]) {
                                $(this).val(response.data[type]);
                            }
                        });
                        bbcsToast('success', response.data.message);
                    } else {
                        bbcsToast('error', bbcsDashL10n.error_prefix + response.data.message);
                    }
                },
                error: function (xhr, status, error) {
                    $btn.prop('disabled', false);
                    bbcsToast('error', bbcsDashL10n.ajax_error + error);
                },
            });
        });
    });
})(jQuery);
