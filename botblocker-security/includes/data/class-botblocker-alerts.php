<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// core-helpers.php is also loaded by the main plugin bootstrap (botblocker-security.php:68).
// This require_once is a safety net: when BotBlockerAlerts is used in isolation
// (e.g., by external tooling, CLI scripts, or addon bootstrap), the helpers are
// guaranteed to be available without depending on the main plugin load order.
require_once BOTBLOCKER_DIR . 'core-helpers.php';

class BotBlockerAlerts {

	public static function getAll(): array {
		$alerts = array();

		$failed_alert_err = get_transient( 'bbcs_cloud_connection_failed_alert' );
		if ( ! empty( $failed_alert_err ) ) {
			$alerts[] = $failed_alert_err;
		}

		$missing_files_alert = get_transient( 'bbcs_missing_files_alert' );
		if ( ! empty( $missing_files_alert ) ) {
			$alerts[] = $missing_files_alert;
		}

		$cloud_api_expired_alert = get_transient( 'bbcs_cloud_api_expired_alert' );
		if ( ! empty( $cloud_api_expired_alert ) ) {
			$alerts[] = $cloud_api_expired_alert;
		}

		$cloud_api_hits_exhausted_alert = get_transient( 'bbcs_cloud_api_hits_exhausted_alert' );
		if ( ! empty( $cloud_api_hits_exhausted_alert ) ) {
			$alerts[] = $cloud_api_hits_exhausted_alert;
		}

		$addon_update_failed_alert = get_transient( 'bbcs_addon_update_failed_alert' );
		if ( ! empty( $addon_update_failed_alert ) ) {
			$alerts[] = $addon_update_failed_alert;
		}

		$addon_incompatible_alert = get_transient( 'bbcs_addon_incompatible_alert' );
		if ( ! empty( $addon_incompatible_alert ) ) {
			$alerts[] = $addon_incompatible_alert;
		}

		$asn_db_failed_alert = get_transient( 'bbcs_asn_db_failed_alert' );
		if ( ! empty( $asn_db_failed_alert ) ) {
			$alerts[] = $asn_db_failed_alert;
		}

		// Cache plugin compatibility warnings
		$cache_alert = self::detectCacheIncompatibility();
		if ( ! empty( $cache_alert ) ) {
			$alerts[] = $cache_alert;
		}

		$alerts = apply_filters( 'bbcs_alerts_collect', $alerts );

		return is_array( $alerts ) ? $alerts : array();
	}

	public static function setCustom( string $key, array $alert, int $ttl = DAY_IN_SECONDS ): bool {
		if ( $key === '' || empty( $alert['title'] ) || empty( $alert['message'] ) ) {
			return false;
		}

		$alert = wp_parse_args(
			$alert,
			array(
				'type' => 'custom',
				'icon' => 'fas fa-info-circle bg-info text-light',
			)
		);

		return set_transient( $key, $alert, max( 0, $ttl ) );
	}

	public static function setCloudConnectionFailed(): void {
		$alert = array(
			'type'    => 'no_connection_bbcloud',
			'icon'    => 'fas fa-signal bg-success text-light',
			'title'   => __( 'No connection to BotBlocker Cloud', 'botblocker-security' ),
			'message' => gmdate( 'd/m/Y' ),
		);

		set_transient( 'bbcs_cloud_connection_failed_alert', $alert, DAY_IN_SECONDS );
	}


	public static function setMissingFiles(): void {
		$alert = array(
			'type'    => 'missing_files',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'Missing Files', 'botblocker-security' ),
			'message' => __( 'Required files missing. Regenerated.', 'botblocker-security' ),
		);

