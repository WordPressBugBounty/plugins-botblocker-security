<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

/**
 * BotBlocker JavaScript Template
 *
 * @package    BotBlocker
 * @subpackage BotBlocker/javascript-template
 * @author     BotBlocker
 * @copyright  Copyright (c) 2026, BotBlocker
 *
 */

global $wpdb;
$BBCS                    = BotBlocker::getInstance();
$bbcs_effective_samesite = BotBlocker::effective_samesite( $BBCS->settings->samesite );

/**
 * REVIEWER NOTE:
 * The conventional WordPress script enqueuing mechanism is intentionally bypassed in this template.
 * This template is executed during the early stages of plugin initialization, preceding the availability of enqueuing functions,
 * and directly outputs content to the client, terminating execution and circumventing the standard WordPress rendering process.
 * This approach is implemented as a performance optimization to reduce server resource utilization for requests identified as likely automated or non-human traffic.
 * Consequently, the internal logic of wp_enqueue_scripts is invoked programmatically within this context to ensure that all necessary assets are properly loaded.
 */
$wp_scripts = wp_scripts();
$wp_scripts->add( 'bbcs-inline', '', array(), BOTBLOCKER_VERSION, true );
$wp_scripts->add_inline_script( 'bbcs-inline', 'var adb_var = 1;' );
$wp_scripts->add( 'adblock-blocker', esc_url_raw( $BBCS->botblockerUrl . 'public/js/rails.js?bannerid=' . $BBCS->time ), array(), null, true );
$wp_scripts->add( 'bbcs-detection-utils', esc_url_raw( $BBCS->botblockerUrl . 'public/js/detection-utils.js?ver=' . $BBCS->time ), array( 'adblock-blocker' ), $BBCS->time, true );
$wp_scripts->add( 'bbcs-bbidentfunc', esc_url_raw( $BBCS->botblockerUrl . 'public/js/bbidentfunc.js?ver=' . $BBCS->time ), array( 'bbcs-detection-utils' ), $BBCS->time, true );

$wp_scripts->enqueue( 'bbcs-inline' );
$wp_scripts->enqueue( 'adblock-blocker' );
$wp_scripts->enqueue( 'bbcs-detection-utils' );
$wp_scripts->enqueue( 'bbcs-bbidentfunc' );
if ( $BBCS->settings->recaptcha_check == 1 && ! empty( $BBCS->settings->recaptcha_key3 ) ) {
	$wp_scripts->add( 'bbcs-google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_html( $BBCS->settings->recaptcha_key3 ), array(), null, true );
	$wp_scripts->enqueue( 'bbcs-google-recaptcha' );
}

if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) {
	$wp_scripts->add( 'bbcs-circuit-breaker', esc_url_raw( $BBCS->botblockerUrl . 'public/js/bbcs-circuit-breaker.js?ver=' . $BBCS->version ), array(), null, true );
	$wp_scripts->enqueue( 'bbcs-circuit-breaker' );
}

$wp_scripts->do_items( $wp_scripts->queue );



$botblocker_check_function_name = 'f' . md5( $BBCS->ip . $BBCS->time );

$botblocker_output    = array();
$botblocker_parse_url = wp_parse_url( $BBCS->uri );
if ( $BBCS->settings->utm_referrer == 1 and $BBCS->referer != '' ) {

	if ( isset( $botblocker_parse_url['query'] ) ) {
		parse_str( $botblocker_parse_url['query'], $botblocker_output );
	}

	// REVIEWER NOTE: This is not form input but a read-only tracking parameter for analytics. No user-submitted form is being processed.
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['utm_referrer'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$botblocker_output['utm_referrer'] = trim( wp_strip_all_tags( wp_unslash( $_GET['utm_referrer'] ) ) );
	} else {
		$botblocker_output['utm_referrer'] = $BBCS->referer;
	}

	if ( ! isset( $botblocker_parse_url['path'] ) || $botblocker_parse_url['path'] == '' ) {
		$botblocker_parse_url['path'] = '/';
	}

	$botblocker_redirect_url = $botblocker_parse_url['path'] . '?' . http_build_query( $botblocker_output );
} else {
	$botblocker_redirect_url = $BBCS->uri;
}

