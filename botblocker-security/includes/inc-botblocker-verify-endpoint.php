<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Register bbcs-verify rewrite rules.
 * Named function so the activation hook can call it before flushing.
 */
function bbcs_register_verify_rewrite_rules() {
    add_rewrite_rule(
        '^bbcs-verify/?$',
        'index.php?bbcs_verify=1',
        'top'
    );
}
add_action('init', 'bbcs_register_verify_rewrite_rules', 10);

/**
 * Register the query variable so WordPress exposes it.
 */
add_filter('query_vars', function ($vars) {
    $vars[] = 'bbcs_verify';
    return $vars;
}, 10, 1);

/**
 * Fallback parse_request handler for servers where rewrite rules
 * have not been flushed yet or are not supported.
 */
add_action('parse_request', function ($wp) {
    /* phpcs:disable WordPress.Security.NonceVerification.Recommended */
    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    /* phpcs:enable WordPress.Security.NonceVerification.Recommended */
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    $rel = '/' . ltrim(substr((string) $path, strlen((string) $home_path)), '/');
    if (preg_match('#^/bbcs-verify/?$#', $rel)) {
        $wp->query_vars['bbcs_verify'] = '1';
    }
}, 0);

/**
 * Auto-flush rewrite rules when the endpoint version changes.
 */
add_action('init', function () {
    $rules_version = get_option('bbcs_verify_rules_version', '0');
    $current_version = '1.0';

    if ($rules_version !== $current_version) {
        bbcs_register_verify_rewrite_rules();
        flush_rewrite_rules(false);
        update_option('bbcs_verify_rules_version', $current_version);
    }
}, 999);

/**
 * Handle incoming POST requests to the bbcs-verify endpoint.
 * Mirrors the wp_ajax_bbcs_botblocker_check handler.
 */
add_action('template_redirect', function () {
    if ( ! get_query_var('bbcs_verify') ) {
        return;
    }

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
        wp_die( 'Method Not Allowed', 'Method Not Allowed', [ 'response' => 405 ] );
    }

    check_ajax_referer('botblocker_nonce', 'nonce');

    require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker.php';
    $botBlocker = new BotBlocker();
    $botBlocker->init_visitor_pages();
    $botBlocker->initialize();

    wp_die();
}, 0);
