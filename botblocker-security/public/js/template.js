// BotBlocker Template JavaScript

window.bbcs_clean_and_decode_base64_to_utf8 = function(str) {
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
		this._render('Verifying...');
	},

	showRetry: function (attemptNum) {
		this.attempt = attemptNum;
		this._render('Retrying... (' + attemptNum + '/' + this.maxAttempts + ')');
		this._startTimer();
	},

	showCAPTCHA: function () {
		this._stopTimer();
		this._render('Verification failed. Please complete the challenge below.');
	},

	showFallback: function () {
		this._stopTimer();
		this._render('Verification is temporarily unavailable. Please wait and reload the page.');
	},

	showSuccess: function () {
		this._stopTimer();
		this._render('Access approved. Redirecting...');
	},

	hide: function () {
		this._stopTimer();
		var el = document.getElementById('bbcs-status');
		if (el) { el.innerHTML = ''; }
	},

	_render: function (msg) {
		this._lastMsg = msg;
		var el = document.getElementById('bbcs-status');
		if (!el) return;
		el.innerHTML = '<div style="margin-bottom:2px;">' + msg + '</div>'
			+ '<div style="font-size:12px;color:#999;">' + this.secondsLeft + 's</div>';
	},

	_startTimer: function () {
		var self = this;
		this.timerInterval = setInterval(function () {
			if (self.secondsLeft > 0) {
				self.secondsLeft--;
				self._render(self._lastMsg);
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

function bbcs_isDdosResponse(responseText) {
    if (!responseText) return false;
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
        batteryAPIMismatch: bbcs_detectBatteryAPI(),
        mediaDevicesMismatch: bbcs_detectMediaDevices(),
        permissionsMismatch: bbcs_detectPermissions(),
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
            detectionParams += `&${key}=${encodeURIComponent(value)}`;
        }
        return detectionParams;
    } catch (e) {
        bbcsDebugError('Error during detection:', e);
        return `&error=detection_failed`;
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

if (window.location.hostname !== window.atob(bbcsJsData.hostBase64) && 
    window.location.hostname !== window.atob(bbcsJsData.hostNoPortBase64)) {
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

function dispatchServiceEvent() {
    return new Promise(function(resolve) {
        if (bbcsJsData.ipVersion == 6) {
            var GLOBUS_studio_API_request = new XMLHttpRequest();
            GLOBUS_studio_API_request.open('GET', bbcsJsData.apiGsIpv6, true);
            GLOBUS_studio_API_request.setRequestHeader("Content-Type", "application/json");
            GLOBUS_studio_API_request.timeout = 5000; 
            GLOBUS_studio_API_request.onload = function() {
                if (GLOBUS_studio_API_request.readyState === 4 && GLOBUS_studio_API_request.status === 200) {
                    var json = JSON.parse(GLOBUS_studio_API_request.responseText);
                    window.ipv4 = json.ip;
                    window.ipdbc = bbcsJsData.emptyValue;
                    resolve('DSE');
                } else {
                    bbcsDebugError('Error status:', GLOBUS_studio_API_request.status);
                    resolve('Error DSE');
                }
            };
            GLOBUS_studio_API_request.ontimeout = function() {
                bbcsDebugError('Timeout');
                resolve('Error DSE');
            };
            GLOBUS_studio_API_request.onerror = function() {
                bbcsDebugError('Error');
                resolve('Error DSE');
            };
            GLOBUS_studio_API_request.send();
        } else {
            window.ipv4 = '';
            window.ipdbc = '';
            resolve('Result DSE');
        }
    });
}

function initProcessHandler(result1, result2) {
    bbcsDebugLog('initProcessHandler: mode=' + bbcsJsData.selectRequestMode + ' debugEnabled=' + bbcsJsData.debugEnabled + ' ddosResilience=' + bbcsJsData.ddosResilience);
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
    bbcsDebugLog('botblocker_captcha_render called. renderCaptcha defined=' + (typeof renderCaptcha === 'function') + ' bbcsCaptchaData defined=' + (typeof bbcsCaptchaData !== 'undefined'));
    if (bbcsJsData.ddosResilience && typeof bbcsCircuitBreaker !== 'undefined' && bbcsCircuitBreaker.isOpen()) {
        return;
    }
    if (typeof renderCaptcha === 'function') {
        renderCaptcha();
    }
}

window[bbcsJsData.checkFunctionName] = function(s, d, x, ajaxEndpoint) {
    if (bbcsJsData.ddosResilience) {
        if (typeof bbcsCircuitBreaker !== 'undefined' && bbcsCircuitBreaker.isOpen()) {
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
    
    var data = new FormData();
    data.append('action', 'bbcs_botblocker_check');
    data.append('nonce', bbcsJsData.nonce);
    data.append(bbcsJsData.selectRequestMode, s);
    data.append('xxx', x);
    data.append('rowid', bbcsJsData.ruleRecordId);
    data.append('from_suspect', bbcsJsData.suspectStatus);
    data.append('suspect_reason', bbcsJsData.reasonForAction);
    data.append('check_result', bbcsJsData.resultOfAction);
    if (typeof bbcsCaptchaData !== 'undefined' && bbcsCaptchaData.challengeToken) {
        data.append('challenge_token', bbcsCaptchaData.challengeToken);
    }

    var additionalParams = new URLSearchParams(d);
    for (var pair of additionalParams.entries()) {
        data.append(pair[0], pair[1]);
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

                    if (bbcsJsData.ddosResilience) {
                        if (typeof obj.bbcs_sig !== 'string') {
                            if (typeof bbcsCircuitBreaker !== 'undefined') {
                                bbcsCircuitBreaker.recordFailure();
                            }
                        } else {
                            delete obj.bbcs_sig;
                        }

                        if (typeof obj.cookie !== 'string') {
                            var backupCookie = xhr.getResponseHeader('X-BBCS-' + bbcsJsData.uid);
                            if (backupCookie) {
                                obj.cookie = backupCookie;
                            }
                        }
                    }

                    if (typeof(obj.cookie) == "string") {
                        bbcsDebugLog('Got cookie in response, redirecting');
                        if (bbcsJsData.ddosResilience) {
                            bbcsCheckUI.showSuccess();
                            if (typeof bbcsCircuitBreaker !== 'undefined') {
                                bbcsCircuitBreaker.failures = 0;
                            }
                        }
                        var d = new Date();
                        d.setTime(d.getTime() + ((bbcsJsData.cookieLifetime || 604800) * 1000)); 
                        var expires = "expires=" + d.toUTCString();
                        var bbcsSS = bbcsJsData.samesite;
                        if (bbcsSS === 'None' && window.location.protocol !== 'https:') { bbcsSS = 'Lax'; }
                        document.cookie = bbcsJsData.uid + "=" + obj.cookie + "-" + bbcsJsData.time + "; SameSite=" + bbcsSS + ";" + 
                            (bbcsSS === 'None' ? ' Secure' : '') + "; " + expires + "; path=/;";
                        if (bbcsJsData.silentMode) {
                            try { sessionStorage.removeItem('bbcsMode8Retries'); } catch(e) {}
                            document.getElementById("content").innerHTML = bbcsJsData.approvedText;
                            setTimeout(function() { window.location.href = bbcsJsData.redirectUrl; }, 0);
                        } else {
                            document.getElementById("content").innerHTML = bbcsJsData.loadingText;
                            window.location.href = bbcsJsData.redirectUrl;
                        }
                    } else {
                        botblocker_captcha_render();
                        bbcsDebugLog('Bad bot detected');
                    }

                    if (typeof(obj.error) == "string") {
                        if (bbcsJsData.jsAdminEnabled) {
                            if (obj.error == "CiberSecure Account Not Found" 
                                || obj.error == "This domain don't have a valid license" 
                                || obj.error == "Subscription Expired" 
                                || obj.error == "This domain is not registered or not active"
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
                }
            } catch (e) {
                bbcsDebugError('Error parsing JSON:', e);
                bbcsDebugLog('Response text received:', xhr.responseText);
                if (bbcs_isDdosResponse(xhr.responseText)) {
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
            if (bbcs_isDdosResponse(xhr.responseText)) {
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

    xhr.send(data);
};
