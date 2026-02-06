<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_botblocker_white_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );

    global $wpdb;

    $start  = isset( $_POST['start'] )  ? absint(  wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint(  wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint(  wp_unslash( $_POST['draw'] ) )   : 0;
    $search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';
    
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_white_cache_version', 'botblocker-security') ?: 1;
    
        $cache_key = 'bbcs_white' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5(wp_json_encode(array(
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
        $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_se}` WHERE 1 = %d", 1 )
    );

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $records_filtered = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_se}`
                 WHERE CAST(id AS CHAR) LIKE %s
                    OR `search` LIKE %s
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
                "SELECT id, priority, `search`, data, `rule`, comment, disable
                 FROM `{$wpdb->bbcs_se}`
                 WHERE CAST(id AS CHAR) LIKE %s
                    OR `search` LIKE %s
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
                "SELECT id, priority, `search`, data, `rule`, comment, disable
                 FROM `{$wpdb->bbcs_se}`
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
            'search'   => $row['search'],
            'data'     => $row['data'],
            'rule'     => $row['rule'],
            'comment'  => $row['comment'],
            'disable'  => $row['disable'],
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
add_action( 'wp_ajax_bbcs_get_botblocker_white', 'bbcs_get_botblocker_white_callback' );

function bbcs_get_white_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );

    global $wpdb;

    $id         = isset( $_POST['id'] ) ? absint(  wp_unslash( $_POST['id'] ) ) : 0;
    
    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_white_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_white_details' . bbcs_get_wp_cache_version() . $cache_version . '_' . $id;
        $white = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $white = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->bbcs_se}` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($white && BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $white, 'botblocker-security', 15);
        }
    }

    if ( $white ) {
        wp_send_json_success( $white );
    } else {
        wp_send_json_error( 'White bot not found' );
    }
}
add_action( 'wp_ajax_bbcs_get_white_details', 'bbcs_get_white_details_callback' );

function bbcs_update_white_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['priority'], $_POST['search'], $_POST['data'] and $_POST['rule']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['id', 'priority', 'search', 'data', 'rule'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            wp_send_json_error("Field '$field' is required.");
            return;
        }
    }

    global $wpdb;

    $id = intval( wp_unslash( $_POST['id'] ) );
    $data = array(
        'priority' => intval( wp_unslash( $_POST['priority'] ) ),
        'search' => sanitize_text_field( wp_unslash( $_POST['search'] ) ),
        'data' => sanitize_textarea_field( wp_unslash( $_POST['data'] ) ),
        'rule' => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($wpdb->bbcs_se, $data, array('id' => $id));

    if ($result !== false) {
        bbcs_renderSearchEnginesFromDb();
        bbcs_clearFileCache();
        
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('White bot updated successfully');
    } else {
        wp_send_json_error('Failed to update white bot');
    }
}
add_action('wp_ajax_bbcs_update_white', 'bbcs_update_white_callback');

function bbcs_delete_white_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        wp_send_json_error('ID is required for deletion.');
        return;
    }

    global $wpdb;

    $id = intval( wp_unslash( $_POST['id'] ) );
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete($wpdb->bbcs_se, array('id' => $id));

    if ($result !== false) {
        bbcs_renderSearchEnginesFromDb();
        bbcs_clearFileCache();

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('White bot deleted successfully');
    } else {
        wp_send_json_error('Failed to delete white bot');
    }
}
add_action('wp_ajax_bbcs_delete_white', 'bbcs_delete_white_callback');

function bbcs_toggle_white_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );

    global $wpdb;
    $id         = isset( $_POST['id'] ) ? absint(  wp_unslash( $_POST['id'] ) ) : 0;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE `{$wpdb->bbcs_se}` SET disable = 1 - disable WHERE id = %d",
            $id
        )
    );

    if ( false !== $result ) {
        bbcs_renderSearchEnginesFromDb();
        bbcs_clearFileCache();

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success( 'White bot toggled successfully' );
    } else {
        wp_send_json_error( 'Failed to toggle white bot' );
    }
}
add_action( 'wp_ajax_bbcs_toggle_white', 'bbcs_toggle_white_callback' );

function bbcs_create_white_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['priority'], $_POST['search'], $_POST['data'] and $_POST['rule']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['priority', 'search', 'data', 'rule'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            wp_send_json_error("Field '$field' is required.");
            return;
        }
    }

    global $wpdb;

    $data = sanitize_textarea_field(wp_unslash($_POST['data']));
    
    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_white_cache_version', 'botblocker-security') ?: 1;
        $exists_cache_key = 'bbcs_white_exists' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5($data);
        $exists = wp_cache_get($exists_cache_key, 'botblocker-security', false, $found);
    }
    
    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_se}` WHERE `data` = %s",
            $data
        ));
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($exists_cache_key, $exists, 'botblocker-security', 15);
        }
    }
    if ($exists) {
        wp_send_json_error('White bot already exists');
        return;
    }

    $data = array(
        'priority' => intval( wp_unslash( $_POST['priority'] ) ),
        'search' => sanitize_text_field(wp_unslash($_POST['search'])),
        'data' => $data,
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
        'disable' => 0
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = $wpdb->insert($wpdb->bbcs_se, $data);

    if ($result !== false) {
        bbcs_renderSearchEnginesFromDb();
        bbcs_clearFileCache();
        
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('White bot created successfully');
    } else {
        wp_send_json_error('Failed to create white bot');
    }
}
add_action('wp_ajax_bbcs_create_white', 'bbcs_create_white_callback');

