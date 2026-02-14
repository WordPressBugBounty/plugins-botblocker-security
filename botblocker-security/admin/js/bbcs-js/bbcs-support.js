(function ($) {
    "use strict";

    $(document).ready(function () {
        const $btn = $('#bbcs-support-btn');
        const $panel = $('#bbcs-support-panel');
        const $closeBtn = $('#bbcs-close-btn');
        const $form = $('#bbcs-support-form');
        const $message = $('#bbcs-message');

        $btn.on('click', function (e) {
            e.stopPropagation();
            $panel.toggleClass('bbcs-hidden');
        });

        $closeBtn.on('click', function (e) {
            e.stopPropagation();
            $panel.addClass('bbcs-hidden');
        });

        $panel.on('click', function (e) {
            e.stopPropagation();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            
            const $submitBtn = $form.find('.bbcs-submit-btn');
            const formData = new FormData(this);
            formData.append('action', 'bbcs_send_support');
            formData.append('nonce', botblockerSupportData.nonce);

            $submitBtn.prop('disabled', true).text(botblockerSupportData.i18n.sending);
            $message.addClass('bbcs-hidden').removeClass('bbcs-success bbcs-error');

            $.ajax({
                url: botblockerSupportData.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $submitBtn.prop('disabled', false).text(botblockerSupportData.i18n.send);
                    $message.removeClass('bbcs-hidden');

                    if (response.success) {
                        $message.addClass('bbcs-success').text(response.data.message);
                        $form[0].reset();
                    } else {
                        $message.addClass('bbcs-error').text(response.data.message);
                    }
                },
                error: function (xhr, status, error) {
                    $submitBtn.prop('disabled', false).text(botblockerSupportData.i18n.send);
                    $message.removeClass('bbcs-hidden')
                        .addClass('bbcs-error')
                        .text(botblockerSupportData.i18n.error);
                }
            });
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bbcs-support-component').length) {
                $panel.addClass('bbcs-hidden');
            }
        });
    });

})(jQuery);