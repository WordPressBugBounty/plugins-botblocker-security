<?php
declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	http_response_code( 404 );
	echo '404 Not Found';
	exit;
}

global $wpdb;

if (
	file_exists( plugin_dir_path( __FILE__ ) . 'includes/inc-botblocker-define.php' )
	&& file_exists( plugin_dir_path( __FILE__ ) . 'includes/database/inc-botblocker-tables.php' )
) {
	define( 'BOTBLOCKER', true );
	define( 'BOTBLOCKER_DIR', plugin_dir_path( __FILE__ ) );
	require_once plugin_dir_path( __FILE__ ) . 'includes/inc-botblocker-define.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/database/inc-botblocker-tables.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/install/class-botblocker-install.php';
} else {
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Define or tables file missing.' );
	}
	exit;
}

if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( '[BBCS DEBUG] [Uninstall] Define and table files loaded successfully.' );
}

if ( method_exists( 'BotBlockerInstall', 'removeWpConfigEarlyInitCode' ) ) {
	BotBlockerInstall::removeWpConfigEarlyInitCode();
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] removeWpConfigEarlyInitCode executed.' );
	}
} else {
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] BotBlockerInstall::removeWpConfigEarlyInitCode not found.' );
	}
}

$bbcs_cron_hooks = array(
	'bbcs_daily_task',
	'bbcs_hourly_task',
	'bbcs_weekly_task',
	'bbcs_five_days_task',
	'bbcs_two_hours_task',
	'bbcs_one_time_task',
	'bbcs_summary_backfill',
	'bbcs_asn_db_freshness_task',
	'bbcs_asn_db_download_event',
	'bbcs_llm_sync_event',
	'bbcs_malware_scheduled_scan_task',
	'bbcs_malware_scan_batch_task',
	'bbcs_rugov_update_event',
);

$bbcs_option_keys = array(
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
	'bbcs_active_addons',
	'bbcs_tools_core_settings',
	'bbcs_tools_login_settings',
	'bbcs_tools_headers_settings',
	'bbcs_asn_db_status',
	'bbcs_llm_sync_status',
	'bbcs_tools_https_protocol_settings',
	'bbcs_tools_malware_settings',
	'bbcs_malware_last_scan',
	'bbcs_malware_scan_state',
	'bbcs_malware_scan_queue',
	'bbcs_malware_signature_db',
	'bbcs_malware_signature_db_status',
	'bbcs_blocked_countries',
	'bbcs_payment_autodetect_lastrun',
	'bbcs_payment_autoenabled_done',
	'bbcs_payment_bypass_autoenabled_at',
	'bbcs_salt_fallback',
	'bbcs_verify_rules_version',
	'bbcs_rugov_sync_status',
	'bbcs_migration_lock',
	// Legacy botblocker_ prefix — kept for sites that haven't migrated
	// TODO: Remove in a future major release after giving sites sufficient time to migrate.
	'botblocker_active_addons',
	'botblocker_tools_core_settings',
	'botblocker_tools_login_settings',
	'botblocker_tools_headers_settings',
	'botblocker_tools_https_protocol_settings',
	'botblocker_tools_malware_settings',
	'botblocker_malware_last_scan',
	'botblocker_malware_scan_state',
	'botblocker_malware_scan_queue',
	'botblocker_malware_signature_db',
	'botblocker_malware_signature_db_status',
);

/**
 * Remove per-site data: cron → tables → options → upload directory.
 */
