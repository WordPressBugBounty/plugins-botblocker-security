(function ($) {
    $(document).ready(function () {
      //  console.log('BotBlocker Wizard: Initializing...');
        
        const wizard = {
            currentStep: 0,
            totalSteps: 8,
            selectedPreset: null,
            selectedCaptchaMode: 8, // Default to Silent Auto-Verify (recommended)
            selectedInitMode: 'regular', // Default to regular plugin mode
            selectedCache: null,
            
            init: function() {
          //      console.log('BotBlocker Wizard: Init called');
                this.restoreProgress();
                this.bindEvents();
                this.displayCurrentIP();
                this.initializeContactEmail();
                this.showStep(this.currentStep);
            },

            initializeContactEmail: function() {
                const $emailInput = $('#bbcs-wizard-contact-email');
                if (!$emailInput.length) {
                    return;
                }

                const storedEmail = localStorage.getItem('bbcs_wizard_contact_email');
                if (storedEmail) {
                    $emailInput.val(storedEmail);
                } else if (!$emailInput.val() && bbcs_setup_wizard_vars.current_user_email) {
                    $emailInput.val(bbcs_setup_wizard_vars.current_user_email);
                }

                $emailInput.on('input', function () {
                    localStorage.setItem('bbcs_wizard_contact_email', $(this).val().trim());
                });
            },
            
            bindEvents: function() {
            //    console.log('BotBlocker Wizard: Binding events');
                
                // Next button
                $(document).on('click', '.bbcs-wizard-next', (e) => {
                    e.preventDefault();
                    const nextStep = parseInt($(e.currentTarget).data('next-step'));
                //    console.log('Next button clicked, going to step:', nextStep);
                    this.goToStep(nextStep);
                });
                
                // Back button
                $(document).on('click', '.bbcs-wizard-back', (e) => {
                    e.preventDefault();
                    const prevStep = this.currentStep - 1;
                    if (prevStep >= 0) {
                 //       console.log('Back button clicked, going to step:', prevStep);
                        this.goBackToStep(prevStep);
                    }
                });
                
                // Skip button
                $(document).on('click', '.bbcs-wizard-skip', (e) => {
                    e.preventDefault();
                    if (confirm(bbcs_setup_wizard_vars.i18n.confirm_apply_defaults)) {
                        this.applyDefaultsAndFinish();
                    }
                });
                
                // Preset selection
                $(document).on('click', '.bbcs-wizard-preset', (e) => {
                    if ($(e.target).closest('.bbcs-pro-badge-bottom').length > 0) {
                        return; 
                    }
                    
                    const $card = $(e.currentTarget);
                    
                    if ($card.find('.bbcs-preset-pro-overlay').length > 0) {
                        return;
                    }
                    
                    $('.bbcs-wizard-preset').removeClass('selected');
                    $card.addClass('selected');
                    this.selectedPreset = $card.data('preset');
                    this.saveProgress();
                    $('.bbcs-wizard-apply-preset').prop('disabled', false);
                });
                
                // Apply preset
                $(document).on('click', '.bbcs-wizard-apply-preset', (e) => {
                    this.applyPreset();
                });
                
                // CAPTCHA mode selection
                $(document).on('click', '.bbcs-captcha-card', (e) => {
                    e.preventDefault();
                    const $card = $(e.currentTarget);
                    
                    $('.bbcs-captcha-card').removeClass('selected playing');
                    $('.bbcs-captcha-video').each(function() {
                        if (typeof this.pause === 'function') {
                            if (!this.paused) {
                                this.pause();
                            }
                            this.currentTime = 0;
                        }
                    });
                    
                    $card.addClass('selected');
                    this.selectedCaptchaMode = $card.data('captcha');
                    this.saveProgress();
                    $('.bbcs-wizard-save-captcha').prop('disabled', false);
                    
                    const video = $card.find('.bbcs-captcha-video')[0];
                    if (video && typeof video.play === 'function') {
                        $card.addClass('playing');
                        const playPromise = video.play();
                        if (playPromise !== undefined) {
                            playPromise.catch((error) => {
                              //  console.log('Video play prevented:', error);
                                $card.removeClass('playing');
                            });
                        }
                    }
                });
                
                // Video hover effects
                $(document).on('mouseenter', '.bbcs-captcha-card', function() {
                    const $card = $(this);
                    const video = $card.find('.bbcs-captcha-video')[0];
                    if (video && !$card.hasClass('playing') && video.readyState >= 2) {
                        $card.addClass('playing');
                        const playPromise = video.play();
                        if (playPromise !== undefined) {
                            playPromise.catch((error) => {
                            //    console.log('Video hover play prevented:', error);
                                $card.removeClass('playing');
                            });
                        }
                    }
                });
                
                $(document).on('mouseleave', '.bbcs-captcha-card', function() {
                    const $card = $(this);
                    if (!$card.hasClass('selected')) {
                        const video = $card.find('.bbcs-captcha-video')[0];
                        if (video && typeof video.pause === 'function' && !video.paused) {
                            $card.removeClass('playing');
                            video.pause();
                            video.currentTime = 0;
                        }
                    }
                });
                
                // Save CAPTCHA
                $(document).on('click', '.bbcs-wizard-save-captcha', (e) => {
                    e.preventDefault();
                    this.saveCaptchaMode();
                });
                
                // Initialization mode selection
                $(document).on('click', '.bbcs-wizard-init-card', (e) => {
                    if ($(e.target).closest('.bbcs-pro-badge-bottom').length > 0) {
                        return; 
                    }
                    
                    const $card = $(e.currentTarget);
                    
                    if ($card.find('.bbcs-init-pro-overlay').length > 0) {
                        return;
                    }
                    
                    $('.bbcs-wizard-init-card').removeClass('selected');
                    $card.addClass('selected');
                    this.selectedInitMode = $card.data('mode');
                    this.saveProgress();
                    $('.bbcs-wizard-save-init-mode').prop('disabled', false);
                });
                
                // Save Initialization Mode
                $(document).on('click', '.bbcs-wizard-save-init-mode', (e) => {
                    e.preventDefault();
                    this.saveInitMode();
                });
                
                $(document).on('click', '.bbcs-cache-card', (e) => {
                    const $card = $(e.currentTarget);
                    const cacheType = $card.data('cache');
                    
                    if ($card.hasClass('disabled')) {
                        return;
                    }
                    
                    $('.bbcs-cache-card').removeClass('selected');
                    $card.addClass('selected');
                    this.selectedCache = cacheType;
                    this.saveProgress();
                    $('.bbcs-wizard-save-cache').prop('disabled', false);
                });
                
                $(document).on('click', '.bbcs-wizard-save-cache', (e) => {
                    e.preventDefault();
                    this.saveCache();
                });
                
                $(document).on('click', '.bbcs-wizard-run-test', (e) => {
                    this.runTestAttack();
                });
                
                // Fix auto
                $(document).on('click', '.bbcs-wizard-fix-auto', (e) => {
                    this.fixCompatibilityAuto();
                });
                
                // Fix manual
                $(document).on('click', '.bbcs-wizard-fix-manual', (e) => {
                    this.goToStep(3);
                });
            },
            
            saveProgress: function() {
                const state = {
                    currentStep: this.currentStep,
                    selectedPreset: this.selectedPreset,
                    selectedCaptchaMode: this.selectedCaptchaMode,
                    selectedInitMode: this.selectedInitMode,
                    selectedCache: this.selectedCache
                };
                localStorage.setItem('bbcs_wizard_progress', JSON.stringify(state));
            },
            
            restoreProgress: function() {
                const saved = localStorage.getItem('bbcs_wizard_progress');
                if (saved) {
                    const state = JSON.parse(saved);
                    this.currentStep = state.currentStep || 0;
                    this.selectedPreset = state.selectedPreset || null;
                    this.selectedCaptchaMode = state.selectedCaptchaMode || 8;
                    this.selectedInitMode = state.selectedInitMode || 'regular';
                    this.selectedCache = state.selectedCache || null;
                    
                    this.restoreUIState();
                }
            },
            
            restoreUIState: function() {
                if (this.selectedPreset) {
                    $(`.bbcs-wizard-preset[data-preset="${this.selectedPreset}"]`).addClass('selected');
                    $('.bbcs-wizard-apply-preset').prop('disabled', false);
                }
                
                if (this.selectedCaptchaMode) {
                    $(`.bbcs-captcha-card[data-captcha="${this.selectedCaptchaMode}"]`).addClass('selected');
                    $('.bbcs-wizard-save-captcha').prop('disabled', false);
                }
                
                if (this.selectedInitMode) {
                    $(`.bbcs-wizard-init-card[data-mode="${this.selectedInitMode}"]`).addClass('selected');
                    $('.bbcs-wizard-save-init-mode').prop('disabled', false);
                }
                
                if (this.selectedCache) {
                    $(`.bbcs-cache-card[data-cache="${this.selectedCache}"]`).addClass('selected');
                    $('.bbcs-wizard-save-cache').prop('disabled', false);
                }
            },
            
            clearProgress: function() {
                localStorage.removeItem('bbcs_wizard_progress');
                localStorage.removeItem('bbcs_wizard_contact_email');
            },
            
            displayCurrentIP: function() {
                $('.bbcs-wizard-current-ip').text('(' + bbcs_setup_wizard_vars.current_ip + ')');
            },
            
            resetStepState: function(step) {
                // Сброс состояния UI для каждого шага при возврате
                if (step === 1) {
                    // Step 1: Preset selection - восстановить кнопку
                    const $btn = $('.bbcs-wizard-apply-preset');
                    if (!$btn.data('original-text')) {
                        $btn.data('original-text', $btn.html());
                    }
                    $btn.prop('disabled', this.selectedPreset === null).html($btn.data('original-text'));
                } else if (step === 2) {
                    // Step 2: Compatibility tests - сбросить к начальному состоянию (спиннеры)
                    $('.bbcs-wizard-test-status i').removeClass('fa-check fa-times text-success text-danger').addClass('fa-spinner fa-spin');
                    $('.bbcs-wizard-test-warnings').hide();
                    $('.bbcs-wizard-test-success').hide();
                } else if (step === 3) {
                    // Step 3: Exclusions - ничего особенного, просто показать шаг
                    // Чекбоксы сохраняют свое состояние
                } else if (step === 4) {
                    // Step 4: CAPTCHA mode - восстановить кнопку
                    const $btn = $('.bbcs-wizard-save-captcha');
                    if (!$btn.data('original-text')) {
                        $btn.data('original-text', $btn.html());
                    }
                    $btn.prop('disabled', this.selectedCaptchaMode === null).html($btn.data('original-text'));
                    
                    // Reset videos safely
                    $('.bbcs-captcha-card').removeClass('playing');
                    $('.bbcs-captcha-video').each(function() {
                        if (typeof this.pause === 'function') {
                            if (!this.paused) {
                                this.pause();
                            }
                            this.currentTime = 0;
                        }
                    });
                } else if (step === 5) {
                    // Step 5: Initialization Mode - восстановить кнопку
                    const $btn = $('.bbcs-wizard-save-init-mode');
                    if (!$btn.data('original-text')) {
                        $btn.data('original-text', $btn.html());
                    }
                    $btn.prop('disabled', this.selectedInitMode === null).html($btn.data('original-text'));
                } else if (step === 6) {
                    // Step 6: Cache Selection - восстановить кнопку
                    const $btn = $('.bbcs-wizard-save-cache');
                    if (!$btn.data('original-text')) {
                        $btn.data('original-text', $btn.html());
                    }
                    $btn.prop('disabled', this.selectedCache === null).html($btn.data('original-text'));
                }
            },
            
            showStep: function(step) {
              //  console.log('Showing step:', step, 'from current step:', this.currentStep);
                
                // Просто скрыть все и показать нужный
                $('.bbcs-wizard-step').hide();
                $(`.bbcs-wizard-step[data-step="${step}"]`).show();
                
                // Обновить текущий шаг
                this.currentStep = step;
                this.updateProgress();
            },
            
            goBackToStep: function(step) {
                if (step === 1) {
                    const $btn = $('.bbcs-wizard-apply-preset');
                    if ($btn.data('original-text')) {
                        $btn.html($btn.data('original-text')).prop('disabled', this.selectedPreset === null);
                    }
                } else if (step === 4) {
                    const $btn = $('.bbcs-wizard-save-captcha');
                    if ($btn.data('original-text')) {
                        $btn.html($btn.data('original-text')).prop('disabled', this.selectedCaptchaMode === null);
                    }
                    
                    $('.bbcs-captcha-card').removeClass('playing');
                    $('.bbcs-captcha-video').each(function() {
                        if (typeof this.pause === 'function') {
                            if (!this.paused) {
                                this.pause();
                            }
                            this.currentTime = 0;
                        }
                    });
                } else if (step === 5) {
                    const $btn = $('.bbcs-wizard-save-init-mode');
                    if ($btn.data('original-text')) {
                        $btn.html($btn.data('original-text')).prop('disabled', this.selectedInitMode === null);
                    }
                } else if (step === 6) {
                    const $btn = $('.bbcs-wizard-save-cache');
                    if ($btn.data('original-text')) {
                        $btn.html($btn.data('original-text')).prop('disabled', this.selectedCache === null);
                    }
                }
                this.saveProgress();
                this.showStep(step);
            },
            
            goToStep: function(step) {
                const previousStep = this.currentStep;
                
                if (step > previousStep) {
                    this.resetStepState(step);
                }
                
                this.saveProgress();
                this.showStep(step);
                
                if (step === 2 && step > previousStep) {
                    this.runCompatibilityTests();
                } else if (step === 3 && step > previousStep) {
                    this.saveExclusions();
                } else if (step === 5 && step > previousStep) {
                    this.saveNotifications();
                } else if (step === 6 && step > previousStep) {
                    this.checkCacheAvailability();
                } else if (step === 7 && step > previousStep) {
                    this.completeWizard();
                }
            },
            
            updateProgress: function() {
                const progress = (this.currentStep / (this.totalSteps - 1)) * 100;
                //console.log('Progress:', progress + '%', 'Step:', this.currentStep + 1);
                $('.bbcs-wizard-progress-fill').css('width', progress + '%');
                $('.bbcs-wizard-current-step').text(this.currentStep + 1);
            },
            
            applyPreset: function() {
                const $btn = $('.bbcs-wizard-apply-preset');
                if (!$btn.data('original-text')) {
                    $btn.data('original-text', $btn.html());
                }
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Applying...');
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_preset',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        preset: this.selectedPreset
                    },
                    success: (response) => {
                        if (response.success) {
                            this.goToStep(2);
                        } else {
                            alert(bbcs_setup_wizard_vars.i18n.error_prefix + (response.data || bbcs_setup_wizard_vars.i18n.unknown_error));
                            $('.bbcs-wizard-apply-preset').prop('disabled', false).text(bbcs_setup_wizard_vars.i18n.apply_preset);
                        }
                    },
                    error: () => {
                        alert(bbcs_setup_wizard_vars.i18n.ajax_error);
                        $('.bbcs-wizard-apply-preset').prop('disabled', false).text(bbcs_setup_wizard_vars.i18n.apply_preset);
                    }
                });
            },
            
            runCompatibilityTests: function() {
                $('.bbcs-wizard-test-status i').removeClass('fa-check fa-times text-success text-danger').addClass('fa-spinner fa-spin');
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_compatibility_test',
                        nonce: bbcs_setup_wizard_vars.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            this.displayTestResults(response.data);
                        } else {
                            alert(bbcs_setup_wizard_vars.i18n.test_failed_prefix + (response.data || bbcs_setup_wizard_vars.i18n.unknown_error));
                        }
                    },
                    error: () => {
                        alert(bbcs_setup_wizard_vars.i18n.ajax_error_compat);
                    }
                });
            },
            
            displayTestResults: function(results) {
                let hasWarnings = false;
                const warnings = [];
                
                $.each(results, (testName, result) => {
                    const $test = $(`.bbcs-wizard-test[data-test="${testName}"]`);
                    const $status = $test.find('.bbcs-wizard-test-status i');
                    
                    $status.removeClass('fa-spinner fa-spin');
                    
                    if (result.status === 'ok') {
                        $status.addClass('fa-check text-success');
                    } else {
                        $status.addClass('fa-times text-danger');
                        hasWarnings = true;
                        warnings.push(result.message || 'Unknown issue with ' + testName);
                    }
                });
                
                if (hasWarnings) {
                    const $warningsList = $('.bbcs-wizard-warnings-list');
                    $warningsList.empty();
                    warnings.forEach(w => {
                        $warningsList.append(`<li>${w}</li>`);
                    });
                    $('.bbcs-wizard-test-warnings').fadeIn();
                } else {
                    $('.bbcs-wizard-test-success').fadeIn();
                }
            },
            
            fixCompatibilityAuto: function() {
                // TODO: автофикс проблем совместимости
                alert(bbcs_setup_wizard_vars.i18n.auto_fix_stub);
                this.goToStep(3);
            },
            
            saveExclusions: function() {
                const excludeAdmins = $('#exclude-admins').is(':checked');
                const excludeCurrentIp = $('#exclude-current-ip').is(':checked');
                const excludeCron = $('#exclude-cron').is(':checked');
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_exclusions',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        exclude_admins: excludeAdmins,
                        exclude_current_ip: excludeCurrentIp,
                        exclude_cron: excludeCron,
                        current_ip: bbcs_setup_wizard_vars.current_ip
                    }
                });
            },
            
            saveCaptchaMode: function() {
                const $btn = $('.bbcs-wizard-save-captcha');
                if (!$btn.data('original-text')) {
                    $btn.data('original-text', $btn.html());
                }
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> ' + bbcs_setup_wizard_vars.i18n.saving);
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_captcha',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        captcha_mode: this.selectedCaptchaMode
                    },
                    success: (response) => {
                        if (response.success) {
                            this.goToStep(5);
                        } else {
                            alert(bbcs_setup_wizard_vars.i18n.error_prefix + (response.data || bbcs_setup_wizard_vars.i18n.unknown_error));
                            $btn.prop('disabled', false).html($btn.data('original-text'));
                        }
                    },
                    error: () => {
                        alert(bbcs_setup_wizard_vars.i18n.ajax_error_captcha);
                        $btn.prop('disabled', false).html($btn.data('original-text'));
                    }
                });
            },
            
            saveInitMode: function() {
                const $btn = $('.bbcs-wizard-save-init-mode');
                if (!$btn.data('original-text')) {
                    $btn.data('original-text', $btn.html());
                }
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> ' + bbcs_setup_wizard_vars.i18n.saving);
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_init_mode',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        init_mode: this.selectedInitMode
                    },
                    success: (response) => {
                        if (response.success) {
                            this.goToStep(6);
                        } else {
                            alert(bbcs_setup_wizard_vars.i18n.error_prefix + (response.data || bbcs_setup_wizard_vars.i18n.unknown_error));
                            $btn.prop('disabled', false).html($btn.data('original-text'));
                        }
                    },
                    error: () => {
                        alert(bbcs_setup_wizard_vars.i18n.ajax_error_init);
                        $btn.prop('disabled', false).html($btn.data('original-text'));
                    }
                });
            },
            
            checkCacheAvailability: function() {
                $('.bbcs-cache-status').html('<i class="fa-solid fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_check_cache',
                        nonce: bbcs_setup_wizard_vars.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            const $redisCard = $('.bbcs-cache-card[data-cache="redis"]');
                            const $memcachedCard = $('.bbcs-cache-card[data-cache="memcached"]');
                            
                            if (response.data.redis) {
                                $redisCard.find('.bbcs-cache-status').html('<i class="fa-solid fa-check text-success"></i>');
                            } else {
                                $redisCard.find('.bbcs-cache-status').html('<i class="fa-solid fa-times text-danger"></i>');
                                $redisCard.addClass('disabled');
                            }
                            
                            if (response.data.memcached) {
                                $memcachedCard.find('.bbcs-cache-status').html('<i class="fa-solid fa-check text-success"></i>');
                            } else {
                                $memcachedCard.find('.bbcs-cache-status').html('<i class="fa-solid fa-times text-danger"></i>');
                                $memcachedCard.addClass('disabled');
                            }
                            
                            if (!this.selectedCache) {
                                this.selectedCache = 'none';
                                $('.bbcs-wizard-save-cache').prop('disabled', false);
                            }
                        }
                    }
                });
            },
            
            saveCache: function() {
                const $btn = $('.bbcs-wizard-save-cache');
                if (!$btn.data('original-text')) {
                    $btn.data('original-text', $btn.html());
                }
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> ' + bbcs_setup_wizard_vars.i18n.saving);
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_cache',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        cache_type: this.selectedCache
                    },
                    success: (response) => {
                        if (response.success) {
                            this.goToStep(7);
                        } else {
                            alert(bbcs_setup_wizard_vars.i18n.error_prefix + (response.data || bbcs_setup_wizard_vars.i18n.unknown_error));
                            $btn.prop('disabled', false).html($btn.data('original-text'));
                        }
                    },
                    error: () => {
                        alert(bbcs_setup_wizard_vars.i18n.ajax_error_cache);
                        $btn.prop('disabled', false).html($btn.data('original-text'));
                    }
                });
            },
            
            saveNotifications: function() {
                const notifyDaily = $('#notify-daily').is(':checked');
                const notifyBruteForce = $('#notify-brute-force').is(':checked');
                const notifyWeekly = $('#notify-weekly').is(':checked');
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_notifications',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        notify_daily: notifyDaily,
                        notify_brute_force: notifyBruteForce,
                        notify_weekly: notifyWeekly
                    }
                });
            },
            
            completeWizard: function() {
                const contactEmail = ($('#bbcs-wizard-contact-email').val() || '').trim();

                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_complete',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        contact_email: contactEmail
                    },
                    success: (response) => {
                        if (response.success) {
                            this.clearProgress();
                            
                            const modeNames = {
                                'light': 'Light',
                                'strong': 'Strong',
                                'full': 'Full'
                            };
                            $('.bbcs-wizard-final-mode').text(modeNames[this.selectedPreset] || 'Strong');
                            
                            const captchaNames = {
                                '1': 'Color Circles',
                                '2': 'Image Recognition',
                                '5': 'Dynamic Shapes',
                                '6': 'Dynamic Digits',
                                '7': 'Hold Button',
                                '8': 'Silent Auto-Verify'
                            };
                            $('.bbcs-wizard-final-captcha').text(captchaNames[this.selectedCaptchaMode] || 'Silent Auto-Verify');
                            
                            const initNames = {
                                'regular': 'Regular Plugin',
                                'mu': 'MU Plugin',
                                'early': 'Early Initialization'
                            };
                            $('.bbcs-wizard-final-init').text(initNames[this.selectedInitMode] || 'Regular Plugin');
                            
                            $('.bbcs-wizard-final-score').text(response.data.score + '%');
                        }
                    }
                });
            },
            
            runTestAttack: function() {
                $('.bbcs-wizard-run-test').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Testing...');
                
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_test_attack',
                        nonce: bbcs_setup_wizard_vars.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            const result = response.data;
                            $('.bbcs-wizard-test-result')
                                .html(`
                                    <div class="alert alert-success mt-3">
                                        <strong>${result.message}</strong><br>
                                        <small>Reason: ${result.event.reason} | URL: ${result.event.url} | Action: ${result.event.action}</small>
                                    </div>
                                `)
                                .fadeIn();
                        } else {
                            $('.bbcs-wizard-test-result')
                                .html(`<div class="alert alert-danger mt-3">Test failed</div>`)
                                .fadeIn();
                        }
                        $('.bbcs-wizard-run-test').prop('disabled', false).html('<i class="fa-solid fa-vial"></i> Run safe test');
                    }
                });
            },
            
            applyDefaultsAndFinish: function() {
			this.selectedPreset = 'strong';
                $.ajax({
                    url: bbcs_setup_wizard_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bbcs_wizard_save_preset',
                        nonce: bbcs_setup_wizard_vars.nonce,
                        preset: 'balanced'
                    },
                    success: () => {
                        // Применяем дефолтные настройки для всех остальных шагов
                        $.ajax({
                            url: bbcs_setup_wizard_vars.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'bbcs_wizard_save_exclusions',
                                nonce: bbcs_setup_wizard_vars.nonce,
                                exclude_admins: true,
                                exclude_current_ip: true,
                                exclude_cron: true,
                                current_ip: bbcs_setup_wizard_vars.current_ip
                            }
                        });
                        
                        $.ajax({
                            url: bbcs_setup_wizard_vars.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'bbcs_wizard_save_ux',
                                nonce: bbcs_setup_wizard_vars.nonce,
                                ux_mode: 'challenge'
                            }
                        });
                        
                        $.ajax({
                            url: bbcs_setup_wizard_vars.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'bbcs_wizard_save_notifications',
                                nonce: bbcs_setup_wizard_vars.nonce,
                                notify_daily: true,
                                notify_brute_force: true,
                                notify_weekly: false
                            }
                        });
                        
                        // Redirect to dashboard
                        window.location.href = bbcs_setup_wizard_vars.dashboard_url;
                    }
                });
            }
        };
        
        wizard.init();
    });
})(jQuery); 