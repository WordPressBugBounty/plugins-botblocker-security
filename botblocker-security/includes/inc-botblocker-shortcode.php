<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-header.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-tasks.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-health.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-health-full.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-tooltips.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-sidebar.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-rules.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-cloud-api.php';

include_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-daily.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-hits.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-map.php';
include_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-stat.php';

function bbcs_render_top_list($type, $limit, $days)
{
    $data = bbcs_get_top_data($type, $limit, $days);

    if (empty($data)) {
        return '<p>' . esc_html_e('No data available.', 'botblocker-security') .'</p>';
    }

    $output = '<ul class="bbcs-top-ul">';
    foreach ($data as $item) {
        $key = esc_html($item[$type]);
        $flag = strtolower($key);
        $count = esc_html($item['count']);
        if ($type == 'country') {
            $output .= "<li class='bbcs-top-li'>
                            <span class='bbcs-top-span'>
                                <div class='bbcs-flag-wrapper'>
                                    <div class='flag flag-$flag bbcs-flag-scale'></div>
                                </div>
                                <div class='ms-2 text-truncate' style='max-width:100%; display:inline-block; vertical-align:middle;' data-bs-toggle='tooltip' title=\"{$key} - " . bbcs_get_country_by_code($key) . "\">
                                    {$key} - " . bbcs_get_country_by_code($key) . " (<b>{$count}</b>)
                                </div>
                            </span>
                        </li>";
        } else {
            $output .= "<li class='bbcs-top-li'><span class='bbcs-top-span text-truncate' style='max-width:100%; display:inline-block; vertical-align:middle;' data-bs-toggle='tooltip' title=\"{$key}\">{$key} (<b>{$count}</b>)</span></li>";
        }
    }
    $output .= '</ul>';

    return $output;
}

add_shortcode('bbcs_top_ips', function ($atts) {
    $BBCS = BotBlocker::getInstance();
    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_top_ips';
        $bbcs_top_ips = get_transient($cache_key);
        if ($bbcs_top_ips) {
            return $bbcs_top_ips;
        }
    }
    $atts = shortcode_atts(['limit' => 5, 'days' => 7], $atts, 'bbcs_top_ips');
    // $html = '<div class="bbcs-statistics-chart-title-div-start"><span class="bbcs-statistics-chart-title">Top-' . intval($atts['limit']) . ' IPs</span></div>';
    // translators: %d is the number of top IP addresses to display.
    $bbcs_ips_title = sprintf( __('Top-%d IPs', 'botblocker-security'), intval($atts['limit']) );
    $html = '<div class="bbcs-statistics-chart-title-div-start">
                <span class="bbcs-statistics-chart-title">' . esc_html( $bbcs_ips_title ) . '</span>
            </div>';
    $output = $html . bbcs_render_top_list('ip', intval($atts['limit']), intval($atts['days']));
    if ($BBCS->settings->cache_ui_data == 1) {
        set_transient($cache_key, $output, $BBCS->settings->cache_ui_duration);
    }
    return $output;
});

add_shortcode('bbcs_top_countries', function ($atts) {
    $BBCS = BotBlocker::getInstance();
    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_top_countries';
        $bbcs_top_countries = get_transient($cache_key);
        if ($bbcs_top_countries) {
            return $bbcs_top_countries;
        }
    }
    $atts = shortcode_atts(['limit' => 5, 'days' => 7], $atts, 'bbcs_top_countries');
    // $html = '<div class="bbcs-statistics-chart-title-div-start"><span class="bbcs-statistics-chart-title">Top-' . intval($atts['limit']) . ' countries</span></div>';
    // translators: %d is the number of top countries to display.
    $bbcs_countries_title = sprintf( __('Top-%d countries', 'botblocker-security'), intval($atts['limit']) );
    $html = '<div class="bbcs-statistics-chart-title-div-start">
                <span class="bbcs-statistics-chart-title">' . esc_html( $bbcs_countries_title ) . '</span>
            </div>';
    $output = $html . bbcs_render_top_list('country', intval($atts['limit']), intval($atts['days']));
    if ($BBCS->settings->cache_ui_data == 1) {
        set_transient($cache_key, $output, $BBCS->settings->cache_ui_duration);
    }
    return $output;
});

add_shortcode('bbcs_top_devices', function ($atts) {
    $BBCS = BotBlocker::getInstance();
    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_top_devices';
        $bbcs_top_devices = get_transient($cache_key);
        if ($bbcs_top_devices) {
            return $bbcs_top_devices;
        }
    }
    $atts = shortcode_atts(['limit' => 5, 'days' => 7], $atts, 'bbcs_top_devices');
    // $html = '<div class="bbcs-statistics-chart-title-div-start"><span class="bbcs-statistics-chart-title">Top-' . intval($atts['limit']) . ' devices</span></div>';
    // translators: %d is the number of top devices to display.
    $bbcs_devices_title = sprintf( __('Top-%d devices', 'botblocker-security'), intval($atts['limit']) );
    $html = '<div class="bbcs-statistics-chart-title-div-start">
                <span class="bbcs-statistics-chart-title">' . esc_html( $bbcs_devices_title ) . '</span>
            </div>';
    $output =  $html . bbcs_render_top_list('device', intval($atts['limit']), intval($atts['days']));
    if ($BBCS->settings->cache_ui_data == 1) {
        set_transient($cache_key, $output, $BBCS->settings->cache_ui_duration);
    }
    return $output;
});

