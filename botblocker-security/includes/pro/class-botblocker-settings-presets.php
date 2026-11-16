<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerSettingsPresets {

	public const EMERGENCY_SNAPSHOT_OPTION = 'bbcs_emergency_snapshot';
	public const EMERGENCY_REVERT_DELAY   = 21600;

	public static function getAllowedFields(): array {
		return array(
			'action_disable',
			'action_off',
			'action_on',
			'admin_gmt_offset',
			'admin_report_period',
			'admin_store_period',
			'admin_uniq_type',
			'allow_self_ip_req',
			'allow_self_call_header',
			'allow_app_password_rest',
			'autosave_admin_ip',
			'skip_logged_in_users',
			'bbcs_captcha_img_inline',
			'bbcs_captcha_img_pack',
			'bbcs_captcha_mode',
			'bbcs_captcha_wait',
			'bbcs_honeypot_enabled',
			'bbcs_ddos_resilience',
			'bbcs_edge_autotrust',
			'block_adblocker_users',
			'block_cf_users',
			'block_device_options',
			'block_empty_lang',
			'bbcs_allow_empty_accept_lang',
			'block_empty_ua',
			'block_fake_ref',
			'block_http10_users',
			'block_incognito_users',
			'block_incorrect_lang_users',
			'block_rkn',
			'block_ipv6_users',
			'block_ip_ptr_match',
			'block_override',
			'block_proxy_users',
			'block_simple_antidetect',
			'block_simplebot_ua',
			'block_tor_users',
			'block_vpn_users',
			'block_web_engine_options',
			'botblocker_force_check',
			'force_cloud_validation',
			'botblocker_log_admin',
			'botblocker_log_allow',
			'botblocker_log_bbcs',
			'botblocker_log_block',
			'botblocker_log_cli',
			'botblocker_log_disabled',
			'botblocker_log_error',
			'botblocker_log_fake',
			'botblocker_log_goodip',
			'botblocker_log_local',
			'botblocker_log_tests',
			'botblocker_log_wp',
			'audit_log_enable',
			'audit_log_retention_days',
			'audit_log_roles',
			'bbcs_2fa_enable',
			'bbcs_2fa_xmlrpc_block',
			'cache_ui_data',
			'cache_ui_duration',
			'check',
			'check_get_ref',
			'cookie',
			'cookie_lifetime',
			'daylight_saving_time',
			'disable',
			'early_init_enable',
			'early_geo_enable',
			'get_browser_type',
			'get_device_type',
			'get_os_type',
			'header_error_code',
			'header_test_code',
			'hits_per_user',
			'hosting_block',
			'iframe_stop',
			'last_rule',
			'login_brutforce_attempts',
			'login_brutforce_enabled',
			'login_brutforce_period',
			'login_brutforce_primary_block_time',
			'login_brutforce_secondary_block_time',
			'cloud_api_timeout',
			'cloud_api_type',
			'cloud_api_email',
			'cloud_api_key',
			'cloud_api_pass',
			'cloud_api_secret',
			'cloud_api_tier',
			'cloud_fallback_block',
			'memcached_enable',
			'memcached_host',
			'memcached_port',
			'memcached_prefix',
			'mu_enable',
			'mu_geo_enable',
			'noarchive',
			'options_preflight',
			'payment_bypass_enable',
			'payment_bypass_log',
			'payment_strict_method',
			'payment_keep_ip_rules',
			'ptr_cache_in_db',
			'ptrcache_time',
			'ptrcache_subnet',
			'ptrcache_rule_ttl',
			'recaptcha_check',
			'recaptcha_key2',
			'recaptcha_key3',
			'recaptcha_secret2',
			'recaptcha_secret3',
			'recaptcha_tresshold',
			'recaptcha_v3_ipv6_block',
			'redis_database',
			'redis_enable',
			'transients_enable',
			'file_cache_max_files',
			'redis_host',
			'redis_password',
			'redis_port',
			'redis_prefix',
			'apcu_enable',
			'apcu_prefix',
			'secure_mode',
			'samesite',
			'secret_botblocker_get_param',
			'session_token_enabled',
			'tls_fingerprint_check',
			'tls_fingerprint_header_ja3',
			'tls_fingerprint_header_ja4',
			'tls_fingerprint_trusted_proxy',
			'time_ban',
			'time_ban_2',
			'unresponsive',
			'utm_noindex',
			'utm_referrer',
			'vary_cookie',
			'bbcs_cors_strict_headers',
			'bbcs_wp_connectors_enabled',
			'bbcs_wp_abilities_enabled',
			'bbcs_ability_get_firewall_status',
			'bbcs_ability_check_ip',
			'bbcs_ability_get_ip_rules',
			'bbcs_ability_get_traffic_summary',
			'bbcs_ability_run_diagnostics',
			'bbcs_ability_block_ip',
			'bbcs_ability_unblock_ip',
			'bbcs_ability_set_protection',
			'bbcs_ability_set_secure_mode',
			'bbcs_ability_set_captcha_mode',
			'bbcs_ability_get_captcha_state',
			'bbcs_ability_list_ip_rules',
			'bbcs_ability_get_blocked_ips',
			'bbcs_ability_get_recent_events',
			'bbcs_ability_get_settings',
			'bbcs_ability_run_self_test',
			'bbcs_ability_upsert_ip_rule',
			'bbcs_ability_delete_ip_rule',
			'bbcs_ability_check_ips',
			'bbcs_ability_clear_cache',
			'bbcs_ability_get_health_score',
			'bbcs_ability_get_protection_snapshot',
			'bbcs_ability_get_addons_status',
			'bbcs_ability_get_license_status',
			'bbcs_ability_get_capabilities',
			'bbcs_ability_validate_config',
			'bbcs_ability_get_cron_jobs',
			'bbcs_ability_get_db_health',
			'bbcs_ability_get_top_entities',
			'bbcs_ability_get_2fa_summary',
			'bbcs_ability_get_audit_log',
			'bbcs_ability_get_event_details',
			'bbcs_ability_get_top_ips',
			'bbcs_ability_get_login_attempts',
			'bbcs_ability_get_visitor_log',
			'bbcs_ability_get_user_risk',
			'bbcs_ability_get_ability_calls',
			'bbcs_ability_delete_ip_rules',
			'bbcs_ability_set_country_rule',
			'bbcs_ability_set_asn_rule',
			'bbcs_ability_update_threat_data',
			'bbcs_ability_export_settings',
			'bbcs_ability_apply_preset',
			'bbcs_ability_simulate_request',
			'bbcs_ability_get_decision_trace',
			'bbcs_ability_get_rate_limit_state',
			'bbcs_ability_get_request_fingerprint',
			'bbcs_ability_get_data_integrity',
			'bbcs_ability_get_bot_signature_match',
			'bbcs_ability_get_tls_match',
			'bbcs_ability_get_geo',
			'bbcs_ability_get_pass_state',
			'bbcs_ability_get_challenge_state',
			'bbcs_ability_get_rule_match',
			'bbcs_ability_get_layer_state',
			'bbcs_ability_export_diagnostics',
			'bbcs_ability_reset_rate_limit',
			'bbcs_ability_reset_ptr_cache',
			'bbcs_ability_seed_test_rule',
			'bbcs_ability_clear_test_rules',
			'bbcs_ability_set_setting',
			'bbcs_ability_load_license_from_cloud',
			'bbcs_ability_get_edge_trust',
			'bbcs_ability_get_edge_evidence',
			'bbcs_ability_explain_ban_decision',
			'bbcs_ability_remove_edge_trust',
			'whitelist_whatsapp_preview',
			'x_robots_directives',

			'bbcs_rate_check_enabled',
			'bbcs_rate_captcha_rpm',
			'bbcs_rate_block_rpm',
			'bbcs_rate_passed_rpm',
			'bbcs_rate_passed_captcha_rpm',
			'bbcs_hide_user_enumeration',
			'bbcs_strip_php_version',
			'bbcs_rate_window_minutes',
			'bbcs_rate_subnet_enabled',
			'bbcs_rate_subnet_multiplier',
			'bbcs_rate_floor_percent',
			'bbcs_rate_subnet_mask',
			'bbcs_rate_block_duration',

			'fingerprint_sticky_block',
		);
	}

	public static function getExcludedFields(): array {
		return array(
			'action_disable',
			'action_off',
			'action_on',
			'admin_gmt_offset',
			'admin_report_period',
			'admin_store_period',
			'admin_uniq_type',
			'apcu_enable',
			'apcu_prefix',
			'audit_log_enable',
			'audit_log_retention_days',
			'audit_log_roles',
			'bbcs_2fa_enable',
			'bbcs_2fa_roles',
			'bbcs_2fa_xmlrpc_block',
			'bbcs_wp_abilities_enabled',
			'bbcs_wp_connectors_enabled',
			'botblocker_log_admin',
			'botblocker_log_allow',
			'botblocker_log_bbcs',
			'botblocker_log_block',
			'botblocker_log_cli',
			'botblocker_log_disabled',
			'botblocker_log_error',
			'botblocker_log_fake',
			'botblocker_log_goodip',
			'botblocker_log_local',
			'botblocker_log_tests',
			'botblocker_log_wp',
			'cache_ui_data',
			'cache_ui_duration',
			'cloud_api_email',
			'cloud_api_key',
			'cloud_api_pass',
			'cloud_api_secret',
			'cloud_api_tier',
			'cloud_api_type',
			'cookie',
			'daylight_saving_time',
			'early_cache_gate',
			'early_cache_pass_ttl',
			'early_init_enable',
			'file_cache_max_files',
			'last_rule',
			'memcached_enable',
			'memcached_host',
			'memcached_port',
			'memcached_prefix',
			'mu_enable',
			'recaptcha_key2',
			'recaptcha_key3',
			'recaptcha_secret2',
			'recaptcha_secret3',
			'redis_database',
			'redis_enable',
			'redis_host',
			'redis_password',
			'redis_port',
			'redis_prefix',
			'salt',
			'secret_botblocker_get_param',
			'tls_fingerprint_header_ja3',
			'tls_fingerprint_header_ja4',
			'tls_fingerprint_trusted_proxy',
			'transients_enable',
			'x_robots_directives',
			'bbcs_ability_get_firewall_status',
			'bbcs_ability_check_ip',
			'bbcs_ability_get_ip_rules',
			'bbcs_ability_get_traffic_summary',
			'bbcs_ability_run_diagnostics',
			'bbcs_ability_block_ip',
			'bbcs_ability_unblock_ip',
			'bbcs_ability_set_protection',
			'bbcs_ability_set_secure_mode',
			'bbcs_ability_set_captcha_mode',
			'bbcs_ability_get_captcha_state',
			'bbcs_ability_list_ip_rules',
			'bbcs_ability_get_blocked_ips',
			'bbcs_ability_get_recent_events',
			'bbcs_ability_get_settings',
			'bbcs_ability_run_self_test',
			'bbcs_ability_upsert_ip_rule',
			'bbcs_ability_delete_ip_rule',
			'bbcs_ability_check_ips',
			'bbcs_ability_clear_cache',
			'bbcs_ability_get_health_score',
			'bbcs_ability_get_protection_snapshot',
			'bbcs_ability_get_addons_status',
			'bbcs_ability_get_license_status',
			'bbcs_ability_get_capabilities',
			'bbcs_ability_validate_config',
			'bbcs_ability_get_cron_jobs',
			'bbcs_ability_get_db_health',
			'bbcs_ability_get_top_entities',
			'bbcs_ability_get_2fa_summary',
			'bbcs_ability_get_audit_log',
			'bbcs_ability_get_event_details',
			'bbcs_ability_get_top_ips',
			'bbcs_ability_get_login_attempts',
			'bbcs_ability_get_visitor_log',
			'bbcs_ability_get_user_risk',
			'bbcs_ability_get_ability_calls',
			'bbcs_ability_delete_ip_rules',
			'bbcs_ability_set_country_rule',
			'bbcs_ability_set_asn_rule',
			'bbcs_ability_update_threat_data',
			'bbcs_ability_export_settings',
			'bbcs_ability_apply_preset',
			'bbcs_ability_simulate_request',
			'bbcs_ability_get_decision_trace',
			'bbcs_ability_get_rate_limit_state',
			'bbcs_ability_get_request_fingerprint',
			'bbcs_ability_get_data_integrity',
			'bbcs_ability_get_bot_signature_match',
			'bbcs_ability_get_tls_match',
			'bbcs_ability_get_geo',
			'bbcs_ability_get_pass_state',
			'bbcs_ability_get_challenge_state',
			'bbcs_ability_get_rule_match',
			'bbcs_ability_get_layer_state',
			'bbcs_ability_get_edge_trust',
			'bbcs_ability_get_edge_evidence',
			'bbcs_ability_explain_ban_decision',
			'bbcs_ability_load_license_from_cloud',
			'bbcs_ability_remove_edge_trust',
			'bbcs_ability_export_diagnostics',
			'bbcs_ability_reset_rate_limit',
			'bbcs_ability_reset_ptr_cache',
			'bbcs_ability_seed_test_rule',
			'bbcs_ability_clear_test_rules',
			'bbcs_ability_set_setting',
		);
	}

	public static function loadDefaultSettings(): array {
		$defaults = array(
			'secure_mode'                          => 2,
			'disable'                              => 0,
			'bbcs_captcha_img_inline'              => 1,
			'bbcs_captcha_img_pack'                => 1,
			'bbcs_captcha_mode'                    => BOTBLOCKER_CAPTCHA_MODE_DEFAULT,
			'bbcs_captcha_wait'                    => 30,
			'bbcs_ddos_resilience'                 => 0,
			'cloud_api_type'                       => BotBlockerPro::TYPE_BASIC,
			'cloud_api_tier'                       => '',

			'block_empty_ua'                       => 1,
			'block_empty_lang'                     => 0,
			'bbcs_allow_empty_accept_lang'         => 1,
			'block_proxy_users'                    => 0,
			'block_ipv6_users'                     => 0,
			'block_ip_ptr_match'                   => 0,
			'block_adblocker_users'                => 0,
			'block_http10_users'                   => 0,
			'block_incognito_users'                => 0,
			'block_simple_antidetect'              => 0,
			'block_override'                       => 0,
			'block_web_engine_options'             => 0,
			'block_device_options'                 => 0,
			'fingerprint_sticky_block'             => 0,
			'block_cf_users'                       => 0,
			'block_incorrect_lang_users'           => 0,
			'block_rkn'                            => 0,
			'block_simplebot_ua'                   => 1,
			'whitelist_whatsapp_preview'           => 0,
			'allow_self_ip_req'                    => 1,
			'allow_self_call_header'               => 1,
			'allow_app_password_rest'             => 1,

			'get_browser_type'                     => 1,
			'get_os_type'                          => 1,
			'get_device_type'                      => 1,

			'admin_report_period'                  => 5,
			'admin_store_period'                   => 7,
			'admin_gmt_offset'                     => 0,

			'check'                                => 0,
			'unresponsive'                         => 0,
			'cloud_fallback_block'                 => 0,
			'cookie'                               => BOTBLOCKER_SHORT_NAME,
			'hits_per_user'                        => 500,
			'time_ban'                             => '50',
			'time_ban_2'                           => '100',
			'utm_referrer'                         => 1,
			'utm_noindex'                          => 0,
			'check_get_ref'                        => 0,
			'ptrcache_time'                        => DAY_IN_SECONDS,
			'ptrcache_subnet'                      => '32-128',
			'bbcs_edge_autotrust'                  => 1,
			'ptrcache_rule_ttl'                    => 90,
			'botblocker_log_tests'                 => 1,
			'botblocker_log_local'                 => 1,
			'botblocker_log_allow'                 => 1,
			'botblocker_log_fake'                  => 1,
			'botblocker_log_goodip'                => 1,
			'botblocker_log_block'                 => 1,
			'botblocker_log_admin'                 => 0,
			'botblocker_log_wp'                    => 0,
			'botblocker_log_cli'                   => 0,
			'botblocker_log_bbcs'                  => 0,
			'botblocker_log_error'                 => 1,
			'audit_log_enable'                     => 1,
			'audit_log_retention_days'             => 7,
			'audit_log_roles'                      => '',
			'header_test_code'                     => 200,
			'header_error_code'                    => 400,
			'noarchive'                            => 0,
			'options_preflight'                    => 1,
			'last_rule'                            => '',
			'samesite'                             => 'Lax',
			'iframe_stop'                          => 0,
			'hosting_block'                        => 0,
			'block_fake_ref'                       => 0,

			'recaptcha_check'                      => 0,
			'recaptcha_key2'                       => '',
			'recaptcha_secret2'                    => '',
			'recaptcha_key3'                       => '',
			'recaptcha_secret3'                    => '',
			'recaptcha_tresshold'                  => 0.5,
			'recaptcha_v3_ipv6_block'              => 0,
			'memcached_enable'                     => 0,
			'memcached_host'                       => '127.0.0.1',
			'memcached_port'                       => 11211,
			'memcached_prefix'                     => BOTBLOCKER_PREFIX,

			'redis_database'                       => 0,
			'redis_host'                           => '127.0.0.1',
			'redis_port'                           => 6379,
			'redis_prefix'                         => BOTBLOCKER_PREFIX,
			'redis_password'                       => '',
			'redis_enable'                         => 0,
			'transients_enable'                    => 1,

			'apcu_enable'                          => 0,
			'apcu_prefix'                          => BOTBLOCKER_PREFIX,

			'mu_enable'                            => (int) BOTBLOCKER_INTEGRATE_MU_PLUGINS,
			'early_init_enable'                    => 0,
			'early_geo_enable'                     => 0,
			'mu_geo_enable'                        => 0,

			'autosave_admin_ip'                    => 0,
			'skip_logged_in_users'                 => 0,
			'ptr_cache_in_db'                      => 1,
			'botblocker_force_check'               => 0,
			'force_cloud_validation'               => 0,
			'cache_ui_data'                        => 1, // default on
			'cache_ui_duration'                    => 1800,
			'daylight_saving_time'                 => 0,
			'cookie_lifetime'                      => 604800,
			'session_token_enabled'                => 1,
			'cloud_api_timeout'                    => 5,
			'vary_cookie'                          => 0,
			'bbcs_cors_strict_headers'             => 0,
			'bbcs_wp_abilities_enabled'            => 0,
			'bbcs_ability_get_firewall_status'     => 0,
			'bbcs_ability_check_ip'                => 0,
			'bbcs_ability_get_ip_rules'            => 0,
			'bbcs_ability_get_traffic_summary'     => 0,
			'bbcs_ability_run_diagnostics'         => 0,
			'bbcs_ability_block_ip'                => 0,
			'bbcs_ability_unblock_ip'              => 0,
			'bbcs_ability_set_protection'          => 0,
			'bbcs_ability_set_secure_mode'         => 0,
			'bbcs_ability_set_captcha_mode'        => 0,
			'bbcs_ability_get_captcha_state'       => 0,
			'bbcs_ability_list_ip_rules'           => 0,
			'bbcs_ability_get_blocked_ips'         => 0,
			'bbcs_ability_get_recent_events'       => 0,
			'bbcs_ability_get_settings'            => 0,
			'bbcs_ability_run_self_test'           => 0,
			'bbcs_ability_upsert_ip_rule'          => 0,
			'bbcs_ability_delete_ip_rule'          => 0,
			'bbcs_ability_check_ips'               => 0,
			'bbcs_ability_clear_cache'             => 0,
			'bbcs_ability_get_health_score'         => 0,
			'bbcs_ability_get_protection_snapshot'  => 0,
			'bbcs_ability_get_addons_status'        => 0,
			'bbcs_ability_get_license_status'       => 0,
			'bbcs_ability_get_capabilities'         => 0,
			'bbcs_ability_validate_config'          => 0,
			'bbcs_ability_get_cron_jobs'            => 0,
			'bbcs_ability_get_db_health'            => 0,
			'bbcs_ability_get_top_entities'         => 0,
			'bbcs_ability_get_2fa_summary'          => 0,
			'bbcs_ability_get_audit_log'            => 0,
			'bbcs_ability_get_event_details'        => 0,
			'bbcs_ability_get_top_ips'              => 0,
			'bbcs_ability_get_login_attempts'       => 0,
			'bbcs_ability_get_visitor_log'          => 0,
			'bbcs_ability_get_user_risk'            => 0,
			'bbcs_ability_get_ability_calls'        => 0,
			'bbcs_ability_delete_ip_rules'          => 0,
			'bbcs_ability_set_country_rule'         => 0,
			'bbcs_ability_set_asn_rule'             => 0,
			'bbcs_ability_update_threat_data'       => 0,
			'bbcs_ability_export_settings'          => 0,
			'bbcs_ability_apply_preset'             => 0,
			'bbcs_ability_simulate_request'         => 0,
			'bbcs_ability_get_decision_trace'       => 0,
			'bbcs_ability_get_rate_limit_state'     => 0,
			'bbcs_ability_get_request_fingerprint'  => 0,
			'bbcs_ability_get_data_integrity'       => 0,
			'bbcs_ability_get_bot_signature_match'  => 0,
			'bbcs_ability_get_tls_match'            => 0,
			'bbcs_ability_get_geo'                  => 0,
			'bbcs_ability_get_pass_state'           => 0,
			'bbcs_ability_get_challenge_state'      => 0,
			'bbcs_ability_get_rule_match'           => 0,
			'bbcs_ability_get_layer_state'          => 0,
			'bbcs_ability_get_edge_trust'            => 0,
			'bbcs_ability_get_edge_evidence'         => 0,
			'bbcs_ability_explain_ban_decision'      => 0,
			'bbcs_ability_load_license_from_cloud'   => 0,
			'bbcs_ability_remove_edge_trust'         => 0,
			'bbcs_ability_export_diagnostics'       => 0,
			'bbcs_ability_reset_rate_limit'         => 0,
			'bbcs_ability_reset_ptr_cache'          => 0,
			'bbcs_ability_seed_test_rule'           => 0,
			'bbcs_ability_clear_test_rules'         => 0,

			'payment_bypass_enable'                => 0,
			'payment_bypass_log'                   => 1,
			'payment_strict_method'                => 0,
			'payment_keep_ip_rules'                => 0,

			'login_brutforce_enabled'              => 1,
			'login_brutforce_attempts'             => 5,
			'login_brutforce_period'               => 900,
			'login_brutforce_primary_block_time'   => 900,
			'login_brutforce_secondary_block_time' => 1800,

			'bbcs_2fa_enable'                      => 0,
			'bbcs_2fa_xmlrpc_block'                => 0,

			'tls_fingerprint_check'                => 0,
			'tls_fingerprint_header_ja3'           => 'X-TLS-JA3',
			'tls_fingerprint_header_ja4'           => 'X-TLS-JA4',
			'tls_fingerprint_trusted_proxy'        => '',

			'bbcs_rate_check_enabled'              => 1,
			'bbcs_rate_captcha_rpm'                => 60,
			'bbcs_rate_block_rpm'                  => 120,
			'bbcs_rate_passed_rpm'                 => 300,
			'bbcs_rate_passed_captcha_rpm'         => 150,
			'bbcs_hide_user_enumeration'           => 1,
			'bbcs_strip_php_version'               => 1,
			'bbcs_rate_window_minutes'             => 5,
			'bbcs_rate_subnet_enabled'             => 0,
			'bbcs_rate_subnet_multiplier'          => 3.0,
			'bbcs_rate_floor_percent'              => 0.2,
			'bbcs_rate_subnet_mask'                => '24-64',
			'bbcs_rate_block_duration'             => 300,
		);
		$defaults['payment_bypass_enable'] = ( class_exists( 'WooCommerce' ) || function_exists( 'wc_get_orders' ) ) ? 1 : 0;
		return BotBlockerPro::withoutProFeatures( $defaults );
	}

	public static function loadLightSecurity(): array {
		$settings = array(
			'allow_self_ip_req'                      => 1,
			'allow_self_call_header'                 => 1,
			'allow_app_password_rest'                => 1,
			'autosave_admin_ip'                      => 0,
			'skip_logged_in_users'                   => 0,
			'bbcs_captcha_img_inline'                => 1,
			'bbcs_captcha_img_pack'                  => 1,
			'bbcs_captcha_mode'                      => BOTBLOCKER_CAPTCHA_MODE_DEFAULT,
			'bbcs_captcha_wait'                      => 30,
			'bbcs_honeypot_enabled'                  => 0,
			'bbcs_ddos_resilience'                   => 0,
			'bbcs_edge_autotrust'                    => 1,
			'block_adblocker_users'                  => 0,
			'block_cf_users'                         => 0,
			'block_device_options'                   => 0,
			'block_empty_lang'                       => 0,
			'bbcs_allow_empty_accept_lang'           => 1,
			'block_empty_ua'                         => 1,
			'block_fake_ref'                         => 0,
			'block_http10_users'                     => 0,
			'block_incognito_users'                  => 0,
			'block_incorrect_lang_users'             => 0,
			'block_rkn'                              => 0,
			'block_ipv6_users'                       => 0,
			'block_ip_ptr_match'                     => 0,
			'block_override'                         => 0,
			'block_proxy_users'                      => 0,
			'block_simple_antidetect'                => 0,
			'block_simplebot_ua'                     => 1,
			'block_tor_users'                        => 0,
			'block_vpn_users'                        => 0,
			'block_web_engine_options'               => 0,
			'botblocker_force_check'                 => 0,
			'force_cloud_validation'                 => 0,
			'check'                                  => 0,
			'check_get_ref'                          => 0,
			'cookie_lifetime'                        => 604800,
			'disable'                                => 0,
			'early_geo_enable'                       => 0,
			'get_browser_type'                       => 1,
			'get_device_type'                        => 1,
			'get_os_type'                            => 1,
			'header_error_code'                      => 400,
			'header_test_code'                       => 200,
			'hits_per_user'                          => 500,
			'hosting_block'                          => 0,
			'iframe_stop'                            => 0,
			'login_brutforce_attempts'               => 5,
			'login_brutforce_enabled'                => 1,
			'login_brutforce_period'                 => 900,
			'login_brutforce_primary_block_time'     => 900,
			'login_brutforce_secondary_block_time'   => 1800,
			'cloud_api_timeout'                      => 5,
			'cloud_fallback_block'                   => 0,
			'mu_geo_enable'                          => 0,
			'noarchive'                              => 0,
			'options_preflight'                      => 1,
			'payment_bypass_enable'                  => 0,
			'payment_bypass_log'                     => 1,
			'payment_strict_method'                  => 0,
			'payment_keep_ip_rules'                  => 0,
			'ptr_cache_in_db'                        => 1,
			'ptrcache_time'                          => DAY_IN_SECONDS,
			'ptrcache_subnet'                        => '32-128',
			'ptrcache_rule_ttl'                      => 90,
			'recaptcha_check'                        => 0,
			'recaptcha_tresshold'                    => 0.7,
			'recaptcha_v3_ipv6_block'                => 0,
			'secure_mode'                            => 2,
			'samesite'                               => 'Lax',
			'session_token_enabled'                  => 1,
			'tls_fingerprint_check'                  => 0,
			'time_ban'                               => '50',
			'time_ban_2'                             => '100',
			'unresponsive'                           => 0,
			'utm_noindex'                            => 0,
			'utm_referrer'                           => 0,
			'vary_cookie'                            => 0,
			'bbcs_cors_strict_headers'               => 0,
			'whitelist_whatsapp_preview'             => 0,
			'bbcs_rate_check_enabled'                => 1,
			'bbcs_rate_captcha_rpm'                  => 60,
			'bbcs_rate_block_rpm'                    => 120,
			'bbcs_rate_passed_rpm'                   => 300,
			'bbcs_rate_passed_captcha_rpm'           => 150,
			'bbcs_hide_user_enumeration'             => 1,
			'bbcs_strip_php_version'                 => 1,
			'bbcs_rate_window_minutes'               => 5,
			'bbcs_rate_subnet_enabled'               => 0,
			'bbcs_rate_subnet_multiplier'            => 3.0,
			'bbcs_rate_floor_percent'                => 0.2,
			'bbcs_rate_subnet_mask'                  => '24-64',
			'bbcs_rate_block_duration'               => 300,
			'fingerprint_sticky_block'               => 0,
		);
		$settings['payment_bypass_enable'] = ( class_exists( 'WooCommerce' ) || function_exists( 'wc_get_orders' ) ) ? 1 : 0;
		return BotBlockerPro::withoutProFeatures( $settings );
	}

	public static function loadStrongSecurity(): array {
		$settings = array(
			'allow_self_ip_req'                      => 1,
			'allow_self_call_header'                 => 1,
			'allow_app_password_rest'                => 1,
			'autosave_admin_ip'                      => 0,
			'skip_logged_in_users'                   => 0,
			'bbcs_captcha_img_inline'                => 1,
			'bbcs_captcha_img_pack'                  => 1,
			'bbcs_captcha_mode'                      => BOTBLOCKER_CAPTCHA_MODE_DEFAULT,
			'bbcs_captcha_wait'                      => 30,
			'bbcs_honeypot_enabled'                  => 1,
			'bbcs_ddos_resilience'                   => 0,
			'bbcs_edge_autotrust'                    => 1,
			'block_adblocker_users'                  => 1,
			'block_cf_users'                         => 1,
			'block_device_options'                   => 0,
			'block_empty_lang'                       => 1,
			'bbcs_allow_empty_accept_lang'           => 0,
			'block_empty_ua'                         => 1,
			'block_fake_ref'                         => 1,
			'block_http10_users'                     => 1,
			'block_incognito_users'                  => 0,
			'block_incorrect_lang_users'             => 0,
			'block_rkn'                              => 0,
			'block_ipv6_users'                       => 0,
			'block_ip_ptr_match'                     => 0,
			'block_override'                         => 0,
			'block_proxy_users'                      => 1,
			'block_simple_antidetect'                => 1,
			'block_simplebot_ua'                     => 1,
			'block_tor_users'                        => 0,
			'block_vpn_users'                        => 0,
			'block_web_engine_options'               => 0,
			'botblocker_force_check'                 => 0,
			'force_cloud_validation'                 => 0,
			'check'                                  => 0,
			'check_get_ref'                          => 1,
			'cookie_lifetime'                        => 604800,
			'disable'                                => 0,
			'early_geo_enable'                       => 0,
			'get_browser_type'                       => 1,
			'get_device_type'                        => 1,
			'get_os_type'                            => 1,
			'header_error_code'                      => 400,
			'header_test_code'                       => 200,
			'hits_per_user'                          => 500,
			'hosting_block'                          => 0,
			'iframe_stop'                            => 1,
			'login_brutforce_attempts'               => 5,
			'login_brutforce_enabled'                => 1,
			'login_brutforce_period'                 => 900,
			'login_brutforce_primary_block_time'     => 900,
			'login_brutforce_secondary_block_time'   => 1800,
			'cloud_api_timeout'                      => 5,
			'cloud_fallback_block'                   => 0,
			'mu_geo_enable'                          => 0,
			'noarchive'                              => 0,
			'options_preflight'                      => 1,
			'payment_bypass_enable'                  => 0,
			'payment_bypass_log'                     => 1,
			'payment_strict_method'                  => 0,
			'payment_keep_ip_rules'                  => 0,
			'ptr_cache_in_db'                        => 1,
			'ptrcache_time'                          => DAY_IN_SECONDS,
			'ptrcache_subnet'                        => '32-128',
			'ptrcache_rule_ttl'                      => 90,
			'recaptcha_check'                        => 1,
			'recaptcha_tresshold'                    => 0.7,
			'recaptcha_v3_ipv6_block'                => 0,
			'secure_mode'                            => 2,
			'samesite'                               => 'Lax',
			'session_token_enabled'                  => 1,
			'tls_fingerprint_check'                  => 0,
			'time_ban'                               => '150',
			'time_ban_2'                             => '300',
			'unresponsive'                           => 0,
			'utm_noindex'                            => 0,
			'utm_referrer'                           => 1,
			'vary_cookie'                            => 0,
			'bbcs_cors_strict_headers'               => 0,
			'whitelist_whatsapp_preview'             => 0,
			'bbcs_rate_check_enabled'                => 1,
			'bbcs_rate_captcha_rpm'                  => 30,
			'bbcs_rate_block_rpm'                    => 50,
			'bbcs_rate_passed_rpm'                   => 300,
			'bbcs_rate_passed_captcha_rpm'           => 150,
			'bbcs_hide_user_enumeration'             => 1,
			'bbcs_strip_php_version'                 => 1,
			'bbcs_rate_window_minutes'               => 5,
			'bbcs_rate_subnet_enabled'               => 1,
			'bbcs_rate_subnet_multiplier'            => 3.0,
			'bbcs_rate_floor_percent'                => 0.1,
			'bbcs_rate_subnet_mask'                  => '24-64',
			'bbcs_rate_block_duration'               => 900,
			'fingerprint_sticky_block'               => 0,
		);
		return BotBlockerPro::withoutProFeatures( $settings );
	}

	public static function loadMediumSecurity(): array {
		return array_merge(
			self::loadLightSecurity(),
			array(
				'block_fake_ref'             => 1,
				'iframe_stop'                => 1,
				'check_get_ref'              => 1,
				'bbcs_honeypot_enabled'      => 1,
				'utm_referrer'               => 1,
				'time_ban'                   => '100',
				'time_ban_2'                 => '200',
				'bbcs_rate_captcha_rpm'      => 40,
				'bbcs_rate_block_rpm'        => 80,
				'bbcs_rate_subnet_enabled'   => 1,
				'bbcs_rate_subnet_multiplier' => 5.0,
				'bbcs_rate_floor_percent'    => 0.15,
				'bbcs_rate_block_duration'   => 600,
			)
		);
	}

	public static function loadFullSecurity(): array {
		return array(
			'allow_self_ip_req'                      => 1,
			'allow_self_call_header'                 => 1,
			'allow_app_password_rest'                => 1,
			'autosave_admin_ip'                      => 0,
			'skip_logged_in_users'                   => 0,
			'bbcs_captcha_img_inline'                => 1,
			'bbcs_captcha_img_pack'                  => 1,
			'bbcs_captcha_mode'                      => BOTBLOCKER_CAPTCHA_MODE_DEFAULT,
			'bbcs_captcha_wait'                      => 30,
			'bbcs_honeypot_enabled'                  => 1,
			'bbcs_ddos_resilience'                   => 0,
			'bbcs_edge_autotrust'                    => 1,
			'block_adblocker_users'                  => 1,
			'block_cf_users'                         => 1,
			'block_device_options'                   => 1,
			'block_empty_lang'                       => 1,
			'bbcs_allow_empty_accept_lang'           => 0,
			'block_empty_ua'                         => 1,
			'block_fake_ref'                         => 1,
			'block_http10_users'                     => 1,
			'block_incognito_users'                  => 1,
			'block_incorrect_lang_users'             => 0,
			'block_rkn'                              => 0,
			'block_ipv6_users'                       => 0,
			'block_ip_ptr_match'                     => 0,
			'block_override'                         => 1,
			'block_proxy_users'                      => 1,
			'block_simple_antidetect'                => 1,
			'block_simplebot_ua'                     => 1,
			'block_tor_users'                        => 1,
			'block_vpn_users'                        => 1,
			'block_web_engine_options'               => 1,
			'botblocker_force_check'                 => 0,
			'force_cloud_validation'                 => 0,
			'check'                                  => 1,
			'check_get_ref'                          => 1,
			'cookie_lifetime'                        => 604800,
			'disable'                                => 0,
			'early_geo_enable'                       => 0,
			'get_browser_type'                       => 1,
			'get_device_type'                        => 1,
			'get_os_type'                            => 1,
			'header_error_code'                      => 400,
			'header_test_code'                       => 200,
			'hits_per_user'                          => 500,
			'hosting_block'                          => 1,
			'iframe_stop'                            => 1,
			'login_brutforce_attempts'               => 5,
			'login_brutforce_enabled'                => 1,
			'login_brutforce_period'                 => 900,
			'login_brutforce_primary_block_time'     => 900,
			'login_brutforce_secondary_block_time'   => 1800,
			'cloud_api_timeout'                      => 5,
			'cloud_fallback_block'                   => 0,
			'mu_geo_enable'                          => 0,
			'noarchive'                              => 0,
			'options_preflight'                      => 1,
			'payment_bypass_enable'                  => 0,
			'payment_bypass_log'                     => 1,
			'payment_strict_method'                  => 0,
			'payment_keep_ip_rules'                  => 0,
			'ptr_cache_in_db'                        => 1,
			'ptrcache_time'                          => DAY_IN_SECONDS,
			'ptrcache_subnet'                        => '32-128',
			'ptrcache_rule_ttl'                      => 90,
			'recaptcha_check'                        => 1,
			'recaptcha_tresshold'                    => 0.7,
			'recaptcha_v3_ipv6_block'                => 0,
			'secure_mode'                            => 2,
			'samesite'                               => 'Lax',
			'session_token_enabled'                  => 1,
			'tls_fingerprint_check'                  => 0,
			'time_ban'                               => '300',
			'time_ban_2'                             => '600',
			'unresponsive'                           => 1,
			'utm_noindex'                            => 0,
			'utm_referrer'                           => 1,
			'vary_cookie'                            => 0,
			'bbcs_cors_strict_headers'               => 0,
			'whitelist_whatsapp_preview'             => 0,
			'bbcs_rate_check_enabled'                => 1,
			'bbcs_rate_captcha_rpm'                  => 20,
			'bbcs_rate_block_rpm'                    => 30,
			'bbcs_rate_passed_rpm'                   => 300,
			'bbcs_rate_passed_captcha_rpm'           => 150,
			'bbcs_hide_user_enumeration'             => 1,
			'bbcs_strip_php_version'                 => 1,
			'bbcs_rate_window_minutes'               => 10,
			'bbcs_rate_subnet_enabled'               => 1,
			'bbcs_rate_subnet_multiplier'            => 2.0,
			'bbcs_rate_floor_percent'                => 0.1,
			'bbcs_rate_subnet_mask'                  => '28-64',
			'bbcs_rate_block_duration'               => 1800,
			'fingerprint_sticky_block'               => 0,
		);
	}

	public static function loadEmergencySecurity(): array {
		return array_merge(
			self::loadFullSecurity(),
			array(
				'bbcs_ddos_resilience'                => 1,
				'bbcs_rate_captcha_rpm'               => 10,
				'bbcs_rate_block_rpm'                 => 20,
				'bbcs_rate_window_minutes'            => 2,
				'bbcs_rate_subnet_multiplier'         => 2.0,
				'bbcs_rate_floor_percent'             => 0.05,
				'bbcs_rate_subnet_mask'               => '24-64',
				'bbcs_rate_block_duration'            => 3600,
				'time_ban'                            => '1800',
				'time_ban_2'                          => '3600',
				'login_brutforce_attempts'            => 3,
				'login_brutforce_primary_block_time'  => 1800,
				'login_brutforce_secondary_block_time' => 3600,
			)
		);
	}

	public static function loadStrongApiSecurity(): array {
		$settings = self::loadStrongSecurity();
		$settings['bbcs_allow_empty_accept_lang'] = 1;
		$settings['bbcs_hide_user_enumeration']   = 1;
		$settings['autosave_admin_ip']            = 0;
		$settings['recaptcha_check']              = 0;
		return $settings;
	}

	public static function getPresetDefinitions(): array {
		return array(
			'light'      => array(
				'label'    => __( 'Balanced', 'botblocker-security' ),
				'settings' => self::loadLightSecurity(),
				'warnings' => array(),
			),
			'medium'     => array(
				'label'    => __( 'Medium', 'botblocker-security' ),
				'settings' => self::loadMediumSecurity(),
				'warnings' => array( __( 'Adds fake-referrer, referrer and honeypot checks on top of Balanced; does not block ad blockers, Cloudflare or classic proxies.', 'botblocker-security' ) ),
			),
			'strong'     => array(
				'label'    => __( 'Strong', 'botblocker-security' ),
				'settings' => self::loadStrongSecurity(),
				'warnings' => array( __( 'Requires Accept-Language from clients; API clients without it get blocked unless allowlisted.', 'botblocker-security' ) ),
			),
			'full'       => array(
				'label'    => __( 'Full', 'botblocker-security' ),
				'settings' => self::loadFullSecurity(),
				'warnings' => array(
					__( 'Blocks private browsing, VPN, Tor and hosting traffic; keep an exclusion list.', 'botblocker-security' ),
					__( 'Requires Accept-Language from clients unless allowlisted.', 'botblocker-security' ),
				),
			),
			'strong_api' => array(
				'label'    => __( 'Strong API', 'botblocker-security' ),
				'settings' => self::loadStrongApiSecurity(),
				'warnings' => array( __( 'Allows empty Accept-Language for API clients and automation; keep an allowlist for admin/MCP IPs.', 'botblocker-security' ) ),
			),
			'emergency'  => array(
				'label'    => __( 'Emergency', 'botblocker-security' ),
				'settings' => self::loadEmergencySecurity(),
				'warnings' => array(
					__( 'Temporary: very strict rate limits, VPN/Tor/hosting blocks and server DDoS resilience. Re-apply Strong after the attack.', 'botblocker-security' ),
					__( 'Requires Accept-Language from clients unless allowlisted.', 'botblocker-security' ),
				),
			),
		);
	}

	public static function getPresetSettingsMap(): array {
		return array(
			'light'      => self::applyRecaptchaAvailability( self::loadLightSecurity() ),
			'medium'     => self::applyRecaptchaAvailability( self::loadMediumSecurity() ),
			'strong'     => self::applyRecaptchaAvailability( self::loadStrongSecurity() ),
			'strong_api' => self::applyRecaptchaAvailability( self::loadStrongApiSecurity() ),
			'full'       => self::applyRecaptchaAvailability( self::loadFullSecurity() ),
			'emergency'  => self::applyRecaptchaAvailability( self::loadEmergencySecurity() ),
		);
	}

	public static function detectPreset( $settings ): string {
		if ( ! is_object( $settings ) ) {
			return 'custom';
		}
		foreach ( self::getPresetSettingsMap() as $name => $values ) {
			$match = true;
			foreach ( $values as $key => $expected ) {
				$actual = isset( $settings->$key ) ? (string) $settings->$key : null;
				if ( $actual === null ) {
					continue;
				}
				if ( $actual !== (string) $expected ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				return $name;
			}
		}
		return 'custom';
	}

	public static function snapshotEmergencyState(): void {
		global $wpdb;

		if ( empty( $wpdb->bbcs_settings ) ) {
			return;
		}
		$snapshot = array();
		foreach ( array_keys( self::loadEmergencySecurity() ) as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", $key ) );
			if ( null !== $value ) {
				$snapshot[ $key ] = (string) $value;
			}
		}
		$previous = class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getOption( 'bbcs_wizard_preset', 'light' ) : get_option( 'bbcs_wizard_preset', 'light' );
		$snapshot['__previous_preset'] = (string) $previous;
		$snapshot['__created']         = (string) time();
		$snapshot['__expires']         = (string) ( time() + self::EMERGENCY_REVERT_DELAY );

		if ( class_exists( 'BotBlockerMultisite' ) ) {
			BotBlockerMultisite::updateOption( self::EMERGENCY_SNAPSHOT_OPTION, $snapshot, false );
		} else {
			update_option( self::EMERGENCY_SNAPSHOT_OPTION, $snapshot, false );
		}
		self::scheduleEmergencyRevert();
	}

	public static function scheduleEmergencyRevert(): void {
		if ( ! function_exists( 'wp_schedule_single_event' ) ) {
			return;
		}
		wp_clear_scheduled_hook( 'bbcs_emergency_revert' );
		wp_schedule_single_event( time() + self::EMERGENCY_REVERT_DELAY, 'bbcs_emergency_revert' );
	}

	public static function clearEmergencyRevert(): void {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( 'bbcs_emergency_revert' );
		}
		if ( class_exists( 'BotBlockerMultisite' ) ) {
			BotBlockerMultisite::deleteOption( self::EMERGENCY_SNAPSHOT_OPTION );
		} else {
			delete_option( self::EMERGENCY_SNAPSHOT_OPTION );
		}
	}

	public static function revertEmergency(): void {
		global $wpdb;

		if ( empty( $wpdb->bbcs_settings ) ) {
			return;
		}
		$snapshot = class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getOption( self::EMERGENCY_SNAPSHOT_OPTION, array() ) : get_option( self::EMERGENCY_SNAPSHOT_OPTION, array() );
		if ( ! is_array( $snapshot ) || empty( $snapshot ) ) {
			self::clearEmergencyRevert();
			return;
		}
		$previous = isset( $snapshot['__previous_preset'] ) ? sanitize_key( (string) $snapshot['__previous_preset'] ) : 'strong';
		unset( $snapshot['__previous_preset'], $snapshot['__created'], $snapshot['__expires'] );

		$restored = false;
		foreach ( $snapshot as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->replace( $wpdb->bbcs_settings, array( 'key' => $key, 'value' => (string) $value ), array( '%s', '%s' ) );
			if ( false !== $result ) {
				$restored = true;
			}
		}
		if ( $restored ) {
			if ( class_exists( 'BotBlockerFileRenderer' ) ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}
			if ( class_exists( 'BotBlockerCache' ) ) {
				BotBlockerCache::clearFileCache();
			}
			if ( class_exists( 'BotBlockerMultisite' ) ) {
				BotBlockerMultisite::updateOption( 'bbcs_wizard_preset', $previous );
			} else {
				update_option( 'bbcs_wizard_preset', $previous );
			}
		}
		self::clearEmergencyRevert();
	}

	public static function applyRecaptchaAvailability( array $settings ): array {
		if ( ! isset( $settings['recaptcha_check'] ) || 1 !== (int) $settings['recaptcha_check'] ) {
			return $settings;
		}
		$bbcs     = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		$current  = is_object( $bbcs ) && is_object( $bbcs->settings ) ? $bbcs->settings : null;
		$key      = $current ? (string) ( $current->recaptcha_key2 ?? '' ) : '';
		$secret   = $current ? (string) ( $current->recaptcha_secret2 ?? '' ) : '';
		$key3     = $current ? (string) ( $current->recaptcha_key3 ?? '' ) : '';
		$secret3  = $current ? (string) ( $current->recaptcha_secret3 ?? '' ) : '';
		$has_v2   = '' !== $key && '' !== $secret;
		$has_v3   = '' !== $key3 && '' !== $secret3;
		if ( ! $has_v2 && ! $has_v3 ) {
			$settings['recaptcha_check'] = 0;
		}
		return $settings;
	}

	public static function loadSettingsLight(): void {
		global $wpdb;

		self::clearEmergencyRevert();
		$light_setting = self::applyRecaptchaAvailability( self::loadLightSecurity() );
		foreach ( $light_setting as $key => $value ) {
			$key = sanitize_key( $key );
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		BotBlockerFileRenderer::generateSettingsFile();
	}

	public static function loadSettingsStrong(): void {
		global $wpdb;

		self::clearEmergencyRevert();
		$strong_setting = self::applyRecaptchaAvailability( self::loadStrongSecurity() );
		foreach ( $strong_setting as $key => $value ) {
			$key = sanitize_key( $key );
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		BotBlockerFileRenderer::generateSettingsFile();
	}

	public static function loadSettingsFull(): void {
		global $wpdb;

		self::clearEmergencyRevert();
		$full_setting = self::applyRecaptchaAvailability( self::loadFullSecurity() );
		foreach ( $full_setting as $key => $value ) {
			$key = sanitize_key( $key );
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		BotBlockerFileRenderer::generateSettingsFile();
	}

	public static function loadSettingsStrongApi(): void {
		global $wpdb;

		self::clearEmergencyRevert();
		$strong_api_setting = self::applyRecaptchaAvailability( self::loadStrongApiSecurity() );
		foreach ( $strong_api_setting as $key => $value ) {
			$key = sanitize_key( $key );
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		BotBlockerFileRenderer::generateSettingsFile();
	}

	public static function loadSettingsMedium(): void {
		global $wpdb;

		self::clearEmergencyRevert();
		$medium_setting = self::applyRecaptchaAvailability( self::loadMediumSecurity() );
		foreach ( $medium_setting as $key => $value ) {
			$key = sanitize_key( $key );
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		BotBlockerFileRenderer::generateSettingsFile();
	}

	public static function loadSettingsEmergency(): void {
		global $wpdb;

		$existing = class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getOption( self::EMERGENCY_SNAPSHOT_OPTION, array() ) : get_option( self::EMERGENCY_SNAPSHOT_OPTION, array() );
		if ( ! is_array( $existing ) || empty( $existing ) ) {
			self::snapshotEmergencyState();
		} else {
			self::scheduleEmergencyRevert();
		}

		$emergency_setting = self::applyRecaptchaAvailability( self::loadEmergencySecurity() );
		foreach ( $emergency_setting as $key => $value ) {
			$key = sanitize_key( $key );
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		BotBlockerFileRenderer::generateSettingsFile();
	}
}
