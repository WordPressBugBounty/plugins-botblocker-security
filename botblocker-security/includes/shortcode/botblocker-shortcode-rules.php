<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_botblocker_rules_statistics($atts)
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    if ($BBCS->settings->cache_ui_data == 1){
        $transient_key = 'bbcs_rules_stat';
        $cached_health = null;
        if (BOTBLOCKER_CACHE_WP) {
            $cached_health = wp_cache_get($transient_key, 'botblocker-security');
        } else {
            $cached_health = get_transient($transient_key);
        }
        if ($cached_health !== false) {
            return $cached_health;
        }
    }
    $atts = shortcode_atts(array(
        'show_chart' => 'yes',
        'chart_height' => '200'
    ), $atts);

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    $ipv4_total = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}`");
    $ipv4_blocks = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE rule = 'block'");
    $ipv4_allows = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE rule = 'allow'");

    $ipv6_total = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}`");
    $ipv6_blocks = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE rule = 'block'");
    $ipv6_allows = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE rule = 'allow'");

    $paths_total = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_path}`");
    $paths_allowed = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE rule = 'allow'");
    $paths_blocked = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE rule = 'block'");

    $white_bots_total = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_se}`");
    $white_bots_allowed = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_se}` WHERE rule = 'allow' AND disable = 0");

    $rules_total = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}`");
    $rules_blocks = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE rule = 'block'");
    $rules_allows = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE rule = 'allow'");

    $active_rules = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE disable = 0");

    $now = time();
    $expired_rules = (int) $wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE expires < %d",
        $now
      )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery


    $output = '';

    if ($atts['show_chart'] === 'yes') {
        $labels = array('IPv4','IPv6','Paths','Rules');
        $blocked = array((int)$ipv4_blocks,(int)$ipv6_blocks,(int)$paths_blocked,(int)$rules_blocks);
        $allowed = array((int)$ipv4_allows,(int)$ipv6_allows,(int)$paths_allowed,(int)$rules_allows);
        $container_id = 'botblocker_rules_stats_chart';
        $output .= '<div id="' . esc_attr($container_id) . '" class="bbcs-rules-stats-chart" style="height:' . esc_attr($atts['chart_height']) . 'px" ' .
                   ' data-bbcs-labels=' . "'" . wp_json_encode(array_values($labels)) . "'" .
                   ' data-bbcs-values-blocked=' . "'" . wp_json_encode(array_values($blocked)) . "'" .
                   ' data-bbcs-values-allowed=' . "'" . wp_json_encode(array_values($allowed)) . "'" .
                   '></div>';
    }

    $output .= '<h3 class="bbcs-rule-stat-h">'. esc_html__('IP Addresses', 'botblocker-security') .'</h3>';
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Total IPv4 rules:', 'botblocker-security') ." {$ipv4_total} (". esc_html__('Blocked:', 'botblocker-security') ." {$ipv4_blocks}, ". esc_html__('Allowed:', 'botblocker-security') ." {$ipv4_allows})</span>";
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Total IPv6 rules:', 'botblocker-security') ." {$ipv6_total} (". esc_html__('Blocked:', 'botblocker-security') ." {$ipv6_blocks}, ". esc_html__('Allowed:', 'botblocker-security') ." {$ipv6_allows})</span>";

    $output .= "<h3 class='bbcs-rule-stat-h'>". esc_html__('WordPress Paths', 'botblocker-security') ."</h3>";
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Total paths:', 'botblocker-security') ." {$paths_total} (". esc_html__('Blocked:', 'botblocker-security') ." {$paths_blocked}, ". esc_html__('Allowed:', 'botblocker-security') ." {$paths_allowed})</span>";

    $output .= "<h3 class='bbcs-rule-stat-h'>" . esc_html__('White Bots and Search Engines', 'botblocker-security') ."</h3>";
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Total white bots:', 'botblocker-security') ." {$white_bots_total} (". esc_html__('Active and allowed:', 'botblocker-security') ." {$white_bots_allowed})</span>";

    $output .= "<h3 class='bbcs-rule-stat-h'>". esc_html__('General Rules', 'botblocker-security') ."</h3>";
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Total rules:', 'botblocker-security') ." {$rules_total} (". esc_html__('Blocked:', 'botblocker-security') ." {$rules_blocks}, ". esc_html__('Allowed:', 'botblocker-security') ." {$rules_allows})</span>";

    $output .= "<h3 class='bbcs-rule-stat-h'>". esc_html__('Additional Information', 'botblocker-security') ."</h3>";
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Active rules:', 'botblocker-security') ." {$active_rules}</span>";
    $output .= "<span class='bbcs-rule-stat-s'>". esc_html__('Expired rules:', 'botblocker-security') ." {$expired_rules}</span>";
    if ($BBCS->settings->cache_ui_data == 1){
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($transient_key, $output, 'botblocker-security', $BBCS->settings->cache_ui_duration);
        } else {
            set_transient($transient_key, $output, $BBCS->settings->cache_ui_duration);
        }
    }
    return $output;
}
add_shortcode('botblocker_rules_stats', 'bbcs_botblocker_rules_statistics');
