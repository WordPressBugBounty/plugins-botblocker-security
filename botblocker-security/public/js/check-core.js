// BotBlocker Check Core - Shared JS for FULL and FRONTEND modes
// Requires: window.bbcsJsData to be set BEFORE this script runs.

window.bbcs_cleanAndDecodeBase64ToUtf8 = function(str) {
    str = str.replace(/\s/g, '');
    return decodeURIComponent(escape(window.atob(str)));
};

window.ipv4 = '';
window.ipdbc = '';
window.rct = '';

window.bbcsDebugEnabled = bbcsJsData.debugEnabled ? 'true' : 'false';

window.bbcsDebugLog = function(...args) {
    if (window.bbcsDebugEnabled) {
        console.log('[BBCS DEBUG]', ...args);
    }
};

window.bbcsDebugWarn = function(...args) {
    if (window.bbcsDebugEnabled) {
        console.warn('[BBCS DEBUG]', ...args);
    }
};

window.bbcsDebugError = function(...args) {
    if (window.bbcsDebugEnabled) {
        console.error('[BBCS DEBUG]', ...args);
    }
};

bbcsDebugLog(bbcsJsData.shortName + ' v.' + bbcsJsData.version);

var bbcsDdosRetryCount = 0;
var bbcsDdosMaxRetries = 3;

window.bbcsCheckUI = {
    timerInterval: null,
    secondsLeft: 6,
    attempt: 0,
    maxAttempts: 3,
    _lastMsg: '',

    showVerifying: function () {
        this._stopTimer();
        this.secondsLeft = 6;
        this.attempt = 0;
        this._render(bbcsJsData.verifyingText || 'Verifying...');
    },

    showRetry: function (attemptNum) {
        this._stopTimer();
        this.attempt = attemptNum;
        this._render((bbcsJsData.retryingText || 'Retrying...') + ' (' + attemptNum + '/' + this.maxAttempts + ')', true);
        this._startTimer();
    },

    showCAPTCHA: function () {
        this._stopTimer();
        this._render(bbcsJsData.verificationFailedText || 'Verification failed. Please complete the challenge below.');
    },

    showSuccess: function () {
        this._stopTimer();
        this._render(bbcsJsData.approvedText || 'Access approved. Redirecting...');
    },

    hide: function () {
        this._stopTimer();
        var el = document.getElementById('bbcs-status');
        if (el) { el.innerHTML = ''; el.style.display = 'none'; }
    },

    _render: function (msg, showSeconds) {
        this._lastMsg = msg;
        var el = document.getElementById('bbcs-status');
        if (!el) return;
        el.style.display = '';
        var html = '<div style="margin-bottom:2px;">' + msg + '</div>';
        if (showSeconds) {
            html += '<div style="font-size:12px;color:#999;">' + this.secondsLeft + 's</div>';
        }
        el.innerHTML = html;
    },

    _startTimer: function () {
        var self = this;
        this.timerInterval = setInterval(function () {
            if (self.secondsLeft > 0) {
                self.secondsLeft--;
                self._render(self._lastMsg, true);
            }
            if (self.secondsLeft <= 0) {
                self._stopTimer();
            }
        }, 1000);
    },

    _stopTimer: function () {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }
};

function bbcs_isDdosResponse(responseText, status) {
    if (!responseText) return false;
    if (typeof status !== 'undefined' && status !== 200) return false;
    try {
        JSON.parse(responseText.trim());
        return false;
    } catch(e) {}
    return responseText.indexOf('<') !== -1 || responseText.indexOf('document.cookie') !== -1;
}

function bbcs_extractDdosCookie(responseText) {
    if (!responseText) return false;
    var cookiePatterns = [
        /document\.cookie\s*=\s*"([^"]+)"/,
        /document\.cookie\s*=\s*'([^']+)'/
    ];
    for (var i = 0; i < cookiePatterns.length; i++) {
        var cookieMatch = responseText.match(cookiePatterns[i]);
        if (cookieMatch && cookieMatch[1]) {
            bbcsDebugLog('DDoS protection cookie detected, setting: ' + cookieMatch[1].substring(0, 20) + '...');
            document.cookie = cookieMatch[1];
            return true;
        }
    }
    return false;
}

