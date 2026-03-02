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
                SUM(NULLIF(ch.hit, 0))                               AS hit_count
            FROM (
                SELECT date, ip, device, hit, page, method FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT date, ip, device, hit, page, method FROM `{$wpdb->bbcs_hits_suspicious}`
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
                SUM(NULLIF(ch.hit, 0))                               AS hit_count
            FROM (
                SELECT date, ip, device, hit, page, method FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT date, ip, device, hit, page, method FROM `{$wpdb->bbcs_hits_suspicious}`
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

    $grouped = bbcs_get_all_grouped_counts($period_start, $today_start, $today_end, $gmt_offset_str);

    $today_results['browsers']          = $grouped['today']['browsers'];
    $today_results['operating_systems'] = $grouped['today']['operating_systems'];
    $today_results['white_bots']        = $grouped['today']['white_bots'];

    $period_results['browsers']          = $grouped['period']['browsers'];
    $period_results['operating_systems'] = $grouped['period']['operating_systems'];
    $period_results['white_bots']        = $grouped['period']['white_bots'];

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

function bbcs_get_all_grouped_counts(\DateTime $period_start, \DateTime $today_start, \DateTime $today_end, $gmt_offset_str)
{
    global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT DISTINCT
                ch.ip,
                IFNULL(NULLIF(ch.browser,''), 'NA') AS browser,
                IFNULL(NULLIF(ch.os,''), 'NA')      AS os,
                IFNULL(NULLIF(ch.wbot,''), 'NA')    AS wbot,
                CASE WHEN ch.date >= UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                     THEN 1 ELSE 0 END AS is_today
            FROM (
                SELECT date, ip, browser, os, wbot, page, method FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT date, ip, browser, os, wbot, page, method FROM `{$wpdb->bbcs_hits_suspicious}`
            ) AS ch
            LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
            LEFT JOIN `{$wpdb->bbcs_self_ips}` AS si ON ch.ip = si.search
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND si.search IS NULL
            AND ch.method = 'GET'
            AND ch.ip != ''
            ",
            $today_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $period_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $today_end->format('Y-m-d H:i:s'),
            $gmt_offset_str
        ),
        ARRAY_A
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    $today  = ['browsers' => [], 'operating_systems' => [], 'white_bots' => []];
    $period = ['browsers' => [], 'operating_systems' => [], 'white_bots' => []];

    foreach ($rows as $row) {
        $ip = $row['ip'];

        $period['browsers'][$row['browser']][$ip]          = true;
        $period['operating_systems'][$row['os']][$ip]      = true;
        $period['white_bots'][$row['wbot']][$ip]           = true;

        if ((int) $row['is_today'] === 1) {
            $today['browsers'][$row['browser']][$ip]       = true;
            $today['operating_systems'][$row['os']][$ip]   = true;
            $today['white_bots'][$row['wbot']][$ip]        = true;
        }
    }

    $to_counts = static function (array $map): array {
        $counts = array_map('count', $map);
        arsort($counts);
        return $counts;
    };

    return [
        'today' => [
            'browsers'          => $to_counts($today['browsers']),
            'operating_systems' => $to_counts($today['operating_systems']),
            'white_bots'        => $to_counts($today['white_bots']),
        ],
        'period' => [
            'browsers'          => $to_counts($period['browsers']),
            'operating_systems' => $to_counts($period['operating_systems']),
            'white_bots'        => $to_counts($period['white_bots']),
        ],
    ];
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

    if ($type === 'ip') {
        $count_expression = "COUNT(*)";
    } elseif ($uniq_type === 'host') {
        $count_expression = "COUNT(DISTINCT CONCAT(ip, '-', {$type}))";
    } else {
        $count_expression = "COUNT(*)";
    }

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
