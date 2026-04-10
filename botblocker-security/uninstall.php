<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

global $wpdb;

if (
	file_exists(plugin_dir_path(__FILE__) . 'includes/inc-botblocker-define.php')
	&& file_exists(plugin_dir_path(__FILE__) . 'includes/inc-botblocker-tables.php')
) {
	define('BOTBLOCKER', true);
	define('BOTBLOCKER_DIR', plugin_dir_path(__FILE__));
    require_once plugin_dir_path(__FILE__) . 'includes/inc-botblocker-define.php';
	require_once plugin_dir_path(__FILE__) . 'includes/inc-botblocker-tables.php';
	require_once plugin_dir_path(__FILE__) . 'includes/install/botblocker-install-files.php';
} else {
    // error_log('Uninstall.php: Define or tables file missing.');
    exit;
}

if ( function_exists( 'bbcs_removeWpConfigEarlyInitCode' ) ) {
	bbcs_removeWpConfigEarlyInitCode();
}

$bbcs_cron_hooks = [
	'bbcs_daily_task',
	'bbcs_hourly_task',
	'bbcs_weekly_task',
	'bbcs_five_days_task',
	'bbcs_two_hours_task',
	'bbcs_one_time_task',
];

$bbcs_option_keys = [
	'bbcs_db_version',
	'bbcs_wizard_completed',
	'bbcs_setup_wizard_completed',
	'bbcs_setup_wizard_completed_at',
	'bbcs_wizard_cache_type',
	'bbcs_wizard_preset',
	'bbcs_wizard_ux_mode',
	'bbcs_wizard_captcha_mode',
	'bbcs_wizard_init_mode',
	'bbcs_contact_email_collected',
	'bbcs_support_data',
	'bbcs_2fa_rules_version',
	'bbcs_activation_redirect',
	'bbcs_activation_prevent_redirect',
	'bbcs_initial_version',
	'botblocker_active_addons',
	'botblocker_tools_core_settings',
	'botblocker_tools_login_settings',
	'botblocker_tools_headers_settings',				// <---------- ДОБАВЛЕНО !!!!!!!!!!
];

/**
 * Remove per-site data: cron → tables → options → upload directory.
 */
function bbcs_uninstall_site_data( $cron_hooks, $option_keys ) {
	global $wpdb;

	// 1. Remove cron tasks
	foreach ( $cron_hooks as $hook ) {
		wp_clear_scheduled_hook( $hook );
	}

	// 2. Drop plugin tables
	$bbcs_tables = [
		$wpdb->bbcs_hits,
		$wpdb->bbcs_hits_suspicious,
		$wpdb->bbcs_hits_cloud,
		$wpdb->bbcs_se,
		$wpdb->bbcs_proxy,
		$wpdb->bbcs_path,
		$wpdb->bbcs_rules,
		$wpdb->bbcs_settings,
		$wpdb->bbcs_ptrcache,
		$wpdb->bbcs_ipv4rules,
		$wpdb->bbcs_ipv6rules,
		$wpdb->bbcs_counters,
		$wpdb->bbcs_page_filters,
		$wpdb->bbcs_daily_summary,
	];

	/** REVIEWER NOTE:
	 * Direct DROP TABLE statements are necessary in uninstall - wpdb::prepare()
	 * cannot bind identifiers. Suppressing Sniffs for schema-level queries.
	 */
	foreach ( $bbcs_tables as $bbcs_table ) {
		if ( empty( $bbcs_table ) ) {
			continue;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS `{$bbcs_table}`" );
	}

	// 3. Clean up options
	foreach ( $option_keys as $key ) {
		delete_option( $key );
	}

	// 4. Remove per-site upload directory
	$slug = defined( 'BOTBLOCKER_SHORT_NAME' ) ? sanitize_title( BOTBLOCKER_SHORT_NAME ) : 'botblocker';
	$uploads = wp_upload_dir();
	$dir = trailingslashit( $uploads['basedir'] ) . $slug;
	if ( is_dir( $dir ) ) {
		bbcs_uninstall_rmdir_recursive( $dir );
	}
}

/**
 * Recursively remove a directory and all its contents.
 */
function bbcs_uninstall_rmdir_recursive( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	global $wp_filesystem;
	
	if ( empty( $wp_filesystem ) ) {
		WP_Filesystem();
	}

	if ( ! empty( $wp_filesystem ) ) {
		$wp_filesystem->delete( $dir, true );
	}
}

//BBCS-MULTISITE
if ( is_multisite() ) {
	$bbcs_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $bbcs_site_ids as $bbcs_site_id ) {
		switch_to_blog( $bbcs_site_id );
		require plugin_dir_path(__FILE__) . 'includes/inc-botblocker-tables.php';
		bbcs_uninstall_site_data( $bbcs_cron_hooks, $bbcs_option_keys );
		restore_current_blog();
	}
	require plugin_dir_path(__FILE__) . 'includes/inc-botblocker-tables.php';
	delete_site_option( 'bbcs_network_license_key' );
	delete_site_option( 'bbcs_network_cloud_api_key' );
} else {
	bbcs_uninstall_site_data( $bbcs_cron_hooks, $bbcs_option_keys );
}

$bbcs_mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php';
if ( file_exists( $bbcs_mu_plugin_file ) ) {
	wp_delete_file( $bbcs_mu_plugin_file );
}

$bbcs_sec_headers_mu = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-security-headers.php';
if ( file_exists( $bbcs_sec_headers_mu ) ) {
	wp_delete_file( $bbcs_sec_headers_mu );
}
