<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerCoreTrait {

	public function start_bbcs(): void {
		$this->_dst_applied  = false;
		$this->error_headers = BotBlockerData::getHeadersArray();
		$this->country       = BOTBLOCKER_EMPTY;
		$this->cidr          = BOTBLOCKER_EMPTY;
		$this->asname        = BOTBLOCKER_EMPTY;
		$this->asnum         = BOTBLOCKER_EMPTY;
		$this->hosting       = BOTBLOCKER_EMPTY;
		$this->x_robots_tag  = array();
	}

	public function load_directories(): void {
		$this->botblockerUrl = BOTBLOCKER_URL;
		$this->version       = BOTBLOCKER_VERSION;
		$this->dirs          = array(
			'root'      => BOTBLOCKER_DIR,
			'public'    => BOTBLOCKER_DIR . 'public/',
			'languages' => BOTBLOCKER_DIR . 'languages/',
			'includes'  => BOTBLOCKER_DIR . 'includes/',
			'admin'     => BOTBLOCKER_DIR . 'admin/',
			'data'      => BOTBLOCKER_DIR . 'data/',
			'vendor'    => BOTBLOCKER_DIR . 'vendor/',
		);
	}

	private function generate_missing_files(): void {
		$files = array(
			'search_engines.php' => 'BotBlockerFileRenderer::renderSearchEngines',
			'llm_trusted.php'    => 'BotBlockerFileRenderer::renderLlmTrusted',
			'asn_rules.php'      => 'BotBlockerFileRenderer::renderAsn',
			'ip.php'             => 'BotBlockerFileRenderer::renderIps',
			'proxy.php'          => 'BotBlockerFileRenderer::renderProxy',
			'paths.php'          => 'BotBlockerFileRenderer::renderPaths',
			'rules.php'          => 'BotBlockerFileRenderer::renderRules',
		);

		$is_any_missing = false;
		foreach ( $files as $filename => $callback ) {
			$filepath = BotBlockerMultisite::getDataDir() . $filename;
			if ( ! file_exists( $filepath ) ) {
				$is_any_missing = true;
				$callback();
			}
		}

		if ( $is_any_missing ) {
			BotBlockerAlerts::setMissingFiles();
		}
	}

	public function load_data(): void {
		$this->bbcs_rule       = array();
		$this->bbcs_se         = array();
		$this->bbcs_asn        = array();
		$this->self_ips        = array();
		$this->admin_ips       = array();
		$this->bbcs_llm        = array();
		$this->bbcs_good_bots  = array();
		$this->bbcs_proxy      = array();
		$this->bbcs_path       = array();

		$this->media_logo_botblocker = BOTBLOCKER_URL . 'admin/img/logo-small-transparent.webp';
		$this->media_icon_stop       = BOTBLOCKER_URL . 'public/icons/security.svg';
	}

	private function lazy_load_search_engines(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		if ( file_exists( BotBlockerMultisite::getDataDir() . 'search_engines.php' ) ) {
			$rules           = bbcs_safe_load_with_recovery( BotBlockerMultisite::getDataDir() . 'search_engines.php' );
			$this->bbcs_rule = $rules['bbcs_rule'] ?? $this->bbcs_rule;
			$this->bbcs_se   = $rules['bbcs_se'] ?? $this->bbcs_se;
		}
	}

	private function lazy_load_asn_rules(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		if ( file_exists( BotBlockerMultisite::getDataDir() . 'asn_rules.php' ) ) {
			$asn_rules      = bbcs_safe_load_with_recovery( BotBlockerMultisite::getDataDir() . 'asn_rules.php' );
			$this->bbcs_asn = $asn_rules['bbcs_asn'] ?? $this->bbcs_asn;
		}
	}

	private function lazy_load_ip_rules(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		if ( file_exists( BotBlockerMultisite::getDataDir() . 'ip.php' ) ) {
			$self_ip_rules   = bbcs_safe_load_with_recovery( BotBlockerMultisite::getDataDir() . 'ip.php' );
			$this->self_ips  = $self_ip_rules['self_ips'] ?? $this->self_ips;
			$this->admin_ips = $self_ip_rules['admin'] ?? $this->admin_ips;
		}
	}

	private function lazy_load_llm_trusted(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		if ( file_exists( BotBlockerMultisite::getDataDir() . 'llm_trusted.php' ) ) {
			$llm_data       = bbcs_safe_load_with_recovery( BotBlockerMultisite::getDataDir() . 'llm_trusted.php' );
			$this->bbcs_llm = $llm_data['bbcs_llm'] ?? $this->bbcs_llm;
		}
	}

	private function lazy_load_proxy(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		if ( file_exists( BotBlockerMultisite::getDataDir() . 'proxy.php' ) ) {
			$proxy            = bbcs_safe_load_with_recovery( BotBlockerMultisite::getDataDir() . 'proxy.php' );
			$this->bbcs_proxy = $proxy['bbcs_proxy'] ?? $this->bbcs_proxy;
		}
	}

	private function lazy_load_paths(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		if ( file_exists( BotBlockerMultisite::getDataDir() . 'paths.php' ) ) {
			$path            = bbcs_safe_load_with_recovery( BotBlockerMultisite::getDataDir() . 'paths.php' );
			$this->bbcs_path = $path['bbcs_path'] ?? $this->bbcs_path;
		}
	}

	private function initialize_config(): void {
		$this->list_of_colors_for_captcha         = array( 'BLACK', 'GRAY', 'RED', 'YELLOW', 'GREEN', 'BLUE', 'MAROON', 'PURPLE' );
		$this->country                            = BOTBLOCKER_EMPTY;
		$this->cidr                               = BOTBLOCKER_EMPTY;
		$this->asname                             = BOTBLOCKER_EMPTY;
		$this->asnum                              = BOTBLOCKER_EMPTY;
		$this->result_of_action                   = BOTBLOCKER_EMPTY;
		$this->hosting                            = BOTBLOCKER_EMPTY;
		$this->rule_record_id                     = 0;
		$this->suspect_status                     = 0;
		$this->timezone                           = BOTBLOCKER_EMPTY;
		$this->js_error_message                   = 'Your request has been denied.';
		$this->test_page_language                 = BOTBLOCKER_EMPTY;
		$this->list_of_bad_get_params_in_referrer = 'q, text, utm_source, utm_medium, utm_campaign, utm_term, utm_content, utm_referrer, yclid, ysclid, gclid, fbclid, mc_eid, mc_cid, aff_id, aff_sub, aff_sub2, clickid, ref, ref_id, referrer, partner_id, cid, ad_id, track_id, campaign_id, banner_id, keyword_id, s_kwcid, msclkid';
		$this->delete_query_string_from_referrer  = 0;
		$this->delete_page_query_string_from_URL  = 0;
		$this->post_antidetect_scope              = BOTBLOCKER_EMPTY;
	}

	public function load_settings(): bool {
		$settingsFile = BotBlockerMultisite::getDataDir() . 'settings.php';

		$this->settings = new BotBlockerSettings();
		$this->settings->load( $settingsFile );

		foreach ( $this->settings as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		if ( $this->settings->disable == 1 ) {
			$this->isDisabled = true;
			return true;
		}
		return false;
	}

	public function perform_prefly_checks(): bool {

		if ( $this->is_cli_request() ) {
			return true;
		}
		if ( $this->is_wordpress_maintenance_request() ) {
			return true;
		}
		$this->apply_daylight_saving();
		$this->set_error_header_fallback();
		$this->check_php_environment();
		$this->set_recaptcha_fallback();
		$this->load_salt_settings();
		return false;
	}

	public function is_wordpress_maintenance_request(): bool {

		$uri = isset( $this->uri ) ? (string) $this->uri : '';
		if ( $uri === '' && isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		if ( class_exists( 'BotBlockerWpUtility' ) && BotBlockerWpUtility::is_wordpress_maintenance_page( $uri ) ) {
			return true;
		}

		if ( wp_doing_cron() ) {
			return true;
		}

		return false;
	}

	public function generate_connection_id(): void {

		$this->time = time();
		$this->date = gmdate( 'Y.m.d', $this->time );
		$this->cid  = $this->time . '.' . wp_rand( 11111, 99999 );
	}

	public function is_cli_request(): bool {
		if ( php_sapi_name() == 'cli' ) {
			if ( $this->settings->botblocker_log_cli == 1 ) {
				BotBlockerStore::storeData( 'CLI request', 90 );
			}
			BotBlockerCounters::processHit( 90 );
			return true;
		} else {
			return false;
		}
	}

	public function apply_daylight_saving(): void {
		if ( $this->settings->daylight_saving_time != 1 || ! empty( $this->_dst_applied ) ) {
			return;
		}
		$this->_dst_applied = true;
		$base_seconds       = (int) ( floatval( $this->settings->admin_gmt_offset ) * 3600 );
		$now_ts             = time();

		$cache_key = 'bbcs_dst_offset_' . $base_seconds;
		$cached    = get_transient( $cache_key );

		if ( $cached === '1' ) {
			$this->settings->admin_gmt_offset = (string) ( (int) $this->settings->admin_gmt_offset + 1 );
			return;
		}
		if ( $cached === '0' ) {
			return;
		}

		$dst_active = false;
		foreach ( \DateTimeZone::listIdentifiers() as $tz_id ) {
			$tz    = new \DateTimeZone( $tz_id );
			$trans = $tz->getTransitions( $now_ts, $now_ts );
			if ( empty( $trans ) ) {
				continue;
			}
			if ( $trans[0]['isdst'] && ( $trans[0]['offset'] - 3600 ) === $base_seconds ) {
				$dst_active = true;
				break;
			}
		}

		set_transient( $cache_key, $dst_active ? '1' : '0', HOUR_IN_SECONDS );

		if ( $dst_active ) {
			$this->settings->admin_gmt_offset = (string) ( (int) $this->settings->admin_gmt_offset + 1 );
		}
	}

	public function set_error_header_fallback(): void {
		if ( ! isset( $this->error_headers[ $this->settings->header_error_code ] ) ) {
			$this->settings->header_error_code = 200;
		}
	}

	public function check_php_environment(): void {
		$this->prefly = BotBlockerEnv::prefly_check();
	}

	public function set_recaptcha_fallback(): void {
		// Only apply fallback when GD is unavailable AND the current mode actually requires GD (modes 1 and 2).
		// Modes 0, 3-8 do not use GD and must not be overridden.
		$gd_modes = array( 1, 2 );
		if ( isset( $this->prefly['gd'] ) && $this->prefly['gd'] === 0
			&& in_array( (int) $this->settings->bbcs_captcha_mode, $gd_modes, true )
		) {
			if ( empty( $this->settings->recaptcha_key2 ) || empty( $this->settings->recaptcha_secret2 ) ) {
				$this->settings->bbcs_captcha_mode = '0';
			} else {
				$this->settings->bbcs_captcha_mode = '4';
			}
			BotBlockerUI::fallback_captcha( $this->settings->bbcs_captcha_mode );
		}
	}

	public function load_salt_settings(): void {
		$salt_file = BotBlockerMultisite::getNetworkDataDir() . 'salt.php';
		if ( ! file_exists( $salt_file ) ) {
			BotBlockerInstall::createSaltFile( true );
		}

		if ( file_exists( $salt_file ) ) {
			$salt_data = bbcs_safe_load_with_recovery( $salt_file );
			if ( is_array( $salt_data ) ) {
				foreach ( $salt_data as $key => $value ) {
					if ( property_exists( $this->settings, $key ) ) {
						$this->settings->$key = (string) $value;
					}
				}
			}
		}

		if ( isset( $this->settings->salt_pz ) ) {
			$this->settings->salt = $this->settings->salt_pz . $this->settings->salt;
		}
	}

	public function has_valid_cloud_api(): bool {
		$type = BotBlockerPro::getType();
		if ( empty( $type ) ) {
			$this->cloud_api_status = null;
			return false;
		}
		$this->cloud_api_status = BotBlockerPro::getStatus();
		return $this->cloud_api_status !== null;
	}

	public function process_disabled_state(): bool {
		if ( $this->isDisabled ) {
			$this->store_disable_state();
			return true;
		}
		return false;
	}

	public function store_disable_state(): bool {
		if ( $this->settings->botblocker_log_disabled == true ) {
			$this->visitorType = self::VISITOR_UNDEFINED;
			BotBlockerStore::storeData( 'BotBlocker disabled', 23 );
		}
		BotBlockerCounters::processHit( 23 );
		return true;
	}

	public function collect_cloud_data(): array {
		$ip        = $this->ip;
		$cache_key = BotBlockerCache::getPrefix( '_IP_' );
		$cache_ttl = 86400; // 1 day

		$cached_data = BotBlockerCache::getCacheData( $cache_key );
		if ( $cached_data ) {
			return $cached_data;
		}

		$data = array(
			'ip_info' => true,
			'ip'      => $ip,
		);

		$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_GS_URL );
		if ( $cloud === false ) {
			$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_URL );
		}

		if ( $cloud !== null && $cloud !== false ) {
			BotBlockerCache::setCacheData( $cache_key, $cloud, $cache_ttl );
			return $cloud;
		}
		return array();
	}
}