?><script nonce="<?php echo esc_attr( $BBCS->csp_nonce ); ?>">

	var bbcsDebugEnabled = <?php echo ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG === true ) ? 'true' : 'false'; ?>;

	function bbcsDebugLog(...args) {
		if (bbcsDebugEnabled) {
			console.log('[BBCS DEBUG]', ...args);
		}
	}
	
	function bbcsDebugWarn(...args) {
		if (bbcsDebugEnabled) {
			console.warn('[BBCS DEBUG]', ...args);
		}
	}
	
	function bbcsDebugError(...args) {
		if (bbcsDebugEnabled) {
			console.error('[BBCS DEBUG]', ...args);
		}
	}
 
	bbcsDebugLog('<?php echo esc_js( BOTBLOCKER_SHORT_NAME ); ?> v.<?php echo esc_js( $BBCS->version ); ?>');

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

	function bbcs_handleDdosResponse(responseText, s, d, x) {
		bbcs_extractDdosCookie(responseText);
		if (bbcsDdosRetryCount < bbcsDdosMaxRetries) {
			bbcsDdosRetryCount++;
			<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
			bbcsCheckUI.showRetry(bbcsDdosRetryCount);
			<?php endif; ?>
			var delay = bbcsDdosRetryCount * 1000;
			bbcsDebugLog('DDoS retry ' + bbcsDdosRetryCount + '/' + bbcsDdosMaxRetries + ' in ' + delay + 'ms');
			setTimeout(function() {
				<?php echo esc_js( $botblocker_check_function_name ); ?>(s, d, x);
			}, delay);
			return true;
		}
		<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
		bbcsCheckUI.showCAPTCHA();
		<?php endif; ?>
		bbcsDebugLog('DDoS retries exhausted, redirecting to page');
		window.location.href = "<?php echo esc_js( esc_url_raw( $botblocker_redirect_url ) ); ?>";
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

	function bbcs_getDetectionParam() {
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

	function areCookiesEnabled() {
	var cookieEnabled = navigator.cookieEnabled;
	if (cookieEnabled === undefined) {
		document.cookie = "testcookie";
		cookieEnabled = document.cookie.indexOf("testcookie") != -1;
	}
	return cookieEnabled;
	}

	if (!areCookiesEnabled()) {
	var cookieoff = 1;
	} else {
	var cookieoff = 0;
	}

	if (window.location.hostname !== window.atob("<?php echo esc_js( base64_encode( $BBCS->host ) ); ?>") && window.location.hostname !== window.atob("<?php echo esc_js( base64_encode( strstr( $BBCS->host, ':', true ) ?: $BBCS->host ) ); ?>")) {
	window.location = window.atob("<?php echo esc_js( base64_encode( esc_url_raw( $BBCS->scheme . '://' . $BBCS->host . $BBCS->uri ) ) ); ?>");
	throw "stop";
	}

	function clean_and_decode_base64_to_utf8(str) {
	str = str.replace(/\s/g, '');
	return decodeURIComponent(escape(window.atob(str)));
	}

	document.getElementById("content").innerHTML = "<?php echo esc_js( __( 'Loading...', 'botblocker-security' ) ); ?>";

	function handleWorkerSignal() {
	return new Promise(function(resolve) {
		<?php if ( $BBCS->settings->recaptcha_check == 1 && ! empty( $BBCS->settings->recaptcha_key3 ) ) { ?>
		grecaptcha.ready(function() {
			grecaptcha.execute('<?php echo esc_js( $BBCS->settings->recaptcha_key3 ); ?>', {
			action: '<?php echo esc_js( preg_replace( '/[^A-Za-z0-9\/_]/', '_', $BBCS->country ) ); ?>'
			}).then(function(token) {
			rct = token; 
			resolve('HWS');
			});
		});
		<?php } else { ?>
		rct = ''; 
		resolve('HWS');
		<?php } ?>
	});
	}

	function dispatchServiceEvent() {
	return new Promise(function(resolve) {
		<?php
		$bbcs_use_ipv6_echo = ( $BBCS->ip_version == 6 )
			&& ( ! class_exists( 'BotBlockerAsnDb' ) || ! BotBlockerAsnDb::isPresent() );
		?>
		<?php if ( $bbcs_use_ipv6_echo ) { ?>
		var GLOBUS_studio_API_request = new XMLHttpRequest();
		// Constant URL → not user input. Printed inside a JS string.
		// Escaping HTML is irrelevant; value is validated via esc_url_raw().
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		GLOBUS_studio_API_request.open('GET', '<?php echo esc_url_raw( BOTBLOCKER_API_GS_IPV6 ); ?>', true);
		GLOBUS_studio_API_request.setRequestHeader("Content-Type", "application/json");
		GLOBUS_studio_API_request.timeout = 5000; 
		GLOBUS_studio_API_request.onload = function() {
			if (GLOBUS_studio_API_request.readyState === 4 && GLOBUS_studio_API_request.status === 200) {
			var json = JSON.parse(GLOBUS_studio_API_request.responseText);
			ipv4 = json.ip;
			ipdbc = '<?php echo esc_js( BOTBLOCKER_EMPTY ); ?>';
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
		<?php } else { ?>
		ipv4 = '';
		ipdbc = '';
		resolve('Result DSE');
		<?php } ?>
	});
	}

	function initProcessHandler(result1, result2) {
	bbcs_detectionParam = bbcs_getDetectionParam();
	data = 'test=<?php echo esc_js( hash( 'sha256', $BBCS->useragent . $BBCS->ip . $BBCS->time . $BBCS->country . $BBCS->ptr . $BBCS->settings->salt ) ); ?>&h1=<?php echo esc_js( hash( 'sha256', $BBCS->settings->cloud_api_email . $BBCS->settings->cloud_api_pass . $BBCS->host . $BBCS->useragent . $BBCS->ip . $BBCS->time ) ); ?>&date=<?php echo esc_js( $BBCS->time ); ?>&hdc=<?php echo esc_js( $BBCS->hosting ); ?>&a=' + adb_var + '&country=<?php echo esc_js( $BBCS->country ); ?>&ip=<?php echo esc_js( $BBCS->ip ); ?>&version=<?php echo esc_js( $BBCS->version ); ?>&cid=<?php echo esc_js( $BBCS->cid ); ?>&ptr=<?php echo esc_js( $BBCS->ptr ); ?>&w=' + screen.width + '&h=' + screen.height + '&cw=' + document.documentElement.clientWidth + '&ch=' + document.documentElement.clientHeight + '&co=' + screen.colorDepth + '&pi=' + screen.pixelDepth + '&ref=' + encodeURIComponent(document.referrer) + '&accept=<?php echo urlencode( $BBCS->http_accept ); ?>&tz=' + Intl.DateTimeFormat().resolvedOptions().timeZone + '&ipdbc=' + ipdbc + '&ipv4=' + ipv4 + '&rct=' + rct + '&cookieoff=' + cookieoff + bbcs_detectionParam;
	<?php echo esc_js( $botblocker_check_function_name ); ?>('botblocker-security', data, '');
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
	<?php
		require_once $BBCS->dirs['public'] . 'class-botblocker-captcha-renderer-full.php';
		$bbcs_renderer = new BotBlockerCaptchaRendererFull( $botblocker_check_function_name );

		/**
		* REVIEWER NOTE:
		* $renderer->render() returns a fully sanitized HTML string. All dynamic values are escaped
		* with esc_html()/esc_attr()/esc_url() or passed through wp_kses with an allowlist.
		* The final echo must remain unescaped because additional escaping would strip or corrupt
		* required <script>/<style> sections. Therefore, we suppress
		* WordPress.Security.EscapeOutput.OutputNotEscaped on the exact echo line.
		*/

		// The renderer returns a pre-sanitized string (esc_html/esc_attr/esc_url/wp_kses whitelist).
		// We need the raw HTML/JS intact; extra escaping would break it.
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $bbcs_renderer->render();

		$bbcs_ct = $bbcs_renderer->getChallengeToken();
	?>
	<?php if ( ! empty( $bbcs_ct ) ) : ?>
	window.bbcs_challenge_token = "<?php echo esc_js( $bbcs_ct ); ?>";
	<?php endif; ?>
	}

	var bbcsVerifyUrl = '<?php echo esc_url( home_url( '/bbcs-verify/' ) ); ?>';
	var bbcsAjaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

	function <?php echo esc_js( $botblocker_check_function_name ); ?>(s, d, x, ajaxEndpoint) {
	<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
	if (typeof bbcsCircuitBreaker !== 'undefined' && bbcsCircuitBreaker.isOpen()) {
		return;
	}
	<?php endif; ?>
	if (!ajaxEndpoint) ajaxEndpoint = bbcsAjaxUrl;
	<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
	if (bbcsDdosRetryCount === 0) {
		bbcsCheckUI.showVerifying();
	}
	<?php else : ?>
	document.getElementById("content").innerHTML = "<?php echo esc_js( __( 'Loading...', 'botblocker-security' ) ); ?>";
	<?php endif; ?>
	
	var data = new FormData();
	data.append('action', 'bbcs_botblocker_check');
	data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'botblocker_nonce' ) ); ?>');
	data.append('<?php echo esc_js( $BBCS->select_request_mode ); ?>', s);
	data.append('xxx', x);
	data.append('rowid', '<?php echo esc_js( $BBCS->rule_record_id ); ?>');
	data.append('from_suspect', '<?php echo esc_js( $BBCS->suspect_status ); ?>');

	data.append('suspect_reason', '<?php echo esc_js( $BBCS->reason_for_action ); ?>');
	data.append('check_result', '<?php echo esc_js( $BBCS->result_of_action ); ?>');
	if (typeof bbcs_challenge_token !== 'undefined' && bbcs_challenge_token) {
		data.append('challenge_token', bbcs_challenge_token);
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

					<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
					if (typeof obj.bbcs_sig !== 'string') {
						if (typeof bbcsCircuitBreaker !== 'undefined') {
							bbcsCircuitBreaker.recordFailure();
						}
					} else {
						delete obj.bbcs_sig;
					}

					if (typeof obj.cookie !== 'string') {
						var backupCookie = xhr.getResponseHeader('X-BBCS-<?php echo esc_js( $BBCS->uid ); ?>');
						if (backupCookie) {
							obj.cookie = backupCookie;
						}
					}
					<?php endif; ?>

					if (typeof(obj.cookie) == "string") {
						<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
						bbcsCheckUI.showSuccess();
						if (typeof bbcsCircuitBreaker !== 'undefined') {
							bbcsCircuitBreaker.failures = 0;
						}
						<?php endif; ?>
						var d = new Date();
						d.setTime(d.getTime() + (<?php echo (int) $BBCS->settings->cookie_lifetime; ?> * 1000)); 
						var expires = "expires=" + d.toUTCString();
						try { sessionStorage.removeItem('bbcsMode8Retries'); } catch(e) {}
						document.cookie = "<?php echo esc_js( $BBCS->uid ); ?>=" + obj.cookie + "-<?php echo esc_js( $BBCS->time ); ?>; SameSite=<?php echo esc_js( $bbcs_effective_samesite ); ?>;<?php echo ( ( $bbcs_effective_samesite === 'None' ) ? ' Secure' : '' ); ?>; " + expires + "; path=/;";
						<?php if ( (int) $BBCS->settings->bbcs_captcha_mode === BOTBLOCKER_CAPTCHA_MODE_SILENT ) : ?>
						document.getElementById("content").innerHTML = "<?php echo esc_js( __( 'Access approved. Redirecting...', 'botblocker-security' ) ); ?>";
						setTimeout(function() { window.location.href = "<?php echo esc_js( esc_url_raw( $botblocker_redirect_url ) ); ?>"; }, 0);
						<?php else : ?>
						document.getElementById("content").innerHTML = "<?php echo esc_js( __( 'Loading...', 'botblocker-security' ) ); ?>";
						window.location.href = "<?php echo esc_js( esc_url_raw( $botblocker_redirect_url ) ); ?>";
						<?php endif; ?>
					} else {
						botblocker_captcha_render(); 
						bbcsDebugLog('Bad bot detected');
					}

					if (typeof(obj.error) == "string") {
						<?php if ( defined( 'BOTBLOCKER_JS_ADMIN' ) && BOTBLOCKER_JS_ADMIN == true ) { ?>
							if (obj.error == "CiberSecure Account Not Found" 
							|| obj.error == "This domain don't have a valid license" 
							|| obj.error == "Subscription Expired" 
							|| obj.error == "This domain is not registered or not active"
							|| obj.error == "<?php echo esc_js( $BBCS->js_error_message ); ?>") {
								const ErrorMsg = document.createElement('div');
								ErrorMsg.innerHTML = '<h1 style="text-align:center; color:red;">' + obj.error + '</h1>';
								document.body.insertAdjacentElement('afterbegin', ErrorMsg);
								document.getElementById("content").style.visibility = "hidden";
								document.getElementById("content").innerHTML = '';
							} else if (obj.error == "Cookies disabled") {
								document.getElementById("content").innerHTML = "<h2 style=\"text-align:center; color:red;\"><?php echo esc_js( __( 'Cookies are disabled in your browser. Enable cookies to continue.', 'botblocker-security' ) ); ?></h2>";
							}
						<?php } ?>
						if (obj.error == "timeout" || obj.error == "Wrong Click") {
							<?php if ( (int) $BBCS->settings->bbcs_captcha_mode === BOTBLOCKER_CAPTCHA_MODE_SILENT ) : ?>
							var r = 0;
							try { r = parseInt(sessionStorage.getItem('bbcsMode8Retries') || '0', 10); } catch(e) {}
							if (r < 2) {
								try { sessionStorage.setItem('bbcsMode8Retries', String(r + 1)); } catch(e) {}
								window.location.reload();
								return;
							}
							<?php endif; ?>
							document.getElementById("content").innerHTML = "<?php echo esc_js( __( 'Loading...', 'botblocker-security' ) ); ?>";
							window.location.href = "<?php echo esc_js( esc_url_raw( $botblocker_redirect_url ) ); ?>";
						}
					}
				} else {
					bbcsDebugWarn('Empty or invalid response from server.');
				}
			} catch (e) {
				bbcsDebugError('Error parsing JSON:', e);
				bbcsDebugLog('Response text received:', xhr.responseText);
				if (bbcs_isDdosResponse(xhr.responseText)) {
					if (bbcs_handleDdosResponse(xhr.responseText, s, d, x)) return;
				}
				<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
				if (typeof bbcsCircuitBreaker !== 'undefined') {
					bbcsCircuitBreaker.recordFailure();
				}
				bbcsCheckUI.showCAPTCHA();
				<?php endif; ?>
				botblocker_captcha_render(); 
			}

		} else {
			bbcsDebugLog('Error: ' + xhr.status);
			if (ajaxEndpoint !== bbcsVerifyUrl && bbcsVerifyUrl) {
				bbcsDebugLog('admin-ajax.php returned ' + xhr.status + ', trying verify endpoint');
				<?php echo esc_js( $botblocker_check_function_name ); ?>(s, d, x, bbcsVerifyUrl);
				return;
			}
			if (bbcs_isDdosResponse(xhr.responseText)) {
				if (bbcs_handleDdosResponse(xhr.responseText, s, d, x)) return;
			}
			<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
			if (typeof bbcsCircuitBreaker !== 'undefined') {
				bbcsCircuitBreaker.recordFailure();
			}
			bbcsCheckUI.showCAPTCHA();
			<?php endif; ?>
			botblocker_captcha_render(); 
		}
	};

	xhr.ontimeout = function() {
		bbcsDebugLog('timeout');
		if (ajaxEndpoint !== bbcsVerifyUrl && bbcsVerifyUrl) {
			bbcsDebugLog('admin-ajax.php timeout, trying verify endpoint');
			<?php echo esc_js( $botblocker_check_function_name ); ?>(s, d, x, bbcsVerifyUrl);
			return;
		}
		if (bbcsDdosRetryCount > 0) {
			bbcsDebugLog('Timeout during DDoS retry, redirecting');
			window.location.href = "<?php echo esc_js( esc_url_raw( $botblocker_redirect_url ) ); ?>";
			return;
		}
		<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
		if (typeof bbcsCircuitBreaker !== 'undefined') {
			bbcsCircuitBreaker.recordFailure();
		}
		bbcsCheckUI.showCAPTCHA();
		<?php endif; ?>
		botblocker_captcha_render();
	};

	xhr.onerror = function() {
		bbcsDebugLog('error');
		if (ajaxEndpoint !== bbcsVerifyUrl && bbcsVerifyUrl) {
			bbcsDebugLog('admin-ajax.php error, trying verify endpoint');
			<?php echo esc_js( $botblocker_check_function_name ); ?>(s, d, x, bbcsVerifyUrl);
			return;
		}
		if (bbcsDdosRetryCount > 0) {
			bbcsDebugLog('Error during DDoS retry, redirecting');
			window.location.href = "<?php echo esc_js( esc_url_raw( $botblocker_redirect_url ) ); ?>";
			return;
		}
		<?php if ( $BBCS->settings->bbcs_ddos_resilience == 1 ) : ?>
		if (typeof bbcsCircuitBreaker !== 'undefined') {
			bbcsCircuitBreaker.recordFailure();
		}
		bbcsCheckUI.showCAPTCHA();
		<?php endif; ?>
		botblocker_captcha_render();
	};

	xhr.send(data);
}

</script>

<noscript>
	<h2 style="text-align:center; color:red;">
	<?php echo esc_html( __( 'JavaScript is disabled in your browser. Enable JavaScript to continue.', 'botblocker-security' ) ); ?>
	</h2>
</noscript>
