<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('upgrader_process_complete', 'bbcs_on_plugin_updated', 10, 2);
function bbcs_on_plugin_updated($upgrader, $hook_extra): void
{
    if (empty($hook_extra['type']) || $hook_extra['type'] !== 'plugin') return;
    $plugins = $hook_extra['plugins'] ?? (isset($hook_extra['plugin']) ? [$hook_extra['plugin']] : []);
    $bbcs_plugin = plugin_basename(BOTBLOCKER_DIR . 'botblocker-security.php');
    if (!in_array($bbcs_plugin, $plugins, true)) return;

    $plugin_data = get_file_data(BOTBLOCKER_DIR . 'botblocker-security.php', ['Version' => 'Version']);
    $new_version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : BOTBLOCKER_VERSION;

    bbcs_foreach_site(function($site_id) use ($new_version) {
        $result = bbcs_auto_update_addons($new_version);
        if (!empty($result['failed'])) {
            $failed_slugs = array_column($result['failed'], 'slug');
            $active = get_option('botblocker_active_addons', []);
            if (is_array($active) && !empty($failed_slugs)) {
                update_option('botblocker_active_addons', array_values(array_diff($active, $failed_slugs)));
            }
            bbcs_alerts_set_addon_update_failed($result['failed']);
        }
        bbcs_deactivate_incompatible_addons($new_version);
    });
}

function bbcs_deactivate_incompatible_addons(string $core_version = ''): void
{
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active) || empty($active)) return;

    $addons = bbcs_scan_addons();
    $incompatible = [];

    foreach ($active as $slug) {
        if (!isset($addons[$slug]) || !$addons[$slug]['valid']) continue;
        if (!bbcs_is_addon_compatible($addons[$slug], $core_version)) {
            $incompatible[] = [
                'name'          => $addons[$slug]['name'] ?: $slug,
                'requires_core' => $addons[$slug]['requires_core'],
            ];
        }
    }

    $new_active = array_values(array_filter($active, function($s) use ($addons, $core_version) {
        return isset($addons[$s]) && $addons[$s]['valid'] && bbcs_is_addon_compatible($addons[$s], $core_version);
    }));

    if ($new_active !== array_values($active)) {
        update_option('botblocker_active_addons', $new_active);
    }

    if (!empty($incompatible)) {
        bbcs_alerts_set_addon_incompatible($incompatible);
    }
}

add_action('admin_post_bbcs_update_all_addons', 'bbcs_update_all_addons_handler');
function bbcs_update_all_addons_handler(): void
{
    if (!current_user_can(bbcs_can_manage())) {
        wp_die(esc_html__('Insufficient permissions.', 'botblocker-security'));
    }
    $nonce = isset($_POST['bbcs_update_all_addons_nonce']) ? sanitize_text_field(wp_unslash($_POST['bbcs_update_all_addons_nonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'bbcs_update_all_addons')) {
        wp_die('Nonce verification failed');
    }
    $result = bbcs_auto_update_addons();
    if (!empty($result['failed'])) {
        bbcs_alerts_set_addon_update_failed($result['failed']);
    }
    bbcs_deactivate_incompatible_addons();
    wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_updated_all', '1', bbcs_admin_page_url('bbcs_addons'))));
    exit;
}

add_action('admin_post_bbcs_toggle_addon', 'bbcs_toggle_addon_handler');
function bbcs_toggle_addon_handler()
{
    if ( ! current_user_can(bbcs_can_manage()) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
    }
    check_admin_referer( 'bbcs_toggle_addon', 'bbcs_toggle_addon_nonce' );
    
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    if ($slug === '') {
        wp_safe_redirect(bbcs_admin_page_url('bbcs_addons'));
        exit;
    }
    if (!function_exists('bbcs_scan_addons')) {
        wp_safe_redirect(bbcs_admin_page_url('bbcs_addons'));
        exit;
    }
    $addons = bbcs_scan_addons();
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active))
        $active = [];
    if (!isset($addons[$slug]) || !$addons[$slug]['valid']) {
        $active = array_values(array_diff($active, [$slug]));
        update_option('botblocker_active_addons', $active);
        wp_safe_redirect(bbcs_admin_page_url('bbcs_addons') . '&bbcs_error=invalid');
        exit;
    }
    if (!in_array($slug, $active, true) && !bbcs_is_addon_compatible($addons[$slug])) {
        wp_safe_redirect(bbcs_admin_page_url('bbcs_addons') . '&bbcs_error=requires_core&bbcs_error_msg=' . rawurlencode($addons[$slug]['requires_core']));
        exit;
    }
    if (in_array($slug, $active, true)) {
        $active = array_values(array_diff($active, [$slug]));
    } else {
        $active[] = $slug;
        $active = array_values(array_unique($active));
    }
    update_option('botblocker_active_addons', $active);

    $is_now_active = in_array($slug, $active, true);
    if ($is_now_active && isset($addons[$slug]['core']) && file_exists($addons[$slug]['core'])) {
        include_once $addons[$slug]['core'];
    }

    do_action('bbcs_addon_toggled', $slug, $is_now_active);

    if ($slug === 'bbcs-security-headers') {
        bbcs_security_headers_mu_fallback($is_now_active, $addons[$slug] ?? []);
    }

    wp_safe_redirect(bbcs_admin_page_url('bbcs_addons') . '&bbcs_updated=1');
    exit;
}

