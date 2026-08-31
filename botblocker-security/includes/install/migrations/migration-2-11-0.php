<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_11_0() {
	if ( ! bbcs_migration_2_11_0_upgrade_asn_column() ) {
		return false;
	}

	if ( ! bbcs_migration_2_11_0_ensure_audit_log_table() ) {
		return false;
	}

	if ( ! bbcs_migration_2_11_0_add_audit_settings() ) {
		return false;
	}

	if ( ! bbcs_migration_2_11_0_cleanup_public_self_ips() ) {
		return false;
	}

	return true;
}

function bbcs_migration_2_11_0_upgrade_asn_column(): bool {
	global $wpdb;

	if ( empty( $wpdb->bbcs_asn ) ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$column = $wpdb->get_row(
		$wpdb->prepare( "SHOW COLUMNS FROM `{$wpdb->bbcs_asn}` LIKE %s", 'asnum' ),
		ARRAY_A
	);
	if ( ! is_array( $column ) ) {
		return false;
	}

	$type = strtolower( isset( $column['Type'] ) ? (string) $column['Type'] : '' );
	if ( preg_match( '/^bigint(?:\(\d+\))? unsigned$/', $type ) === 1 ) {
		return true;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$result = $wpdb->query( "ALTER TABLE `{$wpdb->bbcs_asn}` MODIFY `asnum` BIGINT UNSIGNED NOT NULL" );
	if ( false === $result ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$column = $wpdb->get_row(
		$wpdb->prepare( "SHOW COLUMNS FROM `{$wpdb->bbcs_asn}` LIKE %s", 'asnum' ),
		ARRAY_A
	);
	$type = is_array( $column ) && isset( $column['Type'] ) ? strtolower( (string) $column['Type'] ) : '';

	return preg_match( '/^bigint(?:\(\d+\))? unsigned$/', $type ) === 1;
}

function bbcs_migration_2_11_0_ensure_audit_log_table(): bool {
	if ( class_exists( 'BotBlockerInstall' ) && method_exists( 'BotBlockerInstall', 'createAuditLogTable' ) ) {
		BotBlockerInstall::createAuditLogTable();
	}

	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$table_exists = $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->bbcs_audit_log ) )
	);

	if ( ! $table_exists ) {
		return false;
	}

	return true;
}

function bbcs_migration_2_11_0_add_audit_settings(): bool {
	global $wpdb;

	$defaults = array(
		'audit_log_enable'         => '1',
		'audit_log_retention_days' => '7',
	);

	foreach ( $defaults as $key => $value ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s LIMIT 1",
				$key
			)
		);
		if ( $exists !== null ) {
			continue;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->bbcs_settings,
			array(
				'key'   => $key,
				'value' => $value,
			),
			array( '%s', '%s' )
		);
	}

	$roles = array();
	if ( function_exists( 'wp_roles' ) ) {
		foreach ( wp_roles()->roles as $role_key => $role ) {
			$roles[ $role_key ] = '1';
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$roles_exist = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s LIMIT 1",
			'audit_log_roles'
		)
	);
	if ( $roles_exist === null && $roles ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'audit_log_roles',
				'value' => wp_json_encode( $roles ),
			),
			array( '%s', '%s' )
		);
	}

	if ( class_exists( 'BotBlockerFileRenderer' ) && method_exists( 'BotBlockerFileRenderer', 'generateSettingsFile' ) ) {
		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::clearFileCache();
	}

	return true;
}

function bbcs_migration_2_11_0_cleanup_public_self_ips(): bool {
	global $wpdb;

	$self_comments = array( 'Local IP', 'Local IP from SERVER_ADDR', 'Server IPv4', 'Server IPv6' );
	$comment_in    = implode( ',', array_fill( 0, count( $self_comments ), '%s' ) );

	foreach ( array( $wpdb->bbcs_ipv4rules, $wpdb->bbcs_ipv6rules ) as $table ) {
		$query_args = array_merge( array( 1 ), $self_comments );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table name from $wpdb, comments bound via prepare
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `id`, `search` FROM `{$table}` WHERE `readonly` = %d AND `comment` IN ( {$comment_in} )",
				...$query_args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( ! is_array( $rows ) ) {
			continue;
		}
		foreach ( $rows as $row ) {
			if ( ! isset( $row['search'] ) || ! BotBlockerIp::isPublicIp( (string) $row['search'] ) ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'id' => (int) $row['id'] ) );
		}
	}

	if ( class_exists( 'BotBlockerFileRenderer' ) ) {
		BotBlockerFileRenderer::renderIps();
		BotBlockerCache::clearFileCache();
	}

	return true;
}
