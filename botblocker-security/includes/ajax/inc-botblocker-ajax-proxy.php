<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Retrieves proxies via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_get_botblocker_proxies_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $start  = isset( $_POST['start'] )  ? absint( wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint( wp_unslash( $_POST['draw'] ) )   : 0;
    $search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $records_total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_proxy}` WHERE 1 = %d", 1 )
    );

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';

        // REVIEWER NOTE: custom table operations require direct database queries.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $records_filtered = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_proxy}`
                 WHERE id LIKE %s OR `key` LIKE %s OR `value` LIKE %s OR comment LIKE %s",
                $like, $like, $like, $like
            )
        );

        // REVIEWER NOTE: custom table operations require direct database queries.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, `key`, `value`, comment
                 FROM `{$wpdb->bbcs_proxy}`
                 WHERE id LIKE %s OR `key` LIKE %s OR `value` LIKE %s OR comment LIKE %s
                 ORDER BY `comment`, `key`
                 LIMIT %d, %d",
                $like, $like, $like, $like, $start, $length
            ),
            ARRAY_A
        );
    } else {
        $records_filtered = $records_total;

        // REVIEWER NOTE: custom table operations require direct database queries.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, `key`, `value`, comment
                 FROM `{$wpdb->bbcs_proxy}`
                 WHERE 1 = %d
                 ORDER BY `comment`, `key`
                 LIMIT %d, %d",
                1, $start, $length
            ),
            ARRAY_A
        );
    }

    $data = array();
    foreach ( (array) $results as $row ) {
        $data[] = array(
            'id'      => $row['id'],
            'key'     => $row['key'],
            'value'   => $row['value'],
            'comment' => $row['comment'],
        );
    }

    wp_send_json( array(
        'draw'            => $draw,
        'recordsTotal'    => $records_total,
        'recordsFiltered' => $records_filtered,
        'data'            => $data,
    ) );
}
add_action( 'wp_ajax_bbcs_get_botblocker_proxies', 'bbcs_get_botblocker_proxies_callback' );

