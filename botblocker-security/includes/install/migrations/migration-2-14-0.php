<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_14_0() {
	bbcs_migration_2_14_0_edge_roles();
	bbcs_migration_2_14_0_ban_safety();
	bbcs_migration_2_14_0_add_settings();
	bbcs_migration_2_14_0_create_edge_stats_table();
	bbcs_migration_2_14_0_narrow_ptr_rules();
	BotBlockerDb::generateAllFiles();
	return true;
}

/**
 * Ban-safety repair: shared flag, shared data file and the auto-trust file.
 * Idempotent; also used when the stored DB version is already 2.14.0.
 */
function bbcs_migration_2_14_0_ban_safety(): void {
	bbcs_migration_2_14_0_add_proxy_shared_column();
	bbcs_migration_2_14_0_classify_shared_proxies();
	if ( class_exists( 'BotBlockerFileRenderer' ) ) {
		BotBlockerFileRenderer::renderProxy();
	}
	if ( class_exists( 'BotBlockerEdgeStats' ) ) {
		BotBlockerEdgeStats::repairAutoEdgesFile();
	}
	update_option( 'bbcs_ban_safety_rev', '1', true );
}

function bbcs_migration_2_14_0_add_proxy_shared_column(): void {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_proxy ) ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$column = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->bbcs_proxy}` LIKE 'shared'" );
	if ( ! empty( $column ) ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "ALTER TABLE `{$wpdb->bbcs_proxy}` ADD COLUMN `shared` TINYINT(1) NOT NULL DEFAULT 0" );
}

function bbcs_migration_2_14_0_classify_shared_proxies(): void {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_proxy ) ) {
		return;
	}

	$shared_ranges = array( '54.239.0.0/16', '54.240.0.0/12', '204.246.164.0/22', '205.251.192.0/19' );
	foreach ( $shared_ranges as $mask ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$wpdb->bbcs_proxy}` SET `shared` = 1 WHERE `key` = %s AND `role` = %s",
				$mask,
				'edge'
			)
		);
	}
}

/**
 * Introduce edge roles: site edges may assert a client, crawler networks may not.
 * Idempotent; also used as a repair for installs that already stored 2.14.0.
 */
function bbcs_migration_2_14_0_edge_roles(): void {
	if ( ! defined( 'ABSPATH' ) ) {
		return;
	}
	if ( ! class_exists( 'BotBlockerMultisite' ) ) {
		return;
	}
	bbcs_migration_2_14_0_add_proxy_role_column();
	bbcs_migration_2_14_0_classify_proxy_networks();
	if ( class_exists( 'BotBlockerFileRenderer' ) ) {
		BotBlockerFileRenderer::renderProxy();
	}
	update_option( 'bbcs_edge_roles_rev', '1', true );
}

function bbcs_migration_2_14_0_add_proxy_role_column(): void {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_proxy ) ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$column = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->bbcs_proxy}` LIKE 'role'" );
	if ( ! empty( $column ) ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "ALTER TABLE `{$wpdb->bbcs_proxy}` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'edge'" );
}

function bbcs_migration_2_14_0_classify_proxy_networks(): void {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_proxy ) ) {
		return;
	}

	$crawler_masks = array(
		'64.233.160.0/19',
		'66.102.0.0/20',
		'66.249.80.0/20',
		'72.14.192.0/18',
		'74.125.0.0/16',
	);

	foreach ( $crawler_masks as $mask ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$wpdb->bbcs_proxy}` SET `role` = 'bot_network', `comment` = 'Google crawler network' WHERE `key` = %s AND `role` = 'edge'",
				$mask
			)
		);
	}

	// A Google Cloud tenant prefix is neither a site edge nor a crawler identity.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete( $wpdb->bbcs_proxy, array( 'key' => '35.190.0.0/17' ), array( '%s' ) );
}

function bbcs_migration_2_14_0_add_settings() {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_settings ) ) {
		return;
	}

	$block_rpm   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", 'bbcs_rate_block_rpm' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$captcha_rpm = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", 'bbcs_rate_captcha_rpm' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$new_settings = array(
		'bbcs_hide_user_enumeration'   => '1',
		'bbcs_strip_php_version'       => '1',
		'bbcs_rate_passed_rpm'         => (string) max( 300, $block_rpm ),
		'bbcs_rate_passed_captcha_rpm' => (string) max( 150, $captcha_rpm ),
		'bbcs_edge_autotrust'          => '1',
	);

	foreach ( $new_settings as $key => $value ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", $key ) );
		if ( ! $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $wpdb->bbcs_settings, array( 'key' => $key, 'value' => $value ) );
		}
	}

	// The old stock default trusted a whole subnet after one verified hit.
	// Only the stock value is migrated; a deliberate admin choice is kept.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ptr_subnet = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", 'ptrcache_subnet' ) );
	if ( $ptr_subnet === '24-64' ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $wpdb->bbcs_settings, array( 'value' => '32-128' ), array( 'key' => 'ptrcache_subnet' ) );
	}
}

function bbcs_migration_2_14_0_create_edge_stats_table(): void {
	if ( class_exists( 'BotBlockerInstall' ) ) {
		BotBlockerInstall::createEdgeStatsTable();
	}
}

function bbcs_migration_2_14_0_narrow_ptr_rules(): void {
	if ( class_exists( 'BotBlockerEdgeStats' ) ) {
		BotBlockerEdgeStats::hygiene();
	}
}
