<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the BOTBLOCKER_GLOBAL_SEARCH_INDEX data structure for the sidebar nav search
 * and the global ⌘K command palette.
 *
 * All user-facing strings use __() for translation.
 * Used by bbcs-snav.js and bbcs-multipage.js via inline script injection.
 *
 * @return array
 */
function bbcs_get_global_search_index(): array {
	static $core_groups = null;

	if ( $core_groups !== null ) {
		return $core_groups;
	}

	/**
	 * Filter: bbcs_global_search_index
	 *
	 * Allows add-ons to inject additional groups/settings into the
	 * global search index (BOTBLOCKER_GLOBAL_SEARCH_INDEX).
	 *
	 * Each group follows this structure:
	 *   array(
	 *     't'    => 'Group Title',
	 *     'ic'   => 'icon-name',
	 *     'go'   => 'page_slug',       // REQUIRED - target admin page
	 *     'tabs' => array(
	 *       array(
	 *         't'   => 'Tab Title',
	 *         'tab' => 'tab-id',
	 *         'go'  => 'page_slug',     // REQUIRED - explicit routing for ⌘K
	 *         'sg'  => array(
	 *           array(
	 *             't' => 'Subgroup Title',
	 *             's' => array(
	 *               array( __( 'Setting Label', 'domain' ), 'form_key' ),  // Tuple [ label, key ]
	 *               …
	 *             ),
	 *           ),
	 *         ),
	 *       ),
	 *     ),
	 *   )
	 *
	 * @param array $groups  Current global search index groups.
	 * @return array
	 */
	$core_groups = array(

		/* ═══════════════  DETECTION  ═══════════════ */
		array(
			't'  => __( 'Detection', 'botblocker-security' ),
			'ic' => 'search',
			'go' => 'settings',
			'tabs' => array(
				array(
					't'   => __( 'Bot Detection', 'botblocker-security' ),
					'tab' => 'simple-detection',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Simple bot detection', 'botblocker-security' ),
							's' => array(
								array( __( 'Empty User-Agent', 'botblocker-security' ), 'block_empty_ua' ),
								array( __( 'User-Agent Anomalies', 'botblocker-security' ), 'block_simplebot_ua' ),
								array( __( 'Empty Accept-Language', 'botblocker-security' ), 'block_empty_lang' ),
								array( __( 'Allow Empty Accept-Language in Verification', 'botblocker-security' ), 'bbcs_allow_empty_accept_lang' ),
								array( __( 'No JavaScript Support', 'botblocker-security' ), 'block_nojs_users' ),
								array( __( 'Fake Referer', 'botblocker-security' ), 'block_fake_ref' ),
							),
						),
						array(
							't' => __( 'PTR Options', 'botblocker-security' ),
							's' => array(
								array( __( 'PTR / DNS Anomalies', 'botblocker-security' ), 'block_ip_ptr_match' ),
							),
						),
						array(
							't' => __( 'Extra Options', 'botblocker-security' ),
							's' => array(
								array( __( 'Allow OPTIONS Preflight', 'botblocker-security' ), 'options_preflight' ),
								array( __( 'Geo IP / Language Mismatch', 'botblocker-security' ), 'block_incorrect_lang_users' ),
								array( __( 'Whitelist WhatsApp Preview', 'botblocker-security' ), 'whitelist_whatsapp_preview' ),
								array( __( 'Block RKN', 'botblocker-security' ), 'block_rkn' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Browser & Plugins', 'botblocker-security' ),
					'tab' => 'browser-plugins',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Browser Modes', 'botblocker-security' ),
							's' => array(
								array( __( 'Incognito / Private', 'botblocker-security' ), 'block_incognito_users' ),
							),
						),
						array(
							't' => __( 'Browser Plugins', 'botblocker-security' ),
							's' => array(
								array( __( 'AdBlock / uBlock', 'botblocker-security' ), 'block_adblocker_users' ),
							),
						),
						array(
							't' => __( 'Browser Options', 'botblocker-security' ),
							's' => array(
								array( __( 'Simple JS Consistency', 'botblocker-security' ), 'block_simple_antidetect' ),
								array( __( 'Override Detection', 'botblocker-security' ), 'block_override' ),
								array( __( 'Engine Parameter Checks', 'botblocker-security' ), 'block_web_engine_options' ),
								array( __( 'Device API Verification', 'botblocker-security' ), 'block_device_options' ),
							),
						),
					),
				),
				array(
					't'   => __( 'TLS Fingerprinting', 'botblocker-security' ),
					'tab' => 'tls_fingerprint',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'TLS Fingerprint Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable TLS Fingerprint Check', 'botblocker-security' ), 'tls_fingerprint_check' ),
							),
						),
						array(
							't' => __( 'Header Configuration', 'botblocker-security' ),
							's' => array(
								array( __( 'JA3 Header Name', 'botblocker-security' ), 'tls_fingerprint_header_ja3' ),
								array( __( 'JA4 Header Name', 'botblocker-security' ), 'tls_fingerprint_header_ja4' ),
							),
						),
						array(
							't' => __( 'Trusted Proxy', 'botblocker-security' ),
							's' => array(
								array( __( 'Trusted Proxy IP/CIDR', 'botblocker-security' ), 'tls_fingerprint_trusted_proxy' ),
							),
						),
						array(
							't' => __( 'Fingerprint Database', 'botblocker-security' ),
							's' => array(
								array( __( 'Import JSON', 'botblocker-security' ), 'bbcs_tls_import' ),
								array( __( 'Clear All', 'botblocker-security' ), 'bbcs_tls_clear' ),
								array( __( 'Sync Now', 'botblocker-security' ), 'bbcs_tls_sync' ),
							),
						),
						array(
							't' => __( 'Diagnostics', 'botblocker-security' ),
							's' => array(
								array( __( 'Current JA3', 'botblocker-security' ), 'current_ja3' ),
								array( __( 'Current JA4', 'botblocker-security' ), 'current_ja4' ),
							),
						),
					),
				),
			),
		),

		/* ═══════════════  TRAFFIC  ═══════════════ */
		array(
			't'  => __( 'Traffic', 'botblocker-security' ),
			'ic' => 'traffic',
			'go' => 'settings',
			'tabs' => array(
				array(
					't'   => __( 'Connection Types', 'botblocker-security' ),
					'tab' => 'connection-types',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Connection Types', 'botblocker-security' ),
							's' => array(
								array( __( 'Classic Proxy', 'botblocker-security' ), 'block_proxy_users' ),
								array( __( 'Cloudflare Origin IPs', 'botblocker-security' ), 'block_cf_users' ),
								array( __( 'IPv6 Connections', 'botblocker-security' ), 'block_ipv6_users' ),
								array( __( 'HTTP/1.0 Protocol', 'botblocker-security' ), 'block_http10_users' ),
							),
						),
						array(
							't' => __( 'Extra Connection Types', 'botblocker-security' ),
							's' => array(
								array( __( 'Hosting Provider IPs', 'botblocker-security' ), 'hosting_block' ),
								array( __( 'VPN Connections', 'botblocker-security' ), 'block_vpn_users' ),
								array( __( 'Tor Exit Nodes', 'botblocker-security' ), 'block_tor_users' ),
							),
						),
						array(
							't' => __( 'Self Connections', 'botblocker-security' ),
							's' => array(
								array( __( 'Allow requests from your server IP', 'botblocker-security' ), 'allow_self_ip_req' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Rate Limiting', 'botblocker-security' ),
					'tab' => 'rate-limiting',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Rate Limiting', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable Rate Limiting', 'botblocker-security' ), 'bbcs_rate_check_enabled' ),
								array( __( 'Captcha Threshold', 'botblocker-security' ), 'bbcs_rate_captcha_rpm' ),
								array( __( 'Block Threshold', 'botblocker-security' ), 'bbcs_rate_block_rpm' ),
								array( __( 'Block Time', 'botblocker-security' ), 'bbcs_rate_block_duration' ),
								array( __( 'Window', 'botblocker-security' ), 'bbcs_rate_window_minutes' ),
							),
						),
						array(
							't' => __( 'Subnet Aggregation', 'botblocker-security' ),
							's' => array(
								array( __( 'Subnet Aggregation', 'botblocker-security' ), 'bbcs_rate_subnet_enabled' ),
								array( __( 'Subnet Multiplier', 'botblocker-security' ), 'bbcs_rate_subnet_multiplier' ),
								array( __( 'Floor %', 'botblocker-security' ), 'bbcs_rate_floor_percent' ),
								array( __( 'Subnet Mask', 'botblocker-security' ), 'bbcs_rate_subnet_mask' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Traffic & Referrer', 'botblocker-security' ),
					'tab' => 'traffic',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Traffic and Referrer Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'UTM Referrer Processing', 'botblocker-security' ), 'utm_referrer' ),
								array( __( 'Check GET Parameters in Referrer', 'botblocker-security' ), 'check_get_ref' ),
								array( __( 'Block Cross-Origin Iframes', 'botblocker-security' ), 'iframe_stop' ),
							),
						),
						array(
							't' => __( 'Header Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Add noarchive to Blocked Pages', 'botblocker-security' ), 'noarchive' ),
								array( __( 'Add noindex to UTM Pages', 'botblocker-security' ), 'utm_noindex' ),
							),
						),
					),
				),
			),
		),

		/* ═══════════════  ACCESS CONTROL  ═══════════════ */
		array(
			't'  => __( 'Access Control', 'botblocker-security' ),
			'ic' => 'lock',
			'go' => 'settings',
			'tabs' => array(
				array(
					't'   => __( 'Login Brute-Force', 'botblocker-security' ),
					'tab' => 'brute-force',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Login Brute-Force Protection', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable Login Brute-Force Protection', 'botblocker-security' ), 'login_brutforce_enabled' ),
								array( __( 'Failed Attempts Before Blocking', 'botblocker-security' ), 'login_brutforce_attempts' ),
								array( __( 'Failed Attempt Time Window (seconds)', 'botblocker-security' ), 'login_brutforce_period' ),
								array( __( 'Primary Block Time (seconds)', 'botblocker-security' ), 'login_brutforce_primary_block_time' ),
								array( __( 'Secondary Block Time (seconds)', 'botblocker-security' ), 'login_brutforce_secondary_block_time' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Captcha', 'botblocker-security' ),
					'tab' => 'captcha',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'BotBlocker Captcha', 'botblocker-security' ),
							's' => array(
								array( __( 'Captcha Mode', 'botblocker-security' ), 'bbcs_captcha_mode' ),
								array( __( 'Image Delivery Mode', 'botblocker-security' ), 'bbcs_captcha_img_inline' ),
								array( __( 'Image Captcha Pack', 'botblocker-security' ), 'bbcs_captcha_img_pack' ),
								array( __( 'Captcha Timeout (seconds)', 'botblocker-security' ), 'bbcs_captcha_wait' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Payment Gateways', 'botblocker-security' ),
					'tab' => 'payment',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Payment Gateways', 'botblocker-security' ),
							's' => array(
								array( __( 'Allow Payment Gateway Callbacks', 'botblocker-security' ), 'payment_bypass_enable' ),
								array( __( 'Log Payment Bypass Events', 'botblocker-security' ), 'payment_bypass_log' ),
								array( __( 'Strict Webhook Validation (POST only)', 'botblocker-security' ), 'payment_strict_method' ),
								array( __( 'Enforce IP / ASN Rules for Payment Callbacks', 'botblocker-security' ), 'payment_keep_ip_rules' ),
							),
						),
					),
				),
			),
		),

		/* ═══════════════  DATA  ═══════════════ */
		array(
			't'  => __( 'Data', 'botblocker-security' ),
			'ic' => 'database',
			'go' => 'settings',
			'tabs' => array(
				array(
					't'   => __( 'Data Log & Processing', 'botblocker-security' ),
					'tab' => 'data-log',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Data Log and Processing', 'botblocker-security' ),
							's' => array(
								array( __( 'Record Browser Type', 'botblocker-security' ), 'get_browser_type' ),
								array( __( 'Record OS Type', 'botblocker-security' ), 'get_os_type' ),
								array( __( 'Record Device Type', 'botblocker-security' ), 'get_device_type' ),
								array( __( 'Daylight Saving Time', 'botblocker-security' ), 'daylight_saving_time' ),
								array( __( 'Log Retention Period', 'botblocker-security' ), 'admin_store_period' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Logging', 'botblocker-security' ),
					'tab' => 'log',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Visitor Logging Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Log Manual Verification Requests', 'botblocker-security' ), 'botblocker_log_tests' ),
								array( __( 'Log Verified Local Visitors', 'botblocker-security' ), 'botblocker_log_local' ),
								array( __( 'Log Allowed Visitors', 'botblocker-security' ), 'botblocker_log_allow' ),
								array( __( 'Log Suspected Fake Bots', 'botblocker-security' ), 'botblocker_log_fake' ),
								array( __( 'Log Known Good IPs', 'botblocker-security' ), 'botblocker_log_goodip' ),
								array( __( 'Log Blocked Visitors', 'botblocker-security' ), 'botblocker_log_block' ),
							),
						),
						array(
							't' => __( 'Admin and WordPress Logging', 'botblocker-security' ),
							's' => array(
								array( __( 'Log Actions in WordPress Admin Panel', 'botblocker-security' ), 'botblocker_log_admin' ),
								array( __( 'Log BotBlocker Page Visits', 'botblocker-security' ), 'botblocker_log_bbcs' ),
								array( __( 'Log WordPress Actions', 'botblocker-security' ), 'botblocker_log_wp' ),
							),
						),
						array(
							't' => __( 'Error Logging', 'botblocker-security' ),
							's' => array(
								array( __( 'Log BotBlocker Errors', 'botblocker-security' ), 'botblocker_log_error' ),
								array( __( 'Log CLI requests', 'botblocker-security' ), 'botblocker_log_cli' ),
								array( __( 'Log Visits When BotBlocker Protection is Disabled', 'botblocker-security' ), 'botblocker_log_disabled' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Cron Jobs', 'botblocker-security' ),
					'tab' => 'cron',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'WP Cron', 'botblocker-security' ),
							's' => array(
								array( __( 'WP Cron Enabled', 'botblocker-security' ), 'wp_cron_enabled' ),
								array( __( 'cURL Command', 'botblocker-security' ), 'cron_curl' ),
								array( __( 'Wget Command', 'botblocker-security' ), 'cron_wget' ),
							),
						),
						array(
							't' => __( 'Task Recovery', 'botblocker-security' ),
							's' => array(
								array( __( 'Automatic task recovery', 'botblocker-security' ), 'cron-task-recovery-auto' ),
								array( __( 'Fallback check every 15 minutes', 'botblocker-security' ), 'cron-task-recovery-fallback' ),
							),
						),
						array(
							't' => __( 'Task List', 'botblocker-security' ),
							's' => array(
								array( __( 'Task List Table', 'botblocker-security' ), 'task-list-table' ),
							),
						),
					),
				),
			),
		),

		/* ═══════════════  SYSTEM  ═══════════════ */
		array(
			't'  => __( 'System', 'botblocker-security' ),
			'ic' => 'system',
			'go' => 'settings',
			'tabs' => array(
				array(
					't'   => __( 'Early Phase', 'botblocker-security' ),
					'tab' => 'performance',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Early Phase toggles', 'botblocker-security' ),
							's' => array(
								array( __( 'Early Init', 'botblocker-security' ), 'early_init_enable' ),
								array( __( 'MU-plugin', 'botblocker-security' ), 'mu_enable' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Cookie Settings', 'botblocker-security' ),
					'tab' => 'cookie',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Cookie Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Cookie Name', 'botblocker-security' ), 'cookie' ),
								array( __( 'Cookie SameSite Policy', 'botblocker-security' ), 'samesite' ),
								array( __( 'Cookie Lifetime', 'botblocker-security' ), 'cookie_lifetime' ),
								array( __( 'Session Token Verification', 'botblocker-security' ), 'session_token_enabled' ),
								array( __( 'Salt', 'botblocker-security' ), 'salt' ),
							),
						),
						array(
							't' => __( 'Cache Compatibility', 'botblocker-security' ),
							's' => array(
								array( __( 'Cloud API Timeout', 'botblocker-security' ), 'cloud_api_timeout' ),
								array( __( 'Send Vary: Cookie Header', 'botblocker-security' ), 'vary_cookie' ),
								array( __( 'Strict CORS Headers', 'botblocker-security' ), 'bbcs_cors_strict_headers' ),
							),
						),
					),
				),
				array(
					't'   => __( 'General', 'botblocker-security' ),
					'tab' => 'general',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'General', 'botblocker-security' ),
							's' => array(
								array( __( 'Security Check Mode', 'botblocker-security' ), 'secure_mode' ),
								array( __( 'Hits Per User', 'botblocker-security' ), 'hits_per_user' ),
								array( __( 'PTR cache', 'botblocker-security' ), 'ptr_cache_in_db' ),
								array( __( 'PTR Cache Lifetime', 'botblocker-security' ), 'ptrcache_time' ),
								array( __( 'PTR Rule Subnet Mask', 'botblocker-security' ), 'ptrcache_subnet' ),
								array( __( 'PTR Rule Lifetime', 'botblocker-security' ), 'ptrcache_rule_ttl' ),
							),
						),
						array(
							't' => __( 'Administrator Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Auto-save administrator IPs', 'botblocker-security' ), 'autosave_admin_ip' ),
								array( __( 'Skip checks for all logged-in users', 'botblocker-security' ), 'skip_logged_in_users' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Error & Access', 'botblocker-security' ),
					'tab' => 'error',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Error and Access Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Test Response Code', 'botblocker-security' ), 'header_test_code' ),
								array( __( 'Block Response Code', 'botblocker-security' ), 'header_error_code' ),
								array( __( 'Block Time (seconds)', 'botblocker-security' ), 'time_ban' ),
								array( __( 'Repeat Block Time (seconds)', 'botblocker-security' ), 'time_ban_2' ),
							),
						),
						array(
							't' => __( 'Headers for Search Engines', 'botblocker-security' ),
							's' => array(
								array( __( 'X-Robots-Tag directives', 'botblocker-security' ), 'x_robots_directives' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Advanced Protection', 'botblocker-security' ),
					'tab' => 'advanced-protection',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Advanced Protection', 'botblocker-security' ),
							's' => array(
								array( __( 'Cloud Validation', 'botblocker-security' ), 'check' ),
								array( __( 'Block Unresponsive Clients', 'botblocker-security' ), 'unresponsive' ),
								array( __( 'Block on Cloud API Errors', 'botblocker-security' ), 'cloud_fallback_block' ),
								array( __( 'Force Verification for All', 'botblocker-security' ), 'botblocker_force_check' ),
								array( __( 'Server DDoS Protection Support (Experimental)', 'botblocker-security' ), 'bbcs_ddos_resilience' ),
								array( __( 'Force Cloud Validation', 'botblocker-security' ), 'force_cloud_validation' ),
							),
						),
					),
				),
				array(
					't'   => __( 'UI Settings', 'botblocker-security' ),
					'tab' => 'settings-ui',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Interface Caching', 'botblocker-security' ),
							's' => array(
								array( __( 'Cache Plugin Interface', 'botblocker-security' ), 'cache_ui_data' ),
								array( __( 'Cache Duration', 'botblocker-security' ), 'cache_ui_duration' ),
							),
						),
						array(
							't' => __( 'Reports and Statistics', 'botblocker-security' ),
							's' => array(
								array( __( 'Report Period', 'botblocker-security' ), 'admin_report_period' ),
								array( __( 'GMT Offset for Reports', 'botblocker-security' ), 'admin_gmt_offset' ),
								array( __( 'Statistics Display Mode', 'botblocker-security' ), 'admin_uniq_type' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Notifications', 'botblocker-security' ),
					'tab' => 'notifications',
					'go'  => 'settings',
					'sg'  => array(
						array(
							't' => __( 'Notification Types', 'botblocker-security' ),
							's' => array(
								array( __( 'Email', 'botblocker-security' ), 'email_notifications' ),
								array( __( 'Pusher', 'botblocker-security' ), 'pusher_notifications' ),
							),
						),
						array(
							't' => __( 'Notification Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Notify on Critical Server Load', 'botblocker-security' ), 'critical_load_notifications' ),
								array( __( 'Regular Report Frequency', 'botblocker-security' ), 'regular_notifications_frequency' ),
							),
						),
					),
				),
			),
		),

		/* ═══════════════  INTEGRATIONS  ═══════════════ */
		array(
			't'  => __( 'Integrations', 'botblocker-security' ),
			'ic' => 'plug',
			'go' => 'integrations',
			'tabs' => array(
				array(
					't'   => __( 'reCaptcha v2', 'botblocker-security' ),
					'tab' => 'recaptcha-v2',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'reCaptcha v2', 'botblocker-security' ),
							's' => array(
								array( __( 'reCaptcha v2 Site Key', 'botblocker-security' ), 'recaptcha_key2' ),
								array( __( 'reCaptcha v2 Secret Key', 'botblocker-security' ), 'recaptcha_secret2' ),
							),
						),
					),
				),
				array(
					't'   => __( 'reCaptcha v3', 'botblocker-security' ),
					'tab' => 'recaptcha-v3',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'reCaptcha v3', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable reCaptcha v3 protection', 'botblocker-security' ), 'recaptcha_check' ),
								array( __( 'Block IPv6 connections', 'botblocker-security' ), 'recaptcha_v3_ipv6_block' ),
								array( __( 'reCaptcha v3 Site Key', 'botblocker-security' ), 'recaptcha_key3' ),
								array( __( 'reCaptcha v3 Secret Key', 'botblocker-security' ), 'recaptcha_secret3' ),
								array( __( 'reCaptcha Threshold Level', 'botblocker-security' ), 'recaptcha_tresshold' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Memcached', 'botblocker-security' ),
					'tab' => 'memcached',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'Memcached Cache Integration', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable Memcached counters', 'botblocker-security' ), 'memcached_enable' ),
								array( __( 'Memcached Host Address', 'botblocker-security' ), 'memcached_host' ),
								array( __( 'Memcached Port Number', 'botblocker-security' ), 'memcached_port' ),
								array( __( 'Cache Key Prefix', 'botblocker-security' ), 'memcached_prefix' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Redis', 'botblocker-security' ),
					'tab' => 'redis',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'Redis Cache Integration', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable Redis counters', 'botblocker-security' ), 'redis_enable' ),
								array( __( 'Redis Server Host', 'botblocker-security' ), 'redis_host' ),
								array( __( 'Redis Server Port', 'botblocker-security' ), 'redis_port' ),
								array( __( 'Redis Database Index', 'botblocker-security' ), 'redis_database' ),
								array( __( 'Redis Authentication Password', 'botblocker-security' ), 'redis_password' ),
								array( __( 'Redis Key Prefix', 'botblocker-security' ), 'redis_prefix' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Cache', 'botblocker-security' ),
					'tab' => 'cache',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'Cache Compatibility', 'botblocker-security' ),
							's' => array(
								array( __( 'Cache Compatibility Guide', 'botblocker-security' ), 'cache_compat_guide' ),
							),
						),
					),
				),
				array(
					't'   => __( 'BotBlocker Cloud', 'botblocker-security' ),
					'tab' => 'cloud',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'BotBlocker API Integration', 'botblocker-security' ),
							's' => array(
								array( __( 'BotBlocker API URL', 'botblocker-security' ), 'bbcs_api_url' ),
								array( __( 'Additional API URL', 'botblocker-security' ), 'bbcs_api_gs_url' ),
							),
						),
					),
				),
				array(
					't'   => __( 'BotBlocker 2FA', 'botblocker-security' ),
					'tab' => 'bbcs-2fa',
					'go'  => 'integrations',
					'sg'  => array(
						array(
							't' => __( 'Two-Factor Authentication', 'botblocker-security' ),
							's' => array(
								array( __( 'Enable Two-Factor Authentication', 'botblocker-security' ), 'bbcs_2fa_enable' ),
								array( __( 'Roles with 2FA', 'botblocker-security' ), 'bbcs_2fa_roles' ),
							),
						),
						array(
							't' => __( '2FA Setup', 'botblocker-security' ),
							's' => array(
								array( __( 'Scan QR Code', 'botblocker-security' ), 'bbcs_2fa_qr' ),
								array( __( 'Enter 6-digit code from app', 'botblocker-security' ), 'bbcs_2fa_code' ),
								array( __( 'Scan with authenticator app', 'botblocker-security' ), 'bbcs_2fa_app' ),
								array( __( 'Reset 2FA', 'botblocker-security' ), 'bbcs_2fa_reset' ),
							),
						),
					),
				),
			),
		),

		/* ═══════════════  TOOLS  ═══════════════ */
		array(
			't'  => __( 'Tools', 'botblocker-security' ),
			'ic' => 'broom',
			'go' => 'tools',
			'tabs' => array(
				array(
					't'   => __( 'WordPress Core', 'botblocker-security' ),
					'tab' => 'WordPress',
					'go'  => 'tools',
					'sg'  => array(
						array(
							't' => __( 'WordPress Service', 'botblocker-security' ),
							's' => array(
								array( __( 'Site Health', 'botblocker-security' ), 'site_health' ),
								array( __( 'Clear Debug Log', 'botblocker-security' ), 'clear_wp_log' ),
								array( __( 'Download Debug Log', 'botblocker-security' ), 'download_wp_log' ),
							),
						),
					),
				),
				array(
					't'   => __( 'BotBlocker', 'botblocker-security' ),
					'tab' => 'BotBlocker',
					'go'  => 'tools',
					'sg'  => array(
						array(
							't' => __( 'BotBlocker Settings', 'botblocker-security' ),
							's' => array(
								array( __( 'Export data and settings', 'botblocker-security' ), 'bbcs-backup-data-settings' ),
								array( __( 'Import data and settings', 'botblocker-security' ), 'bbcs-import-data-settings' ),
							),
						),
					),
				),
				array(
					't'   => __( 'Maintenance', 'botblocker-security' ),
					'tab' => 'Maintenance',
					'go'  => 'tools',
					'sg'  => array(
						array(
							't' => __( 'Database', 'botblocker-security' ),
							's' => array(
								array( __( 'Reinstall Database', 'botblocker-security' ), 'bbcs-reinstall-database' ),
								array( __( 'Repair and Optimize Database', 'botblocker-security' ), 'bbcs-db-repair-info' ),
								array( __( 'Clear All Visitor Data', 'botblocker-security' ), 'bbcs-clear-hits-database' ),
								array( __( 'Clear transients', 'botblocker-security' ), 'bbcs_clear_transients' ),
								array( __( 'Update ASN database', 'botblocker-security' ), 'bbcs-update-asn-database' ),
								array( __( 'Update RU-Gov list', 'botblocker-security' ), 'bbcs_update_rugov' ),
								array( __( 'Sync LLM providers', 'botblocker-security' ), 'bbcs_sync_llm' ),
							),
						),
						array(
							't' => __( 'Features', 'botblocker-security' ),
							's' => array(
								array( __( 'Clear visitor cookies', 'botblocker-security' ), 'bbcs_clear_cookies' ),
								array( __( 'Reset URL rewrite rules', 'botblocker-security' ), 'bbcs-flush-rewrite-rules' ),
								array( __( 'Clear Object Cache', 'botblocker-security' ), 'bbcs-flush-object-cache' ),
							),
						),
					),
				),
			),
		),

	);

	$core_groups = apply_filters( 'bbcs_global_search_index', $core_groups );

	return $core_groups;
}

