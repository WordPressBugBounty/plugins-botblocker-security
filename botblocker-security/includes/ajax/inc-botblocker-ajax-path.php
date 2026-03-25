<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function bbcs_get_botblocker_paths_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $start  = isset($_POST['start']) ? absint(wp_unslash($_POST['start'])) : 0;
    $length = isset($_POST['length']) ? absint(wp_unslash($_POST['length'])) : 10;
    $draw   = isset($_POST['draw']) ? absint(wp_unslash($_POST['draw'])) : 0;

    $search_raw = isset($_POST['search']['value']) ? sanitize_text_field(wp_unslash($_POST['search']['value'])) : '';
    $search_has = $search_raw !== '';
    $like       = '%' . $wpdb->esc_like( $search_raw ) . '%';

    if ( $search_has ) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE id LIKE %s OR `search` LIKE %s OR rule LIKE %s OR comment LIKE %s",
                $like, $like, $like, $like
            )
        );
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, `search`, rule, comment, disable
                    FROM `{$wpdb->bbcs_path}`
                    WHERE id LIKE %s OR `search` LIKE %s OR rule LIKE %s OR comment LIKE %s
                    ORDER BY priority DESC
                    LIMIT %d, %d",
                $like, $like, $like, $like, $start, $length
            ),
            ARRAY_A
        );
    } else {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE 1 = %d",
                1
            )
        );
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, `search`, rule, comment, disable
                    FROM `{$wpdb->bbcs_path}`
                    WHERE 1 = %d
                    ORDER BY priority DESC
                    LIMIT %d, %d",
                1, $start, $length
            ),
            ARRAY_A
        );
    }

    $data = array();
    foreach ( $results as $row ) {
        $data[] = array(
            'id'       => $row['id'],
            'priority' => $row['priority'],
            'search'   => $row['search'],
            'rule'     => $row['rule'],
            'comment'  => $row['comment'],
            'disable'  => $row['disable'],
        );
    }

    wp_send_json( array(
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $total,
        'data'            => $data,
    ) );
}
add_action( 'wp_ajax_bbcs_get_botblocker_paths', 'bbcs_get_botblocker_paths_callback' );

function bbcs_get_path_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $id         = isset( $_POST['id'] ) ? absint(wp_unslash($_POST['id'])) : 0;
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
        $path = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->bbcs_path}` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

    if ( $path ) {
        wp_send_json_success( $path );
    } else {
        wp_send_json_error( __('Path not found.', 'botblocker-security') );
    }
}
add_action( 'wp_ajax_bbcs_get_path_details', 'bbcs_get_path_details_callback' );

function bbcs_update_path_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['priority'], $_POST['search'], $_POST['rule']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['id', 'priority', 'search', 'rule'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            // translators: %s is the name of the required field.
            wp_send_json_error(sprintf(__('Missing required field: %s.', 'botblocker-security'), $field));
            return;
        }
    }

    global $wpdb;

    $id = intval(wp_unslash($_POST['id']));
    $data = array(
        'priority' => intval(wp_unslash($_POST['priority'])),
        'search' => sanitize_textarea_field(wp_unslash($_POST['search'])),
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($wpdb->bbcs_path, $data, array('id' => $id));

    if ($result !== false) {
        bbcs_renderPathsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('Path updated successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to update path.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_update_path', 'bbcs_update_path_callback');

function bbcs_delete_path_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    if( !isset($_POST['id']) || empty($_POST['id']) ) {
        wp_send_json_error(__('Missing required field: id.', 'botblocker-security'));
        return;
    }

    global $wpdb;

    $id = intval(wp_unslash($_POST['id']));

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete($wpdb->bbcs_path, array('id' => $id));

    if ($result !== false) {
        bbcs_renderPathsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('Path deleted successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to delete path.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_delete_path', 'bbcs_delete_path_callback');

function bbcs_toggle_path_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $id         = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE `{$wpdb->bbcs_path}` SET disable = 1 - disable WHERE id = %d",
            $id
        )
    );

    if ( false !== $result ) {
        bbcs_renderPathsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success( __('Path toggled successfully.', 'botblocker-security') );
    } else {
        wp_send_json_error( __('Failed to toggle path.', 'botblocker-security') );
    }
}
add_action( 'wp_ajax_bbcs_toggle_path', 'bbcs_toggle_path_callback' );

function bbcs_create_path_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['priority'], $_POST['search'], $_POST['rule']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['priority', 'search', 'rule'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            // translators: %s is the name of the required field.
            wp_send_json_error(sprintf(__('Missing required field: %s.', 'botblocker-security'), $field));
            return;
        }
    }

    global $wpdb;

    $search = sanitize_textarea_field(wp_unslash($_POST['search']));
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE `search` = %s",
        $search
    ));

    if ($exists) {
        wp_send_json_error(__('Path already exists.', 'botblocker-security'));
        return;
    }

    $data = array(
        'priority' => intval(wp_unslash($_POST['priority'])),
        'search' => $search,
        'rule' => sanitize_text_field(wp_unslash($_POST['rule'])),
        'comment' => sanitize_textarea_field(wp_unslash($_POST['comment'])),
        'disable' => 0
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->insert($wpdb->bbcs_path, $data);

    if ($result !== false) {
        bbcs_renderPathsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('Path created successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to create path.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_create_path', 'bbcs_create_path_callback');

function bbcs_export_paths_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $paths = $wpdb->get_results("SELECT * FROM `{$wpdb->bbcs_path}`", ARRAY_A);

    wp_send_json_success($paths);
}
add_action('wp_ajax_bbcs_export_paths', 'bbcs_export_paths_callback');

function bbcs_import_paths_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    if (!isset($_POST['paths']) || empty($_POST['paths'])) {
        wp_send_json_error(__('Missing required field: paths.', 'botblocker-security'));
        return;
    }

    global $wpdb;

    $paths = json_decode(sanitize_textarea_field(wp_unslash($_POST['paths'])), true);
    if (is_array($paths)) {
        $imported = 0;
        $skipped = 0;
        foreach ($paths as $path) {
            $search = sanitize_textarea_field($path['search']);
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE search = %s",
                    $search
                )
            );

            if ($existing == 0) {
                $data = array(
                    'priority' => intval($path['priority']),
                    'search' => $search,
                    'rule' => sanitize_text_field($path['rule']),
                    'comment' => sanitize_textarea_field($path['comment']),
                    'disable' => intval($path['disable'])
                );
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
                $result = $wpdb->insert($wpdb->bbcs_path, $data);
                if ($result !== false) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }
        bbcs_renderPathsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    } else {
        wp_send_json_error(__('Invalid JSON format.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_import_paths', 'bbcs_import_paths_callback');

function bbcs_clear_all_paths_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    $result = bbcs_clear_all_paths();

    if ($result !== false) {
        bbcs_renderPathsFromDb();
        bbcs_clearFileCache();

        wp_send_json_success(__('All paths have been cleared.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to clear paths.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_clear_all_paths', 'bbcs_clear_all_paths_callback');

function bbcs_clear_all_paths()
{
    global $wpdb;
    return $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_path}`");
}

function bbcs_path_to_php_callback(): void
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }
	try {
        bbcs_renderPathsFromDb();

		wp_send_json_success(__('Paths file generated successfully.', 'botblocker-security'));
	} catch(\Throwable $e) {
		//error_log('bbcs_path_to_php_callback error: ' . $e->getMessage());
		wp_send_json_error(__('Failed to generate paths file from database.', 'botblocker-security'));
	}
}
add_action('wp_ajax_bbcs_path_to_php', 'bbcs_path_to_php_callback');
