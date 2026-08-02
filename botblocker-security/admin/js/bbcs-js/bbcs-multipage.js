/* ============================================================
   bbcs-multipage.js - multipage version.
   Navigation between sections: clean <a href> in HTML, JS not needed.
   JS handles only: tabs within pages, toggles,
   custom select and command palette (⌘K).
   ============================================================ */
jQuery(function ($) {
  'use strict';

  var $doc = $(document);

  /* ── Tab loading registry - each table JS file registers a function(tabName) → bool ── */
  window.BBCS_TabLoadingRegistry = window.BBCS_TabLoadingRegistry || {};

  /* ── Shared helpers via BBCS_Helpers (bbcs-shared-helpers.js) ── */
  var findAndScrollToSetting = (window.BBCS_Helpers && window.BBCS_Helpers.findAndScrollToSetting) || function () {};
  var triggerBlinkHighlight = (window.BBCS_Helpers && window.BBCS_Helpers.triggerBlinkHighlight) || function () {};
  var checkUrlFocusAndJump  = (window.BBCS_Helpers && window.BBCS_Helpers.checkUrlFocusAndJump)  || function () {};

  $doc.on('ready bbcs:tab-changed', function () {
    checkUrlFocusAndJump();
  });
  $(window).on('hashchange', function () {
    checkUrlFocusAndJump();
  });
  /* Deferred initial check */
  setTimeout(function () {
    checkUrlFocusAndJump();
  }, 100);

  /* ── 1. Navigation - clean <a href>, JS not needed ── */
  /* URL map is only used by command palette (section 7) */
  var ADMIN_URL = (typeof botblockerData !== 'undefined' && botblockerData.adminUrl) || '';
  var PAGE_URLS = {
    home:         ADMIN_URL + 'admin.php?page=bbcs_dashboard',
    status:       ADMIN_URL + 'admin.php?page=bbcs_setup_guide',
    settings:     ADMIN_URL + 'admin.php?page=bbcs_settings',
    advanced:     ADMIN_URL + 'admin.php?page=bbcs_settings',
    cron:         ADMIN_URL + 'admin.php?page=bbcs_settings',
    rules:        ADMIN_URL + 'admin.php?page=bbcs_rules',
    integrations: ADMIN_URL + 'admin.php?page=bbcs_integrations',
    tools:        ADMIN_URL + 'admin.php?page=bbcs_tools',
    log:          ADMIN_URL + 'admin.php?page=bbcs_reports',
    addons:       ADMIN_URL + 'admin.php?page=bbcs_addons',
    pro:          ADMIN_URL + 'admin.php?page=bbcs_cloud_api',
    support:      ADMIN_URL + 'admin.php?page=bbcs_about',
    '2fa':        ADMIN_URL + 'admin.php?page=bbcs_integrations',
    setup_wizard: ADMIN_URL + 'admin.php?page=bbcs_setup_wizard'
  };

  /* ── 2. Tabs within page ── */
  /* Slugify tab name for clean URL hashes (spaces → hyphens). */
  function tabSlug(tab) { return tab.replace(/\s+/g, '-').toLowerCase(); }

  function activateTab($scope, tab) {
    var $tabs  = $scope.find('.bbcs-tab');
    var $match = $tabs.filter('[data-tab="' + tab + '"]');
    if (!$match.length) return;
    $tabs.removeClass('is-active').attr('aria-selected', 'false');
    $match.addClass('is-active').attr('aria-selected', 'true');
    $scope.find('.bbcs-tabpanel').attr('hidden', true)
      .attr('aria-hidden', 'true')
      .filter('[data-tabpanel="' + tab + '"]').removeAttr('hidden')
      .removeAttr('aria-hidden');
    $doc.trigger('bbcs:tab-changed', { tab: tab, $scope: $scope });
  }

  $doc.on('click', '.bbcs-tab', function (e) {
    e.stopPropagation();
    var $clickedTab = $(this);
    var tab = $clickedTab.data('tab');
    var $page = $clickedTab.closest('.bbcs-page');

    if ($clickedTab.hasClass('is-active')) return;

    var $activeTab = $page.find('.bbcs-tab.is-active');
    var activeTabName = $activeTab.data('tab');
    if (activeTabName && typeof window.BBCS_TabLoadingRegistry[activeTabName] === 'function' && window.BBCS_TabLoadingRegistry[activeTabName]()) {
      $activeTab.addClass('bbcs-tab-wait');
      setTimeout(function(){ $activeTab.removeClass('bbcs-tab-wait'); }, 400);
      return;
    }

    activateTab($page, tab);
    if (tab) history.replaceState(null, '', '#' + tabSlug(tab));
    $clickedTab.closest('.bbcs-scroll').scrollTop(0);
  });

  $doc.on('click', '[data-tab-link]', function (e) {
    e.stopPropagation();
    var tab = $(this).data('tab-link');
    var $page = $(this).closest('.bbcs-page');

    var $activeTab = $page.find('.bbcs-tab.is-active');
    if ($activeTab.length && $activeTab.data('tab') === tab) return;

    var activeTabName = $activeTab.data('tab');
    if (activeTabName && typeof window.BBCS_TabLoadingRegistry[activeTabName] === 'function' && window.BBCS_TabLoadingRegistry[activeTabName]()) {
      $activeTab.addClass('bbcs-tab-wait');
      setTimeout(function(){ $activeTab.removeClass('bbcs-tab-wait'); }, 400);
      return;
    }

    activateTab($page, tab);
    if (tab) history.replaceState(null, '', '#' + tabSlug(tab));
  });

  /* ── 3. Toggles ── */
  /* Simple toggles (no data-bbcs-toggle) - visual only. */
  /* Toggles with data-bbcs-toggle="1" - send AJAX. */
  function setToggleState($t, on) {
    $t.toggleClass('is-on', on).attr('aria-checked', on ? 'true' : 'false');
  }

  function getRailToggleValue($t) {
    return $t.hasClass('is-on') ? 1 : 0;
  }

  $doc.on('click', '.bbcs-toggle', function (e) {
    e.stopPropagation();
    var $t = $(this);

    // Visual-only toggle (no data-bbcs-toggle attribute) - flip appearance state.
    //
    // When a data-field attribute is present the matching hidden input (by name)
    // is also updated so the parent form serialises the correct value on submit.
    // Toggles without data-field (e.g. addon activate / deactivate submit buttons)
    // are left alone - their native form behaviour must not be altered.
    if (!$t.is('[data-bbcs-toggle]')) {
      $t.toggleClass('is-on');
      $t.attr('aria-checked', $t.hasClass('is-on') ? 'true' : 'false');
      var isOn = $t.hasClass('is-on');
      var field = $t.attr('data-field');
      if (field) {
        // Prefer exact name match (covers memcached_enable, redis_enable, etc.)
        var $backing = $t.siblings('input[type="hidden"]').filter(function () {
          return $(this).attr('name') === field;
        }).first();
        // Fallback: first hidden input (covers legacy cases where data-field
        // uses underscore notation but the input name uses bracket notation).
        if (!$backing.length) {
          $backing = $t.siblings('input[type="hidden"]').first();
        }
        if ($backing.length) {
          $backing.val(isOn ? '1' : '0').trigger('change');
        }
      }
      // Also sync checkbox siblings (e.g. x_robots_directives[])
      var $cb = $t.siblings('input[type="checkbox"]').first();
      if ($cb.length) {
        $cb.prop('checked', isOn).trigger('change');
      }
      return;
    }

    // AJAX toggle - gather params.
    var action  = $t.data('action');
    var setting = $t.data('setting');
    var newVal  = getRailToggleValue($t) ? 0 : 1;  // flip

    // ── Early Init disabled guard ──
    if (action === 'bbcs_toggle_early_phase_in_db' && setting === 'early_init_enable' && newVal === 1 && $t.is(':disabled')) {
      // Redirect to addons page if user confirms.
      if (confirm(window.bbcsEarlyInitConfirm || 'Early Init requires active BotBlocker PRO and the Early Init add-on. Open Add-ons page?')) {
        var addonsUrl = $('#bbcs-main').data('addons-url') || '';
        if (addonsUrl) window.location.href = addonsUrl;
      }
      return;
    }

    var data = {
      action: action,
      nonce:  (typeof botblockerData !== 'undefined') ? botblockerData.nonce : '',
    };

    // ── Action-specific params ──
    if (action === 'bbcs_toggle_early_phase_in_db') {
      data.setting = setting;

      if (setting === 'disable') {
        // disable is inverted: toggle ON = disable=0, toggle OFF = disable=1
        data[setting] = newVal === 1 ? 0 : 1;
      } else {
        data[setting] = newVal;
      }

      // Optimistically toggle now; the server will handle mutual exclusion.
      setToggleState($t, newVal === 1);

      if (setting === 'disable') {
        var $hero = $t.closest('.bbcs-hero');
        if ($hero.length) {
          var $tile = $hero.find('.bbcs-tile');
          var $title = $hero.find('.bbcs-hero-title');
          var $label = $hero.find('.bbcs-fw-bold').first();
          if (newVal === 0) {
            $tile.removeClass('bbcs-acc-green').addClass('bbcs-acc-amber');
            $tile.find('use').attr('href', '#bbcs-i-shield');
            $title.text(bbcsMultipageL10n.protection_paused);
            $label.text(bbcsMultipageL10n.disabled);
          } else {
            $tile.removeClass('bbcs-acc-amber').addClass('bbcs-acc-green');
            $tile.find('use').attr('href', '#bbcs-i-shieldCheck');
            $title.text(bbcsMultipageL10n.site_is_protected);
            $label.text(bbcsMultipageL10n.enabled);
          }
        }
        var $railLabel = $('.bbcs-rail .bbcs-fw-bold.bbcs-fs-md').first();
        if ($railLabel.length) {
          $railLabel.text(newVal === 0 ? bbcsMultipageL10n.protection_paused : bbcsMultipageL10n.protection_active);
          var $railRow = $railLabel.closest('.bbcs-row');
          if ($railRow.length) {
            var $railIconSpan = $railRow.find('span').first();
            var $railIconUse = $railRow.find('use');
            if (newVal === 0) {
              $railIconSpan.removeClass('bbcs-tx-green').addClass('bbcs-tx-amber');
              $railIconUse.attr('href', '#bbcs-i-shield');
            } else {
              $railIconSpan.removeClass('bbcs-tx-amber').addClass('bbcs-tx-green');
              $railIconUse.attr('href', '#bbcs-i-shieldCheck');
            }
          }
        }
      }

      // If enabling Early Init, visually uncheck MU-plugin (server does the same).
      if (setting === 'early_init_enable' && newVal === 1) {
        $('.bbcs-toggle[data-action="bbcs_toggle_early_phase_in_db"][data-setting="mu_enable"]').each(function () {
          setToggleState($(this), false);
        });
      }
      // If enabling MU-plugin, visually uncheck Early Init.
      if (setting === 'mu_enable' && newVal === 1) {
        $('.bbcs-toggle[data-action="bbcs_toggle_early_phase_in_db"][data-setting="early_init_enable"]').each(function () {
          setToggleState($(this), false);
        });
      }
    } else if (action === 'bbcs_toggle_redis_and_memcached') {
      // Redis and Memcached are mutually exclusive.
      // Read the OTHER toggle's current state from the DOM.
      var otherSetting = setting === 'redis_enable' ? 'memcached_enable' : 'redis_enable';
      var $other = $('.bbcs-toggle[data-action="bbcs_toggle_redis_and_memcached"][data-setting="' + otherSetting + '"]');

      data.redis_enable    = setting === 'redis_enable' ? newVal : 0;
      data.memcached_enable = setting === 'memcached_enable' ? newVal : 0;

      // Optimistically toggle both.
      setToggleState($t, newVal === 1);
      setToggleState($other, false);

      // Also update data-value attributes.
      $t.attr('data-value', newVal);
      $other.attr('data-value', 0);
    } else if (action === 'bbcs_switch_ptr_cache_in_db') {
      data.ptr_cache_in_db = newVal;
      setToggleState($t, newVal === 1);
    } else if (action === 'bbcs_switch_ui_cache_in_db') {
      data.cache_ui_data = newVal;
      setToggleState($t, newVal === 1);
    } else {
      // Fallback for unknown actions - just visual toggle.
      setToggleState($t, newVal === 1);
      return;
    }

    // ── Lock paired toggles during AJAX ──
    var $pair = $();
    var pairWasDisabled = false;
    if (action === 'bbcs_toggle_early_phase_in_db' && setting !== 'disable') {
      var pairSetting = setting === 'mu_enable' ? 'early_init_enable' : 'mu_enable';
      $pair = $('.bbcs-toggle[data-action="bbcs_toggle_early_phase_in_db"][data-setting="' + pairSetting + '"]');
      pairWasDisabled = $pair.prop('disabled');
    }
    $t.prop('disabled', true);
    $pair.prop('disabled', true);
    $.ajax({
      url:  (typeof botblockerData !== 'undefined') ? botblockerData.ajaxurl : ajaxurl,
      type: 'POST',
      data: data,
      success: function (resp) {
        $t.prop('disabled', false);
        $pair.prop('disabled', pairWasDisabled);
        if (!resp.success) {
          // Revert on failure.
          setToggleState($t, newVal === 0);
          if (action === 'bbcs_toggle_redis_and_memcached') {
            var $other2 = $('.bbcs-toggle[data-action="bbcs_toggle_redis_and_memcached"][data-setting="' + otherSetting + '"]');
            setToggleState($other2, $other2.attr('data-value') === '1');
          }
          if (resp.data && resp.data.message) alert(resp.data.message);
        } else if (action === 'bbcs_toggle_early_phase_in_db' && setting !== 'disable' && resp.data && resp.data.final_state) {
          // Sync both toggles from the server's authoritative dedup'd state.
          var fs = resp.data.final_state;
          var $early = $('.bbcs-toggle[data-action="bbcs_toggle_early_phase_in_db"][data-setting="early_init_enable"]');
          var $mu    = $('.bbcs-toggle[data-action="bbcs_toggle_early_phase_in_db"][data-setting="mu_enable"]');
          if ($early.length) {
            setToggleState($early, fs.early_init_enable === 1 || fs.early_init_enable === '1');
            $early.attr('data-value', fs.early_init_enable ? 1 : 0);
          }
          if ($mu.length) {
            setToggleState($mu, fs.mu_enable === 1 || fs.mu_enable === '1');
            $mu.attr('data-value', fs.mu_enable ? 1 : 0);
          }
        }
      },
      error: function () {
        $t.prop('disabled', false);
        $pair.prop('disabled', pairWasDisabled);
        // Revert on network error.
        setToggleState($t, newVal === 0);
        if (action === 'bbcs_toggle_redis_and_memcached') {
          var $other3 = $('.bbcs-toggle[data-action="bbcs_toggle_redis_and_memcached"][data-setting="' + otherSetting + '"]');
          setToggleState($other3, $other3.attr('data-value') === '1');
        }
      }
    });
  });

  /* ── 4. Segmented ── */
  $doc.on('click', '.bbcs-seg-opt', function (e) {
    e.stopPropagation();
    var $opt = $(this),
        val  = $opt.data('value');
    $opt.addClass('is-active').attr('aria-pressed', 'true')
        .siblings('.bbcs-seg-opt').removeClass('is-active').attr('aria-pressed', 'false');
    $opt.closest('.bbcs-seg').find('input[type="hidden"]').val(val).trigger('change');
  });

  /* ── 5. Custom select ── */
  $doc.on('click', '.bbcs-select-trigger', function (e) {
    e.stopPropagation();
    if ($(this).closest('.bbcs-select').hasClass('is-disabled')) return;
    var $menu = $(this).siblings('.bbcs-select-menu');
    $('.bbcs-select-menu').not($menu).hide();
    $menu.toggle();
  });
  $doc.on('click', '.bbcs-select-opt', function (e) {
    e.stopPropagation();
    var $wrap = $(this).closest('.bbcs-select');
    var val = $(this).attr('data-value') || $(this).text();
    // Display the option's label, not the data-value.
    $wrap.find('.bbcs-select-value').text($(this).text());
    $wrap.find('.bbcs-select-opt').removeClass('is-sel');
    $(this).addClass('is-sel');
    $wrap.find('.bbcs-select-menu').hide();
    // Update hidden input and fire change for dirty tracking.
    var $hidden = $wrap.closest('.bbcs-field').find('> input[type="hidden"]');
    if ($hidden.length) {
      $hidden.val(val).trigger('change');
    }
  });
  $doc.on('click', function () { $('.bbcs-select-menu').hide(); });

  /* ── 6. Tab activation from hash # ── */
  /* Deferred with setTimeout so all bbcs:tab-changed handlers are registered first. */
  setTimeout(function () {
    var $page = $('.bbcs-page');
    if (!$page.length) return;
    var hash = window.location.hash.replace(/^#/, '');
    if (!hash) return;
    var $tabs = $page.find('.bbcs-tab');
    var $match = $tabs.filter(function () {
      return tabSlug($(this).data('tab')) === hash;
    });
    if ($match.length) {
      activateTab($page, $match.data('tab'));
    }
  }, 0);

  /* ── 6b. Dynamic label update for "Add …" button in Rules pagehead ── */
  var L = window.bbcsMultipageL10n || {};
  var ADD_LABELS = (typeof BOTBLOCKER_ADD_LABELS !== 'undefined') ? BOTBLOCKER_ADD_LABELS : {
    'Rules':        L.add_rule || 'Add Rule',
    'Paths':        L.add_path || 'Add Path',
    'Trusted Bots': L.add_bot || 'Add Bot',
    'IPv4 List':    L.add_ipv4 || 'Add IPv4',
    'IPv6 List':    L.add_ipv6 || 'Add IPv6',
    'Proxy':        L.add_proxy || 'Add Proxy',
    'ASN':          L.add_asn || 'Add ASN'
  };
  var TAB_HIDE_IMPORT_EXPORT = { 'GEO': true, 'LLM': true };
  var TAB_SHOW_LLM           = { 'LLM': true };
  var $addBtn     = $('#bbcs_pagehead_add');
  var $importBtn  = $('#bbcs_pagehead_import');
  var $exportBtn  = $('#bbcs_pagehead_export');
  var $llmSync    = $('#bbcs_pagehead_llm_sync');
  var $llmDownload = $('#bbcs_pagehead_llm_download');
  function setAddLabel(label) {
    /* Remove existing text nodes, keep only the SVG child */
    $addBtn.contents().filter(function () { return this.nodeType === 3; }).remove();
    $addBtn.append(document.createTextNode(' ' + label));
  }
  if ($addBtn.length) {
    $doc.on('bbcs:tab-changed', function (e, data) {
      var label = ADD_LABELS[data.tab];
      if (label) {
        $addBtn.show();
        setAddLabel(label);
      } else {
        $addBtn.hide();
      }
      if (TAB_HIDE_IMPORT_EXPORT[data.tab]) {
        $importBtn.hide();
        $exportBtn.hide();
      } else {
        $importBtn.show();
        $exportBtn.show();
      }
      if (TAB_SHOW_LLM[data.tab]) {
        $llmSync.show();
        $llmDownload.show();
      } else {
        $llmSync.hide();
        $llmDownload.hide();
      }
    });
    /* Set initial label from default active tab */
    var $initPage = $('.bbcs-page');
    var initTab = $initPage.length ? $initPage.find('.bbcs-tab.is-active').data('tab') : '';
    setAddLabel(ADD_LABELS[initTab] || L.add_rule || 'Add Rule');
  }

  /* ── 7. Command palette ── */
  var ACTIONS = (typeof BOTBLOCKER_PALETTE_ACTIONS !== 'undefined') ? BOTBLOCKER_PALETTE_ACTIONS : [];
  var GROUPS   = (typeof BOTBLOCKER_PALETTE_GROUPS !== 'undefined')   ? BOTBLOCKER_PALETTE_GROUPS   : [];
  var SECTIONS = (typeof BOTBLOCKER_PALETTE_SECTIONS !== 'undefined') ? BOTBLOCKER_PALETTE_SECTIONS : [];

    function svgUse(name) {
    return '<svg class="bbcs-ico"><use href="#bbcs-i-' + name + '"></use></svg>';
  }

  /* Highlight the portion of label that matches query (case-insensitive). */
  function highlightLabel(label, q) {
    if (!q) return label;
    var lower = label.toLowerCase();
    var idx = lower.indexOf(q);
    if (idx === -1) return label;
    return label.substring(0, idx) +
      '<mark class="bbcs-pal-match">' + label.substring(idx, idx + q.length) + '</mark>' +
      label.substring(idx + q.length);
  }

  function rowExtra(e) {
    var cls = '';
    if (e.pro) cls += ' bbcs-pal-row--pro';
    if (e.addon) cls += ' bbcs-pal-row--addon';
    return cls;
  }

  function rowBadge(e) {
    if (e.pro) return '<span class="bbcs-chip bbcs-chip--pro bbcs-pal-badge">' + (L.pro_badge || 'PRO') + '</span>';
    if (e.addon) return '<span class="bbcs-chip bbcs-chip--addon bbcs-pal-badge">' + (L.addon_badge || 'addon') + '</span>';
    return '';
  }

  var pal = { sel: 0, flat: [] };

  function buildPalette(rawQ) {
    var q = (rawQ || '').toLowerCase().trim();
    var flat = [], html = '';
    function hit(s) { return !q || s.toLowerCase().indexOf(q) >= 0; }

    var acts = ACTIONS.filter(function (a) { return hit(a.t); });
    if (acts.length) {
      html += '<div class="bbcs-pal-head">' + (L.actions || 'Actions') + '</div>';
      acts.forEach(function (a) {
        var i = flat.length; flat.push(a);
        html += palRow(i, svgUse(a.ic || 'bolt'), a.t, false, a, rawQ);
      });
    }

    var grpOut = [];
    GROUPS.forEach(function (g) {
      var titleHit = q && g.t.toLowerCase().indexOf(q) >= 0;
      var ch = q ? g.ch.filter(function (c) {
        var labelStr = Array.isArray(c) ? c[0] : c;
        return labelStr.toLowerCase().indexOf(q) >= 0;
      }) : [];
      if (!q) { grpOut.push({ g: g, ch: [] }); return; }
      if (titleHit) grpOut.push({ g: g, ch: g.ch });
      else if (ch.length) grpOut.push({ g: g, ch: ch });
    });
    if (grpOut.length) {
      html += '<div class="bbcs-pal-head">' + (L.settings || 'Settings') + '</div>';
      grpOut.forEach(function (o) {
        var g = o.g;
        var i = flat.length; flat.push(g);
        html += palRow(i, svgUse(g.ic || 'gear'), g.t, true, g, rawQ);
        o.ch.forEach(function (c) {
          var labelStr = Array.isArray(c) ? c[0] : c;
          var targetKey = Array.isArray(c) && c[1] ? c[1] : '';
          var ci = flat.length; flat.push({ t: labelStr, focusKey: targetKey, go: g.go, tab: g.tab, pro: g.pro, addon: g.addon });
          html += palChild(ci, labelStr, g, rawQ);
        });
      });
    }

    var secs = SECTIONS.filter(function (s) { return hit(s.t); });
    if (secs.length) {
      html += '<div class="bbcs-pal-head">' + (L.sections || 'Sections') + '</div>';
      secs.forEach(function (s) {
        var i = flat.length; flat.push(s);
        html += palRow(i, svgUse(s.ic), s.t, false, s, rawQ);
      });
    }

    if (!flat.length) html = '<div class="bbcs-pal-empty">' + (L.nothing_found || 'Nothing found for') + ' «' + $('<span>').text(q).html() + '»</div>';
    pal.flat = flat;
    if (pal.sel > flat.length - 1) pal.sel = Math.max(0, flat.length - 1);
    $('#bbcs-pal-list').html(html);
    paintSel();
  }

  function palRow(i, ic, label, group, e, rawQ) {
    return '<div class="bbcs-pal-row' + rowExtra(e || {}) + '" data-idx="' + i + '">' +
      '<span class="bbcs-pal-ic">' + ic + '</span>' +
      '<span class="bbcs-pal-label' + (group ? ' bbcs-pal-label--group' : '') + '">' + highlightLabel(label, rawQ) + '</span>' +
      '<span class="bbcs-pal-right">' +
      rowBadge(e || {}) +
      '<span class="bbcs-pal-enter bbcs-chip bbcs-chip--kbd">↵</span>' +
      '</span></div>';
  }
  function palChild(i, label, g, rawQ) {
    return '<div class="bbcs-pal-row bbcs-pal-row--child' + rowExtra(g || {}) + '" data-idx="' + i + '">' +
      '<span class="bbcs-pal-label bbcs-muted">' + highlightLabel(label, rawQ) + '</span>' +
      '<span class="bbcs-pal-right">' +
      '<span class="bbcs-pal-enter bbcs-chip bbcs-chip--kbd">↵</span>' +
      '</span></div>';
  }
  function paintSel() {
    $('.bbcs-pal-row').removeClass('is-active');
    var $r = $('.bbcs-pal-row[data-idx="' + pal.sel + '"]');
    $r.addClass('is-active');
  }
  function runPalette(i) {
    var e = pal.flat[i]; if (!e) return;
    closePalette();
    var proActive = (typeof botblockerData !== 'undefined' && botblockerData.proActive);
    // Inactive-addon / marketplace items → addons page with focus on card
    if (e.addon) {
      window.location.href = (PAGE_URLS.addons || ADMIN_URL + 'admin.php?page=bbcs_addons') + '&focus=' + encodeURIComponent(e.addon);
      return;
    }
    // PRO-badged items for non-PRO users → redirect to upgrade page
    if (e.pro && !proActive) {
      window.location.href = PAGE_URLS.pro || ADMIN_URL + 'admin.php?page=bbcs_cloud_api';
      return;
    }
    var url = PAGE_URLS[e.go]; if (!url) return;
    if (e.tab) {
      url += '#' + tabSlug(e.tab);
      if (e.focusKey) {
        url += '&focus=' + encodeURIComponent(e.focusKey);
      }
    }
    window.location.href = url;
  }
  function openPalette() {
    pal.sel = 0;
    $('#bbcs-palette').removeAttr('hidden');
    $('#bbcs-pal-input').val('');
    buildPalette('');
    $('#bbcs-pal-input').trigger('focus');
  }
  function closePalette() { $('#bbcs-palette').attr('hidden', true); }
  function palOpen() { return !$('#bbcs-palette').attr('hidden'); }

  $doc.on('click', '#bbcs-search',     openPalette);
  $doc.on('click', '#bbcs-search-mob', openPalette);
  $doc.on('input', '#bbcs-pal-input',  function () { pal.sel = 0; buildPalette($(this).val()); });
  $doc.on('mouseenter', '.bbcs-pal-row', function () { pal.sel = parseInt($(this).data('idx'), 10); paintSel(); });
  $doc.on('click', '.bbcs-pal-row',    function () { runPalette(parseInt($(this).data('idx'), 10)); });
  $doc.on('click', '#bbcs-palette',    function (e) { if (e.target.id === 'bbcs-palette') closePalette(); });
  $doc.on('click', '#bbcs-pal-close',  closePalette);
  $doc.on('keydown', '#bbcs-pal-input', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); pal.sel = Math.min(pal.sel + 1, pal.flat.length - 1); paintSel(); }
    else if (e.key === 'ArrowUp')  { e.preventDefault(); pal.sel = Math.max(pal.sel - 1, 0); paintSel(); }
    else if (e.key === 'Enter')    { e.preventDefault(); runPalette(pal.sel); }
    else if (e.key === 'Escape')   { closePalette(); }
  });
  $doc.on('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      if (palOpen()) closePalette(); else openPalette();
      return;
    }
    tryOpenPaletteByTyping(e);
  });

  function tryOpenPaletteByTyping(e) {
    if (palOpen()) return;
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key.length !== 1) return;
    var $t = $(e.target);
    if ($t.is('input, textarea, select, [contenteditable]')) return;
    if ($t.closest('[contenteditable]').length) return;
    e.preventDefault();
    openPalette();
    $('#bbcs-pal-input').val(e.key);
    pal.sel = 0;
    buildPalette(e.key);
  }

  /* ── 8. Copy button (data-copy-sibling) ── */
  $doc.on('click', '[data-copy-sibling]', function (e) {
    e.stopPropagation();
    var sel = $(this).data('copy-sibling');
    var text = $(this).closest('.bbcs-field-box').find(sel).text();
    if (text && navigator.clipboard) navigator.clipboard.writeText(text);
  });

  /* ── 9. Dropdown menus (language, bell, etc.) ── */
  $doc.on('click', '.bbcs-drop-trigger', function (e) {
    e.stopPropagation();
    var $trigger = $(this);
    var $menu = $trigger.siblings('.bbcs-drop-menu');
    if (!$menu.length) $menu = $trigger.closest('.bbcs-drop').find('.bbcs-drop-menu').first();
    if (!$menu.length) return;
    var isHidden = $menu.attr('hidden') !== undefined;
    $('.bbcs-drop-menu').not($menu).attr('hidden', true);
    $('.bbcs-drop-trigger').not($trigger).attr('aria-expanded', 'false');
    if (isHidden) {
      $menu.removeAttr('hidden');
      $trigger.attr('aria-expanded', 'true');
    } else {
      $menu.attr('hidden', true);
      $trigger.attr('aria-expanded', 'false');
    }
  });
  $doc.on('click', function () {
    $('.bbcs-drop-menu').attr('hidden', true);
  });
  $doc.on('click', '.bbcs-drop-item', function () {
    var $item = $(this);
    $item.closest('.bbcs-drop-menu').attr('hidden', true);

    /* Language switcher: if the item has a data-lang attribute, set cookie and reload. */
    var lang = $item.data('lang');
    if (lang) {
      $item.closest('.bbcs-drop-menu').find('.bbcs-drop-item').removeClass('active');
      $item.addClass('active');
      document.cookie = 'bbcs_preferred_language=' + lang + '; path=/';
      location.reload();
    }
  });
  $doc.on('keydown', function (e) {
    if (e.key === 'Escape') $('.bbcs-drop-menu').attr('hidden', true);
  });

  /* ── 10. Cron task buttons ── */

  function flashCronBtn($btn) {
    var $use = $btn.find('use');
    var origHref = $use.attr('href') || '';
    $btn.prop('disabled', true);
    $use.attr('href', '#bbcs-i-check');
    setTimeout(function () {
      $btn.prop('disabled', false);
      $use.attr('href', origHref);
    }, 2000);
  }

  function resetCronProgressRow($row) {
    var $prog  = $row.find('.bbcs-cron-progress');
    var $bar   = $row.find('.bbcs-cron-progress-bar');
    var $sec   = $row.find('.bbcs-cron-progress-s');
    var $tag   = $row.find('.bbcs-cron-status');
    var interval = parseInt($prog.attr('data-interval'), 10) || 0;
    if ($bar.length) $bar.css('width', '0%');
    if ($sec.length && interval > 0) {
      $sec.attr('data-seconds', interval);
      if (typeof window.bbcsCronFormatTime === 'function') {
        $sec.text(window.bbcsCronFormatTime(interval));
      } else {
        $sec.text(interval + 's');
      }
    }
    if ($tag.length) {
      $tag.removeClass('bbcs-tag--red').addClass('bbcs-tag--green');
      var activeLabel = $tag.data('active-label') || 'Active';
      $tag.text(activeLabel);
    }
  }

  $doc.on('click', '[data-bbcs-cron-action="run-now"]', function () {
    var $btn = $(this);
    var hook = $btn.data('bbcs-cron-hook');
    if (!hook) return;

    flashCronBtn($btn);
    $.ajax({
      url: (typeof botblockerData !== 'undefined') ? botblockerData.ajaxurl : ajaxurl,
      type: 'POST',
      data: {
        action: 'bbcs_run_cron_task',
        hook: hook,
        nonce: (typeof botblockerData !== 'undefined') ? botblockerData.nonce : ''
      },
      success: function () {
        var $row = $btn.closest('tr');
        if ($row.length) resetCronProgressRow($row);
      },
      error: function () {
        $btn.prop('disabled', false);
      }
    });
  });

  $doc.on('click', '[data-bbcs-cron-action="run-all"]', function () {
    var $btn = $(this);
    flashCronBtn($btn);
    $.ajax({
      url: (typeof botblockerData !== 'undefined') ? botblockerData.ajaxurl : ajaxurl,
      type: 'POST',
      data: {
        action: 'bbcs_run_all_cron_tasks',
        nonce: (typeof botblockerData !== 'undefined') ? botblockerData.nonce : ''
      },
      success: function (resp) {
        if (resp.success && resp.data && resp.data.tasks) {
          resp.data.tasks.forEach(function (hook) {
            var $row = $('.bbcs-table--cron tr[data-bbcs-cron-hook="' + hook + '"]');
            if ($row.length) resetCronProgressRow($row);
          });
        }
      },
      error: function () {
        $btn.prop('disabled', false);
      }
    });
  });

  $doc.on('click', '[data-bbcs-cron-action="run-stale"]', function () {
    var $btn = $(this);
    flashCronBtn($btn);
    $.ajax({
      url: (typeof botblockerData !== 'undefined') ? botblockerData.ajaxurl : ajaxurl,
      type: 'POST',
      data: {
        action: 'bbcs_run_stale_cron_tasks',
        nonce: (typeof botblockerData !== 'undefined') ? botblockerData.nonce : ''
      },
      success: function (resp) {
        if (resp.success && resp.data && resp.data.tasks) {
          resp.data.tasks.forEach(function (hook) {
            var $row = $('.bbcs-table--cron tr[data-bbcs-cron-hook="' + hook + '"]');
            if ($row.length) resetCronProgressRow($row);
          });
        }
      },
      error: function () {
        $btn.prop('disabled', false);
      }
    });
  });


});

