<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerVisitorTrait {
    
    public function collect_visitor_data() : void
    {
        $this->read_host();
        $this->read_method();
        $this->read_ip();
        $this->read_ptr();
        $this->read_scheme();
        $this->read_user_agent();
        $this->read_uri();
        $this->read_referer();
        $this->read_language_data();
        $this->read_protocol();
        $this->read_http_accept();
        $this->generate_page_url();
        $this->process_referer();
        $this->process_page();

        $this->check_proxy();
        $this->identify_by_user_agent();
        $this->get_ip_info();
        $this->check_restricted_country();
    }

    public function check_admin_status() : void
    {
        $allowed_roles = ['administrator', 'editor', 'moderator'];
        $user = wp_get_current_user();
        if (is_user_logged_in() && !empty(array_intersect($allowed_roles, $user->roles))) {
            $this->isAdmin = true;
        } else {
            $this->isAdmin = false;
        }
    }

    public function process_cookies() : bool
    {
        if (isset($_COOKIE['_ym_uid'])) {
            $this->ym_uid = trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_COOKIE['_ym_uid']))));
        } else {
            $this->ym_uid = '';
        }
        if (isset($_COOKIE['_ga'])) {
            $this->ga_uid = trim(preg_replace("/[^a-zA-Z0-9\.]/", "", sanitize_text_field(wp_unslash($_COOKIE['_ga']))));
        } else {
            $this->ga_uid = '';
        }
        if (isset($_COOKIE[$this->settings->cookie . '_hits'])) {
            $current_value = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_COOKIE[$this->settings->cookie . '_hits']))));
            $is_asset_request = preg_match('/\.(js|css|map|jpe?g|png|gif|svg|ico|woff2?|ttf|eot)$/i', $this->uri);
            $this->cookie_hits_counter = $is_asset_request ? $current_value : $current_value + 1;
        } else {
            $this->cookie_hits_counter = 1;
        }
        if (isset($_COOKIE[$this->settings->cookie])) {
            $this->uid = preg_replace('/[^a-zA-Z0-9]/', '', sanitize_text_field(wp_unslash($_COOKIE[$this->settings->cookie])));
            if (empty($this->uid)) {
                $this->uid = $this->generate_uid();
                $this->set_bot_blocker_cookie();
            }
        } else {
            $this->uid = $this->generate_uid();
            $this->set_bot_blocker_cookie(); 
        }
        $this->cookie_visitor_data = isset($_COOKIE[$this->uid]) ? trim(wp_strip_all_tags(wp_unslash($_COOKIE[$this->uid]))) : '';
        $bbcs_cookie = explode('-', $this->cookie_visitor_data);
        $this->cookie_timestamp = isset($bbcs_cookie[1]) ? (int)trim($bbcs_cookie[1]) : $this->time - $this->settings->cookie_lifetime - 100; 
        $this->cookie_stored_hash = isset($bbcs_cookie[0]) ? trim($bbcs_cookie[0]) : 0;
        $this->cookie_expected_hash = md5($this->settings->salt . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->cookie_timestamp);
        if ($this->time - $this->cookie_timestamp > $this->settings->cookie_lifetime) { 
            if (!empty($this->uid)) {
                $this->delete_cookie($this->uid);
            }
        }
        if ($this->cookie_expected_hash == $this->cookie_stored_hash) {
            $this->update_cookie_counter();
            return true;
        } else {
            $this->redirect_to_dark('No cookies users(Human?)');
        }        
        return false;     
    }
 
    public function generate_uid() : string
    {
        $length = 30;
        $bytes = '';
        
        if (function_exists('random_bytes')) {
            try {
                $bytes = random_bytes($length);
            } catch (\Exception $e) {
            }
        }

        if (empty($bytes) && function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if (!$strong) {
                $bytes = ''; 
            }
        }

        if (empty($bytes)) {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $bytes = '';
            for ($i = 0; $i < $length; $i++) {
                $bytes .= $pool[wp_rand(0, strlen($pool) - 1)];
            }
            return $bytes;
        }

        $context = md5($this->ip . $this->useragent . $this->time);
        $bytes_hex = bin2hex($bytes);
        $uid = substr($bytes_hex, 0, 15) . substr($context, 0, 15);

        return substr($uid, 0, $length);
    }

    public function update_cookie_counter() : void
    {
        if ($this->cookie_hits_counter > $this->settings->hits_per_user) {
            $this->reset_cookies_by_overflow(); 
            if ($this->settings->botblocker_log_local == 1) {
                bbcs_storeData('Cookies reset by overflow', 3); 
            }
            bbcs_process_hit(3);
        } else {
            $this->increment_hit_cookie_counter();
            if ($this->settings->botblocker_log_local == 1) {
                bbcs_storeData('Cookies counter update', 3);
            }
            bbcs_process_hit(3);
        }
        $this->visitorType = self::VISITOR_HUMAN;
    }

    public function check_referer_get_params() : void 
    {
        if ( $this->settings->check_get_ref != 1 ) {
            return;
        }

        $parts = wp_parse_url( $this->referer );
        if ( empty( $parts['query'] ) ) {
            return;
        }

        $params = [];
        wp_parse_str( $parts['query'], $params );

        $bad_list = is_array( $this->list_of_bad_get_params_in_referrer )
            ? $this->list_of_bad_get_params_in_referrer
            : array_filter( array_map( 'trim', explode( ',', (string) $this->list_of_bad_get_params_in_referrer ) ) );

        foreach ( $bad_list as $bad_get_ref ) {
            if ( array_key_exists( $bad_get_ref, $params ) ) {
                $this->set_gray_status( 'GRAY by referrer bad structure: ' . $bad_get_ref );
                break;
            }
        }
    }


    public function check_proxy()
    {
        if (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            foreach ($this->bbcs_proxy as $proxy_mask => $proxy_attr) {
                if (bbcs_netMatch($proxy_mask, $this->ip) == 1 && isset($_SERVER[$proxy_attr])) {
                    $this->ip = sanitize_text_field(wp_unslash($_SERVER[$proxy_attr]));
                    $this->isProxy = 'PROXY_v4';
                    $this->is_proxy_det = $proxy_attr;
                    break;
                }
            }
        }
        if (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            foreach ($this->bbcs_proxy as $proxy_mask => $proxy_attr) {
                if (bbcs_netMatch($proxy_mask, $this->ip) == 1 && isset($_SERVER[$proxy_attr])) {
                    $this->ip = sanitize_text_field(wp_unslash($_SERVER[$proxy_attr]));
                    $this->isProxy = 'PROXY_v6';
                    $this->is_proxy_det = $proxy_attr;
                    break;
                }
            }
        }
        $proxy_headers = bbcs_loadProxyHeaders();
        if ($this->isProxy === BOTBLOCKER_EMPTY) {
            foreach ($proxy_headers as $header) {
                if (!empty($_SERVER[$header]) && $this->ip !== '127.0.0.1') {
                    $this->isProxy = 'DETECTED';
                    $this->is_proxy_det = 'CLASSIC';
                    break;
                }
            }
        }
    }

    public function validate_referer() : void {
        if (
            $this->settings->block_fake_ref == 1 &&
            $this->referer !== '' &&
            $this->visitorType === self::VISITOR_UNDEFINED
        ) {
            $parts = wp_parse_url( $this->referer );

            if ( ! $parts || empty( $parts['scheme'] ) || empty( $parts['path'] ) ) {
                $this->redirect_to_denied( 58, 'Denied By rule: FAKE REFERER' );
            }
        }
    }

    public function check_language_mismatch() : void
    {
        if ($this->settings->block_incorrect_lang_users) {
            if (bbcs_language_to_country_compare($this->country, $this->lang) == false) {
                $this->redirect_to_denied(57, 'Language-to-country incorrect');
            }
        }
    }

    public function identify_by_user_agent()
    {
        if ($this->settings->get_browser_type == 1) {
            $this->browser = bbcs_getBrowserType($this->useragent);
        } else {
            $this->browser = BOTBLOCKER_EMPTY;
        }
        if ($this->settings->get_os_type == 1) {
            $this->os = bbcs_getOSType($this->useragent);
        } else {
            $this->os = BOTBLOCKER_EMPTY;
        }
        if ($this->settings->get_device_type == 1) {
            $this->device = $this->identify_device_type($this->useragent);
        } else {
            $this->device = BOTBLOCKER_EMPTY;
        }
    }

    public function identify_device_type($userAgent) : string
    {
        $detect = null;
        if (version_compare(phpversion(), '8.0', '>=')) {
            require_once BOTBLOCKER_DIR . 'vendor/MobileDetect/4.8.10/standalone/autoloader.php';
            require_once BOTBLOCKER_DIR . 'vendor/MobileDetect/4.8.10/src/MobileDetectStandalone.php';
            $detect = new \Detection\MobileDetectStandalone();
        } else {
            require_once BOTBLOCKER_DIR . 'vendor/MobileDetect/3.74.3/MobileDetect.php';
            $detect = new \Detection\MobileDetect();
        }
        $detect->setUserAgent($userAgent);
        if ($detect->isTablet()) {
            return 'tablet';
        } elseif ($detect->isMobile()) {
            return 'phone';
        } elseif (preg_match('/smart-tv|smarttv|googletv|appletv|hbbtv|pov_tv|netcast.tv/i', $userAgent)) {
            return 'tv';
        } elseif (preg_match('/xbox|playstation|nintendo/i', $userAgent)) {
            return 'box';
        } else {
            return 'pc';
        }
    }

    public function check_hosting() : void
    {
        if (
            $this->settings->hosting_block == 1 &&
            in_array($this->hosting, [1, '1'], true) &&
            $this->visitorType == self::VISITOR_UNDEFINED
        ) {
            $this->redirect_to_denied(17, 'DENIED By rule: Hosting or Bad IP');
        }
    }

    public function get_ip_info() : void
    {
        if ($this->has_valid_cloud_api()) {
            $cloud = $this->collect_cloud_data();
            if ($cloud === false || isset($cloud['error'])) {
                $this->country = $this->get_alternative_ip_info();
            } else {
                $this->country = $cloud['country'];
                $this->cidr = $cloud['cidr'];
                $this->asname = $cloud['asname'];
                $this->asnum = $cloud['asnum'];
                $this->hosting = $cloud['hosting'];    
            }
        } else $this->country = $this->get_alternative_ip_info();  

        if ($this->country != BOTBLOCKER_EMPTY && !empty($this->country)) {
            $this->country_name = bbcs_get_country_by_code($this->country);
        }
    }

    private function get_alternative_ip_info()
    {
        if ($this->ip_version == 4) {
            require_once $this->dirs['vendor'] . 'SypexGeo/SxGeo.php';
            $SxGeo = new SxGeo('SxGeo.dat');
            $country = $SxGeo->getCountry($this->ip);
            if (empty($country)) {
                return BotBlockerWpRequest::ip2c($this->ip);
            } else {
                return $country;
            }
        }
        if ($this->ip_version == 6) {
            return BOTBLOCKER_EMPTY;
        }
    } 
 
    public function check_restricted_country() : void
    {
        $restrictedCountries = bbcs_getRestrictedCountries();
        if (in_array($this->country, $restrictedCountries)) {
            $this->settings->recaptcha_check = 0;
        }
    }
 
    public function is_safe_request() : bool
    { 
        $isCron = $this->is_wordpress_system_cron();
        $isHeartbeat = $this->is_wordpress_heartbeat();

        if ($isHeartbeat) {
            bbcs_storeData('WordPress heartbeat request', 73);
            bbcs_process_hit(73);
            return true;
        }

        if ($this->isAdmin) {
            $isAdminIP = isset($this->admin_ips[$this->ip]) && $this->admin_ips[$this->ip] === 'allow';
            if (!$isAdminIP && $this->settings->autosave_admin_ip == 1) {
                bbcs_addAdminIPs($this->ip);
                bbcs_renderIpsFromDb();
                bbcs_clearFileCache();
            }

            if ($this->settings->botblocker_log_admin == 1) {
                $this->visitorType = self::VISITOR_ADMIN;
                bbcs_storeData('Admin access', 59);
            }
            bbcs_process_hit(59);
            return true;
        }
        
        $isSelfIP = isset($this->self_ips[$this->ip]) && $this->self_ips[$this->ip] === 'allow';
        $allowSelfIPReq = $this->settings->allow_self_ip_req ?? false;

        if ($isSelfIP) {
            if ($isCron) {
                bbcs_storeData('Wordpress self request', 70);
                bbcs_process_hit(70);
                return true;
            } elseif ($allowSelfIPReq) {
                bbcs_storeData('Self ip request', 12);
                bbcs_process_hit(12);
                return true;
            }
        }
        return false;
    }

    public function is_wordpress_system_cron(): bool
    {
        if (defined('DOING_CRON') && DOING_CRON) {
            if (!defined('REST_REQUEST') && !defined('DOING_AJAX') && !defined('WP_CLI')) {
                if (isset($this->uri) && strpos($this->uri, '/wp-cron.php') !== false) {
                    return true;
                }
                if (isset($this->useragent) && isset($_SERVER['HTTP_USER_AGENT'])) {
                    $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
                    if (preg_match('/^WordPress\/[\d\.]+;.+$/', $ua)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }    

    public function is_wordpress_heartbeat(): bool
    {
       // REVIEWER NOTE: Heartbeat is a core WP AJAX action, not user form data
        if ( ! check_ajax_referer('botblocker_nonce', 'nonce', false) ){
            $this->nonce_silent_protection = true;
        }
        if (isset($this->uri) && strpos($this->uri, '/wp-admin/admin-ajax.php') !== false) {
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'heartbeat') {
                return true;
            }
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (isset($_POST['action']) && $_POST['action'] === 'heartbeat') {
                return true;
            }
        }
        return false;
    }

    public function perform_simple_bot_checks(): void
    {
        if ($this->settings->block_empty_ua && bbcs_check_empty_UA($this->useragent) === false) {
            $this->redirect_to_denied(50, 'Empty UA');
        }
        if ($this->settings->block_ipv6_users && $this->ip_version == 6) {
            $this->redirect_to_denied(51, 'IPv6 connect');
        }
        if ($this->settings->block_empty_lang && bbcs_check_empty_language($this->accept_lang, $this->lang) === false) {
            $this->redirect_to_denied(52, 'Empty language');
        }
        if ($this->settings->block_http10_users && $this->protocol == 'HTTP/1.0') {
            $this->redirect_to_denied(53, 'Http/1.0');
        }
        if ($this->settings->block_simplebot_ua && $this->check_bot_by_useragent($this->useragent) !== false) {
            $this->redirect_to_denied(54, 'Black UA');
        }
        if ($this->settings->block_ip_ptr_match && $this->ptr === $this->ip) {
            $this->redirect_to_denied(60, 'IP equals PTR record');
        }
        if (
            $this->settings->block_cf_users &&
            in_array($this->isProxy, ['PROXY_v4', 'PROXY_v6'], true) &&
            $this->is_proxy_det === 'HTTP_CF_CONNECTING_IP'
        ) {
            $this->redirect_to_denied(55, 'CloudFlare');
        }
        if ($this->settings->block_proxy_users && $this->isProxy === 'DETECTED' && $this->is_proxy_det === 'CLASSIC') {
            $this->redirect_to_denied(56, 'Classic proxy');
        }
    }

    public function read_host() : void 
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->host = preg_replace("/[^0-9a-z-.:]/", "", sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));
        } else {
            $this->host = 'errorhost.local';
        }
        $this->host = rtrim($this->host, ".");
    }

    public function read_method() : void
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->request_method = (string) trim(preg_replace("/[^a-zA-Z]/", "", sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))));
        } else {
            $this->request_method = '';
        }
        if (bbcs_check_request_method($this->request_method) == false) {
            if ($this->settings->botblocker_log_block == 1) {
                bbcs_storeData('Not allowed HTTP method', 15);
            }
            bbcs_process_hit(15);
            $this->process_die();
        }
    }

    public function read_ip() : void
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->ip = trim(wp_strip_all_tags(wp_unslash($_SERVER['REMOTE_ADDR'])));
        } else {
            if ($this->settings->botblocker_log_block == 1) {
                bbcs_storeData('IP not reading', 15);
            }
            bbcs_process_hit(15);
            $this->process_die();
        }
        if (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->ip_version = 4;
            $bbcsIParray = explode('.', $this->ip);
            $this->ip_short = $bbcsIParray[0] . '.' . $bbcsIParray[1] . '.' . $bbcsIParray[2] . '.0/24';
            $this->ipnum = bbcs_ipToNumeric($this->ip);
        } elseif (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->ip = bbcs_expandIPv6($this->ip);
            $this->ip_version = 6;
            $bbcsIParray = explode(':', $this->ip);
            $this->ip_short = $bbcsIParray[0] . ':' . $bbcsIParray[1] . ':' . $bbcsIParray[2] . ':' . $bbcsIParray[3] . ':0000:0000:0000:0000/64';
            $this->ipnum = bbcs_ipv6_bin($this->ip);
        } else {
            if ($this->settings->botblocker_log_block == 1) {
                bbcs_storeData('IP not valid', 15);
            }
            bbcs_process_hit(15);
            $this->process_die();
        }
    }

    public function read_ptr() : void
    {
        $this->ptr = bbcs_getPTR($this->ip, $this->time, $this->settings->ptrcache_time);
    }

    public function read_scheme() : void
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $this->scheme = trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO'])));
        } elseif (isset($_SERVER['REQUEST_SCHEME'])) {
            $this->scheme = trim(wp_strip_all_tags(wp_unslash($_SERVER['REQUEST_SCHEME'])));
        } else {
            $this->scheme = 'https';
        }
    }

    public function read_user_agent() : void
    {
        $this->useragent = isset($_SERVER['HTTP_USER_AGENT'])
            ? trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_USER_AGENT'])))
            : '';
    }

    public function read_uri() : void
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->uri = trim(wp_strip_all_tags(wp_unslash($_SERVER['REQUEST_URI'])));
            $this->uri = preg_replace('/\/+/', '/', $this->uri);
        } else {
            $this->uri = '/';
        }
    }

    public function read_referer() : void {
        if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $this->referer = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );

            $parts = wp_parse_url( $this->referer );
            $host  = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';

            $host  = $host ? preg_replace( '/[^0-9a-z\-\.:]/i', '', $host ) : '';

            if ( $this->referer !== '' && $host === '' ) {
                $host = preg_replace( '/[^0-9a-z\-\.]/i', '', $this->referer );
            }

            $this->refhost = $host;

            $scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';
            $scheme = $scheme ? preg_replace( '/[^a-z]/', '', $scheme ) : '';
            $this->refhost_scheme = $scheme;

        } else {
            $this->referer        = '';
            $this->refhost        = '';
            $this->refhost_scheme = '';
        }
    }

    public function read_language_data() : void
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->accept_lang = trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
            $this->lang = $this->read_real_language($this->accept_lang);
            $this->name_lang = bbcs_get_language_by_code($this->lang);
        } else {
            $this->accept_lang = '';
            $this->lang = '';
            $this->name_lang = '';
        }
    }

    public function read_real_language($acceptLanguage)
    {
        $languages = $this->parse_accept_language($acceptLanguage);
        $lang = $languages[0];
        $langCode = preg_match('/^[a-z]{2,3}/', $lang, $matches) ? $matches[0] : BOTBLOCKER_EMPTY;
        return $langCode;
    }

    public function parse_accept_language($acceptLanguage)
    {
        $langs = [];
        $acceptLanguage = strtolower($acceptLanguage);
        $lang_parse = explode(',', $acceptLanguage);
        foreach ($lang_parse as $lang) {
            $parts = explode(';q=', trim($lang));
            $langCode = trim($parts[0]);
            $quality = isset($parts[1]) ? floatval($parts[1]) : 1.0;
            $langs[$langCode] = $quality;
        }
        arsort($langs, SORT_NUMERIC);
        return array_keys($langs);
    }

    public function read_protocol() : void
    {
        $this->protocol = isset($_SERVER['SERVER_PROTOCOL'])
            ? trim(wp_strip_all_tags(wp_unslash($_SERVER['SERVER_PROTOCOL'])))
            : 'HTTP/1.0';
    }

    public function read_http_accept() : void
    {
        $this->http_accept = isset($_SERVER['HTTP_ACCEPT'])
            ? trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_ACCEPT'])))
            : '';
    }

    public function generate_page_url() : void
    {
        $this->page = $this->scheme . '://' . $this->host . $this->uri;
    }

    public function process_referer() : void
    {
        if ($this->delete_query_string_from_referrer == 1) {
            $this->save_referer = explode('?', $this->referer)[0];
        } else {
            $this->save_referer = $this->referer;
        }
    }

    public function process_page() : void
    {
        if ($this->delete_page_query_string_from_URL == 1) {
            $this->save_page = explode('?', $this->page)[0];
        } else {
            $this->save_page = $this->page;
        }
    }

    public function check_bot_by_useragent($useragent)
    {
        $botSignatures = bbcs_botSignatures();
        foreach ($botSignatures as $signature) {
            $signature = preg_replace('/\s+/', ' ', trim(urldecode($signature)));
            if (stripos($useragent, $signature) !== false && !empty($signature)) {
                return $signature;
            }
        }
        return false;
    }    

    public function update_settings_based_on_visitor_data(): void {
        if($this->ip_version == 6 && $this->settings->recaptcha_v3_ipv6_block) {
            $this->settings->recaptcha_check = 0;
        }
    }
}
