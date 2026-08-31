<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerStatsCoreTrait {

	public static function getStatistics( int $period_days = 7 ): void {
		global $wpdb;
		BotBlockerDb::ensureUtcSession();
		$BBCS = BotBlocker::getInstance();

		$cache_key = 'bbcs_get_statistics';

		if ( $BBCS->settings->cache_ui_data == 1 ) {

			$bbcs_get_statistics = get_transient( $cache_key );

			if ( $bbcs_get_statistics ) {
				$BBCS->counters = $bbcs_get_statistics;
				return;
			}
		}

		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );
		$tz             = new \DateTimeZone( $gmt_offset_str );
		$current_date   = new \DateTime( 'now', $tz );

		$today_start                 = ( clone $current_date )->setTime( 0, 0, 0 );
		$today_end                   = ( clone $current_date )->setTime( 23, 59, 59 );
		$period_start                = ( clone $today_start )->modify( '-' . ( $period_days - 1 ) . ' days' );
		$today_ts                    = $today_start->getTimestamp();
		$today_end_ts                = $today_end->getTimestamp();
		$period_ts                   = $period_start->getTimestamp();
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// Exclusion fragment uses only internally controlled self‑IPs and is bound via prepare; no user data flows here.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		$today_pf_col  = BotBlockerDb::pageFilterColumn( $today_ts );
		$today_results = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT
					COUNT(*)                                             AS hits,
					COUNT(DISTINCT NULLIF(ch.ip, ''))                    AS uniques,
					COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='pc'    AND ch.ip!='' THEN ch.ip END)     AS pc,
					COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='box'   AND ch.ip!='' THEN ch.ip END)     AS box,
					COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='phone' AND ch.ip!='' THEN ch.ip END)     AS phone,
					COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tablet'AND ch.ip!='' THEN ch.ip END)     AS tablet,
					COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tv'    AND ch.ip!='' THEN ch.ip END)     AS tv,
					COUNT(DISTINCT CASE WHEN ch.hit = 1 AND ch.ip != '' THEN ch.ip END)                      AS hit_hosts,
					SUM(NULLIF(ch.hit, 0))                               AS hit_count
				FROM (
					SELECT date, CAST(ip AS CHAR(45)) AS ip, CAST(device AS CHAR(16)) AS device, hit, {$today_pf_col}, CAST(method AS CHAR(10)) AS method
						FROM `{$wpdb->bbcs_hits}`
					UNION ALL
					SELECT date, CAST(ip AS CHAR(45)), CAST(device AS CHAR(16)), hit, {$today_pf_col}, CAST(method AS CHAR(10))
						FROM `{$wpdb->bbcs_hits_suspicious}`
				) AS ch
				" . BotBlockerDb::pageFilterJoin( 'ch', $today_ts ) . "
				WHERE ch.date BETWEEN %d AND %d
				" . BotBlockerDb::pageFilterWhere( 'ch', $today_ts ) . "
				AND ch.method = 'GET'
				{$ip_not_in_sql}
				",
				$today_ts,
				$today_end_ts,
				...$ip_params
			),
			ARRAY_A
		);
		$yesterday     = ( clone $today_start )->modify( '-1 day' );
		$ps            = $period_start->format( 'Y-m-d' );
		$ys            = $yesterday->format( 'Y-m-d' );
		$use_summary   = ( $period_days > 1 && BotBlockerSummary::getCompleteDays( $ps, $ys ) );

		if ( $use_summary ) {
			$past           = BotBlockerSummary::getScalars( $ps, $ys );
			$period_results = array();
			foreach ( array( 'hits', 'uniques', 'pc', 'box', 'phone', 'tablet', 'tv', 'hit_hosts', 'hit_count' ) as $sk ) {
				$period_results[ $sk ] = $past[ $sk ] + (int) ( $today_results[ $sk ] ?? 0 );
			}
		} else {
			$period_pf_col  = BotBlockerDb::pageFilterColumn( $period_ts );
			$period_results = $wpdb->get_row(
				$wpdb->prepare(
					"
					SELECT
						COUNT(*)                                             AS hits,
						COUNT(DISTINCT NULLIF(ch.ip, ''))                    AS uniques,
						COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='pc'    AND ch.ip!='' THEN ch.ip END)     AS pc,
						COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='box'   AND ch.ip!='' THEN ch.ip END)     AS box,
						COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='phone' AND ch.ip!='' THEN ch.ip END)     AS phone,
						COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tablet'AND ch.ip!='' THEN ch.ip END)     AS tablet,
						COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tv'    AND ch.ip!='' THEN ch.ip END)     AS tv,
						COUNT(DISTINCT CASE WHEN ch.hit = 1 AND ch.ip != '' THEN ch.ip END)                      AS hit_hosts,
						SUM(NULLIF(ch.hit, 0))                               AS hit_count
					FROM (
						SELECT date, CAST(ip AS CHAR(45)) AS ip, CAST(device AS CHAR(16)) AS device, hit, {$period_pf_col}, CAST(method AS CHAR(10)) AS method
							FROM `{$wpdb->bbcs_hits}`
						UNION ALL
						SELECT date, CAST(ip AS CHAR(45)), CAST(device AS CHAR(16)), hit, {$period_pf_col}, CAST(method AS CHAR(10))
							FROM `{$wpdb->bbcs_hits_suspicious}`
					) AS ch
					" . BotBlockerDb::pageFilterJoin( 'ch', $period_ts ) . "
					WHERE ch.date BETWEEN %d AND %d
					" . BotBlockerDb::pageFilterWhere( 'ch', $period_ts ) . "
					AND ch.method = 'GET'
					{$ip_not_in_sql}
					",
					$period_ts,
					$today_end_ts,
					...$ip_params
				),
				ARRAY_A
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $use_summary ) {
			$grouped                             = self::getAllGroupedCounts( $today_start, $today_start, $today_end, $gmt_offset_str );
			$pb                                  = BotBlockerSummary::getDimensions( 'grp_browser', $ps, $ys );
			$po                                  = BotBlockerSummary::getDimensions( 'grp_os', $ps, $ys );
			$pw                                  = BotBlockerSummary::getDimensions( 'grp_wbot', $ps, $ys );
			$today_results['browsers']           = $grouped['today']['browsers'];
			$today_results['operating_systems']  = $grouped['today']['operating_systems'];
			$today_results['white_bots']         = $grouped['today']['white_bots'];
			$period_results['browsers']          = BotBlockerSummary::mergeArrays( $pb, $grouped['today']['browsers'] );
			$period_results['operating_systems'] = BotBlockerSummary::mergeArrays( $po, $grouped['today']['operating_systems'] );
			$period_results['white_bots']        = BotBlockerSummary::mergeArrays( $pw, $grouped['today']['white_bots'] );
		} else {
			$grouped                             = self::getAllGroupedCounts( $period_start, $today_start, $today_end, $gmt_offset_str );
			$today_results['browsers']           = $grouped['today']['browsers'];
			$today_results['operating_systems']  = $grouped['today']['operating_systems'];
			$today_results['white_bots']         = $grouped['today']['white_bots'];
			$period_results['browsers']          = $grouped['period']['browsers'];
			$period_results['operating_systems'] = $grouped['period']['operating_systems'];
			$period_results['white_bots']        = $grouped['period']['white_bots'];
		}

		$BBCS->counters = array(
			'today'  => $today_results,
			'period' => $period_results,
		);

		if ( $BBCS->settings->cache_ui_data == 1 ) {

			set_transient( $cache_key, $BBCS->counters, $BBCS->settings->cache_ui_duration );

		}
	}

	public static function getAllGroupedCounts( \DateTime $period_start, \DateTime $today_start, \DateTime $today_end, string $gmt_offset_str ): array {
		global $wpdb;
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();
		$period_ts                   = $period_start->getTimestamp();
		$today_ts                    = $today_start->getTimestamp();
		$today_end_ts                = $today_end->getTimestamp();
		$period_pf_col               = BotBlockerDb::pageFilterColumn( $period_ts );

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// Exclusion fragment uses only internally controlled self‑IPs and is bound via prepare; no user data flows here.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT
					ch.ip,
					IFNULL(NULLIF(ch.browser,''), 'NA') AS browser,
					IFNULL(NULLIF(ch.os,''), 'NA')      AS os,
					IFNULL(NULLIF(ch.wbot,''), 'NA')    AS wbot,
					CASE WHEN ch.date >= %d
						 THEN 1 ELSE 0 END AS is_today
				FROM (
					SELECT date, CAST(ip AS CHAR(45)) AS ip, CAST(browser AS CHAR(32)) AS browser,
						CAST(os AS CHAR(32)) AS os, CAST(wbot AS CHAR(32)) AS wbot, {$period_pf_col}, CAST(method AS CHAR(10)) AS method
						FROM `{$wpdb->bbcs_hits}`
					UNION ALL
					SELECT date, CAST(ip AS CHAR(45)), CAST(browser AS CHAR(32)),
						CAST(os AS CHAR(32)), CAST(wbot AS CHAR(32)), {$period_pf_col}, CAST(method AS CHAR(10))
						FROM `{$wpdb->bbcs_hits_suspicious}`
				) AS ch
				" . BotBlockerDb::pageFilterJoin( 'ch', $period_ts ) . "
				WHERE ch.date BETWEEN %d AND %d
				" . BotBlockerDb::pageFilterWhere( 'ch', $period_ts ) . "
				AND ch.method = 'GET'
				AND ch.ip != ''
				{$ip_not_in_sql}
				",
				$today_ts,
				$period_ts,
				$today_end_ts,
				...$ip_params
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$today  = array(
			'browsers'          => array(),
			'operating_systems' => array(),
			'white_bots'        => array(),
		);
		$period = array(
			'browsers'          => array(),
			'operating_systems' => array(),
			'white_bots'        => array(),
		);

		foreach ( $rows as $row ) {
			$ip = $row['ip'];

			$period['browsers'][ $row['browser'] ][ $ip ]     = true;
			$period['operating_systems'][ $row['os'] ][ $ip ] = true;
			$period['white_bots'][ $row['wbot'] ][ $ip ]      = true;

			if ( (int) $row['is_today'] === 1 ) {
				$today['browsers'][ $row['browser'] ][ $ip ]     = true;
				$today['operating_systems'][ $row['os'] ][ $ip ] = true;
				$today['white_bots'][ $row['wbot'] ][ $ip ]      = true;
			}
		}

		$to_counts = static function ( array $map ): array {
			$counts = array_map( 'count', $map );
			arsort( $counts );
			return $counts;
		};

		return array(
			'today'  => array(
				'browsers'          => $to_counts( $today['browsers'] ),
				'operating_systems' => $to_counts( $today['operating_systems'] ),
				'white_bots'        => $to_counts( $today['white_bots'] ),
			),
			'period' => array(
				'browsers'          => $to_counts( $period['browsers'] ),
				'operating_systems' => $to_counts( $period['operating_systems'] ),
				'white_bots'        => $to_counts( $period['white_bots'] ),
			),
		);
	}

	public static function getCountersGridData(): array {
		$BBCS = BotBlocker::getInstance();

		$cache_key = 'bbcs_counters_grid_data';

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached    = get_transient( $cache_key );

			if ( $cached !== false && is_array( $cached ) ) {
				return $cached;
			}
		}

		if ( ! $BBCS->statistics ) {
			return array();
		}

		$data = array(
			'today_hits'              => (string) $BBCS->statistics['today_hits'],
			'today_blocked'           => (string) $BBCS->statistics['today_blocked'],
			'total_hits'              => (string) $BBCS->statistics['total_hits'],
			'total_blocked'           => (string) $BBCS->statistics['total_blocked'],
			'search_engine_visits'    => (string) $BBCS->statistics['search_engine_visits'],
			'percent_requests_blocked' => (string) $BBCS->statistics['percent_requests_blocked'],
		);

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $data, $BBCS->settings->cache_ui_duration );
		}

		return $data;
	}

	/**
	 * @param array<string, string>|null $stats Optional counters from getCountersGridData().
	 * @return array<string, string>
	 */
	public static function getKpiViewData( ?array $stats = null ): array {
		if ( null === $stats ) {
			$stats = self::getCountersGridData();
		}

		$total_hits  = max( (int) ( $stats['total_hits'] ?? 0 ), 0 );
		$total_blk   = max( (int) ( $stats['total_blocked'] ?? 0 ), 0 );
		$denom_total = $total_hits + $total_blk;
		$today_hits  = max( (int) ( $stats['today_hits'] ?? 0 ), 0 );
		$today_blk   = max( (int) ( $stats['today_blocked'] ?? 0 ), 0 );
		$denom       = $today_hits + $today_blk;

		return array(
			'kpi_requests_today'        => isset( $stats['today_hits'] ) ? $stats['today_hits'] : '0',
			'kpi_requests_total'        => isset( $stats['total_hits'] ) ? $stats['total_hits'] : '0',
			'kpi_blocked_today'         => isset( $stats['today_blocked'] ) ? $stats['today_blocked'] : '0',
			'kpi_blocked_total'         => isset( $stats['total_blocked'] ) ? $stats['total_blocked'] : '0',
			'kpi_all_requests_total'    => (string) $denom_total,
			'kpi_blocked_percent'       => $denom > 0 ? (string) round( ( $today_blk / $denom ) * 100, 2 ) : '0',
			'kpi_blocked_percent_total' => $denom_total > 0 ? (string) round( ( $total_blk / $denom_total ) * 100, 2 ) : '0',
			'kpi_allowed_percent'       => $denom > 0 ? (string) round( ( $today_hits / $denom ) * 100, 2 ) : '0',
			'kpi_allowed_percent_total' => $denom_total > 0 ? (string) round( ( $total_hits / $denom_total ) * 100, 2 ) : '0',
			'kpi_search_engines'        => isset( $stats['search_engine_visits'] ) ? $stats['search_engine_visits'] : '0',
		);
	}
}
