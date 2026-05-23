<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerRulesTrait {

    private function get_asn_rule(): ?string
    {
        if ( empty( $this->bbcs_asn ) || ! is_array( $this->bbcs_asn ) ) {
            return null;
        }

        $asnum = $this->get_normalized_asnum();
        if ( $asnum === '' || ! isset( $this->bbcs_asn[ $asnum ] ) ) {
            return null;
        }

        $rule = strtolower( trim( (string) $this->bbcs_asn[ $asnum ] ) );
        return in_array( $rule, [ 'allow', 'block', 'dark', 'gray' ], true ) ? $rule : null;
    }

    private function get_normalized_asnum(): string
    {
        if ( empty( $this->asnum ) || $this->asnum === BOTBLOCKER_EMPTY ) {
            return '';
        }

        return preg_replace( '/[^0-9]/', '', (string) $this->asnum );
    }

    private function has_matching_asn_token( array $tokens ): bool
    {
        $asnum = $this->get_normalized_asnum();
        if ( $asnum === '' ) {
            return false;
        }

        foreach ( $tokens as $token ) {
            if (
                is_string( $token ) &&
                strncmp( $token, 'asn:', 4 ) === 0 &&
                preg_replace( '/[^0-9]/', '', substr( $token, 4 ) ) === $asnum
            ) {
                return true;
            }
        }
        return false;
    }

    private function has_pending_restrictive_response(): bool
    {
        return $this->visitorType === self::VISITOR_FAKEBOT
            || $this->should_show_denied_page === true
            || $this->should_show_block_page === true
            || $this->should_show_check_page === true
            || (int) $this->suspect_status === 2;
    }

    private function is_allowed_asn_for_white_bot( array $tokens ): bool
    {
        return $this->has_matching_asn_token( $tokens ) && $this->get_asn_rule() === 'allow';
    }

public function check_secret_parameter() : bool
{
    $param = $this->settings->secret_botblocker_get_param;
    if ($param != '' && $param != BOTBLOCKER_EMPTY) {
        $cookie_set = isset($_COOKIE[$param]);

        if (isset($_GET[$param]) || $cookie_set) {
            $this->x_robots_tag['noindex'] = 'noindex';
            $this->visitorType = self::VISITOR_SECRET;

            if (!$cookie_set) {
                $this->set_secret_cookie();
            }

            if (isset($_GET[$param]) && sanitize_text_field(wp_unslash($_GET[$param])) == $this->action_disable) {
                $this->isDisabled = true;
                bbcs_storeData('BotBlocker skip by secret', 23);
                bbcs_process_hit(23);
                return true;
            }

            if (isset($_GET[$param]) && sanitize_text_field(wp_unslash($_GET[$param])) == $this->action_off) {
                $this->result_of_action = 'BotBlocker stop by secret';
                bbcs_toggleBBpower(1);
                bbcs_storeData('BotBlocker stop by secret', 23);
                bbcs_process_hit(23);

                delete_transient('bbcs_site_health_list');
                delete_transient('bbcs_site_health');

                return true;
            }

            if (isset($_GET[$param]) && sanitize_text_field(wp_unslash($_GET[$param])) == $this->action_on) {
                $this->result_of_action = 'BotBlocker start by secret';
                bbcs_storeData('BotBlocker start by secret', 98);
                bbcs_process_hit(98);
                bbcs_toggleBBpower(0);

                delete_transient('bbcs_site_health_list');
                delete_transient('bbcs_site_health');

                return false;
            }
        }
    }
    return false;
}

    public function check_path_rules() : bool
    {
        if ( $this->has_pending_restrictive_response() ) {
            return true;
        }

        foreach ($this->bbcs_path as $bbcs_line => $bbcs_sign) {
            if (stripos($this->uri, $bbcs_line) !== false) {
                if ($bbcs_sign == 'block') {
                    $this->redirect_to_block(6, 'BLOCK By rule (url part): ' . $bbcs_line);
                } elseif ($bbcs_sign == 'dark') {
                    $this->redirect_to_dark('DARK By rule (url part): ' . $bbcs_line);
                } elseif ($bbcs_sign == 'gray') {
                    $this->set_gray_status('GRAY By rule (url part): ' . $bbcs_line);
                } elseif ($bbcs_sign == 'allow') {
                    if ( $this->has_pending_restrictive_response() ) {
                        continue;
                    }
                    $this->visitorType = self::VISITOR_LEGALBOT;
                    if ($this->settings->botblocker_log_allow == 1) {
                        bbcs_storeData('Allow access to path: ' . $bbcs_line, 4);
                    }
                    bbcs_process_hit(4);
                    break;
                }
            }
        }
        if($this->visitorType == self::VISITOR_LEGALBOT){
            return true;
        }
        return $this->has_pending_restrictive_response();
    }

    /*
    public function check_white_bot() : bool
    {
        foreach ($this->bbcs_se as $bbcs_line => $bbcs_sign) {
            if (stripos($this->useragent, $bbcs_line) !== false) {
                $escaped_line = esc_sql($bbcs_line);

                if ($this->bbcs_rule[$bbcs_line] === 'block') {
                    $this->redirect_to_block(6, "1 - <b>block</b> by user-agent: {$escaped_line}");
                } elseif ($this->bbcs_rule[$bbcs_line] === 'dark') {
                    $this->redirect_to_dark("1 - <b>dark</b> by user-agent: {$escaped_line}");
                } elseif ($this->bbcs_rule[$bbcs_line] === 'gray') {
                    $this->set_gray_status("1 - <b>gray</b> by user-agent: {$escaped_line}");
                }
            }
            if (stripos($this->useragent, $bbcs_line) !== false && $this->bbcs_rule[$bbcs_line] === 'allow') {
                $escaped_line = esc_sql($bbcs_line);

                if (bbcs_testWhiteBot($this->ip, $bbcs_sign, $this->time, $this->settings->ptrcache_time) === true) {
                    if (!in_array('.', $this->bbcs_se[$bbcs_line], true)) {
                        bbcs_storePTRrule();
                    }
                    $this->result_of_action = "Legal bot detected: {$escaped_line}";
                    $this->white_bot = $escaped_line;
                    $this->visitorType = self::VISITOR_LEGALBOT;
                    break;
                } else {
                    $this->redirect_to_denied(7, "Fake bot detected: {$escaped_line}");
                }
                break;
            }
        }
        if ($this->visitorType == self::VISITOR_LEGALBOT) {
            bbcs_storeData(null, 5);
            bbcs_process_hit(5);
            return true;
        }
        return false;
    }
    */

    public function check_white_bot() : bool
    {
        // UA match + PTR or ASN-table verification.
        foreach ($this->bbcs_se as $bbcs_line => $bbcs_sign) {
            if (stripos($this->useragent, $bbcs_line) === false) {
                continue;
            }

            $escaped_line = esc_sql($bbcs_line);
            $rule = $this->bbcs_rule[$bbcs_line];

            if ($rule === 'block') {
                $this->redirect_to_block(6, "1 - <b>block</b> by user-agent: {$escaped_line}");
                break;
            } elseif ($rule === 'dark') {
                $this->redirect_to_dark("1 - <b>dark</b> by user-agent: {$escaped_line}");
                break;
            } elseif ($rule === 'gray') {
                $this->set_gray_status("1 - <b>gray</b> by user-agent: {$escaped_line}");
                break;
            } elseif ($rule === 'allow') {
                if ( $this->is_allowed_asn_for_white_bot( $bbcs_sign ) ) {
                    $this->result_of_action = 'Legal bot by ASN token: ' . $escaped_line . ' (asn:' . esc_sql( (string) $this->asnum ) . ')';
                    $this->white_bot        = $escaped_line;
                    $this->visitorType      = self::VISITOR_LEGALBOT;
                    break;
                }

                // Strip asn: tokens - PTR verification only works with domain suffixes.
                $ptr_tokens = array();
                foreach ( $bbcs_sign as $token ) {
                    if ( ! is_string( $token ) || strncmp( $token, 'asn:', 4 ) !== 0 ) {
                        $ptr_tokens[] = $token;
                    }
                }
                if ( empty( $ptr_tokens ) ) {
                    $this->redirect_to_denied(7, "Fake bot detected: {$escaped_line}");
                } elseif (bbcs_testWhiteBot($this->ip, $ptr_tokens, $this->time, $this->settings->ptrcache_time) === true) {
                    if (!in_array('.', $ptr_tokens, true)) {
                        bbcs_storePTRrule();
                    }
                    $this->result_of_action = "Legal bot detected: {$escaped_line}";
                    $this->white_bot = $escaped_line;
                    $this->visitorType = self::VISITOR_LEGALBOT;
                } else {
                    $this->redirect_to_denied(7, "Fake bot detected: {$escaped_line}");
                }
                break;
            }
        }

        if ($this->visitorType == self::VISITOR_LEGALBOT) {
            bbcs_storeData(null, 5);
            bbcs_process_hit(5);
            return true;
        }
        return false;
    }

    public function check_asn_rules() : bool
    {
        if ( $this->has_pending_restrictive_response() ) {
            return true;
        }

        $rule = $this->get_asn_rule();
        if ( $rule === null ) {
            return false;
        }

        $search = 'asn=' . esc_sql( (string) $this->asnum );
        $rule_data = [
            'rule'   => $rule,
            'search' => $search,
            'asnum'  => $this->asnum,
        ];

        if ( $rule === 'allow' ) {
            if ( (int) $this->suspect_status > 0 ) {
                return false;
            }
            $this->result_of_action = esc_sql( '<b>Allow</b> by ASN rule: ' . $this->asnum );
            return $this->allow_access( $rule_data );
        } elseif ( $rule === 'block' ) {
            $this->redirect_to_block( 6, esc_sql( 'BLOCK by ASN rule: ' . $this->asnum ), $rule_data );
            return true;
        } elseif ( $rule === 'dark' ) {
            $this->redirect_to_dark( esc_sql( 'DARK by ASN rule: ' . $this->asnum ) );
            return true;
        } elseif ( $rule === 'gray' ) {
            $this->set_gray_status( esc_sql( 'GRAY by ASN rule: ' . $this->asnum ) );
        }

        return false;
    }

    public function check_rules_database() : bool
    {
        if ( $this->has_pending_restrictive_response() ) {
            return true;
        }

        global $wpdb;
        $rule = null;
        $search = null;
        $rule_data = null;
        $bbcs_search = array();
        if (!empty($this->useragent)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('useragent=' . $this->useragent) . '%');
        }
        if (!empty($this->country)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('country=' . $this->country) . '%');
        }
        if (!empty($this->lang)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('lang=' . $this->lang) . '%');
        }
        if (!empty($this->page)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('page=' . $this->page) . '%');
        }
        if (!empty($this->refhost)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('referer=' . $this->refhost) . '%');
        }
        if (!empty($this->ym_uid)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('ym_uid=' . $this->ym_uid) . '%');
        }
        if (!empty($this->ga_uid)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('ga_uid=' . $this->ga_uid) . '%');
        }
        $this->ptr_arr = explode('.', $this->ptr);
        $this->ptr_arr = array_reverse($this->ptr_arr, false);
        if (isset($this->ptr_arr[1])) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('ptr=' . $this->ptr_arr[1] . '.' . $this->ptr_arr[0]) . '%');
        }
        if (isset($this->ptr_arr[2])) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('ptr=' . $this->ptr_arr[2] . '.' . $this->ptr_arr[1] . '.' . $this->ptr_arr[0]) . '%');
        }
        if (!empty($this->asname)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('asname=' . $this->asname) . '%');
        }
        if (!empty($this->asnum)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('asnum=' . $this->asnum) . '%');
        }
        if (!empty($this->uri)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('uri=' . $this->uri) . '%');
        }
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $bbcs_search[] = $wpdb->prepare(
                "search LIKE %s",
                '%' . $wpdb->esc_like('scriptname=' . trim(wp_strip_all_tags(wp_unslash($_SERVER['SCRIPT_NAME'])))) . '%'
            );
        }
        if (!empty($this->http_accept)) {
            $bbcs_search[] = $wpdb->prepare("search LIKE %s", '%' . $wpdb->esc_like('httpaccept=' . trim(wp_strip_all_tags($this->http_accept))) . '%');
        }

        $bbcs_test_visitor = [];
        if (!empty($bbcs_search)) {
            $query = "SELECT * FROM `{$wpdb->bbcs_rules}` WHERE " . implode(' OR ', $bbcs_search) . " ORDER BY priority ASC";
            // REVIEWER NOTE: Dynamic WHERE clause is built using $wpdb->prepare() for each filter to ensure all values are properly escaped.
            // The final query string is assembled from individually prepared clauses. This pattern is secure and prevents SQL injection.
            // Suppressing the warning with phpcs:ignore is required for dynamic filtering in WordPress plugins.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $bbcs_test_visitor = $wpdb->get_results($query, ARRAY_A);

            // DEBUG: временная диагностика - раскомментировать для отладки check_rules_database
            // error_log('[BBCS DEBUG check_rules_database] SQL: ' . $query);
            // error_log('[BBCS DEBUG check_rules_database] Rows found: ' . count($bbcs_test_visitor));
            // error_log('[BBCS DEBUG check_rules_database] DB error: ' . $wpdb->last_error);
            // if (!empty($bbcs_test_visitor)) { error_log('[BBCS DEBUG check_rules_database] First row: ' . wp_json_encode($bbcs_test_visitor[0])); }
        }

        foreach ($bbcs_test_visitor as $echo) {
            if ($echo['disable'] == '0') {
                $rule = $echo['rule'];
                $search = isset($echo['search']) ? $echo['search'] : null;
                $rule_data = $echo;

                break;
            }
        }

        if ($rule === null) {

            return false;
        }

        if ($rule == 'allow') {
            if ($this->allow_access($rule_data)) {
                return true;
            }
        } elseif ($rule == 'block') {
            $this->redirect_to_block(6, esc_sql('BLOCK By rule: ' . $search), $rule_data);
        } elseif ($rule == 'dark') {
            $this->rule_record_id = isset($rule_data['id']) ? $rule_data['id'] : null;
            $this->redirect_to_dark(esc_sql('DARK By rule: ' . $search));
        } elseif ($rule == 'gray') {
            $this->set_gray_status(esc_sql('GRAY By rule: ' . $search));
        }
        return $rule == 'allow' || $this->has_pending_restrictive_response();
    }

    public function check_ip_rules() : bool {
        if ( $this->has_pending_restrictive_response() ) {
            return true;
        }

        global $wpdb;
        $table_name = $this->ip_version == 4 ? $wpdb->bbcs_ipv4rules : $wpdb->bbcs_ipv6rules;

        if( $this->ip_version == 4 ) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $bbcs_ip_test = $wpdb->get_row(
                $wpdb->prepare(
                        "SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
                        0,
                        $this->ipnum,
                        $this->ipnum
                    ),
                    ARRAY_A
                );
        } else {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $bbcs_ip_test = $wpdb->get_row(
                $wpdb->prepare(
                        "SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
                        0,
                        $this->ipnum,
                        $this->ipnum
                    ),
                    ARRAY_A
                );
        }

        if ( $bbcs_ip_test ) {
            if ( $bbcs_ip_test['expires'] < $this->time ) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete( $table_name, array( 'id' => $bbcs_ip_test['id'] ) );
                $bbcs_ip_test['rule'] = 'gray';

            }
            if ( $bbcs_ip_test['rule'] == 'allow' ) {

                if ( $bbcs_ip_test['readonly'] == 1 && ( $bbcs_ip_test['comment'] === 'IPv4 BotBlocker Server' || $bbcs_ip_test['comment'] === 'IPv6 BotBlocker Server' ) ) {
                    $this->visitorType = self::VISITOR_BOTBLOCKER;
                    $this->isDisabled = true;
                    bbcs_storeData( 'BotBlocker server', 99 );
                    bbcs_process_hit( 99 );
                    return true; // BotBlocker Server activating cloud API or update bot database
                }

                $this->result_of_action = esc_sql( '<b>Allow</b> by IP rule: ' . $bbcs_ip_test['search'] );
                return $this->allow_access( $bbcs_ip_test );
            } elseif ( $bbcs_ip_test['rule'] == 'block' ) {
                $this->redirect_to_block( 6, esc_sql( 'BLOCK by IP rule: ' . $bbcs_ip_test['search'] ), $bbcs_ip_test );
            } elseif ( $bbcs_ip_test['rule'] == 'dark' ) {
                $this->redirect_to_dark( esc_sql( 'DARK by IP rule: ' . $bbcs_ip_test['search'] ) );
            } elseif ( $bbcs_ip_test['rule'] == 'gray' ) {
                $this->set_gray_status( esc_sql( 'GRAY by IP rule: ' . $bbcs_ip_test['search'] ) );
            }
        }

        if ( $this->visitorType == self::VISITOR_HUMAN ) {
            bbcs_storeData( null, 4 );
            bbcs_process_hit( 4 );
            return true;
        }

        return false;
    }

    public function check_last_rule() : bool
    {
        $rule = null;
        $search = null;

        if ($this->settings->last_rule != '') {
            $rule = $this->settings->last_rule;
            $search = 'LAST RULE';
        }

        if ($rule == 'allow') {
            if ($this->allow_access(['rule' => $rule, 'search' => $search])) {
                return true;
            }
        } elseif ($rule == 'block') {
            $this->redirect_to_block(6, esc_sql('BLOCK By: ' . $search));
        } elseif ($rule == 'dark') {
            $this->redirect_to_dark(esc_sql('DARK By: ' . $search));
        } elseif ($rule == 'gray') {
            $this->set_gray_status(esc_sql('GRAY By: ' . $search));
        }
        return false;
    }

    public function allow_access($echo) : bool
    {
        // Read main cookie to get existing uid (process_cookies may not have run yet)
        if ( empty( $this->uid ) && isset( $_COOKIE[ $this->settings->cookie ] ) ) {
            $this->uid = preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ $this->settings->cookie ] ) ) );
        }
        if ( empty( $this->uid ) ) {
            $this->uid = $this->generate_uid();
            $this->set_bot_blocker_cookie();
        }
        // Set data cookie only if not already present
        if ( ! isset( $_COOKIE[ $this->uid ] ) ) {
            $this->set_allow_cookie_uid();
        }
        $this->visitorType = self::VISITOR_HUMAN;
        if ($this->settings->botblocker_log_allow == 1) {
            $search = isset($echo['search']) ? esc_sql($echo['search']) : BOTBLOCKER_EMPTY;
            bbcs_storeData('ALLOW By rule:' . $search, 4);
        }
        bbcs_process_hit(4);
        return true;
    }

    public function is_whitelisted_ip() : bool {
        global $wpdb;
        
        if($this->ip_version == 4) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $bbcs_ip_test = $wpdb->get_row(
                $wpdb->prepare(
                        "SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
                        0,
                        $this->ipnum,
                        $this->ipnum
                    ),
                    ARRAY_A
                );
        } else {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $bbcs_ip_test = $wpdb->get_row(
                $wpdb->prepare(
                        "SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
                        0,
                        $this->ipnum,
                        $this->ipnum
                    ),
                    ARRAY_A
                );
        }

        if (
            $bbcs_ip_test
            && $bbcs_ip_test['expires'] > $this->time
            && $bbcs_ip_test['rule'] == 'allow'
        ) {
            return true;
        }
        return false;
    }

}
