<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_8_0() {
	bbcs_migration_2_8_0_add_new_settings();
	BotBlockerDb::generateAllFiles();
}

function bbcs_migration_2_8_0_add_new_settings() {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_settings ) ) {
		return;
	}
	$new_settings = array(
		'bbcs_rate_check_enabled'     => '1',
		'bbcs_rate_captcha_rpm'       => '30',
		'bbcs_rate_block_rpm'         => '50',
		'bbcs_rate_window_minutes'    => '5',
		'bbcs_rate_subnet_enabled'    => '0',
		'bbcs_rate_subnet_multiplier' => '3.0',
		'bbcs_rate_floor_percent'     => '0.1',
		'bbcs_rate_subnet_mask'       => '24-64',
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
