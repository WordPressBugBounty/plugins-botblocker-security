(function ($) {
    'use strict';

    const SELECTORS = {
        input: '#bbcs-2fa-code-input',
        button: '#bbcs-2fa-submit-btn',
        verified: '.bbcs-2fa-verified',
        reset: '.bbcs-2fa-reset',
        message: '#bbcs-2fa-message'
    };

    const UI = {        
        setLoading(isLoading) {
            const $btn = $(SELECTORS.button);

            if (isLoading) {
                $btn.prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-2"></i>Verifying...');
            } else {
                $btn.prop('disabled', false)
                    .html('<i class="fas fa-check me-1"></i>Activate');
            }
        },
        // Marks the input as invalid and shows an optional message via toast
        markInputInvalid(message = null) {
            const $input = $(SELECTORS.input);

            $input.addClass('is-invalid');
            $input.css('border-color', '#dc3545');         

            const $feedback = $input.siblings('.invalid-feedback');        

            if ($feedback.length) {
                if (message) {
                    $feedback.text(message);
                }

                $feedback.css('display', 'block');
            }
        },
        // Clears the invalid state from the input
        clearInputValidation() {
            const $input = $(SELECTORS.input);

            $input.removeClass('is-invalid');
            $input.css('border-color', '');            

            if ($input.length > 0) {
                $input[0].style.removeProperty('border-color');
                $input[0].style.removeProperty('box-shadow');
            }

            $input
                .siblings('.invalid-feedback')
                .css('display', 'none');
        },

        showToast(type, message) {
            window.bbcsToast(type, message);
        },

        toggleVerifiedState() {
            $(SELECTORS.verified).hide();
            $(SELECTORS.reset).show();
        },

        resetVerifiedState() {
            $(SELECTORS.verified).show();
            $(SELECTORS.reset).hide();
        }
    };

    function bbcsGetBackupCodes() {
        const codes = [];

        document.querySelectorAll(
            'input[name="bbcs_2fa_backup_code"]'
        ).forEach(input => {
            const value = input.value.trim();
            if (value) {
                codes.push(value);
            }
        });

        return codes;
    }

    function bbcsDownloadBackupCodes() {
        const codes = bbcsGetBackupCodes();
        const text = codes.join('\n');
        const blob = new Blob([text], {
            type: 'text/plain'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'botblocker-backup-codes.txt';
        a.click();
        URL.revokeObjectURL(url);
    }
    $(document).on('click', '#bbcs_download_backup_codes', function (e) {
        e.preventDefault();
        bbcsDownloadBackupCodes();
    });

    function resetFormState() {
        const $input = $(SELECTORS.input);
        $input.val('').focus();
        // UI.clearInputValidation();
        UI.setLoading(false);
    }

    function isValidCode(code) {
        return /^\d{6}$/.test(code);
    }

    function submit2FACode() {
        const $input = $(SELECTORS.input);
        const code = $input.val().trim();

        if (!isValidCode(code)) {
            const msg = (window.bbcs2faL10n && window.bbcs2faL10n.invalid_format) || 'Please enter a valid 6-digit code.';
            UI.markInputInvalid(msg);
            UI.showToast('error', msg);
            return;
        }

        UI.clearInputValidation();
        UI.setLoading(true);

        $.ajax({
            url: botblockerData.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'bbcs_2fa_setup',
                bbcs_2fa_code: code,
                nonce: botblockerData.nonce
            }
        })
            .done(function (response) {               
                if (response.success) {
                    UI.toggleVerifiedState();
                    const msg = (window.bbcs2faL10n && window.bbcs2faL10n.enabled) || 'Two-Factor Authentication enabled.';
                    UI.showToast(
                        'success',
                        response.data?.message || msg
                    );
                } else {
                    const errMsg = (window.bbcs2faL10n && window.bbcs2faL10n.invalid_code) || 'Invalid verification code.';
                    const failMsg = (window.bbcs2faL10n && window.bbcs2faL10n.verify_failed) || 'Verification failed.';
                    UI.markInputInvalid(
                        response.data?.message || errMsg
                    );

                    UI.showToast(
                        'error',
                        response.data?.message || failMsg
                    );
                }

                resetFormState();
            })
            .fail(function (xhr) {
                console.error('AJAX Error:', xhr);

                const connErr = (window.bbcs2faL10n && window.bbcs2faL10n.connection_error) || 'Connection error. Please try again.';
                let errorMessage = connErr;

                if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        const invCode = (window.bbcs2faL10n && window.bbcs2faL10n.invalid_code) || 'Invalid verification code.';
                        errorMessage = errorData?.data?.message || connErr;
                    } catch (e) {
                        errorMessage += ` (Status: ${xhr.status})`;
                    }
                }

                UI.markInputInvalid(errorMessage);

                UI.showToast('error', errorMessage);
                resetFormState();
            });
    }

    function bindEvents() {
        const $input = $(SELECTORS.input);
        const $button = $(SELECTORS.button);
        const $reset = $(SELECTORS.reset);

        $input.on('input', function () {
            this.value = this.value.replace(/\D/g, '');
            UI.clearInputValidation();
        });


        $input.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submit2FACode();
            }
        });

        $button.on('click', function (e) {
            e.preventDefault();
            submit2FACode();
        });

        $reset.on('click', function (e) {
            e.preventDefault();

            resetFormState();
            UI.resetVerifiedState();
            const resetMsg = (window.bbcs2faL10n && window.bbcs2faL10n.reset_success) || 'Two-Factor Authentication has been reset.';
            UI.showToast('success', resetMsg);
        });
    }

    $(document).ready(function () {
        bindEvents();
    });


    // Reset 2FA
    function get2FACardBody() {        
        const $tab = $('#bbcs-2fa');
        return $tab.length ? $tab : $('.card-body');
    }

    function showLoadingOverlayFor2FA() {
        const $container = get2FACardBody();
        
        if (!$container.length) {
            console.warn('2FA container not found');
            return;
        }
                
        if ($container.find('.bbcs-loading-overlay--2fa').length) {
            return;
        }
                
        if ($container.css('position') === 'static') {
            $container.css('position', 'relative');
        }
        
        $container.append(`
            <div class="bbcs-loading-overlay bbcs-loading-overlay--2fa" style="
                position: absolute;
                top: 0;
                left: -2px;
                right: -2px;
                bottom: 0;
                background: rgba(255, 255, 255, 0.95);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            ">
                <i class="fas fa-spinner fa-spin fa-3x" style="color: #3498db;"></i>
            </div>
        `);
    }

    function hideLoadingOverlayFor2FA() {
        $('.bbcs-loading-overlay--2fa').fadeOut(200, function() {
            $(this).remove();
        });
    }
  
    $(document).on('click', '[data-bbcs-action="reset"]', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;

        showLoadingOverlayFor2FA();
        $btn.prop('disabled', true);

        $.post(botblockerData.ajaxurl, {
            action: 'bbcs_reset_2fa',
            nonce: botblockerData.nonce
        })
            .done(function (res) {

                if (!res || !res.success) {
                    bbcsToast('error', bbcs2faL10n.reset_failed);
                    return;
                }

                if (res.data.bbcs_qr_code) {
                    $('#bbcs-2fa-qr-code').attr('src', res.data.bbcs_qr_code);
                }

                if (Array.isArray(res.data.bbcs_backup_codes)) {
                    const $container = $('#bbcs-backup-codes').empty();
                    res.data.bbcs_backup_codes.forEach(code => {
                        $container.append(`
                            <div class="col-6">
                                <div class="bbcs_text_input_inner">
                                    <input type="text" name="bbcs_2fa_backup_code" class="bbcs_text_input_input" value="${code}" readonly>
                                </div>
                            </div>
                        `);
                    });
                }

                $('.bbcs-2fa-reset').hide();
                $('.bbcs-2fa-verified').show();
            })
            .always(function () {
                hideLoadingOverlayFor2FA();
                $btn.prop('disabled', false);
            });
    });

    $(document).on('click', '[data-bbcs-action="revoke-devices"]', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;

        showLoadingOverlayFor2FA();
        $btn.prop('disabled', true);

        $.post(botblockerData.ajaxurl, {
            action: 'bbcs_revoke_2fa_devices',
            nonce: botblockerData.nonce
        })
            .done(function (res) {
                if (!res || !res.success) {
                    bbcsToast('error', (window.bbcs2faL10n && window.bbcs2faL10n.revoke_failed) || 'Failed to revoke trusted devices.');
                    return;
                }
                bbcsToast('success', (window.bbcs2faL10n && window.bbcs2faL10n.revoke_ok) || 'All trusted devices have been revoked.');
            })
            .always(function () {
                hideLoadingOverlayFor2FA();
                $btn.prop('disabled', false);
            });
    });

})(jQuery);