function bbcs_security_headers_mu_fallback(bool $is_active, array $addon): void {
    if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
        return;
    }
    $mu_file = trailingslashit(WPMU_PLUGIN_DIR) . 'botblocker-security-headers.php';
    if ($is_active) {
        if (file_exists($mu_file)) {
            return;
        }
        $src = isset($addon['core']) ? dirname($addon['core']) . '/../mu/botblocker-security-headers.php' : '';
        if ($src === '' || !file_exists($src)) {
            return;
        }
        if (!is_dir(WPMU_PLUGIN_DIR)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
            if (!@mkdir(WPMU_PLUGIN_DIR, 0755, true) && !is_dir(WPMU_PLUGIN_DIR)) {
                return;
            }
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
        $content = file_get_contents($src);
        if (false === $content || $content === '') {
            return;
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        @file_put_contents($mu_file, $content, LOCK_EX);
    } else {
        if (!file_exists($mu_file)) {
            return;
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        @unlink($mu_file);
    }
    if (function_exists('bbcs_clearFileCache')) {
        bbcs_clearFileCache();
    }
    clearstatcache(true, $mu_file);
}

function bbcs_is_allowed_addon_url( $url ) {
    $allowed_domains = array( 'botblocker.top', 'globus.studio' );
    $parsed = wp_parse_url( $url );
    if ( empty( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'https', 'http' ), true ) ) {
        return false;
    }
    if ( empty( $parsed['host'] ) ) {
        return false;
    }
    $host = strtolower( $parsed['host'] );
    foreach ( $allowed_domains as $domain ) {
        if ( $host === $domain || substr( $host, -( strlen( $domain ) + 1 ) ) === '.' . $domain ) {
            return true;
        }
    }
    return false;
}

add_action('admin_post_bbcs_install_addon', 'bbcs_install_addon_handler');
function bbcs_install_addon_handler()
{
    if (!current_user_can(bbcs_can_manage())) {
        wp_die(esc_html__('Forbidden.', 'botblocker-security'));
    }
    $nonce_install = isset($_POST['bbcs_install_addon_nonce']) ? sanitize_text_field( wp_unslash( $_POST['bbcs_install_addon_nonce'] ) ) : '';
    if ( empty( $nonce_install ) || ! wp_verify_nonce( $nonce_install, 'bbcs_install_addon' ) ) {
        wp_die(esc_html__('Nonce verification failed.', 'botblocker-security'));
    }
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    $requires_core = isset($_POST['requires_core']) ? sanitize_text_field(wp_unslash($_POST['requires_core'])) : '';
    $redir = bbcs_admin_page_url('bbcs_addons');
    if ($slug === '' || $url === '') {
        wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_error', 'install_args', $redir)));
        exit;
    }
    if ( ! bbcs_is_allowed_addon_url( $url ) ) {
        wp_safe_redirect( esc_url_raw( add_query_arg( 'bbcs_error', 'url_not_allowed', $redir ) ) );
        exit;
    }
    if (!empty($requires_core) && version_compare(BOTBLOCKER_VERSION, $requires_core, '<')) {
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'requires_core', 'bbcs_error_msg' => $requires_core], $redir)));
        exit;
    }
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!WP_Filesystem()) {
        wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_error', 'fs_unavailable', $redir)));
        exit;
    }
    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'download', 'bbcs_error_msg' => $tmp->get_error_message()], $redir)));
        exit;
    }
    $dest = trailingslashit(bbcs_addons_dir());
    if (!is_dir($dest)) {
        wp_mkdir_p($dest);
    }
    $folder = $dest . $slug;
    $backup = $dest . $slug . '_bbcs_bak';

    // Backup existing folder if present
    if (is_dir($backup)) bbcs_rrmdir($backup);
    $backed_up = false;
    if (is_dir($folder)) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
        $backed_up = @rename($folder, $backup);
        if (!$backed_up) {
            bbcs_rrmdir($folder);
        }
    }

    $unz = unzip_file($tmp, $dest);
    if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
    if (is_wp_error($unz)) {
        if ($backed_up) {
            if (is_dir($folder)) bbcs_rrmdir($folder);
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
            @rename($backup, $folder);
        }
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'unzip', 'bbcs_error_msg' => $unz->get_error_message()], $redir)));
        exit;
    }

    // Verify actual requires_core from extracted files
    if (function_exists('bbcs_addon_file_requires_core')) {
        $actual_req = bbcs_addon_file_requires_core($slug);
        if (!empty($actual_req) && version_compare(BOTBLOCKER_VERSION, $actual_req, '<')) {
            if (is_dir($folder)) bbcs_rrmdir($folder);
            if ($backed_up) {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
                @rename($backup, $folder);
            }
            wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'requires_core', 'bbcs_error_msg' => $actual_req], $redir)));
            exit;
        }
    }

    if ($backed_up && is_dir($backup)) {
        bbcs_rrmdir($backup);
    }

    wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_installed', '1', $redir)));
    exit;
}

