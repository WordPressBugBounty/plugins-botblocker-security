(function () {
  'use strict';

  var CSS_MAP = {
    success: 'toast-success',
    error: 'toast-error',
    warning: 'toast-warning',
    info: 'toast-info'
  };

  window.bbcsToast = function (type, message) {
    if (typeof Toastify !== 'function') {
      alert(message);
      return;
    }
    var className = CSS_MAP[type] || 'toast-error';
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

  window.bbcsRulesToast = window.bbcsToast;

  var confirmL10n = window.bbcsConfirmL10n || { title: 'Please Confirm', cancel: 'Cancel', confirm: 'Confirm' };

  window.bbcsConfirm = function (message, onConfirmCallback) {
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
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) cleanup();
    });

    confirmBtn.addEventListener('click', function () {
      cleanup();
      if (typeof onConfirmCallback === 'function') {
        onConfirmCallback();
      }
    });
  };
})();
