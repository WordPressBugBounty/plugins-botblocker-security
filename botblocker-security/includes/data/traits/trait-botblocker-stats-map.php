<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerStatsMapTrait {

	public static function getVisitorsMapData( int $days ): array {
		global $wpdb;
		$BBCS                        = BotBlocker::getInstance();
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();

		$cache_key = 'bbcs_visitors_map_data_' . $days;

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );

			if ( $cached !== false && is_array( $cached ) ) {
				return $cached;
			}
		}

		$days = min( max( $days, 1 ), 365 );

		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );

		$tz  = new \DateTimeZone( $gmt_offset_str );
		$now = new \DateTime( 'now', $tz );

		$start_dt = ( clone $now )->modify( '-' . ( $days - 1 ) . ' days' )->setTime( 0, 0, 0 );
		$end_dt   = ( clone $now )->setTime( 23, 59, 59 );

		$utc      = new \DateTimeZone( 'UTC' );
		$start_ts = (int) ( clone $start_dt )->setTimezone( $utc )->getTimestamp();
		$end_ts   = (int) ( clone $end_dt )->setTimezone( $utc )->getTimestamp();

		$today_str     = $now->format( 'Y-m-d' );
		$yesterday_str = ( clone $now )->modify( '-1 day' )->format( 'Y-m-d' );
		$start_str     = $start_dt->format( 'Y-m-d' );

		$chart_data = array();

		if ( $days > 1 && BotBlockerSummary::getCompleteDays( $start_str, $yesterday_str ) ) {
			$past_countries = BotBlockerSummary::getDimensions( 'top_country', $start_str, $yesterday_str );

			$today_start_ts = (int) ( clone (new \DateTime( $today_str . ' 00:00:00', $tz )) )->setTimezone( $utc )->getTimestamp();
			$today_end_ts   = (int) ( clone (new \DateTime( $today_str . ' 23:59:59', $tz )) )->setTimezone( $utc )->getTimestamp();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$today_results = $wpdb->get_results(
				$wpdb->prepare(
					'
            SELECT u.country, COUNT(*) AS unique_visitors
            FROM (
                SELECT CAST(h.country AS CHAR(8)) AS country, CAST(h.ip AS CHAR(45)) AS ip
                FROM `' . $wpdb->bbcs_hits . '` h
                ' . BotBlockerDb::pageFilterJoin( 'h', $today_start_ts ) . '
                WHERE h.date BETWEEN %d AND %d
                  ' . BotBlockerDb::pageFilterWhere( 'h', $today_start_ts ) . '
                  AND h.country <> \'\' AND h.country <> %s
                  ' . $ip_not_in_sql . '
                GROUP BY 1, 2

                UNION

                SELECT CAST(s.country AS CHAR(8)) AS country, CAST(s.ip AS CHAR(45)) AS ip
                FROM `' . $wpdb->bbcs_hits_suspicious . '` s
                ' . BotBlockerDb::pageFilterJoin( 's', $today_start_ts ) . '
                WHERE s.date BETWEEN %d AND %d
                  ' . BotBlockerDb::pageFilterWhere( 's', $today_start_ts ) . '
                  AND s.country <> \'\' AND s.country <> %s
                  ' . $ip_not_in_sql . '
                GROUP BY 1, 2
            ) u
            GROUP BY u.country
            ORDER BY unique_visitors DESC
        ',
					...array_merge( array( $today_start_ts, $today_end_ts, BOTBLOCKER_EMPTY ), $ip_params, array( $today_start_ts, $today_end_ts, BOTBLOCKER_EMPTY ), $ip_params )
				)
			);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$today_countries = array();
			foreach ( (array) $today_results as $row ) {
				$today_countries[ $row->country ] = (int) $row->unique_visitors;
			}

			$chart_data = BotBlockerSummary::mergeArrays( $past_countries, $today_countries );
		} else {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'
            SELECT u.country, COUNT(*) AS unique_visitors
            FROM (
                SELECT CAST(h.country AS CHAR(8)) AS country, CAST(h.ip AS CHAR(45)) AS ip
                FROM `' . $wpdb->bbcs_hits . '` h
                ' . BotBlockerDb::pageFilterJoin( 'h', $start_ts ) . '
                WHERE h.date BETWEEN %d AND %d
                  ' . BotBlockerDb::pageFilterWhere( 'h', $start_ts ) . '
                  AND h.country <> \'\' AND h.country <> %s
                  ' . $ip_not_in_sql . '
                GROUP BY 1, 2

                UNION

                SELECT CAST(s.country AS CHAR(8)) AS country, CAST(s.ip AS CHAR(45)) AS ip
                FROM `' . $wpdb->bbcs_hits_suspicious . '` s
                ' . BotBlockerDb::pageFilterJoin( 's', $start_ts ) . '
                WHERE s.date BETWEEN %d AND %d
                  ' . BotBlockerDb::pageFilterWhere( 's', $start_ts ) . '
                  AND s.country <> \'\' AND s.country <> %s
                  ' . $ip_not_in_sql . '
                GROUP BY 1, 2
            ) u
            GROUP BY u.country
            ORDER BY unique_visitors DESC
        ',
					...array_merge( array( $start_ts, $end_ts, BOTBLOCKER_EMPTY ), $ip_params, array( $start_ts, $end_ts, BOTBLOCKER_EMPTY ), $ip_params )
				)
			);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			foreach ( (array) $results as $row ) {
				$chart_data[ $row->country ] = (int) $row->unique_visitors;
			}
		}

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $chart_data, $BBCS->settings->cache_ui_duration );
		}

		return $chart_data;
	}
}
