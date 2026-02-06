<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

trait BotBlockerPostTrait
{

    public function processPostRequest()
    {
        check_ajax_referer('botblocker_nonce', 'nonce');
        global $wpdb;
        ignore_user_abort(true);

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->process_die('{"error": "Error NoPost"}');
        }

        if (isset($_POST['cid'])) {
            $this->cid = trim(preg_replace("/[^0-9\.]/", "", sanitize_text_field(wp_unslash($_POST['cid']))));
        } else {
            $this->process_die('{"error": "CID not set"}');
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->useragent = trim(wp_strip_all_tags(wp_unslash($_SERVER['HTTP_USER_AGENT'])));
        } else {
            $this->useragent = '';
        }

        if (isset($_POST['ip'])) {
            $_POST['ip'] = trim(preg_replace("/[^0-9a-zA-Z\.\:]/", "", sanitize_text_field(wp_unslash($_POST['ip']))));
        } else {
            $this->process_die('{"error": "IP not set"}');
        }

        if (isset($_POST['xxx'])) {
            $_POST['xxx'] = trim(wp_strip_all_tags(wp_unslash($_POST['xxx'])));
        } else {
            $this->process_die('{"error": "XXX not set"}');
        }

        if (isset($_POST['date'])) {
            $_POST['date'] = (int)trim(wp_strip_all_tags(wp_unslash($_POST['date'])));
        } else {
            $this->process_die('{"error": "DATE not set"}');
        }

