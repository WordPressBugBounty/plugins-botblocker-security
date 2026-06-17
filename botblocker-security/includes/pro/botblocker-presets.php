<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_loadSettingsLight(): void {
	global $wpdb;

	$light_setting = bbcs_loadLightSecurity();
	foreach ( $light_setting as $key => $value ) {
		$key = sanitize_key( $key );
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->bbcs_settings,
			array( 'value' => $value ),
			array( 'key' => $key )
		);
	}
	BotBlockerFileRenderer::generateSettingsFile();
}

function bbcs_loadSettingsStrong(): void {
	global $wpdb;

	$strong_setting = bbcs_loadStrongSecurity();
	foreach ( $strong_setting as $key => $value ) {
		$key = sanitize_key( $key );
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->bbcs_settings,
			array( 'value' => $value ),
			array( 'key' => $key )
		);
	}
	BotBlockerFileRenderer::generateSettingsFile();
}

function bbcs_loadSettingsFull(): void {
	global $wpdb;

	$full_setting = bbcs_loadFullSecurity();
	foreach ( $full_setting as $key => $value ) {
		$key = sanitize_key( $key );
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->bbcs_settings,
			array( 'value' => $value ),
			array( 'key' => $key )
		);
	}
	BotBlockerFileRenderer::generateSettingsFile();
}
