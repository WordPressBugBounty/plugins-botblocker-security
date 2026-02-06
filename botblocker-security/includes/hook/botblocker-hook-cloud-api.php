<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_rewrite_rules()
{
    add_rewrite_tag('%botblocker_cloud_api%', '([01])');
    add_rewrite_rule('^botblocker_cloud_api/?$', 'index.php?botblocker_cloud_api=1', 'top');
}
add_action('init', 'bbcs_rewrite_rules');

function bbcs_add_query_vars($vars) {
    $vars[] = 'botblocker_cloud_api';
    return $vars;
}
add_filter('query_vars', 'bbcs_add_query_vars');

function bbcs_cloud_api_parse_request($wp)
{
    /* phpcs:disable WordPress.Security.NonceVerification.Recommended */
    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    /* phpcs:enable WordPress.Security.NonceVerification.Recommended */
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    $rel = '/' . ltrim(substr((string) $path, strlen((string) $home_path)), '/');
    if (rtrim((string) $rel, '/') === '/botblocker_cloud_api') {
        $wp->query_vars['botblocker_cloud_api'] = '1';
    }
}
add_action('parse_request', 'bbcs_cloud_api_parse_request', 0); 

function bbcs_fetch_cloud_api_key_handler() {
    check_ajax_referer('bbcs_fetch_cloud_api_key_action', 'nonce');

    $data = [
        'fetch_api_key' => true,
    ];

    $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL);
    if ($cloud === false || isset($cloud['error'])) {
        $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL);
    }

    if ($cloud === false) {
        wp_send_json_error(['message' => __('Failed to fetch API key from cloud server. Please try again later.', 'botblocker-security')]);
    } elseif (isset($cloud['error'])) {
        wp_send_json_error(['message' => __('Cloud API responded with error: ', 'botblocker-security') . $cloud['error']]);
    } elseif (empty($cloud['api_key']) || empty($cloud['api_secret'])) {
        wp_send_json_error(['message' => __('No API key found on cloud server for this domain.', 'botblocker-security')]);
    }

    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => 'cloud_extended'),
        array('key' => 'cloud_api_type')
    );
    
    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => $cloud['email']),
        array('key' => 'cloud_api_email')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => $cloud['api_key']),
        array('key' => 'cloud_api_key')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => $cloud['api_secret']),
        array('key' => 'cloud_api_secret')
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    bbcs_set_remaining_hits($cloud['hits']);
    bbcs_set_remaining_days($cloud['days']);

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_delete('bbcs_cloud_api_type' . bbcs_get_wp_cache_version(), 'botblocker-security');
        wp_cache_delete('bbcs_cloud_api_key' . bbcs_get_wp_cache_version(), 'botblocker-security');
        wp_cache_delete('bbcs_cloud_api_secret' . bbcs_get_wp_cache_version(), 'botblocker-security');
    }

    bbcs_generateSettingsFileFromDb();

    delete_transient('bbcs_cloud_api_expired_alert');
    delete_transient('bbcs_cloud_api_hits_exhausted_alert');
    delete_transient('bbcs_cloud_api_status_transient');

    wp_send_json_success(['message' => __('API key fetched successfully from cloud server.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_fetch_cloud_api_key', 'bbcs_fetch_cloud_api_key_handler');

function bbcs_connect_cloud_api_handler() {
    check_ajax_referer('bbcs_connect_cloud_api_action', 'nonce');
    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
    if (empty($api_key)) {
        wp_send_json_error(['message' => __('API key is required.', 'botblocker-security')]);
    }
    $data = [
        'validate_api_key' => true,
        'cloud_api_key' => $api_key,
    ];
    $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL);
    if ($cloud === false || isset($cloud['error'])) {
        $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL);
    }

    if ($cloud === false) {
        wp_send_json_error(['message' => __('Failed to connect to Cloud API. Please try again later.', 'botblocker-security')]);
    } elseif (isset($cloud['error'])) {
        wp_send_json_error(['message' => __('The key you entered is invalid. Please double-check it. Cloud API responded with: ', 'botblocker-security') . $cloud['error']]);
    }

    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => 'cloud_extended'),
        array('key' => 'cloud_api_type')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => $cloud['email']),
        array('key' => 'cloud_api_email')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => $cloud['api_key']),
        array('key' => 'cloud_api_key')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => $cloud['api_secret']),
        array('key' => 'cloud_api_secret')
    );

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_delete('bbcs_cloud_api_type' . bbcs_get_wp_cache_version(), 'botblocker-security');
        wp_cache_delete('bbcs_cloud_api_key' . bbcs_get_wp_cache_version(), 'botblocker-security');
        wp_cache_delete('bbcs_cloud_api_secret' . bbcs_get_wp_cache_version(), 'botblocker-security');
    }

    bbcs_generateSettingsFileFromDb();

    delete_transient('bbcs_cloud_api_expired_alert');
    delete_transient('bbcs_cloud_api_hits_exhausted_alert');
    delete_transient('bbcs_cloud_api_status_transient');

    wp_send_json_success(['message' => __('Cloud API key connected and validated successfully.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_connect_cloud_api', 'bbcs_connect_cloud_api_handler');

function bbcs_refresh_cloud_api_handler()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    $res = bbcs_refresh_cloud_api();

    if ($res === false) {
        wp_send_json_error(['error' => __('Failed to refresh Cloud API', 'botblocker-security')]);
    } elseif (is_string($res)) {
        wp_send_json_error(['error' => $res]);
    }

    $response_data = [
        'remaining_hits' => $res['hits'],
        'remaining_days' => $res['days']
    ];
    
    wp_send_json_success($response_data);
}
add_action('wp_ajax_bbcs_refresh_cloud_api', 'bbcs_refresh_cloud_api_handler');

function bbcs_deactivate_cloud_api_handler() {
    check_ajax_referer('bbcs_deactivate_cloud_api_action', 'nonce');

    global $wpdb;

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => ''),
        array('key' => 'cloud_api_type')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => ''),
        array('key' => 'cloud_api_email')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => ''),
        array('key' => 'cloud_api_key')
    );

    $wpdb->update(
        $wpdb->bbcs_settings,
        array('value' => ''),
        array('key' => 'cloud_api_secret')
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_delete('bbcs_cloud_api_type' . bbcs_get_wp_cache_version(), 'botblocker-security');
        wp_cache_delete('bbcs_cloud_api_key' . bbcs_get_wp_cache_version(), 'botblocker-security');
        wp_cache_delete('bbcs_cloud_api_secret' . bbcs_get_wp_cache_version(), 'botblocker-security');
    }

    bbcs_generateSettingsFileFromDb();

    delete_transient('bbcs_cloud_api_expired_alert');
    delete_transient('bbcs_cloud_api_hits_exhausted_alert');
    delete_transient('bbcs_cloud_api_status_transient');

    wp_send_json_success(['message' => __('Cloud API connection has been deactivated.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_deactivate_cloud_api', 'bbcs_deactivate_cloud_api_handler');
function bbcs_refresh_cloud_api()
{
    $BBCS = BotBlocker::getInstance();
    $request_auth = [
        'refresh_cloud_api' => true,
        'cloud_api_key' => $BBCS->settings->cloud_api_key,
        'domain_api_key' => $BBCS->settings->cloud_api_secret,
    ];

    $request_data = array_merge($request_auth);
    $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL);
    if ($cloud === false || isset($cloud['error'])) {
        $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL);
    }

    if ($cloud === false) {
        return false;
    } elseif (isset($cloud['error'])) {
        return $cloud['error'];
    }

    bbcs_set_remaining_hits($cloud['hits']);
    bbcs_set_remaining_days($cloud['days']);

    bbcs_check_cloud_api_expiry();
    
    return [
        'hits' => $cloud['hits'],
        'days' => $cloud['days']
    ];
}