function bbcs_handleDdosResponse(responseText, s, d, x, checkFn) {
    bbcs_extractDdosCookie(responseText);
    if (bbcsDdosRetryCount < bbcsDdosMaxRetries) {
        bbcsDdosRetryCount++;
        if (bbcsJsData.ddosResilience) {
            bbcsCheckUI.showRetry(bbcsDdosRetryCount);
        }
        var delay = bbcsDdosRetryCount * 1000;
        bbcsDebugLog('DDoS retry ' + bbcsDdosRetryCount + '/' + bbcsDdosMaxRetries + ' in ' + delay + 'ms');
        setTimeout(function() {
            checkFn(s, d, x);
        }, delay);
        return true;
    }
    if (bbcsJsData.ddosResilience) {
        bbcsCheckUI.showCAPTCHA();
    }
    var rc = 0;
    try { rc = parseInt(sessionStorage.getItem('bbcsRedirectCount') || '0', 10); } catch(e) {}
    if (rc >= 2) {
        bbcsDebugLog('DDoS redirect loop detected, stopping');
        botblocker_captcha_render();
        return true;
    }
    try { sessionStorage.setItem('bbcsRedirectCount', String(rc + 1)); } catch(e) {}
    bbcsDebugLog('DDoS retries exhausted, redirecting to page');
    window.location.href = bbcsJsData.redirectUrl;
    return true;
}

function bbcs_detectAll() {
    const results = {
        navigatorMismatch: bbcs_detectNavigatorMismatch(),
        unsupportedFeatures: bbcs_detectUnsupportedFeatures(),
        fakePlugins: bbcs_detectFakePlugins(),
        fontRenderMismatch: bbcs_detectFontRenderMismatch(),
        chromiumProperties: bbcs_detectChromiumProperties(),
        jitter: bbcs_detectJitter(),
        webGLMismatch: bbcs_detectWebGL(),
        touchEventMismatch: bbcs_detectTouchEvent(),
        languageMismatch: bbcs_detectLanguageMismatch(),
        crossbrowserIncognito: bbcs_isIncognito(),
        browserFingerprint: bbcs_computeFingerprint()
    };
    return results;
}

function bbcs_getDetectionParams() {
    const startTime = Date.now();
    const timeoutLimit = 1000;
    try {
        const detectionResult = bbcs_detectAll();
        if (Date.now() - startTime > timeoutLimit) {
            bbcsDebugWarn('Detection methods took too long, returning partial results');
        }
        detectionResult.browserFingerprint = detectionResult.browserFingerprint.fingerprint;
        let detectionParams = '';
        for (const [key, value] of Object.entries(detectionResult)) {
            detectionParams += '&' + key + '=' + encodeURIComponent(value);
        }
        return detectionParams;
    } catch (e) {
        bbcsDebugError('Error during detection:', e);
        return '&error=detection_failed';
    }
}

function bbcs_areCookiesEnabled() {
    var cookieEnabled = navigator.cookieEnabled;
    if (cookieEnabled === undefined) {
        document.cookie = "testcookie";
        cookieEnabled = document.cookie.indexOf("testcookie") != -1;
    }
    return cookieEnabled;
}

if (!bbcs_areCookiesEnabled()) {
    var cookieoff = 1;
} else {
    var cookieoff = 0;
}

var _host = window.location.hostname;
if (_host.indexOf('www.') === 0) { _host = _host.substring(4); }
if (_host !== window.atob(bbcsJsData.hostBase64) &&
    _host !== window.atob(bbcsJsData.hostNoPortBase64)) {
    window.location = window.atob(bbcsJsData.redirectUrlBase64);
    throw "stop";
}

