(function ($) {
    'use strict';

    $(document).ready(function () {
        var modalEl = document.getElementById('bbcsOneClickSetupModal');
        if ($('#bbcsOpenOneClickSetup').length && modalEl) {
            $('#bbcsOpenOneClickSetup').on('click', function (e) {
                e.preventDefault();
                var m = new bootstrap.Modal(modalEl); m.show();
            });
        }
        $(document).on('click', '.bbcs-apply-profile', function (e) {
            e.preventDefault();
            
            var $btn = $(this);
            if ($btn.prop('disabled')) return;
            
            var mode = $btn.data('mode');
            var $card = $btn.closest('.bbcs-profile-choice');
            
            // Check if Full Protection requires PRO
            if (mode === 'full') {
                var $modal = $btn.closest('.modal');
                var hasPro = $modal.data('pro') === 1 || $modal.data('pro') === '1';
                if (!hasPro) {
                    alert('Full Protection requires PRO license. Please upgrade to PRO first.');
                    return;
                }
            }
            
            if (!$btn.data('original-text')) {
                $btn.data('original-text', $btn.find('.bbcs-btn-text').text());
            }
            

            $('.bbcs-apply-profile').prop('disabled', true).addClass('disabled');
            $('.bbcs-profile-choice').removeClass('border-primary');
            $card.addClass('border-primary');
            
            $btn.find('.bbcs-btn-text').text('Please wait...').removeClass('d-none');
            $btn.find('.spinner-border').removeClass('d-none');
            
            $.ajax({
                url: botblockerData.ajaxurl,
                type: 'POST',
                data: { 
                    action: 'bbcs_apply_security_profile', 
                    nonce: botblockerData.nonce, 
                    mode: mode 
                },
                dataType: 'json'
            }).done(function (resp) {
                if (resp && resp.success) {
                    window.location.reload();
                } else {
                    alert(resp && resp.data && resp.data.message ? resp.data.message : 'Error applying profile');
                    $('.bbcs-apply-profile').prop('disabled', false).removeClass('disabled');
                    $btn.find('.bbcs-btn-text').text($btn.data('original-text') || 'Apply Now');
                    $btn.find('.spinner-border').addClass('d-none');
                }
            }).fail(function () {
                alert('Request failed. Please try again.');
                $('.bbcs-apply-profile').prop('disabled', false).removeClass('disabled');
                $btn.find('.bbcs-btn-text').text($btn.data('original-text') || 'Apply Now');
                $btn.find('.spinner-border').addClass('d-none');
            });
        });
    });

})(jQuery);