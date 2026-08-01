<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_9_0() {
	global $wpdb;
	BotBlockerInstall::createFingerprintTable();
	if ( ! empty( $wpdb->last_error ) ) {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Migration] migration 2.9.0: create fingerprint table error - ' . $wpdb->last_error );
		}
	}
	bbcs_migration_2_9_0_add_new_settings();
	BotBlockerDb::generateAllFiles();
}

function bbcs_migration_2_9_0_add_new_settings() {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_settings ) ) {
		return;
	}
	$new_settings = array(
		'fingerprint_sticky_block' => '0',
	);
	foreach ( $new_settings as $key => $value ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
			$key
		) );
		if ( ! $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $wpdb->bbcs_settings, array( 'key' => $key, 'value' => $value ) );
		}
	}
}
