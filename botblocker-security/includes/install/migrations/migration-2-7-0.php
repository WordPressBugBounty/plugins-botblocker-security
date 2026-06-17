<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_7_0() {
	global $wpdb;
	BotBlockerInstall::createLlmTrustedTable();
	if ( ! empty( $wpdb->last_error ) ) {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Migration] migration 2.7.0: create tables error - ' . $wpdb->last_error );
		}
	}
	BotBlockerLlmSync::scheduleSync( 'migration', 30 );
	bbcs_migration_2_7_0_append_yandex_cidrs();
	bbcs_migration_2_7_0_add_bing_preview();
	bbcs_migration_2_7_0_rename_options_to_bbcs_prefix();
	bbcs_migration_2_7_0_cleanup_use_transients_for_cloud();
	bbcs_migration_2_7_0_add_new_settings();

	BotBlockerDb::generateAllFiles();
}

function bbcs_migration_2_7_0_add_new_settings() {
	global $wpdb;
	if ( ! isset( $wpdb->bbcs_settings ) ) {
		return;
	}
	$new_settings = array(
		'ptrcache_subnet'        => '24-64',
		'ptrcache_rule_ttl'      => '90',
		'bbcs_ddos_resilience'   => '0',
		'options_preflight'      => '1',
		'session_token_enabled'  => '1',
		'payment_strict_method'  => '0',
		'payment_keep_ip_rules'  => '0',
		'skip_logged_in_users'   => '0',
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

function bbcs_migration_2_7_0_append_yandex_cidrs() {
	global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

	$yandex_cidrs = array(
		'ip:45.138.0.0/24',
		'ip:45.148.65.0/24',
		'ip:141.8.183.0/24',
	);

	$current_data = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `data` FROM `{$wpdb->bbcs_se}` WHERE `search` = %s",
			'yandex.com'
		)
	);

	if ( $current_data === null ) {
		return;
	}

	$tokens = preg_split( '/\s+/', trim( (string) $current_data ), -1, PREG_SPLIT_NO_EMPTY );

	$changed = false;
	foreach ( $yandex_cidrs as $cidr_token ) {
		if ( ! in_array( $cidr_token, $tokens, true ) ) {
			$tokens[] = $cidr_token;
			$changed  = true;
		}
	}

	if ( $changed ) {
		$wpdb->update(
			$wpdb->bbcs_se,
			array( 'data' => implode( ' ', $tokens ) ),
			array( 'search' => 'yandex.com' ),
			array( '%s' ),
			array( '%s' )
		);
	}

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
}

function bbcs_migration_2_7_0_add_bing_preview() {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM `{$wpdb->bbcs_se}` WHERE `search` = %s",
			'BingPreview'
		)
	);

	if ( (int) $exists === 0 ) {
		$wpdb->insert(
			$wpdb->bbcs_se,
			array(
				'priority' => 2,
				'search'   => 'BingPreview',
				'data'     => 'search.msn.com',
				'rule'     => 'allow',
				'comment'  => 'Bing Preview bot',
				'disable'  => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

function bbcs_migration_2_7_0_rename_options_to_bbcs_prefix() {
	$rename_map = array(
		'botblocker_active_addons'               => 'bbcs_active_addons',
		'botblocker_tools_core_settings'         => 'bbcs_tools_core_settings',
		'botblocker_tools_login_settings'        => 'bbcs_tools_login_settings',
		'botblocker_tools_headers_settings'      => 'bbcs_tools_headers_settings',
		'botblocker_tools_https_protocol_settings' => 'bbcs_tools_https_protocol_settings',
		'botblocker_tools_malware_settings'      => 'bbcs_tools_malware_settings',
		'botblocker_malware_last_scan'           => 'bbcs_malware_last_scan',
		'botblocker_malware_scan_state'          => 'bbcs_malware_scan_state',
		'botblocker_malware_scan_queue'          => 'bbcs_malware_scan_queue',
		'botblocker_malware_signature_db'        => 'bbcs_malware_signature_db',
		'botblocker_malware_signature_db_status' => 'bbcs_malware_signature_db_status',
	);

	foreach ( $rename_map as $old_key => $new_key ) {
		$existing = get_option( $old_key, null );
		if ( $existing !== null && get_option( $new_key, null ) === null ) {
			update_option( $new_key, $existing );
		}
	}

	if ( is_multisite() ) {
		$network_rename = array(
			'botblocker_network_license_key'   => 'bbcs_network_license_key',
			'botblocker_network_cloud_api_key' => 'bbcs_network_cloud_api_key',
		);
		foreach ( $network_rename as $old_key => $new_key ) {
			$existing = get_site_option( $old_key, null );
			if ( $existing !== null ) {
				update_site_option( $new_key, $existing );
			}
		}
	}
}

function bbcs_migration_2_7_0_cleanup_use_transients_for_cloud() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete(
		$wpdb->bbcs_settings,
		array( 'key' => 'use_transients_for_cloud' ),
		array( '%s' )
	);
}
