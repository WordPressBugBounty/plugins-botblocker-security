<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerCounters {

	private static $pendingAllowed      = 0;
	private static $pendingBlocked      = 0;
	private static $pendingSearchEngine = 0;
	private static $hookRegistered      = false;

	private static function ensureFlushRegistered(): void {
		if ( self::$hookRegistered ) {
			return;
		}
		self::$hookRegistered = true;
		add_action( 'shutdown', array( __CLASS__, 'flushHits' ), 9999 );
	}

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

	public static function processHit( $reason ): void {
		$reason = is_numeric( $reason ) ? (int) $reason : null;
		$code   = bbcs_codeList( $reason );

		if ( ! $code['count'] ) {
			return;
		}

		self::ensureFlushRegistered();

		if ( $code['allow'] ) {
			self::$pendingAllowed++;
			if ( ! empty( $code['searchbot'] ) ) {
				self::$pendingSearchEngine++;
			}
		} else {
			self::$pendingBlocked++;
		}
	}

	public static function flushHits(): void {
		if ( self::$pendingAllowed === 0 && self::$pendingBlocked === 0 && self::$pendingSearchEngine === 0 ) {
			return;
		}

		$ph  = self::$pendingAllowed;
		$pb  = self::$pendingBlocked;
		$pse = self::$pendingSearchEngine;

		self::$pendingAllowed      = 0;
		self::$pendingBlocked      = 0;
		self::$pendingSearchEngine = 0;

		global $wpdb;
		if ( empty( $wpdb->bbcs_counters ) ) {
			return;
		}

		$BBCS           = BotBlocker::getInstance();
		$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? floatval( $BBCS->settings->admin_gmt_offset ) : 0;
		$timezone       = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
		$now            = new \DateTime( 'now', $timezone );
		$today_start_dt = ( clone $now )->setTime( 0, 0, 0 );
		$today_start    = $today_start_dt->format( 'Y-m-d H:i:s' );
		$now_local      = $now->format( 'Y-m-d H:i:s' );

		$se_sql     = $pse > 0 ? ", search_engine_visits = search_engine_visits + {$pse}" : '';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$result = $wpdb->query(
			"UPDATE `{$wpdb->bbcs_counters}` SET
			today_hits = IF(last_update IS NOT NULL AND last_update < '{$today_start}', {$ph}, today_hits + {$ph}),
			today_blocked = IF(last_update IS NOT NULL AND last_update < '{$today_start}', {$pb}, today_blocked + {$pb}),
			total_hits = total_hits + {$ph},
			total_blocked = total_blocked + {$pb}{$se_sql},
			last_update = '{$now_local}'
			WHERE id = 1"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( $result === false || $wpdb->rows_affected === 0 ) {
			self::ensureRow( true );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "UPDATE `{$wpdb->bbcs_counters}` SET today_hits = {$ph}, today_blocked = {$pb}, total_hits = {$ph}, total_blocked = {$pb}{$se_sql}, last_update = '{$now_local}' WHERE id = 1" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_update_time = $wpdb->get_var( "SELECT last_update FROM `{$wpdb->bbcs_counters}` WHERE id = 1" );
		$is_new_day       = $last_update_time && \DateTime::createFromFormat( 'Y-m-d H:i:s', $last_update_time, $timezone ) < $today_start_dt;
		if ( $is_new_day ) {
			delete_transient( 'bbcs_site_health' );
			delete_transient( 'bbcs_site_health_list' );
		}
	}
}
