<?php

declare(strict_types=1);
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class BotBlockerSettings
{

	public $secure_mode = 1; // 1 = Frontend, 2 = Full

	public $admin_gmt_offset		= 0;
	public $admin_report_period		= 5;
	public $admin_store_period		= 7;
	public $admin_uniq_type			= 'host';
	public $allow_self_ip_req		= 1;
	public $autosave_admin_ip		= 0;
	public $skip_logged_in_users	= 0;

	public $bbcs_api_gs_url = '';
	public $bbcs_api_url    = '';

	public $bbcs_allow_empty_accept_lang = 0;
	public $bbcs_captcha_img_inline = 1;
	public $bbcs_captcha_img_pack   = 1;
	public $bbcs_captcha_mode       = 1;
	public $bbcs_captcha_wait       = 30;
	public $bbcs_cors_strict_headers    = 0;
	public $bbcs_ddos_resilience    = 0;

	public $block_adblocker_users      = 1;
	public $block_cf_users             = 1;
	public $block_device_options       = 0;
	public $block_empty_lang           = 1;
	public $block_empty_ua             = 1;
	public $block_fake_ref             = 1;
	public $block_http10_users         = 1;
	public $block_incognito_users      = 1;
	public $block_incorrect_lang_users = 0;
	public $block_ipv6_users           = 0;
	public $block_ip_ptr_match         = 0;
	public $block_nojs_users           = 1;
	public $block_override             = 0;
	public $block_proxy_users          = 1;
	public $block_rkn                  = 0;
	public $block_simple_antidetect    = 1;
	public $block_simplebot_ua         = 1;
	public $block_tor_users            = 0;
	public $block_vpn_users            = 0;
	public $block_web_engine_options   = 0;
	public $options_preflight          = 1;
	public $whitelist_whatsapp_preview = 0;


	public $botblocker_force_check = 0;
	public $force_cloud_validation = 0;

	public $botblocker_log_error    = 1;
	public $botblocker_log_admin    = 0;
	public $botblocker_log_allow    = 1;
	public $botblocker_log_bbcs     = 0;
	public $botblocker_log_block    = 1;
	public $botblocker_log_cli      = 0;
	public $botblocker_log_disabled = 0;
	public $botblocker_log_fake     = 1;
	public $botblocker_log_goodip   = 1;
	public $botblocker_log_local    = 1;
	public $botblocker_log_tests    = 1;
	public $botblocker_log_wp       = 0;

	public $payment_bypass_enable	= 0;
	public $payment_bypass_log		= 1;
	public $payment_strict_method	= 0;
	public $payment_keep_ip_rules	= 0;

	public $cache_ui_data     = 0;
	public $cache_ui_duration = 3600;
	public $check             = 0;
	public $check_get_ref     = 1;

	public $cloud_api_timeout = 5;

	public $cookie          		= 'BotBlocker';
	public $cookie_lifetime 		= 604800;
	public $session_token_enabled 	= 1;
	public $vary_cookie     		= 0;

	public $daylight_saving_time = 0;
	public $disable              = 0;
	public $get_browser_type     = 1;
	public $get_device_type      = 1;
	public $get_os_type          = 1;
	public $header_error_code    = 400;
	public $header_test_code     = 200;
	public $hits_per_user        = 500;
	public $hosting_block        = 0;
	public $iframe_stop          = 0;
	public $last_rule            = '';

	public $cloud_api_type   = '';
	public $cloud_api_email  = '';
	public $cloud_api_key    = '';
	public $cloud_api_pass   = '';
	public $cloud_api_secret = '';
	public $cloud_api_tier   = '';

	public $memcached_enable = 1;
	public $memcached_host   = '127.0.0.1';
	public $memcached_port   = 11211;
	public $memcached_prefix = 'bb_';

	public $noarchive          = 0;
	public $ptr_cache_in_db    = 1;
	public $ptrcache_time      = 86400;
	public $ptrcache_subnet    = '24-64';
	public $ptrcache_rule_ttl  = 90;

	public $recaptcha_check         = 0;
	public $recaptcha_key2          = '';
	public $recaptcha_key3          = '';
	public $recaptcha_secret2       = '';
	public $recaptcha_secret3       = '';
	public $recaptcha_tresshold     = 0.5;
	public $recaptcha_v3_ipv6_block = 0;

	public $login_brutforce_enabled              = 1;
	public $login_brutforce_attempts             = 5;
	public $login_brutforce_period               = 900;
	public $login_brutforce_primary_block_time   = 900;
	public $login_brutforce_secondary_block_time = 1800;

	public $redis_enable   = 0;
	public $redis_host     = '127.0.0.1';
	public $redis_password = '';
	public $redis_port     = 6379;
	public $redis_prefix   = 'bb_';

	public $mu_enable = 0;

	public $early_init_enable 	= 0;

	public $x_robots_directives = array();

	public $salt     = '';
	public $salt_pz  = '';
	public $salt_ps  = '';
	public $salt_bb  = '';
	public $host_key = '';

	public $samesite                    = 'Lax';
	public $secret_botblocker_get_param = '';
	public $action_disable              = '';
	public $action_off                  = '';
	public $action_on                   = '';

	public $time_ban   = 200;
	public $time_ban_2 = 400;

	public $unresponsive            = 0;
	public $cloud_fallback_block	= 0;
	public $utm_noindex             = 0;
	public $utm_referrer            = 1;

	public $telegram_notifications          = 0;
	public $telegram_bot_token              = '';
	public $telegram_chat_id                = '';
	public $email_notifications             = 0;
	public $pusher_notifications            = 0;
	public $critical_load_notifications     = 0;
	public $regular_notifications_frequency = 'disabled';

	public $bbcs_2fa_enable = 0;

	public $tls_fingerprint_check 			= 0;
	public $tls_fingerprint_header_ja3		= 'X-TLS-JA3';
	public $tls_fingerprint_header_ja4 		= 'X-TLS-JA4';
	public $tls_fingerprint_trusted_proxy	= '';

	public $bbcs_rate_check_enabled     = 1;
	public $bbcs_rate_captcha_rpm       = 30;
	public $bbcs_rate_block_rpm         = 50;
	public $bbcs_rate_window_minutes    = 5;
	public $bbcs_rate_subnet_enabled    = 1;
	public $bbcs_rate_subnet_multiplier = 3.0;
	public $bbcs_rate_floor_percent     = 0.1;
	public $bbcs_rate_subnet_mask       = '24-64';
	public $bbcs_rate_block_duration    = 600;

	public $fingerprint_sticky_block = 0;

	public function load(string $settingsFile): bool
	{
		$settings = bbcs_safe_load_with_recovery($settingsFile);
		if (is_array($settings) && ! empty($settings)) {
			$this->assignSettings($settings);
			return true;
		}

		$settings = BotBlockerFileRenderer::generateSettingsFile(true);
		if (is_array($settings)) {
			$this->assignSettings($settings);
			return true;
		}
		return false;
	}

	private function assignSettings(array $settings): void
	{
		$defaults = get_class_vars(__CLASS__);
		foreach ($settings as $key => $value) {
			if (! property_exists($this, $key)) {
				continue;
			}
			if (array_key_exists($key, $defaults) && is_scalar($defaults[$key]) && is_scalar($value)) {
				settype($value, gettype($defaults[$key]));
			}
			$this->$key = $value;
		}
	}
}
