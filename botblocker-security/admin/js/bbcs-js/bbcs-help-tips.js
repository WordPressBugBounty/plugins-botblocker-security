(function () {
  'use strict';

  var activeHelp = null;
  var GAP = 8;
  var PAD = 12;
  var ARROW_INSET = 24;

  function nearestHelp(node) {
    return node && node.closest ? node.closest('.bbcs-help') : null;
  }

  function resetTooltip(help) {
    var tip = help.querySelector('.bbcs-help-tip');
    if (!tip) {
      return;
    }
    tip.classList.remove('bbcs-help-tip--left', 'bbcs-help-tip--bottom', 'bbcs-help-tip--fixed');
    tip.style.top = '';
    tip.style.left = '';
    tip.style.right = '';
    tip.style.bottom = '';
    tip.style.removeProperty('--bbcs-tip-arrow-left');
    tip.style.display = '';
  }

  function positionTooltip(help) {
    var tip = help.querySelector('.bbcs-help-tip');
    if (!tip) {
      return;
    }

    tip.classList.remove('bbcs-help-tip--left', 'bbcs-help-tip--bottom', 'bbcs-help-tip--fixed');
    tip.style.top = '';
    tip.style.left = '';
    tip.style.right = '';
    tip.style.bottom = '';
    tip.style.removeProperty('--bbcs-tip-arrow-left');
    tip.style.display = 'block';

    var helpRect = help.getBoundingClientRect();
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var tipRect = tip.getBoundingClientRect();
    var tipW = tipRect.width;
    var tipH = tipRect.height;
    var placeBelow = false;
    var top = helpRect.top - tipH - GAP;

    if (top < PAD) {
      top = helpRect.bottom + GAP;
      placeBelow = true;
    }

    var helpCenter = helpRect.left + helpRect.width / 2;
    var left = helpCenter - tipW + ARROW_INSET;
    if (left < PAD) {
      left = PAD;
      tip.classList.add('bbcs-help-tip--left');
    } else if (left + tipW > vw - PAD) {
      left = vw - PAD - tipW;
    }

    if (top + tipH > vh - PAD) {
      top = vh - PAD - tipH;
    }
    if (top < PAD) {
      top = PAD;
    }

    tip.classList.add('bbcs-help-tip--fixed');
    if (placeBelow) {
      tip.classList.add('bbcs-help-tip--bottom');
    }

    tip.style.top = Math.round(top) + 'px';
    tip.style.left = Math.round(left) + 'px';

    var arrowLeft = helpCenter - left;
    if (arrowLeft < ARROW_INSET) {
      arrowLeft = ARROW_INSET;
    }
    if (arrowLeft > tipW - ARROW_INSET) {
      arrowLeft = tipW - ARROW_INSET;
    }
    tip.style.setProperty('--bbcs-tip-arrow-left', Math.round(arrowLeft) + 'px');
  }

  function activateHelp(help) {
    if (activeHelp && activeHelp !== help) {
      resetTooltip(activeHelp);
    }
    activeHelp = help;
    positionTooltip(help);
  }

  function deactivateHelp(help) {
    help.classList.remove('bbcs-help--pinned');
    resetTooltip(help);
    if (activeHelp === help) {
      activeHelp = null;
    }
  }

  function closePinned() {
    var pinned = document.querySelector('.bbcs-help--pinned');
    if (pinned) {
      deactivateHelp(pinned);
    }
  }

  document.addEventListener('click', function (e) {
    if (!nearestHelp(e.target)) {
      closePinned();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closePinned();
    }
  });

  function bind(root) {
    root.addEventListener('mouseover', function (e) {
      var help = nearestHelp(e.target);
      if (!help || help === activeHelp) {
        return;
      }
      activateHelp(help);
    }, true);

    root.addEventListener('mouseout', function (e) {
      var help = nearestHelp(e.target);
      if (!help) {
        return;
      }
      var to = e.relatedTarget;
      if (help.classList.contains('bbcs-help--pinned') || (to && help.contains(to))) {
        return;
      }
      deactivateHelp(help);
    }, true);

    root.addEventListener('click', function (e) {
      var help = nearestHelp(e.target);
      if (!help || !e.target.closest('.bbcs-help-q') || !help.querySelector('.bbcs-help-tip')) {
        return;
      }
      if (help.classList.contains('bbcs-help--pinned')) {
        deactivateHelp(help);
        return;
      }
      closePinned();
      help.classList.add('bbcs-help--pinned');
      activateHelp(help);
    }, true);

    root.addEventListener('keydown', function (e) {
      if ((e.key === 'Enter' || e.key === ' ') && e.target.classList && e.target.classList.contains('bbcs-help-q')) {
        e.preventDefault();
        e.target.click();
      }
    }, true);

    root.addEventListener('focusin', function (e) {
      var help = nearestHelp(e.target);
      if (!help) {
        return;
      }
      activateHelp(help);
    }, true);

    root.addEventListener('focusout', function (e) {
      var help = nearestHelp(e.target);
      if (!help) {
        return;
      }
      var to = e.relatedTarget;
      if (to && help.contains(to)) {
        return;
      }
      deactivateHelp(help);
    }, true);

    root.addEventListener('scroll', function () {
      if (activeHelp) {
        positionTooltip(activeHelp);
      }
    }, true);
  }

  var roots = document.querySelectorAll('.bbcs-settings-content');
  if (!roots.length) {
    return;
  }

  Array.prototype.forEach.call(roots, bind);

  var resizeTimer;
  window.addEventListener('resize', function () {
    if (!activeHelp) {
      return;
    }
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      if (activeHelp) {
        positionTooltip(activeHelp);
      }
    }, 100);
  });
})();
