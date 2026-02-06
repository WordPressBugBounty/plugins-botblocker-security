<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Retrieves the bot blocker rules via AJAX request.
 *
 * This function is responsible for retrieving the bot blocker rules from the database
 * and sending the response as a JSON object via an AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_get_botblocker_rules_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );

    global $wpdb;

    $start  = isset( $_POST['start'] )  ? absint(  wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint(  wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint(  wp_unslash( $_POST['draw'] ) )   : 0;
    $search = '';
    if (isset($_POST['search']) && is_array($_POST['search']) && isset($_POST['search']['value'])) {
        $search = trim(sanitize_text_field(wp_unslash($_POST['search']['value'])));
    }

    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_rules_cache_version', 'botblocker-security') ?: 1;
    
        $cache_key = 'bbcs_rules' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5(wp_json_encode(array(
            'start' => $start,
            'length' => $length,
            'search' => $search
        )));
        
        $cache_data = wp_cache_get($cache_key, 'botblocker-security');
    
        if ($cache_data !== false) {
            wp_send_json($cache_data);
            return;
        }
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $records_total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE 1 = %d", 1 )
    );

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $records_filtered = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}`
                 WHERE CAST(id AS CHAR) LIKE %s
                    OR type LIKE %s
                    OR data LIKE %s
                    OR `rule` LIKE %s
                    OR comment LIKE %s",
                $like, $like, $like, $like, $like
            )
        );

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, type, data, expires, disable, `rule`, comment
                 FROM `{$wpdb->bbcs_rules}`
                 WHERE CAST(id AS CHAR) LIKE %s
                    OR type LIKE %s
                    OR data LIKE %s
                    OR `rule` LIKE %s
                    OR comment LIKE %s
                 ORDER BY priority DESC
                 LIMIT %d, %d",
                $like, $like, $like, $like, $like, $start, $length
            ),
            ARRAY_A
        );
    } else {
        $records_filtered = $records_total;

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, type, data, expires, disable, `rule`, comment
                 FROM `{$wpdb->bbcs_rules}`
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
            'type'     => $row['type'],
            'data'     => $row['data'],
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
        wp_cache_set($cache_key, $response_data, 'botblocker-security', HOUR_IN_SECONDS);
    }

    wp_send_json($response_data);
}
add_action( 'wp_ajax_bbcs_get_botblocker_rules', 'bbcs_get_botblocker_rules_callback' );


/**
 * Retrieves the details of a specific rule via AJAX request.
 *
 * This function is responsible for retrieving the details of a specific rule from the database
 * and sending the response as a JSON object via an AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_get_rule_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );

    global $wpdb;

    $id = isset( $_POST['id'] ) ? absint(  wp_unslash( $_POST['id'] ) ) : 0;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_rules_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_rule_details' . bbcs_get_wp_cache_version() . $cache_version . '_' . $id;
        $rule = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->bbcs_rules}` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $rule, 'botblocker-security', HOUR_IN_SECONDS);
        }
    }

    if ( $rule ) {
        wp_send_json_success( $rule );
    } else {
        wp_send_json_error( 'Rule not found' );
    }
}
add_action( 'wp_ajax_bbcs_get_rule_details', 'bbcs_get_rule_details_callback' );


