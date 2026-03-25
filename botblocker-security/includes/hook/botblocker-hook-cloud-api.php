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
    ];

    $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL, 'fetch_api_key');
    if ($cloud === false || isset($cloud['error'])) {
        $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL, 'fetch_api_key');
    }

    if ($cloud === false) {
        wp_send_json_error(['message' => __('Failed to retrieve API key. Try again later.', 'botblocker-security')]);
    } elseif (isset($cloud['error'])) {
        wp_send_json_error(['message' => __('Cloud API responded with error: ', 'botblocker-security') . $cloud['error']]);
    } elseif (empty($cloud['api_key']) || empty($cloud['api_secret'])) {
        wp_send_json_error(['message' => __('No API key found for this domain.', 'botblocker-security')]);
    }

    bbcs_set_remaining_hits($cloud['hits']);
    bbcs_set_remaining_days($cloud['days']);

    // BBCS-MULTISITE
    $bbcs_propagate = array(
        'cloud_api_type'   => 'cloud_extended',
        'cloud_api_email'  => $cloud['email'],
        'cloud_api_key'    => $cloud['api_key'],
        'cloud_api_secret' => $cloud['api_secret'],
        'cloud_api_tier'   => $cloud['tier'],
        'check'            => 1,
    );
    if ( ! isset( $cloud['tier'] ) || $cloud['tier'] !== 'ultimate' ) {
        $bbcs_propagate['force_cloud_validation'] = 0;
    }
    bbcs_sync_cloud_settings_network( $bbcs_propagate );

    wp_send_json_success(['message' => __('API key retrieved successfully.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_fetch_cloud_api_key', 'bbcs_fetch_cloud_api_key_handler');

function bbcs_connect_cloud_api_handler() {
    check_ajax_referer('bbcs_connect_cloud_api_action', 'nonce');
    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
    if (empty($api_key)) {
        wp_send_json_error(['message' => __('API key is required.', 'botblocker-security')]);
    }
    $data = [
        'cloud_api_key' => $api_key,
    ];
    $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL, 'validate_api_key');
    if ($cloud === false || isset($cloud['error'])) {
        $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL, 'validate_api_key');
    }

    if ($cloud === false) {
        wp_send_json_error(['message' => __('Failed to connect to Cloud API. Try again later.', 'botblocker-security')]);
    } elseif (isset($cloud['error'])) {
        wp_send_json_error(['message' => __('Invalid API key. Cloud API responded with: ', 'botblocker-security') . $cloud['error']]);
    }

    $cloud_api_tier = isset($cloud['api_tier']) ? sanitize_text_field($cloud['api_tier']) : '';
    if (!bbcs_is_valid_cloud_api_tier($cloud_api_tier)) {
        $cloud_api_tier = '';
    }

    // BBCS-MULTISITE
    $bbcs_propagate = array(
        'cloud_api_type'   => 'cloud_extended',
        'cloud_api_email'  => $cloud['email'],
        'cloud_api_key'    => $cloud['api_key'],
        'cloud_api_secret' => $cloud['api_secret'],
        'cloud_api_tier'   => $cloud_api_tier,
        'check'            => 1,
    );
    if ( $cloud_api_tier !== 'ultimate' ) {
        $bbcs_propagate['force_cloud_validation'] = 0;
    }
    bbcs_sync_cloud_settings_network( $bbcs_propagate );

    wp_send_json_success(['message' => __('Cloud API key connected and validated.', 'botblocker-security')]);
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

    // BBCS-MULTISITE
    bbcs_sync_cloud_settings_network( array(
        'cloud_api_type'   => '',
        'cloud_api_email'  => '',
        'cloud_api_key'    => '',
        'cloud_api_secret' => '',
        'cloud_api_tier'   => '',
    ) );

    wp_send_json_success(['message' => __('Cloud API connection deactivated.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_deactivate_cloud_api', 'bbcs_deactivate_cloud_api_handler');
function bbcs_refresh_cloud_api()
{
    if (!bbcs_isCloudAPIActive()) {
        return false;
    }

    $BBCS = BotBlocker::getInstance();
    $request_auth = [
        'cloud_api_key' => $BBCS->settings->cloud_api_key,
        'domain_api_key' => $BBCS->settings->cloud_api_secret,
    ];

    $request_data = array_merge($request_auth);
    $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL, 'refresh_cloud_api');
    if ($cloud === false || isset($cloud['error'])) {
        $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL, 'refresh_cloud_api');
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