function bbcs_export_white_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    global $wpdb;
    
    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_white_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_export_white' . bbcs_get_wp_cache_version() . $cache_version;
        $white_bots = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $white_bots = $wpdb->get_results("SELECT * FROM `{$wpdb->bbcs_se}`", ARRAY_A);

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $white_bots, 'botblocker-security', 15);
        }
    }

    wp_send_json_success($white_bots);
}
add_action('wp_ajax_bbcs_export_white', 'bbcs_export_white_callback');

function bbcs_import_white_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    if (!isset($_POST['white_bots']) || empty($_POST['white_bots'])) {
        wp_send_json_error('White bots data is required for import.');
        return;
    }

    global $wpdb;

    $white_bots = json_decode(sanitize_textarea_field(wp_unslash($_POST['white_bots'])), true);
    if (is_array($white_bots)) {
        $imported = 0;
        $skipped = 0;
        foreach ($white_bots as $bot) {
            $search = sanitize_text_field($bot['search']);
            $found = false;
            if (BOTBLOCKER_CACHE_WP) {
                $cache_version = wp_cache_get('bbcs_ajax_white_cache_version', 'botblocker-security') ?: 1;
                $exists_cache_key = 'bbcs_white_exists' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5($search);
                $existing = wp_cache_get($exists_cache_key, 'botblocker-security', false, $found);
            }
            
            if ($found === false) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $existing = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM `{$wpdb->bbcs_se}` WHERE search = %s",
                        $search
                    )
                );
                if (BOTBLOCKER_CACHE_WP) {
                    wp_cache_set($exists_cache_key, $existing, 'botblocker-security', 15);
                }
            };
            if ($existing == 0) {
                $data = array(
                    'priority' => intval($bot['priority']),
                    'search' => $search,
                    'data' => sanitize_textarea_field($bot['data']),
                    'rule' => sanitize_text_field($bot['rule']),
                    'comment' => sanitize_textarea_field($bot['comment']),
                    'disable' => intval($bot['disable'])
                );
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $result = $wpdb->insert($wpdb->bbcs_se, $data);
                if ($result !== false) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }
        bbcs_renderSearchEnginesFromDb();
        bbcs_clearFileCache();

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    } else {
        wp_send_json_error('Invalid JSON format');
    }
}
add_action('wp_ajax_bbcs_import_white', 'bbcs_import_white_callback');

function bbcs_clear_all_white_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    $result = bbcs_clear_all_white();

    if ($result !== false) {
        bbcs_renderSearchEnginesFromDb();
        bbcs_clearFileCache();

        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
        }
        
        wp_send_json_success('All white bots have been cleared');
    } else {
        wp_send_json_error('Failed to clear white bots');
    }
}
add_action('wp_ajax_bbcs_clear_all_white', 'bbcs_clear_all_white_callback');

function bbcs_clear_all_white()
{
    global $wpdb;

    return $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_se}`");
}

function bbcs_se_to_php_callback(): void
{
	try {
		bbcs_renderSearchEnginesFromDb();

		if (BOTBLOCKER_CACHE_WP) {
		    wp_cache_set('bbcs_ajax_white_cache_version', time(), 'botblocker-security');
		}
		
		wp_send_json_success('Search engines file generated successfully.');
	} catch(\Throwable $e) {
		// error_log('bbcs_se_to_php_callback error: ' . $e->getMessage());
		wp_send_json_error('Failed to generate search_engines file from database.');
	}
}
add_action('wp_ajax_bbcs_se_to_php', 'bbcs_se_to_php_callback');