		set_transient( 'bbcs_missing_files_alert', $alert, HOUR_IN_SECONDS );
	}

	public static function setCloudApiExpired( ?int $days_left = null ): void {
		/* translators: %d: number of days left before the cloud API expires. */
		$about_to_expire_message = __( 'Your cloud API will expire in %d days.', 'botblocker-security' );
		$message                 = $days_left !== null ? sprintf( $about_to_expire_message, intval( $days_left ) ) : __( 'Your cloud API has expired. Please renew it.', 'botblocker-security' );
		$alert                   = array(
			'type'    => 'cloud_api_expired',
			'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
			'title'   => __( 'Cloud API Expired', 'botblocker-security' ),
			'message' => $message,
		);

		set_transient( 'bbcs_cloud_api_expired_alert', $alert, DAY_IN_SECONDS );
	}

	public static function setCloudApiHitsExhausted( ?int $hits_left = null ): void {
		/* translators: %d: number of hits remaining before the cloud API is exhausted. */
		$low_hits_message = __( 'Your cloud API has fewer than %d hits remaining.', 'botblocker-security' );
		$message          = $hits_left !== null ? sprintf( $low_hits_message, intval( $hits_left ) ) : __( 'Your cloud API has no hits remaining. Please renew.', 'botblocker-security' );
		$alert            = array(
			'type'    => 'cloud_api_hits_exhausted',
			'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
			'title'   => __( 'Cloud API Hits Exhausted', 'botblocker-security' ),
			'message' => $message,
		);

		set_transient( 'bbcs_cloud_api_hits_exhausted_alert', $alert, DAY_IN_SECONDS );
	}

	public static function setAsnDbFailed( string $error_code = '' ): void {
		$alert = array(
			'type'    => 'asn_db_failed',
			'icon'    => 'fas fa-database bg-warning text-light',
			'title'   => __( 'ASN Database Update Failed', 'botblocker-security' ),
			'message' => $error_code !== ''
				? sprintf(
					/* translators: %s: short error code returned by the downloader. */
					__( 'Could not download the ASN database (%s). Will retry automatically.', 'botblocker-security' ),
					$error_code
				)
				: __( 'Could not download the ASN database. Will retry automatically.', 'botblocker-security' ),
		);

		set_transient( 'bbcs_asn_db_failed_alert', $alert, DAY_IN_SECONDS );
	}

	/**
	 * Detect active cache plugins that may require manual server-level configuration.
	 * Returns an alert array if an incompatible mode is detected, or null if OK.
	 */
	public static function detectCacheIncompatibility(): ?array {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$warnings = array();

		// WP Super Cache - Expert (mod_rewrite) mode bypasses PHP entirely
		if ( defined( 'WPCACHEHOME' ) || is_plugin_active( 'wp-super-cache/wp-cache.php' ) || ( is_multisite() && is_plugin_active_for_network( 'wp-super-cache/wp-cache.php' ) ) ) {
			$wpsc_config    = defined( 'WPCACHEHOME' ) ? rtrim( WPCACHEHOME, '/' ) . '/wp-cache-config.php' : '';
			$is_mod_rewrite = false;
			if ( $wpsc_config && file_exists( $wpsc_config ) ) {
				$config_content = file_get_contents( $wpsc_config );
				if ( $config_content !== false && preg_match( '/\$wp_cache_mod_rewrite\s*=\s*1/', $config_content ) ) {
					$is_mod_rewrite = true;
				}
			}
			if ( $is_mod_rewrite ) {
				$warnings[] = __( 'WP Super Cache (Expert/mod_rewrite mode) serves cached pages via .htaccess, bypassing PHP. Add a cookie-based exception. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
			}
		}

		// W3 Total Cache - Disk Enhanced with rewrite rules
		if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) || ( is_multisite() && is_plugin_active_for_network( 'w3-total-cache/w3-total-cache.php' ) ) ) {
			if ( defined( 'W3TC_DIR' ) ) {
				$warnings[] = __( 'W3 Total Cache detected. If using Disk Enhanced mode with rewrite rules, add a cookie-based server exception. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
			}
		}

		// Nginx FastCGI / Redis page cache (server-level)
		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'nginx' ) !== false ) {
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				$warnings[] = __( 'Nginx detected with WP_CACHE enabled. If using FastCGI Cache, ensure the BotBlocker cookie bypasses it. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
			}
		}

		// LiteSpeed server with LSCache
		if ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) || ( is_multisite() && is_plugin_active_for_network( 'litespeed-cache/litespeed-cache.php' ) ) ) {
			// LSCWP respects X-LiteSpeed-Cache-Control: no-cache - auto-compatible
			// but still worth noting for users
		}

		// WP Rocket
		if ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) || ( is_multisite() && is_plugin_active_for_network( 'wp-rocket/wp-rocket.php' ) ) ) {
			$warnings[] = __( 'WP Rocket detected. Add the BotBlocker cookie to the "Never Cache Cookies" list in WP Rocket settings. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}

		// Kinsta server-level cache
		if ( defined( 'KINSTAMU_VERSION' ) ) {
			$warnings[] = __( 'Kinsta hosting detected. Add the BotBlocker cookie as a cache bypass rule in the Kinsta dashboard. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}

		if ( empty( $warnings ) ) {
			return null;
		}

		return array(
			'type'    => 'cache_compatibility',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'Cache Plugin Compatibility', 'botblocker-security' ),
			'message' => implode( ' | ', $warnings ),
		);
	}

	/**
	 * @param array $failed_addons Array of ['slug'=>..., 'name'=>..., 'error'=>...].
	 */
	public static function setAddonUpdateFailed( array $failed_addons ): void {
		$names   = array_map(
			function ( $f ) {
				return $f['name'];
			},
			$failed_addons
		);
		$message = sprintf(
			/* translators: %s: comma-separated list of add-on names that failed to update. */
			__( 'Failed to auto-update add-on(s): %s. Please retry from the Add-ons page.', 'botblocker-security' ),
			implode( ', ', $names )
		);
		$alert = array(
			'type'      => 'addon_update_failed',
			'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'     => __( 'Add-on Update Failed', 'botblocker-security' ),
			'message'   => $message,
			'link'      => method_exists( 'BotBlockerMultisite', 'getAdminPageUrl' ) ? BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) : '',
			'link_text' => __( 'Go to Add-ons', 'botblocker-security' ),
		);

		set_transient( 'bbcs_addon_update_failed_alert', $alert, DAY_IN_SECONDS );
	}

	/**
	 * @param array $deactivated Array of ['name'=>..., 'requires_core'=>...].
	 */
	public static function setAddonIncompatible( array $deactivated ): void {
		$lines = array();
		foreach ( $deactivated as $item ) {
			if ( ! empty( $item['requires_core'] ) ) {
				/* translators: 1: add-on name, 2: required BotBlocker version. */
				$lines[] = sprintf( __( '%1$s (requires >= %2$s)', 'botblocker-security' ), $item['name'], $item['requires_core'] );
			} else {
				$lines[] = $item['name'];
			}
		}
		$alert = array(
			'type'      => 'addon_incompatible',
			'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'     => __( 'Add-ons Deactivated', 'botblocker-security' ),
			'message'   => sprintf(
				/* translators: %s: comma-separated list of deactivated add-on names with version requirements. */
				__( 'Incompatible add-ons were deactivated: %s. Please update BotBlocker.', 'botblocker-security' ),
				implode( ', ', $lines )
			),
			'link'      => method_exists( 'BotBlockerMultisite', 'getAdminPageUrl' ) ? BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) : '',
			'link_text' => __( 'View Add-ons', 'botblocker-security' ),
		);

		delete_transient( 'bbcs_addon_incompatible_alert' );
		delete_option( '_transient_bbcs_addon_incompatible_alert' );
		delete_option( '_transient_timeout_bbcs_addon_incompatible_alert' );
		wp_cache_delete( '_transient_bbcs_addon_incompatible_alert', 'options' );
		wp_cache_delete( '_transient_timeout_bbcs_addon_incompatible_alert', 'options' );
		set_transient( 'bbcs_addon_incompatible_alert', $alert, DAY_IN_SECONDS );
	}
}
