<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerSummary
{
	public static function isTableReady(): bool {
		static $ready = array();
		$blog_id      = get_current_blog_id();
		if ( isset( $ready[ $blog_id ] ) ) {
			return $ready[ $blog_id ];
		}
		global $wpdb;
		if ( empty( $wpdb->bbcs_daily_summary ) ) {
			$ready[ $blog_id ] = false;
			return false;
		}
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ready[ $blog_id ] = (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->bbcs_daily_summary )
		);
		return $ready[ $blog_id ];
	}

	public static function getCompleteDays( string $start, string $end ): bool {
		if ( ! self::isTableReady() ) {
			return false;
		}
		global $wpdb;
		$d1 = new \DateTime( $start );
		$d2 = new \DateTime( $end );
		if ( $d1 > $d2 ) {
			return false;
		}
		$expected = (int) $d1->diff( $d2 )->days + 1;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->bbcs_daily_summary}`
	             WHERE metric = '_complete' AND dim_key = '' AND val = 1
	             AND date_key BETWEEN %s AND %s",
				$start,
				$end
			)
		);
		return $found >= $expected;
	}

	public static function getScalars( string $start, string $end ): array {
		global $wpdb;
		$keys         = array( 'hits', 'uniques', 'pc', 'box', 'phone', 'tablet', 'tv', 'hit_hosts', 'hit_count', 'chart_hits', 'chart_uniques' );
		$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT metric, SUM(val) AS total
	             FROM `{$wpdb->bbcs_daily_summary}`
	             WHERE date_key BETWEEN %s AND %s
	             AND metric IN ($placeholders) AND dim_key = ''
	             GROUP BY metric",
				$start,
				$end,
				...$keys
			),
			ARRAY_A
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$out = array_fill_keys( $keys, 0 );
		foreach ( (array) $rows as $r ) {
			$out[ $r['metric'] ] = (int) $r['total'];
		}
		return $out;
	}

	public static function getDimensions( string $metric, string $start, string $end, int $limit = 0 ): array {
		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT dim_key, SUM(val) AS total
	         FROM `{$wpdb->bbcs_daily_summary}`
	         WHERE metric = %s AND date_key BETWEEN %s AND %s AND dim_key != ''
	         GROUP BY dim_key ORDER BY total DESC",
			$metric,
			$start,
			$end
		);
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $r ) {
			$out[ $r['dim_key'] ] = (int) $r['total'];
		}
		return $out;
	}

	public static function getPerDay( string $metric, string $start, string $end ): array {
		global $wpdb;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date_key, val
	             FROM `{$wpdb->bbcs_daily_summary}`
	             WHERE metric = %s AND dim_key = '' AND date_key BETWEEN %s AND %s
	             ORDER BY date_key ASC",
				$metric,
				$start,
				$end
			),
			ARRAY_A
		);
		$out  = array();
		foreach ( (array) $rows as $r ) {
			$out[ $r['date_key'] ] = (int) $r['val'];
		}
		return $out;
	}

	public static function invalidate(): void {
		if ( ! self::isTableReady() ) {
			return;
		}
		global $wpdb;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM `{$wpdb->bbcs_daily_summary}` WHERE metric = '_complete'" );
	}

	public static function truncate(): void {
		if ( ! self::isTableReady() ) {
			return;
		}
		global $wpdb;
		// DELETE FROM is used instead of TRUNCATE because TRUNCATE requires
		// the DROP privilege which may be unavailable on shared hosting.
		// The daily_summary table has no AUTO_INCREMENT column, so DELETE
		// is functionally identical for this use case.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query( "DELETE FROM `{$wpdb->bbcs_daily_summary}`" );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && $deleted !== false ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Summary] Daily summary truncated, ' . (int) $deleted . ' rows deleted' );
		}
	}

	public static function cleanOldData( int $store_period ): void {
		if ( ! self::isTableReady() ) {
			return;
		}
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d', strtotime( '-' . $store_period . ' days' ) );
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->bbcs_daily_summary}` WHERE date_key < %s",
				$cutoff
			)
		);
	}

	public static function aggregateDay( string $date_key ): bool {
		global $wpdb;
		$BBCS = BotBlocker::getInstance();

		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );
		$tz             = new \DateTimeZone( $gmt_offset_str );

		$day_start = $date_key . ' 00:00:00';
		$day_end   = $date_key . ' 23:59:59';

		[ $ip_frag, $ip_vals ] = BotBlockerDb::getIPNotLikeSQL();

		$base_from = "FROM (
	        SELECT date, ip, device, hit, page, method, country, browser, os, wbot FROM `{$wpdb->bbcs_hits}`
	        UNION ALL
	        SELECT date, ip, device, hit, page, method, country, browser, os, wbot FROM `{$wpdb->bbcs_hits_suspicious}`
	    ) AS ch
	    LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern";

		$base_where = "WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
	                   AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
	                   AND pf.pattern IS NULL {$ip_frag}";

		$date_params = array( $day_start, $gmt_offset_str, $day_end, $gmt_offset_str );
		$base_params = array_merge( $date_params, $ip_vals );

	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
	                COUNT(*) AS chart_hits,
	                COUNT(DISTINCT NULLIF(ch.ip, '')) AS chart_uniques,
	                SUM(CASE WHEN ch.method = 'GET' THEN 1 ELSE 0 END) AS hits,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' THEN NULLIF(ch.ip, '') END) AS uniques,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='pc' AND ch.ip!='' THEN ch.ip END) AS pc,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='box' AND ch.ip!='' THEN ch.ip END) AS box,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='phone' AND ch.ip!='' THEN ch.ip END) AS phone,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='tablet' AND ch.ip!='' THEN ch.ip END) AS tablet,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='tv' AND ch.ip!='' THEN ch.ip END) AS tv,
	                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND ch.hit = 1 AND ch.ip != '' THEN ch.ip END) AS hit_hosts,
	                SUM(CASE WHEN ch.method = 'GET' THEN NULLIF(ch.hit, 0) ELSE NULL END) AS hit_count
	            {$base_from} {$base_where}",
				...$base_params
			),
			ARRAY_A
		);

		$rows = array();
		foreach ( array( 'hits', 'uniques', 'pc', 'box', 'phone', 'tablet', 'tv', 'hit_hosts', 'hit_count', 'chart_hits', 'chart_uniques' ) as $m ) {
			$rows[] = array( $date_key, $m, '', (int) ( $stats[ $m ] ?? 0 ) );
		}

		$grp_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
	                IFNULL(NULLIF(ch.browser,''), 'NA') AS browser,
	                IFNULL(NULLIF(ch.os,''), 'NA') AS os,
	                IFNULL(NULLIF(ch.wbot,''), 'NA') AS wbot,
	                ch.ip
	            {$base_from} {$base_where} AND ch.method = 'GET' AND ch.ip != ''
	            GROUP BY ch.ip, browser, os, wbot",
				...$base_params
			),
			ARRAY_A
		);

		$browsers = array();
		$oses     = array();
		$wbots    = array();
		foreach ( (array) $grp_rows as $r ) {
			$browsers[ $r['browser'] ][ $r['ip'] ] = 1;
			$oses[ $r['os'] ][ $r['ip'] ]          = 1;
			$wbots[ $r['wbot'] ][ $r['ip'] ]       = 1;
		}
		foreach ( $browsers as $k => $ips ) {
			$rows[] = array( $date_key, 'grp_browser', $k, count( $ips ) );
		}
		foreach ( $oses as $k => $ips ) {
			$rows[] = array( $date_key, 'grp_os', $k, count( $ips ) );
		}
		foreach ( $wbots as $k => $ips ) {
			$rows[] = array( $date_key, 'grp_wbot', $k, count( $ips ) );
		}

		$empty_val  = defined( 'BOTBLOCKER_EMPTY' ) ? BOTBLOCKER_EMPTY : '-';
		$top_params = array_merge( $base_params, array( $empty_val, $empty_val ) );

		$top_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ch.ip, ch.country, ch.device, ch.browser
	            {$base_from} {$base_where}
	            AND ch.ip != '' AND ch.ip != %s
	            AND ch.country != '' AND ch.country != %s AND ch.country != 'XX' AND ch.country != 'XZ'",
				...$top_params
			),
			ARRAY_A
		);

		$t_ip        = array();
		$t_country   = array();
		$t_device    = array();
		$t_browser   = array();
		$t_country_h = array();
		$t_device_h  = array();
		$t_browser_h = array();

		foreach ( (array) $top_rows as $r ) {
			$ip = $r['ip'];
			if ( ! isset( $t_ip[ $ip ] ) ) {
				$t_ip[ $ip ] = 0;
			}
			++$t_ip[ $ip ];

			$t_country[ $r['country'] ][ $ip ] = 1;
			$t_device[ $r['device'] ][ $ip ]   = 1;
			$t_browser[ $r['browser'] ][ $ip ] = 1;

			if ( ! isset( $t_country_h[ $r['country'] ] ) ) {
				$t_country_h[ $r['country'] ] = 0;
			}
			++$t_country_h[ $r['country'] ];

			if ( ! isset( $t_device_h[ $r['device'] ] ) ) {
				$t_device_h[ $r['device'] ] = 0;
			}
			++$t_device_h[ $r['device'] ];

			if ( ! isset( $t_browser_h[ $r['browser'] ] ) ) {
				$t_browser_h[ $r['browser'] ] = 0;
			}
			++$t_browser_h[ $r['browser'] ];
		}

		arsort( $t_ip );
		$top_ip_limit = 200;
		$i            = 0;
		foreach ( $t_ip as $ip => $cnt ) {
			if ( ++$i > $top_ip_limit ) {
				break;
			}
			$rows[] = array( $date_key, 'top_ip', $ip, $cnt );
		}

		foreach ( $t_country as $k => $ips ) {
			$rows[] = array( $date_key, 'top_country', $k, count( $ips ) );
		}
		foreach ( $t_country_h as $k => $cnt ) {
			$rows[] = array( $date_key, 'top_country_h', $k, $cnt );
		}
		foreach ( $t_device as $k => $ips ) {
			$rows[] = array( $date_key, 'top_device', $k, count( $ips ) );
		}
		foreach ( $t_device_h as $k => $cnt ) {
			$rows[] = array( $date_key, 'top_device_h', $k, $cnt );
		}
		foreach ( $t_browser as $k => $ips ) {
			$rows[] = array( $date_key, 'top_browser', $k, count( $ips ) );
		}
		foreach ( $t_browser_h as $k => $cnt ) {
			$rows[] = array( $date_key, 'top_browser_h', $k, $cnt );
		}

	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->bbcs_daily_summary, array( 'date_key' => $date_key ), array( '%s' ) );

		$table = $wpdb->bbcs_daily_summary;
		$batch = array();
		foreach ( $rows as $r ) {
			$batch[] = $wpdb->prepare( '(%s,%s,%s,%d)', $r[0], $r[1], $r[2], $r[3] );
			if ( count( $batch ) >= 100 ) {
				$vals = implode( ',', $batch );
	            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "INSERT INTO `{$table}` (date_key, metric, dim_key, val) VALUES {$vals} ON DUPLICATE KEY UPDATE val = VALUES(val)" );
				$batch = array();
			}
		}
		if ( $batch ) {
			$vals = implode( ',', $batch );
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "INSERT INTO `{$table}` (date_key, metric, dim_key, val) VALUES {$vals} ON DUPLICATE KEY UPDATE val = VALUES(val)" );
		}

		$today    = ( new \DateTime( 'now', $tz ) )->format( 'Y-m-d' );
		$complete = ( $date_key < $today ) ? 1 : 0;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$table,
			array(
				'date_key' => $date_key,
				'metric'   => '_complete',
				'dim_key'  => '',
				'val'      => $complete,
			),
			array( '%s', '%s', '%s', '%d' )
		);

		return true;
	}

	public static function cronHandler(): void {
		if ( ! self::isTableReady() ) {
			return;
		}
		$BBCS       = BotBlocker::getInstance();
		$gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$tz         = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
		$today      = new \DateTime( 'now', $tz );
		$period     = isset( $BBCS->settings->admin_report_period ) ? max( 1, (int) $BBCS->settings->admin_report_period ) : 7;

		$cursor      = ( clone $today )->modify( '-' . ( $period - 1 ) . ' days' );
		$processed   = 0;
		$max_per_run = 3;

		while ( $cursor <= $today && $processed < $max_per_run ) {
			$dk       = $cursor->format( 'Y-m-d' );
			$is_today = ( $dk === $today->format( 'Y-m-d' ) );

			if ( ! $is_today && self::getCompleteDays( $dk, $dk ) ) {
				$cursor->modify( '+1 day' );
				continue;
			}

			self::aggregateDay( $dk );
			++$processed;
			$cursor->modify( '+1 day' );
		}
	}

	public static function backfillHandler(): void {
		if ( ! self::isTableReady() ) {
			return;
		}
		$BBCS       = BotBlocker::getInstance();
		$gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$tz         = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
		$today      = new \DateTime( 'now', $tz );
		$period     = isset( $BBCS->settings->admin_report_period ) ? max( 1, (int) $BBCS->settings->admin_report_period ) : 7;

		$start       = ( clone $today )->modify( '-' . ( $period - 1 ) . ' days' );
		$all_done    = true;
		$processed   = 0;
		$max_per_run = 2;

		$cursor = clone $start;
		while ( $cursor <= $today ) {
			$dk = $cursor->format( 'Y-m-d' );
			if ( ! self::getCompleteDays( $dk, $dk ) || $dk === $today->format( 'Y-m-d' ) ) {
				if ( $processed < $max_per_run ) {
					self::aggregateDay( $dk );
					++$processed;
				}
				if ( $dk !== $today->format( 'Y-m-d' ) ) {
					$all_done = false;
				}
			}
			$cursor->modify( '+1 day' );
		}

		if ( ! $all_done && ! wp_next_scheduled( 'bbcs_summary_backfill' ) ) {
			wp_schedule_single_event( time() + 120, 'bbcs_summary_backfill' );
		}
	}

	public static function mergeArrays( array $past, array $today ): array {
		$merged = $past;
		foreach ( $today as $k => $v ) {
			$merged[ $k ] = ( $merged[ $k ] ?? 0 ) + $v;
		}
		arsort( $merged );
		return $merged;
	}
}
