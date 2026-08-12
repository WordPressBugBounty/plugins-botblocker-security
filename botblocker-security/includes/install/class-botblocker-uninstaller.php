<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	http_response_code( 404 );
	echo '404 Not Found';
	exit;
}

class Botblocker_Uninstaller {

	/**
	 * Option keys deleted per-site during uninstall.
	 *
	 * @var string[]
	 */
	public static array $option_keys = array(
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
		'bbcs_asn_db_status',
		'bbcs_llm_sync_status',
		'bbcs_blocked_countries',
		'bbcs_payment_autodetect_lastrun',
		'bbcs_payment_autoenabled_done',
		'bbcs_payment_bypass_autoenabled_at',
		'bbcs_salt_fallback',
		'bbcs_verify_rules_version',
		'bbcs_rugov_sync_status',
		'bbcs_tls_fingerprints_sync_status',
		'bbcs_migration_lock',
		// Legacy botblocker_ prefix - kept for sites that haven't migrated
		'botblocker_active_addons',
	);

	/**
	 * Named transients deleted per-site during uninstall.
	 *
	 * @var string[]
	 */
	public static array $named_transients = array(
		'bbcs_asn_db_lock',
		'bbcs_asn_db_failed_alert',
		'bbcs_asn_db_self_heal_throttle',
		'bbcs_llm_sync_lock',
		'bbcs_llm_sync_self_heal_throttle',
		'bbcs_rugov_sync_lock',
		'bbcs_rugov_self_heal_throttle',
		'bbcs_just_activated',
		'bbcs_cron_fallback_last_check',
		'bbcs_cron_fallback_lock',
		'bbcs_salt_write_error',
		'bbcs_remaining_days',
		'bbcs_remaining_hits',
		'bbcs_cloud_connection_failed_alert',
		'bbcs_missing_files_alert',
		'bbcs_cloud_api_expired_alert',
		'bbcs_cloud_api_hits_exhausted_alert',
		'bbcs_addon_update_failed_alert',
		'bbcs_addon_incompatible_alert',
		'bbcs_ip_render_throttle',
	);

	/**
	 * $wpdb property names for plugin tables dropped during uninstall.
	 *
	 * @var string[]
	 */
	public static array $table_properties = array(
		'bbcs_hits',
		'bbcs_hits_suspicious',
		'bbcs_hits_cloud',
		'bbcs_se',
		'bbcs_proxy',
		'bbcs_path',
		'bbcs_rules',
		'bbcs_settings',
		'bbcs_ptrcache',
		'bbcs_ipv4rules',
		'bbcs_ipv6rules',
		'bbcs_counters',
		'bbcs_page_filters',
		'bbcs_daily_summary',
		'bbcs_asn',
		'bbcs_llm_trusted',
		'bbcs_tls_fingerprints',
		'bbcs_countries',
		'bbcs_fingerprint',
		'bbcs_sessions',
	);

	/**
	 * Network-level option keys deleted during multisite uninstall.
	 *
	 * @var string[]
	 */
	public static array $network_option_keys = array(
		'bbcs_network_license_key',
		'bbcs_network_cloud_api_key',
		'bbcs_sites_map_dirty',
		'botblocker_network_license_key',
		'botblocker_network_cloud_api_key',
	);

	/**
	 * Network-level named transients deleted during multisite uninstall.
	 *
	 * @var string[]
	 */
	public static array $network_named_transients = array(
		'bbcs_sites_map_regen_lock',
		'bbcs_wpconfig_writing',
	);

	private static function isDebug(): bool {
		return defined( 'BBCS_DEBUG' ) && BBCS_DEBUG;
	}

	private static function log( string $message ): void {
		if ( ! self::isDebug() ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] ' . $message );
	}

	/**
	 * Entry point for plugin uninstallation.
	 *
	 * Guarded by WP_UNINSTALL_PLUGIN constant (checked in uninstall.php before
	 * this class is loaded). Handles addon cleanup, per-site data removal,
	 * multisite iteration, and MU plugin removal.
	 */
	public static function uninstall(): void {
		$debug   = self::isDebug();
		$started = $debug ? microtime( true ) : 0.0;

		if ( $debug ) {
			self::log( '===== uninstall() START | plugin version ' . ( defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : 'unknown' ) . ' =====' );
		}

		self::uninstallAddons();

		if ( is_multisite() ) {
			if ( $debug ) {
				self::log( 'Site type: multisite. Running uninstallNetwork().' );
			}
			self::uninstallNetwork();
		} else {
			if ( $debug ) {
				self::log( 'Site type: single site. Running uninstallSite().' );
			}
			self::uninstallSite();
		}

		self::removeMuPlugin();

		if ( $debug ) {
			self::log( sprintf( '===== uninstall() DONE in %.3fs =====', microtime( true ) - $started ) );
		}
	}