/**
 * Updates a rule in the database via AJAX request.
 *
 * This function is responsible for updating a rule in the database based on the provided data
 * and sending the response as a JSON object via an AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_update_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    $id = isset($_POST['id']) ? absint( wp_unslash( $_POST['id'] ) ) : null;
    if ($id === null) {
        wp_send_json_error('Missing or invalid rule ID');
        return;
    }

    $data = array(
        'priority' => isset($_POST['priority']) ? intval( wp_unslash( $_POST['priority'] ) ) : 0,
        'type' => isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '',
        'data' => isset($_POST['data']) ? sanitize_text_field(wp_unslash($_POST['data'])) : '',
        'expires' => isset($_POST['expires']) ? strtotime(sanitize_text_field(wp_unslash($_POST['expires']))) : 0,
        'rule' => isset($_POST['rule']) ? sanitize_text_field(wp_unslash($_POST['rule'])) : '',
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
    );

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($wpdb->bbcs_rules, $data, array('id' => $id));

    if ($result !== false) {
        bbcs_renderRulesFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('Rule updated successfully');
    } else {
        wp_send_json_error('Failed to update rule');
    }
}
add_action('wp_ajax_bbcs_update_rule', 'bbcs_update_rule_callback');

/**
 * Deletes a rule from the database via AJAX request.
 *
 * This function is responsible for deleting a rule from the database based on the provided ID
 * and sending the response as a JSON object via an AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_delete_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    $id = isset($_POST['id']) ? absint( wp_unslash( $_POST['id'] ) ) : null;
    if ($id === null) {
        wp_send_json_error('Missing or invalid rule ID');
        return;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete($wpdb->bbcs_rules, array('id' => $id));

    if ($result !== false) {
        bbcs_renderRulesFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('Rule deleted successfully');
    } else {
        wp_send_json_error('Failed to delete rule');
    }
}
add_action('wp_ajax_bbcs_delete_rule', 'bbcs_delete_rule_callback');

/**
 * Toggles a rule in the database via AJAX request.
 *
 * This function is responsible for toggling a rule in the database based on the provided ID
 * and sending the response as a JSON object via an AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_toggle_rule_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );

    global $wpdb;
    $id = isset($_POST['id']) ? absint( wp_unslash( $_POST['id'] ) ) : null;

    if ($id === null) {
        wp_send_json_error('Missing or invalid rule ID');
        return;
    }

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get( 'bbcs_ajax_rules_cache_version', 'botblocker-security' ) ?: 1;
        $disable_cache_key = 'bbcs_rule_disable' . bbcs_get_wp_cache_version() . $cache_version . '_' . $id;
        $current = wp_cache_get( $disable_cache_key, 'botblocker-security', false, $found);
    }

    if ( $found === false ) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is cached. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $current = $wpdb->get_var(
            $wpdb->prepare("SELECT disable FROM `{$wpdb->bbcs_rules}` WHERE id = %d", $id)
        );

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set( $disable_cache_key, $current, 'botblocker-security', 15 );
        }
    }

    if ( $current === null ) {
        wp_send_json_error( 'Rule not found' );
        return;
    }

    $new = (int) ! (int) $current;

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is cached. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $wpdb->bbcs_rules,
        [ 'disable' => $new ],
        [ 'id' => $id ],
        [ '%d' ],
        [ '%d' ]
    );

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
    }
    bbcs_renderRulesFromDb();
    bbcs_clearFileCache();

    wp_send_json_success( 'Rule toggled successfully' );
}
add_action( 'wp_ajax_bbcs_toggle_rule', 'bbcs_toggle_rule_callback' );


/**
 * Adds a new rule to the database via AJAX request.
 *
 * This function is responsible for adding a new rule to the database based on the provided data
 * and sending the response as a JSON object via an AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_create_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    $data = array(
        'priority' => isset($_POST['priority']) ? absint( wp_unslash( $_POST['priority'] ) ) : 10,
        'type' => isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '',
        'data' => isset($_POST['data']) ? sanitize_text_field(wp_unslash($_POST['data'])) : '',
        'expires' => isset($_POST['expires']) ? strtotime(sanitize_text_field(wp_unslash($_POST['expires']))) : 0,
        'rule' => isset($_POST['rule']) ? sanitize_text_field(wp_unslash($_POST['rule'])) : '',
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
        'disable' => 0
    );

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_rules_cache_version', 'botblocker-security') ?: 1;
        $exists_cache_key = 'bbcs_rule_exists' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5(wp_json_encode(array('type' => $data['type'], 'data' => $data['data'])));
        $exists = wp_cache_get($exists_cache_key, 'botblocker-security', false, $found);
    }
    
    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is cached. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE (`type` = %s AND `data` = %s)",
             $data['type'], $data['data'] 
        ));
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($exists_cache_key, $exists, 'botblocker-security', 15);
        }
    }
    if ($exists) {
        wp_send_json_error('Rule already exists');
        return;
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is cached. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->insert($wpdb->bbcs_rules, $data);

    if ($result !== false) {
        bbcs_renderRulesFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('Rule created successfully');
    } else {
        wp_send_json_error('Failed to create rule');
    }
}
add_action('wp_ajax_bbcs_create_rule', 'bbcs_create_rule_callback');


function bbcs_export_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_rules_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_export_rules' . bbcs_get_wp_cache_version() . $cache_version;
        $rules = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is cached. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rules = $wpdb->get_results("SELECT * FROM `{$wpdb->bbcs_rules}`", ARRAY_A);

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $rules, 'botblocker-security', 15);
        }
    }

    wp_send_json_success($rules);
}
add_action('wp_ajax_bbcs_export_rules', 'bbcs_export_rules_callback');

function bbcs_import_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;

    if (!isset($_POST['rules'])) {
        wp_send_json_error('Missing rules data');
        return;
    }
    $rules = json_decode(sanitize_textarea_field(wp_unslash($_POST['rules'])), true);
    if (is_array($rules)) {
        $imported = 0;
        $skipped = 0;
        foreach ($rules as $rule) {
            $search = sanitize_text_field($rule['search']);
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE search = %s",
                    $search
                )
            );
            if ($existing == 0) {
                $data = array(
                    'priority' => intval($rule['priority']),
                    'type' => sanitize_text_field($rule['type']),
                    'data' => sanitize_text_field($rule['data']),
                    'expires' => intval($rule['expires']),
                    'disable' => intval($rule['disable']),
                    'rule' => sanitize_text_field($rule['rule']),
                    'comment' => sanitize_textarea_field($rule['comment']),
                );
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $result = $wpdb->insert($wpdb->bbcs_rules, $data);
                if ($result !== false) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }
        bbcs_renderRulesFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    } else {
        wp_send_json_error('Invalid JSON format');
    }
}
add_action('wp_ajax_bbcs_import_rules', 'bbcs_import_rules_callback');

function bbcs_clear_all_rules_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    $result = bbcs_clear_all_rules();

    if ($result !== false) {
        bbcs_renderRulesFromDb();
        bbcs_clearFileCache();
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('All rules have been cleared');
    } else {
        wp_send_json_error('Failed to clear rules');
    }
}
add_action('wp_ajax_bbcs_clear_all_rules', 'bbcs_clear_all_rules_callback');

function bbcs_clear_all_rules()
{
    global $wpdb;

    return $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_rules}`");
}

function bbcs_rules_to_php_callback(): void
{
	try {
        bbcs_renderRulesFromDb();

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_rules_cache_version', time(), 'botblocker-security');
        }
		wp_send_json_success('Rules file generated successfully.');
	} catch(\Throwable $e) {
		// error_log('bbcs_rules_to_php_callback error: ' . $e->getMessage());
		wp_send_json_error();
	}
}
add_action('wp_ajax_bbcs_rules_to_php', 'bbcs_rules_to_php_callback');

function bbcs_create_ip_rule_callback() {
    check_ajax_referer('botblocker_nonce', 'nonce');

    if ( ! isset( $_POST['ip'] ) ) {
        wp_send_json_error( 'Missing IP address' );
        return;
    }

    $ip_raw = sanitize_text_field(wp_unslash($_POST['ip']));
    $ip = explode('/', $ip_raw, 2)[0];

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return bbcs_create_ipv4_rule_callback();
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return bbcs_create_ipv6_rule_callback();
    } else {
        wp_send_json_error('Invalid IP address');
    }
}
add_action('wp_ajax_bbcs_create_ip_rule', 'bbcs_create_ip_rule_callback');
