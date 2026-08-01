<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\DailyHitsChart;
use BotBlocker\Component\DonutChart;

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-secret-links.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-health-check-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/helpers/class-health-score-helper.php';

final class Botblocker_DashboardViewModel {
	public const PROTECTION_STATUS_FULL       = 'full';
	public const PROTECTION_STATUS_INCOMPLETE = 'incomplete';
	public const PROTECTION_STATUS_PARTIAL    = 'partial';

	public const HEALTH_STATUS_CLASS_PRO  = 'bbcs-status-pro';
	public const HEALTH_STATUS_CLASS_FREE = 'bbcs-status-free';

	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;
	/** @var string */
	public $admin_report_period;
	/** @var bool */
	public $has_pro;
	/** @var bool */
	public $wizard_completed;
	/** @var int */
	public $health_score;
	/** @var string */
	public $health_label;
	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var Botblocker_SecretLinks */
	public $secret;
	/** @var string */
	public $protection_status;
	/** @var string */
	public $addons_icon_color;
	/** @var string */
	public $crown_tooltip;
	/** @var string */
	public $crown_icon_class;
	/** @var string */
	public $health_card_class;
	/** @var string */
	public $health_status_class;
	/** @var DailyHitsChart */
	public $daily_hits_chart;
	/** @var DonutChart */
	public $donut_hosts;
	/** @var DonutChart */
	public $donut_devices;
	/** @var DonutChart */
	public $donut_browsers;
	/** @var DonutChart */
	public $donut_os;
	/** @var string */
	public $kpi_requests_today;
	/** @var string */
	public $kpi_requests_total;
	/** @var string */
	public $kpi_blocked_total;
	/** @var string */
	public $kpi_blocked_today;
	/** @var string */
	public $kpi_blocked_percent;
	/** @var string */
	public $kpi_blocked_percent_total;
	/** @var string */
	public $kpi_allowed_percent;
	/** @var string */
	public $kpi_allowed_percent_total;
	/** @var string */
	public $kpi_search_engines;
	/** @var string */
	public $kpi_health_score;
	/** @var bool */
	public $is_active;
	/** @var string */
	public $hero_status_text;
	/** @var string */
	public $hero_subtitle;
	/** @var string */
	public $protection_mode;
	/** @var Botblocker_HealthCheckItemData[] */
	public $health_checks;

