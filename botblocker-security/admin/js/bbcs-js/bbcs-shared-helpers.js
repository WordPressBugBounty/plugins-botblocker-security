/* ============================================================
   bbcs-shared-helpers.js - shared scroll-to-setting + blink
   highlight utilities used by bbcs-snav.js and bbcs-multipage.js.

   Uses explicit data-anchor attributes to find the element that
   should be highlighted - no closest() guessing.
   ============================================================ */
(function ($) {
  'use strict';

  /**
   * Find the DOM element that corresponds to targetKey and smooth-scroll
   * to it, then trigger the blink highlight animation.
   *
   * Priority:
   * 1. [data-anchor="targetKey"] - explicit anchor set by PHP templates
   * 2. [data-addon-slug="targetKey"] - addon card slug (marketplace / installed)
   * 3. [data-field="targetKey"] - toggle button data-field attribute
   * 4. [name="bbcs_targetKey"], [name="targetKey"], etc. - form inputs
   * 5. #bbcs_targetKey, #targetKey, #bbcs-target-key - IDs
   * 6. [data-setting="targetKey"] - snav data-setting attribute
   */
  function findAndScrollToSetting(targetKey) {
    if (!targetKey) return;

    var $highlight = null;

    /* 1. Explicit data-anchor - the new preferred mechanism */
    $highlight = $('[data-anchor="' + targetKey + '"]').first();
    if ($highlight.length) {
      $highlight[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      triggerBlinkHighlight($highlight);
      return;
    }

    /* 2. Addon card by slug (data-addon-slug) - scroll the whole card */
    $highlight = $('[data-addon-slug="' + targetKey + '"]').first();
    if ($highlight.length) {
      $highlight[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      triggerBlinkHighlight($highlight);
      return;
    }

    /* 3. data-field on toggle buttons - highlight the parent .bbcs-option */
    var $fieldBtn = $('[data-field="' + targetKey + '"]').first();
    if ($fieldBtn.length) {
      var $row = $fieldBtn.closest('.bbcs-option, .bbcs-field, .bbcs_text_input, .bbcs-form-row, tr');
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        triggerBlinkHighlight($row);
        return;
      }
    }

    /* 4. Form inputs by name */
    var $input = $('[name="bbcs_' + targetKey + '"], [name="bbcs_' + targetKey + '[]"], [name="' + targetKey + '"], [name="' + targetKey + '[]"]').first();
    if ($input.length) {
      var $inputRow = $input.closest('.bbcs-option, .bbcs-field, .bbcs_text_input, .bbcs-form-row, tr');
      if ($inputRow.length) {
        $inputRow[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        triggerBlinkHighlight($inputRow);
        return;
      }
    }

    /* 5. By ID - including hyphenated variant for action buttons */
    var keyHyphen = targetKey.replace(/_/g, '-');
    var $byId = $('#bbcs_' + targetKey + ', #bbcs-' + keyHyphen + ', #' + keyHyphen + ', #' + targetKey + ', [data-setting="' + targetKey + '"]').first();
    if ($byId.length) {
      var $idRow = $byId.closest('.bbcs-option, .bbcs-field, .bbcs_text_input, .bbcs-form-row, tr');
      if (!$idRow.length) $idRow = $byId;
      $idRow[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      triggerBlinkHighlight($idRow);
      return;
    }
  }

  /**
   * Add the glow animation class, then remove it after 3.2 s.
   */
  function triggerBlinkHighlight($el) {
    $('.bbcs-flash-highlight').removeClass('bbcs-flash-highlight');
    $el.addClass('bbcs-flash-highlight');
    setTimeout(function () {
      $el.removeClass('bbcs-flash-highlight');
    }, 3200);
  }

  /**
   * Extract a query/hash parameter by name from the current URL.
   */
  function getUrlParam(name) {
    var m = window.location.search.match(new RegExp('[?&]' + name + '=([^&]+)'));
    return m ? decodeURIComponent(m[1]) : null;
  }

  /**
   * Read &focus=key from the URL hash OR from the query string,
   * activate the matching tab (if present), and scroll+highlight
   * the target element.
   */
  function checkUrlFocusAndJump() {
    // Check hash first: #tab&focus=key
    var hash = window.location.hash || '';
    var focusKey = null;
    var tabSlugForKey = null;
    var focusMatch = hash.match(/[?&]focus=([^&]+)/);
    if (focusMatch) {
      focusKey = decodeURIComponent(focusMatch[1]);
      var tabMatch = hash.match(/^#([^?&]+)/);
      if (tabMatch) tabSlugForKey = tabMatch[1];
    } else {
      // Fallback to query string: ?page=...&focus=key
      focusKey = getUrlParam('focus');
    }

    if (!focusKey) return;

    if (tabSlugForKey) {
      var $tabBtn = $('.bbcs-tab[data-tab="' + tabSlugForKey + '"], .bbcs-snav-item[data-snav-tab="' + tabSlugForKey + '"]');
      if ($tabBtn.length && !$tabBtn.hasClass('is-active')) {
        $tabBtn.trigger('click');
      }
    }

    setTimeout(function () {
      findAndScrollToSetting(focusKey);
    }, 250);
  }

  /**
   * Single Toastify wrapper shared by every admin JS module.
   * Falls back to alert() if Toastify hasn't loaded (script order issue, ad blocker, etc.).
   */
  window.bbcsToast = function (type, message) {
    if (typeof Toastify !== 'function') {
      alert(message);
      return;
    }
    var className = type === 'success' ? 'toast-success' : 'toast-error';
    var el = document.querySelector('.bbcs-app') || document.body;
    Toastify({
      text: message,
      duration: 6000,
      close: true,
      gravity: 'top',
      position: 'right',
      offset: { y: 65 },
      className: className,
      selector: el,
      stopOnFocus: true
    }).showToast();
  };

  /* Expose globally for other scripts */
  window.BBCS_Helpers = {
    findAndScrollToSetting: findAndScrollToSetting,
    triggerBlinkHighlight: triggerBlinkHighlight,
    checkUrlFocusAndJump: checkUrlFocusAndJump
  };

  /* ── Confirmation modal for delete/destructive actions ── */
  var confirmL10n = window.bbcsConfirmL10n || { title: 'Please Confirm', cancel: 'Cancel', confirm: 'Confirm' };

  window.bbcsConfirm = function(message, onConfirmCallback) {
    var overlay = document.createElement('div');
    overlay.className = 'bbcs-modal-overlay';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.background = 'rgba(0,0,0,0.5)';
    overlay.style.backdropFilter = 'blur(4px)';

    var modal = document.createElement('div');
    modal.className = 'bbcs-modal bbcs-pal';
    modal.style.pointerEvents = 'auto';
    modal.style.maxWidth = '400px';
    modal.style.width = '90%';

    var header = document.createElement('div');
    header.className = 'bbcs-modal-header bbcs-flex bbcs-align-center';
    header.style.padding = 'var(--bbcs-sp-3) var(--bbcs-sp-4)';
    header.style.borderBottom = '1px solid var(--bbcs-line)';
    header.style.justifyContent = 'space-between';

    var title = document.createElement('h3');
    title.className = 'bbcs-modal-title m-0 bbcs-fs-base';
    title.innerText = confirmL10n.title;

    var closeBtn = document.createElement('button');
    closeBtn.className = 'bbcs-modal-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.style.background = 'none';
    closeBtn.style.border = 'none';
    closeBtn.style.fontSize = '1.5rem';
    closeBtn.style.lineHeight = '1';
    closeBtn.style.cursor = 'pointer';

    header.appendChild(title);
    header.appendChild(closeBtn);

    var body = document.createElement('div');
    body.className = 'bbcs-modal-body';
    body.style.padding = 'var(--bbcs-sp-4)';
    body.innerText = message;

    var footer = document.createElement('div');
    footer.className = 'bbcs-modal-footer bbcs-flex bbcs-space-between';
    footer.style.padding = 'var(--bbcs-sp-3) var(--bbcs-sp-4)';
    footer.style.borderTop = '1px solid var(--bbcs-line)';
    footer.style.gap = 'var(--bbcs-sp-2)';
    footer.style.justifyContent = 'flex-end';

    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'bbcs-btn bbcs-btn--ghost';
    cancelBtn.innerText = confirmL10n.cancel;

    var confirmBtn = document.createElement('button');
    confirmBtn.type = 'button';
    confirmBtn.className = 'bbcs-btn bbcs-btn--danger';
    confirmBtn.innerText = confirmL10n.confirm;

    footer.appendChild(cancelBtn);
    footer.appendChild(confirmBtn);

    modal.appendChild(header);
    modal.appendChild(body);
    modal.appendChild(footer);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    function cleanup() {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }

    closeBtn.addEventListener('click', cleanup);
    cancelBtn.addEventListener('click', cleanup);
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) cleanup();
    });

    confirmBtn.addEventListener('click', function() {
      cleanup();
      if (typeof onConfirmCallback === 'function') {
        onConfirmCallback();
      }
    });
  };

  $(document).on('submit', 'form[data-bbcs-confirm]', function(e) {
    var $form = $(this);
    if (!$form.data('bbcs-confirmed')) {
      e.preventDefault();
      e.stopImmediatePropagation();
      var msg = $form.attr('data-bbcs-confirm');
      window.bbcsConfirm(msg, function() {
        $form.data('bbcs-confirmed', true);
        $form.trigger('submit');
      });
      return false;
    }
  });

  /* Modal shim */
  function bbcsModalElementAction(el, action) {
    if (!el) return;
    if (el.classList.contains('bbcs-modal-overlay')) {
      el.style.display = action === 'show' ? 'flex' : 'none';
      return;
    }
    if (action === 'show') {
      el.classList.add('show');
      document.body.classList.add('modal-open');
    } else {
      el.classList.remove('show');
      document.body.classList.remove('modal-open');
    }
  }

  function bbcsModalShow(el) {
    bbcsModalElementAction(el, 'show');
  }

  function bbcsModalHide(el) {
    bbcsModalElementAction(el, 'hide');
  }

  if (typeof $.fn.modal !== 'function') {
    $.fn.modal = function (action) {
      return this.each(function () {
        bbcsModalElementAction(this, action);
      });
    };
  }

  $(document).on('click', '[data-bs-dismiss="modal"]', function () {
    var $modal = $(this).closest('.modal, .bbcs-modal-overlay');
    if ($modal.length) {
      bbcsModalHide($modal[0]);
    }
  });

  $(document).on('click', '.modal.show', function (e) {
    if (e.target === this) {
      bbcsModalHide(this);
    }
  });

  $(document).on('click', '[data-modal-close]', function () {
    var overlay = this.closest('.bbcs-modal-overlay');
    if (overlay) {
      bbcsModalHide(overlay);
    }
  });

  window.BBCS_Helpers.modalShow = function (sel) {
    var $el = sel instanceof jQuery ? sel : $(sel);
    if ($el.length) bbcsModalShow($el[0]);
  };
  window.BBCS_Helpers.modalHide = function (sel) {
    var $el = sel instanceof jQuery ? sel : $(sel);
    if ($el.length) bbcsModalHide($el[0]);
  };

})(jQuery);
