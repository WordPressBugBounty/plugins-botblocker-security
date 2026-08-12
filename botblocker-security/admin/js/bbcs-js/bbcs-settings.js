(function ($) {
    "use strict";

    function toggleImgPack() {
        var mode = $('input[name="bbcs_captcha_mode"]').val();
        var enabled = mode === '2';

        // Image Delivery Mode
        var $inlineHidden = $('input[name="bbcs_captcha_img_inline"]');
        var $inlineSelect = $inlineHidden.closest('.bbcs-field').find('.bbcs-select');
        $inlineHidden.prop('disabled', !enabled);
        $inlineSelect.toggleClass('is-disabled', !enabled);

        // Image Captcha Pack
        var $packHidden = $('input[name="bbcs_captcha_img_pack"]');
        var $packSelect = $packHidden.closest('.bbcs-field').find('.bbcs-select');
        $packHidden.prop('disabled', !enabled);
        $packSelect.toggleClass('is-disabled', !enabled);
    }

    $(document).on('change', 'input[name="bbcs_captcha_mode"]', toggleImgPack);

    function togglePaymentSubOptions() {
        // Check toggle button's is-on class (hidden input is never :checked).
        var $mainBtn = $('.bbcs-toggle[data-field="payment_bypass_enable"]');
        var enabled = $mainBtn.hasClass('is-on');
        // Disable sub-option toggle buttons (prevent clicks) AND hidden inputs (prevent submit).
        // Disabled inputs are excluded from form POST, matching old checkbox behavior.
        $('.bbcs-toggle[data-field="payment_strict_method"]').prop('disabled', !enabled);
        $('.bbcs-toggle[data-field="payment_keep_ip_rules"]').prop('disabled', !enabled);
        $('input[name="payment_strict_method"]').prop('disabled', !enabled);
        $('input[name="payment_keep_ip_rules"]').prop('disabled', !enabled);
    }

    // The change event fires on the hidden input thanks to multipage.js trigger('change').
    $(document).on('change', 'input[name="payment_bypass_enable"]', togglePaymentSubOptions);

    $(document).ready(function () {
        toggleImgPack();
        togglePaymentSubOptions();
    });

})(jQuery);

(function($) {
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[action$="admin-post.php"]').forEach(function (form) {
      form.addEventListener('submit', function () {
        var fld = form.querySelector('input[name="bbcs_anchor"]');
        if (fld) { fld.value = (window.location.hash || '').replace(/^#/, ''); }
      });
    });
  });
})(jQuery);

(function ($) {
  "use strict";
  $(function () {
    var $form = $('#bbcs-settings-form');
    if (!$form.length) return;

    var $saveBtn = $('#bbcs-save-settings-btn');
    if (!$saveBtn.length) return;
    var $unsavedLabel = $('#bbcs-unsaved-label');

    var initial = $form.serialize();
    var dirty = false;

    function updateUI() {
      $unsavedLabel.css('display', dirty ? 'inline-block' : 'none');
      $('[data-bbcs-reset]').prop('disabled', !dirty).toggleClass('is-disabled', !dirty);
    }

    function checkDirty() {
      var now = $form.serialize();
      dirty = (now !== initial);
      updateUI();
    }

    // Set initial state: "Not saved!" hidden when form matches initial.
    updateUI();

    $form.on('change input keyup', 'input, select, textarea', checkDirty);

    // Disable action buttons on save to prevent double-submit and accidental navigation.
    // The save button itself is disabled asynchronously so the form submission isn't blocked.
    function disableActions() {
      // Reset button (the non-submit button in the pagehead)
      $('.bbcs-pagehead-actions button[type="button"]').prop('disabled', true).addClass('is-disabled');
      // One-click setup link
      $('#bbcsOpenOneClickSetup').addClass('is-disabled');
      // Defer disabling the save button itself so the browser can submit the form
      setTimeout(function () { $saveBtn.prop('disabled', true).addClass('is-disabled'); }, 0);
    }

    // Reset dirty immediately on save-button click (before beforeunload can fire).
    $saveBtn.on('click', function () {
      dirty = false;
      updateUI();
      disableActions();
    });

    // beforeunload: only warn when dirty, but save-button has already cleared dirty.
    window.addEventListener('beforeunload', function (e) {
      if (dirty) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  });
})(jQuery);

(function($) {
	function readJSONFile(file, callback) {
		var reader = new FileReader();
		reader.onload = function(e) {
			try {
				var data = JSON.parse(e.target.result);
				callback(data);
			} catch (err) {
				alert(bbcsTlsL10n.invalid_json + err.message);
			}
		};
		reader.readAsText(file);
	}

	$("#bbcs_tls_import").on("click", function() {
		var fileInput = $("<input>", {
			type: "file",
			accept: "application/json",
		}).on("change", function() {
			var file = this.files[0];
			if (file) {
				readJSONFile(file, function(data) {
					$.ajax({
						url: botblockerData.ajaxurl,
						type: "POST",
						data: {
							action: "bbcs_import_tls_fingerprints",
							fingerprints: JSON.stringify(data),
							nonce: botblockerData.nonce,
						},
						success: function(response) {
							if (response.success) {
								alert(bbcsTlsL10n.import_success + ': ' + response.data.imported + ' ' + (bbcsTlsL10n.imported || 'imported') + ', ' + response.data.skipped + ' ' + (bbcsTlsL10n.skipped || 'skipped') + '.');
							} else {
								alert(bbcsTlsL10n.failed_import + response.data);
							}
						},
					});
				});
			}
		});
		fileInput.click();
	});

	$("#bbcs_tls_clear").on("click", function() {
		if (confirm(bbcsTlsL10n.confirm_ask)) {
			$.ajax({
				url: botblockerData.ajaxurl,
				type: "POST",
				data: {
					action: "bbcs_clear_all_tls_fingerprints",
					nonce: botblockerData.nonce,
				},
				success: function(response) {
					if (response.success) {
						alert(bbcsTlsL10n.cleared);
					} else {
						alert(bbcsTlsL10n.failed_clear + response.data);
					}
				},
			});
		}
	});

	$("#bbcs_tls_sync").on("click", function() {
		var btn = $(this);
		btn.prop("disabled", true).html('<i class="fa-solid fa-sync"></i> ' + bbcsTlsL10n.syncing_process);
		$.ajax({
			url: botblockerData.ajaxurl,
			type: "POST",
			data: {
				action: "bbcs_sync_tls_fingerprints",
				nonce: botblockerData.nonce,
			},
			success: function(response) {
				if (response.success) {
					alert(bbcsTlsL10n.sync_success + ' (' + response.data.fingerprint_count + ' fingerprints)');
				} else {
					alert(bbcsTlsL10n.failed_sync + response.data);
				}
			},
			complete: function() {
				btn.prop("disabled", false).html('<i class="fa-solid fa-sync"></i> ' + bbcsTlsL10n.sync_now);
			},
		});
	});
})(jQuery);


