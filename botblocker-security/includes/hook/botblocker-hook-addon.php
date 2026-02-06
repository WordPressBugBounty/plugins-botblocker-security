<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_post_bbcs_toggle_addon', 'bbcs_toggle_addon_handler');
function bbcs_toggle_addon_handler()
{
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
    }
    check_admin_referer( 'bbcs_toggle_addon', 'bbcs_toggle_addon_nonce' );
    
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    if ($slug === '') {
        wp_safe_redirect(admin_url('admin.php?page=bbcs_addons'));
        exit;
    }
    if (!function_exists('bbcs_scan_addons')) {
        wp_safe_redirect(admin_url('admin.php?page=bbcs_addons'));
        exit;
    }
    $addons = bbcs_scan_addons();
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active))
        $active = [];
    if (!isset($addons[$slug]) || !$addons[$slug]['valid']) {
        $active = array_values(array_diff($active, [$slug]));
        update_option('botblocker_active_addons', $active);
        wp_safe_redirect(admin_url('admin.php?page=bbcs_addons&bbcs_error=invalid'));
        exit;
    }
    if (in_array($slug, $active, true)) {
        $active = array_values(array_diff($active, [$slug]));
    } else {
        $active[] = $slug;
        $active = array_values(array_unique($active));
    }
    update_option('botblocker_active_addons', $active);
    wp_safe_redirect(admin_url('admin.php?page=bbcs_addons&bbcs_updated=1'));
    exit;
}

add_action('admin_post_bbcs_install_addon', 'bbcs_install_addon_handler');
function bbcs_install_addon_handler()
{
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden');
    }
    $nonce_install = isset($_POST['bbcs_install_addon_nonce']) ? sanitize_text_field( wp_unslash( $_POST['bbcs_install_addon_nonce'] ) ) : '';
    if ( empty( $nonce_install ) || ! wp_verify_nonce( $nonce_install, 'bbcs_install_addon' ) ) {
        wp_die('Nonce verification failed');
    }
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    $redir = admin_url('admin.php?page=bbcs_addons');
    if ($slug === '' || $url === '') {
        wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_error', 'install_args', $redir)));
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
    //$dest = trailingslashit(BOTBLOCKER_DIR . 'addons');
    $dest = trailingslashit(BOTBLOCKER_ADDONS_DIR);
    if (!is_dir($dest)) {
        wp_mkdir_p($dest);
    }
    $folder = $dest . $slug;
    if (is_dir($folder))
        bbcs_rrmdir($folder);
    $unz = unzip_file($tmp, $dest);
    if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
    if (is_wp_error($unz)) {
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'unzip', 'bbcs_error_msg' => $unz->get_error_message()], $redir)));
        exit;
    }
    wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_installed', '1', $redir)));
    exit;
}

add_action('admin_post_bbcs_delete_addon', 'bbcs_delete_addon_handler');
function bbcs_delete_addon_handler()
{
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden');
    }
    $nonce_delete = isset($_POST['bbcs_delete_addon_nonce']) ? sanitize_text_field( wp_unslash( $_POST['bbcs_delete_addon_nonce'] ) ) : '';
    if ( empty( $nonce_delete ) || ! wp_verify_nonce( $nonce_delete, 'bbcs_delete_addon' ) ) {
        wp_die('Nonce verification failed');
    }
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    if ($slug === '') {
        wp_safe_redirect(admin_url('admin.php?page=bbcs_addons'));
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
    $folder = trailingslashit(BOTBLOCKER_ADDONS_DIR) . $slug;
    if (is_dir($folder))
        bbcs_rrmdir($folder);
    wp_safe_redirect(admin_url('admin.php?page=bbcs_addons&bbcs_deleted=1'));
    exit;
}

add_action('admin_post_bbcs_update_addon', 'bbcs_update_addon_handler');
function bbcs_update_addon_handler()
{
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden');
    }
    $nonce_update = isset($_POST['bbcs_update_addon_nonce']) ? sanitize_text_field( wp_unslash( $_POST['bbcs_update_addon_nonce'] ) ) : '';
    if ( empty( $nonce_update ) || ! wp_verify_nonce( $nonce_update, 'bbcs_update_addon' ) ) {
        wp_die('Nonce verification failed');
    }
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    $redir = admin_url('admin.php?page=bbcs_addons');
    if ($slug === '' || $url === '') {
        wp_safe_redirect(esc_url_raw($redir));
        exit;
    }
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active))
        $active = [];
    $wasActive = in_array($slug, $active, true);
    $active = array_values(array_diff($active, [$slug]));
    update_option('botblocker_active_addons', $active);

    //$folder = trailingslashit(BOTBLOCKER_DIR . 'addons') . $slug;
    $folder = trailingslashit(BOTBLOCKER_ADDONS_DIR) . $slug;
    if (is_dir($folder))
        bbcs_rrmdir($folder);
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

    //$dest = trailingslashit(BOTBLOCKER_DIR . 'addons');
    $dest = trailingslashit(BOTBLOCKER_ADDONS_DIR);
    if (!is_dir($dest))
        wp_mkdir_p($dest);
    $unz = unzip_file($tmp, $dest);
    if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
    if (is_wp_error($unz)) {
        wp_safe_redirect(esc_url_raw(add_query_arg(['bbcs_error' => 'unzip', 'bbcs_error_msg' => $unz->get_error_message()], $redir)));
        exit;
    }
    if ($wasActive) {
        $active = get_option('botblocker_active_addons', []);
        $active[] = $slug;
        $active = array_values(array_unique($active));
        update_option('botblocker_active_addons', $active);
    }
    wp_safe_redirect(esc_url_raw(add_query_arg('bbcs_updated', '1', $redir)));
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