add_shortcode('bbcs_top_browsers', function ($atts) {
    $BBCS = BotBlocker::getInstance();
    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_top_browsers';
        $bbcs_top_browsers = get_transient($cache_key);
        if ($bbcs_top_browsers) {
            return $bbcs_top_browsers;
        }
    }
    $atts = shortcode_atts(['limit' => 5, 'days' => 7], $atts, 'bbcs_top_browsers');
    // $html = '<div class="bbcs-statistics-chart-title-div-start"><span class="bbcs-statistics-chart-title">Top-' . intval($atts['limit']) . ' browsers</span></div>';
    // translators: %d is the number of top browsers to display.
    $bbcs_browsers_title = sprintf( __('Top-%d browsers', 'botblocker-security'), intval($atts['limit']) );
    $html = '<div class="bbcs-statistics-chart-title-div-start">
                <span class="bbcs-statistics-chart-title">' . esc_html( $bbcs_browsers_title ) . '</span>
            </div>';    
    $output = $html . bbcs_render_top_list('browser', intval($atts['limit']), intval($atts['days']));
    if ($BBCS->settings->cache_ui_data == 1) {
        set_transient($cache_key, $output, $BBCS->settings->cache_ui_duration);
    }
    return $output;
});


function bbcs_latest_hits_shortcode()
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_latest_hits_shortcode';
        $bbcs_latest_hits_shortcode = null;
        if (BOTBLOCKER_CACHE_WP) {
            $bbcs_latest_hits_shortcode = wp_cache_get($cache_key, 'botblocker-security');
        } else {
            $bbcs_latest_hits_shortcode = get_transient($cache_key);
        }
        if ($bbcs_latest_hits_shortcode) {
            return $bbcs_latest_hits_shortcode;
        }
    }
    $gmt_offset = isset($BBCS->settings->admin_gmt_offset) ? floatval($BBCS->settings->admin_gmt_offset) : 0;

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $results = $wpdb->get_results("
        SELECT ch.date, ch.ip, ch.country, ch.lang, ch.device, ch.os 
        FROM (
            SELECT date, ip, country, lang, device, os, page FROM `{$wpdb->bbcs_hits}`
            UNION ALL
            SELECT date, ip, country, lang, device, os, page FROM `{$wpdb->bbcs_hits_suspicious}`
        ) AS ch
        LEFT JOIN `{$wpdb->bbcs_page_filters}` AS pf ON ch.page LIKE pf.pattern
        LEFT JOIN `{$wpdb->bbcs_self_ips}` AS si ON ch.ip = si.search
        WHERE pf.pattern IS NULL AND si.search IS NULL
        ORDER BY ch.date DESC 
        LIMIT 10
    ", ARRAY_A);

    if (empty($results)) {
        return '<p>' . esc_html_e('No data available.', 'botblocker-security') .'</p>';
    }

    ob_start();
    ?>
    <table class="bbcs-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Date/Time', 'botblocker-security') ?></th>
                <th><?php esc_html_e('IP Address', 'botblocker-security') ?></th>
                <th><?php esc_html_e('Country', 'botblocker-security') ?></th>
                <th><?php esc_html_e('Language', 'botblocker-security') ?></th>
                <th><?php esc_html_e('Device', 'botblocker-security') ?></th>
                <th><?php esc_html_e('OS', 'botblocker-security') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td>
                        <?php
                        $datetime = new \DateTime("@{$row['date']}", new \DateTimeZone('UTC'));
                        if ($gmt_offset != 0) {
                            $hours = floor(abs($gmt_offset));
                            $minutes = (abs($gmt_offset) * 60) % 60;
                            $interval_spec = 'PT' . $hours . 'H' . $minutes . 'M';
                            $interval = new \DateInterval($interval_spec);
                            if ($gmt_offset > 0) {
                                $datetime->add($interval);
                            } else {
                                $datetime->sub($interval);
                            }
                        }
                        echo esc_html($datetime->format('Y-m-d H:i:s'));
                        ?>
                    </td>
                    <td><?php echo esc_html($row['ip']); ?></td>
                    <td>
                        <span class="bbcs-top-span">
                            <div class="bbcs-flag-wrapper">
                                <div class="flag flag-<?php echo esc_attr(strtolower($row['country'])); ?> bbcs-flag-scale"></div>
                            </div>
                            <div class="ms-2"><?php echo esc_html($row['country']); ?></div>
                        </span>
                    </td>
                    <td><?php echo esc_html($row['lang']); ?></td>
                    <td><?php echo esc_html($row['device']); ?></td>
                    <td><?php echo esc_html($row['os']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
add_shortcode('bbcs_latest_hits', 'bbcs_latest_hits_shortcode');
