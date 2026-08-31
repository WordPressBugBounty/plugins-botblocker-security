<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_10_0() {
	if ( ! bbcs_migration_2_10_0_drop_tools_infix() ) {
		return false;
	}

	if ( ! bbcs_migration_2_10_0_ensure_filtered_column() ) {
		return false;
	}

	if ( ! bbcs_migration_2_10_0_ensure_perf_indexes() ) {
		return false;
	}

	if ( ! bbcs_migration_2_10_0_ensure_sessions_table() ) {
		return false;
	}

	if ( ! bbcs_migration_2_10_0_ensure_countries_table() ) {
		return false;
	}

	return true;
}

function bbcs_migration_2_10_0_drop_tools_infix(): bool {
	$rename_map = array(
		'bbcs_tools_behavior_settings'             => 'bbcs_behavior_settings',
		'bbcs_tools_core_settings'                 => 'bbcs_core_settings',
		'bbcs_tools_cron_settings'                 => 'bbcs_cron_settings',
		'bbcs_tools_early_init_settings'           => 'bbcs_early_init_settings',
		'bbcs_tools_early_init_enable'             => 'bbcs_early_init_enable',
		'bbcs_tools_headers_settings'              => 'bbcs_headers_settings',
		'bbcs_tools_https_protocol_settings'       => 'bbcs_https_protocol_settings',
		'bbcs_tools_login_settings'                => 'bbcs_login_settings',
		'bbcs_tools_malware_settings'              => 'bbcs_malware_settings',
		'bbcs_tools_speedup_settings'              => 'bbcs_speedup_settings',
		'bbcs_tools_truth_source_settings'         => 'bbcs_truth_source_settings',
		'bbcs_tools_xmlrpc_tunnel_settings'        => 'bbcs_xmlrpc_tunnel_settings',

		'botblocker_tools_cookie_alert_settings'   => 'bbcs_cookie_alert_settings',
		'botblocker_tools_core_settings'           => 'bbcs_core_settings',
		'botblocker_tools_headers_settings'        => 'bbcs_headers_settings',
		'botblocker_tools_https_protocol_settings' => 'bbcs_https_protocol_settings',
		'botblocker_tools_login_settings'          => 'bbcs_login_settings',
		'botblocker_tools_malware_settings'        => 'bbcs_malware_settings',

		'botblocker_malware_last_scan'             => 'bbcs_malware_last_scan',
		'botblocker_malware_scan_state'            => 'bbcs_malware_scan_state',
		'botblocker_malware_scan_queue'            => 'bbcs_malware_scan_queue',
		'botblocker_malware_signature_db'          => 'bbcs_malware_signature_db',
		'botblocker_malware_signature_db_status'   => 'bbcs_malware_signature_db_status',
	);

	foreach ( $rename_map as $old_key => $new_key ) {
		$existing = get_option( $old_key, null );
		if ( $existing === null ) {
			continue;
		}

		if ( get_option( $new_key, null ) === null ) {
			update_option( $new_key, $existing );
		}

		delete_option( $old_key );
	}

	// Orphaned since 2.10: early-init reads bbcs_settings table keys, these options have no reader.
	delete_option( 'bbcs_early_init_settings' );
	delete_option( 'bbcs_early_init_enable' );

	return true;
}

