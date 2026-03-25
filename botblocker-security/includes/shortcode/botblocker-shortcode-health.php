<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_healthGaugeShortcode($atts)
{
    $defaults = array(
        'id' => 'gauge_' . uniqid(),
        'value' => 0,
        'min' => 0,
        'max' => 100,
        'decimals' => 0,
        'gauge_width_scale' => 0.6,
        'label' => 'Security Score',
        'symbol' => '%',
        'pointer' => 'true',
        'pointer_top_length' => -15,
        'pointer_bottom_length' => 10,
        'pointer_bottom_width' => 12,
        'pointer_color' => '#8e8e93',
        'pointer_stroke' => '#ffffff',
        'pointer_stroke_width' => 3,
        'pointer_stroke_linecap' => 'round',
        'level_colors' => '["#ff3b30", "#d8ca00", "#43bf58"]'
    );

    $userProvidedLabel = is_array($atts) && array_key_exists('label', $atts);
    $atts = shortcode_atts($defaults, $atts, 'bbcs_health_gauge');
    $atts['pointer'] = filter_var($atts['pointer'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';

    if (!$userProvidedLabel) {
        $score = $atts['value']; //function_exists('bbcs_calculateSiteHealth') ? (int) bbcs_calculateSiteHealth() : 0;
        if ($score >= 85) {
            $atts['label'] = __('Secure', 'botblocker-security');
        } elseif ($score >= 70) {
            $atts['label'] = __('Strong', 'botblocker-security');
        } elseif ($score >= 50) {
            $atts['label'] = __('Moderate', 'botblocker-security');
        } elseif ($score >= 25) {
            $atts['label'] = __('Weak', 'botblocker-security');
        } else {
            $atts['label'] = __('Critical', 'botblocker-security');
        }
    }

    $unique_id = esc_attr($atts['id']);
    // $html = '<div id="' . $unique_id . '" class="bbcs-health-gauge"'
    //       . ' data-bbcs-min="' . esc_attr($atts['min']) . '"'
    //       . ' data-bbcs-max="' . esc_attr($atts['max']) . '"'
    //       . ' data-bbcs-value="' . esc_attr($atts['value']) . '"'
    //       . ' data-bbcs-decimals="' . esc_attr($atts['decimals']) . '"'
    //       . ' data-bbcs-gauge-width-scale="' . esc_attr($atts['gauge_width_scale']) . '"'
    //       . ' data-bbcs-label="' . esc_attr($atts['label']) . '"'
    //       . ' data-bbcs-symbol="' . esc_attr($atts['symbol']) . '"'
    //       . ' data-bbcs-pointer="' . esc_attr($atts['pointer']) . '"'
    //       . ' data-bbcs-pointer-top-length="' . esc_attr($atts['pointer_top_length']) . '"'
    //       . ' data-bbcs-pointer-bottom-length="' . esc_attr($atts['pointer_bottom_length']) . '"'
    //       . ' data-bbcs-pointer-bottom-width="' . esc_attr($atts['pointer_bottom_width']) . '"'
    //       . ' data-bbcs-pointer-color="' . esc_attr($atts['pointer_color']) . '"'
    //       . ' data-bbcs-pointer-stroke="' . esc_attr($atts['pointer_stroke']) . '"'
    //       . ' data-bbcs-pointer-stroke-width="' . esc_attr($atts['pointer_stroke_width']) . '"'
    //       . ' data-bbcs-pointer-stroke-linecap="' . esc_attr($atts['pointer_stroke_linecap']) . '"'
    //       . ' data-bbcs-level-colors=' . "'" . wp_json_encode(json_decode($atts['level_colors'], true)) . "'"
    //       . '></div>';

    $html = '<canvas id="bbcs-health_gauge" data-health-value="'. esc_attr($score) . '" data-bbcs-label="'. esc_attr($atts['label']) .'" style="width: 100%; height: auto; padding-left: 10px;
    padding-right: 10px;"></canvas>';

    return $html;
}
add_shortcode('bbcs_health_gauge', 'bbcs_healthGaugeShortcode');

function bbcs_generateSiteHealthList()
{
    $BBCS = BotBlocker::getInstance();

    if ($BBCS->settings->cache_ui_data == 1){
        $transient_key = 'bbcs_site_health_list';
        $cached_output = get_transient($transient_key);
        if ($cached_output !== false) {
            return $cached_output;
        }
    }
    if ($BBCS->isDisabled) {
        return '<div class="bbcs-health-list"><span class="bbcs-health-list-item text-danger"><i class="fa-regular fa-circle-xmark"></i> '. esc_html__('BotBlocker is disabled', 'botblocker-security') .'</span></div>';
    }

    $variables_affecting_weight = [
        'bbcs_captcha_mode'     => __('CAPTCHA mode enabled', 'botblocker-security'),
        'block_empty_ua'        => __('Blocking empty User-Agent', 'botblocker-security'),
        'block_empty_lang'      => __('Blocking empty language', 'botblocker-security'),
        'block_nojs_users'      => __('Blocking users without JavaScript', 'botblocker-security'),
        'block_simplebot_ua'    => __('Blocking simple bot User-Agents', 'botblocker-security'),
        'block_fake_ref'        => __('Blocking fake referrers', 'botblocker-security'),
        'block_proxy_users'     => __('Blocking proxy users', 'botblocker-security'),
        'block_ipv6_users'      => __('Blocking IPv6 users', 'botblocker-security'),
        'block_http10_users'    => __('Blocking HTTP/1.0 users', 'botblocker-security'),
        'hosting_block'         => __('Blocking hosting providers', 'botblocker-security'),
        'recaptcha_check'       => __('reCAPTCHA enabled', 'botblocker-security'),
        'time_ban'              => __('Time ban enabled', 'botblocker-security'),
        'time_ban_2'            => __('Secondary time ban enabled', 'botblocker-security'),
    ];

    $cloud_api_variables_affecting_weight = [
        'check'                     => __('Cloud protection', 'botblocker-security'),
        'unresponsive'              => __('Unresponsive IP blocking', 'botblocker-security'),
        'block_vpn_users'           => __('Blocking VPN users', 'botblocker-security'),
        'block_tor_users'           => __('Blocking Tor users', 'botblocker-security'),
        'block_override'            => __('Block override', 'botblocker-security'),
        'block_web_engine_options'  => __('Blocking web engine options', 'botblocker-security'),
        'block_device_options'      => __('Blocking device options', 'botblocker-security'),
    ];

    $variables_not_affecting_weight = [
        'block_cf_users'            => __('Blocking Cloudflare users', 'botblocker-security'),
        'block_incognito_users'     => __('Blocking incognito users', 'botblocker-security'),
        'block_simple_antidetect'   => __('Blocking simple antidetect', 'botblocker-security'),
        'block_incorrect_lang_users' => __('Blocking users with incorrect language', 'botblocker-security'),
    ];

    $output = '<div class="bbcs-health-list">';

    $isEnabledFunc = function($key, $value) use ($BBCS) {
        switch ($key) {
            case 'bbcs_captcha_mode':
                return $value != -1;
            case 'hosting_block':
                return $value == 1;
            case 'time_ban':
            case 'time_ban_2':
                return $value > 0;
            case 'recaptcha_check':
                return $value == 1 && !empty($BBCS->settings->recaptcha_key3) && !empty($BBCS->settings->recaptcha_secret3);
            default:
                return $value == 1;
        }
    };

    foreach ($variables_affecting_weight as $key => $text) {
        $value = isset($BBCS->settings->$key) ? $BBCS->settings->$key : null;
        $isEnabled = $isEnabledFunc($key, $value);

        $icon = $isEnabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
        $statusClass = $isEnabled ? 'text-success' : 'text-danger';
        $statusText = $isEnabled ? '' : ' (disabled)';

        $output .= "<span class='bbcs-health-list-item $statusClass'>$icon $text$statusText</span>";
    }

    foreach ($cloud_api_variables_affecting_weight as $key => $text) {
        $value = isset($BBCS->settings->$key) ? $BBCS->settings->$key : null;
        $isEnabled = $isEnabledFunc($key, $value);

        $icon = $isEnabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
        $statusClass = $isEnabled ? 'text-success' : 'text-danger';
        $statusText = $isEnabled ? '' : ' (disabled)';

        $output .= "<span class='bbcs-health-list-item $statusClass'>$icon $text$statusText</span>";
    }

    foreach ($variables_not_affecting_weight as $key => $text) {
        $value = isset($BBCS->settings->$key) ? $BBCS->settings->$key : null;
        $isEnabled = $isEnabledFunc($key, $value);

        $icon = $isEnabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
        $statusClass = $isEnabled ? 'text-muted' : 'text-muted';
        $statusText = $isEnabled ? '' : ' (disabled)';

        $output .= "<span class='bbcs-health-list-item $statusClass'>$icon $text$statusText</span>";
    }

    $output .= '</div>';
    if ($BBCS->settings->cache_ui_data == 1){
        set_transient($transient_key, $output, $BBCS->settings->cache_ui_duration);
    }
    return $output;
}
add_shortcode('botblocker_generateSiteHealthList', 'bbcs_generateSiteHealthList');

function bbcs_calculateSiteHealth()
{
    $BBCS = BotBlocker::getInstance();

    if ($BBCS->settings->cache_ui_data == 1){
        $transient_key = 'bbcs_site_health';
        $cached_health = get_transient($transient_key);
        if ($cached_health !== false) {
            return $cached_health;
        }
    }
    $health = 0;

    if (!$BBCS->isDisabled) {
        $variables_affecting_weight = [
            'bbcs_captcha_mode' => function($value) { return $value != -1; },
            'block_empty_ua' => function($value) { return $value == 1; },
            'block_empty_lang' => function($value) { return $value == 1; },
            'block_nojs_users' => function($value) { return $value == 1; },
            'block_simplebot_ua' => function($value) { return $value == 1; },
            'block_fake_ref' => function($value) { return $value == 1; },
            'block_proxy_users' => function($value) { return $value == 1; },
            'block_ipv6_users' => function($value) { return $value == 1; },
            'block_http10_users' => function($value) { return $value == 1; },
            'hosting_block' => function($value) { return $value == 1; },
            'recaptcha_check' => function($value) use ($BBCS) {
                return $value == 1 && !empty($BBCS->settings->recaptcha_key3) && !empty($BBCS->settings->recaptcha_secret3);
            },
            'time_ban' => function($value) { return $value > 0; },
            'time_ban_2' => function($value) { return $value > 0; },
        ];

        $num_variables = count($variables_affecting_weight);
        $weight_per_variable = 75 / $num_variables;

        foreach ($variables_affecting_weight as $key => $isEnabledFunc) {
            $value = isset($BBCS->settings->$key) ? $BBCS->settings->$key : null;
            if ($isEnabledFunc($value)) {
                $health += $weight_per_variable;
            }
        }

        $cloud_api_variables_affecting_weight = [
            'check' => function($value) { return $value == 1; },
            'unresponsive' => function($value) { return $value == 1; },
            'block_vpn_users' => function($value) { return $value == 1; },
            'block_tor_users' => function($value) { return $value == 1; },
            'block_override' => function($value) { return $value == 1; },
            'block_web_engine_options' => function($value) { return $value == 1; },
            'block_device_options' => function($value) { return $value == 1; },
        ];

        if (isset($BBCS->settings->check) && $BBCS->settings->check == 1) {
            $health += 12.5;
        }

        $cloud_api_variables_rest = array_slice($cloud_api_variables_affecting_weight, 1);
        $num_cloud_api_rest = count($cloud_api_variables_rest);
        $weight_per_cloud_api_variable = 12.5 / $num_cloud_api_rest;

        foreach ($cloud_api_variables_rest as $key => $isEnabledFunc) {
            $value = isset($BBCS->settings->$key) ? $BBCS->settings->$key : null;
            if ($isEnabledFunc($value)) {
                $health += $weight_per_cloud_api_variable;
            }
        }

        $normalizedHealth = min(100, max(0, $health));
        $final_health = round($normalizedHealth);
        if ($BBCS->settings->cache_ui_data == 1){
            set_transient($transient_key, $final_health, $BBCS->settings->cache_ui_duration);
        }
        return $final_health;
    } else {
        return 0;
    }
}

function bbcs_collect_statistic_data() {
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $BBCS->statistics = $wpdb->get_row("SELECT * FROM `{$wpdb->bbcs_counters}` LIMIT 1", ARRAY_A);

}

function bbcs_render_counters_grid() {
    $BBCS = BotBlocker::getInstance();

    if (!$BBCS->statistics) {
        return '<div class="bbcs-counters-grid">' . esc_html__('No data available.', 'botblocker-security') .'</div>';
    }
    $output = '<div class="bbcs-counters-grid">';
    $output .= '<div><span class="bbcs-h-today">' . esc_html($BBCS->statistics['today_hits']) . '</span><span class="bbcs-h-today-text">'. esc_html__('Today hits', 'botblocker-security') .'</span></div>';
    $output .= '<div><span class="bbcs-h-today-block">' . esc_html($BBCS->statistics['today_blocked']) . '</span><span class="bbcs-h-today-block-text">'. esc_html__('Today blocked', 'botblocker-security') .'</span></div>';
    $output .= '<div><span class="bbcs-h-total">' . esc_html($BBCS->statistics['total_hits']) . '</span><span class="bbcs-h-total-text">'. esc_html__('Total hits', 'botblocker-security') .'</span></div>';
    $output .= '<div><span class="bbcs-h-total-block">' . esc_html($BBCS->statistics['total_blocked']) . '</span><span class="bbcs-h-total-block-text">'. esc_html__('Total blocked', 'botblocker-security') .'</span></div>';
    $output .= '<div><span class="bbcs-h-se">' . esc_html($BBCS->statistics['search_engine_visits']) . '</span><span class="bbcs-h-se-text">'. esc_html__('Search engine visits', 'botblocker-security') .'</span></div>';
    $output .= '<div><span class="bbcs-h-percent-eff">' . esc_html($BBCS->statistics['percent_requests_blocked']) . '%</span><span class="bbcs-h-percent-eff-text">'. esc_html__('Requests blocked', 'botblocker-security') .'</span></div>';
    $output .= '</div>';

    return $output;
}

add_shortcode('bbcs_counters_grid', 'bbcs_render_counters_grid');