add_action('admin_post_bbcs_delete_addon', 'bbcs_delete_addon_handler');
function bbcs_delete_addon_handler()
{
    if (!current_user_can(bbcs_can_manage())) {
        wp_die(esc_html__('Forbidden.', 'botblocker-security'));
    }
    $nonce_delete = isset($_POST['bbcs_delete_addon_nonce']) ? sanitize_text_field( wp_unslash( $_POST['bbcs_delete_addon_nonce'] ) ) : '';
    if ( empty( $nonce_delete ) || ! wp_verify_nonce( $nonce_delete, 'bbcs_delete_addon' ) ) {
        wp_die(esc_html__('Nonce verification failed.', 'botblocker-security'));
    }
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    if ($slug === '') {
        wp_safe_redirect(bbcs_admin_page_url('bbcs_addons'));
        exit;
    }
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active))
        $active = [];
    if (in_array($slug, $active, true)) {
        $active = array_values(array_diff($active, [$slug]));
        update_option('botblocker_active_addons', $active);
    }
    //$folder = trailingslashit(BOTBLOCKER_DIR . 'addons') . $slug;
    $folder = trailingslashit(bbcs_addons_dir()) . $slug;
    if (is_dir($folder))
        bbcs_rrmdir($folder);
    wp_safe_redirect(bbcs_admin_page_url('bbcs_addons') . '&bbcs_deleted=1');
    exit;
}

