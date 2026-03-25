<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//BBCS-MULTISITE

function bbcs_is_network_active(): bool {
	if ( ! is_multisite() ) {
		return false;
	}
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return is_plugin_active_for_network( BOTBLOCKER_BASENAME );
}

function bbcs_network_option_keys(): array {
	return array(
		'bbcs_network_license_key',
		'bbcs_network_cloud_api_key',
	);
}

function bbcs_get_option( string $key, $default = false ) {
	if ( is_multisite() && in_array( $key, bbcs_network_option_keys(), true ) ) {
		return get_site_option( $key, $default );
	}
	return get_option( $key, $default );
}

function bbcs_update_option( string $key, $value, $autoload = null ): bool {
	if ( is_multisite() && in_array( $key, bbcs_network_option_keys(), true ) ) {
		return update_site_option( $key, $value );
	}
	if ( null !== $autoload ) {
		return update_option( $key, $value, $autoload );
	}
	return update_option( $key, $value );
}

function bbcs_delete_option( string $key ): bool {
	if ( is_multisite() && in_array( $key, bbcs_network_option_keys(), true ) ) {
		return delete_site_option( $key );
	}
	return delete_option( $key );
}

function bbcs_current_site_url(): string {
	return get_site_url();
}

function bbcs_current_site_clear(): string {
	return bbcs_full_domain_with_underscores( get_site_url() );
}

function bbcs_current_site_name(): string {
	return get_bloginfo( 'name' );
}

function bbcs_current_site_email(): string {
	return get_bloginfo( 'admin_email' );
}

function bbcs_current_user_agent(): string {
	return 'BotBlocker-Wordpress-Security-Plugin/ ' . BOTBLOCKER_VERSION . ' by https://globus.studio; Client:' . get_bloginfo( 'url' );
}

function bbcs_can_manage(): string {
	if ( is_multisite() && bbcs_is_network_active() ) {
		return 'manage_network_options';
	}
	return 'manage_options';
}

function bbcs_foreach_site( callable $callback ): void {
	if ( is_multisite() && function_exists( 'get_sites' ) ) {
		$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
			$callback( $site_id );
			restore_current_blog();
		}
		require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
	} else {
		$callback( get_current_blog_id() );
	}
}

function bbcs_uploads_dir(): string {
	$dir = bbcs_get_protected_upload_dir();
	if ( $dir === null || is_wp_error( $dir ) ) {
		$dir = bbcs_create_protected_upload_dir();
	}
	if ( is_wp_error( $dir ) || ! is_string( $dir ) ) {
		return '';
	}
	return $dir;
}

function bbcs_data_dir(): string {
	return bbcs_uploads_dir() . 'data/';
}

function bbcs_addons_dir(): string {
	return bbcs_uploads_dir() . 'addons/';
}

function bbcs_addons_url(): string {
	$url = bbcs_get_protected_upload_dir( true );
	if ( ! is_string( $url ) || $url === '' ) {
		return '';
	}
	return $url . 'addons/';
}

function bbcs_admin_page_url( string $path = '' ): string {
	$network = is_multisite() && bbcs_is_network_active();
	if ( $network && ! is_network_admin() ) {
		$ref = wp_get_referer();
		if ( $ref && strpos( $ref, '/network/' ) !== false ) {
			$network = true;
		} else {
			$network = false;
		}
	}
	$base = $network
		? network_admin_url( 'admin.php' )
		: admin_url( 'admin.php' );
	return $path !== '' ? $base . '?page=' . $path : $base;
}

// site-admin only
function bbcs_site_admin_page_url( string $path = '' ): string {
	$base = admin_url( 'admin.php' );
	return $path !== '' ? $base . '?page=' . $path : $base;
}

function bbcs_sync_cloud_settings_network( array $settings ): void
{
	bbcs_foreach_site( function ( $site_id ) use ( $settings ) {
		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( $settings as $key => $value ) {
			$wpdb->update(
				$wpdb->bbcs_settings,
				array( 'value' => $value ),
				array( 'key'   => $key )
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		bbcs_generateSettingsFileFromDb();

		delete_transient( 'bbcs_cloud_api_expired_alert' );
		delete_transient( 'bbcs_cloud_api_hits_exhausted_alert' );
		delete_transient( 'bbcs_cloud_api_status_transient' );
	} );
}
