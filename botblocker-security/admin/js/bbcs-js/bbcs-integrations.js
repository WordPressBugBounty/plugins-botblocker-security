(function ($) {
    "use strict";

    var bbcsFileCacheCap = document.getElementById('bbcs_file_cache_max_files');
    if (bbcsFileCacheCap) {
        bbcsFileCacheCap.addEventListener('input', function () {
            document.getElementById('bbcs_file_cache_max_files_value').textContent = this.value;
        });
    }
    var bbcsRecaptchaThreshold = document.getElementById('bbcs_recaptcha_tresshold');
    if (bbcsRecaptchaThreshold) {
        bbcsRecaptchaThreshold.addEventListener('input', function () {
            document.getElementById('bbcs_recaptcha_tresshold_value').textContent = this.value;
        });
    }
})(jQuery);

(function($) {
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#bbcs-integrations-form').forEach(function (form) {
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
      $('[data-bbcs-reset]').prop('disabled', !dirty).toggleClass('is-disabled', !dirty);
    }

    function checkDirty() {
      var now = $form.serialize();
      dirty = (now !== initial);
      updateUI();
    }

    ensureIndicators();
    updateUI();

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

(function ($) {
  "use strict";
  $(function () {
    var $form = $('#bbcs-integrations-form');

    function findKeyInputs(names) {
      return $form.find(names.map(function (name) { return '[name="' + name + '"]'; }).join(','));
    }

    function allFilled($inputs, expected) {
      var filled = $inputs.filter(function () { return $.trim(this.value) !== ''; });
      return filled.length === expected;
    }

    function setFormValue(field, value) {
      var $input = $form.find('input[name="' + field + '"]');
      if (!$input.length) {
        $input = $('<input type="hidden">').attr('name', field).appendTo($form);
      }
      $input.val(value);
    }

    function saveForm() {
      $form.find('button[name="save_settings"]').get(0).click();
    }

    $form.find('[data-bbcs-use-recaptcha]').each(function () {
      var $btn = $(this);
      var $needKeys = $('#' + $btn.attr('data-need'));
      var keyNames = $btn.attr('data-keys').split(',');
      var $keys = findKeyInputs(keyNames);

      function sync() {
        var ready = allFilled($keys, keyNames.length);
        $btn.prop('disabled', !ready);
        $needKeys.prop('hidden', ready);
      }

      $keys.on('input change', sync);
      sync();

      $btn.on('click', function () {
        setFormValue($btn.attr('data-field'), $btn.attr('data-value'));
        $btn.prop('disabled', true);
        saveForm();
      });
    });
  });
})(jQuery);

(function ($) {
  "use strict";
  $(function () {
    var $panel = $('[data-tabpanel="wp-abilities"]');
    if (!$panel.length) return;

    var $master = $panel.find('.bbcs-toggle[data-field="bbcs_wp_abilities_enabled"]');
    var $abilities = $panel.find('.bbcs-toggle[data-field^="bbcs_ability_"]');

    function setState($toggles, on) {
      $toggles.each(function () {
        var $toggle = $(this);
        if ($toggle.hasClass('is-on') !== on) {
          $toggle.trigger('click');
        }
      });
    }

    function scope(name) {
      return $panel.find('[data-bbcs-ability-scope="' + name + '"] .bbcs-toggle');
    }

    $panel.on('click', '[data-bbcs-abilities-bulk]', function (event) {
      event.preventDefault();
      var mode = $(this).attr('data-bbcs-abilities-bulk');

      if (mode === 'enable-all') {
        setState($master, true);
        setState($abilities, true);
      } else if (mode === 'disable-all') {
        setState($master, false);
        setState($abilities, false);
      } else if (mode === 'read-only') {
        setState($master, true);
        setState($abilities, false);
        setState(scope('read'), true);
      }
    });
  });
})(jQuery);