	/**
	 * Dispatch 'delete' lifecycle to all active add-ons.
	 */
	private static function uninstallAddons(): void {
		self::log( '--- STEP: addon cleanup ---' );

		if ( ! file_exists( BOTBLOCKER_DIR . 'includes/class-botblocker-addons.php' ) ) {
			self::log( 'Addons: file class-botblocker-addons.php missing. SKIPPED.' );
			return;
		}
		require_once BOTBLOCKER_DIR . 'includes/class-botblocker-addons.php';

		if ( ! class_exists( 'BotBlockerAddons' ) ) {
			self::log( 'Addons: class BotBlockerAddons not found after require. SKIPPED.' );
			return;
		}

		try {
			$addons = BotBlockerAddons::scanAll();
			$active = BotBlockerAddons::getActive();

			foreach ( $active as $slug ) {
				if ( isset( $addons[ $slug ] ) && ! empty( $addons[ $slug ]['valid'] ) ) {
					BotBlockerAddons::loadCore( $addons[ $slug ] );
					BotBlockerAddons::dispatchLifecycle(
						$slug, 'delete', $addons[ $slug ],
						array( 'reason' => 'delete' )
					);
					if ( self::isDebug() ) {
						self::log( 'Addons: dispatched "delete" lifecycle to ' . $slug . '.' );
					}
				} elseif ( self::isDebug() ) {
					self::log( 'Addons: ' . $slug . ' active but missing or invalid on disk. SKIPPED.' );
				}
			}

		} catch ( \Throwable $e ) {
			if ( self::isDebug() ) {
				self::log( 'Addons: ERROR - ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Uninstall across all sites in a multisite network.
	 *
	 * Iterates all site IDs in pages of 50, performing per-site cleanup
	 * followed by network-level option and transient removal.
	 */
	private static function uninstallNetwork(): void {
		$site_ids = BotBlockerMultisite::getAllSiteIds( 50 );

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			try {
				require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
				self::uninstallSite();
			} finally {
				restore_current_blog();
			}
		}

		self::log( '--- STEP: network-level options and transients ---' );

		require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';

		// Network-level options
		foreach ( self::$network_option_keys as $key ) {
			delete_site_option( $key );
		}

		// Network-level named transients
		foreach ( self::$network_named_transients as $key ) {
			delete_site_transient( $key );
		}

		// Prefix-based site transients
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->sitemeta}`
				 WHERE meta_key LIKE %s OR meta_key LIKE %s",
				$wpdb->esc_like( '_site_transient_bbcs_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_bbcs_' ) . '%'
			)
		);
	}

	/**
	 * Remove all per-site plugin data: cron → transients → tables → options → upload dir.
	 */
	private static function uninstallSite(): void {
		$debug  = self::isDebug();
		$site_id = get_current_blog_id();

		if ( $debug ) {
			self::log( '--- SITE ' . $site_id . ': cleanup START ---' );
		}

		// 1. Remove cron tasks (delegates to BotBlockerCron, single source of truth)
		if ( class_exists( 'BotBlockerCron' ) ) {
			BotBlockerCron::removeTasks();
			if ( $debug ) {
				self::log( 'Site ' . $site_id . ' step 1/6 cron: BotBlockerCron::removeTasks() called.' );
			}
		} elseif ( $debug ) {
			self::log( 'Site ' . $site_id . ' step 1/6 cron: class BotBlockerCron not loaded. SKIPPED.' );
		}

		// 2a. Named transients
		self::cleanupNamedTransients();

		// 2b. Prefix-based transients
		self::cleanupPrefixTransients();

		// 3. Drop plugin tables
		self::dropTables();

		// 4. Clean up named options
		self::cleanupOptions();

		// 5. Clean up any remaining prefix-based options (deactivated addons, etc.)
		self::cleanupPrefixOptions();

		// 6. Remove per-site upload directory
		$addon_cache_dirs = array( 'bbcs-malware-ts-cache', 'bbcs-truth-source' );
		if ( class_exists( 'BotBlockerAddons' ) ) {
			$addons_dir = BotBlockerAddons::rootDir();
			if ( $addons_dir !== '' && is_dir( $addons_dir ) ) {
				foreach ( BotBlockerAddons::scanAll() as $addon ) {
					if ( ! empty( $addon['storage']['cache_dirs'] ) && is_array( $addon['storage']['cache_dirs'] ) ) {
						foreach ( $addon['storage']['cache_dirs'] as $cache_dir ) {
							$addon_cache_dirs[] = $cache_dir;
						}
					}
				}
			}
		}
		self::removeUploadDirectory( array_unique( $addon_cache_dirs ) );

		if ( $debug ) {
			self::log( '--- SITE ' . $site_id . ': cleanup DONE ---' );
		}
	}

	/**
	 * Delete all named plugin transients.
	 */
	private static function cleanupNamedTransients(): void {
		foreach ( self::$named_transients as $key ) {
			delete_transient( $key );
		}
	}

	/**
	 * Delete all prefix-matching plugin transients from the options table.
	 *
	 * Covers site transients too. On a single site install WordPress stores
	 * site transients in the options table under a _site_transient_ prefix,
	 * so matching only _transient_ leaves them behind.
	 */
	private static function cleanupPrefixTransients(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->options}`
				 WHERE option_name LIKE %s OR option_name LIKE %s
				    OR option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_bbcs_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_bbcs_' ) . '%',
				$wpdb->esc_like( '_site_transient_bbcs_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_bbcs_' ) . '%'
			)
		);
	}

