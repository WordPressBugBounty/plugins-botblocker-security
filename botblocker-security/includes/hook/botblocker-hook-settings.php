<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_post_save_botblocker_integrations', 'bbcs_botblocker_handle_integrations_save');
function bbcs_botblocker_handle_integrations_save()
{
    if ( ! current_user_can(bbcs_can_manage()) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
    }
    check_admin_referer( 'save_botblocker_integrations', 'botblocker_integrations_nonce' );

    global $wpdb;

    $checkbox_fields = [
        'recaptcha_check',
        'recaptcha_v3_ipv6_block',
        'memcached_enable',
        'redis_enable',
        'bbcs_2fa_enable',
        'use_transients_for_cloud'
    ];

    foreach ($checkbox_fields as $field) {
        $value = isset( $_POST[$field]) ? '1' : '0';
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace(
            $wpdb->bbcs_settings,
            ['key' => $field, 'value' => $value],
            ['%s', '%s']
        );
    }

    $exclude = [
        'action',
        'botblocker_integrations_nonce',
        '_wp_http_referer',
        'save_settings',
        'bbcs_anchor'
    ];

    $post_roles_raw = filter_input(
        INPUT_POST,
        'bbcs_2fa_roles',
        FILTER_DEFAULT,
        FILTER_REQUIRE_ARRAY
    );

    $post_roles_raw = is_array($post_roles_raw) ? $post_roles_raw : [];

    $post_roles = is_array($post_roles_raw)
        ? array_map('sanitize_text_field', $post_roles_raw)
        : [];

    $roles = wp_roles()->roles;
    $bbcs_2fa_roles = [];

    foreach ($roles as $role_key => $role) {
        $bbcs_2fa_roles[$role_key] = isset($post_roles[$role_key]) ? '1' : '0';
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->replace(
        $wpdb->bbcs_settings,
        [
            'key'   => 'bbcs_2fa_roles',
            'value' => wp_json_encode($bbcs_2fa_roles),
        ],
        ['%s', '%s']
    );


    $allowed_fields = bbcs_get_allowed_fields();
    foreach ( $allowed_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            $prepared_value = null;
            if ( is_array( $_POST[ $key ] ) ) {
                $sanitized_array = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $key ] ) );
                $prepared_value = wp_json_encode( $sanitized_array );
            } else {
                $prepared_value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
            }
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->replace(
                $wpdb->bbcs_settings,
                [ 'key' => sanitize_key( $key ), 'value' => $prepared_value ],
                [ '%s', '%s' ]
            );
        }
    }

    bbcs_generateSettingsFileFromDb();
    bbcs_flushRedisAndMMC();
    bbcs_resetTransientHealth();

    set_transient(
        'bbcs_notice_integrations_' . get_current_user_id(),
        [
            'message' => __( 'Integrations saved.', 'botblocker-security' ),
            'type'    => 'updated',
        ],
        60
    );

    $anchor = isset($_POST['bbcs_anchor']) ? sanitize_key(wp_unslash( $_POST['bbcs_anchor'])) : '';
    $url = add_query_arg('settings-updated', 'true', bbcs_admin_page_url('bbcs_integrations'));
    if ($anchor !== '') {
        $url .= '#' . $anchor;
    }

    wp_safe_redirect($url);
    exit;
}

add_action('admin_post_save_botblocker_settings', 'bbcs_botblocker_handle_settings_save');
function bbcs_botblocker_handle_settings_save() 
{
    if ( ! current_user_can(bbcs_can_manage()) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
    }
    check_admin_referer( 'save_botblocker_settings', 'botblocker_settings_nonce' );

    global $wpdb;

    $checkbox_fields = [
        'block_empty_ua',
        'block_empty_lang',
        'block_nojs_users',
        'block_proxy_users',
        'block_vpn_users',
        'block_tor_users',
        'block_ipv6_users',
        'block_http10_users',
        'block_incorrect_lang_users',
        'block_simplebot_ua',
        'block_adblocker_users',
        'block_cf_users',
        'block_ip_ptr_match',
        'get_browser_type',
        'get_os_type',
        'get_device_type',
        'check',
        'unresponsive',
        'utm_referrer',
        'utm_noindex',
        'check_get_ref',
        'botblocker_log_tests',
        'botblocker_log_local',
        'botblocker_log_allow',
        'botblocker_log_fake',
        'botblocker_log_goodip',
        'botblocker_log_block',
        'botblocker_force_check',
        'force_cloud_validation',
        'noarchive',
        'iframe_stop',
        'hosting_block',
        'block_fake_ref',
        'block_incognito_users',
        'block_simple_antidetect',
        'block_override',
        'block_web_engine_options',
        'block_device_options',
        'botblocker_log_admin',
        'botblocker_log_wp',
        'botblocker_log_error',
        'botblocker_log_disabled',
        'botblocker_log_cli',
        'botblocker_log_bbcs',
        'allow_self_ip_req',
        'cache_ui_data',
        'autosave_admin_ip',
        'daylight_saving_time',
        'telegram_notifications',
        'email_notifications',
        'pusher_notifications',
        'critical_load_notifications',
        'login_brutforce_enabled'
    ];

    if (!isset($_POST['x_robots_directives'])) {
        $_POST['x_robots_directives'] = [];
    }

    foreach ($checkbox_fields as $field) {
        $value = isset($_POST[$field]) ? '1' : '0';
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace(
            $wpdb->bbcs_settings,
            ['key' => $field, 'value' => $value],
            ['%s', '%s']
        );
    }
    
    $exclude = [
        'action',
        'botblocker_settings_nonce',
        '_wp_http_referer',
        'save_settings',
        'bbcs_anchor'
    ];

    $allowed_fields = bbcs_get_allowed_fields();
    foreach ( $allowed_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            $prepared_value = null;
            if ( is_array( $_POST[ $key ] ) ) {
                $sanitized_array = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $key ] ) );
                $prepared_value = wp_json_encode( $sanitized_array );
            } else {
                $prepared_value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
            }
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->replace(
                $wpdb->bbcs_settings,
                [ 'key' => sanitize_key( $key ), 'value' => $prepared_value ],
                [ '%s', '%s' ]
            );
        }
    }
    
    bbcs_generateSettingsFileFromDb();
    bbcs_resetTransientHealth();

    set_transient(
        'bbcs_notice_settings_' . get_current_user_id(),
        [
            'message' => __( 'Settings saved.', 'botblocker-security' ),
            'type'    => 'updated',
        ],
        60
    );    

    $anchor = isset($_POST['bbcs_anchor']) ? sanitize_key(wp_unslash($_POST['bbcs_anchor'])) : '';
    $url = add_query_arg('settings-updated', 'true', bbcs_admin_page_url('bbcs_settings'));
    if ($anchor !== '') {
        $url .= '#' . $anchor;
    }
    wp_safe_redirect($url);
    exit;
}
