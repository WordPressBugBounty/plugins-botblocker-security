<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//BBCS-MULTISITE
require_once BOTBLOCKER_DIR . 'core-helpers-shield.php';

final class BotBlockerMultisite {

	public static function isNetworkActive(): bool {
		if ( ! is_multisite() ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active_for_network( BOTBLOCKER_BASENAME );
	}

	public static function getNetworkOptionKeys(): array {
		return array(
			'bbcs_network_license_key',
			'bbcs_network_cloud_api_key',
		);
	}

	public static function getOption( string $key, $default = false ) {
		if ( is_multisite() && in_array( $key, self::getNetworkOptionKeys(), true ) ) {
			return get_site_option( $key, $default );
		}
		return get_option( $key, $default );
	}

	public static function updateOption( string $key, $value, $autoload = null ): bool {
		if ( is_multisite() && in_array( $key, self::getNetworkOptionKeys(), true ) ) {
			return update_site_option( $key, $value );
		}
		if ( null !== $autoload ) {
			return update_option( $key, $value, $autoload );
		}
		return update_option( $key, $value );
	}

	public static function deleteOption( string $key ): bool {
		if ( is_multisite() && in_array( $key, self::getNetworkOptionKeys(), true ) ) {
			return delete_site_option( $key );
		}
		return delete_option( $key );
	}

	public static function getCurrentSiteUrl(): string {
		return get_site_url();
	}

	public static function getCurrentSiteClear(): string {
		return bbcs_full_domain_with_underscores( get_site_url() );
	}

	public static function getCurrentSiteName(): string {
		return get_bloginfo( 'name' );
	}

	public static function getCurrentSiteEmail(): string {
		return get_bloginfo( 'admin_email' );
	}

	public static function getCurrentUserAgent(): string {
		return 'BotBlocker-Security-Plugin/ ' . BOTBLOCKER_VERSION . ' by https://globus.studio; Client:' . get_bloginfo( 'url' );
	}

	public static function canManage(): string {
		if ( is_multisite() && self::isNetworkActive() ) {
			return 'manage_network_options';
		}
		return 'manage_options';
	}

	/**
	 * Return all site IDs in the network using paginated queries.
	 *
	 * Using 'number' => 0 loads every row into memory at once - on networks with
	 * 1 000+ sites this can exhaust the PHP memory limit.  This helper fetches IDs
	 * in batches of $per_page (default 50) so memory usage stays bounded regardless
	 * of network size.
	 *
	 * On a single-site install (or when get_sites() is unavailable) the function
	 * returns an array containing only the current blog ID.
	 *
	 * @param int $per_page Batch size (default 50).
	 * @return int[] Ordered list of integer site IDs.
	 */
	public static function getAllSiteIds( int $per_page = 50 ): array {
		if ( ! is_multisite() || ! function_exists( 'get_sites' ) ) {
			return array( get_current_blog_id() );
		}

		$all_ids = array();
		$offset  = 0;

		do {
			$batch = get_sites(
				array(
					'fields' => 'ids',
					'number' => $per_page,
					'offset' => $offset,
				)
			);
			foreach ( $batch as $id ) {
				$all_ids[] = (int) $id;
			}
			$offset += $per_page;
		} while ( count( $batch ) === $per_page );

		return $all_ids;
	}

	public static function forEachSite( callable $callback ): void {
		if ( is_multisite() && function_exists( 'get_sites' ) ) {
			$site_ids = self::getAllSiteIds();
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				try {
					require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
					$callback( $site_id );
				} catch ( \Throwable $e ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[BBCS DEBUG] [Multisite] foreach_site failed for site ' . $site_id . ': ' . $e->getMessage() );
					}
				} finally {
					restore_current_blog();
				}
			}
			require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
		} else {
			$callback( get_current_blog_id() );
		}
	}

	private static $cachedUploadsDir = null;

	public static function getUploadsDir(): string {
		if ( self::$cachedUploadsDir !== null ) {
			return self::$cachedUploadsDir;
		}
		$dir = bbcs_get_protected_upload_dir();
		if ( $dir === null || is_wp_error( $dir ) ) {
			$dir = bbcs_create_protected_upload_dir();
		}
		if ( is_wp_error( $dir ) || ! is_string( $dir ) ) {
			self::$cachedUploadsDir = '';
			return '';
		}
		self::$cachedUploadsDir = $dir;
		return $dir;
	}

	public static function getDataDir(): string {
		if ( defined( 'BOTBLOCKER_DATA_DIR' ) ) {
			return rtrim( (string) BOTBLOCKER_DATA_DIR, '/\\' ) . '/';
		}
		static $dataDir = null;
		if ( $dataDir === null ) {
			$dataDir = self::$cachedUploadsDir !== null
				? self::$cachedUploadsDir . 'data/'
				: self::getUploadsDir() . 'data/';
		}
		return $dataDir;
	}

	public static function getNetworkDataDir(): string {
		if ( ! is_multisite() ) {
			return self::getDataDir();
		}

		static $cached = null;
		if ( $cached !== null ) {
			return $cached;
		}

		$main_site_id = get_main_site_id();
		$current_blog = get_current_blog_id();

		if ( $current_blog === $main_site_id ) {
			$cached = self::getDataDir();
			return $cached;
		}

		switch_to_blog( $main_site_id );
		$cached = self::getDataDir();
		restore_current_blog();

		return $cached;
	}

	public static function isAddonsLocalMode(): bool {
		return defined( 'BOTBLOCKER_ADDONS_MODE' ) && defined( 'BOTBLOCKER_MODE_LOCAL' )
			&& BOTBLOCKER_ADDONS_MODE === BOTBLOCKER_MODE_LOCAL;
	}

	public static function getPluginAddonsDir(): string {
		return trailingslashit( wp_normalize_path( BOTBLOCKER_DIR ) ) . 'addons/';
	}

	public static function getAddonsDir(): string {
		if ( self::isAddonsLocalMode() ) {
			return self::getPluginAddonsDir();
		}
		return self::getUploadsDir() . 'addons/';
	}

	public static function getAddonsUrl(): string {
		if ( self::isAddonsLocalMode() ) {
			return trailingslashit( BOTBLOCKER_URL ) . 'addons/';
		}
		$url = bbcs_get_protected_upload_dir( true );
		if ( ! is_string( $url ) || $url === '' ) {
			return '';
		}
		return $url . 'addons/';
	}

	public static function getAdminPageUrl( string $path = '' ): string {
		$network = is_multisite() && self::isNetworkActive() && is_network_admin();
		$base = $network
			? network_admin_url( 'admin.php' )
			: admin_url( 'admin.php' );
		return $path !== '' ? $base . '?page=' . $path : $base;
	}

	public static function getSiteAdminPageUrl( string $path = '' ): string {
		$base = admin_url( 'admin.php' );
		return $path !== '' ? $base . '?page=' . $path : $base;
	}

	public static function syncCloudSettingsNetwork( array $settings ): void {
		self::forEachSite(
			function ( $site_id ) use ( $settings ) {
				global $wpdb;
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized.
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				foreach ( $settings as $key => $value ) {
						$wpdb->update(
							$wpdb->bbcs_settings,
							array( 'value' => $value ),
							array( 'key' => $key )
						);
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				BotBlockerFileRenderer::generateSettingsFile();

				delete_transient( 'bbcs_cloud_api_expired_alert' );
				delete_transient( 'bbcs_cloud_api_hits_exhausted_alert' );
				BotBlockerCache::deleteCacheData( 'bbcs_cloud_api_status_transient' );
			}
		);
	}
}
