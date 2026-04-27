<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerMuDB
{

    private function increment_blocked_hit(): void
	{
		global $wpdb;
		$gmt_offset = isset($this->settings['admin_gmt_offset']) ? (float) $this->settings['admin_gmt_offset'] : 0;
		$sign = ($gmt_offset >= 0) ? '+' : '-';
		$hours = floor(abs($gmt_offset));
		$minutes = (abs($gmt_offset) - $hours) * 60;
		$gmt_offset_str = sprintf('%s%02d:%02d', $sign, $hours, $minutes);
		try {
			$timezone = new \DateTimeZone($gmt_offset_str);
			$now = new \DateTime('now', $timezone);
			$today_start = (clone $now)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
			$now_local = $now->format('Y-m-d H:i:s');
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query($wpdb->prepare(
				"UPDATE `{$wpdb->bbcs_counters}` SET
					today_hits = IF(last_update IS NOT NULL AND last_update < %s, 0, today_hits),
					today_blocked = IF(last_update IS NOT NULL AND last_update < %s, 1, today_blocked + 1),
					total_blocked = total_blocked + 1,
					last_update = %s
					WHERE id = %d",
				$today_start, $today_start, $now_local, 1
			));
			if ($result === false || $wpdb->rows_affected === 0) {
				if (function_exists('bbcs_ensure_counters_row')) {
					bbcs_ensure_counters_row(true);
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query($wpdb->prepare(
					"UPDATE `{$wpdb->bbcs_counters}` SET today_blocked = 1, total_blocked = 1, last_update = %s WHERE id = %d",
					$now_local, 1
				));
			}
		} catch (\Exception $e) {
		}
	}

}
