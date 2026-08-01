<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerStatsChartsTrait {

	public static function getDailyHitsChartData(): array {
		global $wpdb;
		$BBCS                        = BotBlocker::getInstance();
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();

		$cache_key = 'bbcs_daily_hits_chart_data';

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );

			if ( $cached !== false && is_array( $cached ) && isset( $cached['labels'], $cached['values'] ) ) {
				return $cached;
			}
		}

		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );

		$tz           = new \DateTimeZone( $gmt_offset_str );
		$current_date = new \DateTime( 'now', $tz );

		$start_of_day = ( clone $current_date )->setTime( 0, 0, 0 );
		$end_of_day   = ( clone $current_date )->setTime( 23, 59, 59 );

		// REVIEWER NOTE: Exclusion fragment uses only internally controlled self‑IPs and is bound via prepare; no user data flows here.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT HOUR(CONVERT_TZ(FROM_UNIXTIME(date), '+00:00', %s)) AS hour, COUNT(*) AS hits
				FROM (
					SELECT * FROM `{$wpdb->bbcs_hits}`
					UNION ALL
					SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
				) AS combined_hits
				WHERE date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
								AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
				AND NOT EXISTS (
					SELECT 1 FROM `{$wpdb->bbcs_page_filters}` AS pf
					WHERE combined_hits.page LIKE pf.pattern
				)
				{$ip_not_in_sql}
				GROUP BY hour
				ORDER BY hour
				",
				$gmt_offset_str,
				$start_of_day->format( 'Y-m-d H:i:s' ),
				$gmt_offset_str,
				$end_of_day->format( 'Y-m-d H:i:s' ),
				$gmt_offset_str,
				...$ip_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$values = array_fill( 0, 24, 0 );
		foreach ( (array) $results as $row ) {
			$hour            = (int) $row->hour;
			$values[ $hour ] = (int) $row->hits;
		}

		$labels = array();
		for ( $i = 0; $i < 24; $i++ ) {
			$labels[] = sprintf( '%02d:00', $i );
		}

		$data = array(
			'labels' => $labels,
			'values' => $values,
		);

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $data, $BBCS->settings->cache_ui_duration );
		}

		return $data;
	}

	public static function getHitsAndUniquesChartData( int $days ): array {
		global $wpdb;
		$BBCS                        = BotBlocker::getInstance();
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();

		$cache_key = 'bbcs_hits_and_uniques_chart_data_' . $days;

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );

			if ( $cached !== false && is_array( $cached ) && isset( $cached['labels'], $cached['uniques'], $cached['hits'] ) ) {
				return $cached;
			}
		}

		$days = min( max( $days, 1 ), 31 );

		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );

		$tz           = new \DateTimeZone( $gmt_offset_str );
		$current_date = new \DateTime( 'now', $tz );

		$end_date   = $current_date->format( 'Y-m-d 23:59:59' );
		$start_date = ( clone $current_date )->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d 00:00:00' );

		$today_str     = $current_date->format( 'Y-m-d' );
		$yesterday_str = ( clone $current_date )->modify( '-1 day' )->format( 'Y-m-d' );
		$start_day     = substr( $start_date, 0, 10 );

		$chart_data = array();
		$cur        = new \DateTime( $start_date, $tz );
		$end        = new \DateTime( $end_date, $tz );
		while ( $cur <= $end ) {
			$d                = $cur->format( 'Y-m-d' );
			$chart_data[ $d ] = array(
				'uniques' => 0,
				'hits'    => 0,
			);
			$cur->modify( '+1 day' );
		}

		if ( $days > 1 && BotBlockerSummary::getCompleteDays( $start_day, $yesterday_str ) ) {
			$past_hits = BotBlockerSummary::getPerDay( 'chart_hits', $start_day, $yesterday_str );
			$past_uniq = BotBlockerSummary::getPerDay( 'chart_uniques', $start_day, $yesterday_str );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$today_live = $wpdb->get_row(
				$wpdb->prepare(
					'
                SELECT COUNT(*) AS hits, COUNT(DISTINCT ch.ip) AS uniques
                FROM (
                    SELECT date, ip, page FROM `' . $wpdb->bbcs_hits . '`
                    UNION ALL
                    SELECT date, ip, page FROM `' . $wpdb->bbcs_hits_suspicious . '`
                ) AS ch
                LEFT JOIN `' . $wpdb->bbcs_page_filters . '` AS pf ON ch.page LIKE pf.pattern
                WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, \'+00:00\'))
                                AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, \'+00:00\'))
                AND pf.pattern IS NULL
                ' . $ip_not_in_sql . '
                ',
					$today_str . ' 00:00:00',
					$gmt_offset_str,
					$today_str . ' 23:59:59',
					$gmt_offset_str,
					...$ip_params
				),
				ARRAY_A
			);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $chart_data as $d => &$vals ) {
				if ( $d === $today_str ) {
					$vals['hits']    = (int) ( $today_live['hits'] ?? 0 );
					$vals['uniques'] = (int) ( $today_live['uniques'] ?? 0 );
				} else {
					$vals['hits']    = $past_hits[ $d ] ?? 0;
					$vals['uniques'] = $past_uniq[ $d ] ?? 0;
				}
			}
			unset( $vals );
		} else {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'
                SELECT
                    DATE(CONVERT_TZ(FROM_UNIXTIME(ch.date), \'+00:00\', %s)) AS visit_date,
                    COUNT(DISTINCT ch.ip) AS uniques,
                    COUNT(*) AS hits
                FROM (
                    SELECT date, ip, page FROM `' . $wpdb->bbcs_hits . '`
                    UNION ALL
                    SELECT date, ip, page FROM `' . $wpdb->bbcs_hits_suspicious . '`
                ) AS ch
                LEFT JOIN `' . $wpdb->bbcs_page_filters . '` AS pf ON ch.page LIKE pf.pattern
                WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, \'+00:00\'))
                                AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, \'+00:00\'))
                AND pf.pattern IS NULL
                ' . $ip_not_in_sql . '
                GROUP BY visit_date
                ORDER BY visit_date ASC
                ',
					$gmt_offset_str,
					$start_date,
					$gmt_offset_str,
					$end_date,
					$gmt_offset_str,
					...$ip_params
				)
			);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			foreach ( (array) $results as $row ) {
				$chart_data[ $row->visit_date ]['uniques'] = (int) $row->uniques;
				$chart_data[ $row->visit_date ]['hits']    = (int) $row->hits;
			}
		}

		$labels  = array();
		$uniques = array();
		$hits    = array();
		ksort( $chart_data );
		foreach ( $chart_data as $date => $values ) {
			$labels[]  = gmdate( 'd', strtotime( $date ) );
			$uniques[] = (int) $values['uniques'];
			$hits[]    = (int) $values['hits'];
		}

		$data = array(
			'labels'  => $labels,
			'uniques' => $uniques,
			'hits'    => $hits,
		);

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $data, $BBCS->settings->cache_ui_duration );
		}

		return $data;
	}

	public static function getDonutPieChartData( string $data_type, string $period = 'today' ): array {
		$BBCS = BotBlocker::getInstance();

		if ( ! isset( $BBCS->counters[ $period ] ) ) {
			return array( 'labels' => array(), 'values' => array(), 'title' => '', 'container_id' => '' );
		}

		$data   = $BBCS->counters[ $period ];
		$labels = array();
		$values = array();
		$title  = '';

		switch ( $data_type ) {
			case 'ip_hits_hosts':
				$labels = array(
					_x( 'Hits', 'statistics label', 'botblocker-security' ),
					_x( 'Unique IPs', 'statistics label', 'botblocker-security' ),
				);
				$values = array( (int) ( isset( $data['hits'] ) ? $data['hits'] : 0 ), (int) ( isset( $data['uniques'] ) ? $data['uniques'] : 0 ) );
				$title  = __( 'IP Hits and Unique IPs', 'botblocker-security' );
				break;

			case 'cookie_hits_hosts':
				$labels = array(
					_x( 'Hits', 'statistics label', 'botblocker-security' ),
					_x( 'Unique Visitors', 'statistics label', 'botblocker-security' ),
				);
				$values = array( (int) ( isset( $data['hit_count'] ) ? $data['hit_count'] : 0 ), (int) ( isset( $data['hit_hosts'] ) ? $data['hit_hosts'] : 0 ) );
				$title  = __( 'Cookie Hits and Visitors', 'botblocker-security' );
				break;

			case 'device_types':
				$labels = array(
					_x( 'PC', 'device type label', 'botblocker-security' ),
					_x( 'Set-top Box', 'device type label', 'botblocker-security' ),
					_x( 'Phone', 'device type label', 'botblocker-security' ),
					_x( 'Tablet', 'device type label', 'botblocker-security' ),
					_x( 'TV', 'device type label', 'botblocker-security' ),
				);
				$values = array( (int) ( isset( $data['pc'] ) ? $data['pc'] : 0 ), (int) ( isset( $data['box'] ) ? $data['box'] : 0 ), (int) ( isset( $data['phone'] ) ? $data['phone'] : 0 ), (int) ( isset( $data['tablet'] ) ? $data['tablet'] : 0 ), (int) ( isset( $data['tv'] ) ? $data['tv'] : 0 ) );
				$title  = __( 'Device Types', 'botblocker-security' );
				break;

			case 'browsers':
				if ( ! empty( $data['browsers'] ) ) {
					foreach ( $data['browsers'] as $browser => $count ) {
						$labels[] = sanitize_text_field( (string) $browser );
						$values[] = (int) $count;
					}
				}
				$title = __( 'Browsers', 'botblocker-security' );
				break;

			case 'operating_systems':
				if ( ! empty( $data['operating_systems'] ) ) {
					foreach ( $data['operating_systems'] as $os => $count ) {
						$labels[] = sanitize_text_field( (string) $os );
						$values[] = (int) $count;
					}
				}
				$title = __( 'Operating Systems', 'botblocker-security' );
				break;

			default:
				return array( 'labels' => array(), 'values' => array(), 'title' => '', 'container_id' => '' );
		}

		$container_id = 'bbcs_statistics_chart_' . sanitize_key( $data_type );

		return array(
			'labels'       => $labels,
			'values'       => $values,
			'title'        => $title,
			'container_id' => $container_id,
		);
	}
}