/**
 * Centralized setting registry: flat hashmap of every setting key → route info.
 *
 * Built once from bbcs_get_global_search_index() and statically cached.
 * All routing functions (bbcs_get_setting_link, bbcs_get_health_key_tab_map,
 * bbcs_find_tab_by_health_key) derive their data from this single source.
 *
 * @return array<string, array{page: string, tab: string, label: string}>
 */
function bbcs_get_setting_index(): array {
	static $index = null;

	if ( $index !== null ) {
		return $index;
	}

	$index = array();
	$tree  = bbcs_get_global_search_index();

	foreach ( $tree as $group ) {
		$group_go = $group['go'] ?? 'settings';
		if ( empty( $group['tabs'] ) || ! is_array( $group['tabs'] ) ) {
			continue;
		}
		foreach ( $group['tabs'] as $tab ) {
			$tab_go = $tab['go'] ?? $group_go;
			$tab_id = $tab['tab'] ?? '';
			if ( empty( $tab['sg'] ) || ! is_array( $tab['sg'] ) ) {
				continue;
			}
			foreach ( $tab['sg'] as $sg ) {
				if ( empty( $sg['s'] ) || ! is_array( $sg['s'] ) ) {
					continue;
				}
				foreach ( $sg['s'] as $setting ) {
					if ( is_array( $setting ) && isset( $setting[1] ) ) {
						$index[ $setting[1] ] = array(
							'page'  => $tab_go,
							'tab'   => $tab_id,
							'label' => $setting[0],
						);
					}
				}
			}
		}
	}

	return $index;
}

