/**
 * Add-on settings form - unsaved changes detection and anchor tracking.
 *
 * Mirrors the approach in bbcs-settings.js: jQuery .serialize() for
 * dirty detection so that checkboxes, hidden-backed toggles, and all
 * other controls are detected consistently.
 *
 * @package BotBlocker
 */
(function ($) {
	'use strict';

	$(function () {
		var $forms = $('form.bbcs-addon-settings-form');
		if (!$forms.length) return;

		// Anchor: set from the current URL hash on every form submit.
		$forms.on('submit', function () {
			var fld = this.querySelector('input[name="bbcs_anchor"]');
			if (fld) {
				fld.value = (window.location.hash || '').replace(/^#/, '');
			}
		});

		// Unsaved changes detection - identical logic to the main settings page.
		$forms.each(function () {
			var $form = $(this);
			var initial = $form.serialize();
			var $unsavedLabel = $form.find('.bbcs-unsaved-label');

			function checkDirty() {
				var dirty = $form.serialize() !== initial;
				$unsavedLabel.css('display', dirty ? '' : 'none');
			}

			$form.on('change input', 'input, select, textarea', checkDirty);
		});

		$(document).on('click', 'button[name="save_settings"]', function () {
			$('.bbcs-unsaved-label').css('display', 'none');
		});

		// Warn on navigation away if ANY form has unsaved changes.
		$(window).on('beforeunload', function (e) {
			var hasDirty = false;
			$('.bbcs-unsaved-label').each(function () {
				if ($(this).css('display') !== 'none') {
					hasDirty = true;
					return false;
				}
			});
			if (hasDirty) {
				e.preventDefault();
			}
		});
	});
})(jQuery);