add_action('admin_post_bbcs_update_addon', 'bbcs_update_addon_handler');
function bbcs_update_addon_handler()
{
    if (!current_user_can(bbcs_can_manage())) {
        wp_die(esc_html__('Forbidden.', 'botblocker-security'));
    }
    $nonce_update = isset($_POST['bbcs_update_addon_nonce']) ? sanitize_text_field( wp_unslash( $_POST['bbcs_update_addon_nonce'] ) ) : '';
    if ( empty( $nonce_update ) || ! wp_verify_nonce( $nonce_update, 'bbcs_update_addon' ) ) {
        wp_die(esc_html__('Nonce verification failed.', 'botblocker-security'));
    }
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    $url  = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    $requires_core = isset($_POST['requires_core']) ? sanitize_text_field(wp_unslash($_POST['requires_core'])) : '';
    $redir = bbcs_admin_page_url('bbcs_addons');
    if ($slug === '' || $url === '') {
        wp_safe_redirect(esc_url_raw($redir));
        exit;
    }
    if ( ! bbcs_is_allowed_addon_url( $url ) ) {
        wp_safe_redirect( esc_url_raw( add_query_arg( 'bbcs_error', 'url_not_allowed', $redir ) ) );
        exit;
    }
    if (!empty($requires_core) && version_compare(BOTBLOCKER_VERSION, $requires_core, '<')) {
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'requires_core', 'bbcs_error_msg' => $requires_core], $redir)));
        exit;
    }
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!WP_Filesystem()) {
        wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_error', 'fs_unavailable', $redir)));
        exit;
    }

    // Download BEFORE touching existing files
    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'download', 'bbcs_error_msg' => $tmp->get_error_message()], $redir)));
        exit;
    }

    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active)) $active = [];
    $wasActive = in_array($slug, $active, true);

    // Temporarily deactivate
    if ($wasActive) {
        $active = array_values(array_diff($active, [$slug]));
        update_option('botblocker_active_addons', $active);
    }

    $dest = trailingslashit(bbcs_addons_dir());
    if (!is_dir($dest)) wp_mkdir_p($dest);
    $folder = $dest . $slug;
    $backup = $dest . $slug . '_bbcs_bak';

    // Backup existing folder before replacing
    if (is_dir($backup)) bbcs_rrmdir($backup);
    $backed_up = false;
    if (is_dir($folder)) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
        $backed_up = @rename($folder, $backup);
        if (!$backed_up) {
            bbcs_rrmdir($folder);
        }
    }

    $unz = unzip_file($tmp, $dest);
    if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }

    if (is_wp_error($unz)) {
        if ($backed_up) {
            if (is_dir($folder)) bbcs_rrmdir($folder);
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
            @rename($backup, $folder);
        }
        if ($wasActive) {
            $active = get_option('botblocker_active_addons', []);
            $active[] = $slug;
            update_option('botblocker_active_addons', array_values(array_unique($active)));
        }
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'unzip', 'bbcs_error_msg' => $unz->get_error_message()], $redir)));
        exit;
    }

    // Verify actual requires_core from extracted files
    $actual_req = function_exists('bbcs_addon_file_requires_core') ? bbcs_addon_file_requires_core($slug) : '';
    $skipped_requires_core = '';

    if (!empty($actual_req) && version_compare(BOTBLOCKER_VERSION, $actual_req, '<')) {
        // New version incompatible - restore previous version
        if (is_dir($folder)) bbcs_rrmdir($folder);
        if ($backed_up) {
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
            @rename($backup, $folder);
            if ($wasActive) {
                $old_req = bbcs_addon_file_requires_core($slug);
                if (empty($old_req) || version_compare(BOTBLOCKER_VERSION, $old_req, '>=')) {
                    $active = get_option('botblocker_active_addons', []);
                    $active[] = $slug;
                    update_option('botblocker_active_addons', array_values(array_unique($active)));
                }
            }
        }
        $skipped_requires_core = $actual_req;
    } else {
        // Success - remove backup
        if ($backed_up && is_dir($backup)) {
            bbcs_rrmdir($backup);
        }
        if ($wasActive) {
            $updated_addons = function_exists('bbcs_scan_addons') ? bbcs_scan_addons() : [];
            if (isset($updated_addons[$slug]) && bbcs_is_addon_compatible($updated_addons[$slug])) {
                $active = get_option('botblocker_active_addons', []);
                $active[] = $slug;
                update_option('botblocker_active_addons', array_values(array_unique($active)));
            } elseif (isset($updated_addons[$slug]) && !empty($updated_addons[$slug]['requires_core'])) {
                $skipped_requires_core = $updated_addons[$slug]['requires_core'];
            }
        }
    }

    $redirect_args = ['bbcs_updated' => '1'];
    if (!empty($skipped_requires_core)) {
        $redirect_args['bbcs_requires_core'] = $skipped_requires_core;
    }
    wp_safe_redirect(esc_url_raw(add_query_arg($redirect_args, $redir)));
    exit;
}


function bbcs_rrmdir($dir)
{
    if ( ! function_exists('WP_Filesystem') ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
        WP_Filesystem();
    }
    if ( $wp_filesystem && $wp_filesystem->exists( $dir ) ) {
        // true = recursive delete for dirs
        $wp_filesystem->delete( $dir, true );
        return;
    }
    // Fallback (should rarely run) without direct rmdir/unlink usage warnings suppressed.
    if ( is_file($dir) || is_link($dir) ) {
        if ( file_exists( $dir ) ) { wp_delete_file( $dir ); }
        return;
    }
    if ( ! is_dir($dir) ) return;
    $items = scandir($dir);
    foreach ( $items as $item ) {
        if ( $item === '.' || $item === '..' ) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if ( is_dir( $path ) ) {
            bbcs_rrmdir( $path );
        } else {
            if ( file_exists( $path ) ) { wp_delete_file( $path ); }
        }
    }
    // Attempt to remove now-empty directory via Filesystem if available
    if ( $wp_filesystem && $wp_filesystem->exists( $dir ) ) {
        $wp_filesystem->delete( $dir );
    }
}
