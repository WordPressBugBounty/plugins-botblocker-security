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
                        alert(response.data.message); 
                    } else {
                        alert(bbcsDashL10n.error_prefix + response.data.message); 
                    }
                },
                error: function (xhr, status, error) {
                    alert(bbcsDashL10n.ajax_error + error);
                },
            });
        });
    });
})(jQuery);
