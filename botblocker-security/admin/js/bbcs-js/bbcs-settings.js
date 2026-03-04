(function ($) {
    "use strict";

    function toggleImgPack() {
        var mode = $('select[name="bbcs_captcha_mode"]').val();
        var $pack = $('#bbcs_captcha_img_pack');
        var $inline = $('#bbcs_captcha_img_inline');
        if (mode === '2') {
            $pack.prop('disabled', false);
            $inline.prop('disabled', false);
        } else {
            $pack.prop('disabled', true);
            $inline.prop('disabled', true);
        }
    }

    $(document).on('change', 'select[name="bbcs_captcha_mode"]', toggleImgPack);

    $(document).ready(function () {
        toggleImgPack();
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
    var $form = $('form[action$="admin-post.php"]').first();
    if (!$form.length) return;

    var $saveButtons = $form.find('button[name="save_settings"]');
    if (!$saveButtons.length) return;

    var initial = $form.serialize();
    var dirty = false;
    var isSaving = false;

    function ensureIndicators() {
      $saveButtons.each(function () {
        var $btn = $(this);
        if (!$btn.prev('.bbcs-unsaved-label').length) {
          $('<span class="bbcs-unsaved-label" style="font-weight: 700;margin-right:8px;color:#dc3545;display:none;">' + (window.bbcsUnsavedLabel || 'Not saved!') + '</span>').insertBefore($btn);
        }
      });
    }

    function updateUI() {
      var show = dirty;
      $form.find('.bbcs-unsaved-label').css('display', show ? 'inline-block' : 'none');
      $saveButtons.find('.bbcs-card-action').css('color', show ? '#dc3545' : '');
    }

    function checkDirty() {
      var now = $form.serialize();
      dirty = (now !== initial);
      updateUI();
    }

    ensureIndicators();

    $form.on('change input keyup', 'input, select, textarea', checkDirty);

    $form.on('click', 'button[name="save_settings"]', function () {
      isSaving = true;
    });

    window.addEventListener('beforeunload', function (e) {
      if (dirty) {
        e.preventDefault();
        e.returnValue = '';
      }
    });

    $form.on('submit', function () {
      if (isSaving) {
        dirty = false;
        updateUI();
        isSaving = false;
      }
    });
  });
})(jQuery);