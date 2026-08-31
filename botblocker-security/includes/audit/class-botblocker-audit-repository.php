<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditRepository {

	public const PAGE_LENGTH_MAX = 200;

	/**
	 * Minimum severity asked for, or 0 for no severity filter.
	 *
	 * @param mixed $value
	 */
	private static function severityFloor( $value ): int {
		if ( $value === '' || $value === null || (int) $value === 0 ) {
			return 0;
		}
		return BotBlockerAuditLogger::normalizeSeverity( $value );
	}

	/** Whitelist, never the raw request: this value is interpolated into the query. */
	private static function sanitizeOrderBy( string $column ): string {
		$allowed = array( 'created_at', 'severity', 'event_key', 'actor_username', 'ip', 'object_type' );

		return in_array( $column, $allowed, true ) ? $column : 'created_at';
	}

	/** Rows in the table, ignoring every filter. */
	public static function countAll(): int {
		global $wpdb;

		if ( empty( $wpdb->bbcs_audit_log ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_audit_log}`" );
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function insert( array $row ): bool {
		global $wpdb;

		if ( empty( $wpdb->bbcs_audit_log ) ) {
			return false;
		}

		$data = array(
			'event_key'      => isset( $row['event_key'] ) ? (string) $row['event_key'] : '',
			'severity'       => BotBlockerAuditLogger::normalizeSeverity( isset( $row['severity'] ) ? $row['severity'] : BotBlockerAuditLogger::SEVERITY_INFO ),
			'actor_user_id'  => isset( $row['actor_user_id'] ) ? (int) $row['actor_user_id'] : BotBlockerAuditContext::ACTOR_UNIDENTIFIED,
			'actor_username' => isset( $row['actor_username'] ) ? (string) $row['actor_username'] : '',
			'actor_role'     => isset( $row['actor_role'] ) ? (string) $row['actor_role'] : '',
			'object_type'    => isset( $row['object_type'] ) ? (string) $row['object_type'] : '',
			'object_id'      => isset( $row['object_id'] ) ? (int) $row['object_id'] : 0,
			'ip'             => isset( $row['ip'] ) ? (string) $row['ip'] : '',
			'context'        => isset( $row['context'] ) ? (string) $row['context'] : '',
			'request_path'   => isset( $row['request_path'] ) ? (string) $row['request_path'] : '',
			'user_agent'     => isset( $row['user_agent'] ) ? (string) $row['user_agent'] : '',
			'data'           => isset( $row['data'] ) ? (string) $row['data'] : '',
			'created_at'     => isset( $row['created_at'] ) ? (int) $row['created_at'] : time(),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$wpdb->bbcs_audit_log,
			$data,
			// One specifier per column, in $data order. created_at is the trailing %d.
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		return $result !== false;
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function query( array $args ): array {
		global $wpdb;

		$defaults = array(
			'start'      => 0,
			'length'     => 25,
			'search'     => '',
			'event_key'  => '',
			'category'   => '',
			'actor_id'   => 0,
			'role'       => '',
			'object_type'=> '',
			'object_id'  => 0,
			'ip'         => '',
			'severity'   => 0,
			'context'    => '',
			'date_from'  => 0,
			'date_to'    => 0,
			'orderby'    => 'created_at',
			'order'      => 'DESC',
		);
		$args = array_merge( $defaults, $args );

		$where  = array( '1=1' );
		$params = array();

		if ( $args['event_key'] !== '' ) {
			$where[]  = 'event_key = %s';
			$params[] = (string) $args['event_key'];
		} elseif ( $args['category'] !== '' ) {
			$like     = (string) $args['category'] . '.%';
			$where[]  = 'event_key LIKE %s';
			$params[] = $like;
		}

		if ( (int) $args['actor_id'] > 0 ) {
			$where[]  = 'actor_user_id = %d';
			$params[] = (int) $args['actor_id'];
		}

		if ( $args['role'] !== '' ) {
			$where[]  = 'actor_role = %s';
			$params[] = (string) $args['role'];
		}

		if ( $args['object_type'] !== '' ) {
			$where[]  = 'object_type = %s';
			$params[] = (string) $args['object_type'];
		}

		if ( (int) $args['object_id'] > 0 ) {
			$where[]  = 'object_id = %d';
			$params[] = (int) $args['object_id'];
		}

		if ( $args['ip'] !== '' ) {
			$where[]  = 'ip = %s';
			$params[] = (string) $args['ip'];
		}

		// Reads as "this level and above", not one exact level.
		if ( self::severityFloor( $args['severity'] ) > 0 ) {
			$where[]  = 'severity >= %d';
			$params[] = self::severityFloor( $args['severity'] );
		}

		if ( $args['context'] !== '' ) {
			$where[]  = 'context = %s';
			$params[] = (string) $args['context'];
		}

		if ( (int) $args['date_from'] > 0 ) {
			$where[]  = 'created_at >= %d';
			$params[] = (int) $args['date_from'];
		}

		if ( (int) $args['date_to'] > 0 ) {
			$where[]  = 'created_at <= %d';
			$params[] = (int) $args['date_to'];
		}

		if ( $args['search'] !== '' ) {
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$where[]  = '(event_key LIKE %s OR actor_username LIKE %s OR actor_role LIKE %s OR object_type LIKE %s OR ip LIKE %s OR request_path LIKE %s OR data LIKE %s)';
			$params   = array_merge( $params, array_fill( 0, 7, $like ) );
		}

		$where_sql = implode( ' AND ', $where );
		$table     = $wpdb->bbcs_audit_log;

		$count_sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";
		if ( $params ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$total = (int) $wpdb->get_var( $count_sql );
		}

		$start  = max( 0, (int) $args['start'] );
		$length = max( 1, min( self::PAGE_LENGTH_MAX, (int) $args['length'] ) );

		$orderby = self::sanitizeOrderBy( (string) $args['orderby'] );
		$order   = strtoupper( (string) $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$data_sql    = "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY `{$orderby}` {$order}, id {$order} LIMIT %d OFFSET %d";
		$data_params = array_merge( $params, array( $length, $start ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ), ARRAY_A );

		return array(
			'rows'  => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}

	public static function purgeOlderThan( int $cutoff ): int {
		global $wpdb;

		if ( empty( $wpdb->bbcs_audit_log ) || $cutoff <= 0 ) {
			return 0;
		}

		$deleted = 0;
		$batch   = 500;

		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					// Ordered by created_at, not id, so the delete walks i_created_at.
					"SELECT id FROM `{$wpdb->bbcs_audit_log}` WHERE created_at < %d ORDER BY created_at ASC LIMIT %d",
					$cutoff,
					$batch
				)
			);

			if ( ! is_array( $ids ) || ! $ids ) {
				break;
			}

			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->bbcs_audit_log}` WHERE id IN ({$placeholders})", $ids ) );
			$deleted += count( $ids );
		} while ( count( $ids ) === $batch );

		return $deleted;
	}

	public static function sanitizeCsvCell( $value ): string {
		$text = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
		if ( $text === '' ) {
			return '';
		}
		$first = $text[0];
		if ( in_array( $first, array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$text = "'" . $text;
		}
		return str_replace( array( "\r\n", "\r", "\n" ), ' ', $text );
	}
}
