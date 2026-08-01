<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_SidebarViewModel {
	/** @var string */
	public $settings_url;
	/** @var string */
	public $addons_url;
	/** @var string */
	public $cloud_api_url;
	/** @var string */
	public $setup_url;
	/** @var string */
	public $integrations_url;
	/** @var string */
	public $tools_url;
	/** @var bool */
	public $cloud_api_active;
	/** @var bool */
	public $early_available;
	/** @var bool */
	public $early_addon_active;
	/** @var bool */
	public $contact_collected;
	/** @var \BotBlocker\Component\Botblocker_StatusToggles */
	public $toggles;
	/** @var string */
	public $today_blocked;
	/** @var string */
	public $total_blocked;
	/** @var bool Whether BotBlocker protection is currently active. */
	public $is_active;
	/** @var string Protection status label for the rail status card. */
	public $status_label;
	/** @var bool */
	public $display_news;
	/** @var string */
	public $news_url;
	/** @var array */
	public $news_items;
	/** @var string */
	public $database_update_text;
	/** @var string */
	public $database_total_text;
	/** @var BotBlockerSystemInfoData */
	public $system_info;
	/** @var array */
	public $pro_features;
	/** @var Botblocker_SocialProofData|null */
	public $social_proof;
	/** @var string */
	public $contact_email;
	/** @var string */
	public $early_init_disabled_message;

	public function __construct() {
		require_once BOTBLOCKER_DIR . 'includes/data/botblocker-pro-features.php';
		require_once BOTBLOCKER_DIR . 'includes/data/botblocker-marketing-blocks.php';
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-social-proof-data.php';
		require_once BOTBLOCKER_DIR . 'includes/dto/class-status-toggles.php';

		$bbcs  = BotBlocker::getInstance();
		$bbcsa = Botblocker_Admin::getInstance();
		$settings = $bbcs->settings;

		$this->settings_url     = $bbcsa->pages_settings;
		$this->addons_url       = $bbcsa->pages_addons;
		$this->cloud_api_url    = $bbcsa->pages_cloud_api;
		$this->setup_url        = $bbcsa->pages_setup;
		$this->integrations_url = $bbcsa->pages_integrations;
		$this->tools_url        = $bbcsa->pages_tools;
		$this->cloud_api_active = BotBlockerPro::isActive();

		$early_addon = class_exists( 'BotBlockerAddons' )
			? BotBlockerAddons::hasActiveProvider( 'early_init_provider', 'bbcs_early_init_provider_active' )
			: false;

		$this->early_addon_active = $early_addon;
		$this->early_available    = $this->cloud_api_active && $early_addon;
		$this->contact_collected  = (int) BotBlockerMultisite::getOption( 'bbcs_contact_email_collected', 0 ) === 1;

		$this->apply_early_init_disabled_message();

		$this->toggles                       = new \BotBlocker\Component\Botblocker_StatusToggles();
		$this->toggles->early_init_checked   = $this->early_available ? (int) ( isset( $settings->early_init_enable ) ? $settings->early_init_enable : 0 ) : 0;
		$this->toggles->early_init_available = $this->early_available;
		$this->toggles->early_init_disabled  = ! $this->early_available;
		$this->toggles->mu_checked           = (int) ( isset( $settings->mu_enable ) ? $settings->mu_enable : 0 );
		$this->toggles->redis_checked        = (int) ( isset( $settings->redis_enable ) ? $settings->redis_enable : 1 );
		$this->toggles->redis_disabled       = ! extension_loaded( 'redis' );
		$this->toggles->memcached_checked    = (int) ( isset( $settings->memcached_enable ) ? $settings->memcached_enable : 1 );
		$this->toggles->memcached_disabled   = ! extension_loaded( 'memcached' );
		$this->toggles->ptr_cache_checked    = (int) ( isset( $settings->ptr_cache_in_db ) ? $settings->ptr_cache_in_db : 1 );
		
		$ptr_time = (int) ( isset( $settings->ptrcache_time ) ? $settings->ptrcache_time : 86400 );
		$ptr_labels = function_exists( 'bbcs_get_ptr_lifetimes' ) ? bbcs_get_ptr_lifetimes() : array();
		$this->toggles->ptrcache_time_label = $ptr_labels[ $ptr_time ] ?? ( $ptr_time / 3600 ) . 'h';

		$this->toggles->cache_ui_checked = (int) ( isset( $settings->cache_ui_data ) ? $settings->cache_ui_data : 0 );
		$ui_dur = (int) ( isset( $settings->cache_ui_duration ) ? $settings->cache_ui_duration : 300 );
		$ui_labels = function_exists( 'bbcs_get_cache_durations' ) ? bbcs_get_cache_durations() : array();
		$this->toggles->cache_ui_duration_label = $ui_labels[ $ui_dur ] ?? ( $ui_dur / 60 ) . 'm';

		$this->today_blocked = BotBlockerStats::blockedToday();
		$this->total_blocked = BotBlockerStats::blockedTotal();

		$this->is_active     = ! $bbcs->isDisabled;
		$this->status_label  = $this->is_active
			? esc_html__( 'Protection active', 'botblocker-security' )
			: esc_html__( 'Protection paused', 'botblocker-security' );

		$this->display_news = defined( 'BOTBLOCKER_DISPLAY_NEWS' ) && BOTBLOCKER_DISPLAY_NEWS;
		$this->news_url     = defined( 'BOTBLOCKER_NEWS_URL' ) ? BOTBLOCKER_NEWS_URL : '';
		if ( $this->display_news ) {
			$this->news_items           = function_exists( 'bbcs_get_news_items' ) ? bbcs_get_news_items( 5 ) : array();
			$this->database_update_text = function_exists( 'bbcs_getDatabaseUpdate' ) ? bbcs_getDatabaseUpdate() : '';
			$this->database_total_text  = function_exists( 'bbcs_getDatabaseAll' ) ? bbcs_getDatabaseAll() : '';
		} else {
			$this->news_items           = array();
			$this->database_update_text = '';
			$this->database_total_text  = '';
		}

		$this->system_info  = BotBlockerSystemInfoData::getInstance();
		$this->pro_features = function_exists( 'bbcs_get_pro_features' ) ? bbcs_get_pro_features() : array();
		$this->social_proof = $this->build_social_proof_data();

		$this->contact_email = BotBlockerInstall::getCloudAPIEmail();
	}

	private function apply_early_init_disabled_message(): void {
		if ( ! $this->cloud_api_active && ! $this->early_addon_active ) {
			$this->early_init_disabled_message = esc_html__( 'Requires BotBlocker PRO and the Early Init add-on.', 'botblocker-security' );
		} elseif ( ! $this->cloud_api_active ) {
			$this->early_init_disabled_message = esc_html__( 'Requires active BotBlocker PRO', 'botblocker-security' );
		} else {
			$this->early_init_disabled_message = esc_html__( 'Requires the Early Init add-on to be enabled.', 'botblocker-security' );
		}
	}

	private function build_social_proof_data(): ?Botblocker_SocialProofData {
		$stats = function_exists( 'bbcs_get_wp_org_stats' ) ? bbcs_get_wp_org_stats() : null;
		if ( ! $stats || ( $stats['active_installs'] < 10 && $stats['num_ratings'] < 1 ) ) {
			return null;
		}

		return new Botblocker_SocialProofData( $stats );
	}
}