document.getElementById("content").innerHTML = bbcsJsData.loadingText;

function handleWorkerSignal() {
    return new Promise(function(resolve) {
        if (bbcsJsData.recaptchaEnabled) {
            grecaptcha.ready(function() {
                grecaptcha.execute(bbcsJsData.recaptchaKey3, {
                    action: bbcsJsData.country
                }).then(function(token) {
                    window.rct = token;
                    resolve('HWS');
                });
            });
        } else {
            window.rct = '';
            resolve('HWS');
        }
    });
}

function makeIpv6Request(url) {
    return new Promise(function(resolve) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.timeout = 5000;
        xhr.onload = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var json = JSON.parse(xhr.responseText);
                window.ipv4 = json.ip;
                window.ipdbc = bbcsJsData.emptyValue;
                resolve(true);
            } else {
                bbcsDebugError('Error status:', xhr.status);
                resolve(false);
            }
        };
        xhr.ontimeout = function() {
            bbcsDebugError('Timeout');
            resolve(false);
        };
        xhr.onerror = function() {
            bbcsDebugError('Error');
            resolve(false);
        };
        xhr.send();
    });
}

async function dispatchServiceEvent() {
    if (bbcsJsData.ipVersion == 6) {
        var ok = await makeIpv6Request(bbcsJsData.apiIpv6);
        if (!ok) {
            ok = await makeIpv6Request(bbcsJsData.apiGsIpv6);
        }
        return ok ? 'DSE' : 'Error DSE';
    }
    window.ipv4 = '';
    window.ipdbc = '';
    return 'Result DSE';
}

function initProcessHandler(result1, result2) {
    var bbcs_detectionParams = bbcs_getDetectionParams();
    window.data = 'test=' + bbcsJsData.testHash +
        '&h1=' + bbcsJsData.h1Hash +
        '&date=' + bbcsJsData.time +
        '&hdc=' + bbcsJsData.hosting +
        '&a=' + window.adb_var +
        '&country=' + bbcsJsData.country +
        '&ip=' + bbcsJsData.ip +
        '&version=' + bbcsJsData.version +
        '&cid=' + bbcsJsData.cid +
        '&ptr=' + bbcsJsData.ptr +
        '&w=' + screen.width +
        '&h=' + screen.height +
        '&cw=' + document.documentElement.clientWidth +
        '&ch=' + document.documentElement.clientHeight +
        '&co=' + screen.colorDepth +
        '&pi=' + screen.pixelDepth +
        '&ref=' + encodeURIComponent(document.referrer) +
        '&accept=' + bbcsJsData.httpAccept +
        '&tz=' + Intl.DateTimeFormat().resolvedOptions().timeZone +
        '&ipdbc=' + window.ipdbc +
        '&ipv4=' + window.ipv4 +
        '&rct=' + window.rct +
        '&cookieoff=' + cookieoff +
        bbcs_detectionParams;
    window[bbcsJsData.checkFunctionName]('botblocker-security', window.data, '');
    bbcsDebugLog('initProcessHandler -> ', result1, result2);
}

async function performAsyncStep() {
    try {
        const result1 = await handleWorkerSignal();
        const result2 = await dispatchServiceEvent();
        initProcessHandler(result1, result2);
    } catch (error) {
        bbcsDebugError(error);
    }
}

performAsyncStep();

function botblocker_captcha_render() {
    if (bbcsJsData.ddosResilience && typeof bbcsCircuitBreaker !== 'undefined' && bbcsCircuitBreaker.isOpen()) {
        bbcsCircuitBreaker.showFallback();
        return;
    }
    if (typeof renderCaptcha === 'function') {
        renderCaptcha();
    }
}

