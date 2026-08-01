(function($) {
 
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#bbcs-tools-form').forEach(function (form) {
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
    var $form = $('#bbcs-tools-form');
    if (!$form.length) return;

    var $saveButtons = $form.find('button[name="save_settings"]');
    if (!$saveButtons.length) return;

    function serializeSettings() {
      return $form.find(':input').not('[data-bbcs-no-dirty]').serialize();
    }

    var initial = serializeSettings();
    var dirty = false;
    var isSaving = false;

    function bypassDirtyPrompt() {
      window.bbcsBypassUnsavedPrompt = true;
      dirty = false;
      isSaving = true;
      updateUI();
    }

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
      var now = serializeSettings();
      dirty = (now !== initial);
      updateUI();
    }

    ensureIndicators();
    updateUI();

    $form.on('change input keyup', 'input:not([data-bbcs-no-dirty]), select:not([data-bbcs-no-dirty]), textarea:not([data-bbcs-no-dirty])', checkDirty);

    $form.on('click', 'button[name="save_settings"]', function () {
      isSaving = true;
    });

    $form.on('click', '[data-bbcs-bypass-dirty], button[formaction]', bypassDirtyPrompt);

    window.addEventListener('beforeunload', function (e) {
      if (window.bbcsBypassUnsavedPrompt) {
        return;
      }

      if (dirty) {
        e.preventDefault();
        e.returnValue = '';
      }
    });

    $form.on('submit', function (event) {
      var submitter = event.originalEvent && event.originalEvent.submitter ? event.originalEvent.submitter : document.activeElement;
      if (submitter && $(submitter).is('[data-bbcs-bypass-dirty], button[formaction]')) {
        bypassDirtyPrompt();
      }

      if (isSaving || window.bbcsBypassUnsavedPrompt) {
        dirty = false;
        updateUI();
        isSaving = false;
      }
    });
  });
})(jQuery);
