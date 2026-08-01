/* ── Shared vertical nav sidebar (snav) ── */
(function ($) {
  'use strict';

  /* ── Shared helpers via BBCS_Helpers (bbcs-shared-helpers.js) ── */
  var findAndScrollToSetting = (window.BBCS_Helpers && window.BBCS_Helpers.findAndScrollToSetting) || function () {};
  var triggerBlinkHighlight = (window.BBCS_Helpers && window.BBCS_Helpers.triggerBlinkHighlight) || function () {};

  $(function () {
    var $nav = $('.bbcs-snav');
    if (!$nav.length) return;

    var $page = $nav.closest('.bbcs-page');
    var $search = $nav.find('.bbcs-snav-search-input');
    var $clearBtn = $nav.find('.bbcs-snav-search-clear');
    var $items = $nav.find('.bbcs-snav-item');
    var $groups = $nav.find('.bbcs-snav-group');
    var $toggle = $('.bbcs-snav-toggle');
    var $modeCheckbox = $nav.find('.bbcs-snav-mode-checkbox');

    /* ── Build sub-items from BOTBLOCKER_GLOBAL_SEARCH_INDEX ── */
    var indexData = typeof BOTBLOCKER_GLOBAL_SEARCH_INDEX !== 'undefined' ? BOTBLOCKER_GLOBAL_SEARCH_INDEX : null;

    if (indexData) {
      var tabMap = {};
      $.each(indexData, function (_, group) {
        $.each(group.tabs, function (_, tab) {
          tabMap[tab.tab] = tab;
        });
      });

      $items.each(function () {
        var $item = $(this);
        var tabId = $item.data('snav-tab');
        if (!tabId || !tabMap[tabId]) return;

        var tab = tabMap[tabId];
        var $sub = $('<div class="bbcs-snav-subitems">');

        $.each(tab.sg, function (_, sg) {
          var $sg = $('<div class="bbcs-snav-subgroup">');
          $sg.append('<span class="bbcs-snav-subgroup-label">' + $('<span>').text(sg.t).html() + '</span>');
          $.each(sg.s, function (_, setting) {
            var labelStr = Array.isArray(setting) ? setting[0] : setting;
            var targetKey = Array.isArray(setting) && setting[1] ? setting[1] : '';
            var esc = $('<span>').text(labelStr).html();
            var escKey = $('<span>').text(targetKey).html();
            $sg.append('<span class="bbcs-snav-setting" data-setting="' + esc + '" data-key="' + escKey + '">' + esc + '</span>');
          });
          $sub.append($sg);
        });

        $item.append($sub);
      });
    }

    var $settings = $nav.find('.bbcs-snav-setting');
    var $collapseBtn = $nav.find('.bbcs-snav-collapse-btn');
    // Derive advanced tab IDs from nav items that have data-simple="0".
    var advancedTabIds = [];
    $nav.find('.bbcs-snav-item[data-simple="0"]').each(function () {
      var tabId = $(this).data('snav-tab');
      if (tabId) advancedTabIds.push(tabId);
    });

    /* ══════════════════════════════════════════
       Simple/complex mode
       ══════════════════════════════════════════ */

    var STORAGE_KEY_MODE = 'bbcs_snav_simple_mode';
    var STORAGE_KEY_COLLAPSED = 'bbcs_snav_collapsed';
    var STORAGE_KEY_EXPANDED = 'bbcs_snav_expanded';

    var hasModeToggle = $modeCheckbox.length > 0;

    function isSimpleMode() {
      return hasModeToggle && $modeCheckbox.prop('checked');
    }

    function toggleAdvancedVisibility(simple) {
      if (!hasModeToggle) return;
      // Determine which tab is currently active
      var $activeItem = $items.filter('.is-active');
      var activeTabId = $activeItem.length ? $activeItem.data('snav-tab') : null;

      // Show/hide advanced tab panels
      $.each(advancedTabIds, function (_, tabId) {
        var $panel = $page.find('[data-tabpanel="' + tabId + '"]');
        if ($panel.length) {
          if (simple) {
            $panel.attr('hidden', true).attr('aria-hidden', 'true');
          } else {
            if (tabId === activeTabId) {
              $panel.removeAttr('hidden').removeAttr('aria-hidden');
            } else {
              $panel.attr('hidden', true).attr('aria-hidden', 'true');
            }
          }
        }
      });
      // CSS class .is-simple-mode on $nav handles advanced nav item visibility
      // via the rule: .is-simple-mode .is-advanced { display: none; }
      // Hide groups that have ONLY advanced items (no simple items) in simple mode
      $groups.each(function () {
        var $group = $(this);
        if (simple) {
          var $allItems = $group.find('.bbcs-snav-item');
          var $simpleItems = $allItems.not('.is-advanced');
          $group.toggle($simpleItems.length > 0);
        } else {
          $group.show();
        }
      });
    }

    function setSimpleMode(simple) {
      if (!hasModeToggle) return;
      $modeCheckbox.prop('checked', simple);
      if (simple) {
        $nav.addClass('is-simple-mode');
      } else {
        $nav.removeClass('is-simple-mode');
      }
      toggleAdvancedVisibility(simple);
      try { localStorage.setItem(STORAGE_KEY_MODE, simple ? '1' : '0'); } catch (_) {}
    }

    function getExpandedSet() {
      try {
        var raw = localStorage.getItem(STORAGE_KEY_EXPANDED);
        return raw ? JSON.parse(raw) : {};
      } catch (_) { return {}; }
    }

    function saveExpandedSet(set) {
      try { localStorage.setItem(STORAGE_KEY_EXPANDED, JSON.stringify(set)); } catch (_) {}
    }

    function isItemExpanded(tabId) {
      var set = getExpandedSet();
      return !!set[tabId];
    }

    function setItemExpanded(tabId, expanded) {
      var set = getExpandedSet();
      if (expanded) {
        set[tabId] = true;
      } else {
        delete set[tabId];
      }
      saveExpandedSet(set);
    }

    function expandItem($item) {
      var tabId = $item.data('snav-tab');
      $item.addClass('is-expanded');
      setItemExpanded(tabId, true);
    }

    function collapseItem($item) {
      var tabId = $item.data('snav-tab');
      $item.removeClass('is-expanded');
      setItemExpanded(tabId, false);
    }

    function applySimpleMode() {
      if (isSimpleMode()) {
        $nav.addClass('is-simple-mode');
      } else {
        $nav.removeClass('is-simple-mode');
      }
      toggleAdvancedVisibility(isSimpleMode());
    }

    function switchToFirstSimpleTabIfAdvanced() {
      if (!hasModeToggle || !isSimpleMode()) return;
      var $active = $items.filter('.is-active');
      if ($active.hasClass('is-advanced')) {
        var $firstSimple = $items.not('.is-advanced').first();
        if ($firstSimple.length) {
          $items.removeClass('is-active').attr('aria-current', 'false');
          $firstSimple.trigger('click');
        }
      }
    }

    /* Strip PHP-rendered is-simple-mode if this page has no mode toggle */
    if (!hasModeToggle) {
      $nav.removeClass('is-simple-mode');
    }

    /* ── Restore simple mode from localStorage, but honour URL hash ── */
    if (hasModeToggle) {
      var initHash = (window.location.hash || '').replace(/^#/, '').toLowerCase();
      var initTabHash = initHash.split('&')[0];

      // Check if the URL hash targets an advanced tab - if so, force simple mode OFF
      var $hashTarget = initTabHash ? $items.filter(function () {
        var id = ($(this).data('snav-tab') || '').replace(/_/g, '-').toLowerCase();
        return id === initTabHash;
      }) : $();

      if ($hashTarget.hasClass('is-advanced')) {
        // Hash points to an advanced tab - override saved mode, turn simple OFF
        try { localStorage.setItem(STORAGE_KEY_MODE, '0'); } catch (_) {}
        setSimpleMode(false);
      } else {
        // Restore simple mode from localStorage as usual
        try {
          var savedMode = localStorage.getItem(STORAGE_KEY_MODE);
          var modeOn = savedMode !== '0'; // defaults to '1' (simple)
          setSimpleMode(modeOn);
        } catch (_) {
          setSimpleMode(true);
        }
      }
    }

    /* ── Mode switch change ── */
    if (hasModeToggle) {
      $modeCheckbox.on('change', function () {
        setSimpleMode($(this).prop('checked'));
        switchToFirstSimpleTabIfAdvanced();
      });
    }

    /* ══════════════════════════════════════════
       Global collapse/expand (chevron button)
       ══════════════════════════════════════════ */

    function setCollapsed(collapsed) {
      var $use = $collapseBtn.find('use');
      if (collapsed) {
        $nav.addClass('is-collapsed');
        $collapseBtn.attr('data-collapsed', 'true')
          .attr('title', 'Unfold all settings')
          .attr('aria-label', 'Unfold all settings');
        if ($use.length) {
          $use.attr('href', '#bbcs-i-chevrons-up-down');
        }
      } else {
        $nav.removeClass('is-collapsed');
        $collapseBtn.attr('data-collapsed', 'false')
          .attr('title', 'Fold all except current setting')
          .attr('aria-label', 'Fold all except current setting');
        if ($use.length) {
          $use.attr('href', '#bbcs-i-chevrons-down-up');
        }
      }
      try { localStorage.setItem(STORAGE_KEY_COLLAPSED, collapsed ? '1' : '0'); } catch (_) {}
    }

    // Default: folded mode ON (is-collapsed) unless explicitly saved unfolded ('0')
    try {
      var savedCollapsed = localStorage.getItem(STORAGE_KEY_COLLAPSED);
      var isFolded = savedCollapsed !== '0'; // default true (folded)
      setCollapsed(isFolded);
    } catch (_) {
      setCollapsed(true);
    }

    $collapseBtn.on('click', function () {
      var isCollapsed = $nav.hasClass('is-collapsed');
      setCollapsed(!isCollapsed);
    });

    /* ══════════════════════════════════════════
       Mobile toggle
       ══════════════════════════════════════════ */

    $toggle.on('click', function () {
      var isOpen = $nav.hasClass('is-open');
      if (isOpen) {
        $nav.removeClass('is-open');
        $toggle.removeClass('is-open').attr('aria-expanded', 'false');
      } else {
        $nav.addClass('is-open');
        $toggle.addClass('is-open').attr('aria-expanded', 'true');
      }
    });

    /* ══════════════════════════════════════════
       Click a nav item → show matching tabpanel
       ══════════════════════════════════════════ */

    $nav.on('click', '.bbcs-snav-item', function (e) {
      e.preventDefault();
      var $item = $(this);
      var tabId = $item.data('snav-tab');
      if (!tabId) return;

      $items.removeClass('is-active').attr('aria-current', 'false');
      $item.addClass('is-active').attr('aria-current', 'true');

      var $panel = $page.find('[data-tabpanel="' + tabId + '"]');
      if ($panel.length) {
        $page.find('.bbcs-tabpanel').attr('hidden', true).attr('aria-hidden', 'true');
        $panel.removeAttr('hidden').removeAttr('aria-hidden');
      }

      var slug = tabId.replace(/\s+/g, '-').replace(/_/g, '-').toLowerCase();
      var focusMatch = (window.location.hash || '').match(/[?&]focus=([^&]+)/);
      var focusSuffix = focusMatch ? '&focus=' + focusMatch[1] : '';

      // Marketplace items: set hash to #marketplace&focus=slug so &focus highlights the card
      if (tabId.indexOf('market-') === 0) {
        focusSuffix = '&focus=' + encodeURIComponent(tabId.slice(7));
        try { history.replaceState(null, '', '#marketplace' + focusSuffix); } catch (_) {}
        var fn = (window.BBCS_Helpers && window.BBCS_Helpers.findAndScrollToSetting) || function(){};
        setTimeout(function() { fn(tabId.slice(7)); }, 200);
      } else {
        try { history.replaceState(null, '', '#' + slug + focusSuffix); } catch (_) {}
      }

      var label = $item.find('.bbcs-snav-label').text();
      var icon = $item.data('snav-icon');
      if (label && $toggle.length) {
        $toggle.find('.bbcs-snav-toggle-label').text(label);
      }
      if (icon && $toggle.length) {
        var $toggleIcon = $toggle.find('.bbcs-snav-toggle-icon');
        if ($toggleIcon.length) {
          if (icon.indexOf('/') !== -1 || icon.indexOf('.svg') !== -1) {
            $toggleIcon.replaceWith('<img src="' + icon + '" alt="" class="bbcs-ico bbcs-ico--sm bbcs-snav-toggle-icon bbcs-snav-img">');
          } else {
            $toggleIcon.replaceWith('<svg class="bbcs-ico bbcs-ico--sm bbcs-snav-toggle-icon" aria-hidden="true"><use href="#bbcs-i-' + icon + '"></use></svg>');
          }
        }
      }

      if (window.innerWidth <= 1024) {
        $nav.removeClass('is-open');
        $toggle.removeClass('is-open').attr('aria-expanded', 'false');
        var $content = $page.find('.bbcs-settings-content');
        if ($content.length) {
          $('html, body').animate({ scrollTop: $content.offset().top - 20 }, 200);
        }
      }

      scrollActiveIntoView();
    });

    /* ══════════════════════════════════════════
       Click a setting → activate parent tab, scroll, highlight
       ══════════════════════════════════════════ */

    $nav.on('click', '.bbcs-snav-setting', function (e) {
      e.stopPropagation();
      var targetKey = $(this).data('key');
      var $parentItem = $(this).closest('.bbcs-snav-item');
      if ($parentItem.length && !$parentItem.hasClass('is-active')) {
        $parentItem.trigger('click');
      }
      if (targetKey) {
        var tabId = $parentItem.data('snav-tab') || '';
        var slug = tabId.replace(/\s+/g, '-').replace(/_/g, '-').toLowerCase();
        try { history.replaceState(null, '', '#' + slug + '&focus=' + encodeURIComponent(targetKey)); } catch (_) {}
        setTimeout(function () {
          findAndScrollToSetting(targetKey);
        }, 150);
      }
    });

    /* ══════════════════════════════════════════
       Search filter
       ══════════════════════════════════════════ */

    function scrollActiveIntoView() {
      var $active = $items.filter('.is-active');
      if ($active.length) {
        $active[0].scrollIntoView({ block: 'start', behavior: 'smooth' });
      }
    }

    function highlightText($el, q) {
      var orig = $el.data('orig-text');
      if (!orig) {
        orig = $el.text();
        $el.data('orig-text', orig);
      }
      if (!q) {
        $el.html($('<span>').text(orig).html());
        return;
      }
      var idx = orig.toLowerCase().indexOf(q);
      if (idx === -1) {
        $el.html($('<span>').text(orig).html());
        return;
      }
      var before = orig.slice(0, idx);
      var match  = orig.slice(idx, idx + q.length);
      var after  = orig.slice(idx + q.length);
      $el.html(
        $('<span>').text(before).html() +
        '<mark class="bbcs-snav-hl">' + $('<span>').text(match).html() + '</mark>' +
        $('<span>').text(after).html()
      );
    }

    function restoreText($el) {
      var orig = $el.data('orig-text');
      if (orig) {
        $el.html($('<span>').text(orig).html());
      }
    }

    $search.on('input', function () {
      var q = $.trim($(this).val()).toLowerCase();

      if (!q) {
        /* ── Empty search: restore everything ── */
        $nav.removeClass('has-search');
        $items.removeClass('is-hidden has-submatch');
        $settings.removeClass('is-hidden');
        $nav.find('.bbcs-snav-label').each(function () { restoreText($(this)); });
        $nav.find('.bbcs-snav-setting').each(function () { restoreText($(this)); });
        $groups.each(function () { $(this).show(); });
        // Restore per-item expansion from stored state
        applySimpleMode();
        return;
      }

      $nav.addClass('has-search');

      $items.each(function () {
        var $item = $(this);
        var $label = $item.find('.bbcs-snav-label');
        var labelText = ($item.data('snav-label') || $label.text() || '').toLowerCase();
        var $subSettings = $item.find('.bbcs-snav-setting');
        var labelMatch = labelText.indexOf(q) !== -1;
        var subMatch = false;

        if (labelMatch) {
          highlightText($label, q);
        } else {
          restoreText($label);
        }

        $subSettings.each(function () {
          var $s = $(this);
          var sLabel = ($s.data('setting') || '').toLowerCase();
          if (sLabel.indexOf(q) !== -1) {
            $s.removeClass('is-hidden');
            highlightText($s, q);
            subMatch = true;
          } else {
            $s.addClass('is-hidden');
            restoreText($s);
          }
        });

        if (labelMatch || subMatch) {
          $item.removeClass('is-hidden');
          // If sub-match found in simple mode, mark for forced expansion
          if (subMatch) {
            $item.addClass('has-submatch');
          } else {
            $item.removeClass('has-submatch');
          }
          if (labelMatch && !subMatch) {
            $subSettings.removeClass('is-hidden');
            $subSettings.each(function () {
              restoreText($(this));
            });
          }
        } else {
          $item.addClass('is-hidden');
          $item.removeClass('has-submatch');
        }
      });

      $groups.each(function () {
        var $group = $(this);
        var $visibleItems = $group.find('.bbcs-snav-item').not('.is-hidden');
        // In simple mode, also exclude advanced items (hidden by .is-advanced CSS)
        if (isSimpleMode()) {
          $visibleItems = $visibleItems.not('.is-advanced');
        }
        $group.toggle($visibleItems.length > 0);
      });
    });

    /* ── Clear search button (X) ── */
    $clearBtn.on('click', function () {
      $search.val('').trigger('input').focus();
    });

    /* ══════════════════════════════════════════
       On load: activate initial tab from hash or active class
       ══════════════════════════════════════════ */

    function findFirstVisibleSimpleItem() {
      return $items.not('.is-advanced').first();
    }

    var hash = (window.location.hash || '').replace(/^#/, '').toLowerCase();
    // Deferred: wait for DOM to settle before activating initial tab
    function activateInitialTab() {
      var $target;
      if (hash) {
        var tabHash = hash.split('&')[0];
        $target = $items.filter(function () {
          var id = ($(this).data('snav-tab') || '').replace(/_/g, '-').toLowerCase();
          return id === tabHash;
        });
        // If hash targets an advanced item while in simple mode, disable simple mode
        if ($target.hasClass('is-advanced') && isSimpleMode()) {
          setSimpleMode(false);
        }
      }
      if (!$target || !$target.length) {
        $target = $items.filter('.is-active');
        // If active is advanced in simple mode, disable simple mode
        if ($target.hasClass('is-advanced') && isSimpleMode()) {
          setSimpleMode(false);
        }
      }
      if ($target && $target.length) {
        $target.trigger('click');
      }
    }
    setTimeout(activateInitialTab, 10);

    /* Check for &focus=key on page load */
    var focusMatch = (window.location.hash || '').match(/[?&]focus=([^&]+)/);
    if (focusMatch) {
      var focusKey = decodeURIComponent(focusMatch[1]);
      setTimeout(function () {
        findAndScrollToSetting(focusKey);
      }, 300);
    }

    /* ── Hash change: activate the tab matching the new hash ── */
    $(window).on('hashchange', function () {
      var hash = (window.location.hash || '').replace(/^#/, '').toLowerCase();
      if (!hash) return;
      var tabHash = hash.split('&')[0];
      var $target = $items.filter(function () {
        var id = ($(this).data('snav-tab') || '').replace(/_/g, '-').toLowerCase();
        return id === tabHash;
      });
      // If hash targets an advanced item while in simple mode, disable simple mode
      if ($target.hasClass('is-advanced') && isSimpleMode()) {
        setSimpleMode(false);
      }
      if ($target.length) {
        $target.trigger('click');
      }
    });
  });
})(jQuery);
