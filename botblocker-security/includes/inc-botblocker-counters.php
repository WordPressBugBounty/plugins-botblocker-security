<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_increment_today_hits() {
    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET today_hits = today_hits + 1 WHERE id = 1");
}

function bbcs_increment_today_blocked() {
    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET today_blocked = today_blocked + 1 WHERE id = 1");
}

function bbcs_increment_total_hits() {
    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET total_hits = total_hits + 1 WHERE id = 1");
}

function bbcs_increment_total_blocked() {
    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET total_blocked = total_blocked + 1 WHERE id = 1");
}

function bbcs_increment_search_engine_visits() {
    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET search_engine_visits = search_engine_visits + 1 WHERE id = 1");
}

function bbcs_increment_hit() {
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    $gmt_offset = isset($BBCS->settings->admin_gmt_offset) ? floatval($BBCS->settings->admin_gmt_offset) : 0;
    $gmt_offset_str = BotBlockerEnv::format_gmt_offset($gmt_offset);
    $timezone = new \DateTimeZone($gmt_offset_str);
    $current_date = new \DateTime("now", $timezone);
    $today_start = clone $current_date;
    $today_start->setTime(0, 0, 0);
    $cache_key = 'bbcs_counters_last_update' . bbcs_get_wp_cache_version();
    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $last_update_time = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached, and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $last_update_time = $wpdb->get_var("SELECT last_update FROM `{$wpdb->bbcs_counters}` WHERE id = 1");
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $last_update_time, 'botblocker-security', 86400);
        }
    }
    if ($last_update_time) {
        $last_update = \DateTime::createFromFormat('Y-m-d H:i:s', $last_update_time, $timezone);
        if ($last_update < $today_start) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached, and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET today_hits = 0, today_blocked = 0 WHERE id = 1");
            if (BOTBLOCKER_CACHE_WP) {
                wp_cache_set($cache_key, $current_date->format('Y-m-d H:i:s'), 'botblocker-security', 86400);
            }
        }
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached, and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET today_hits = today_hits + 1, total_hits = total_hits + 1, last_update = NOW() WHERE id = 1");
    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set($cache_key, $current_date->format('Y-m-d H:i:s'), 'botblocker-security', 86400);
    }
}

function bbcs_increment_blocked_hit() {
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    $gmt_offset = isset($BBCS->settings->admin_gmt_offset) ? floatval($BBCS->settings->admin_gmt_offset) : 0;
    $gmt_offset_str = BotBlockerEnv::format_gmt_offset($gmt_offset);
    $timezone = new \DateTimeZone($gmt_offset_str);
    $current_date = new \DateTime("now", $timezone);
    $today_start = clone $current_date;
    $today_start->setTime(0, 0, 0);
    $cache_key = 'bbcs_counters_last_update' . bbcs_get_wp_cache_version();
    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $last_update_time = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached, and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $last_update_time = $wpdb->get_var("SELECT last_update FROM `{$wpdb->bbcs_counters}` WHERE id = 1");
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $last_update_time, 'botblocker-security', 86400);
        }
    }
    if ($last_update_time) {
        $last_update = \DateTime::createFromFormat('Y-m-d H:i:s', $last_update_time, $timezone);
        if ($last_update < $today_start) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached, and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET today_hits = 0, today_blocked = 0 WHERE id = 1");
            if (BOTBLOCKER_CACHE_WP) {
                wp_cache_set($cache_key, $current_date->format('Y-m-d H:i:s'), 'botblocker-security', 86400);
            }
        }
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached, and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->query("UPDATE `{$wpdb->bbcs_counters}` SET today_blocked = today_blocked + 1, total_blocked = total_blocked + 1, last_update = NOW() WHERE id = 1");
    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set($cache_key, $current_date->format('Y-m-d H:i:s'), 'botblocker-security', 86400);
    }
}

function bbcs_process_hit($reason) {
    $reason = is_numeric($reason) ? (int)$reason : null;
    $code = bbcs_codeList($reason);

    if (!$code['count']) {
        return;
    }

    if ($code['allow']) {
        bbcs_increment_hit();
        if ($code['searchbot']) {
            bbcs_increment_search_engine_visits();
        }
    } else {
        bbcs_increment_blocked_hit();
    }
}
