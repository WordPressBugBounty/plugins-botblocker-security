<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerCoreTrait {
    
    public function start_bbcs()
    {
        $this->error_headers = bbcs_loadHeadersArray();
        $this->country = BOTBLOCKER_EMPTY;
        $this->cidr = BOTBLOCKER_EMPTY;
        $this->asname = BOTBLOCKER_EMPTY;
        $this->asnum = BOTBLOCKER_EMPTY;
        $this->hosting = BOTBLOCKER_EMPTY;
        $this->x_robots_tag = [];
    }

    public function load_directories()
    {
        $this->botblockerUrl = BOTBLOCKER_URL;
        $this->version = BOTBLOCKER_VERSION;
        $this->dirs = array(
            'root'      => BOTBLOCKER_DIR,
            'public'    => BOTBLOCKER_DIR . 'public/',
            'languages' => BOTBLOCKER_DIR . 'languages/',
            'includes'  => BOTBLOCKER_DIR . 'includes/',
            'admin'     => BOTBLOCKER_DIR . 'admin/',
            'data'      => BOTBLOCKER_DIR . 'data/',
            'vendor'    => BOTBLOCKER_DIR . 'vendor/',
        );
    }

	private function generate_missing_files(): void
	{
    	$files = [
        	'search_engines.php' => 'bbcs_renderSearchEnginesFromDb',
        	'ip.php'             => 'bbcs_renderIpsFromDb',
        	'proxy.php'          => 'bbcs_renderProxyFromDb',
        	'paths.php'          => 'bbcs_renderPathsFromDb',
        	'rules.php'          => 'bbcs_renderRulesFromDb',
    	];

    	$is_any_missing = false;
    	foreach ( $files as $filename => $callback ) {
        	$filepath = BOTBLOCKER_DATA_DIR . $filename;
        	if ( ! file_exists( $filepath ) ) {
            	$is_any_missing = true;
            	$callback();
        	}
    	}

    	if ( $is_any_missing ) {
			bbcs_alerts_set_missing_files();
    	}
	}

    public function load_data()
    {
        if (file_exists(BOTBLOCKER_DATA_DIR . 'search_engines.php')) {
            $rules = include(BOTBLOCKER_DATA_DIR . 'search_engines.php');
            $this->bbcs_rule = $rules['bbcs_rule'] ?? [];
            $this->bbcs_se = $rules['bbcs_se'] ?? [];
        } else {
            $this->bbcs_rule = [];
            $this->bbcs_se = [];
        }
        if (file_exists(BOTBLOCKER_DATA_DIR . 'ip.php')) {
            $self_ip_rules = include(BOTBLOCKER_DATA_DIR . 'ip.php');
            $this->self_ips = $self_ip_rules['self_ips'] ?? [];
            $this->admin_ips = $self_ip_rules['admin'] ?? [];
        } else {
            $this->self_ips = [];
            $this->admin_ips = [];
        }
        if (file_exists(BOTBLOCKER_DATA_DIR . 'base/good_bots.php')) {
            $data = include(BOTBLOCKER_DATA_DIR . 'base/good_bots.php');
            $this->bbcs_good_bots = $data['bbcs_good_bots'] ?? [];
        } else {
            $this->bbcs_good_bots = [];
        }
        if (file_exists(BOTBLOCKER_DATA_DIR . 'proxy.php')) {
            $proxy = include(BOTBLOCKER_DATA_DIR . 'proxy.php');
            $this->bbcs_proxy = $proxy['bbcs_proxy'] ?? [];
        } else {
            $this->bbcs_proxy = [];
        }
        if (file_exists(BOTBLOCKER_DATA_DIR . 'paths.php')) {
            $path = include(BOTBLOCKER_DATA_DIR . 'paths.php');
            $this->bbcs_path = $path['bbcs_path'] ?? []; 
        } else {
            $this->bbcs_path = [];
        }

        $this->media_logo_botblocker = BOTBLOCKER_URL . 'admin/img/logo-small-transparent.webp';
        $this->media_icon_stop = BOTBLOCKER_URL . 'public/icons/security.svg';
    }

    private function initialize_config()
    {
        $this->list_of_colors_for_captcha = ['BLACK', 'GRAY', 'RED', 'YELLOW', 'GREEN', 'BLUE', 'MAROON', 'PURPLE'];
        $this->country = BOTBLOCKER_EMPTY;
        $this->cidr = BOTBLOCKER_EMPTY;
        $this->asname = BOTBLOCKER_EMPTY;
        $this->asnum = BOTBLOCKER_EMPTY;
        $this->result_of_action = BOTBLOCKER_EMPTY;
        $this->hosting = BOTBLOCKER_EMPTY;
        $this->rule_record_id = 0;
        $this->suspect_status = 0;
        $this->timezone = BOTBLOCKER_EMPTY;
        $this->js_error_message = 'Your request has been denied.';
        $this->test_page_language = BOTBLOCKER_EMPTY;
        $this->list_of_bad_get_params_in_referrer = 'q, text, utm_source, utm_medium, utm_campaign, utm_term, utm_content, utm_referrer, yclid, ysclid, gclid, fbclid, mc_eid, mc_cid, aff_id, aff_sub, aff_sub2, clickid, ref, ref_id, referrer, partner_id, cid, ad_id, track_id, campaign_id, banner_id, keyword_id, s_kwcid, msclkid';
        $this->delete_query_string_from_referrer = 0;
        $this->delete_page_query_string_from_URL = 0;
        $this->post_antidetect_scope = BOTBLOCKER_EMPTY;
    }
 
    public function load_settings() : bool
    { 
        $settingsFile = BOTBLOCKER_DATA_DIR . 'settings.php';

        $this->settings = new BotBlockerSettings();
        $this->settings->load($settingsFile);

        foreach ($this->settings as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        if ($this->settings->disable == 1) {
            $this->isDisabled = true;
            return true;
        }
        return false;
    }

    public function perform_prefly_checks() : bool
    {
        if ($this->is_cli_request()) return true;
        $this->apply_daylight_saving();
        $this->set_error_header_fallback();
        $this->check_php_environment();
        $this->set_recaptcha_fallback();
        $this->load_salt_settings();
        return false;
    }

    public function generate_connection_id() : void
    {
        $this->time = time();
        $this->date = gmdate('Y.m.d', $this->time); 
        $this->cid = $this->time . '.' . wp_rand(11111, 99999);
    }

    public function is_cli_request() : bool
    {
        if (php_sapi_name() == 'cli') {
            if ($this->settings->botblocker_log_cli == 1) {
                bbcs_storeData('CLI request', 90);
            }
            bbcs_process_hit(90);
            return true;
        } else {
            return false;
        }
    }

    public function apply_daylight_saving() : void
    {
        if($this->settings->daylight_saving_time == 1) {
            if (gmdate('I')) $this->settings->admin_gmt_offset += 1; 
        }    
    }

    public function set_error_header_fallback() : void
    {
        if (!isset($this->error_headers[$this->settings->header_error_code])) {
            $this->settings->header_error_code = 200;
        }
    }

    public function check_php_environment() : void
    {
        $this->prefly = BotBlockerEnv::prefly_check(); 
    }

    public function set_recaptcha_fallback() : void
    {
        if (isset($this->prefly['gd']) && $this->prefly['gd'] === 0) {
            if (empty($this->settings->recaptcha_key2) || empty($this->settings->recaptcha_secret2)) {
                $this->settings->bbcs_captcha_mode = '1';
            } else {
                $this->settings->bbcs_captcha_mode = '4';
            }
            BotBlockerUI::fallback_captcha($this->settings->bbcs_captcha_mode); 
        }
    }

    public function load_salt_settings() : void
    {
        $salt_file = BOTBLOCKER_DATA_DIR . 'salt.php';
        if (!file_exists($salt_file)) {
            bbcs_createSaltFile(true);
        }

        if (file_exists($salt_file)) {
            $salt_data = include($salt_file);
            if (is_array($salt_data)) {
                foreach ($salt_data as $key => $value) {
                    if (property_exists($this->settings, $key)) {
                        $this->settings->$key = $value;
                    }
                }
            }
        }

        if (isset($this->settings->salt_pz)) {
            $this->settings->salt = $this->settings->salt_pz . $this->settings->salt;
        }
    }

    public function has_valid_cloud_api() : bool
    {
        $this->cloud_api_status = bbcs_getCloudAPIStatus();
        if ($this->cloud_api_status !== null) {
            if (($this->cloud_api_status == 'cloud_basic') || ($this->cloud_api_status == 'cloud_extended')) {
                return true;
            }
        } else return false;
        return false;
    }
    
    public function process_disabled_state() : bool
    {
        if ($this->isDisabled) {
            $this->store_disable_state();
            return true;
        }
        return false;
    }

    public function store_disable_state() : bool
    { 
        if ($this->settings->botblocker_log_disabled == true) {
            $this->visitorType = self::VISITOR_UNDEFINED;
            bbcs_storeData('BotBlocker disabled', 23);
        }
        bbcs_process_hit(23);
        return true;
    }

    public function collect_cloud_data() : array
    {
        $ip = $this->ip;
        $cache_key = bbcs_getCachePrefix('_IP_');
        $cache_ttl = 86400; // 1 day

        $cached_data = bbcs_getCachedCloudData($cache_key);
        if ($cached_data) {
            return $cached_data;
        }

        $data = [
            'ip_info' => true,
            'ip' => $ip
        ];
 
        $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL);
        if ($cloud === false) {
            $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL);
        }

        if ($cloud !== null && $cloud !== false) {
            bbcs_cacheCloudData($cache_key, $cloud, $cache_ttl);
        }
        return $cloud;
    }


}
