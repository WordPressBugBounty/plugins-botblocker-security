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

		function proceedDeactivate() {
			if (!pendingUrl) {
				return;
			}
			window.location.href = pendingUrl;
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
				details: $details.val()
			})
				.always(function () {
					// Feedback is best-effort: never block deactivation on a failed/rate-limited request.
					proceedDeactivate();
				});
		}

		$(document).on('click', 'a', function (e) {
			var href = $(this).attr('href') || '';
			if (!isDeactivateLink(href)) {
				return;
			}

			e.preventDefault();
			pendingUrl = href;
			$('input[name="bbcs_deactivation_reason"]').prop('checked', false);
			$details.val('');
			$submit.prop('disabled', false).text(cfg.i18n.submit);
			showModal();
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
