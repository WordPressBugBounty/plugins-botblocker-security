(function () {
	'use strict';

	var bbcsSetWizVars = window.bbcs_setup_wizard_vars || {};

	var state = {
		currentStep: 0,
		totalSteps: 9,
		selectedPreset: 'strong',
		selectedCaptchaMode: 8,
		selectedRecaptchaProvider: bbcsSetWizVars.recaptcha_provider || 'none',
		selectedInitMode: 'regular',
		selectedCache: 'none'
	};

	var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
	var $$ = function (sel, ctx) { return (ctx || document).querySelectorAll(sel); };

	var presetNames = {
		light: 'Light',
		strong: 'Strong',
		full: 'Full'
	};
	var captchaNames = {
		1: 'Color Buttons',
		2: 'BotBlocker Image Captcha',
		3: 'Google reCAPTCHA v2 with Button',
		4: 'Google reCAPTCHA v2',
		5: 'Dynamic Shape Captcha',
		6: 'Dynamic Digit Captcha',
		7: 'Hold Button Captcha',
		8: 'Silent Auto-Verify (No Captcha)'
	};
	var recaptchaNames = {
		none: 'Not connected',
		v2: 'Google reCAPTCHA v2',
		v3: 'Google reCAPTCHA v3 (additional layer)'
	};
	var initNames = {
		regular: 'Regular plugin',
		mu: 'MU-plugin',
		early: 'Early Init'
	};

	function ajax(data) {
		return fetch(bbcsSetWizVars.ajax_url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		}).then(function (r) { return r.json(); });
	}

	function saveProgress() {
		localStorage.setItem('bbcs_wizard_progress', JSON.stringify({
			currentStep: state.currentStep,
			selectedPreset: state.selectedPreset,
			selectedCaptchaMode: state.selectedCaptchaMode,
			selectedRecaptchaProvider: state.selectedRecaptchaProvider,
			selectedInitMode: state.selectedInitMode,
			selectedCache: state.selectedCache
		}));
	}

	function restoreProgress() {
		var raw = localStorage.getItem('bbcs_wizard_progress');
		if (!raw) return;
		try {
			var saved = JSON.parse(raw);
			state.currentStep = typeof saved.currentStep === 'number' ? saved.currentStep : 0;
			state.selectedPreset = saved.selectedPreset || 'strong';
			state.selectedCaptchaMode = typeof saved.selectedCaptchaMode === 'number' ? saved.selectedCaptchaMode : 8;
			state.selectedRecaptchaProvider = saved.selectedRecaptchaProvider || bbcsSetWizVars.recaptcha_provider || 'none';
			state.selectedInitMode = saved.selectedInitMode || 'regular';
			state.selectedCache = saved.selectedCache || 'none';
		} catch (e) { /* ignore */ }
	}

	function clearProgress() {
		localStorage.removeItem('bbcs_wizard_progress');
		localStorage.removeItem('bbcs_wizard_contact_email');
	}

	function updateMeter() {
		for (var i = 0; i < state.totalSteps; i++) {
			var dot = $('.bbcs-wizdot[data-dot="' + i + '"]');
			var line = $('.bbcs-wizline[data-line="' + i + '"]');
			if (dot) {
				dot.classList.remove('is-active', 'is-done');
				if (i < state.currentStep) dot.classList.add('is-done');
				if (i === state.currentStep) dot.classList.add('is-active');
			}
			if (line) {
				line.classList.toggle('is-done', i < state.currentStep);
			}
		}
	}

	function showStep(step) {
		$$('.bbcs-wizstep').forEach(function (el) { el.classList.remove('is-active'); });
		var stepEl = $('.bbcs-wizstep[data-step="' + step + '"]');
		if (stepEl) stepEl.classList.add('is-active');
		state.currentStep = step;
		updateMeter();
		saveProgress();

		// Step-specific async checks when the step becomes visible
		if (step === 7) {
			checkCacheAvailability();
		}
	}

	function applyPreset() {
		var btn = $('#bbcs-wiz-apply-preset');
		if (!btn) return;
		var orig = btn.textContent;
		btn.disabled = true;
		btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> Applying...';

		ajax({
			action: 'bbcs_wizard_save_preset',
			nonce: bbcsSetWizVars.nonce,
			preset: state.selectedPreset
		}).then(function (r) {
			btn.disabled = false;
			btn.innerHTML = orig;
			if (r.success) {
				state.selectedPreset = state.selectedPreset;
				state.currentStep = 2;
				saveProgress();
				window.location.reload();
			} else {
				bbcsToast('error', (bbcsSetWizVars.i18n && bbcsSetWizVars.i18n.error_prefix || 'Error: ') + (r.data || 'Unknown error'));
			}
		}).catch(function () {
			btn.disabled = false;
			btn.innerHTML = orig;
		});
	}

	function runCompatibilityTests() {
		$$('.bbcs-wiztest-status .bbcs-ico').forEach(function (el) {
			el.classList.remove('bbcs-tx-green', 'bbcs-tx-red');
			el.classList.add('bbcs-ico--spinner');
			el.setAttribute('href', '#bbcs-i-refresh');
		});

		ajax({
			action: 'bbcs_wizard_compatibility_test',
			nonce: bbcsSetWizVars.nonce
		}).then(function (r) {
			if (!r.success) return;
			var hasWarnings = false;
			var results = r.data;
			for (var testName in results) {
				if (!results.hasOwnProperty(testName)) continue;
				var row = $('.bbcs-wiztest[data-test="' + testName + '"]');
				if (!row) continue;
				var icon = $('.bbcs-ico', row);
				if (!icon) continue;
				icon.classList.remove('bbcs-ico--spinner');
				if (results[testName].status === 'ok') {
					icon.setAttribute('href', '#bbcs-i-check');
					icon.classList.add('bbcs-tx-green');
				} else {
					icon.setAttribute('href', '#bbcs-i-x');
					icon.classList.add('bbcs-tx-red');
					hasWarnings = true;
				}
			}
			var warnBlock = $('#bbcs-wiz-test-warn');
			var okBlock = $('#bbcs-wiz-test-ok');
			if (hasWarnings && warnBlock) warnBlock.hidden = false;
			if (!hasWarnings && okBlock) okBlock.hidden = false;
		});
	}

	function runTestAttack() {
		var btn = $('#bbcs-wiz-run-test');
		if (!btn) return;
		var resultEl = $('#bbcs-wiz-test-result');
		if (!resultEl) return;

		btn.disabled = true;
		btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> Testing...';
		resultEl.innerHTML = '';

		ajax({
			action: 'bbcs_wizard_test_attack',
			nonce: bbcsSetWizVars.nonce
		}).then(function (r) {
			btn.disabled = false;
			btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-shield"></use></svg> Run safe test';
			if (r.success) {
				var result = r.data;
				resultEl.innerHTML = '<div class="bbcs-card bbcs-green-card" style="padding:var(--bbcs-sp-3);font-size:var(--bbcs-fs-sm);display:flex;align-items:flex-start;gap:var(--bbcs-sp-2h)"><svg class="bbcs-ico bbcs-ico--sm bbcs-tx-green" style="flex:0 0 auto;margin-top:2px"><use href="#bbcs-i-check"></use></svg> <div><strong>' + (result.message || 'OK') + '</strong><br><span class="bbcs-dim" style="font-size:var(--bbcs-fs-xs)">Reason: ' + (result.event.reason || '-') + ' &middot; URL: ' + (result.event.url || '-') + ' &middot; Action: ' + (result.event.action || '-') + '</span></div></div>';
			} else {
				var msg = (r.data && r.data.message) || 'Test failed';
				resultEl.innerHTML = '<div class="bbcs-card" style="padding:var(--bbcs-sp-3);font-size:var(--bbcs-fs-sm);display:flex;align-items:flex-start;gap:var(--bbcs-sp-2h);background:var(--bbcs-red-dim);border-color:var(--bbcs-red)"><svg class="bbcs-ico bbcs-ico--sm bbcs-tx-red" style="flex:0 0 auto;margin-top:2px"><use href="#bbcs-i-x"></use></svg> <span>' + msg + '</span></div>';
			}
		}).catch(function () {
			btn.disabled = false;
			btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-shield"></use></svg> Run safe test';
			resultEl.innerHTML = '<div class="bbcs-card" style="padding:var(--bbcs-sp-3);font-size:var(--bbcs-fs-sm);display:flex;align-items:flex-start;gap:var(--bbcs-sp-2h);background:var(--bbcs-red-dim);border-color:var(--bbcs-red)"><svg class="bbcs-ico bbcs-ico--sm bbcs-tx-red" style="flex:0 0 auto;margin-top:2px"><use href="#bbcs-i-x"></use></svg> <span>AJAX error</span></div>';
		});
	}

	function saveExclusions() {
		var adminsEl = $('.bbcs-wizcheck[data-key="exclude-admins"]');
		var currentIpEl = $('.bbcs-wizcheck[data-key="exclude-current-ip"]');
		var cronEl = $('.bbcs-wizcheck[data-key="exclude-cron"]');

		ajax({
			action: 'bbcs_wizard_save_exclusions',
			nonce: bbcsSetWizVars.nonce,
			exclude_admins: adminsEl && adminsEl.classList.contains('is-on') ? 1 : 0,
			exclude_current_ip: currentIpEl && currentIpEl.classList.contains('is-on') ? 1 : 0,
			exclude_cron: cronEl && cronEl.classList.contains('is-on') ? 1 : 0,
			current_ip: bbcsSetWizVars.current_ip || ''
		});
	}

	function saveRecaptchaAndAdvance() {
		var btn = $('#bbcs-wiz-next4');
		if (!btn) return;
		var orig = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> ' + (bbcsSetWizVars.i18n.saving || 'Saving...');

		ajax({
			action: 'bbcs_wizard_save_recaptcha',
			nonce: bbcsSetWizVars.nonce,
			provider: state.selectedRecaptchaProvider,
			recaptcha_key2: $('#bbcs-wiz-recaptcha-key2') ? $('#bbcs-wiz-recaptcha-key2').value.trim() : '',
			recaptcha_secret2: $('#bbcs-wiz-recaptcha-secret2') ? $('#bbcs-wiz-recaptcha-secret2').value.trim() : '',
			recaptcha_key3: $('#bbcs-wiz-recaptcha-key3') ? $('#bbcs-wiz-recaptcha-key3').value.trim() : '',
			recaptcha_secret3: $('#bbcs-wiz-recaptcha-secret3') ? $('#bbcs-wiz-recaptcha-secret3').value.trim() : '',
			recaptcha_tresshold: $('#bbcs-wiz-recaptcha-threshold') ? $('#bbcs-wiz-recaptcha-threshold').value : '0.5',
			recaptcha_check: $('.bbcs-toggle[data-recaptcha-toggle="enable"]') && $('.bbcs-toggle[data-recaptcha-toggle="enable"]').classList.contains('is-on') ? 1 : 0,
			recaptcha_v3_ipv6_block: $('.bbcs-toggle[data-recaptcha-toggle="ipv6"]') && $('.bbcs-toggle[data-recaptcha-toggle="ipv6"]').classList.contains('is-on') ? 1 : 0
		}).then(function (r) {
			btn.disabled = false;
			btn.innerHTML = orig;
			if (r.success) {
				showStep(5);
			} else {
				bbcsToast('error', (bbcsSetWizVars.i18n && bbcsSetWizVars.i18n.error_prefix || 'Error: ') + (r.data || 'Unknown error'));
			}
		}).catch(function () { btn.disabled = false; btn.innerHTML = orig; });
	}

	function saveCaptchaAndAdvance() {
		var btn = $('#bbcs-wiz-next5');
		if (!btn) return;
		var orig = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> ' + (bbcsSetWizVars.i18n.saving || 'Saving...');

		ajax({
			action: 'bbcs_wizard_save_captcha',
			nonce: bbcsSetWizVars.nonce,
			captcha_mode: state.selectedCaptchaMode
		}).then(function (r) {
			btn.disabled = false;
			btn.innerHTML = orig;
			if (r.success) {
				showStep(6);
			} else {
				bbcsToast('error', (bbcsSetWizVars.i18n && bbcsSetWizVars.i18n.error_prefix || 'Error: ') + (r.data || 'Unknown error'));
			}
		}).catch(function () { btn.disabled = false; btn.innerHTML = orig; });
	}

	function saveInitAndAdvance() {
		var btn = $('#bbcs-wiz-next6');
		if (!btn) return;
		var orig = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> ' + (bbcsSetWizVars.i18n.saving || 'Saving...');

		ajax({
			action: 'bbcs_wizard_save_init_mode',
			nonce: bbcsSetWizVars.nonce,
			init_mode: state.selectedInitMode
		}).then(function (r) {
			btn.disabled = false;
			btn.innerHTML = orig;
			if (r.success) {
				showStep(7);
			} else {
				bbcsToast('error', (bbcsSetWizVars.i18n && bbcsSetWizVars.i18n.error_prefix || 'Error: ') + (r.data || 'Unknown error'));
			}
		}).catch(function () { btn.disabled = false; btn.innerHTML = orig; });
	}

	function checkCacheAvailability() {
		var redisCard = $('.bbcs-wizcard[data-cache="redis"]');
		var memcachedCard = $('.bbcs-wizcard[data-cache="memcached"]');
		// Lock both while checking
		[redisCard, memcachedCard].forEach(function (card) {
			if (card) { card.style.opacity = '0.5'; card.style.pointerEvents = 'none'; }
		});
		ajax({
			action: 'bbcs_wizard_check_cache',
			nonce: bbcsSetWizVars.nonce
		}).then(function (r) {
			if (!r.success) {
				// On error, unlock both and show red X
				[redisCard, memcachedCard].forEach(function (card) {
					if (!card) return;
					card.style.opacity = '0.5';
					card.style.pointerEvents = 'none';
					var status = card.querySelector('.bbcs-cache-status');
					if (status) status.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-tx-red"><use href="#bbcs-i-x"></use></svg>';
				});
				return;
			}
			['redis', 'memcached'].forEach(function (type) {
				var card = $('.bbcs-wizcard[data-cache="' + type + '"]');
				if (!card) return;
				var status = card.querySelector('.bbcs-cache-status');
				if (!status) return;
				if (r.data[type]) {
					status.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-tx-green"><use href="#bbcs-i-check"></use></svg>';
					card.style.opacity = '';
					card.style.pointerEvents = '';
				} else {
					status.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-tx-red"><use href="#bbcs-i-x"></use></svg>';
				}
			});
		});
	}

	function saveCacheAndAdvance() {
		var btn = $('#bbcs-wiz-next7');
		if (!btn) return;
		var orig = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> ' + (bbcsSetWizVars.i18n.saving || 'Saving...');

		ajax({
			action: 'bbcs_wizard_save_cache',
			nonce: bbcsSetWizVars.nonce,
			cache_type: state.selectedCache
		}).then(function (r) {
			btn.disabled = false;
			btn.innerHTML = orig;
			if (r.success) {
				completeWizard();
				showStep(8);
			} else {
				bbcsToast('error', (bbcsSetWizVars.i18n && bbcsSetWizVars.i18n.error_prefix || 'Error: ') + (r.data || 'Unknown error'));
			}
		}).catch(function () { btn.disabled = false; btn.innerHTML = orig; });
	}

	function completeWizard() {
		var email = $('#bbcs-wiz-contact-email');
		var contactEmail = email ? email.value.trim() : '';

		ajax({
			action: 'bbcs_wizard_complete',
			nonce: bbcsSetWizVars.nonce,
			contact_email: contactEmail
		}).then(function (r) {
			if (r.success) {
				clearProgress();
				var presetEl = $('#bbcs-wiz-final-preset');
				if (presetEl) presetEl.textContent = presetNames[state.selectedPreset] || 'Strong';
				var captchaEl = $('#bbcs-wiz-final-captcha');
				if (captchaEl) captchaEl.textContent = captchaNames[state.selectedCaptchaMode] || 'Silent Auto-Verify (No Captcha)';
				var recaptchaEl = $('#bbcs-wiz-final-recaptcha');
				if (recaptchaEl) recaptchaEl.textContent = recaptchaNames[state.selectedRecaptchaProvider] || 'Not connected';
				var initEl = $('#bbcs-wiz-final-init');
				if (initEl) initEl.textContent = initNames[state.selectedInitMode] || 'Regular plugin';
				var scoreEl = $('#bbcs-wiz-final-score');
				if (scoreEl && r.data && r.data.score) scoreEl.textContent = r.data.score + '%';
			}
		});
	}

	function goToStep(step) {
		// Run side effects for current step before advancing
		if (step > state.currentStep) {
			if (state.currentStep === 0 && step >= 1) {
				showStep(step);
				return;
			}
			if (state.currentStep === 1 && step >= 2) {
				applyPreset();
				return;
			}
			if (state.currentStep === 2 && step === 3) {
				showStep(step);
				return;
			}
			if (state.currentStep === 3 && step >= 4) {
				saveExclusions();
				showStep(step);
				return;
			}
			if (state.currentStep === 4 && step >= 5) {
				saveRecaptchaAndAdvance();
				return;
			}
			if (state.currentStep === 5 && step >= 6) {
				saveCaptchaAndAdvance();
				return;
			}
			if (state.currentStep === 6 && step >= 7) {
				saveInitAndAdvance();
				return;
			}
			if (state.currentStep === 7 && step >= 8) {
				saveCacheAndAdvance();
				return;
			}
		}
		showStep(step);
	}

	function goBack(step) {
		showStep(step);
	}

	function skipToDefaults() {
		var btn = $('#bbcs-wiz-skip');
		if (btn) { btn.disabled = true; btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> ' + (bbcsSetWizVars.i18n.saving || 'Saving...'); }
		state.selectedPreset = 'strong';
		saveProgress();
		ajax({
			action: 'bbcs_wizard_save_preset',
			nonce: bbcsSetWizVars.nonce,
			preset: 'strong'
		}).then(function () {
			window.location.href = bbcsSetWizVars.dashboard_url;
		}).catch(function () {
			window.location.href = bbcsSetWizVars.dashboard_url;
		});
	}

	function initEmail() {
		var input = $('#bbcs-wiz-contact-email');
		if (!input) return;
		var stored = localStorage.getItem('bbcs_wizard_contact_email');
		if (stored) {
			input.value = stored;
		} else if (!input.value && bbcsSetWizVars.current_user_email) {
			input.value = bbcsSetWizVars.current_user_email;
		}
		input.addEventListener('input', function () {
			localStorage.setItem('bbcs_wizard_contact_email', input.value.trim());
		});
	}

	function updateCaptchaSelection(mode) {
		var modeValue = String(mode);
		$$('.bbcs-wizcaptcha-option, .bbcs-wizcard[data-captcha]').forEach(function (option) {
			option.classList.toggle('is-sel', option.getAttribute('data-captcha') === modeValue);
		});
		$$('.bbcs-wizcaptcha-radio').forEach(function (radio) {
			radio.checked = radio.value === modeValue;
		});
		$$('[data-captcha-detail]').forEach(function (detail) {
			var active = detail.getAttribute('data-captcha-detail') === modeValue;
			detail.hidden = !active;
			detail.classList.toggle('is-active', active);
			var video = $('video', detail);
			if (!video) return;
			if (active && typeof video.play === 'function') {
				try {
					var playPromise = video.play();
					if (playPromise && typeof playPromise.catch === 'function') playPromise.catch(function () {});
				} catch (e) {}
			} else if (typeof video.pause === 'function') {
				video.pause();
			}
		});
	}

	function bindEvents() {
		$('#bbcs-wiz-start') && $('#bbcs-wiz-start').addEventListener('click', function () { goToStep(1); });
		$('#bbcs-wiz-skip') && $('#bbcs-wiz-skip').addEventListener('click', function () {
			bbcsConfirm(bbcsSetWizVars.i18n && bbcsSetWizVars.i18n.confirm_apply_defaults || 'Are you sure?', skipToDefaults);
		});

		$$('.bbcs-wizcard[data-preset]').forEach(function (card) {
			card.addEventListener('click', function (e) {
				if (e.target.closest('.bbcs-wizcard-pro-overlay') || e.target.closest('.bbcs-wizcard-pro-link')) return;
				if (card.querySelector('.bbcs-wizcard-pro-overlay')) return;
				$$('.bbcs-wizcard[data-preset]').forEach(function (c) { c.classList.remove('is-sel'); });
				card.classList.add('is-sel');
				state.selectedPreset = card.getAttribute('data-preset');
				saveProgress();
				var btn = $('#bbcs-wiz-apply-preset');
				if (btn) btn.disabled = false;
			});
		});

		$('#bbcs-wiz-apply-preset') && $('#bbcs-wiz-apply-preset').addEventListener('click', applyPreset);

		$$('.bbcs-wizcaptcha-radio').forEach(function (radio) {
			radio.addEventListener('change', function () {
				state.selectedCaptchaMode = parseInt(radio.value, 10);
				updateCaptchaSelection(state.selectedCaptchaMode);
				saveProgress();
			});
		});

		$$('.bbcs-wizcard[data-captcha]').forEach(function (card) {
			card.addEventListener('click', function () {
				state.selectedCaptchaMode = parseInt(card.getAttribute('data-captcha'), 10);
				updateCaptchaSelection(state.selectedCaptchaMode);
				saveProgress();
			});
		});

		$$('.bbcs-wizcard[data-recaptcha-provider]').forEach(function (card) {
			card.addEventListener('click', function () {
				$$('.bbcs-wizcard[data-recaptcha-provider]').forEach(function (c) { c.classList.remove('is-sel'); });
				card.classList.add('is-sel');
				state.selectedRecaptchaProvider = card.getAttribute('data-recaptcha-provider');
				$$('[data-recaptcha-fields]').forEach(function (fields) {
					fields.hidden = fields.getAttribute('data-recaptcha-fields') !== state.selectedRecaptchaProvider;
				});
				saveProgress();
			});
		});

		$$('[data-recaptcha-toggle]').forEach(function (toggle) {
			toggle.addEventListener('click', function () {
				var on = !toggle.classList.contains('is-on');
				toggle.classList.toggle('is-on', on);
				toggle.setAttribute('aria-checked', on ? 'true' : 'false');
			});
		});

		var recaptchaThreshold = $('#bbcs-wiz-recaptcha-threshold');
		if (recaptchaThreshold) {
			recaptchaThreshold.addEventListener('input', function () {
				var value = $('#bbcs-wiz-recaptcha-threshold-value');
				if (value) value.textContent = recaptchaThreshold.value;
			});
		}

		$$('.bbcs-wizcard[data-init]').forEach(function (card) {
			card.addEventListener('click', function (e) {
				if (e.target.closest('.bbcs-wizcard-pro-overlay') || e.target.closest('.bbcs-wizcard-pro-link')) return;
				if (card.querySelector('.bbcs-wizcard-pro-overlay')) return;
				$$('.bbcs-wizcard[data-init]').forEach(function (c) { c.classList.remove('is-sel'); });
				card.classList.add('is-sel');
				state.selectedInitMode = card.getAttribute('data-init');
				saveProgress();
			});
		});

		$$('.bbcs-wizcard[data-cache]').forEach(function (card) {
			card.addEventListener('click', function () {
				if (card.style.opacity === '0.5') return;
				$$('.bbcs-wizcard[data-cache]').forEach(function (c) { c.classList.remove('is-sel'); });
				card.classList.add('is-sel');
				state.selectedCache = card.getAttribute('data-cache');
				saveProgress();
			});
		});

		$$('.bbcs-wizcheck').forEach(function (check) {
			check.addEventListener('click', function () {
				check.classList.toggle('is-on');
			});
		});

		// Back buttons for steps 1-7
		for (var i = 1; i <= 7; i++) {
			(function (step) {
				var btn = $('#bbcs-wiz-back' + step);
				if (btn) btn.addEventListener('click', function () { goBack(step - 1); });
			})(i);
		}

		// Next/Continue buttons for steps 2-6 (step 7 has its own handler that saves first)
		for (var j = 2; j <= 6; j++) {
			(function (step) {
				var btn = $('#bbcs-wiz-next' + step);
				if (btn) btn.addEventListener('click', function () { goToStep(step + 1); });
			})(j);
		}

		$('#bbcs-wiz-next7') && $('#bbcs-wiz-next7').addEventListener('click', saveCacheAndAdvance);

		$('#bbcs-wiz-fix-manual') && $('#bbcs-wiz-fix-manual').addEventListener('click', function () { goToStep(3); });

		// TODO: Add test attack button UI (#bbcs-wiz-run-test) - reserved for future step
		//$('#bbcs-wiz-run-test') && $('#bbcs-wiz-run-test').addEventListener('click', runTestAttack);
	}

	function restoreUIState() {
		var presetCard = $('.bbcs-wizcard[data-preset="' + state.selectedPreset + '"]');
		if (presetCard) {
			$$('.bbcs-wizcard[data-preset]').forEach(function (c) { c.classList.remove('is-sel'); });
			presetCard.classList.add('is-sel');
		}

		var captchaCard = $('.bbcs-wizcard[data-captcha="' + state.selectedCaptchaMode + '"]');
		updateCaptchaSelection(state.selectedCaptchaMode);
		if (captchaCard) captchaCard.classList.add('is-sel');

		var recaptchaCard = $('.bbcs-wizcard[data-recaptcha-provider="' + state.selectedRecaptchaProvider + '"]');
		if (recaptchaCard) {
			$$('.bbcs-wizcard[data-recaptcha-provider]').forEach(function (c) { c.classList.remove('is-sel'); });
			recaptchaCard.classList.add('is-sel');
		}
		$$('[data-recaptcha-fields]').forEach(function (fields) {
			fields.hidden = fields.getAttribute('data-recaptcha-fields') !== state.selectedRecaptchaProvider;
		});

		var initCard = $('.bbcs-wizcard[data-init="' + state.selectedInitMode + '"]');
		if (initCard) {
			$$('.bbcs-wizcard[data-init]').forEach(function (c) { c.classList.remove('is-sel'); });
			initCard.classList.add('is-sel');
		}

		var cacheCard = $('.bbcs-wizcard[data-cache="' + state.selectedCache + '"]');
		if (cacheCard) {
			$$('.bbcs-wizcard[data-cache]').forEach(function (c) { c.classList.remove('is-sel'); });
			cacheCard.classList.add('is-sel');
		}
	}

	function init() {
		restoreProgress();
		// Populate captcha names from the rendered option list so that the
		// finish summary shows the correct label for addon modes (90+).
		$$('.bbcs-wizcaptcha-option').forEach(function (option) {
			var id = parseInt(option.getAttribute('data-captcha'), 10);
			var titleEl = option.querySelector('.bbcs-wizcaptcha-option-title');
			if (id && titleEl) captchaNames[id] = titleEl.textContent.trim();
		});
		restoreUIState();
		bindEvents();
		initEmail();

		// Display current IP
		if (bbcsSetWizVars.current_ip) {
			var ipEl = $('#bbcs-wiz-myip');
			if (ipEl) ipEl.textContent = bbcsSetWizVars.current_ip;
		}

		showStep(state.currentStep);

		if (state.currentStep === 2) {
			runCompatibilityTests();
		} else if (state.currentStep === 8) {
			completeWizard();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
