(function ($) {
    "use strict";

    $(document).ready(function () {
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
                        alert('Error: ' + response.data.message); 
                    }
                },
                error: function (xhr, status, error) {
                    alert('AJAX Error: ' + error);
                },
            });
        });
    });
})(jQuery);
