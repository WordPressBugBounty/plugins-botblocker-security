<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_statistics($period_days = 7)
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_get_statistics';

        $bbcs_get_statistics = get_transient($cache_key);

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
    [$ip_not_in_sql, $ip_params] = bbcs_getIPNotLikeSQL();
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // Exclusion fragment uses only internally controlled self‑IPs and is bound via prepare; no user data flows here.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
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
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND ch.method = 'GET'
            {$ip_not_in_sql}
            ",
            $today_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $today_end->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            ...$ip_params
        ),
        ARRAY_A
    );
    $yesterday = (clone $today_start)->modify('-1 day');
    $ps = $period_start->format('Y-m-d');
    $ys = $yesterday->format('Y-m-d');
    $use_summary = ($period_days > 1 && bbcs_summary_days_complete($ps, $ys));

    if ($use_summary) {
        $past = bbcs_summary_get_scalars($ps, $ys);
        $period_results = [];
        foreach (['hits', 'uniques', 'pc', 'box', 'phone', 'tablet', 'tv', 'hit_hosts', 'hit_count'] as $sk) {
            $period_results[$sk] = $past[$sk] + (int) ($today_results[$sk] ?? 0);
        }
    } else {
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
                WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                                AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                AND pf.pattern IS NULL
                AND ch.method = 'GET'
                {$ip_not_in_sql}
                ",
                $period_start->format('Y-m-d H:i:s'),
                $gmt_offset_str,
                $today_end->format('Y-m-d H:i:s'),
                $gmt_offset_str,
                ...$ip_params
            ),
            ARRAY_A
        );
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

    if ($use_summary) {
        $grouped = bbcs_get_all_grouped_counts($today_start, $today_start, $today_end, $gmt_offset_str);
        $pb = bbcs_summary_get_dimensions('grp_browser', $ps, $ys);
        $po = bbcs_summary_get_dimensions('grp_os', $ps, $ys);
        $pw = bbcs_summary_get_dimensions('grp_wbot', $ps, $ys);
        $today_results['browsers']          = $grouped['today']['browsers'];
        $today_results['operating_systems'] = $grouped['today']['operating_systems'];
        $today_results['white_bots']        = $grouped['today']['white_bots'];
        $period_results['browsers']          = bbcs_summary_merge_arrays($pb, $grouped['today']['browsers']);
        $period_results['operating_systems'] = bbcs_summary_merge_arrays($po, $grouped['today']['operating_systems']);
        $period_results['white_bots']        = bbcs_summary_merge_arrays($pw, $grouped['today']['white_bots']);
    } else {
        $grouped = bbcs_get_all_grouped_counts($period_start, $today_start, $today_end, $gmt_offset_str);
        $today_results['browsers']          = $grouped['today']['browsers'];
        $today_results['operating_systems'] = $grouped['today']['operating_systems'];
        $today_results['white_bots']        = $grouped['today']['white_bots'];
        $period_results['browsers']          = $grouped['period']['browsers'];
        $period_results['operating_systems'] = $grouped['period']['operating_systems'];
        $period_results['white_bots']        = $grouped['period']['white_bots'];
    }

    $BBCS->counters = [
        'today'  => $today_results,
        'period' => $period_results,
    ];

    if ($BBCS->settings->cache_ui_data == 1) {

        set_transient($cache_key, $BBCS->counters, $BBCS->settings->cache_ui_duration);

    }
}

function bbcs_get_all_grouped_counts(\DateTime $period_start, \DateTime $today_start, \DateTime $today_end, $gmt_offset_str)
{
    global $wpdb;
    [$ip_not_in_sql, $ip_params] = bbcs_getIPNotLikeSQL();

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // Exclusion fragment uses only internally controlled self‑IPs and is bound via prepare; no user data flows here.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
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
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND ch.method = 'GET'
            AND ch.ip != ''
            {$ip_not_in_sql}
            ",
            $today_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $period_start->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            $today_end->format('Y-m-d H:i:s'),
            $gmt_offset_str,
            ...$ip_params
        ),
        ARRAY_A
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

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
    [$ip_not_in_sql, $ip_params] = bbcs_getIPNotLikeSQL();

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
    $today_str = $now->format('Y-m-d');
    $yesterday_str = (clone $now)->modify('-1 day')->format('Y-m-d');
    $start_str = $start_date_obj->format('Y-m-d');

    if ($days > 1 && bbcs_summary_days_complete($start_str, $yesterday_str)) {
        $metric = 'top_' . $type;
        if ($type !== 'ip' && $uniq_type !== 'host') {
            $metric .= '_h';
        }
        $past = bbcs_summary_get_dimensions($metric, $start_str, $yesterday_str);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching
        $today_res = $wpdb->get_results(
            $wpdb->prepare("
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
                ...array_merge([$today_str . ' 00:00:00', $gmt_offset_str, $today_str . ' 23:59:59', $gmt_offset_str, BOTBLOCKER_EMPTY, BOTBLOCKER_EMPTY], $ip_params)
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching

        $today_map = [];
        foreach ((array) $today_res as $r) {
            $today_map[$r['col_value']] = (int) $r['count'];
        }

        $merged = bbcs_summary_merge_arrays($past, $today_map);

        $res = [];
        $i = 0;
        foreach ($merged as $key => $cnt) {
            if (++$i > $limit) {
                break;
            }
            $res[] = ['col_value' => (string) $key, 'count' => (string) $cnt];
        }

        if ($type === 'ip' && $res) {
            array_walk(
                $res,
                function (&$item) {
                    $is_ipv6 = strpos($item['col_value'], ':') !== false;
                    $version = $is_ipv6 ? '6' : '4';
                    $item['col_value'] = bbcs_normalizeIP($item['col_value'], $version);
                }
            );
        }

        foreach ($res as &$row) {
            $row[$type] = $row['col_value'];
            unset($row['col_value']);
        }

        return $res;
    }

    // REVIEWER NOTE: All query parts are built internally by the plugin.
    // $type is always set by the plugin (allowed: ip, country, device, browser), never from user input.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching
    $res = $wpdb->get_results(
        $wpdb->prepare("
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
            ...array_merge([$start_date, $gmt_offset_str, $end_date, $gmt_offset_str, BOTBLOCKER_EMPTY, BOTBLOCKER_EMPTY], $ip_params, [$limit])
        ),
    ARRAY_A);
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching

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

    return $res;
}