	public function __construct() {
		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();
		$botblocker                = BotBlocker::getInstance();
		$bbcsa                     = Botblocker_Admin::getInstance();
		$this->admin_report_period = $botblocker->settings->admin_report_period;
		BotBlockerStats::getStatistics( $this->admin_report_period );
		$this->has_pro          = BotBlockerPro::isActive();
		$this->wizard_completed = (bool) BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );
		$this->health_score     = bbcs_calculateSiteHealth();
		$this->health_label     = Botblocker_HealthScoreHelper::getLabel( $this->health_score );
		$this->apply_protection_status();
		$this->urls              = new Botblocker_DashboardUrls();
		$this->urls->cloud_api   = $bbcsa->pages_cloud_api;
		$this->urls->settings    = $bbcsa->pages_settings;
		$this->urls->setup       = $bbcsa->pages_setup;
		$this->urls->reports     = $bbcsa->pages_reports;
		$this->urls->addons      = $bbcsa->pages_addons;
		$this->urls->rules       = $bbcsa->pages_rules;
		$this->urls->tools       = $bbcsa->pages_tools;
		$this->urls->integrations = $bbcsa->pages_integrations;
		$this->urls->about       = $bbcsa->pages_about;
		$this->urls->wizard      = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' );
		$this->urls->pricing     = 'https://botblocker.top/pricing/';
		$this->urls->docs        = 'https://botblocker.top/docs/';
		$this->secret              = new Botblocker_SecretLinks();
		$this->secret->disable_url = BotBlockerMailer::getDisableUrl();
		$this->secret->off_url     = BotBlockerMailer::getOffUrl();
		$this->secret->on_url      = BotBlockerMailer::getOnUrl();
		$daily_hits             = BotBlockerStats::getDailyHitsChartData();
		$this->daily_hits_chart = DailyHitsChart::make()->withLabels( $daily_hits['labels'] )->withValues( $daily_hits['values'] );
		$this->donut_hosts = $this->build_donut( 'ip_hits_hosts' );
		$this->donut_devices = $this->build_donut( 'device_types' );
		$this->donut_browsers = $this->build_donut( 'browsers' );
		$this->donut_os = $this->build_donut( 'operating_systems' );
		$kpi = BotBlockerStats::getKpiViewData();
		foreach ( $kpi as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}
		$this->kpi_health_score = (string) $this->health_score;
		$this->is_active = ! $botblocker->isDisabled;
		if ( $this->is_active ) {
			$this->hero_status_text = __( 'Site is protected', 'botblocker-security' );
			$this->hero_subtitle = sprintf( /* translators: %1$s: number of blocked bots today, %2$s: number of search engine crawlers passed */ __( 'Mode "Recommended" today blocked %1$s bots, passed %2$s search engines.', 'botblocker-security' ), $this->kpi_blocked_today, $this->kpi_search_engines );
		} else {
			$this->hero_status_text = __( 'Protection paused', 'botblocker-security' );
			$this->hero_subtitle = __( 'BotBlocker is currently disabled. Enable it to protect your site.', 'botblocker-security' );
		}
		$this->protection_mode = __( 'Recommended', 'botblocker-security' );
		$this->health_checks = $this->build_health_checks( $botblocker );
	}

	/**
	 * @return Botblocker_HealthCheckItemData[]
	 */
	private function build_health_checks( $bbcs ): array {
		$settings = $bbcs->settings;
		return array(
			new Botblocker_HealthCheckItemData( __( 'Cloud threat checking', 'botblocker-security' ), $this->has_pro ),
			new Botblocker_HealthCheckItemData( __( 'Empty User-Agent blocking', 'botblocker-security' ), ! empty( $settings->check_empty_user_agent ) ),
			new Botblocker_HealthCheckItemData( __( 'PTR / DNS anomalies', 'botblocker-security' ), ! empty( $settings->ptr_enable ) ),
			new Botblocker_HealthCheckItemData( __( 'Brute-force protection', 'botblocker-security' ), ! empty( $settings->check_login_bruteforce ) ),
			new Botblocker_HealthCheckItemData( __( 'Captcha enabled', 'botblocker-security' ), ! empty( $settings->captcha_type ) && 'none' !== $settings->captcha_type ),
			new Botblocker_HealthCheckItemData( __( 'Simple bot UA blocking', 'botblocker-security' ), ! empty( $settings->check_simple_bots ) ),
			new Botblocker_HealthCheckItemData( __( 'Telegram notifications', 'botblocker-security' ), ! empty( $settings->telegram_bot_token ) && ! empty( $settings->telegram_chat_id ) ),
			new Botblocker_HealthCheckItemData( __( 'VPN and Tor blocking', 'botblocker-security' ), $this->has_pro && ! empty( $settings->proxy_check ), true ),
		);
	}

	private function apply_protection_status(): void {
		if ( $this->has_pro ) {
			$this->protection_status = $this->health_score >= 85 ? self::PROTECTION_STATUS_FULL : self::PROTECTION_STATUS_INCOMPLETE;
			$this->addons_icon_color = '';
			$this->crown_tooltip = esc_attr__( 'You have PRO activated. Check your plan.', 'botblocker-security' );
			$this->crown_icon_class = 'bbcs-cloud-api-color';
			$this->health_card_class = 'bbcs-fill-height bbcs-card-pro-active';
			$this->health_status_class = self::HEALTH_STATUS_CLASS_PRO;
		} else {
			$this->protection_status = self::PROTECTION_STATUS_PARTIAL;
			$this->addons_icon_color = ' bbcs-color-white';
			$this->crown_tooltip = esc_attr__( 'Improve your plan for excellent security protection.', 'botblocker-security' );
			$this->crown_icon_class = '';
			$this->health_card_class = 'bbcs-fill-height bbcs-card-free';
			$this->health_status_class = self::HEALTH_STATUS_CLASS_FREE;
		}
	}

	private function build_donut( string $metric ): DonutChart {
		$data = BotBlockerStats::getDonutPieChartData( $metric, 'today' );
		return DonutChart::make()->withLabels( $data['labels'] )->withValues( $data['values'] )->withTitle( $data['title'] )->withId( $data['container_id'] );
	}
}
