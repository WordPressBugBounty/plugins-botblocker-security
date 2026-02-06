<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_generate_recommendations() {
    $BBCS = BotBlocker::getInstance();
    $BBCSA = Botblocker_Admin::getInstance();
    
    if ($BBCS->settings->cache_ui_data == 1) {
        $cache_key = 'bbcs_recommendations';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $recommendations = [];
    
    $wizard_completed = get_option('bbcs_setup_wizard_completed', false);
    if (!$wizard_completed) {
        $recommendations[] = [
            'icon' => 'fa-wand-magic-sparkles',
            'text' => __('Complete Setup Wizard', 'botblocker-security'),
            'url' => $BBCSA->pages_wizard,
            'priority' => 'high'
        ];
    }

    $has_recaptcha = (!empty($BBCS->settings->recaptcha_key3) && !empty($BBCS->settings->recaptcha_secret3)) ||
                     (!empty($BBCS->settings->recaptcha_key2) && !empty($BBCS->settings->recaptcha_secret2));
    if (!$has_recaptcha) {
        $recommendations[] = [
            'icon' => 'fa-shield-halved',
            'text' => __('Configure reCAPTCHA', 'botblocker-security'),
            'url' => $BBCSA->pages_integrations . '#bbcs_recaptchav3',
            'priority' => 'medium'
        ];
    }

    $has_cache = (isset($BBCS->settings->redis_enable) && $BBCS->settings->redis_enable == 1) ||
                 (isset($BBCS->settings->memcached_enable) && $BBCS->settings->memcached_enable == 1);
    if (!$has_cache) {
        $recommendations[] = [
            'icon' => 'fa-gauge-high',
            'text' => __('Enable Redis or Memcached', 'botblocker-security'),
            'url' => $BBCSA->pages_integrations . '#bbcs_redis',
            'priority' => 'high'
        ];
    }

    if (!isset($BBCS->settings->block_empty_ua) || $BBCS->settings->block_empty_ua != 1) {
        $recommendations[] = [
            'icon' => 'fa-robot',
            'text' => __('Enable empty User-Agent blocking', 'botblocker-security'),
            'url' => $BBCSA->pages_settings . '#simple_bots',
            'priority' => 'medium'
        ];
    }

    if (!isset($BBCS->settings->block_nojs_users) || $BBCS->settings->block_nojs_users != 1) {
        $recommendations[] = [
            'icon' => 'fa-code',
            'text' => __('Enable JavaScript verification', 'botblocker-security'),
            'url' => $BBCSA->pages_settings . '#simple_bots',
            'priority' => 'medium'
        ];
    }

    if (!isset($BBCS->settings->ptr_cache_in_db) || $BBCS->settings->ptr_cache_in_db != 1) {
        $recommendations[] = [
            'icon' => 'fa-database',
            'text' => __('Enable PTR cache in database', 'botblocker-security'),
            'url' => $BBCSA->pages_settings . '#data_log_and_processing',
            'priority' => 'low'
        ];
    }

    $mu_enable = isset($BBCS->settings->mu_enable) ? $BBCS->settings->mu_enable : 0;
    $early_init = isset($BBCS->settings->early_init_enable) ? $BBCS->settings->early_init_enable : 0;
    if ($mu_enable != 1 && $early_init != 1) {
        $recommendations[] = [
            'icon' => 'fa-rocket',
            'text' => __('Enable MU plugin mode', 'botblocker-security'),
            'url' => $BBCSA->pages_settings . '#general',
            'priority' => 'medium'
        ];
    }

    if (!bbcs_isCloudAPIActive()) {
        $recommendations[] = [
            'icon' => 'fa-crown',
            'text' => __('Activate BotBlocker PRO', 'botblocker-security'),
            'url' => $BBCSA->pages_cloud_api,
            'priority' => 'high'
        ];
    }

    $has_2fa = isset($BBCS->settings->bbcs_2fa_enable) && $BBCS->settings->bbcs_2fa_enable == 1;
    if (!$has_2fa) {
        $recommendations[] = [
            'icon' => 'fa-lock',
            'text' => __('Enable Two-Factor Authentication', 'botblocker-security'),
            'url' => $BBCSA->pages_integrations . '#bbcs_2fa',
            'priority' => 'medium'
        ];
    }

    if (empty($recommendations)) {
        $output = '<div class="alert alert-success mb-0" style="font-size: 11px; padding: 10px;">
            <i class="fa-solid fa-check-circle me-1"></i>
            <strong>' . esc_html__('All optimized!', 'botblocker-security') . '</strong> ' .
            esc_html__('Your security configuration is complete.', 'botblocker-security') . '
        </div>';
    } else {
        $output = '<ul class="bbcs-recommendations-list">';
        foreach ($recommendations as $rec) {
            $priority_class = 'bbcs-priority-' . $rec['priority'];
            $output .= '<li class="bbcs-recommendation-item ' . esc_attr($priority_class) . '">';
            $output .= '<i class="fa-solid ' . esc_attr($rec['icon']) . ' bbcs-rec-icon"></i>';
            $output .= '<a href="' . esc_url($rec['url']) . '" class="bbcs-rec-link">' . esc_html($rec['text']) . '</a>';
            $output .= '</li>';
        }
        $output .= '</ul>';
    }

    if ($BBCS->settings->cache_ui_data == 1) {
        set_transient($cache_key, $output, $BBCS->settings->cache_ui_duration);
    }

    return $output;
}
add_shortcode('bbcs_recommendations', 'bbcs_generate_recommendations');