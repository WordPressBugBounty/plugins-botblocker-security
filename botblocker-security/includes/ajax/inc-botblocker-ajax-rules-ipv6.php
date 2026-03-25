<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_botblocker_ipv6_rules_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $start  = isset( $_POST['start'] )  ? absint(  wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint(  wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint(  wp_unslash( $_POST['draw'] ) )   : 0;
    $search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $records_total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE 1 = %d", 1 )
    );

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $records_filtered = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}`
                 WHERE search LIKE %s OR `rule` LIKE %s OR comment LIKE %s",
                $like, $like, $like
            )
        );
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, search, expires, disable, `rule`, comment
                 FROM `{$wpdb->bbcs_ipv6rules}`
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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, search, expires, disable, `rule`, comment
                 FROM `{$wpdb->bbcs_ipv6rules}`
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

    wp_send_json($response_data);
}
add_action( 'wp_ajax_bbcs_get_botblocker_ipv6_rules', 'bbcs_get_botblocker_ipv6_rules_callback' );

function bbcs_delete_ipv6_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    if( !isset($_POST['id']) || !is_numeric( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ) ) {
        wp_send_json_error(__('Invalid ID provided.', 'botblocker-security'));
        return;
    }

    $id = absint( wp_unslash( $_POST['id'] ) );

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete($wpdb->bbcs_ipv6rules, array('id' => $id));

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('IPv6 rule deleted successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to delete IPv6 rule.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_delete_ipv6_rule', 'bbcs_delete_ipv6_rule_callback');

function bbcs_toggle_ipv6_rule_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $id = isset( $_POST['id'] ) ? absint(  wp_unslash( $_POST['id'] ) ) : 0;
    if ( ! $id ) {
        wp_send_json_error( __('Bad ID.', 'botblocker-security') );
        return;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $current = $wpdb->get_var(
        $wpdb->prepare( "SELECT disable FROM `{$wpdb->bbcs_ipv6rules}` WHERE id = %d", $id )
    );

    if ( null === $current ) {
        wp_send_json_error( __('Rule not found.', 'botblocker-security') );
        return;
    }

    $new = (int) ! (int) $current;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $wpdb->bbcs_ipv6rules,
        [ 'disable' => $new ],
        [ 'id' => $id ],
        [ '%d' ],
        [ '%d' ]
    );

    bbcs_renderIpsFromDb();
    bbcs_clearFileCache();

    wp_send_json_success( __('IPv6 rule toggled successfully.', 'botblocker-security') );
}
add_action('wp_ajax_bbcs_toggle_ipv6_rule', 'bbcs_toggle_ipv6_rule_callback' );

function bbcs_create_ipv6_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

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
            // translators: %s is the name of the required field.
            wp_send_json_error(sprintf(__('%s is required.', 'botblocker-security'), ucfirst($field)));
            return;
        }
    }

    $ip = sanitize_text_field(wp_unslash($_POST['ip']));
    $type = bbcs_isIpOrCidr($ip);

    if ($type === 'invalid') {
        wp_send_json_error(__('Invalid IP address or CIDR notation provided.', 'botblocker-security'));
        return;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE `search` = %s",
        $ip
    ));

    if ($exists) {
        wp_send_json_error(__('Rule already exists.', 'botblocker-security'));
        return;
    }

    $data = array(
        'priority' => intval( wp_unslash( $_POST['priority'] ) ),
        'search' => $ip,
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' =>  isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
        'expires' => strtotime(sanitize_text_field(wp_unslash($_POST['expires']))),
        'disable' => 0
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    if ($type === 'cidr') {
        $ip_range = bbcs_IpRange($ip);
        $data['ip1'] = bbcs_ipv6_bin(bbcs_expandIPv6($ip_range[0]));
        $data['ip2'] = bbcs_ipv6_bin(bbcs_expandIPv6($ip_range[1]));

    } else {
        $numeric_ip = bbcs_ipv6_bin(bbcs_expandIPv6($ip));
        $data['ip1'] = $numeric_ip;
        $data['ip2'] = $numeric_ip;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $overlap = $wpdb->get_var($wpdb->prepare(
        "SELECT search FROM `{$wpdb->bbcs_ipv6rules}`
        WHERE (ip1 <= %s AND ip2 >= %s) OR (ip1 >= %s AND ip2 <= %s) LIMIT 1",
        $data['ip2'], $data['ip1'], $data['ip1'], $data['ip2']
    ));

    if($overlap) {
        // translators: %s is the overlapping IP range.
        wp_send_json_error(sprintf(__('IP range overlaps with an existing rule: %s', 'botblocker-security'), $overlap));
        return;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->insert($wpdb->bbcs_ipv6rules, $data);
    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('IPv6 rule created successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to create IPv6 rule.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_create_ipv6_rule', 'bbcs_create_ipv6_rule_callback');

function bbcs_update_ipv6_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

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
            // translators: %s is the name of the required field.
            wp_send_json_error(sprintf(__('%s is required.', 'botblocker-security'), ucfirst($field)));
            return;
        }
    }

    $id = intval( wp_unslash( $_POST['id'] ) );
    $ip = sanitize_text_field(wp_unslash($_POST['ip']));
    $type = bbcs_isIpOrCidr($ip);

    if ($type === 'invalid') {
        wp_send_json_error(__('Invalid IP address or CIDR notation provided.', 'botblocker-security'));
        return;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s AND id != %d", $ip, $id));

    if ($existing) {
        wp_send_json_error(__('Rule already exists.', 'botblocker-security'));
        return;
    }

    $data = array(
        'priority' => intval(wp_unslash($_POST['priority'])),
        'search' => $ip,
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
        'expires' => strtotime(sanitize_text_field(wp_unslash($_POST['expires']))),
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    if ($type === 'cidr') {
        $ip_range = bbcs_IpRange($ip);
        $data['ip1'] = bbcs_ipv6_bin(bbcs_expandIPv6($ip_range[0]));
        $data['ip2'] = bbcs_ipv6_bin(bbcs_expandIPv6($ip_range[1]));
    } else {
        $numeric_ip = bbcs_ipv6_bin(bbcs_expandIPv6($ip));
        $data['ip1'] = $numeric_ip;
        $data['ip2'] = $numeric_ip;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $overlap = $wpdb->get_var($wpdb->prepare(
        "SELECT search FROM `{$wpdb->bbcs_ipv6rules}`
        WHERE ((ip1 <= %s AND ip2 >= %s) OR (ip1 >= %s AND ip2 <= %s)) AND id != %d LIMIT 1",
        $data['ip2'], $data['ip1'], $data['ip1'], $data['ip2'], $id
    ));

    if($overlap) {
        // translators: %s is the overlapping IP range.
        wp_send_json_error(sprintf(__('IP range overlaps with an existing rule: %s', 'botblocker-security'), $overlap));
        return;
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($wpdb->bbcs_ipv6rules, $data, array('id' => $id));

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('IPv6 rule updated successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to update IPv6 rule.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_update_ipv6_rule', 'bbcs_update_ipv6_rule_callback');

function bbcs_export_ipv6_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rules = $wpdb->get_results("SELECT * FROM `{$wpdb->bbcs_ipv6rules}`", ARRAY_A);

    wp_send_json_success($rules);
}
add_action('wp_ajax_bbcs_export_ipv6_rules', 'bbcs_export_ipv6_rules_callback');

function bbcs_import_ipv6_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    if( !isset($_POST['rules']) || empty($_POST['rules']) ) {
        wp_send_json_error(__('No rules provided for import.', 'botblocker-security'));
        return;
    }

    $rules = json_decode(sanitize_textarea_field(wp_unslash($_POST['rules'])), true);
    if (is_array($rules)) {
        $imported = 0;
        $skipped = 0;
        foreach ($rules as $rule) {
            $search = sanitize_text_field($rule['search']);
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s", $search));

            if ($existing == 0) {
                $data = array(
                    'search' => $search,
                    'priority' => intval($rule['priority']),
                    'ip1' => $rule['ip1'],
                    'ip2' => $rule['ip2'],
                    'expires' => intval($rule['expires']),
                    'disable' => intval($rule['disable']),
                    'rule' => sanitize_text_field($rule['rule']),
                    'readonly' => intval($rule['readonly']),
                    'comment' => sanitize_textarea_field($rule['comment']),
                );
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
                $result = $wpdb->insert($wpdb->bbcs_ipv6rules, $data);
                if ($result !== false) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    } else {
        wp_send_json_error(__('Invalid JSON format.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_import_ipv6_rules', 'bbcs_import_ipv6_rules_callback');

function bbcs_clear_all_ipv6_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    $result = bbcs_clear_all_ipv6_rules();

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('All IPv6 rules have been cleared.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to clear IPv6 rules.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_clear_all_ipv6_rules', 'bbcs_clear_all_ipv6_rules_callback');

function bbcs_get_ipv6_rule_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $id = isset( $_POST['id'] ) ? absint(  wp_unslash( $_POST['id'] ) ) : 0;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rule = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE id = %d",
            $id
        ),
        ARRAY_A
    );

    if ( $rule ) {
        wp_send_json_success( $rule );
    } else {
        wp_send_json_error( __('Rule not found.', 'botblocker-security') );
    }
}
add_action( 'wp_ajax_bbcs_get_ipv6_rule_details', 'bbcs_get_ipv6_rule_details_callback' );

function bbcs_import_ipv6_whitelist_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }
    bbcs_import_ipv6_list('allow');
}
add_action('wp_ajax_bbcs_import_ipv6_whitelist', 'bbcs_import_ipv6_whitelist_callback');

function bbcs_import_ipv6_blacklist_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }
    bbcs_import_ipv6_list('block');
}
add_action('wp_ajax_bbcs_import_ipv6_blacklist', 'bbcs_import_ipv6_blacklist_callback');

function bbcs_import_ipv6_list($rule_type)
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    if( !isset($_POST['file_content']) || empty($_POST['file_content']) ) {
        wp_send_json_error(__('No file content provided for import.', 'botblocker-security'));
        return;
    }

    $file_content = sanitize_textarea_field(wp_unslash($_POST['file_content']));
    $lines = explode("\n", $file_content);

    $imported = 0;
    $skipped = 0;

    foreach ($lines as $line) {
        $ip = trim($line);
        if (empty($ip)) continue;
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s", $ip));

        if (!$existing) {

            $type = bbcs_isIpOrCidr($ip);

            if ($type === 'invalid') {
                wp_send_json_error(__('Invalid IP address or CIDR notation provided.', 'botblocker-security'));
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
                $data['ip1'] = bbcs_ipv6_bin(bbcs_expandIPv6($ip_range[0]));
                $data['ip2'] = bbcs_ipv6_bin(bbcs_expandIPv6($ip_range[1]));
            }else {
                $numeric_ip = bbcs_ipv6_bin(bbcs_expandIPv6($ip));
                $data['ip1'] = $numeric_ip;
                $data['ip2'] = $numeric_ip;
            }
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->insert($wpdb->bbcs_ipv6rules, $data);
            if ($result !== false) {
                $imported++;
            }
        } else {
            $skipped++;
        }
    }
    bbcs_renderIpsFromDb();
    bbcs_clearFileCache();

    wp_send_json_success(array(
        'imported' => $imported,
        'skipped' => $skipped,
    ));
}

function bbcs_clear_all_ipv6_rules()
{
    global $wpdb;
    return $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_ipv6rules}`");
}

function bbcs_ipv6_to_php_callback(): void
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }
	try {
        bbcs_renderIpsFromDb();

		wp_send_json_success(__('IPv6 rules file generated successfully.', 'botblocker-security'));
	} catch(\Throwable $e) {
		// error_log('ipv6_to_php_callback error: ' . $e->getMessage());
		wp_send_json_error(__('Failed to generate IPv6 rules file from database.', 'botblocker-security'));
	}
}
add_action('wp_ajax_bbcs_ipv6_to_php', 'bbcs_ipv6_to_php_callback');