window[bbcsJsData.checkFunctionName] = function(s, d, x, ajaxEndpoint) {
    if (bbcsJsData.ddosResilience) {
        if (typeof bbcsCircuitBreaker !== 'undefined' && bbcsCircuitBreaker.isOpen()) {
            bbcsCircuitBreaker.showFallback();
            return;
        }
    }
    if (!ajaxEndpoint) ajaxEndpoint = bbcsJsData.ajaxUrl;
    if (bbcsJsData.ddosResilience) {
        if (bbcsDdosRetryCount === 0) {
            bbcsCheckUI.showVerifying();
        }
    } else {
        document.getElementById("content").innerHTML = bbcsJsData.loadingText;
    }

    var formData = new FormData();
    formData.append('action', 'bbcs_botblocker_check');
    formData.append('nonce', bbcsJsData.nonce);
    formData.append(bbcsJsData.selectRequestMode, s);
    formData.append('xxx', x);
    formData.append('rowid', bbcsJsData.ruleRecordId);
    formData.append('from_suspect', bbcsJsData.suspectStatus);
    formData.append('suspect_reason', bbcsJsData.reasonForAction);
    formData.append('check_result', bbcsJsData.resultOfAction);

    if (typeof bbcsCaptchaData !== 'undefined' && bbcsCaptchaData.challengeToken) {
        formData.append('challenge_token', bbcsCaptchaData.challengeToken);
    }

    var additionalParams = new URLSearchParams(d);
    for (var pair of additionalParams.entries()) {
        formData.append(pair[0], pair[1]);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxEndpoint, true);
    xhr.timeout = bbcsDdosRetryCount > 0 ? 10000 : 5000;

    xhr.onload = function() {
        if (xhr.status == 200) {
            bbcsDebugLog('Plugin status: ' + xhr.status);
            try {
                var responseText = xhr.responseText.trim();
                bbcsDebugLog('Response text:', responseText);

                if (responseText) {
                    var obj = JSON.parse(responseText);

                    var bbcsSigValid = true;
                    if (bbcsJsData.ddosResilience) {
                        if (typeof obj.bbcs_sig !== 'string') {
                            bbcsSigValid = false;
                        } else {
                            delete obj.bbcs_sig;
                        }
                    }

                    if (bbcsJsData.ddosResilience && bbcsSigValid && typeof obj.cookie !== 'string') {
                        var backupCookie = xhr.getResponseHeader('X-BBCS-' + bbcsJsData.uid);
                        if (backupCookie) {
                            obj.cookie = backupCookie;
                        }
                    }

                    if (typeof(obj.cookie) == "string") {
                        bbcsDebugLog('Cookie received, value=' + obj.cookie.substring(0,20) + '... uid=' + bbcsJsData.uid + ' redirectUrl=' + bbcsJsData.redirectUrl);
                        if (bbcsJsData.ddosResilience) {
                            bbcsCheckUI.showSuccess();
                            if (typeof bbcsCircuitBreaker !== 'undefined') {
                                if (bbcsSigValid) {
                                    bbcsCircuitBreaker.recordSuccess();
                                } else {
                                    bbcsCircuitBreaker.recordFailure();
                                }
                            }
                        }
                        var expiryDate = new Date();
                        expiryDate.setTime(expiryDate.getTime() + ((bbcsJsData.cookieLifetime || 604800) * 1000));
                        var expires = "expires=" + expiryDate.toUTCString();
                        var bbcsSS = bbcsJsData.samesite;
                        if (bbcsSS === 'None' && window.location.protocol !== 'https:') { bbcsSS = 'Lax'; }
                        var cookieStr = bbcsJsData.uid + "=" + obj.cookie + "-" + bbcsJsData.time + "; SameSite=" + bbcsSS + ";" +
                            (bbcsSS === 'None' ? ' Secure' : '') + "; " + expires + "; path=/;";
                        bbcsDebugLog('Setting cookie: name=' + bbcsJsData.uid + ' value_len=' + (obj.cookie + "-" + bbcsJsData.time).length);
                        document.cookie = cookieStr;
                        var verifyCookie = document.cookie;
                        bbcsDebugLog('document.cookie after set (first 80 chars): ' + (verifyCookie ? verifyCookie.substring(0,80) : 'EMPTY') + ' hasUidCookie=' + (verifyCookie && verifyCookie.indexOf(bbcsJsData.uid) !== -1));
                        try { sessionStorage.removeItem('bbcsRedirectCount'); } catch(e) {}
                        if (bbcsJsData.silentMode) {
                            try { sessionStorage.removeItem('bbcsMode8Retries'); } catch(e) {}
                            document.getElementById("content").innerHTML = bbcsJsData.approvedText;
                            setTimeout(function() { window.location.href = bbcsJsData.redirectUrl; }, 0);
                        } else {
                            document.getElementById("content").innerHTML = bbcsJsData.loadingText;
                            bbcsDebugLog('redirecting to ' + bbcsJsData.redirectUrl);
                            window.location.href = bbcsJsData.redirectUrl;
                        }
                    } else if (!bbcsSigValid) {
                        bbcsDebugLog('No cookie AND invalid sig. cookie=' + (typeof obj.cookie) + ' sigValid=' + bbcsSigValid + ' ddos=' + bbcsJsData.ddosResilience);
                        if (typeof bbcsCircuitBreaker !== 'undefined') {
                            bbcsCircuitBreaker.recordFailure();
                        }
                        if (bbcsJsData.ddosResilience) {
                            bbcsCheckUI.showCAPTCHA();
                        }
                        botblocker_captcha_render();
                        bbcsDebugLog('Response signature missing or invalid; rejecting tampered response');
                    } else {
                        bbcsDebugLog('No cookie, sig valid. cookie=' + (typeof obj.cookie) + ' keys=' + Object.keys(obj).join(','));
                        if (bbcsJsData.ddosResilience) {
                            bbcsCheckUI.showCAPTCHA();
                        }
                        botblocker_captcha_render();
                        bbcsDebugLog('Bad bot detected');
                    }

                    if (typeof(obj.error) == "string") {
                        if (bbcsJsData.jsAdminEnabled) {
                            if (obj.error == "BotBlocker Account Not Found"
                                || obj.error == "This domain doesn't have a valid license"
                                || obj.error == "Subscription Expired"
                                || obj.error == "This domain is not registered or is not active"
                                || obj.error == bbcsJsData.jsErrorMessage) {
                                const ErrorMsg = document.createElement('div');
                                const errH1 = document.createElement('h1');
                                errH1.style.textAlign = 'center';
                                errH1.style.color = 'red';
                                errH1.textContent = obj.error;
                                ErrorMsg.appendChild(errH1);
                                document.body.insertAdjacentElement('afterbegin', ErrorMsg);
                                document.getElementById("content").style.visibility = "hidden";
                                document.getElementById("content").innerHTML = '';
                            } else if (obj.error == "Cookies disabled") {
                                document.getElementById("content").innerHTML =
                                "<h2 style=\"text-align:center; color:red;\">" + bbcsJsData.cookieDisabledText + "</h2>";
                            }
                        }
                        if (obj.error == "timeout" || obj.error == "Wrong Click") {
                            if (bbcsJsData.silentMode) {
                                var r = 0;
                                try { r = parseInt(sessionStorage.getItem('bbcsMode8Retries') || '0', 10); } catch(e) {}
                                if (r < 2) {
                                    try { sessionStorage.setItem('bbcsMode8Retries', String(r + 1)); } catch(e) {}
                                    window.location.reload();
                                    return;
                                }
                            }
                            document.getElementById("content").innerHTML = bbcsJsData.loadingText;
                            window.location.href = bbcsJsData.redirectUrl;
                        }
                    }
                } else {
                    bbcsDebugWarn('Empty or invalid response from server.');
                    if (ajaxEndpoint !== bbcsJsData.verifyUrl && bbcsJsData.verifyUrl) {
                        bbcsDebugLog('admin-ajax.php returned empty response, trying verify endpoint');
                        window[bbcsJsData.checkFunctionName](s, d, x, bbcsJsData.verifyUrl);
                        return;
                    }
                    if (bbcsJsData.ddosResilience) {
                        if (typeof bbcsCircuitBreaker !== 'undefined') {
                            bbcsCircuitBreaker.recordFailure();
                        }
                        bbcsCheckUI.showCAPTCHA();
                    }
                    botblocker_captcha_render();
                }
            } catch (e) {
                bbcsDebugError('Error parsing JSON:', e);
                bbcsDebugLog('Response text received:', xhr.responseText);
                if (bbcs_isDdosResponse(xhr.responseText, xhr.status)) {
                    var checkFn = window[bbcsJsData.checkFunctionName];
                    if (bbcs_handleDdosResponse(xhr.responseText, s, d, x, checkFn)) return;
                }
                if (bbcsJsData.ddosResilience) {
                    if (typeof bbcsCircuitBreaker !== 'undefined') {
                        bbcsCircuitBreaker.recordFailure();
                    }
                    bbcsCheckUI.showCAPTCHA();
                }
                botblocker_captcha_render();
            }

        } else {
            bbcsDebugLog('Error: ' + xhr.status);
            if (ajaxEndpoint !== bbcsJsData.verifyUrl && bbcsJsData.verifyUrl) {
                bbcsDebugLog('admin-ajax.php returned ' + xhr.status + ', trying verify endpoint');
                window[bbcsJsData.checkFunctionName](s, d, x, bbcsJsData.verifyUrl);
                return;
            }
            if (bbcs_isDdosResponse(xhr.responseText, xhr.status)) {
                var checkFn = window[bbcsJsData.checkFunctionName];
                if (bbcs_handleDdosResponse(xhr.responseText, s, d, x, checkFn)) return;
            }
            if (bbcsJsData.ddosResilience) {
                if (typeof bbcsCircuitBreaker !== 'undefined') {
                    bbcsCircuitBreaker.recordFailure();
                }
                bbcsCheckUI.showCAPTCHA();
            }
            botblocker_captcha_render();
        }
    };

    xhr.ontimeout = function() {
        bbcsDebugLog('timeout');
        if (ajaxEndpoint !== bbcsJsData.verifyUrl && bbcsJsData.verifyUrl) {
            bbcsDebugLog('admin-ajax.php timeout, trying verify endpoint');
            window[bbcsJsData.checkFunctionName](s, d, x, bbcsJsData.verifyUrl);
            return;
        }
        if (bbcsDdosRetryCount > 0) {
            bbcsDebugLog('Timeout during DDoS retry, redirecting');
            window.location.href = bbcsJsData.redirectUrl;
            return;
        }
        if (bbcsJsData.ddosResilience) {
            if (typeof bbcsCircuitBreaker !== 'undefined') {
                bbcsCircuitBreaker.recordFailure();
            }
            bbcsCheckUI.showCAPTCHA();
        }
        botblocker_captcha_render();
    };

    xhr.onerror = function() {
        bbcsDebugLog('error');
        if (ajaxEndpoint !== bbcsJsData.verifyUrl && bbcsJsData.verifyUrl) {
            bbcsDebugLog('admin-ajax.php error, trying verify endpoint');
            window[bbcsJsData.checkFunctionName](s, d, x, bbcsJsData.verifyUrl);
            return;
        }
        if (bbcsDdosRetryCount > 0) {
            bbcsDebugLog('Error during DDoS retry, redirecting');
            window.location.href = bbcsJsData.redirectUrl;
            return;
        }
        if (bbcsJsData.ddosResilience) {
            if (typeof bbcsCircuitBreaker !== 'undefined') {
                bbcsCircuitBreaker.recordFailure();
            }
            bbcsCheckUI.showCAPTCHA();
        }
        botblocker_captcha_render();
    };

    xhr.send(formData);
};
