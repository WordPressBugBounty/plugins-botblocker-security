<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerStatsTopTrait {

	public static function getTopData( string $type, int $limit, int $days ): array {
		global $wpdb;
		$BBCS                        = BotBlocker::getInstance();
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();

		$allowed_columns = array( 'ip', 'country', 'device', 'browser' );
		if ( ! in_array( $type, $allowed_columns, true ) ) {
			return array();
		}

		$limit = absint( $limit );
		if ( $limit < 1 ) {
			$limit = 10;
		}
		$days = min( max( (int) $days, 1 ), 365 );

		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
		$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );
		$tz             = new \DateTimeZone( $gmt_offset_str );
		$now            = new \DateTime( 'now', $tz );

		$end_date_obj   = ( clone $now )->setTime( 23, 59, 59 );
		$start_date_obj = ( clone $now )->setTime( 0, 0, 0 );
		if ( $days > 1 ) {
			$start_date_obj->modify( '-' . ( $days - 1 ) . ' days' );
		}

		$start_date = $start_date_obj->format( 'Y-m-d H:i:s' );
		$end_date   = $end_date_obj->format( 'Y-m-d H:i:s' );

		$uniq_type = isset( $BBCS->settings->admin_uniq_type ) ? $BBCS->settings->admin_uniq_type : 'host';

		if ( $type === BBCS_IP_TYPE_IP ) {
			$count_expression = 'COUNT(*)';
		} elseif ( $uniq_type === 'host' ) {
			$count_expression = "COUNT(DISTINCT CONCAT(ip, '-', {$type}))";
		} else {
			$count_expression = 'COUNT(*)';
		}
		$today_str     = $now->format( 'Y-m-d' );
		$yesterday_str = ( clone $now )->modify( '-1 day' )->format( 'Y-m-d' );
		$start_str     = $start_date_obj->format( 'Y-m-d' );

		if ( $days > 1 && BotBlockerSummary::getCompleteDays( $start_str, $yesterday_str ) ) {
			$metric = 'top_' . $type;
			if ( $type !== BBCS_IP_TYPE_IP && $uniq_type !== 'host' ) {
				$metric .= '_h';
			}
			$past = BotBlockerSummary::getDimensions( $metric, $start_str, $yesterday_str );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching
			$today_res = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT ch.{$type} AS col_value, {$count_expression} AS count
					FROM (
						SELECT ip, country, device, browser, date, page FROM `{$wpdb->bbcs_hits}`
						UNION ALL
						SELECT ip, country, device, browser, date, page FROM `{$wpdb->bbcs_hits_suspicious}`
					) AS ch
					LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
					WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
									AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
					AND pf.pattern IS NULL
					AND ch.country != '' AND ch.country != %s AND ch.country != 'XX' AND ch.country != 'XZ'
					AND ch.ip != '' AND ch.ip != %s
					{$ip_not_in_sql}
					GROUP BY ch.{$type}
					ORDER BY count DESC
					",
					...array_merge( array( $today_str . ' 00:00:00', $gmt_offset_str, $today_str . ' 23:59:59', $gmt_offset_str, BOTBLOCKER_EMPTY, BOTBLOCKER_EMPTY ), $ip_params )
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching

			$today_map = array();
			foreach ( (array) $today_res as $r ) {
				$today_map[ $r['col_value'] ] = (int) $r['count'];
			}

			$merged = BotBlockerSummary::mergeArrays( $past, $today_map );

			$res = array();
			$i   = 0;
			foreach ( $merged as $key => $cnt ) {
				if ( ++$i > $limit ) {
					break;
				}
				$res[] = array(
					'col_value' => (string) $key,
					'count'     => (string) $cnt,
				);
			}

			if ( $type === BBCS_IP_TYPE_IP && $res ) {
				array_walk(
					$res,
					function ( &$item ) {
						$is_ipv6           = strpos( $item['col_value'], ':' ) !== false;
						$version           = $is_ipv6 ? 6 : 4;
						$item['col_value'] = BotBlockerIp::normalize( $item['col_value'], $version );
					}
				);
			}

			foreach ( $res as &$row ) {
				$row[ $type ] = $row['col_value'];
				unset( $row['col_value'] );
			}

			return $res;
		}

		// REVIEWER NOTE: All query parts are built internally by the plugin.
		// $type is always set by the plugin (allowed: ip, country, device, browser), never from user input.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT ch.{$type} AS col_value, {$count_expression} AS count
				FROM (
					SELECT ip, country, device, browser, date, page FROM `{$wpdb->bbcs_hits}`
					UNION ALL
					SELECT ip, country, device, browser, date, page FROM `{$wpdb->bbcs_hits_suspicious}`
				) AS ch
				LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
				WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
								AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
				AND pf.pattern IS NULL
				AND ch.country != '' AND ch.country != %s AND ch.country != 'XX' AND ch.country != 'XZ'
				AND ch.ip != '' AND ch.ip != %s
				{$ip_not_in_sql}
				GROUP BY ch.{$type}
				ORDER BY count DESC
				LIMIT %d
				",
				...array_merge( array( $start_date, $gmt_offset_str, $end_date, $gmt_offset_str, BOTBLOCKER_EMPTY, BOTBLOCKER_EMPTY ), $ip_params, array( $limit ) )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $type === BBCS_IP_TYPE_IP && $res ) {
			array_walk(
				$res,
				function ( &$item ) {
					$is_ipv6           = strpos( $item['col_value'], ':' ) !== false;
					$version           = $is_ipv6 ? 6 : 4;
					$item['col_value'] = BotBlockerIp::normalize( $item['col_value'], $version );
				}
			);
		}

		foreach ( $res as &$row ) {
			$row[ $type ] = $row['col_value'];
			unset( $row['col_value'] );
		}

		return $res;
	}
}
