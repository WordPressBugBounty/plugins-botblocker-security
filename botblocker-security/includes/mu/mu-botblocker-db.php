<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerMuDB {

	protected function counters_class_available(): bool {
		return class_exists( 'BotBlockerCounters' );
	}

	/**
	 * Creates the singleton counters row when the normal UPDATE matched no row
	 * (fresh install, or an MU-only context where BotBlockerCounters is absent).
	 */
	protected function upsertCountersRow( string $now_local ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$wpdb->bbcs_counters}` (id, today_hits, today_blocked, total_blocked, last_update) VALUES (1, 0, 1, 1, %s)
				ON DUPLICATE KEY UPDATE today_blocked = 1, total_blocked = 1, last_update = %s",
				$now_local,
				$now_local
			)
		);
	}

	protected function increment_blocked_hit(): void {
		global $wpdb;
		$gmt_offset     = isset( $this->settings['admin_gmt_offset'] ) ? (float) $this->settings['admin_gmt_offset'] : 0;
		$sign           = ( $gmt_offset >= 0 ) ? '+' : '-';
		$hours          = floor( abs( $gmt_offset ) );
		$minutes        = ( abs( $gmt_offset ) - $hours ) * 60;
		$gmt_offset_str = sprintf( '%s%02d:%02d', $sign, $hours, $minutes );
		try {
			$timezone    = new \DateTimeZone( $gmt_offset_str );
			$now         = new \DateTime( 'now', $timezone );
			$today_start = ( clone $now )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
			$now_local   = $now->format( 'Y-m-d H:i:s' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE `{$wpdb->bbcs_counters}` SET
					today_hits = IF(last_update IS NOT NULL AND last_update < %s, 0, today_hits),
					today_blocked = IF(last_update IS NOT NULL AND last_update < %s, 1, today_blocked + 1),
					total_blocked = total_blocked + 1,
					last_update = %s
					WHERE id = %d",
					$today_start,
					$today_start,
					$now_local,
					1
				)
			);
			if ( $result === false || $wpdb->rows_affected === 0 ) {
				if ( $this->counters_class_available() ) {
					BotBlockerCounters::ensureRow( true );
				}
				$this->upsertCountersRow( $now_local );
			}
		} catch ( \Exception $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [MU] increment_blocked_hit failed: ' . $e->getMessage() );
			}
		}
	}
}