/**
 * Retrieves proxy details via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_get_proxy_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $id         = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $proxy = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM `{$wpdb->bbcs_proxy}` WHERE id = %d",
            $id
        ),
        ARRAY_A
    );

    if ( $proxy ) {
        wp_send_json_success( $proxy );
    } else {
        wp_send_json_error( __('Proxy not found.', 'botblocker-security') );
    }
}
add_action( 'wp_ajax_bbcs_get_proxy_details', 'bbcs_get_proxy_details_callback' );

/**
 * Updates a proxy in the database via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_update_proxy_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['ip'], $_POST['key'] and $_POST['value']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['id', 'key', 'value'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            // translators: %s is the name of the required field.
            wp_send_json_error(sprintf(__('Missing required field: %s.', 'botblocker-security'), $field));
            return;
        }
    }

    global $wpdb;

    $id = intval( wp_unslash( $_POST['id'] ) );
    $data = array(
        'key' => sanitize_text_field(wp_unslash($_POST['key'])),
        'value' => sanitize_text_field(wp_unslash($_POST['value'])),
        'comment' => isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '',
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($wpdb->bbcs_proxy, $data, array('id' => $id));

    if ($result !== false) {
        bbcs_renderProxyFromDb();
        bbcs_clearFileCache();
        wp_send_json_success(__('Proxy updated successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to update proxy.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_update_proxy', 'bbcs_update_proxy_callback');

/**
 * Deletes a proxy from the database via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_delete_proxy_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        wp_send_json_error(__('Missing required field: id.', 'botblocker-security'));
        return;
    }

    global $wpdb;

    $id = intval( wp_unslash( $_POST['id'] ) );

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete($wpdb->bbcs_proxy, array('id' => $id));

    if ($result !== false) {
        bbcs_renderProxyFromDb();
        bbcs_clearFileCache();
        wp_send_json_success(__('Proxy deleted successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to delete proxy.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_delete_proxy', 'bbcs_delete_proxy_callback');

/**
 * Creates a new proxy via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_create_proxy_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    /**
     * REVIEWER NOTE:
     * All required $_POST fields are validated for existence in the loop below.
     * This ensures that later direct access to $_POST['key'] and $_POST['value']
     * is always safe and cannot trigger undefined index warnings.
     * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
     */
    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required_fields = ['key', 'value'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            // translators: %s is the name of the required field.
            wp_send_json_error(sprintf(__('Missing required field: %s.', 'botblocker-security'), $field));
            return;
        }
    }

    global $wpdb;

    $key = sanitize_text_field(wp_unslash($_POST['key']));
    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$wpdb->bbcs_proxy}` WHERE `key` = %s",
        $key
    ));
    if ($exists) {
        wp_send_json_error(__('Proxy already exists.', 'botblocker-security'));
        return;
    }

    $data = array(
        'key' => $key,
        'value' => sanitize_text_field(wp_unslash($_POST['value'])),
        'comment' => sanitize_textarea_field(wp_unslash($_POST['comment'])),
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->insert($wpdb->bbcs_proxy, $data);

    if ($result !== false) {
        bbcs_renderProxyFromDb();
        bbcs_clearFileCache();
        wp_send_json_success(__('Proxy created successfully.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to create proxy.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_create_proxy', 'bbcs_create_proxy_callback');

/**
 * Exports proxies via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_export_proxies_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $proxies = $wpdb->get_results("SELECT * FROM `{$wpdb->bbcs_proxy}`", ARRAY_A);

    wp_send_json_success($proxies);
}
add_action('wp_ajax_bbcs_export_proxies', 'bbcs_export_proxies_callback');

/**
 * Imports proxies via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_import_proxies_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    if (!isset($_POST['proxies']) || empty($_POST['proxies'])) {
        wp_send_json_error(__('Missing required field: proxies.', 'botblocker-security'));
        return;
    }

    global $wpdb;

    $proxies = json_decode(sanitize_text_field(wp_unslash($_POST['proxies'])), true);
    if (is_array($proxies)) {
        $imported = 0;
        $skipped = 0;
        foreach ($proxies as $proxy) {
            $key = sanitize_text_field($proxy['key']);
            // REVIEWER NOTE: custom table operations require direct database queries.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_proxy}` WHERE `key` = %s", $key));
            if ($existing == 0) {
                $data = array(
                    'key' => $key,
                    'value' => sanitize_text_field($proxy['value']),
                    'comment' => sanitize_text_field($proxy['comment']),
                );
                // REVIEWER NOTE: custom table operations require direct database queries.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $result = $wpdb->insert($wpdb->bbcs_proxy, $data);
                if ($result !== false) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }
        bbcs_renderProxyFromDb();
        bbcs_clearFileCache();
        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    } else {
        wp_send_json_error(__('Invalid JSON format.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_import_proxies', 'bbcs_import_proxies_callback');

/**
 * Clears all proxies via AJAX request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_clear_all_proxies_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

    global $wpdb;

    $result = $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_proxy}`");

    if ($result !== false) {
        bbcs_renderProxyFromDb();
        bbcs_clearFileCache();
        wp_send_json_success(__('All proxies have been cleared.', 'botblocker-security'));
    } else {
        wp_send_json_error(__('Failed to clear proxies.', 'botblocker-security'));
    }
}
add_action('wp_ajax_bbcs_clear_all_proxies', 'bbcs_clear_all_proxies_callback');

/**
 * Renders the proxy.php file from database
 *
 * @since 1.0.0
 *
 * @return void
 */
function bbcs_render_proxy_file_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }
    
    bbcs_renderProxyFromDb();
    bbcs_clearFileCache();
    
    wp_send_json_success(__('Proxy file rendered successfully.', 'botblocker-security'));
}
add_action('wp_ajax_bbcs_render_proxy_file', 'bbcs_render_proxy_file_callback');