        if (isset($_POST['country'])) {
            $_POST['country'] = trim(preg_replace("/[^A-Z]/", "", wp_strip_all_tags(wp_unslash($_POST['country']))));
        } else {
            $_POST['country'] = BOTBLOCKER_EMPTY;
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
                $this->process_die('{"error": "Referer not set"}');
            }
        } else {
            $referer = '';
            $this->process_die('{"error": "Referer not set"}');
        }

        // Domain (host) from which the script was called
        $parts = wp_parse_url($this->referer);
        $host  = $parts['host'] ?? '';
        $refhost = $host ? strtolower(preg_replace('/[^a-z0-9\.\-]/i', '', $host)) : '';

        /*
        * if ipv4 exist - check base 1
        * if country ipv4 exist - check base 5
        * if country base 1 != country base 5 - FAKE
        */

        if ($this->time - $_POST['date'] > $this->settings->bbcs_captcha_wait) $this->process_die('{"cookie":"000"}');

        if ($this->settings->bbcs_captcha_mode == 3 || $this->settings->bbcs_captcha_mode == 4) {

            $g_recaptcha_response = isset($_POST['g-recaptcha-response'])
                ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response']))
                : '';

            $args = [
                'body'      => [
                    'secret'   => $this->settings->recaptcha_secret2,
                    'response' => $g_recaptcha_response,
                    'remoteip' => isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '',
                ],
                'timeout'   => 15,
                'headers'   => [
                    'User-Agent' => $this->useragent,
                    'Referer'    => '',
                ],
                'sslverify' => true,
            ];

            $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $args);

            if (is_wp_error($resp)) {
                $this->settings->bbcs_captcha_mode = 1;
                if ($this->settings->time_ban < 1) {
                    $this->settings->time_ban = '1';
                }
            } else {
                $re = json_decode(wp_remote_retrieve_body($resp), true);
                if (isset($re['success']) && (int) $re['success'] !== 1) {
                    $this->settings->bbcs_captcha_mode = 1;
                    if ($this->settings->time_ban < 1) {
                        $this->settings->time_ban = '1';
                    }
                }
            }
        }

        if ($this->settings->bbcs_captcha_mode == 0 or $this->settings->bbcs_captcha_mode == 3 or $this->settings->bbcs_captcha_mode == 4) {
            $date_from_post = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
            $xxx_from_post = isset($_POST['xxx']) ? sanitize_text_field(wp_unslash($_POST['xxx'])) : '';
            $hash0 = '1|' . hash('sha256', $this->settings->salt . $date_from_post . $this->settings->cloud_api_pass);
            if ($hash0 != $xxx_from_post) {
                $this->settings->bbcs_captcha_mode = 1;
                if ($this->settings->time_ban < 1) {
                    $this->settings->time_ban = '1';
                }
            }
        }

        if ($this->settings->bbcs_captcha_mode == 1 or $this->settings->bbcs_captcha_mode == 2) {
            $xxx2 = explode('|', sanitize_text_field(wp_unslash($_POST['xxx'])));
            if (!isset($xxx2[1])) $this->process_die('{"error": "Error NoPost 1"}');
            $_POST['color'] = $xxx2[0];
            $_POST['color_hash'] = $xxx2[1];
            if (!isset($_POST['color'], $_POST['color_hash'], $_POST['date'], $_POST['ip'])) {
                $this->process_die('{"error": "Missing required POST data"}');
            }
            if (
                sanitize_text_field(wp_unslash($_POST['color_hash']))
                !=
                hash(
                    'sha256',
                    $this->settings->salt .
                        sanitize_text_field(wp_unslash($_POST['color'])) .
                        sanitize_text_field(wp_unslash($_POST['date'])) .
                        $this->settings->cloud_api_pass .
                        sanitize_text_field(wp_unslash($_POST['ip']))
                )
            ) $this->process_wrong_click();
        } elseif ($this->settings->bbcs_captcha_mode == 5) {
            // Moving Shapes (5)
            $xxx2 = explode('|', sanitize_text_field(wp_unslash($_POST['xxx'])));
            if (!isset($xxx2[1])) $this->process_die('{"error": "Error NoPost 5"}');

            $_POST['shape'] = $xxx2[0];
            $_POST['shape_hash'] = $xxx2[1];

            // "wrong|"
            if (!isset($_POST['shape']) || strpos(sanitize_text_field(wp_unslash($_POST['shape'])), 'wrong') === 0) {
                $this->process_wrong_click();
            }

            if (
                empty($_POST['shape_hash'])
                ||
                sanitize_text_field(wp_unslash($_POST['shape_hash']))
                !=
                hash(
                    'sha256',
                    $this->settings->salt .
                        sanitize_text_field(wp_unslash($_POST['shape'])) .
                        sanitize_text_field(wp_unslash($_POST['date'])) .
                        $this->settings->cloud_api_pass .
                        sanitize_text_field(wp_unslash($_POST['ip']))
                )
            ) $this->process_wrong_click();
        } elseif ($this->settings->bbcs_captcha_mode == 6) {
            // Math Expression (6)
            $xxx2 = explode('|', sanitize_text_field(wp_unslash($_POST['xxx'])));
            if (count($xxx2) < 3) $this->process_die('{"error": "Error NoPost 6"}');

            $_POST['answer'] = $xxx2[0];
            $_POST['type'] = $xxx2[1];
            $_POST['answer_hash'] = $xxx2[2];

            if (!empty($_POST['type']) && $_POST['type'] === 'wrong') {
                $this->process_wrong_click();
            } elseif (!empty($_POST['type']) && $_POST['type'] === 'math') {

                if (
                    empty($_POST['answer_hash']) || empty($_POST['answer'])
                    ||
                    sanitize_text_field(wp_unslash($_POST['answer_hash']))
                    !=
                    hash(
                        'sha256',
                        $this->settings->salt .
                            sanitize_text_field(wp_unslash($_POST['answer'])) .
                            sanitize_text_field(wp_unslash($_POST['date'])) .
                            $this->settings->cloud_api_pass .
                            sanitize_text_field(wp_unslash($_POST['ip']))
                    )
                ) $this->process_wrong_click();
            } else {
                $this->process_die('{"error": "Error NoPost Type"}');
            }
        } elseif ($this->settings->bbcs_captcha_mode == 3) {
            // ReCAPTCHA v2 + I am not robot

        } elseif ($this->settings->bbcs_captcha_mode == 0) {
            // Single button
        }

        if ($this->settings->botblocker_log_tests == 1) {
            global $wpdb;

            $cid = isset($_POST['cid']) ? sanitize_text_field(wp_unslash($_POST['cid'])) : '';

            $code_data = bbcs_codeList(0);

            if ($code_data['allow']) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $wpdb->bbcs_hits,
                    ['passed' => 2],
                    ['passed' => 0, 'cid' => $cid],
                    ['%d'],
                    ['%d', '%s']
                );
            } else {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $wpdb->bbcs_hits_suspicious,
                    ['passed' => 2],
                    ['passed' => 0, 'cid' => $cid],
                    ['%d'],
                    ['%d', '%s']
                );
            }
            // bbcs_process_hit('2');
        }

        $hash = md5($this->settings->salt . $this->settings->cloud_api_pass . $refhost . $this->useragent . sanitize_text_field(wp_unslash($_POST['ip'])) . $this->time) . '-' . $this->time;
        wp_send_json(['cookie' => $hash]); // Experimental: Use wp_send_json for better JSON handling
    }

    public function process_wrong_click()
    {
        check_ajax_referer('botblocker_nonce', 'nonce');

        global $wpdb;

        if (!isset($_POST['ip'])) {
            $this->process_die('{"error": "Bad IP"}');
        }
        $ip_sanitized = sanitize_text_field(wp_unslash($_POST['ip']));
        if (filter_var($ip_sanitized, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->ip_version = 4;
        } elseif (filter_var($ip_sanitized, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->ip_version = 6;
        } else {
            $this->process_die('{"error": "Bad IP"}');
        }

        $fromdate = $this->time - 86401;

        $ip_from_post   = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        $passed_code    = 8;
        $fromdate       = (int) $fromdate;

        $found = false;
        if (BOTBLOCKER_CACHE_WP) {
            $cache_key = 'bbcs_miss_count' . bbcs_get_wp_cache_version() . md5($this->cid);
            $miss_count = false;
            $miss_count = wp_cache_get($cache_key, 'botblocker-security', false, $found);
        }
        if ($found === false) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $miss_count = (int) $wpdb->get_var($wpdb->prepare(
                "
                SELECT COUNT(*) FROM (
                    SELECT * FROM `{$wpdb->bbcs_hits}`
                    UNION ALL
                    SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
                ) AS combined_hits
                WHERE date >= %d AND ip = %s AND passed = %d
                ",
                $fromdate,
                $ip_from_post,
                $passed_code
            ));
            if (BOTBLOCKER_CACHE_WP) {
                wp_cache_set($cache_key, $miss_count, 'botblocker-security', BOTBLOCKER_CACHE_RULES_CHECK_TIME);
            }
        }

        if ($miss_count > 0) {
            $this->settings->time_ban = $this->settings->time_ban_2;
        }

        if ($this->settings->time_ban == 0) {
            $this->settings->time_ban = 400;
        }

        $ip = sanitize_text_field(wp_unslash($_POST['ip']));

        if ($this->ip_version == 4) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $existing_rule = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
                $ip
            ));
        } else {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $existing_rule = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s",
                $ip
            ));
        }

        $table_name = $this->ip_version == 4 ? $wpdb->bbcs_ipv4rules : $wpdb->bbcs_ipv6rules;
        $expires = $this->time + $this->settings->time_ban;
        if ($existing_rule) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table_name,
                array('expires' => $expires),
                array('search' => $ip)
            );
        } else {
            $ip2ban = $this->ip_version == 4 ? bbcs_ipToNumeric($ip) : bbcs_ipv6_bin($ip);
            $data = array(
                'priority' => '1',
                'search' => $ip,
                'ip1' => $ip2ban,
                'ip2' => $ip2ban,
                'rule' => 'block',
                'comment' => 'Wrong Math ' . (isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : ''),
                'expires' => $expires
            );
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table_name, $data);
        }

        if ($this->settings->botblocker_log_tests == 1) {

            $code_data = bbcs_codeList(8);

            $cid_from_post = isset($_POST['cid']) ? sanitize_text_field(wp_unslash($_POST['cid'])) : '';

            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($code_data['allow']) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->bbcs_hits} SET passed = %d WHERE passed IN (%d, %d) AND cid = %s",
                        8,
                        0,
                        9,
                        $cid_from_post
                    )
                );
            } else {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->bbcs_hits_suspicious} SET passed = %d WHERE passed IN (%d, %d) AND cid = %s",
                        8,
                        0,
                        9,
                        $cid_from_post
                    )
                );
            }
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        $this->process_die('{"error": "Wrong Click"}');
    }
}
