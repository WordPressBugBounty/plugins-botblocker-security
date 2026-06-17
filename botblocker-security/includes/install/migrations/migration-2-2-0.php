<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_2_0() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	BotBlockerInstall::createDailySummaryTable();

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$tier_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->bbcs_settings} WHERE `key` = %s",
			'cloud_api_tier'
		)
	);
	if ( $tier_count === 0 ) {
		$wpdb->insert(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'cloud_api_tier',
				'value' => '',
			),
			array( '%s', '%s' )
		);
	}

	if ( ! empty( $wpdb->bbcs_self_ips ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $wpdb->query( "DROP VIEW IF EXISTS `{$wpdb->bbcs_self_ips}`" );
		if ( $result === false && ! empty( $wpdb->last_error ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Migration] migration 2.2.0: DROP VIEW error - ' . $wpdb->last_error );
			}
		}
	}

	$has_data = (bool) $wpdb->get_var( "SELECT 1 FROM `{$wpdb->bbcs_hits}` LIMIT 1" );
	if ( $has_data && ! wp_next_scheduled( 'bbcs_summary_backfill' ) ) {
		wp_schedule_single_event( time() + 30, 'bbcs_summary_backfill' );
	}
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}
