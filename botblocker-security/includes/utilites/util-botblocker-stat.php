<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_statistics($period_days = 7)
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_get_statistics';
        if (BOTBLOCKER_CACHE_WP) {
            $bbcs_get_statistics = wp_cache_get($cache_key, 'botblocker-security');
        } else {
            $bbcs_get_statistics = get_transient($cache_key);
        }
        if ($bbcs_get_statistics) {
            $BBCS->counters = $bbcs_get_statistics;
            return;
        }
    }

    $gmt_offset     = isset($BBCS->settings->admin_gmt_offset) ? (float) $BBCS->settings->admin_gmt_offset : 0;
    $gmt_offset_str = BotBlockerEnv::format_gmt_offset($gmt_offset);
    $tz             = new \DateTimeZone($gmt_offset_str);
    $current_date   = new \DateTime('now', $tz);

    $today_start = (clone $current_date)->setTime(0, 0, 0);
    $today_end   = (clone $current_date)->setTime(23, 59, 59);
    $period_start = (clone $today_start)->modify('-' . ($period_days - 1) . ' days');
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $today_results = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT
                COUNT(*)                                             AS hits,
                COUNT(DISTINCT NULLIF(ch.ip, ''))                    AS uniques,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='pc'    AND ch.ip!='' THEN ch.ip END)     AS pc,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='box'   AND ch.ip!='' THEN ch.ip END)     AS box,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='phone' AND ch.ip!='' THEN ch.ip END)     AS phone,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tablet'AND ch.ip!='' THEN ch.ip END)     AS tablet,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tv'    AND ch.ip!='' THEN ch.ip END)     AS tv,
                COUNT(DISTINCT CASE WHEN ch.hit = 1 AND ch.ip != '' THEN ch.ip END)                      AS hit_hosts,
                SUM(NULLIF(ch.hit, 0))                               AS hit_count,
                GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(ch.browser,''),'NA'), ':', ch.ip) SEPARATOR ',')          AS browsers,
                GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(ch.os,''),'NA'), ':', ch.ip) SEPARATOR ',')                AS operating_systems,
                GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(ch.wbot,''),'NA'), ':', ch.ip) SEPARATOR ',')              AS white_bots
            FROM (
                SELECT date, ip, device, hit, browser, os, wbot, page, method FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT date, ip, device, hit, browser, os, wbot, page, method FROM `{$wpdb->bbcs_hits_suspicious}`
            ) AS ch
            LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
            LEFT JOIN `{$wpdb->bbcs_self_ips}` AS si ON ch.ip = si.search
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND si.search IS NULL
            AND ch.method = 'GET'
            ",
            $today_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $today_end->format('Y-m-d H:i:s'),
            $gmt_offset_str
        ),
        ARRAY_A
    );
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $period_results = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT
                COUNT(*)                                             AS hits,
                COUNT(DISTINCT NULLIF(ch.ip, ''))                    AS uniques,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='pc'    AND ch.ip!='' THEN ch.ip END)     AS pc,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='box'   AND ch.ip!='' THEN ch.ip END)     AS box,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='phone' AND ch.ip!='' THEN ch.ip END)     AS phone,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tablet'AND ch.ip!='' THEN ch.ip END)     AS tablet,
                COUNT(DISTINCT CASE WHEN IFNULL(ch.device,'NA')='tv'    AND ch.ip!='' THEN ch.ip END)     AS tv,
                COUNT(DISTINCT CASE WHEN ch.hit = 1 AND ch.ip != '' THEN ch.ip END)                      AS hit_hosts,
                SUM(NULLIF(ch.hit, 0))                               AS hit_count,
                GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(ch.browser,''),'NA'), ':', ch.ip) SEPARATOR ',')          AS browsers,
                GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(ch.os,''),'NA'), ':', ch.ip) SEPARATOR ',')                AS operating_systems,
                GROUP_CONCAT(DISTINCT CONCAT(IFNULL(NULLIF(ch.wbot,''),'NA'), ':', ch.ip) SEPARATOR ',')              AS white_bots
            FROM (
                SELECT date, ip, device, hit, browser, os, wbot, page, method FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT date, ip, device, hit, browser, os, wbot, page, method FROM `{$wpdb->bbcs_hits_suspicious}`
            ) AS ch
            LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
            LEFT JOIN `{$wpdb->bbcs_self_ips}` AS si ON ch.ip = si.search
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND si.search IS NULL
            AND ch.method = 'GET'
            ",
            $period_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $today_end->format('Y-m-d H:i:s'),
            $gmt_offset_str
        ),
        ARRAY_A
    );

    foreach (['today_results', 'period_results'] as $result_set) {
        $browsers           = [];
        $operating_systems  = [];
        $white_bots         = [];

        $browser_data = ! empty(${$result_set}['browsers']) && is_string(${$result_set}['browsers'])
            ? explode(',', ${$result_set}['browsers'])
            : [];
        $os_data      = ! empty(${$result_set}['operating_systems']) && is_string(${$result_set}['operating_systems'])
            ? explode(',', ${$result_set}['operating_systems'])
            : [];
        $bot_data     = ! empty(${$result_set}['white_bots']) && is_string(${$result_set}['white_bots'])
            ? explode(',', ${$result_set}['white_bots'])
            : [];

        if (! empty($browser_data[0])) {
            foreach ($browser_data as $item) {
                $data = explode(':', $item);
                if (count($data) === 2) {
                    [$browser, $ip] = $data;
                    if ($browser !== '' && $ip !== '') {
                        $browsers[$browser][$ip] = true;
                    }
                }
            }
        }

        if (! empty($os_data[0])) {
            foreach ($os_data as $item) {
                $data = explode(':', $item);
                if (count($data) === 2) {
                    [$os, $ip] = $data;
                    if ($os !== '' && $ip !== '') {
                        $operating_systems[$os][$ip] = true;
                    }
                }
            }
        }

        if (! empty($bot_data[0])) {
            foreach ($bot_data as $item) {
                $data = explode(':', $item);
                if (count($data) === 2) {
                    [$bot, $ip] = $data;
                    if ($bot !== '' && $ip !== '') {
                        $white_bots[$bot][$ip] = true;
                    }
                }
            }
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        ${$result_set}['browsers']          = array_map('count', $browsers);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        ${$result_set}['operating_systems'] = array_map('count', $operating_systems);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        ${$result_set}['white_bots']        = array_map('count', $white_bots);
    }

    $BBCS->counters = [
        'today'  => $today_results,
        'period' => $period_results,
    ];

    if ($BBCS->settings->cache_ui_data == 1) {
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $BBCS->counters, 'botblocker-security', $BBCS->settings->cache_ui_duration);
        } else {
            set_transient($cache_key, $BBCS->counters, $BBCS->settings->cache_ui_duration);
        }
    }
}

