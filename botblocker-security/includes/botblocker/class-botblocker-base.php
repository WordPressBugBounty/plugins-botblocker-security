<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
abstract class BotBlockerBase {

	public const VISITOR_UNDEFINED  = 0;
	public const VISITOR_BOTBLOCKER = 1;
	public const VISITOR_HUMAN      = 2;
	public const VISITOR_LEGALBOT   = 3;
	public const VISITOR_ADMIN      = 4;
	public const VISITOR_FAKEBOT    = 5;
	public const VISITOR_SECRET     = 6;

	public const SECURE_MODE_FRONTEND = 1;
	public const SECURE_MODE_FULL     = 2;

	// Verification-cookie classification (see resolve_cookie_identity()).
	public const VERIFY_INVALID = 'invalid';
	public const VERIFY_VALID   = 'valid';
	public const VERIFY_EXPIRED = 'expired';

	protected static ?self $instance = null;
	public $time;
	public $isProxy     = BOTBLOCKER_EMPTY;
	public $isAdmin     = false;
	public $isDisabled  = false;
	public $visitorType = self::VISITOR_UNDEFINED;

	public $counters   = array(); // STAT BBCS pre array
	public $statistics = array();

	// Core properties
	public $dirs = array();
	public $version;
	public $botblockerUrl;
	public $cloud_api_status;
	public $uid;
	public $action_disable;
	public $action_off;
	public $action_on;
	public $date;
	public $cid;
	public $prefly;
	public bool $_dst_applied = false;

	// Visitor properties
	public $host;
	public $ip;
	public $ip_version;
	public $ipnum;
	public $ip_short;
	public $ptr;
	public $useragent;
	public $referer;
	public $refhost;
	public $refhost_scheme;
	public $uri;
	public $scheme;
	public $request_method;
	public $protocol;
	public $accept_lang;
	public $lang;
	public $http_accept;
	public $name_lang;
	public $page;
	public $save_page;
	public $save_referer;
	public $country;
	public $country_name;
	public $cidr;
	public $asname;
	public $asnum;
	public $hosting;
	public $browser;
	public $os;
	public $device;
	public $ym_uid;
	public $ga_uid;
	public $is_proxy_det;
	public $ptr_arr;

	// Cookie properties
	public $cookie_hits_counter;
	public $cookie_stored_hash;
	public $cookie_expected_hash;
	public $cookie_visitor_data;
	public $cookie_timestamp;
	public $cookie_kind = 'none';
	public bool $is_asset_request = false;

	// Verification state (written by resolve_cookie_identity).
	// null = not yet computed, otherwise VERIFY_INVALID|VERIFY_VALID|VERIFY_EXPIRED.
	public $verification_state = null;

	// Rules properties
	public $bbcs_rule      = array();
	public $bbcs_se        = array();
	public $bbcs_asn       = array();
	public $bbcs_llm       = array();
	public $bbcs_path      = array();
	public $bbcs_proxy     = array();
	public $self_ips       = array();
	public $admin_ips      = array();
	public $bbcs_good_bots = array();

	// Response properties
	public $error_headers = array();
	public $x_robots_tag  = array();
	public $test_page_language;
	public $suspect_status = 0;
	public $white_bot;
	public $select_request_mode;
	public $rule_record_id = 0;
	public $timezone;

	// Endpoint properties
	public $reason_for_action;
	public $result_of_action      = BOTBLOCKER_EMPTY;
	public $payment_bypass_reason  = '';
	public $payment_bypass_partial = false;

	// Initial config properties
	public $delete_query_string_from_referrer;
	public $delete_page_query_string_from_URL;
	public $list_of_bad_get_params_in_referrer;
	public $list_of_colors_for_captcha;
	public $js_error_message;

	// Media properties
	public $media_logo_botblocker;
	public $media_icon_stop;
	public $media_factory_core;

	// POST check properties
	public $cloud_data = array();
	public $cloud_error;

	public $post_antidetect_scope;
	public $post_hosting_detected;
	public $post_adblocker_found;
	public $post_cloudflare_country;
	public $post_cookie_disabled;
	public $post_from_suspect;
	public $post_width;
	public $post_height;
	public $post_client_width;
	public $post_client_height;
	public $post_color_depth;
	public $post_pixel_depth;
	public $post_referrer;
	public $post_timezone;
	public $post_ip_database_result;
	public $post_ipv4_value;
	public $post_recaptcha_token;
	public $post_extra_data;
	public $post_http_accept;
	public $post_hash_cookie;
	public $post_recaptcha_score;
	public $post_start_time;
	public $post_test_code;
	public $post_hash_code;

	// Template properties
	public $template_data_check  = array();
	public $template_data_block  = array();
	public $template_data_denied = array();
	public $captcha_data         = array();
	public $js_data              = array();
	public $block_js_data        = array();
	public $denied_data;
	public $block_data;
	public $block_wait_seconds;

	// Control properties
	public $should_show_check_page      = false;
	public $should_show_block_page      = false;
	public $should_show_denied_page     = false;
	public $csp_nonce                   = '';
	public $addon_traffic_decision      = array();
	public $addon_traffic_decision_stop = false;

	/**
	 * @var BotBlockerSettings
	 */
	public $settings;

	public function is_verified(): bool {
		return $this->verification_state === self::VERIFY_VALID;
	}

	abstract public function initialize(): void;
	abstract public function run(): void;
}
