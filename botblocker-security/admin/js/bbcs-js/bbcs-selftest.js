(function () {
  'use strict';

  var cfg = window.bbcsSelfTestCfg || {};
  var i18n = cfg.i18n || {};

  var POLL_INTERVAL = 1500;
  var TIMEOUT_MS = 15000;
  var MAX_RUNTIME_MS = 120000;

  var ICONS = {
    ok: '✓',
    warn: '!',
    bad: '✕'
  };

  var button = document.getElementById('bbcs-selftest-run');
  var frame = document.getElementById('bbcs-selftest-frame');
  var placeholder = document.getElementById('bbcs-st-placeholder');
  var resultBox = document.getElementById('bbcs-st-result');
  var settingsBox = document.getElementById('bbcs-st-settings');
  var whyBox = document.getElementById('bbcs-st-why');
  var stepsBox = document.getElementById('bbcs-st-steps');
  var about = document.getElementById('bbcs-st-about');

  var timer = null;

  function createElement(tag, className, text) {
    var node = document.createElement(tag);

    if (className) {
      node.className = className;
    }
    if (typeof text === 'string') {
      node.textContent = text;
    }

    return node;
  }

  function clear(node) {
    while (node.firstChild) {
      node.removeChild(node.firstChild);
    }
  }

  function post(action, data) {
    var body = new FormData();

    body.append('action', action);
    body.append('nonce', cfg.nonce);

    Object.keys(data || {}).forEach(function (key) {
      body.append(key, data[key]);
    });

    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      body: body,
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json();
    });
  }

  function toneIcon(tone) {
    if (tone === 'info') {
      return createElement('span', 'bbcs-st-spin');
    }

    return document.createTextNode(ICONS[tone] || '?');
  }

  function setResult(tone, title, text) {
    resultBox.setAttribute('data-tone', tone);
    clear(resultBox);

    var icon = createElement('div', 'bbcs-st-icon');
    icon.appendChild(toneIcon(tone));

    var content = createElement('div');
    content.appendChild(createElement('strong', '', title));
    content.appendChild(createElement('span', 'bbcs-st-sub', text));

    resultBox.appendChild(icon);
    resultBox.appendChild(content);
  }

  function stepFor(event, silent) {
    var steps = i18n.steps || {};

    switch (event.code) {
      case 'allow_rule_ignored':
        return {
          tone: 'info',
          icon: '→',
          text: steps.allowIgnored,
          note: event.detail
        };

      case 'challenge_page':
        return {
          tone: 'info',
          icon: '→',
          text: steps.challengePage,
          note: steps.challengePageNote
        };

      case 'captcha_required':
        if (silent) {
          return {
            tone: 'info',
            icon: '→',
            text: steps.captchaSilent,
            note: steps.captchaSilentNote
          };
        }
        return {
          tone: 'ok',
          icon: '✓',
          text: steps.captchaShown,
          note: steps.captchaShownNote
        };

      case 'passed_silently':
        return {
          tone: 'warn',
          icon: '!',
          text: steps.passedSilently,
          note: steps.passedSilentlyNote
        };

      case 'captcha_timeout':
        return {
          tone: 'warn',
          icon: '!',
          text: steps.timeout,
          note: (event.detail ? event.detail + '. ' : '') + steps.timeoutNote
        };

      case 'captcha_wrong':
        return {
          tone: 'bad',
          icon: '✕',
          text: steps.wrong,
          note: steps.wrongNote
        };

      case 'captcha_solved':
        if (silent) {
          return {
            tone: 'ok',
            icon: '✓',
            text: steps.solvedSilent,
            note: steps.solvedSilentNote
          };
        }
        return {
          tone: 'ok',
          icon: '✓',
          text: steps.solved,
          note: ''
        };

      case 'site_served':
        return {
          tone: 'ok',
          icon: '✓',
          text: steps.served,
          note: ''
        };
    }

    return {
      tone: 'info',
      icon: '•',
      text: event.code,
      note: event.detail || ''
    };
  }

  function formatElapsed(seconds) {
    var template = (i18n.steps && i18n.steps.timeSuffix) || '+%ds';

    return template.replace('%d', String(seconds));
  }

  function renderSteps(events, silent) {
    if (!events.length) {
      return;
    }

    var startedAt = events[0].time;
    var previousKey = '';

    clear(stepsBox);

    events.forEach(function (event) {
      var key = event.code + '|' + event.detail;

      if (key === previousKey) {
        return;
      }
      previousKey = key;

      var step = stepFor(event, silent);
      var note = step.note;
      var elapsed = event.time - startedAt;

      if (elapsed > 0) {
        note = (note ? note + ' · ' : '') + formatElapsed(elapsed);
      }

      var item = createElement('li');
      item.appendChild(createElement('span', 'bbcs-st-dot ' + step.tone, step.icon));
      item.appendChild(document.createTextNode(step.text));

      if (note) {
        item.appendChild(createElement('small', '', note));
      }

      stepsBox.appendChild(item);
    });
  }

  function pill(text, variant) {
    var className = 'bbcs-st-pill' + (variant ? ' ' + variant : '');

    return createElement('span', className, text);
  }

  function onOff(value) {
    var settings = i18n.settings || {};

    return pill(value ? settings.on : settings.off, value ? 'on' : 'off');
  }

  function settingsRow(label, valueNode) {
    var row = createElement('li');

    row.appendChild(document.createTextNode((label || '') + ' '));
    row.appendChild(valueNode);

    return row;
  }

  function renderSettings(settings) {
    var labels = i18n.settings || {};
    var rows = createElement('ul', 'bbcs-st-rows');

    rows.appendChild(settingsRow(labels.protection, onOff(!settings.disabled)));
    rows.appendChild(settingsRow(labels.mode, pill(settings.secure_mode)));
    rows.appendChild(settingsRow(
      labels.captcha,
      settings.silent ? pill(labels.silent, 'warn') : pill(settings.captcha_mode)
    ));
    rows.appendChild(settingsRow(labels.forceCheck, onOff(settings.force_check)));

    clear(settingsBox);
    settingsBox.appendChild(createElement('h2', '', labels.title));

    if (settings.silent) {
      settingsBox.appendChild(createElement('div', 'bbcs-st-alert', labels.silentAlert));
    }

    settingsBox.appendChild(rows);
    settingsBox.hidden = false;
  }

  function renderWhy(reasons) {
    if (!reasons.length) {
      whyBox.hidden = true;
      return;
    }

    var labels = i18n.bypass || {};
    var list = createElement('ul', 'bbcs-st-why');

    reasons.forEach(function (reason) {
      list.appendChild(createElement('li', '', labels[reason] || reason));
    });

    clear(whyBox);
    whyBox.appendChild(createElement('h2', '', i18n.whyTitle));
    whyBox.appendChild(list);
    whyBox.hidden = false;
  }

  function renderResult(data, timedOut) {
    var events = data.events || [];
    var served = events.some(function (event) {
      return event.code === 'site_served';
    });
    var verdict = data.verdict;
    var settings = data.settings || {};
    var labels = i18n.result || {};

    if (verdict === 'captcha_solved' && served) {
      return setResult('ok', labels.worksTitle, labels.worksSolvedServed);
    }
    if (verdict === 'captcha_solved' || verdict === 'captcha_shown') {
      return setResult('ok', labels.worksTitle, labels.worksSolvedPending);
    }
    if (verdict === 'passed_silently' && settings.silent) {
      return setResult('ok', labels.worksTitle, served ? labels.worksSilentServed : labels.worksSilentPending);
    }
    if (verdict === 'passed_silently') {
      return setResult('warn', labels.checkedTitle, served ? labels.checkedServed : labels.checkedPending);
    }
    if (timedOut && verdict === 'pending') {
      return setResult('bad', labels.notCheckedTitle, labels.notCheckedText);
    }
    if (verdict === 'challenge_page') {
      return setResult('info', labels.checkingTitle, labels.checkingText);
    }

    return setResult('info', labels.loadingTitle, labels.loadingText);
  }

  function finish() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }

    button.disabled = false;
    button.textContent = i18n.runAgain || '';
  }

  function showStartError() {
    setResult('bad', i18n.startFailedTitle, i18n.startFailedText);
    finish();
  }

  button.addEventListener('click', function () {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }

    button.disabled = true;
    button.textContent = i18n.testing || '';

    if (about) {
      about.open = false;
    }

    setResult('info', i18n.startingTitle, i18n.startingText);

    clear(stepsBox);
    stepsBox.appendChild(createElement('li', 'bbcs-st-empty', i18n.waitingForStep));

    post('bbcs_selftest_start').then(function (response) {
      if (!response || !response.success || !response.data) {
        showStartError();
        return;
      }

      var token = response.data.token;
      var startedAt = Date.now();

      placeholder.hidden = true;
      frame.hidden = false;
      frame.src = response.data.url;

      timer = setInterval(function () {
        post('bbcs_selftest_status', { token: token }).then(function (response) {
          if (!response || !response.success || !response.data) {
            return;
          }

          var data = response.data;
          var events = data.events || [];
          var settings = data.settings || {};
          var elapsed = Date.now() - startedAt;
          var timedOut = elapsed > TIMEOUT_MS;
          var served = events.some(function (event) {
            return event.code === 'site_served';
          });

          renderResult(data, timedOut);
          renderSettings(settings);
          renderWhy(data.bypasses || []);
          renderSteps(events, Boolean(settings.silent));

          if (served || (timedOut && data.verdict === 'pending') || elapsed > MAX_RUNTIME_MS) {
            finish();
          }
        }).catch(function () {
          setResult('bad', i18n.statusFailedTitle, i18n.statusFailedText);
          finish();
        });
      }, POLL_INTERVAL);
    }).catch(showStartError);
  });
})();
