<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerCounters {

	public static function ensureRow( bool $force = false ): void {
		static $verified = false;
		if ( $verified && ! $force ) {
			return;
		}
		global $wpdb;
		if ( empty( $wpdb->bbcs_counters ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( "SELECT id FROM `{$wpdb->bbcs_counters}` WHERE id = 1" );
		if ( $exists ) {
			$verified = true;
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"INSERT INTO `{$wpdb->bbcs_counters}` (id, today_hits, today_blocked, total_hits, total_blocked, search_engine_visits) VALUES (1, 0, 0, 0, 0, 0)"
		);
		$verified = true;
	}

	public static function incrementTodayHits(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET today_hits = today_hits + 1 WHERE id = 1" );
	}

	public static function incrementTodayBlocked(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET today_blocked = today_blocked + 1 WHERE id = 1" );
	}

	public static function incrementTotalHits(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET total_hits = total_hits + 1 WHERE id = 1" );
	}

	public static function incrementTotalBlocked(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET total_blocked = total_blocked + 1 WHERE id = 1" );
	}

	public static function incrementSearchEngineVisits(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET search_engine_visits = search_engine_visits + 1 WHERE id = 1" );
	}

	public static function incrementHit( bool $searchbot = false ): void {
		global $wpdb;
		$BBCS           = BotBlocker::getInstance();
		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? floatval( $BBCS->settings->admin_gmt_offset ) : 0;
		$timezone       = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
		$now            = new \DateTime( 'now', $timezone );
		$today_start_dt = ( clone $now )->setTime( 0, 0, 0 );
		$today_start    = $today_start_dt->format( 'Y-m-d H:i:s' );
		$now_local      = $now->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_update_time  = $wpdb->get_var( "SELECT last_update FROM `{$wpdb->bbcs_counters}` WHERE id = 1" );
		$is_new_day        = $last_update_time && \DateTime::createFromFormat( 'Y-m-d H:i:s', $last_update_time, $timezone ) < $today_start_dt;
		$search_engine_sql = $searchbot ? ', search_engine_visits = search_engine_visits + 1' : '';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$result = $wpdb->query(
			"UPDATE `{$wpdb->bbcs_counters}` SET
			today_hits = IF(last_update IS NOT NULL AND last_update < '{$today_start}', 1, today_hits + 1),
			today_blocked = IF(last_update IS NOT NULL AND last_update < '{$today_start}', 0, today_blocked),
			total_hits = total_hits + 1{$search_engine_sql},
			last_update = '{$now_local}'
			WHERE id = 1"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( $result === false || $wpdb->rows_affected === 0 ) {
			self::ensureRow( true );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET today_hits = 1, total_hits = 1{$search_engine_sql}, last_update = '{$now_local}' WHERE id = 1" );
		}
		if ( $is_new_day ) {
			delete_transient( 'bbcs_site_health' );
			delete_transient( 'bbcs_site_health_list' );
		}
	}

	public static function incrementBlockedHit(): void {
		global $wpdb;
		$BBCS           = BotBlocker::getInstance();
		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? floatval( $BBCS->settings->admin_gmt_offset ) : 0;
		$timezone       = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
		$now            = new \DateTime( 'now', $timezone );
		$today_start_dt = ( clone $now )->setTime( 0, 0, 0 );
		$today_start    = $today_start_dt->format( 'Y-m-d H:i:s' );
		$now_local      = $now->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_update_time = $wpdb->get_var( "SELECT last_update FROM `{$wpdb->bbcs_counters}` WHERE id = 1" );
		$is_new_day       = $last_update_time && \DateTime::createFromFormat( 'Y-m-d H:i:s', $last_update_time, $timezone ) < $today_start_dt;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$result = $wpdb->query(
			"UPDATE `{$wpdb->bbcs_counters}` SET
			today_hits = IF(last_update IS NOT NULL AND last_update < '{$today_start}', 0, today_hits),
			today_blocked = IF(last_update IS NOT NULL AND last_update < '{$today_start}', 1, today_blocked + 1),
			total_blocked = total_blocked + 1,
			last_update = '{$now_local}'
			WHERE id = 1"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( $result === false || $wpdb->rows_affected === 0 ) {
			self::ensureRow( true );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET today_blocked = 1, total_blocked = 1, last_update = '{$now_local}' WHERE id = 1" );
		}
		if ( $is_new_day ) {
			delete_transient( 'bbcs_site_health' );
			delete_transient( 'bbcs_site_health_list' );
		}
	}

	public static function processHit( $reason ): void {
		$reason = is_numeric( $reason ) ? (int) $reason : null;
		$code   = bbcs_codeList( $reason );

		if ( ! $code['count'] ) {
			return;
		}

		if ( $code['allow'] ) {
			self::incrementHit( ! empty( $code['searchbot'] ) );
		} else {
			self::incrementBlockedHit();
		}
	}
}