function bbcs_migration_2_10_0_ensure_filtered_column(): bool {
	global $wpdb;

	$tables = array( $wpdb->bbcs_hits, $wpdb->bbcs_hits_suspicious, $wpdb->bbcs_hits_cloud );
	$ok     = true;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	foreach ( $tables as $table ) {
		if ( empty( $table ) ) {
			continue;
		}
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
		);
		if ( ! $table_exists || $wpdb->get_var( "SHOW COLUMNS FROM `{$table}` LIKE 'filtered'" ) ) {
			continue;
		}

		$wpdb->query(
			"ALTER TABLE `{$table}` ADD COLUMN `filtered` TINYINT UNSIGNED NOT NULL DEFAULT 0"
		);
		if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$table}` LIKE 'filtered'" ) ) {
			$ok = false;
		}
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	update_option( BotBlockerStore::FILTERED_COLUMN_OPTION, $ok ? '1' : '0', true );
	if ( $ok && get_option( BotBlockerStore::FILTERED_WATERMARK_OPTION, null ) === null ) {
		update_option(
			BotBlockerStore::FILTERED_WATERMARK_OPTION,
			time() + BotBlockerStore::FILTERED_WATERMARK_BUFFER,
			true
		);
	}

	return $ok;
}

function bbcs_migration_2_10_0_ensure_perf_indexes(): bool {
	global $wpdb;
	$ok = true;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( ! empty( $wpdb->bbcs_ptrcache ) && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->bbcs_ptrcache ) ) ) ) {
		if ( ! $wpdb->get_var( "SHOW INDEX FROM `{$wpdb->bbcs_ptrcache}` WHERE Key_name = 'i_date'" ) ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->bbcs_ptrcache}` ADD INDEX i_date (date)" );
			if ( ! $wpdb->get_var( "SHOW INDEX FROM `{$wpdb->bbcs_ptrcache}` WHERE Key_name = 'i_date'" ) ) {
				$ok = false;
			}
		}
	}

	if ( ! empty( $wpdb->bbcs_fingerprint ) && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->bbcs_fingerprint ) ) ) ) {
		if ( ! $wpdb->get_var( "SHOW INDEX FROM `{$wpdb->bbcs_fingerprint}` WHERE Key_name = 'i_status_last_seen'" ) ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->bbcs_fingerprint}` ADD INDEX i_status_last_seen (status, last_seen)" );
			if ( ! $wpdb->get_var( "SHOW INDEX FROM `{$wpdb->bbcs_fingerprint}` WHERE Key_name = 'i_status_last_seen'" ) ) {
				$ok = false;
			}
		}

		$dead_indexes = array( 'i_ip', 'i_block_count', 'i_status' );
		foreach ( $dead_indexes as $key_name ) {
			if ( $wpdb->get_var( "SHOW INDEX FROM `{$wpdb->bbcs_fingerprint}` WHERE Key_name = '{$key_name}'" ) ) {
				$wpdb->query( "ALTER TABLE `{$wpdb->bbcs_fingerprint}` DROP INDEX `{$key_name}`" );
				if ( $wpdb->get_var( "SHOW INDEX FROM `{$wpdb->bbcs_fingerprint}` WHERE Key_name = '{$key_name}'" ) ) {
					$ok = false;
				}
			}
		}
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return $ok;
}

function bbcs_migration_2_10_0_ensure_sessions_table(): bool {
	if ( class_exists( 'BotBlockerInstall' ) && method_exists( 'BotBlockerInstall', 'createSessionsTable' ) ) {
		BotBlockerInstall::createSessionsTable();
	}

	// Migrate existing sessions from transient to table (if any).
	delete_transient( 'bbcs_sessions' );

	return true;
}

function bbcs_migration_2_10_0_ensure_countries_table(): bool {
	global $wpdb;

	if ( class_exists( 'BotBlockerInstall' ) && method_exists( 'BotBlockerInstall', 'createCountriesTable' ) ) {
		BotBlockerInstall::createCountriesTable();
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$table_exists = $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->bbcs_countries ) )
	);
	if ( ! $table_exists ) {
		return false;
	}

	// Migrate the legacy blocked-countries option into the table (single row per code).
	$blocked = get_option( 'bbcs_blocked_countries', null );
	if ( $blocked === null ) {
		return true;
	}
	if ( is_string( $blocked ) ) {
		$decoded = json_decode( $blocked, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			$blocked = $decoded;
		} else {
			$blocked = array_filter( array_map( 'trim', explode( ',', $blocked ) ) );
		}
	}
	if ( ! is_array( $blocked ) ) {
		$blocked = array();
	}
	foreach ( $blocked as $item ) {
		$code = is_string( $item ) ? strtoupper( trim( $item ) ) : '';
		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			continue;
		}
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM `{$wpdb->bbcs_countries}` WHERE code = %s", $code )
		);
		if ( $exists ) {
			continue;
		}
		$wpdb->insert(
			$wpdb->bbcs_countries,
			array(
				'priority' => 50,
				'code'     => $code,
				'rule'     => 'block',
				'comment'  => '',
				'disable'  => 0,
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	delete_option( 'bbcs_blocked_countries' );

	if ( class_exists( 'BotBlockerFileRenderer' ) && method_exists( 'BotBlockerFileRenderer', 'renderCountries' ) ) {
		BotBlockerFileRenderer::renderCountries();
		BotBlockerCache::clearFileCache();
	}

	return true;
}
