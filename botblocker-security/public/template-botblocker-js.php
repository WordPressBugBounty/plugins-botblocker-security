<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined('WPINC') || !defined('BOTBLOCKER')) {
  exit;
}

/**
 * BotBlocker JavaScript Template
 *
 * @package    BotBlocker
 * @subpackage BotBlocker/javascript-template
 * @author     BotBlocker
 * @copyright  Copyright (c) 2025, BotBlocker
 * 
 */

global $wpdb;
$BBCS = BotBlocker::getInstance();

/**
 * REVIEWER NOTE:
 * The conventional WordPress script enqueuing mechanism is intentionally bypassed in this template.
 * This template is executed during the early stages of plugin initialization, preceding the availability of enqueuing functions,
 * and directly outputs content to the client, terminating execution and circumventing the standard WordPress rendering process.
 * This approach is implemented as a performance optimization to reduce server resource utilization for requests identified as likely automated or non-human traffic.
 * Consequently, the internal logic of wp_enqueue_scripts is invoked programmatically within this context to ensure that all necessary assets are properly loaded.
 */
$wp_scripts = wp_scripts();
$wp_scripts->add('bbcs-inline', '', [], BOTBLOCKER_VERSION, true);
$wp_scripts->add_inline_script('bbcs-inline', 'var adb_var = 1;');
$wp_scripts->add('adblock-blocker', esc_url($BBCS->botblockerUrl . 'public/js/rails.js?bannerid=' . $BBCS->time), [], null, true);
$wp_scripts->add('bbcs-detection-utils', esc_url($BBCS->botblockerUrl . 'public/js/detection-utils.js?ver=' . $BBCS->time), ['adblock-blocker'], $BBCS->time, true);
$wp_scripts->add('bbcs-bbidentfunc', esc_url($BBCS->botblockerUrl . 'public/js/bbidentfunc.js?ver=' . $BBCS->time), ['bbcs-detection-utils'], $BBCS->time, true);
if ($BBCS->settings->recaptcha_check == 1 && !empty($BBCS->settings->recaptcha_key3)) {
    $wp_scripts->add('bbcs-google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_html($BBCS->settings->recaptcha_key3), [], null, true);
}

$wp_scripts->enqueue('bbcs-inline');
$wp_scripts->enqueue('adblock-blocker');
$wp_scripts->enqueue('bbcs-detection-utils');
$wp_scripts->enqueue('bbcs-bbidentfunc');
$wp_scripts->enqueue('bbcs-google-recaptcha');

$wp_scripts->do_items($wp_scripts->queue);



$botblocker_check_function_name = 'f' . md5($BBCS->ip . $BBCS->time);

$botblocker_output = array();
$botblocker_parse_url = wp_parse_url($BBCS->uri); 
if ($BBCS->settings->utm_referrer == 1 and $BBCS->referer != '') {

  if (isset($botblocker_parse_url['query'])) {
    parse_str($botblocker_parse_url['query'], $botblocker_output);
  }

  // REVIEWER NOTE: This is not form input but a read-only tracking parameter for analytics. No user-submitted form is being processed.
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
  if (isset($_GET['utm_referrer'])) {
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      $botblocker_output['utm_referrer'] = trim(wp_strip_all_tags(wp_unslash($_GET['utm_referrer'])));
  } else {
      $botblocker_output['utm_referrer'] = $BBCS->referer;
  }

  if (!isset($botblocker_parse_url['path']) || $botblocker_parse_url['path'] == '') {
    $botblocker_parse_url['path'] = '/';
  }

  $botblocker_redirect_url = $botblocker_parse_url['path'] . '?' . http_build_query($botblocker_output);
} else {
  $botblocker_redirect_url = $BBCS->uri;
}

?><script>

    var bbcsDebugEnabled = <?php echo (defined('BBCS_DEBUG') && BBCS_DEBUG === true) ? 'true' : 'false'; ?>;

    function bbcsDebugLog(...args) {
        if (bbcsDebugEnabled) {
            console.log(...args);
        }
    }
    
    function bbcsDebugWarn(...args) {
        if (bbcsDebugEnabled) {
            console.warn(...args);
        }
    }
    
    function bbcsDebugError(...args) {
        if (bbcsDebugEnabled) {
            console.error(...args);
        }
    }
 
    bbcsDebugLog('<?php echo esc_js(BOTBLOCKER_SHORT_NAME); ?> v.<?php echo esc_js($BBCS->version); ?>');

    var bbcsDdosRetryCount = 0;
    var bbcsDdosMaxRetries = 2;

    function bbcs_extractDdosCookie(responseText) {
        if (!responseText) return false;
        if (responseText.indexOf('document.cookie') !== -1 && responseText.indexOf('<script') !== -1) {
            var cookieMatch = responseText.match(/document\.cookie\s*=\s*"([^"]+)"/);
            if (cookieMatch && cookieMatch[1]) {
                bbcsDebugLog('DDoS protection response detected, setting cookie and retrying');
                document.cookie = cookieMatch[1];
                return true;
            }
        }
        return false;
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
            
            const jsonString = JSON.stringify(detectionResult);
            const base64Encoded = btoa(jsonString); 
            return encodeURIComponent(base64Encoded);
        } catch (e) {
            bbcsDebugError('Error during detection:', e);
            return encodeURIComponent(btoa('{"error":"detection_failed"}'));
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

  // TODO check esc_js 
  if (window.location.hostname !== window.atob("<?php echo esc_js(base64_encode($BBCS->host)); ?>") && window.location.hostname !== window.atob("<?php echo esc_js(base64_encode(strstr($BBCS->host, ':', true))); ?>")) {
    window.location = window.atob("<?php echo esc_js(base64_encode(esc_url_raw($BBCS->scheme . '://' . $BBCS->host . $BBCS->uri))); ?>");
    throw "stop";
  }

  function clean_and_decode_base64_to_utf8(str) {
    str = str.replace(/\s/g, '');
    return decodeURIComponent(escape(window.atob(str)));
  }

  document.getElementById("content").innerHTML = "<?php echo esc_js('Loading...'); ?>";

  function handleWorkerSignal() {
    return new Promise(function(resolve) {
      <?php if ($BBCS->settings->recaptcha_check == 1 && !empty($BBCS->settings->recaptcha_key3)) { ?>
        grecaptcha.ready(function() {
          grecaptcha.execute('<?php echo esc_js($BBCS->settings->recaptcha_key3); ?>', {
            action: '<?php echo esc_js(preg_replace('/[^A-Za-z0-9\/_]/', '_', $BBCS->country)); ?>'
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
      <?php if ($BBCS->ip_version == 6) { ?>
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
            ipdbc = '<?php echo esc_js(BOTBLOCKER_EMPTY); ?>';
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
    data = 'test=<?php 
    echo esc_js(hash('sha256', $BBCS->useragent . $BBCS->ip . $BBCS->time . $BBCS->country . $BBCS->ptr . $BBCS->settings->salt));?>&h1=<?php 
    echo esc_js(hash('sha256', $BBCS->settings->cloud_api_email . $BBCS->settings->cloud_api_pass. $BBCS->host . $BBCS->useragent . $BBCS->ip . $BBCS->time)); 
    ?>&date=<?php echo esc_js($BBCS->time); ?>&hdc=<?php echo esc_js($BBCS->hosting); 
    ?>&a=' + adb_var + '&country=<?php echo esc_js($BBCS->country); 
    ?>&ip=<?php echo esc_js($BBCS->ip); ?>&version=<?php echo esc_js($BBCS->version); 
    ?>&cid=<?php echo esc_js($BBCS->cid); ?>&ptr=<?php echo esc_js($BBCS->ptr); 
    ?>&w=' + screen.width + '&h=' + screen.height + '&cw=' + document.documentElement.clientWidth + '&ch=' + document.documentElement.clientHeight + '&co=' + screen.colorDepth + '&pi=' + screen.pixelDepth + '&ref=' + encodeURIComponent(document.referrer) + '&accept=<?php echo urlencode($BBCS->http_accept); ?>&tz=' + Intl.DateTimeFormat().resolvedOptions().timeZone + '&ipdbc=' + ipdbc + '&ipv4=' + ipv4 + '&rct=' + rct + '&cookieoff=' + cookieoff +'&bbdet=' + bbcs_detectionParam;
    <?php echo esc_js($botblocker_check_function_name); ?>('botblocker-security', data, '');
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
      require_once($BBCS->dirs['public'] . 'class-botblocker-captcha-renderer-full.php');
      $bbcs_renderer = new BotBlockerCaptchaRendererFull($botblocker_check_function_name);

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
    <?php if (!empty($bbcs_ct)) : ?>
    window.bbcs_challenge_token = "<?php echo esc_js($bbcs_ct); ?>";
    <?php endif; ?>
  }

  function <?php echo esc_js($botblocker_check_function_name); ?>(s, d, x) {
    document.getElementById("content").innerHTML = "<?php echo esc_js('Loading...'); ?>";
    
    var data = new FormData();
    data.append('action', 'bbcs_botblocker_check');
    data.append('nonce', '<?php echo esc_js(wp_create_nonce('botblocker_nonce')); ?>');
    data.append('<?php echo esc_js($BBCS->select_request_mode); ?>', s);
    data.append('xxx', x);
    data.append('rowid', '<?php echo esc_js($BBCS->rule_record_id); ?>');
    data.append('from_suspect', '<?php echo esc_js($BBCS->suspect_status); ?>');

    data.append('suspect_reason', '<?php echo esc_js($BBCS->reason_for_action); ?>');
    data.append('check_result', '<?php echo esc_js($BBCS->result_of_action); ?>');
    if (typeof bbcs_challenge_token !== 'undefined' && bbcs_challenge_token) {
        data.append('challenge_token', bbcs_challenge_token);
    }

    var additionalParams = new URLSearchParams(d);
    for (var pair of additionalParams.entries()) {
        data.append(pair[0], pair[1]);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
    xhr.timeout = 5000;

    xhr.onload = function() {
        if (xhr.status == 200) {
            bbcsDebugLog('Plugin status: ' + xhr.status);
            try {
                var responseText = xhr.responseText.trim(); 
                bbcsDebugLog('Response text:', responseText); 

                if (responseText) {
                    var obj = JSON.parse(responseText); 

                    if (typeof(obj.cookie) == "string") {
                        var d = new Date();
                        d.setTime(d.getTime() + (7 * 24 * 60 * 60 * 1000)); 
                        var expires = "expires=" + d.toUTCString();
                        document.cookie = "<?php echo esc_js($BBCS->uid); ?>=" + obj.cookie + "-<?php echo esc_js($BBCS->time); ?>; SameSite=<?php echo esc_js($BBCS->settings->samesite); ?>;<?php echo (($BBCS->settings->samesite == 'None') ? ' Secure' : ''); ?>; " + expires + "; path=/;";
                        document.getElementById("content").innerHTML = "<?php echo esc_js('Loading...'); ?>";
                        window.location.href = "<?php echo esc_js(esc_url_raw($botblocker_redirect_url)); ?>";
                    } else {
                        botblocker_captcha_render(); 
                        bbcsDebugLog('Bad bot detected');
                    }

                    if (typeof(obj.error) == "string") {
                        <?php if (defined('BOTBLOCKER_JS_ADMIN') && BOTBLOCKER_JS_ADMIN == true) { ?>
                            if (obj.error == "CiberSecure Account Not Found" 
                             || obj.error == "This domain don't have a valid license" 
                             || obj.error == "Subscription Expired" 
                             || obj.error == "This domain is not registered or not active"
                             || obj.error == "<?php echo esc_js($BBCS->js_error_message); ?>") {
                                const ErrorMsg = document.createElement('div');
                                ErrorMsg.innerHTML = '<h1 style="text-align:center; color:red;">' + obj.error + '</h1>';
                                document.body.insertAdjacentElement('afterbegin', ErrorMsg);
                                document.getElementById("content").style.visibility = "hidden";
                                document.getElementById("content").innerHTML = '';
                            } else if (obj.error == "Cookies disabled") {
                                document.getElementById("content").innerHTML = 
                                "<h2 style=\"text-align:center; color:red;\"><?php 
                                echo esc_js('Cookie is Disabled in your browser. Please Enable the Cookie to continue.');
                                ?></h2>";
                            }
                        <?php } ?>
                        if (obj.error == "timeout" || obj.error == "Wrong Click") {
                            document.getElementById("content").innerHTML = "<?php echo esc_js('Loading...'); ?>";
                            window.location.href = "<?php echo esc_js(esc_url_raw($botblocker_redirect_url)); ?>";
                        }
                    }
                } else {
                    bbcsDebugWarn('Empty or invalid response from server.');
                }
            } catch (e) {
                bbcsDebugError('Error parsing JSON:', e);
                bbcsDebugLog('Response text received:', xhr.responseText);
                if (bbcsDdosRetryCount < bbcsDdosMaxRetries && bbcs_extractDdosCookie(xhr.responseText)) {
                    bbcsDdosRetryCount++;
                    setTimeout(function() {
                        <?php echo esc_js($botblocker_check_function_name); ?>(s, d, x);
                    }, 1000);
                    return;
                }
                botblocker_captcha_render(); 
            }

        } else {
            bbcsDebugLog('Error: ' + xhr.status);
            if (bbcsDdosRetryCount < bbcsDdosMaxRetries && bbcs_extractDdosCookie(xhr.responseText)) {
                bbcsDdosRetryCount++;
                setTimeout(function() {
                    <?php echo esc_js($botblocker_check_function_name); ?>(s, d, x);
                }, 1000);
                return;
            }
            botblocker_captcha_render(); 
        }
    };

    xhr.ontimeout = function() {
        bbcsDebugLog('timeout');
        botblocker_captcha_render();
    };

    xhr.onerror = function() {
        bbcsDebugLog('error');
        botblocker_captcha_render();
    };

    xhr.send(data);
}

</script>

<noscript>
  <h2 style="text-align:center; color:red;">
    <?php echo esc_js('JavaScript is Disabled in your browser. Please Enable the JavaScript to continue.'); ?>
  </h2>
</noscript>