	/**
	 * Drop all plugin database tables.
	 */
	private static function dropTables(): void {
		global $wpdb;

		foreach ( self::$table_properties as $prop ) {
			if ( empty( $wpdb->$prop ) ) {
				continue;
			}
			$table = $wpdb->$prop;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		}
	}

	/**
	 * Delete all plugin options from the options table.
	 */
	private static function cleanupOptions(): void {
		foreach ( self::$option_keys as $key ) {
			delete_option( $key );
		}
	}

	/**
	 * Delete any remaining plugin options by prefix.
	 *
	 * Catches addon-specific options left behind when addons are deactivated
	 * before full plugin uninstall, as well as any bbcs_ options not
	 * explicitly listed in $option_keys.
	 */
	private static function cleanupPrefixOptions(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->options}` WHERE option_name LIKE %s",
				$wpdb->esc_like( 'bbcs_' ) . '%'
			)
		);
	}

	/**
	 * Remove the plugin's per-site upload directory.
	 */
	private static function removeUploadDirectory( array $addon_cache_dirs = array() ): void {
		$slug    = defined( 'BOTBLOCKER_SHORT_NAME' ) ? sanitize_title( BOTBLOCKER_SHORT_NAME ) : 'botblocker';
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . $slug;

		if ( self::isDebug() ) {
			self::log( 'Step 6/6 upload dir cleanup: ' . $dir );
		}
		self::removeDirectory( $dir );

		foreach ( $addon_cache_dirs as $cache_dir ) {
			$path = trailingslashit( $uploads['basedir'] ) . $cache_dir;
			if ( self::isDebug() ) {
				self::log( 'Step 6/6 addon cache cleanup: ' . $path );
			}
			self::removeDirectory( $path );
		}
	}

	/**
	 * Recursively remove a directory using WP_Filesystem.
	 */
	private static function removeDirectory( string $dir ): void {
		if ( self::isDebug() ) {
			self::log( 'removeDirectory(): ' . $dir );
		}

		if ( ! is_dir( $dir ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		if ( ! empty( $wp_filesystem ) ) {
			@$wp_filesystem->delete( $dir, true );
		} elseif ( self::isDebug() ) {
			self::log( 'removeDirectory(): WP_Filesystem unavailable, using native fallback.' );
		}

		if ( is_dir( $dir ) ) {
			if ( self::isDebug() ) {
				self::log( 'removeDirectory(): WP_Filesystem left ' . $dir . ' in place, running native fallback.' );
			}
			self::removeDirectoryNative( $dir );
		}
	}

	private static function removeDirectoryNative( string $dir ): void {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- intentional fallback when WP_Filesystem is unavailable
				@rmdir( $item->getPathname() );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- intentional fallback when WP_Filesystem is unavailable
				@unlink( $item->getPathname() );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- intentional fallback when WP_Filesystem is unavailable
		@rmdir( $dir );
	}

	/**
	 * Remove the BotBlocker MU plugin loader file.
	 */
	private static function removeMuPlugin(): void {
		self::log( '--- STEP: MU plugin cleanup ---' );

		$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php';

		if ( ! file_exists( $mu_plugin_file ) ) {
			if ( self::isDebug() ) {
				self::log( 'MU plugin: ' . $mu_plugin_file . ' not present (early phase was never enabled). Nothing to remove.' );
			}
			return;
		}

		wp_delete_file( $mu_plugin_file );

		if ( self::isDebug() ) {
			self::log(
				'MU plugin: ' . $mu_plugin_file
				. ( file_exists( $mu_plugin_file ) ? ' STILL EXISTS after wp_delete_file() - check permissions.' : ' removed OK.' )
			);
		}
	}
}
