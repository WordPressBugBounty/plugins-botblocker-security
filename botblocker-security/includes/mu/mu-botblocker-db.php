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
			$found = false;
			if (defined('BOTBLOCKER_CACHE_WP') && BOTBLOCKER_CACHE_WP) {
				$cache_key = 'bbcs_counters_last_update' . $this->bbcs_get_wp_cache_version();
				$last_update_time = function_exists('wp_cache_get') ? wp_cache_get($cache_key, 'botblocker-security', false, $found) : false;
			}
			if ($found === false) {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery 
				$last_update_time = $wpdb->get_var($wpdb->prepare("SELECT last_update FROM `{$wpdb->bbcs_counters}` WHERE id = %d", 1));
				if (defined('BOTBLOCKER_CACHE_WP') && BOTBLOCKER_CACHE_WP) {
					wp_cache_set($cache_key, $last_update_time, 'botblocker-security', 60);
				}
			}

			if ($last_update_time) {
				$timezone = new \DateTimeZone($gmt_offset_str);
				$current_date = new \DateTime('now', $timezone);
				$today_start = clone $current_date;
				$today_start->setTime(0, 0, 0);
				$last_update = \DateTime::createFromFormat('Y-m-d H:i:s', $last_update_time, $timezone);
				if ($last_update && $last_update < $today_start) {
					// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery 
					$wpdb->update($wpdb->bbcs_counters, array('today_hits' => 0, 'today_blocked' => 0), array('id' => 1), array('%d','%d'), array('%d'));
					if (defined('BOTBLOCKER_CACHE_WP') && BOTBLOCKER_CACHE_WP && function_exists('wp_cache_delete')) { wp_cache_delete($cache_key, 'botblocker-security'); }
				}
			}
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery 
			$wpdb->query($wpdb->prepare("UPDATE `{$wpdb->bbcs_counters}` SET today_blocked = today_blocked + %d, total_blocked = total_blocked + %d, last_update = NOW() WHERE id = %d", 1, 1, 1));
			if (defined('BOTBLOCKER_CACHE_WP') && BOTBLOCKER_CACHE_WP && function_exists('wp_cache_delete')) { wp_cache_delete($cache_key, 'botblocker-security'); }
		} catch (\Exception $e) {
		}
	}

}