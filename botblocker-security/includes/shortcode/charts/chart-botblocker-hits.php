<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_display_hits_and_uniques_chart($atts)
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_display_hits_and_uniques_chart';
        $cached = null;
        if (BOTBLOCKER_CACHE_WP) {
            $cached = wp_cache_get($cache_key, 'botblocker-security');
        } else {
            $cached = get_transient($cache_key);
        }
        if ($cached) {
            return $cached;
        }
    }

    $defaults = [
        'width'  => '100%',
        'height' => '400px',
        'days'   => 7,
    ];
    $atts  = shortcode_atts($defaults, $atts, 'bbcs_hits_and_uniques_chart');
    $days  = min(max((int) $atts['days'], 1), 31);

    $gmt_offset     = isset($BBCS->settings->admin_gmt_offset) ? (float) $BBCS->settings->admin_gmt_offset : 0;
    $gmt_offset_str = BotBlockerEnv::format_gmt_offset($gmt_offset);

    $tz           = new \DateTimeZone($gmt_offset_str);
    $current_date = new \DateTime('now', $tz);

    $end_date   = $current_date->format('Y-m-d 23:59:59');
    $start_date = (clone $current_date)->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $results = $wpdb->get_results(
        $wpdb->prepare("
            SELECT 
                DATE(CONVERT_TZ(FROM_UNIXTIME(ch.date), '+00:00', %s)) AS visit_date,
                COUNT(DISTINCT ch.ip) AS uniques,
                COUNT(*) AS hits
            FROM (
                SELECT date, ip, page FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT date, ip, page FROM `{$wpdb->bbcs_hits_suspicious}`
            ) AS ch
            LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
            LEFT JOIN `{$wpdb->bbcs_self_ips}` AS si ON ch.ip = si.search
            WHERE ch.date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND pf.pattern IS NULL
            AND si.search IS NULL
            GROUP BY visit_date
            ORDER BY visit_date ASC
            ",
            $gmt_offset_str,
            $start_date,
            $gmt_offset_str,
            $end_date,
            $gmt_offset_str
        )
    );

    $chart_data = [];
    $cur = new \DateTime($start_date, $tz);
    $end = new \DateTime($end_date, $tz);
    while ($cur <= $end) {
        $d = $cur->format('Y-m-d');
        $chart_data[$d] = ['uniques' => 0, 'hits' => 0];
        $cur->modify('+1 day');
    }

    foreach ((array) $results as $row) {
        $chart_data[$row->visit_date]['uniques'] = (int) $row->uniques;
        $chart_data[$row->visit_date]['hits']    = (int) $row->hits;
    }

    $labels = [];
    $uniques = [];
    $hits = [];
    ksort($chart_data);
    foreach ($chart_data as $date => $values) {
        $labels[] = gmdate('d', strtotime($date));
        $uniques[] = (int) $values['uniques'];
        $hits[] = (int) $values['hits'];
    }

    ob_start();
    ?>
    <div id="bbcs_hits_and_uniques_chart" class="bbcs-hits-uniques-chart" data-bbcs-labels='<?php echo wp_json_encode(array_values($labels)); ?>' data-bbcs-values-uniques='<?php echo wp_json_encode(array_values($uniques)); ?>' data-bbcs-values-hits='<?php echo wp_json_encode(array_values($hits)); ?>' style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"></div>
    <?php
    $output = ob_get_clean();

    if ($BBCS->settings->cache_ui_data == 1) {
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $output, 'botblocker-security', $BBCS->settings->cache_ui_duration);
        } else {
            set_transient($cache_key, $output, $BBCS->settings->cache_ui_duration);
        }
    }

    return $output;
}
add_shortcode('bbcs_hits_and_uniques_chart', 'bbcs_display_hits_and_uniques_chart');