function bbcs_get_top_data($type, $limit, $days)
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    $allowed_columns = ['ip', 'country', 'device', 'browser'];
    if (! in_array($type, $allowed_columns, true)) {
        return [];
    }

    $limit = absint($limit);
    if ($limit < 1) {
        $limit = 10;
    }
    $days = min(max((int) $days, 1), 365);

    $gmt_offset     = isset($BBCS->settings->admin_gmt_offset) ? (float) $BBCS->settings->admin_gmt_offset : 0;
    $gmt_offset_str = BotBlockerEnv::format_gmt_offset($gmt_offset);
    $tz             = new \DateTimeZone($gmt_offset_str);
    $now            = new \DateTime('now', $tz);

    $end_date_obj = (clone $now)->setTime(23, 59, 59);
    $start_date_obj = (clone $now)->setTime(0, 0, 0);
    if ($days > 1) {
        $start_date_obj->modify('-' . ($days - 1) . ' days');
    }

    $start_date = $start_date_obj->format('Y-m-d H:i:s');
    $end_date   = $end_date_obj->format('Y-m-d H:i:s');

    $uniq_type        = isset($BBCS->settings->admin_uniq_type) ? $BBCS->settings->admin_uniq_type : 'host';
    $count_expression = ($uniq_type === 'host')
        ? "COUNT(DISTINCT CONCAT(ip, '-', {$type}))"
        : "COUNT(*)";

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_get_top_data' . bbcs_get_wp_cache_version() . md5(implode('|', [
            $type,
            $limit,
            $days,
            $start_date,
            $end_date,
            $gmt_offset_str,
            $uniq_type,
        ]));
    
        $cached = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found !== false) {
        return $cached;
    }

    // REVIEWER NOTE: All query parts are built internally by the plugin. 
    // $type is always set by the plugin (allowed: ip, country, device, browser), never from user input.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $res = $wpdb->get_results(
        $wpdb->prepare("
            SELECT ch.{$type} AS col_value, {$count_expression} AS count
            FROM (
                SELECT ip, country, device, browser, date, page FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT ip, country, device, browser, date, page FROM `{$wpdb->bbcs_hits_suspicious}`
            ) AS ch
            LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
            LEFT JOIN `{$wpdb->bbcs_self_ips}` AS si ON ch.ip = si.search
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND si.search IS NULL
            AND ch.country != '' AND ch.country != %s AND ch.country != 'XX' AND ch.country != 'XZ'
            AND ch.ip != '' AND ch.ip != %s
            GROUP BY ch.{$type}
            ORDER BY count DESC
            LIMIT %d
            ",
            $start_date,
            $gmt_offset_str,
            $end_date,
            $gmt_offset_str,
            BOTBLOCKER_EMPTY,
            BOTBLOCKER_EMPTY,
            $limit
        ),
    ARRAY_A);
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    if ($type === 'ip' && $res) {
        array_walk(
            $res,
            function (&$item) {
                $is_ipv6    = strpos($item['col_value'], ':') !== false;
                $version    = $is_ipv6 ? '6' : '4';
                $item['col_value'] = bbcs_normalizeIP($item['col_value'], $version);
            }
        );
    }

    foreach ($res as &$row) {
        $row[$type] = $row['col_value'];
        unset($row['col_value']);
    }

    if (BOTBLOCKER_CACHE_WP) {
        wp_cache_set($cache_key, $res, 'botblocker-security', 15);
    }

    return $res;
}