/* ── 10. Global copy helper (outside ready - available immediately) ── */
window.copyToClipboard = function (button) {
    var text = '', prev = button.previousElementSibling;
    if (prev) {
      if (prev.tagName === 'INPUT') { text = prev.value; }
      else { text = prev.textContent || ''; }
    }
    if (!text) {
      var box = button.closest('.bbcs-field-box');
      if (box) {
        var val = box.querySelector('.bbcs-field-val');
        if (val) text = val.textContent || '';
      }
    }
    if (!text) return;
    text = text.trim();

    var use = button.querySelector('use');
    var origHref = use ? use.getAttribute('href') : '';

    function showCheck() {
      if (use) {
        use.setAttribute('href', '#bbcs-i-check');
        setTimeout(function () { use.setAttribute('href', origHref || '#bbcs-i-copy'); }, 1500);
      }
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(showCheck).catch(function () {
        fallback(text, showCheck);
      });
    } else {
      fallback(text, showCheck);
    }

    function fallback(str, cb) {
      var ta = document.createElement('textarea');
      ta.value = str;
      ta.style.position = 'fixed'; ta.style.left = '-9999px'; ta.style.top = '0'; ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.focus(); ta.select(); ta.setSelectionRange(0, 99999);
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(ta);
      if (cb) cb();
    }
  };

  /* ── 11. Table overview live refresh (Rules page sidebar) ── */
  window.bbcsRefreshTableOverview = (function () {
    var pending = false;
    var L10n = window.bbcsMultipageL10n || {};

    return function () {
      var $container = jQuery('#bbcs-table-overview');
      if (!$container.length) return;
      if (pending) return;
      pending = true;

      jQuery.ajax({
        url: botblockerData.ajaxurl,
        type: 'POST',
        data: {
          action: 'bbcs_refresh_rules_stats',
          nonce: botblockerData.nonce
        },
        success: function (response) {
          if (!response.success || !response.data) return;
          var counts = response.data;
          var total = counts.length;
          var html = '';

          for (var i = 0; i < total; i++) {
            var tc = counts[i];
            var name = tc.name;
            var label = tc.label || name;
            var active = parseInt(tc.active, 10) || 0;
            var disabled = parseInt(tc.disabled, 10) || 0;
            var attention = parseInt(tc.attention, 10) || 0;
            var showDisabled = (name !== 'Proxy' && name !== 'GEO');

            html += '<div class="bbcs-table-overview__item">';
            html += '<div class="bbcs-table-overview__label">' + jQuery('<span>').text(label).html() + '</div>';
            html += '<div class="bbcs-table-overview__stats">';
            html += '<div class="status-stat">';
            html += '<div class="bbcs-stat bbcs-stat--sm bbcs-tx-green">' + active + '</div>';
            html += '<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1">' + (L10n.stat_active || 'Active') + '</div>';
            html += '</div>';

            if (showDisabled) {
              html += '<div class="status-stat">';
              html += '<div class="bbcs-stat bbcs-stat--sm">' + disabled + '</div>';
              html += '<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1">' + (L10n.stat_disabled || 'Disabled') + '</div>';
              html += '</div>';
            }

            if (attention > 0) {
              html += '<div class="status-stat">';
              html += '<div class="bbcs-stat bbcs-stat--sm bbcs-tx-amber">' + attention + '</div>';
              html += '<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1">' + (L10n.stat_attention || 'Attention') + '</div>';
              html += '</div>';
            }

            html += '</div></div>';
          }

          $container.html(html);
        },
        complete: function () {
          pending = false;
        }
      });
    };
  })();

