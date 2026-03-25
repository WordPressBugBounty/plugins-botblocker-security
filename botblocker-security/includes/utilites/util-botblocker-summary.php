<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_summary_table_ready() {
    static $ready = [];
    $blog_id = get_current_blog_id();
    if (isset($ready[$blog_id])) {
        return $ready[$blog_id];
    }
    global $wpdb;
    if ( empty( $wpdb->bbcs_daily_summary ) ) {
        $ready[$blog_id] = false;
        return false;
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $ready[$blog_id] = (bool) $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->bbcs_daily_summary )
    );
    return $ready[$blog_id];
}

function bbcs_summary_days_complete( string $start, string $end ): bool {
    if ( ! bbcs_summary_table_ready() ) {
        return false;
    }
    global $wpdb;
    $d1 = new \DateTime( $start );
    $d2 = new \DateTime( $end );
    if ( $d1 > $d2 ) {
        return false;
    }
    $expected = (int) $d1->diff( $d2 )->days + 1;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $found = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_daily_summary}`
             WHERE metric = '_complete' AND dim_key = '' AND val = 1
             AND date_key BETWEEN %s AND %s",
            $start,
            $end
        )
    );
    return $found >= $expected;
}

function bbcs_summary_get_scalars( string $start, string $end ): array {
    global $wpdb;
    $keys = [ 'hits', 'uniques', 'pc', 'box', 'phone', 'tablet', 'tv', 'hit_hosts', 'hit_count', 'chart_hits', 'chart_uniques' ];
    $placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT metric, SUM(val) AS total
             FROM `{$wpdb->bbcs_daily_summary}`
             WHERE date_key BETWEEN %s AND %s
             AND metric IN ($placeholders) AND dim_key = ''
             GROUP BY metric",
            $start,
            $end,
            ...$keys
        ),
        ARRAY_A
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    $out = array_fill_keys( $keys, 0 );
    foreach ( (array) $rows as $r ) {
        $out[ $r['metric'] ] = (int) $r['total'];
    }
    return $out;
}

function bbcs_summary_get_dimensions( string $metric, string $start, string $end, int $limit = 0 ): array {
    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT dim_key, SUM(val) AS total
         FROM `{$wpdb->bbcs_daily_summary}`
         WHERE metric = %s AND date_key BETWEEN %s AND %s AND dim_key != ''
         GROUP BY dim_key ORDER BY total DESC",
        $metric,
        $start,
        $end
    );
    if ( $limit > 0 ) {
        $sql .= $wpdb->prepare( ' LIMIT %d', $limit );
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
    $rows = $wpdb->get_results( $sql, ARRAY_A );
    $out = [];
    foreach ( (array) $rows as $r ) {
        $out[ $r['dim_key'] ] = (int) $r['total'];
    }
    return $out;
}

function bbcs_summary_get_per_day( string $metric, string $start, string $end ): array {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT date_key, val
             FROM `{$wpdb->bbcs_daily_summary}`
             WHERE metric = %s AND dim_key = '' AND date_key BETWEEN %s AND %s
             ORDER BY date_key ASC",
            $metric,
            $start,
            $end
        ),
        ARRAY_A
    );
    $out = [];
    foreach ( (array) $rows as $r ) {
        $out[ $r['date_key'] ] = (int) $r['val'];
    }
    return $out;
}

function bbcs_invalidate_daily_summary() {
    if ( ! bbcs_summary_table_ready() ) {
        return;
    }
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "DELETE FROM `{$wpdb->bbcs_daily_summary}` WHERE metric = '_complete'" );
}

function bbcs_truncate_daily_summary() {
    if ( ! bbcs_summary_table_ready() ) {
        return;
    }
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "TRUNCATE TABLE `{$wpdb->bbcs_daily_summary}`" );
}

function bbcs_clean_old_summary_data( int $store_period ) {
    if ( ! bbcs_summary_table_ready() ) {
        return;
    }
    global $wpdb;
    $cutoff = gmdate( 'Y-m-d', strtotime( '-' . $store_period . ' days' ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `{$wpdb->bbcs_daily_summary}` WHERE date_key < %s",
            $cutoff
        )
    );
}

function bbcs_aggregate_day( string $date_key ): bool {
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    $gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
    $gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );
    $tz             = new \DateTimeZone( $gmt_offset_str );

    $day_start = $date_key . ' 00:00:00';
    $day_end   = $date_key . ' 23:59:59';

    [ $ip_frag, $ip_vals ] = bbcs_getIPNotLikeSQL();

    $base_from = "FROM (
        SELECT date, ip, device, hit, page, method, country, browser, os, wbot FROM `{$wpdb->bbcs_hits}`
        UNION ALL
        SELECT date, ip, device, hit, page, method, country, browser, os, wbot FROM `{$wpdb->bbcs_hits_suspicious}`
    ) AS ch
    LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern";

    $base_where = "WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                   AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                   AND pf.pattern IS NULL {$ip_frag}";

    $date_params = [ $day_start, $gmt_offset_str, $day_end, $gmt_offset_str ];
    $base_params = array_merge( $date_params, $ip_vals );

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

    $stats = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT
                COUNT(*) AS chart_hits,
                COUNT(DISTINCT NULLIF(ch.ip, '')) AS chart_uniques,
                SUM(CASE WHEN ch.method = 'GET' THEN 1 ELSE 0 END) AS hits,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' THEN NULLIF(ch.ip, '') END) AS uniques,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='pc' AND ch.ip!='' THEN ch.ip END) AS pc,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='box' AND ch.ip!='' THEN ch.ip END) AS box,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='phone' AND ch.ip!='' THEN ch.ip END) AS phone,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='tablet' AND ch.ip!='' THEN ch.ip END) AS tablet,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND IFNULL(ch.device,'NA')='tv' AND ch.ip!='' THEN ch.ip END) AS tv,
                COUNT(DISTINCT CASE WHEN ch.method = 'GET' AND ch.hit = 1 AND ch.ip != '' THEN ch.ip END) AS hit_hosts,
                SUM(CASE WHEN ch.method = 'GET' THEN NULLIF(ch.hit, 0) ELSE NULL END) AS hit_count
            {$base_from} {$base_where}",
            ...$base_params
        ),
        ARRAY_A
    );

    $rows = [];
    foreach ( [ 'hits', 'uniques', 'pc', 'box', 'phone', 'tablet', 'tv', 'hit_hosts', 'hit_count', 'chart_hits', 'chart_uniques' ] as $m ) {
        $rows[] = [ $date_key, $m, '', (int) ( $stats[ $m ] ?? 0 ) ];
    }

    $grp_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                IFNULL(NULLIF(ch.browser,''), 'NA') AS browser,
                IFNULL(NULLIF(ch.os,''), 'NA') AS os,
                IFNULL(NULLIF(ch.wbot,''), 'NA') AS wbot,
                ch.ip
            {$base_from} {$base_where} AND ch.method = 'GET' AND ch.ip != ''
            GROUP BY ch.ip, browser, os, wbot",
            ...$base_params
        ),
        ARRAY_A
    );

    $browsers = [];
    $oses     = [];
    $wbots    = [];
    foreach ( (array) $grp_rows as $r ) {
        $browsers[ $r['browser'] ][ $r['ip'] ] = 1;
        $oses[ $r['os'] ][ $r['ip'] ]          = 1;
        $wbots[ $r['wbot'] ][ $r['ip'] ]       = 1;
    }
    foreach ( $browsers as $k => $ips ) {
        $rows[] = [ $date_key, 'grp_browser', $k, count( $ips ) ];
    }
    foreach ( $oses as $k => $ips ) {
        $rows[] = [ $date_key, 'grp_os', $k, count( $ips ) ];
    }
    foreach ( $wbots as $k => $ips ) {
        $rows[] = [ $date_key, 'grp_wbot', $k, count( $ips ) ];
    }

    $empty_val = defined( 'BOTBLOCKER_EMPTY' ) ? BOTBLOCKER_EMPTY : '-';
    $top_params = array_merge( $base_params, [ $empty_val, $empty_val ] );

    $top_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ch.ip, ch.country, ch.device, ch.browser
            {$base_from} {$base_where}
            AND ch.ip != '' AND ch.ip != %s
            AND ch.country != '' AND ch.country != %s AND ch.country != 'XX' AND ch.country != 'XZ'",
            ...$top_params
        ),
        ARRAY_A
    );

    $t_ip       = [];
    $t_country  = [];
    $t_device   = [];
    $t_browser  = [];
    $t_country_h = [];
    $t_device_h  = [];
    $t_browser_h = [];

    foreach ( (array) $top_rows as $r ) {
        $ip = $r['ip'];
        if ( ! isset( $t_ip[ $ip ] ) ) {
            $t_ip[ $ip ] = 0;
        }
        ++$t_ip[ $ip ];

        $t_country[ $r['country'] ][ $ip ] = 1;
        $t_device[ $r['device'] ][ $ip ]   = 1;
        $t_browser[ $r['browser'] ][ $ip ] = 1;

        if ( ! isset( $t_country_h[ $r['country'] ] ) ) {
            $t_country_h[ $r['country'] ] = 0;
        }
        ++$t_country_h[ $r['country'] ];

        if ( ! isset( $t_device_h[ $r['device'] ] ) ) {
            $t_device_h[ $r['device'] ] = 0;
        }
        ++$t_device_h[ $r['device'] ];

        if ( ! isset( $t_browser_h[ $r['browser'] ] ) ) {
            $t_browser_h[ $r['browser'] ] = 0;
        }
        ++$t_browser_h[ $r['browser'] ];
    }

    arsort( $t_ip );
    $top_ip_limit = 200;
    $i = 0;
    foreach ( $t_ip as $ip => $cnt ) {
        if ( ++$i > $top_ip_limit ) {
            break;
        }
        $rows[] = [ $date_key, 'top_ip', $ip, $cnt ];
    }

    foreach ( $t_country as $k => $ips ) {
        $rows[] = [ $date_key, 'top_country', $k, count( $ips ) ];
    }
    foreach ( $t_country_h as $k => $cnt ) {
        $rows[] = [ $date_key, 'top_country_h', $k, $cnt ];
    }
    foreach ( $t_device as $k => $ips ) {
        $rows[] = [ $date_key, 'top_device', $k, count( $ips ) ];
    }
    foreach ( $t_device_h as $k => $cnt ) {
        $rows[] = [ $date_key, 'top_device_h', $k, $cnt ];
    }
    foreach ( $t_browser as $k => $ips ) {
        $rows[] = [ $date_key, 'top_browser', $k, count( $ips ) ];
    }
    foreach ( $t_browser_h as $k => $cnt ) {
        $rows[] = [ $date_key, 'top_browser_h', $k, $cnt ];
    }

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete( $wpdb->bbcs_daily_summary, [ 'date_key' => $date_key ], [ '%s' ] );

    $table = $wpdb->bbcs_daily_summary;
    $batch = [];
    foreach ( $rows as $r ) {
        $batch[] = $wpdb->prepare( '(%s,%s,%s,%d)', $r[0], $r[1], $r[2], $r[3] );
        if ( count( $batch ) >= 100 ) {
            $vals = implode( ',', $batch );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "INSERT INTO `{$table}` (date_key, metric, dim_key, val) VALUES {$vals} ON DUPLICATE KEY UPDATE val = VALUES(val)" );
            $batch = [];
        }
    }
    if ( $batch ) {
        $vals = implode( ',', $batch );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "INSERT INTO `{$table}` (date_key, metric, dim_key, val) VALUES {$vals} ON DUPLICATE KEY UPDATE val = VALUES(val)" );
    }

    $today = ( new \DateTime( 'now', $tz ) )->format( 'Y-m-d' );
    $complete = ( $date_key < $today ) ? 1 : 0;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->replace(
        $table,
        [ 'date_key' => $date_key, 'metric' => '_complete', 'dim_key' => '', 'val' => $complete ],
        [ '%s', '%s', '%s', '%d' ]
    );

    return true;
}

function bbcs_summary_cron_handler() {
    if ( ! bbcs_summary_table_ready() ) {
        return;
    }
    $BBCS       = BotBlocker::getInstance();
    $gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
    $tz         = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
    $today      = new \DateTime( 'now', $tz );
    $period     = isset( $BBCS->settings->admin_report_period ) ? max( 1, (int) $BBCS->settings->admin_report_period ) : 7;

    $cursor = ( clone $today )->modify( '-' . ( $period - 1 ) . ' days' );
    $processed = 0;
    $max_per_run = 3;

    while ( $cursor <= $today && $processed < $max_per_run ) {
        $dk = $cursor->format( 'Y-m-d' );
        $is_today = ( $dk === $today->format( 'Y-m-d' ) );

        if ( ! $is_today && bbcs_summary_days_complete( $dk, $dk ) ) {
            $cursor->modify( '+1 day' );
            continue;
        }

        bbcs_aggregate_day( $dk );
        ++$processed;
        $cursor->modify( '+1 day' );
    }
}

function bbcs_summary_backfill_handler() {
    if ( ! bbcs_summary_table_ready() ) {
        return;
    }
    $BBCS       = BotBlocker::getInstance();
    $gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
    $tz         = new \DateTimeZone( BotBlockerEnv::format_gmt_offset( $gmt_offset ) );
    $today      = new \DateTime( 'now', $tz );
    $period     = isset( $BBCS->settings->admin_report_period ) ? max( 1, (int) $BBCS->settings->admin_report_period ) : 7;

    $start = ( clone $today )->modify( '-' . ( $period - 1 ) . ' days' );
    $all_done = true;
    $processed = 0;
    $max_per_run = 2;

    $cursor = clone $start;
    while ( $cursor <= $today ) {
        $dk = $cursor->format( 'Y-m-d' );
        if ( ! bbcs_summary_days_complete( $dk, $dk ) || $dk === $today->format( 'Y-m-d' ) ) {
            if ( $processed < $max_per_run ) {
                bbcs_aggregate_day( $dk );
                ++$processed;
            }
            if ( $dk !== $today->format( 'Y-m-d' ) ) {
                $all_done = false;
            }
        }
        $cursor->modify( '+1 day' );
    }

    if ( ! $all_done && ! wp_next_scheduled( 'bbcs_summary_backfill' ) ) {
        wp_schedule_single_event( time() + 120, 'bbcs_summary_backfill' );
    }
}

function bbcs_summary_merge_arrays( array $past, array $today ): array {
    $merged = $past;
    foreach ( $today as $k => $v ) {
        $merged[ $k ] = ( $merged[ $k ] ?? 0 ) + $v;
    }
    arsort( $merged );
    return $merged;
}