function bbcs_uninstall_site_data( array $cron_hooks, array $option_keys ): void {
	global $wpdb;

	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] bbcs_uninstall_site_data started for site ID ' . get_current_blog_id() . '.' );
	}

	// 1. Remove cron tasks
	foreach ( $cron_hooks as $hook ) {
		wp_unschedule_hook( $hook );
	}

	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Cron tasks unscheduled, removing transients.' );
	}

	delete_transient( 'bbcs_asn_db_lock' );
	delete_transient( 'bbcs_asn_db_failed_alert' );
	delete_transient( 'bbcs_asn_db_self_heal_throttle' );
	delete_transient( 'bbcs_llm_sync_lock' );
	delete_transient( 'bbcs_llm_sync_self_heal_throttle' );
	delete_transient( 'bbcs_rugov_sync_lock' );
	delete_transient( 'bbcs_rugov_self_heal_throttle' );
	delete_transient( 'bbcs_just_activated' );
	delete_transient( 'bbcs_cron_fallback_last_check' );
	delete_transient( 'bbcs_cron_fallback_lock' );
	delete_transient( 'bbcs_salt_write_error' );
	delete_transient( 'bbcs_remaining_days' );
	delete_transient( 'bbcs_remaining_hits' );
	delete_transient( 'bbcs_cloud_connection_failed_alert' );
	delete_transient( 'bbcs_missing_files_alert' );
	delete_transient( 'bbcs_cloud_api_expired_alert' );
	delete_transient( 'bbcs_cloud_api_hits_exhausted_alert' );
	delete_transient( 'bbcs_addon_update_failed_alert' );
	delete_transient( 'bbcs_addon_incompatible_alert' );
	delete_transient( 'bbcs_ip_render_throttle' );
	delete_transient( 'bbcs_security_headers_mu_error' );

	// Clean up prefix-based transients (bbcs_file_tampered_*, bbcs_dst_offset_*, bbcs_ct_*, bbcs_2fa_attempts_*, bbcs_backup_download_*, bbcs_debug_log_*, etc.)
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM `{$wpdb->options}` WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_bbcs_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_bbcs_' ) . '%'
		)
	);

	// 2. Drop plugin tables
	$bbcs_tables = array(
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
		$wpdb->bbcs_asn,
		$wpdb->bbcs_llm_trusted,
	);

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

	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Plugin tables dropped, cleaning options.' );
	}

	// 3. Clean up options
	foreach ( $option_keys as $key ) {
		delete_option( $key );
	}

	// 4. Remove per-site upload directory
	$slug    = defined( 'BOTBLOCKER_SHORT_NAME' ) ? sanitize_title( BOTBLOCKER_SHORT_NAME ) : 'botblocker';
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . $slug;
	if ( is_dir( $dir ) ) {
		bbcs_uninstall_rmdir_recursive( $dir );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Uninstall] Upload directory removed: ' . $dir );
		}
	}

	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] bbcs_uninstall_site_data completed for site ID ' . get_current_blog_id() . '.' );
	}
}

/**
 * Recursively remove a directory and all its contents.
 */
function bbcs_uninstall_rmdir_recursive( string $dir ): void {
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
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Multisite detected, processing all sites.' );
	}

	$bbcs_uninstall_offset   = 0;
	$bbcs_uninstall_per_page = 50;
	do {
		$bbcs_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $bbcs_uninstall_per_page,
				'offset' => $bbcs_uninstall_offset,
			)
		);
		foreach ( $bbcs_site_ids as $bbcs_site_id ) {
			switch_to_blog( $bbcs_site_id );
			try {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Uninstall] Processing site ID ' . $bbcs_site_id . '.' );
				}
				require plugin_dir_path( __FILE__ ) . 'includes/database/inc-botblocker-tables.php';
				bbcs_uninstall_site_data( $bbcs_cron_hooks, $bbcs_option_keys );
			} finally {
				restore_current_blog();
			}
		}
		$bbcs_uninstall_offset += $bbcs_uninstall_per_page;
	} while ( count( $bbcs_site_ids ) === $bbcs_uninstall_per_page );

	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Multisite data cleanup complete, deleting site options.' );
	}
	require plugin_dir_path( __FILE__ ) . 'includes/database/inc-botblocker-tables.php';
	delete_site_option( 'bbcs_network_license_key' );
	delete_site_option( 'bbcs_network_cloud_api_key' );
	delete_site_option( 'bbcs_sites_map_dirty' );
	delete_site_option( 'botblocker_network_license_key' );
	delete_site_option( 'botblocker_network_cloud_api_key' );
	delete_site_transient( 'bbcs_sites_map_regen_lock' );
	delete_site_transient( 'bbcs_wpconfig_writing' );

	// Clean up prefix-based site transients on multisite
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM `{$wpdb->sitemeta}` WHERE meta_key LIKE %s OR meta_key LIKE %s",
			$wpdb->esc_like( '_site_transient_bbcs_' ) . '%',
			$wpdb->esc_like( '_site_transient_timeout_bbcs_' ) . '%'
		)
	);
} else {
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Single site, running bbcs_uninstall_site_data.' );
	}
	bbcs_uninstall_site_data( $bbcs_cron_hooks, $bbcs_option_keys );
}

$bbcs_mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php';
if ( file_exists( $bbcs_mu_plugin_file ) ) {
	wp_delete_file( $bbcs_mu_plugin_file );
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] MU plugin removed: ' . $bbcs_mu_plugin_file );
	}
}

$bbcs_sec_headers_mu = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-security-headers.php';
if ( file_exists( $bbcs_sec_headers_mu ) ) {
	wp_delete_file( $bbcs_sec_headers_mu );
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Security headers MU removed: ' . $bbcs_sec_headers_mu );
	}
}
