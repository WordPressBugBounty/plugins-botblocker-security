<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerStatsLogsTrait {

	public static function getLatestHitsData(): array {
		global $wpdb;
		$BBCS                        = BotBlocker::getInstance();
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();

		$cache_key = 'bbcs_latest_hits_data';

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );

			if ( $cached !== false && is_array( $cached ) ) {
				return $cached;
			}
		}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared
		$sql        = '
        SELECT r.date, r.ip, r.country, r.lang, r.device, r.os FROM (
            (SELECT ch.date, ch.ip, ch.country, ch.lang, ch.device, ch.os
             FROM `' . $wpdb->bbcs_hits . '` AS ch
             LEFT JOIN `' . $wpdb->bbcs_page_filters . '` AS pf ON ch.page LIKE pf.pattern
             WHERE pf.pattern IS NULL' . $ip_not_in_sql . '
             ORDER BY ch.date DESC LIMIT 10)
            UNION ALL
            (SELECT ch.date, ch.ip, ch.country, ch.lang, ch.device, ch.os
             FROM `' . $wpdb->bbcs_hits_suspicious . '` AS ch
             LEFT JOIN `' . $wpdb->bbcs_page_filters . '` AS pf ON ch.page LIKE pf.pattern
             WHERE pf.pattern IS NULL' . $ip_not_in_sql . '
             ORDER BY ch.date DESC LIMIT 10)
        ) AS r ORDER BY r.date DESC LIMIT 10
    ';
		$all_params = array_merge( $ip_params, $ip_params );
		$results    = $wpdb->get_results(
			empty( $all_params ) ? $sql : $wpdb->prepare( $sql, ...$all_params ),
			ARRAY_A
		);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $results, $BBCS->settings->cache_ui_duration );
		}

		return $results;
	}
}