/**
 * Map of health-definition keys to settings tab IDs.
 * Auto-derived from bbcs_get_global_search_index() via bbcs_get_setting_index().
 * Used by bbcs_find_tab_by_health_key() and status-page links.
 *
 * @return array<string, string> health_key => tab_id
 */
function bbcs_get_health_key_tab_map(): array {
	static $map = null;

	if ( $map !== null ) {
		return $map;
	}

	$map = array();
	foreach ( bbcs_get_setting_index() as $key => $route ) {
		$map[ $key ] = $route['tab'];
	}

	return $map;
}

/**
 * Look up the settings tab ID for a given health-definition key.
 *
 * @param string $health_key The health definition key (e.g. 'block_empty_ua').
 * @return string|null The tab ID (e.g. 'simple-detection'), or null if not found.
 */
function bbcs_find_tab_by_health_key( string $health_key ): ?string {
	$map = bbcs_get_health_key_tab_map();
	return isset( $map[ $health_key ] ) ? $map[ $health_key ] : null;
}

/**
 * Returns the tab ID → template file mapping for the Settings page.
 *
 * @return array<string, string> tab_id => absolute file path
 */
function bbcs_get_tabpanels(): array {
	$dir = BOTBLOCKER_DIR . 'admin/templates/settings/';
	return array(
		'performance'         => $dir . 'status.php',
		'simple-detection'    => $dir . 'bot-detection.php',
		'connection-types'    => $dir . 'connection-types.php',
		'browser-plugins'     => $dir . 'browser-plugins.php',
		'data-log'            => $dir . 'data-log.php',
		'advanced-protection' => $dir . 'advanced-protection.php',
		'cookie'              => $dir . 'cookie.php',
		'general'             => $dir . 'general.php',
		'error'               => $dir . 'error.php',
		'brute-force'         => $dir . 'login-brute-force.php',
		'captcha'             => $dir . 'captcha-tab.php',
		'rate-limiting'       => $dir . 'rate-limiting.php',
		'traffic'             => $dir . 'traffic.php',
		'payment'             => $dir . 'payment-tab.php',
		'log'                 => $dir . 'log.php',
		'cron'                => $dir . 'cron.php',
		'tls_fingerprint'     => $dir . 'tls-fingerprint.php',
		'settings-ui'         => $dir . 'ui.php',
		'notifications'       => $dir . 'notifications.php',
	);
}

/**
 * Resolves the admin URL for a specific setting key.
 *
 * Uses the centralized bbcs_get_setting_index() for O(1) lookup instead of
 * scanning the full tree on every call.
 *
 * @param string $key   The setting option key (e.g. 'redis_enable').
 * @param bool   $focus Whether to append &focus=key for scroll-to-field behaviour.
 * @return string The resolved WordPress admin URL with correct page parameter and anchor.
 */
function bbcs_get_setting_link( string $key, bool $focus = false ): string {
	$index = bbcs_get_setting_index();

	if ( isset( $index[ $key ] ) ) {
		$route = $index[ $key ];
		$url   = \BotBlockerMultisite::getAdminPageUrl( 'bbcs_' . $route['page'] );
		if ( $focus ) {
			$url .= '&focus=' . rawurlencode( $key );
		}
		$url .= '#' . $route['tab'];
		return $url;
	}

	return \BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' );
}
