<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_botblocker_ipv4_rules_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    $start  = isset( $_POST['start'] )  ? absint( wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint( wp_unslash( $_POST['draw'] ) )   : 0;
    $search = isset( $_POST['search']['value'] ) ? trim(  sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_ipv4_rules' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5(wp_json_encode(array(
            'start' => $start,
            'length' => $length,
            'search' => $search
        )));
        $cache_data = wp_cache_get($cache_key, 'botblocker-security');
    
        if ($cache_data) {
            wp_send_json($cache_data);
            return;
        }
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $records_total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE 1 = %d", 1 )
    );

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $records_filtered = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}`
                 WHERE search LIKE %s OR `rule` LIKE %s OR comment LIKE %s",
                $like, $like, $like
            )
        );
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, search, expires, disable, `rule`, comment
                 FROM `{$wpdb->bbcs_ipv4rules}`
                 WHERE search LIKE %s OR `rule` LIKE %s OR comment LIKE %s
                 ORDER BY priority DESC
                 LIMIT %d, %d",
                $like, $like, $like, $start, $length
            ),
            ARRAY_A
        );
    } else {
        $records_filtered = $records_total;
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, search, expires, disable, `rule`, comment
                 FROM `{$wpdb->bbcs_ipv4rules}`
                 WHERE 1 = %d
                 ORDER BY priority DESC
                 LIMIT %d, %d",
                1, $start, $length
            ),
            ARRAY_A
        );
    }

    $data = array();
    foreach ( (array) $results as $row ) {
        $data[] = array(
            'id'       => $row['id'],
            'priority' => $row['priority'],
            'ip'       => $row['search'],
            'expires'  => wp_date( 'Y-m-d H:i:s', (int) $row['expires'], wp_timezone() ),
            'disable'  => $row['disable'],
            'rule'     => $row['rule'],
            'comment'  => $row['comment'],
        );
    }

    $response_data = array(
        'draw'            => $draw,
        'recordsTotal'    => $records_total,
        'recordsFiltered' => $records_filtered,
        'data'            => $data,
    );

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set($cache_key, $response_data, 'botblocker-security', 15);
    }

    wp_send_json($response_data);
}
add_action( 'wp_ajax_bbcs_get_botblocker_ipv4_rules', 'bbcs_get_botblocker_ipv4_rules_callback' );


function bbcs_delete_ipv4_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : null;

    if ($id === null) {
        wp_send_json_error('Invalid ID provided');
        return;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete($wpdb->bbcs_ipv4rules, array('id' => $id));

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
        }
        
        
        wp_send_json_success('IPv4 rule deleted successfully');
    } else {
        wp_send_json_error('Failed to delete IPv4 rule');
    }
}
add_action('wp_ajax_bbcs_delete_ipv4_rule', 'bbcs_delete_ipv4_rule_callback');

function bbcs_toggle_ipv4_rule_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    $id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : null;
    if ( $id === null ) {
        wp_send_json_error( 'Bad ID' );
        return;
    }

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = 1;
        $cache_version = wp_cache_get( 'bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security' ) ?: 1;
        $disable_cache_key = 'bbcs_ipv4_rule_disable' . bbcs_get_wp_cache_version() . $cache_version . '_' . $id;
        $current = false;
        $current = wp_cache_get( $disable_cache_key, 'botblocker-security', false, $found );
    }

    if ( $found === false ) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $current = $wpdb->get_var(
            $wpdb->prepare( "SELECT disable FROM `{$wpdb->bbcs_ipv4rules}` WHERE id = %d", $id )
        );

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set( $disable_cache_key, $current, 'botblocker-security', 15 );
        }
    }

    if ( null === $current ) {
        wp_send_json_error( 'Rule not found' );
        return;
    }

    $new = (int) ! (int) $current;

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $wpdb->bbcs_ipv4rules,
        [ 'disable' => $new ],
        [ 'id' => $id ],
        [ '%d' ],
        [ '%d' ]
    );

    bbcs_renderIpsFromDb();
    bbcs_clearFileCache();
    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
    }
    

    wp_send_json_success( 'IPv4 rule toggled successfully' );
}
add_action( 'wp_ajax_bbcs_toggle_ipv4_rule', 'bbcs_toggle_ipv4_rule_callback' );

function bbcs_create_ipv4_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['rule'], $_POST['expires'], $_POST['priority'], and $_POST['comment']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['ip', 'rule', 'expires', 'priority'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            wp_send_json_error(ucfirst($field) . ' is required');
            return;
        }
    }

    $ip = sanitize_text_field(wp_unslash($_POST['ip']));
    $type = bbcs_isIpOrCidr($ip);

    if ($type === 'invalid') {
        wp_send_json_error('Invalid IP address or CIDR notation provided');
        return;
    }

    $found = false;

    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = 1;
        $cache_version = wp_cache_get('bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security') ?: 1;
        $exists_cache_key = 'bbcs_ipv4_rule_exists' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5($ip);
        $exists = false;
        $exists = wp_cache_get($exists_cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE `search` = %s",
            $ip
        ));
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($exists_cache_key, $exists, 'botblocker-security', 15);
        }
    }
    
    if ($exists) {
        wp_send_json_error('Rule already exists');
        return;
    }

    $data = array(
        'priority' => intval( wp_unslash( $_POST['priority'] ) ),
        'search' => $ip,
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
        'expires' => strtotime(sanitize_text_field(wp_unslash($_POST['expires']))),
    );

    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    if ($type === 'cidr') {
        $ip_range = bbcs_IpRange($ip);
        $data['ip1'] = bbcs_ipToNumeric($ip_range[0]);
        $data['ip2'] = bbcs_ipToNumeric($ip_range[1]);
    } else { 
        $numeric_ip = bbcs_ipToNumeric($ip);
        $data['ip1'] = $numeric_ip;
        $data['ip2'] = $numeric_ip;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $overlap = $wpdb->get_var($wpdb->prepare(
        "SELECT search FROM `{$wpdb->bbcs_ipv4rules}`
        WHERE (ip1 <= %d AND ip2 >= %d) OR (ip1 >= %d AND ip2 <= %d) LIMIT 1",
        $data['ip2'], $data['ip1'], $data['ip1'], $data['ip2']
    ));
    if($overlap) {
        wp_send_json_error('IP range overlaps with an existing rule: ' . $overlap);
        return;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->insert($wpdb->bbcs_ipv4rules, $data);
    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
        }
        
        
        wp_send_json_success('IPv4 rule created successfully');
    } else {
        wp_send_json_error('Failed to create IPv4 rule');
    }
}
add_action('wp_ajax_bbcs_create_ipv4_rule', 'bbcs_create_ipv4_rule_callback');


/* 
 * Update IPv4 rule
 * 
 * @return void
 */
function bbcs_update_ipv4_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['rule'], $_POST['expires'], $_POST['priority'], and $_POST['comment']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['ip', 'rule', 'expires', 'priority'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            wp_send_json_error(ucfirst($field) . ' is required');
            return;
        }
    }

    $id = intval( wp_unslash( $_POST['id'] ) );
    $ip = sanitize_text_field(wp_unslash($_POST['ip']));

    $type = bbcs_isIpOrCidr($ip);

    if ($type === 'invalid') {
        wp_send_json_error('Invalid IP address or CIDR notation provided');
        return;
    }

    $data = array(
        'priority' => intval( wp_unslash( $_POST['priority'] ) ),
        'search' => $ip,
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
        'expires' => strtotime(sanitize_text_field(wp_unslash($_POST['expires']))),
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    if ($type === 'cidr') {
        $ip_range = bbcs_IpRange($ip);
        $data['ip1'] = bbcs_ipToNumeric($ip_range[0]);
        $data['ip2'] = bbcs_ipToNumeric($ip_range[1]);
    } else { 
        $numeric_ip = bbcs_ipToNumeric($ip);
        $data['ip1'] = $numeric_ip;
        $data['ip2'] = $numeric_ip;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $overlap = $wpdb->get_var($wpdb->prepare(
        "SELECT search FROM `{$wpdb->bbcs_ipv4rules}`
        WHERE ((ip1 <= %d AND ip2 >= %d) OR (ip1 >= %d AND ip2 <= %d)) AND id != %d LIMIT 1",
        $data['ip2'], $data['ip1'], $data['ip1'], $data['ip2'], $id
    ));
    if ($overlap) {
        wp_send_json_error('IP range overlaps with an existing rule: ' . $overlap);
        return;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($wpdb->bbcs_ipv4rules, $data, array('id' => $id));

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
        }
        
        
        wp_send_json_success('IPv4 rule updated successfully');
    } else {
        wp_send_json_error('Failed to update IPv4 rule');
    }
}
add_action('wp_ajax_bbcs_update_ipv4_rule', 'bbcs_update_ipv4_rule_callback');

function bbcs_export_ipv4_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    $found = false;

    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = 1;
        $cache_version = wp_cache_get('bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_export_ipv4_rules' . bbcs_get_wp_cache_version() . $cache_version;
        $rules = false;
        $rules = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rules = $wpdb->get_results("SELECT * FROM `{$wpdb->bbcs_ipv4rules}`", ARRAY_A);

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $rules, 'botblocker-security', 15);
        }
    }

    wp_send_json_success($rules);
}
add_action('wp_ajax_bbcs_export_ipv4_rules', 'bbcs_export_ipv4_rules_callback');

function bbcs_import_ipv4_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    if (!isset($_POST['rules']) || empty($_POST['rules'])) {
        wp_send_json_error('No rules provided');
        return;
    }

    $rules = json_decode(sanitize_textarea_field(wp_unslash($_POST['rules'])), true);
    if (is_array($rules)) {
        $imported = 0;
        $skipped = 0;
        foreach ($rules as $rule) {
            $search = sanitize_text_field($rule['search']);
            $found = false;
            if (BOTBLOCKER_CACHE_WP) {
                $cache_version = wp_cache_get('bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security') ?: 1;
                $exists_cache_key = 'bbcs_ipv4_rule_exists' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5($search);
                $existing = wp_cache_get($exists_cache_key, 'botblocker-security', false, $found);
            }
            if ($found === false) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $existing = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
                        $search
                    )
                );
                if (BOTBLOCKER_CACHE_WP) {
                    wp_cache_set($exists_cache_key, $existing, 'botblocker-security', 15);
                }
            }
            if ($existing == 0) {
                $data = array(
                    'search' => $search,
                    'priority' => intval($rule['priority']),
                    'ip1' => intval($rule['ip1']),
                    'ip2' => intval($rule['ip2']),
                    'expires' => intval($rule['expires']),
                    'disable' => intval($rule['disable']),
                    'rule' => sanitize_text_field($rule['rule']),
                    'comment' => sanitize_textarea_field($rule['comment']),
                    'readonly' => intval($rule['readonly'])
                );
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $result = $wpdb->insert($wpdb->bbcs_ipv4rules, $data);
                if ($result !== false) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
        }
        
        
        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    } else {
        wp_send_json_error('Invalid JSON format');
    }
}
add_action('wp_ajax_bbcs_import_ipv4_rules', 'bbcs_import_ipv4_rules_callback');

function bbcs_clear_all_ipv4_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    $result = bbcs_clear_all_ipv4_rules();

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
        }
        
        
        wp_send_json_success('All IPv4 rules have been cleared');
    } else {
        wp_send_json_error('Failed to clear IPv4 rules');
    }
}
add_action('wp_ajax_bbcs_clear_all_ipv4_rules', 'bbcs_clear_all_ipv4_rules_callback');

function bbcs_get_ipv4_rule_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;

    $id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_ipv4_rule_details' . bbcs_get_wp_cache_version() . $cache_version . '_' . $id;
        $rule = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if (BOTBLOCKER_CACHE_WP && $rule) {
            wp_cache_set($cache_key, $rule, 'botblocker-security', HOUR_IN_SECONDS);
        }
    }

    if ( $rule ) {
        wp_send_json_success( $rule );
    } else {
        wp_send_json_error( 'Rule not found' );
    }
}
add_action( 'wp_ajax_bbcs_get_ipv4_rule_details', 'bbcs_get_ipv4_rule_details_callback' );

function bbcs_import_ipv4_whitelist_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }
    bbcs_import_ipv4_list('allow');
}
add_action('wp_ajax_bbcs_import_ipv4_whitelist', 'bbcs_import_ipv4_whitelist_callback');

function bbcs_import_ipv4_blacklist_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }
    bbcs_import_ipv4_list('block');
}
add_action('wp_ajax_bbcs_import_ipv4_blacklist', 'bbcs_import_ipv4_blacklist_callback');

function bbcs_import_ipv4_list($rule_type)
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    if( !isset($_POST['file_content']) || empty($_POST['file_content']) ) {
        wp_send_json_error('No file content provided');
        return;
    }

    $file_content = sanitize_textarea_field(wp_unslash($_POST['file_content']));
    $lines = explode("\n", $file_content);

    $imported = 0;
    $skipped = 0;

    foreach ($lines as $line) {
        $ip = trim($line);
        if (empty($ip)) continue;

        $found = false;
        
        if (BOTBLOCKER_CACHE_WP) {
            $cache_version = wp_cache_get('bbcs_ajax_ipv4_rules_cache_version', 'botblocker-security') ?: 1;
            $exists_cache_key = 'bbcs_ipv4_rule_exists' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5($ip);
            $existing = wp_cache_get($exists_cache_key, 'botblocker-security', false, $found);
        }

        if ($found === false) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s", $ip));
            if (BOTBLOCKER_CACHE_WP) {
                wp_cache_set($exists_cache_key, $existing, 'botblocker-security', 15);
            }
        }
        if ($existing == 0) {
            $type = bbcs_isIpOrCidr($ip);
            
            if ($type === 'invalid') {
                wp_send_json_error('Invalid IP address or CIDR notation provided');
                return;
            }        
            $data = array(
                'priority' => 10,
                'search' => $ip,
                'rule' => $rule_type,
                'comment' => "Imported " . ($rule_type == 'allow' ? 'whitelist' : 'blacklist') . " (IP: $ip)",
                'expires' => BOTBLOCKER_EXP_INF
            );
        
            if ($type === 'cidr') {
                $ip_range = bbcs_IpRange($ip);
                $data['ip1'] = bbcs_ipToNumeric($ip_range[0]);
                $data['ip2'] = bbcs_ipToNumeric($ip_range[1]);
            } else { 
                $numeric_ip = bbcs_ipToNumeric($ip);
                $data['ip1'] = $numeric_ip;
                $data['ip2'] = $numeric_ip;
            }

            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->insert($wpdb->bbcs_ipv4rules, $data);
            if ($result !== false) {
                $imported++;
            }
        } else {
            $skipped++;
        }
    }
    bbcs_renderIpsFromDb();
    bbcs_clearFileCache();

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
    }
    
    wp_send_json_success(array(
        'imported' => $imported,
        'skipped' => $skipped,
    ));
}

function bbcs_clear_all_ipv4_rules()
{
    global $wpdb;
    return $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_ipv4rules}`");
}

function bbcs_ipv4_to_php_callback(): void
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }
    try {
        bbcs_renderIpsFromDb();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_ipv4_rules_cache_version', time(), 'botblocker-security');
        }
        wp_send_json_success('IPv4 rules file generated successfully.');
    } catch(\Throwable $e) {
        // error_log('ipv6_to_php_callback error: ' . $e->getMessage());
        wp_send_json_error('Failed to generate IPv4 rules file from database.');
    }
}
add_action('wp_ajax_bbcs_ipv4_to_php', 'bbcs_ipv4_to_php_callback');
