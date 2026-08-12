<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerDb {

	/**
	 * Shifts a UTC unix-timestamp column by an offset (seconds), returns DATETIME.
	 * Avoids FROM_UNIXTIME()/CONVERT_TZ() — both depend on MySQL's session time_zone.
	 *
	 * @param string $column Column/expression holding the UTC unix timestamp.
	 * @param string $offset_placeholder Placeholder for offset in seconds (default %d).
	 */
	public static function localDatetimeExpr( string $column, string $offset_placeholder = '%d' ): string {
		return "DATE_ADD('1970-01-01 00:00:00', INTERVAL ({$column} + {$offset_placeholder}) SECOND)";
	}

	/**
	 * Aligns the MySQL session time zone with the plugin's UTC unix-timestamp
	 * storage. UNIX_TIMESTAMP('Y-m-d H:i:s') and CONVERT_TZ() both interpret
	 * their input in the SESSION time zone — on servers whose session tz is not
	 * UTC the day bounds shift by the offset and the last hours of each UTC day
	 * drop out of "today" stats (B-05). Called once per connection from the
	 * stats entry points; safe to call repeatedly.
	 */
	public static function ensureUtcSession(): void {
		static $done = false;
		if ( $done ) {
			return;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "SET time_zone = '+00:00'" );
		$done = true;
	}

	public static function storePTRrule(): bool {
		$BBCS = BotBlocker::getInstance();
		global $wpdb;

		if ( $BBCS->ip_version != 4 && $BBCS->ip_version != 6 ) {
			return false;
		}

		$subnet_setting = isset( $BBCS->settings->ptrcache_subnet ) ? $BBCS->settings->ptrcache_subnet : '24-64';
		$parts          = explode( '-', $subnet_setting );
		$mask_v4        = isset( $parts[0] ) ? (int) $parts[0] : 24;
		$mask_v6        = isset( $parts[1] ) ? (int) $parts[1] : 64;

		$search_cidr = BotBlockerIp::computePtrSubnet( $BBCS->ip, $BBCS->ip_version, $mask_v4, $mask_v6 );

		if ( $BBCS->ip_version == 4 ) {
			// REVIEWER NOTE: custom table operations require direct database queries.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_entry = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
					$search_cidr
				)
			);
		} else {
			// REVIEWER NOTE: custom table operations require direct database queries.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_entry = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s",
					$search_cidr
				)
			);
		}

		if ( $existing_entry > 0 ) {
			return true;
		}

		$ips = BotBlockerIp::toRange( $search_cidr );

		if ( isset( $ips[2] ) && $ips[2] === 0 ) {
			return false;
		}

		$ttl_days = isset( $BBCS->settings->ptrcache_rule_ttl ) ? (int) $BBCS->settings->ptrcache_rule_ttl : 90;
		if ( $ttl_days < 1 ) {
			$ttl_days = 90;
		}

		$data = array(
			'priority' => '10',
			'search'   => sanitize_text_field( $search_cidr ),
			'rule'     => BBCS_RULE_ALLOW,
			'comment'  => sanitize_text_field( $BBCS->useragent . ' (ip: ' . $BBCS->ip . ')' ),
			'expires'  => ( $BBCS->time + $ttl_days * DAY_IN_SECONDS ),
		);

		if ( $BBCS->ip_version == 4 ) {
			$data['ip1'] = BotBlockerIp::toNumeric( $ips[0] );
			$data['ip2'] = BotBlockerIp::toNumeric( $ips[1] );
		} else {
			$data['ip1'] = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ips[0] ) );
			$data['ip2'] = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ips[1] ) );
		}

		/**
		 * REVIEWER NOTE:
		 * - custom table operations require direct database queries.
		 * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
		 * - no user input is interpolated into this query.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $BBCS->ip_version == 4 ) {
			$result = $wpdb->insert( $wpdb->bbcs_ipv4rules, $data );
		} else {
			$result = $wpdb->insert( $wpdb->bbcs_ipv6rules, $data );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return ( $result !== false );
	}



	public static function togglePower( int $state ): void {
		global $wpdb;

		/**
		 * REVIEWER NOTE:
		 * - custom table operations require direct database queries.
		 * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
		 * - no user input is interpolated into this query.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", 'disable' ) );
		if ( $exists ) {
			$updated = $wpdb->update(
				$wpdb->bbcs_settings,
				array( 'value' => $state ),
				array( 'key' => 'disable' ),
				array( '%d' ),
				array( '%s' )
			);
		} else {
			$updated = $wpdb->insert(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'disable',
					'value' => $state,
				),
				array( '%s', '%d' )
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $updated !== false ) {
			BotBlockerFileRenderer::generateSettingsFile();
		} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [DB] Error change security state of BotBlocker' );
		}
	}

	public static function getIPNotLikeSQL(): array {
		$BBCS = BotBlocker::getInstance();
		if ( empty( $BBCS->self_ips ) || ! is_array( $BBCS->self_ips ) ) {
			return array( '', array() );
		}
		$ips = array();
		foreach ( $BBCS->self_ips as $ip => $status ) {
			if ( $status === BBCS_RULE_ALLOW ) {
				$ips[] = $ip;
			}
		}
		if ( empty( $ips ) ) {
			return array( '', array() );
		}
		$placeholders = implode( ', ', array_fill( 0, count( $ips ), '%s' ) );
		$fragment     = " AND ip NOT IN ( $placeholders )";
		return array( $fragment, $ips );
	}

	/**
	 * Returns the backtick-quoted list of columns present in both tables, in the
	 * destination's ordinal order — e.g. "`cid`, `date`, `ip`".
	 *
	 * `INSERT INTO dst SELECT * FROM src` matches columns positionally, so it breaks the
	 * moment the two schemas diverge by even one column, with "Column count doesn't match
	 * value count". Naming the shared columns explicitly makes the copy independent of
	 * both column count and column order.
	 *
	 * @return string Empty string when the tables share no columns or cannot be read.
	 */
	public static function sharedColumnList( string $src, string $dst ): string {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$src_columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$src}`" );
		if ( ! empty( $wpdb->last_error ) ) {
			return '';
		}
		$dst_columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$dst}`" );
		if ( ! empty( $wpdb->last_error ) ) {
			return '';
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$shared = array_intersect( (array) $dst_columns, (array) $src_columns );
		if ( empty( $shared ) ) {
			return '';
		}

		$quoted = array();
		foreach ( $shared as $column ) {
			$quoted[] = '`' . str_replace( '`', '``', (string) $column ) . '`';
		}

		return implode( ', ', $quoted );
	}

	/** True when `filtered` can be trusted for a query starting at $range_start. */
	public static function pageFilterFastPath( int $range_start ): bool {
		if ( get_option( BotBlockerStore::FILTERED_COLUMN_OPTION ) !== '1' ) {
			return false;
		}
		$watermark = get_option( BotBlockerStore::FILTERED_WATERMARK_OPTION, null );
		if ( null === $watermark ) {
			return false;
		}
		$watermark = (int) $watermark;
		return 0 === $watermark || $range_start >= $watermark;
	}

	/** Fast: ''; legacy: LEFT JOIN page_filters. */
	public static function pageFilterJoin( string $alias, int $range_start ): string {
		global $wpdb;
		if ( self::pageFilterFastPath( $range_start ) ) {
			return '';
		}
		return "LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON {$alias}.page LIKE pf.pattern";
	}

	/** Fast: filtered=0; legacy: pf.pattern IS NULL. */
	public static function pageFilterWhere( string $alias, int $range_start ): string {
		if ( self::pageFilterFastPath( $range_start ) ) {
			return "AND {$alias}.filtered = 0";
		}
		return 'AND pf.pattern IS NULL';
	}

	/** Fast: filtered=0; legacy: NOT EXISTS page_filters LIKE. */
	public static function pageFilterNotExists( string $alias, int $range_start ): string {
		global $wpdb;
		if ( self::pageFilterFastPath( $range_start ) ) {
			return "AND {$alias}.filtered = 0";
		}
		return "AND NOT EXISTS (SELECT 1 FROM `{$wpdb->bbcs_page_filters}` AS pf WHERE {$alias}.page LIKE pf.pattern)";
	}

	/** pageFilterNotExists() without leading AND. */
	public static function pageFilterWhereNotExists( string $alias, int $range_start ): string {
		return substr( self::pageFilterNotExists( $alias, $range_start ), 4 );
	}

	/** Fast: project `filtered`; legacy: project `page`. */
	public static function pageFilterColumn( int $range_start ): string {
		if ( self::pageFilterFastPath( $range_start ) ) {
			return 'filtered';
		}
		return 'page';
	}

	public static function generateAllFiles(): bool {
		BotBlockerFileRenderer::renderRules();
		BotBlockerFileRenderer::renderPaths();
		BotBlockerFileRenderer::renderSearchEngines();
		BotBlockerFileRenderer::renderBotSignatures();
		BotBlockerFileRenderer::renderLlmTrusted();
		BotBlockerFileRenderer::renderAsn();
		BotBlockerFileRenderer::renderIps();
		BotBlockerFileRenderer::renderProxy();
		BotBlockerFileRenderer::renderTlsFingerprints();
		BotBlockerFileRenderer::renderCountries();
		BotBlockerFileRenderer::generateSettingsFile();

		BotBlockerCache::clearFileCache();
		return true;
	}
}
