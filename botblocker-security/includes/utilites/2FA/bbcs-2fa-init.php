<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Register 2FA rewrite rules. Use a named function so activation hook
 * can call it before flushing rules.
 */
function bbcs_register_2fa_rewrite_rules() {
    add_rewrite_rule(
        '^bbcs-2fa/?$',
        'index.php?bbcs_2fa=1',
        'top'
    );
    add_rewrite_rule(
        '^bbcs-2fa-setup/?$',
        'index.php?bbcs_2fa_setup=1',
        'top'
    );
}
add_action('init', 'bbcs_register_2fa_rewrite_rules', 10);

/**
 * Parse incoming requests and set query vars for 2FA endpoints
 * as a fallback if rewrite rules are not yet present or flushed.
 */
function bbcs_2fa_parse_request($wp) {
    global $bbcs_google_auth;

    if ( ! is_user_logged_in() ) {
        return;
    }

    $bbcs_saved_settings = $bbcs_google_auth->get_settings();

    if (isset($bbcs_saved_settings['bbcs_2fa_enable']) && !$bbcs_saved_settings['bbcs_2fa_enable']) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    
    if ($path && preg_match('#/bbcs-2fa/?$#', $path)) {
        $wp->query_vars['bbcs_2fa'] = '1';
    }
    if ($path && preg_match('#/bbcs-2fa-setup/?$#', $path)) {
        $wp->query_vars['bbcs_2fa_setup'] = '1';
    }
}
add_action('parse_request', 'bbcs_2fa_parse_request', 5);

add_action('init', function() {
    $rules_version = get_option('bbcs_2fa_rules_version', '0');
    $current_version = '1.2';
    
    if ($rules_version !== $current_version) {
        bbcs_register_2fa_rewrite_rules();
        flush_rewrite_rules(false);
        update_option('bbcs_2fa_rules_version', $current_version);
    }
}, 999);

add_filter('query_vars', function($vars) {
    $vars[] = 'bbcs_2fa';
    $vars[] = 'bbcs_2fa_setup';
    return $vars;
}, 10, 1);

add_filter('login_redirect', function($redirect_to, $request, $user) {
    if ( is_wp_error($user) || ! bbcs_is_2fa_required_for_user($user->ID) ) {
        return $redirect_to;
    }
    
    if (is_wp_error($user)) {
        return $redirect_to;
    }

    $secret = get_user_meta($user->ID, '_2fa_secret', true);

    if (empty($secret)) {
        update_user_meta($user->ID, '_2fa_setup_pending', 1);
        return site_url('/?bbcs_2fa_setup=1');
    }

    $redirect_candidate = is_string( $request ) ? wp_unslash( $request ) : '';
    $redirect_to_check = sanitize_text_field( $redirect_candidate );
    if ( $redirect_to_check && strpos( $redirect_to_check, 'wp-admin' ) !== false ) {
        update_user_meta( $user->ID, '_2fa_pending', 1 );
        return site_url( '/?bbcs_2fa=1' );
    }

    return $redirect_to;
}, 10, 3);

add_action('admin_init', function() {
    // Ignorе AJAX, REST, CRON - do not perform checks and redirects for background requests
    if (wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    if ('logout' === $action) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    if ($request_uri && strpos($request_uri, '/wp-json/') !== false) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return;
    }

    if ( ! bbcs_is_2fa_required_for_user($user_id) ) {
        return;
    }

    $secret = get_user_meta($user_id, '_2fa_secret', true);

    // Setup pending
    if (empty($secret) && get_user_meta($user_id, '_2fa_setup_pending', true)) {
        wp_safe_redirect(home_url('/?bbcs_2fa_setup=1'));
        exit;
    }

    // Verification pending
    if (get_user_meta($user_id, '_2fa_pending', true)) {
        wp_safe_redirect(home_url('/?bbcs_2fa=1'));
        exit;
    }
}, 5);

function bbcs_generate_backup_codes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {        
        $codes[] = strtoupper(bin2hex(random_bytes(8)));
    }
    return $codes;
}

function bbcs_hash_backup_code($code) {
    return password_hash($code, PASSWORD_DEFAULT);
}

function bbcs_verify_backup_code($code, $stored_codes) {
    if (!is_array($stored_codes)) return false;

    foreach ($stored_codes as $idx => $stored) {
        if (strpos($stored, '$') === 0) {
            if (password_verify($code, $stored)) {
                return $idx;
            }
        }
    }

    $code_upper = strtoupper($code);
    foreach ($stored_codes as $idx => $stored) {
        if (is_string($stored) && strtoupper($stored) === $code_upper) {
            return $idx;
        }
    }

    return false;
}

function bbcs_check_rate_limit($user_id) {
    $attempts = get_transient('bbcs_2fa_attempts_' . $user_id);
    
    if ($attempts === false) {
        set_transient('bbcs_2fa_attempts_' . $user_id, 1, 300); // 5 minutes
        return true;
    }
    
    if ($attempts >= 5) {
        return false;
    }
    
    set_transient('bbcs_2fa_attempts_' . $user_id, $attempts + 1, 300);
    return true;
}

function bbcs_reset_rate_limit($user_id) {
    delete_transient('bbcs_2fa_attempts_' . $user_id);
}