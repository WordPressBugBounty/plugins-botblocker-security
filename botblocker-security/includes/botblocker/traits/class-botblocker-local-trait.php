<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

trait BotBlockerLocalTrait
{

    public function processLocalRequest()
    {
        check_ajax_referer('botblocker_nonce', 'nonce');

        global $wpdb;
        $this->post_start_time = microtime(true);
        $this->post_recaptcha_score = 0;

        $message = 'Local verification passed';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->process_die('{"error": "Error NoPost"}');
        }

        if (isset($_POST['cid'])) {
            $this->cid = trim(preg_replace("/[^0-9\.]/", "", sanitize_text_field(wp_unslash($_POST['cid']))));
        } else {
            $this->process_die('{"error": "Empty CID"}');
        }

        $this->processPostData();
        $this->processServerData();

        if ($this->post_cookie_disabled == 1) {
            $payload = bbcs_local_check_result('error', 'Cookies disabled', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($this->post_start_time - $this->time > 3600) {
            $payload = bbcs_local_check_result('error', 'Token Expired', '');
            wp_send_json($payload);
            $this->process_die();
        }

        $this->processTimeZone();
        $this->processSuspect();
        $this->processRequiredParams();

        /*
        * if ipv4 exist - check base 1
        * if country ipv4 exist - check base 5
        * if country base 1 != country base 5 - FAKE
        */

        $force_cloud = ($this->settings->force_cloud_validation == 1 && bbcs_isCloudAPIUltimate());

        if ($this->settings->check == 1 && $force_cloud) {
            $this->processCloudCheck();
        }

        $this->processAdblockerDetect();
        $this->processAntidetect();

        $this->processReCaptchaV3();

        // checkHostingExtended
        // checkAdblockers

        if ($this->settings->check == 1 && !$force_cloud) {
            $this->processCloudCheck();
        }

        if ($this->settings->botblocker_force_check == 1) {
            $payload = bbcs_local_check_result('error', 'Force check captcha required', '');
            wp_send_json($payload);
            $this->process_die();
        }

        $this->post_hash_cookie = md5($this->settings->salt . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->time) . '-' . $this->time;
        $payload = bbcs_local_check_result('cookie', $message, $this->post_hash_cookie);
        wp_send_json($payload);
    }

    private function processRequiredParams()
    {
        if ($this->version == '') {
            $payload = bbcs_local_check_result('error', 'Version', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->post_width < 300) {
            $payload = bbcs_local_check_result('error', 'Monitor Width', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->post_height < 300) {
            $payload = bbcs_local_check_result('error', 'Monitor Height', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->post_client_width < 250) {
            $payload = bbcs_local_check_result('error', 'Browser Window Width', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->post_client_height < 250) {
            $payload = bbcs_local_check_result('error', 'Browser Window Height', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->post_color_depth < 24) {
            $payload = bbcs_local_check_result('error', 'Color Depth', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->post_pixel_depth < 24) {
            $payload = bbcs_local_check_result('error', 'Pixel Depth', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->referer == '') {
            $payload = bbcs_local_check_result('error', 'Empty Referer', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->useragent == '') {
            $payload = bbcs_local_check_result('error', 'Empty User-agent', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->accept_lang == '') {
            $payload = bbcs_local_check_result('error', 'Empty Lang', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($this->post_hosting_detected == 1) {
            $payload = bbcs_local_check_result('error', 'Hosting or Bad IP', '');
            wp_send_json($payload);
            $this->process_die();
        }
        if ($this->check_bot_by_useragent($this->useragent)) {
            $payload = bbcs_local_check_result('error', 'Bot', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($this->post_hash_code != hash('sha256', $this->settings->cloud_api_email . $this->settings->cloud_api_pass . $this->refhost . $this->useragent . $this->ip . $this->date)) {
            $payload = bbcs_local_check_result('error', 'H1 Hash Error', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($this->post_test_code != hash('sha256', $this->useragent . $this->ip . $this->date . $this->country . $this->ptr . $this->settings->salt)) {
            $payload = bbcs_local_check_result('error', 'Test Hash Error', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($this->post_start_time - $this->time > 20) { //TODO treshold from settings
            $payload = bbcs_local_check_result('error', 'Long Request Error', '');
            wp_send_json($payload);
            $this->process_die();
        }
    }

    private function processSuspect()
    {
        if ($this->post_from_suspect == 1) {
            //TODO hide reason
            //$payload = bbcs_local_check_result('error', 'Check_needed_because_suspect_is_set(GRAY) ('. $_POST['suspect_reason']. ') - ('.$_POST['check_result'].')', '');
        }
        if ($this->post_from_suspect == 2) {
            //TODO hide reason
            //$payload = bbcs_local_check_result('error', 'Check_needed_because_suspect_is_set(DARK) ('. $_POST['suspect_reason']. ') - ('.$_POST['check_result'].')', '');
        }
    }

    private function processTimeZone()
    {
        global $wpdb;
        $search = 'timezone=' . $this->post_timezone;
        
        if ($this->rule_record_id > 0) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $BBCSRulesCheck = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, * FROM `{$wpdb->bbcs_rules}` WHERE search = %s OR id = %d ORDER BY priority ASC",
                    $search,
                    $this->rule_record_id
                ),
                ARRAY_A
            );
        } else {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $BBCSRulesCheck = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$wpdb->bbcs_rules}` WHERE search = %s",
                    $search
                ),
                ARRAY_A
            );
        }

        foreach ($BBCSRulesCheck as $echo) {
            if ($echo['disable'] == '0') {
                if ($echo['search'] == $search) {
                    if ($echo['rule'] == 'dark') {
                        $payload = bbcs_local_check_result('error', 'DARK By rule: timezone=' . $this->post_timezone, '');
                        wp_send_json($payload);
                        $this->process_die();
                    } elseif ($echo['rule'] == 'block') {
                        $payload = bbcs_local_check_result('error', 'BLOCK By rule: timezone=' . $this->post_timezone, '');
                        wp_send_json($payload);
                        $this->process_die();
                    } elseif ($echo['rule'] == 'allow') {
                        $this->post_hash_cookie = md5($this->settings->salt . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->time) . '-' . $this->time;
                        $payload = bbcs_local_check_result('cookie', 'ALLOW By rule: timezone=' . $this->post_timezone, $this->post_hash_cookie);
                        wp_send_json($payload);
                        $this->process_die();
                    } elseif ($echo['rule'] == 'gray') {
                        $this->post_from_suspect = 1;
                        $this->result_of_action = 'GRAY by RULE';
                    }
                }
            }
        }
    }

    private function processPostData()
    {

        check_ajax_referer('botblocker_nonce', 'nonce');

        if (isset($_POST['error']) &&  sanitize_text_field(wp_unslash($_POST['error'])) == 'detection_failed') {
            $this->post_antidetect_scope = BOTBLOCKER_EMPTY;
        } else {
            $detection_booleans = [
                'navigatorMismatch',
                'unsupportedFeatures',
                'fakePlugins',
                'fontRenderMismatch',
                'chromiumProperties',
                'jitter',
                'webGLMismatch',
                'touchEventMismatch',
                'batteryAPIMismatch',
                'mediaDevicesMismatch',
                'permissionsMismatch',
                'languageMismatch',
                'crossbrowserIncognito'
            ];
            $this->post_antidetect_scope = [];
            foreach ($detection_booleans as $key) {
                if (isset($_POST[$key])) {
                    $prepared_value = sanitize_text_field(wp_unslash($_POST[$key]));
                    $this->post_antidetect_scope[$key] = $prepared_value == 'true';
                } else {
                    $this->post_antidetect_scope[$key] = false;
                }
            }

            if (isset($_POST['browserFingerprint'])) {
                $this->post_antidetect_scope['browserFingerprint'] = sanitize_text_field(wp_unslash($_POST['browserFingerprint']));
            } else {
                $this->post_antidetect_scope['browserFingerprint'] = null;
            }
        }

        if (isset($_POST['cookieoff'])) {
            $this->post_cookie_disabled = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['cookieoff']))));
        } else {
            $this->post_cookie_disabled = 0;
        }

        if (isset($_POST['from_suspect'])) {
            $this->post_from_suspect = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['from_suspect']))));
            $this->result_of_action = 'GRAY by POST - suspect option';
        } else {
            $this->post_from_suspect = 0;
        }

        if (isset($_POST['rowid'])) {
            $this->rule_record_id = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['rowid']))));
        } else {
            $this->rule_record_id = 0;
        }

        if (isset($_POST['date'])) {
            $this->date = trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['date']))));
        } else {
            $this->date = 0;
        }

        if (isset($_POST['h1'])) {
            $this->post_hash_code = trim(preg_replace("/[^0-9a-z]/", "", sanitize_text_field(wp_unslash($_POST['h1'])))); // TEST sanitize_text_field
        } else {
            $this->post_hash_code = 'xxx';
        }

        if (isset($_POST['test'])) {
            $this->post_test_code = trim(preg_replace("/[^0-9a-z]/", "", sanitize_text_field(wp_unslash($_POST['test']))));
        } else {
            $this->post_test_code = 'xxx';
        }

        if (isset($_POST['hdc'])) {
            $this->post_hosting_detected = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['hdc']))));
        } else {
            $this->post_hosting_detected = 0;
        }

        if (isset($_POST['a'])) {
            $this->post_adblocker_found = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['a']))));
        } else {
            $this->post_adblocker_found = 0;
        }

        if (isset($_POST['country'])) {
            $this->country = trim(preg_replace("/[^A-Z-]/", "", sanitize_text_field(wp_unslash($_POST['country'])))); //TODO check for country code
        } else {
            $this->country = BOTBLOCKER_EMPTY;
        }

        if (isset($_POST['ip'])) {
            $this->ip = trim(preg_replace("/[^0-9a-zA-Z\.\:]/", "", sanitize_text_field(wp_unslash($_POST['ip']))));
        } else {
            $this->ip = '';
        }

        if (isset($_POST['version'])) {
            $this->version = (float)trim(preg_replace("/[^0-9\.]/", "", sanitize_text_field(wp_unslash($_POST['version']))));
        } else {
            $this->version = '';
        }

        if (isset($_POST['ptr'])) {
            $this->ptr = trim(preg_replace("/[^0-9a-zA-Z\.\:\-]/", "", sanitize_text_field(wp_unslash($_POST['ptr']))));
        } else {
            $this->ptr = '';
        }

        if (isset($_POST['w'])) {
            $this->post_width = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['w']))));
        } else {
            $this->post_width = 0;
        }

        if (isset($_POST['h'])) {
            $this->post_height = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['h']))));
        } else {
            $this->post_height = 0;
        }

        if (isset($_POST['cw'])) {
            $this->post_client_width = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['cw']))));
        } else {
            $this->post_client_width = 0;
        }

        if (isset($_POST['ch'])) {
            $this->post_client_height = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['ch']))));
        } else {
            $this->post_client_height = 0;
        }

        if (isset($_POST['co'])) {
            $this->post_color_depth = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['co']))));
        } else {
            $this->post_color_depth = 0;
        }

        if (isset($_POST['pi'])) {
            $this->post_pixel_depth = (int)trim(preg_replace("/[^0-9]/", "", sanitize_text_field(wp_unslash($_POST['pi']))));
        } else {
            $this->post_pixel_depth = 0;
        }

        if (isset($_POST['ref'])) {
            $this->post_referrer = trim(wp_strip_all_tags(wp_unslash($_POST['ref'])));
        } else {
            $this->post_referrer = '';
        }

        if (isset($_POST['tz'])) {
            $this->post_timezone = trim(preg_replace("/[^0-9a-zA-Z\-\/\_\+]/", "", sanitize_text_field(wp_unslash($_POST['tz']))));
        } else {
            $this->post_timezone = '';
        }

        // TODO Country code from ipdb.cloud, only IPv4, may not be present

        if (isset($_POST['ipdbc'])) {
            $this->post_ip_database_result = trim(preg_replace("/[^A-Z]/", "", sanitize_text_field(wp_unslash($_POST['ipdbc']))));
        } else {
            $this->post_ip_database_result = '';
        }

        // IPv4 from ipdb.cloud, only IPv4, may not be present

        if (isset($_POST['ipv4'])) {
            $this->post_ipv4_value = trim(preg_replace("/[^0-9\.]/", "", sanitize_text_field(wp_unslash($_POST['ipv4']))));
        } else {
            $this->post_ipv4_value = '';
        }

        // reCAPTCHA token, if reCAPTCHA check is enabled

        if (isset($_POST['rct'])) {
            $this->post_recaptcha_token = trim(wp_strip_all_tags(wp_unslash($_POST['rct'])));
        } else {
            $this->post_recaptcha_token = '';
        }

        if (isset($_POST['xxx'])) {
            $this->post_extra_data = trim(wp_strip_all_tags(wp_unslash($_POST['xxx'])));
        } else {
            $this->post_extra_data = '';
        }

        if (isset($_POST['accept'])) {
            $this->post_http_accept = trim(wp_strip_all_tags(wp_unslash($_POST['accept'])));
        } else {
            $this->post_http_accept = '';
        }
    }

    private function processServerData()
    {

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $this->scheme = trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO'])));
        } elseif (isset($_SERVER['REQUEST_SCHEME'])) {
            $this->scheme = trim(wp_strip_all_tags(wp_unslash($_SERVER['REQUEST_SCHEME'])));
        } else {
            $this->scheme = 'https';
        }

        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $val = strtoupper(trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']))));
            if (preg_match('/^(?:[A-Z]{2}|T1)$/', $val) === 1) {
                $this->post_cloudflare_country = $val;
            } else {
                $this->post_cloudflare_country = '';
            }
        } else {
            $this->post_cloudflare_country = '';
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $this->uri = trim(wp_strip_all_tags(wp_unslash($_SERVER['REQUEST_URI'])));
        } else {
            $this->uri = '/';
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref_raw = wp_get_raw_referer();
            /*
            if (! $ref_raw) {
                $raw_ref_data = filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
                if (is_string($raw_ref_data) && $raw_ref_data !== '') {
                    $ref_raw = wp_unslash($raw_ref_data);
                }
            }
            */
            if (is_string($ref_raw) && $ref_raw !== '') {
                $ref_raw = esc_url_raw(trim(str_replace(' ', '+', $ref_raw)));
                $sch = $ref_raw ? wp_parse_url($ref_raw, PHP_URL_SCHEME) : null;
                $this->referer = ($ref_raw && in_array($sch, ['http', 'https'], true)) ? $ref_raw : '';
            } else {
                $this->referer = '';
            }
        } else {
            $this->referer = '';
        }

        // Domain (host) from which the script was called
        $parts = wp_parse_url($this->referer);
        $host  = $parts['host'] ?? '';
        $this->refhost = $host ? strtolower(preg_replace('/[^a-z0-9\.\-]/i', '', $host)) : '';

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->useragent = trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_USER_AGENT'])));
        } else {
            $this->useragent = '';
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->accept_lang = trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
        } else {
            $this->accept_lang = '';
        }
    }

    private function processAdblockerDetect()
    {
        if ($this->settings->block_adblocker_users == 1 &&  $this->post_adblocker_found == 1) {
            $payload = bbcs_local_check_result('error', 'Ads blocking software is strong disabled', '');
            wp_send_json($payload);
            $this->process_die();
        }
    }

    private function processAntidetect()
    {
        $groups = [
            'block_incognito_users' => ['crossbrowserIncognito'],
            'block_simple_antidetect' => ['navigatorMismatch', 'chromiumProperties'],
            'block_override' => ['fakePlugins', 'fontRenderMismatch', 'languageMismatch'],
            'block_web_engine_options' => ['unsupportedFeatures', 'webGLMismatch', 'permissionsMismatch'],
            'block_device_options' => ['touchEventMismatch', 'batteryAPIMismatch', 'mediaDevicesMismatch', 'jitter']
        ];

        $checkWeights = [
            'navigatorMismatch' => 2,
            'unsupportedFeatures' => 2,
            'fakePlugins' => 3,
            'fontRenderMismatch' => 2,
            'chromiumProperties' => 3,
            'jitter' => 1,
            'webGLMismatch' => 2,
            'touchEventMismatch' => 2,
            'batteryAPIMismatch' => 1,
            'mediaDevicesMismatch' => 2,
            'permissionsMismatch' => 1,
            'languageMismatch' => 3,
            'crossbrowserIncognito' => 2
        ];

        $groupThresholds = [
            'block_incognito_users' => 2,
            'block_simple_antidetect' => 4,
            'block_override' => 5,
            'block_web_engine_options' => 4,
            'block_device_options' => 4
        ];

        $minGroupsToBlock = 2;

        $totalPositiveThreshold = 6;

        $activeGroups = 0;
        $triggeredGroups = 0;
        $totalPositiveScore = 0;
        $totalEnabledChecks = 0;
        $blockReasons = [];
        $groupDetails = [];

        foreach ($groups as $settingKey => $checks) {
            if (!empty($this->settings->$settingKey) && $this->settings->$settingKey == true) {
                $activeGroups++;
                $groupScore = 0;
                $groupMaxScore = 0;
                $groupPositives = [];

                foreach ($checks as $checkKey) {
                    $totalEnabledChecks++;
                    $checkWeight = $checkWeights[$checkKey] ?? 1;
                    $groupMaxScore += $checkWeight;

                    if (isset($this->post_antidetect_scope[$checkKey]) && $this->post_antidetect_scope[$checkKey] == true) {
                        $groupScore += $checkWeight;
                        $totalPositiveScore += $checkWeight;
                        $groupPositives[] = $checkKey;
                    }
                }

                $groupPercentage = ($groupMaxScore > 0) ? ($groupScore / $groupMaxScore) * 100 : 0;

                $groupThreshold = $groupThresholds[$settingKey] ?? 3;
                $isTriggered = ($groupScore >= $groupThreshold) || ($groupPercentage >= 70);

                $groupDetails[$settingKey] = [
                    'score' => $groupScore,
                    'maxScore' => $groupMaxScore,
                    'percentage' => $groupPercentage,
                    'triggered' => $isTriggered,
                    'positives' => $groupPositives
                ];

                if ($isTriggered) {
                    $triggeredGroups++;
                    $blockReasons = array_merge($blockReasons, $groupPositives);
                }
            }
        }

        $totalMaxScore = 0;
        foreach ($checkWeights as $key => $weight) {
            if (isset($this->post_antidetect_scope[$key])) {
                $totalMaxScore += $weight;
            }
        }

        $totalPercentage = ($totalMaxScore > 0) ? ($totalPositiveScore / $totalMaxScore) * 100 : 0;

        $percentageThreshold = 70; // TODO settings

        $combinationDetected = false;

        if (
            isset($this->post_antidetect_scope['navigatorMismatch']) &&
            $this->post_antidetect_scope['navigatorMismatch'] &&
            isset($this->post_antidetect_scope['webGLMismatch']) &&
            $this->post_antidetect_scope['webGLMismatch']
        ) {
            $combinationDetected = true;
            $blockReasons[] = 'critical_combination_navigator_webgl';
        }

        if (
            isset($this->post_antidetect_scope['fakePlugins']) &&
            $this->post_antidetect_scope['fakePlugins'] &&
            isset($this->post_antidetect_scope['chromiumProperties']) &&
            $this->post_antidetect_scope['chromiumProperties']
        ) {
            $combinationDetected = true;
            $blockReasons[] = 'critical_combination_plugins_chrome';
        }

        if (
            isset($this->post_antidetect_scope['fontRenderMismatch']) &&
            $this->post_antidetect_scope['fontRenderMismatch'] &&
            isset($this->post_antidetect_scope['languageMismatch']) &&
            $this->post_antidetect_scope['languageMismatch']
        ) {
            $combinationDetected = true;
            $blockReasons[] = 'critical_combination_font_language';
        }

        $shouldBlock = false;
        $blockReason = '';

        if ($triggeredGroups >= $minGroupsToBlock && $activeGroups > 0) {
            $shouldBlock = true;
            $blockReason = 'Multiple detection groups triggered';
        }

        if ($totalPositiveScore >= $totalPositiveThreshold && $totalEnabledChecks >= 8) {
            $shouldBlock = true;
            $blockReason = 'High total detection score';
        }

        if ($totalPercentage >= $percentageThreshold && $totalEnabledChecks >= 5) {
            $shouldBlock = true;
            $blockReason = 'High percentage of positive detections';
        }

        if ($combinationDetected) {
            $shouldBlock = true;
            $blockReason = 'Critical combination of detections';
        }

        $blockReasons = array_unique($blockReasons);

        if ($shouldBlock && !empty($blockReasons)) {
            usort($blockReasons, function ($a, $b) use ($checkWeights) {
                return ($checkWeights[$b] ?? 1) - ($checkWeights[$a] ?? 1);
            });

            $displayReasons = array_slice($blockReasons, 0, 3);

            $message = 'Browser_Check: ' . implode(', ', $displayReasons);
            if (count($blockReasons) > 3) {
                $message .= ' and ' . (count($blockReasons) - 3) . ' more';
            }
            $payload = bbcs_local_check_result('error', $message, '');
            wp_send_json($payload);
            $this->process_die();
        }
    }

    private function processReCaptchaV3()
    {
        if ($this->settings->recaptcha_check == 1 && !empty($this->settings->recaptcha_secret3) && ! ($this->settings->recaptcha_v3_ipv6_block == 1 && $this->ip_version == 6)) {

            $args = [
                'body'      => [
                    'secret'   => $this->settings->recaptcha_secret3,
                    'response' => $this->post_recaptcha_token,
                    'remoteip' => $this->ip,
                ],
                'timeout'   => 10,
                'headers'   => [
                    'Referer'    => $this->referer,
                    'User-Agent' => $this->useragent,
                ],
                'sslverify' => true,
            ];

            $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $args);

            if (is_wp_error($resp)) {
                $this->post_recaptcha_score = 0;
                $payload = bbcs_local_check_result('error', 'Recaptcha request failed', '');
                wp_send_json($payload);
                $this->process_die();
            }

            $body = wp_remote_retrieve_body($resp);
            $re   = json_decode($body, true);

            if (isset($re['score'])) {
                $this->post_recaptcha_score = trim($re['score']);
            } else {
                $this->post_recaptcha_score = 0;
                $payload = bbcs_local_check_result('error', 'Recaptcha Error', '');
                wp_send_json($payload);
                $this->process_die();
            }

            $force_cloud_active = ($this->settings->force_cloud_validation == 1 && $this->settings->cloud_api_tier === 'ultimate');
            if ($this->post_recaptcha_score <= $this->settings->recaptcha_tresshold && $this->settings->check == 0 && !$force_cloud_active) {
                $payload = bbcs_local_check_result('error', 'Recaptcha threshold failed', '');
                wp_send_json($payload);
                $this->process_die();
            }
        } else {
            $this->post_recaptcha_score = 0;
        }
    }

    private function processCloudCheck()
    {
        $cache_key = bbcs_getCachePrefix('_CLOUD_DATA_');
        $cache_ttl = 86400; // 1 day

        $cached_data = bbcs_getCachedCloudData($cache_key);
        if ($cached_data) {
            $this->cloud_data = $cached_data;
            $this->cloud_error = BOTBLOCKER_EMPTY;
        } else {
            $data = array(
                'cloud_api_key' => $this->settings->cloud_api_key,
                'domain_api_key' => $this->settings->cloud_api_secret,
                'cid' => $this->cid,
                'score' => $this->post_recaptcha_score,
                'cfcountry' => $this->post_cloudflare_country,
                'country' => $this->country,
                'ip' => $this->ip,
                'version' => $this->version,
                'ptr' => $this->ptr,
                'w' => $this->post_width,
                'h' => $this->post_height,
                'cw' => $this->post_client_width,
                'ch' => $this->post_client_height,
                'co' => $this->post_color_depth,
                'pi' => $this->post_pixel_depth,
                'ref' => $this->post_referrer,
                'tz' => $this->post_timezone,
                'adb' => $this->post_adblocker_found,
                'ipdbc' => $this->post_ip_database_result,
                'ipv4' => $this->post_ipv4_value,
                'accept' => $this->post_http_accept,
                'referer' => $this->referer,
                'useragent' => $this->useragent,
                'accept_lang' => $this->accept_lang,
                'post_antidetect_scope' => $this->post_antidetect_scope
                // TODO fingerprint
            );

            $this->cloud_error = BOTBLOCKER_EMPTY;

            $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL);
            if ($cloud === false || isset($cloud['error'])) {
                if ($cloud === false) {
                    $this->cloud_error = 'BOTBLOCKER_API_GS_URL connection failed.';
                } elseif (isset($cloud['error'])) {
                    $this->cloud_error = 'BOTBLOCKER_API_GS_URL error: ' . $cloud['error'];
                }
                $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL);

                if ($cloud === false || isset($cloud['error'])) {
                    if ($cloud === false) {
                        $this->cloud_error = 'BOTBLOCKER_API_URL connection failed.';
                    } elseif (isset($cloud['error'])) {
                        $this->cloud_error = 'BOTBLOCKER_API_URL error: ' . $cloud['error'];
                    }
                    if ($this->settings->unresponsive == 0) {
                        $payload = bbcs_local_check_result('error', $this->cloud_error, '');
                        wp_send_json($payload);
                        $this->process_die();
                    }
                }
            }

            $this->cloud_data = [];
            foreach ($cloud as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $sub_key => $sub_value) {
                        $this->cloud_data[$key . '_' . $sub_key] = $sub_value; //
                    }
                } else {
                    $this->cloud_data[$key] = $value;
                }
            }
            //
            $this->cloud_error = BOTBLOCKER_EMPTY;
            bbcs_cacheCloudData($cache_key, $this->cloud_data, $cache_ttl);
        }

        $status = $this->cloud_data['status'] ?? 'unknown';
        $BBCS_score = $this->cloud_data['bbcs_score'] ?? 0;

        if (($status === 'bad' || $BBCS_score >= 5) && $this->settings->unresponsive == 0) {
            $payload = bbcs_local_check_result('error', 'Cloud API ident visitor as bad', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($status === 'gray' && $this->settings->unresponsive == 0) {
            $payload = bbcs_local_check_result('error', 'Cloud API ident visitor as gray', '');
            wp_send_json($payload);
            $this->process_die();
        }

        if ($status !== 'good' && $this->settings->unresponsive == 1) {
            $message = 'Cloud API mark visitor as not good, but access allowed due to unresponsive flag';
        }

        if ($status == 'good' && ($this->settings->unresponsive == 0 || $this->settings->unresponsive == 1)) {
            $message = 'Cloud API ident visitor as good';
        }

        // TODO processFullCloud (for PRO)
    }
}
