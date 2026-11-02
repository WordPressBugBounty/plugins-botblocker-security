(function ($) {
	'use strict';

	$(function () {
		var cfg = window.bbcsDeactivationFeedback;
		if (!cfg || !cfg.pluginBasename) {
			return;
		}

		var overlay = document.getElementById('bbcsDeactivationFeedbackModal');
		if (!overlay) {
			return;
		}

		var pendingUrl = '';
		var $submit = $('#bbcs-deactivation-submit');
		var $skip = $('#bbcs-deactivation-skip');
		var $details = $('#bbcs-deactivation-details');
		var $detailsLabel = $('#bbcs-deactivation-details-label');
		var $followup = $('#bbcs-deactivation-followup');
		var $followupTitle = $('#bbcs-deactivation-followup-title');
		var $pauseError = $('#bbcs-deactivation-pause-error');
		var $rescue = $('#bbcs-deactivation-rescue');
		var $pauseBtn = $('#bbcs-deactivation-pause');
		var $pausedMsg = $('#bbcs-deactivation-paused-msg');
		var $content = $('#bbcs-deactivation-feedback-content');
		var $footer = $('#bbcs-deactivation-modal-footer');
		var $stayBtn = $('#bbcs-deactivation-stay');

		var followupReasons = cfg.followupReasons || [];
		var rescueReasons = cfg.rescueReasons || [];

		function isDeactivateLink(href) {
			if (!href || href.indexOf('action=deactivate') === -1) {
				return false;
			}
			return href.indexOf(cfg.pluginBasename) !== -1 || href.indexOf(encodeURIComponent(cfg.pluginBasename)) !== -1;
		}

		function showModal() {
			if (window.BBCS_Helpers && window.BBCS_Helpers.modalShow) {
				window.BBCS_Helpers.modalShow('#bbcsDeactivationFeedbackModal');
			} else {
				overlay.style.display = 'flex';
			}
		}

		function hideModal() {
			if (window.BBCS_Helpers && window.BBCS_Helpers.modalHide) {
				window.BBCS_Helpers.modalHide('#bbcsDeactivationFeedbackModal');
			} else {
				overlay.style.display = 'none';
			}
		}

		function getSelectedReason() {
			var selected = document.querySelector('input[name="bbcs_deactivation_reason"]:checked');
			return selected ? selected.value : '';
		}

		function getSelectedWhatBroke() {
			var selected = document.querySelector('input[name="bbcs_deactivation_what_broke"]:checked');
			return selected ? selected.value : '';
		}

		function proceedDeactivate() {
			if (!pendingUrl) {
				return;
			}
			window.location.href = pendingUrl;
		}

		function resetModalState() {
			$('input[name="bbcs_deactivation_reason"]').prop('checked', false);
			$('input[name="bbcs_deactivation_what_broke"]').prop('checked', false);
			$details.val('');
			$detailsLabel.text(cfg.i18n.details);
			$followup.hide();
			$rescue.hide();
			$pauseError.hide().text('');
			$content.show();
			$footer.show();
			$pausedMsg.hide();
			$submit.prop('disabled', false).text(cfg.i18n.submit);
			$pauseBtn.prop('disabled', false).text(cfg.i18n.pauseProtection);
		}

		function updateFollowupOptions(reason) {
			$followup.find('label[data-only-reasons]').each(function () {
				var only = $(this).attr('data-only-reasons');
				var visible = only === '' || only.split(',').indexOf(reason) !== -1;
				$(this).toggle(visible);
				if (!visible) {
					$(this).find('input').prop('checked', false);
				}
			});
			if (reason === 'temporary') {
				$followupTitle.text(cfg.i18n.questionTemp);
			} else {
				$followupTitle.text(cfg.i18n.questionBroke);
			}
		}

		function onReasonChange() {
			var reason = getSelectedReason();
			if (followupReasons.indexOf(reason) !== -1) {
				updateFollowupOptions(reason);
				$followup.slideDown(150);
			} else {
				$followup.slideUp(150);
				$('input[name="bbcs_deactivation_what_broke"]').prop('checked', false);
			}

			if (rescueReasons.indexOf(reason) !== -1) {
				$rescue.slideDown(150);
			} else {
				$rescue.slideUp(150);
			}

			if (reason === 'other') {
				$detailsLabel.text(cfg.i18n.detailsOther);
				$details.trigger('focus');
			} else {
				$detailsLabel.text(cfg.i18n.details);
			}
		}

		function storeFeedbackAndDeactivate() {
			var reason = getSelectedReason();
			if (!reason) {
				// No reason picked: skip sending feedback, deactivate anyway.
				proceedDeactivate();
				return;
			}

			$submit.prop('disabled', true).text(cfg.i18n.submitting);

			$.post(cfg.ajaxurl, {
				action: 'bbcs_store_deactivation_feedback',
				nonce: cfg.nonce,
				reason: reason,
				what_broke: getSelectedWhatBroke(),
				details: $details.val()
			})
				.always(function () {
					// Feedback is best-effort: never block deactivation on a failed/rate-limited request.
					proceedDeactivate();
				});
		}

		function pauseProtection() {
			$pauseError.hide().text('');
			$pauseBtn.prop('disabled', true).text(cfg.i18n.pausing);

			$.post(cfg.ajaxurl, {
				action: 'bbcs_pause_protection',
				nonce: cfg.nonce
			})
				.done(function (response) {
					if (response && response.success) {
						$content.hide();
						$footer.hide();
						$pausedMsg.fadeIn(150);
					} else {
						onPauseFailed();
					}
				})
				.fail(onPauseFailed);
		}

		function onPauseFailed() {
			$pauseBtn.prop('disabled', false).text(cfg.i18n.pauseProtection);
			$pauseError.text(cfg.i18n.pauseFailed).show();
		}

		$(document).on('click', 'a', function (e) {
			var href = $(this).attr('href') || '';
			if (!isDeactivateLink(href)) {
				return;
			}

			e.preventDefault();
			pendingUrl = href;
			resetModalState();
			showModal();
		});

		$(document).on('change', 'input[name="bbcs_deactivation_reason"]', onReasonChange);

		$pauseBtn.on('click', function () {
			pauseProtection();
		});

		$stayBtn.on('click', function () {
			hideModal();
			window.location.reload();
		});

		$skip.on('click', function () {
			hideModal();
			proceedDeactivate();
		});

		$submit.on('click', function () {
			storeFeedbackAndDeactivate();
		});
	});
})(jQuery);
