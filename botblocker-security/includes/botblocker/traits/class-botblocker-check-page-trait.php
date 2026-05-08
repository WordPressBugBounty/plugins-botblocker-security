<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerCheckPageTrait {

    public function render_check_page() {
        if ($this->should_show_check_page === true) {
            $this->template_data_check = array(
                'extra_data' => $this->ip,
                'h1_title' => __('Please turn JavaScript on and reload the page', 'botblocker-security'),
                'h2_title' => __('Verifying your browser', 'botblocker-security'),
                'plugin_name' => __('BotBlocker security plugin', 'botblocker-security')
            );
            $this->prepare_check_js_data();
            $this->prepare_captcha_js_data();
            $this->enqueue_check_assets();
            $this->load_check_template();
            exit;
        }
    }

    private function prepare_captcha_js_data()
    {
        require_once($this->dirs['public'] . 'class-botblocker-captcha-renderer.php');
        $botblocker_check_function_name = $this->js_data['checkFunctionName'];
        $renderer = new BotBlockerCaptchaRenderer($botblocker_check_function_name);

        $this->captcha_data = $renderer->getCaptchaData();
        $this->captcha_data = array_merge(
            $this->captcha_data,
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('botblocker_nonce'),
                'time' => $this->time,
                'selectRequestMode' => $this->select_request_mode,
                'checkFunction' => $this->js_data['checkFunctionName'],
                'data' => 'window.data',
                'autoRender' => false,
                'challengeToken' => $renderer->getChallengeToken(),
            )
        );
    }
    
   private function prepare_check_js_data() {

        $botblocker_parse_url = wp_parse_url($this->uri); 
        $botblocker_output = array();
        
        if ($this->settings->utm_referrer == 1 and $this->referer != '') {
            if (isset($botblocker_parse_url['query'])) {
                parse_str($botblocker_parse_url['query'], $botblocker_output);
            }
            if (isset($_GET['utm_referrer'])) {
                $botblocker_output['utm_referrer'] = trim(wp_strip_all_tags(wp_unslash($_GET['utm_referrer'])));
            } else {
                $botblocker_output['utm_referrer'] = $this->referer;
            }
            if (!isset($botblocker_parse_url['path']) || $botblocker_parse_url['path'] == '') {
                $botblocker_parse_url['path'] = '/';
            }
            $bot_blocker_redirect_url = $botblocker_parse_url['path'] . '?' . http_build_query($botblocker_output);
        } else {
            $bot_blocker_redirect_url = $this->uri;
        }
        
        $botblocker_check_function_name = 'f' . md5($this->ip . $this->time);
        
        $host_no_port = strstr($this->host, ':', true);
        if ($host_no_port === false) {
            $host_no_port = $this->host;
        }
        
        $this->js_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'verifyUrl' => home_url('/bbcs-verify/'),
            'nonce' => wp_create_nonce('botblocker_nonce'),           
            'debugEnabled' => (defined('BBCS_DEBUG') && BBCS_DEBUG === true),
            'version' => $this->version,
            'shortName' => BOTBLOCKER_SHORT_NAME,
            'hostBase64' => base64_encode($this->host),
            'hostNoPortBase64' => base64_encode($host_no_port),
            'redirectUrlBase64' => base64_encode(esc_url_raw($this->scheme . '://' . $this->host . $this->uri)),
            'loadingText' => __('Loading...', 'botblocker-security'),
            'testHash' => hash('sha256', $this->useragent . $this->ip . $this->time . $this->country . $this->ptr . $this->settings->salt),
            'h1Hash' => hash('sha256', $this->settings->cloud_api_email . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->time),
            'time' => $this->time,
            'hosting' => $this->hosting,
            'country' => $this->country,
            'ip' => $this->ip,
            'cid' => $this->cid,
            'ptr' => $this->ptr,
            'httpAccept' => urlencode($this->http_accept),
            'ipVersion' => $this->ip_version,
            'recaptchaEnabled' => ($this->settings->recaptcha_check == 1 && !empty($this->settings->recaptcha_key3)),
            'recaptchaKey3' => $this->settings->recaptcha_key3,
            'apiGsIpv6' => BOTBLOCKER_API_GS_IPV6,
            'emptyValue' => BOTBLOCKER_EMPTY,
            'jsAdminEnabled' => (defined('BOTBLOCKER_JS_ADMIN') && BOTBLOCKER_JS_ADMIN == true),
            'jsErrorMessage' => $this->js_error_message,
            'cookieDisabledText' => __('Cookies are disabled in your browser. Enable cookies to continue.', 'botblocker-security'),
            'jsDisabledText' => __('JavaScript is disabled in your browser. Enable JavaScript to continue.', 'botblocker-security'),
            'redirectUrl' => esc_js(esc_url_raw($bot_blocker_redirect_url)),
            'checkFunctionName' => $botblocker_check_function_name,
            'selectRequestMode' => $this->select_request_mode,
            'ruleRecordId' => $this->rule_record_id,
            'suspectStatus' => $this->suspect_status,
            'reasonForAction' => $this->reason_for_action,
            'resultOfAction' => $this->result_of_action,
            'uid' => $this->uid,
            'samesite' => self::effective_samesite( $this->settings->samesite ),
            'cookieLifetime' => (int) $this->settings->cookie_lifetime,
            'botblockerUrl' => $this->botblockerUrl,
            'railsJsUrl' => esc_url($this->botblockerUrl . 'public/js/rails.js?bannerid=' . $this->time),
            'detectionUtilsUrl' => esc_url($this->botblockerUrl . 'public/js/detection-utils.js?ver=' . $this->time),
            'bbidentfuncUrl' => esc_url($this->botblockerUrl . 'public/js/bbidentfunc.js?ver=' . $this->time),
            'captchaURL' => 'https://www.google.com/recaptcha/api.js?render=' . esc_html($this->settings->recaptcha_key3),
            'silentMode' => ( (int) $this->settings->bbcs_captcha_mode === BOTBLOCKER_CAPTCHA_MODE_SILENT ),
            'approvedText' => __( 'Access approved. Redirecting...', 'botblocker-security' ),
        );
    }

    private function enqueue_check_assets() {
        wp_enqueue_style(
            'bbcs-early-style',
            BOTBLOCKER_URL . 'public/css/template.css',
            array(),
            BOTBLOCKER_VERSION
        );

        $css = $this->create_custom_check_style();
        wp_add_inline_style('bbcs-early-style', $css);

        wp_register_script(
            'bbcs-inline', 
            false, 
            array(), 
            BOTBLOCKER_VERSION, 
            true
        );
        wp_enqueue_script('bbcs-inline');
        wp_add_inline_script('bbcs-inline', 'window.adb_var = 1;');
        
        wp_enqueue_script(
            'adblock-blocker',
            $this->js_data['railsJsUrl'],
            array('bbcs-inline'),
            $this->time,
            true
        );
        
        wp_enqueue_script(
            'bbcs-detection-utils',
            $this->js_data['detectionUtilsUrl'],
            array('adblock-blocker'),
            $this->time,
            true
        );
        
        wp_enqueue_script(
            'bbcs-bbidentfunc',
            $this->js_data['bbidentfuncUrl'],
            array('bbcs-detection-utils'),
            $this->time,
            true
        );

        if ($this->js_data['recaptchaEnabled']) {
            wp_enqueue_script(
                'bbcs-google-recaptcha',
                $this->js_data['captchaURL'],
                array('bbcs-bbidentfunc'),
                $this->time,
                true
            );
        }
        
        $captcha_mode = isset($this->settings->bbcs_captcha_mode) ? (int)$this->settings->bbcs_captcha_mode : 0;

        if ($this->js_data['recaptchaEnabled']) { 
            wp_enqueue_script(
                'bbcs-captcha-main',
                BOTBLOCKER_URL . 'public/captcha-js/captcha.js',
                array('bbcs-google-recaptcha'),
                BOTBLOCKER_VERSION,
                true
            );            
        } else {
            wp_enqueue_script(
                'bbcs-captcha-main',
                BOTBLOCKER_URL . 'public/captcha-js/captcha.js',
                array('bbcs-detection-utils'),
                BOTBLOCKER_VERSION,
                true
            );        
        }

        wp_localize_script(
            'bbcs-captcha-main',
            'bbcsCaptchaData',
            $this->captcha_data
        );

        wp_enqueue_script(
            'bbcs-captcha-mode' . $captcha_mode,
            BOTBLOCKER_URL . 'public/captcha-js/mode' . $captcha_mode . '.js',
            array('bbcs-captcha-main'),
            BOTBLOCKER_VERSION,
            true
        );

        wp_enqueue_script(
            'bbcs-template-script',
            BOTBLOCKER_URL . 'public/js/template.js',
            array('bbcs-captcha-main'),
            BOTBLOCKER_VERSION,
            true
        );

        wp_localize_script(
            'bbcs-template-script',
            'bbcsJsData',
            $this->js_data
        );

    }

    private function create_custom_check_style() {
        $time = $this->time ?? time();
        $success_class = 's' . md5( 'botblocker-btn-success' . $time );
        $color_class   = 's' . md5( 'botblocker-btn-color' . $time );

        $css  = '.' . $success_class . '{';
        $css .= 'border:1px solid transparent;';
        $css .= 'background:#7785ef;';
        $css .= 'color:#ffffff;';
        $css .= 'font-size:16px;';
        $css .= 'line-height:15px;';
        $css .= 'padding:10px 15px;';
        $css .= 'text-decoration:none;';
        $css .= 'text-shadow:none;';
        $css .= 'border-radius:5px;';
        $css .= 'box-shadow:none;';
        $css .= 'transition:0.25s;';
        $css .= 'display:block;';
        $css .= 'margin:0 auto;';
        $css .= 'font-weight:600;';
        $css .= '}';
        $css .= '.' . $success_class . ':hover{background-color:#bfc7ff;}';

        $css .= '.' . $color_class . '{';
        $css .= 'cursor:pointer;';
        $css .= 'padding:14px 14px;';
        $css .= 'text-decoration:none;';
        $css .= 'display:inline-block;';
        $css .= 'width:16px;';
        $css .= 'height:16px;';
        $css .= 'border-radius:80px;';
        $css .= '}';
        $css .= '.' . $color_class . ':hover{width:16px;height:16px;}';

        return $css;
    }

    private function load_check_template() {
        $template_path = BOTBLOCKER_DIR . 'public/templates/check-page.php';
        if (file_exists($template_path)) {
            extract($this->template_data_check);
            include $template_path;
        }
    }    

}    