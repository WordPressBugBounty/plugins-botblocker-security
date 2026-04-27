<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
abstract class BotBlockerBase {

    public const VISITOR_UNDEFINED = 0;
    public const VISITOR_BOTBLOCKER = 1;
    public const VISITOR_HUMAN = 2;
    public const VISITOR_LEGALBOT = 3;
    public const VISITOR_ADMIN = 4;
    public const VISITOR_FAKEBOT = 5;
    public const VISITOR_SECRET = 6;

    public const SECURE_MODE_FRONTEND = 1;
    public const SECURE_MODE_FULL = 2;

    protected static ?self $instance = null;
    public $time;
    public $isProxy = BOTBLOCKER_EMPTY;
    public $isAdmin = false;
    public $isDisabled = false;
    public $visitorType = self::VISITOR_UNDEFINED;

    public $counters = []; // STAT BBCS pre array
    public $statistics = []; 

    // Core properties
    public $dirs = [];
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
    public bool $is_asset_request = false;

    // Rules properties
    public $bbcs_rule = [];
    public $bbcs_se = [];
    public $bbcs_path = [];
    public $bbcs_proxy = [];
    public $self_ips = [];
    public $admin_ips = [];
    public $bbcs_good_bots = [];

    // Response properties
    public $error_headers = [];
    public $x_robots_tag = [];
    public $test_page_language;
    public $suspect_status = 0;
    public $white_bot;
    public $select_request_mode;
    public $rule_record_id = 0;
    public $timezone;

    // Endpoint properties
    public $reason_for_action;
    public $result_of_action = BOTBLOCKER_EMPTY;
    public $payment_bypass_reason = '';

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
    public $cloud_data = [];
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
    public $template_data_check = [];
    public $template_data_block = [];
    public $template_data_denied = [];
    public $captcha_data = [];
    public $js_data = [];
    public $block_js_data = [];
    public $denied_data;
    public $block_data;
    public $block_wait_seconds;

    // Control properties
    public $should_show_check_page = false;
    public $should_show_block_page = false;
    public $should_show_denied_page = false;
    public $nonce_silent_protection = false;
    public $csp_nonce = '';

    /**
     * @var BotBlockerSettings
     */
    public $settings;

    abstract public function initialize() : void;
    abstract public function run() : void;
}