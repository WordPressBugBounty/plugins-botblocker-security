<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\DonutChart;
use BotBlocker\Component\HistoryChart;
use BotBlocker\Component\TopList;
use BotBlocker\Component\VisitorsMap;

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/helpers/class-health-score-helper.php';
	require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-llm-sync.php';
	require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-tls-fingerprints-sync.php';
	require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rugov.php';
	require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-asn-db.php';

final class Botblocker_ReportsViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;
	/** @var DonutChart */
	public $donut_hosts;
	/** @var DonutChart */
	public $donut_devices;
	/** @var DonutChart */
	public $donut_browsers;
	/** @var DonutChart */
	public $donut_os;
	/** @var HistoryChart */
	public $traffic_chart;
	/** @var VisitorsMap */
	public $visitors_map;
	/** @var string */
	public $kpi_requests_today;
	/** @var string */
	public $kpi_requests_total;
	/** @var string */
	public $kpi_blocked_today;
	/** @var string */
	public $kpi_blocked_total;
	/** @var string */
	public $kpi_all_requests_total;
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
	/** @var string */
	public $health_label;
	/** @var TopList */
	public $top_ips;
	/** @var TopList */
	public $top_countries;
	/** @var string */
	public $kpi_cloud_ips;
	/** @var string */
	public $kpi_llm_providers;
	/** @var string */
	public $kpi_rkn_ranges;
	/** @var string */
	public $kpi_tls_fingerprints;
	/** @var string */
	public $kpi_asn_signatures;
	/** @var string */
	public $kpi_signatories_total;
	/** @var string */
	public $kpi_blocked_ips;
	/** @var string */
	public $kpi_total_rules;

	public function __construct() {
		$BBCSA     = Botblocker_Admin::getInstance();
		$botblocker = BotBlocker::getInstance();

		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();

		$this->urls            = new Botblocker_DashboardUrls();
		$this->urls->cloud_api = $BBCSA->pages_cloud_api;
		$this->urls->settings  = $BBCSA->pages_settings;
		$this->urls->setup     = $BBCSA->pages_setup;
		$this->urls->reports   = $BBCSA->pages_reports;
		$this->urls->addons    = $BBCSA->pages_addons;
		$this->urls->wizard    = $BBCSA->pages_wizard;
		$this->urls->pricing   = 'https://botblocker.com/pricing/';
		$this->urls->rules     = $BBCSA->pages_rules;
		$this->urls->tools     = $BBCSA->pages_tools;
		$this->urls->integrations = $BBCSA->pages_integrations;

		$this->docs_url = BOTBLOCKER_DOCS_URL;

		$period_days = isset( $botblocker->settings->admin_report_period ) ? (int) $botblocker->settings->admin_report_period : 30;
		BotBlockerStats::getStatistics( $period_days );

		$kpi = BotBlockerStats::getKpiViewData();
		foreach ( $kpi as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}
		$this->kpi_health_score   = (string) BotBlockerHealthShortcodes::calculateSiteHealth();
		$this->health_label       = Botblocker_HealthScoreHelper::getLabel( (int) $this->kpi_health_score );

		$this->donut_hosts     = $this->build_donut( 'ip_hits_hosts' );
		$this->donut_devices   = $this->build_donut( 'device_types' );
		$this->donut_browsers  = $this->build_donut( 'browsers' );
		$this->donut_os        = $this->build_donut( 'operating_systems' );

		$traffic     = BotBlockerStats::getHitsAndUniquesChartData( $period_days );
		$this->traffic_chart = HistoryChart::make()
			->withLabels( $traffic['labels'] )
			->withUniques( $traffic['uniques'] )
			->withHits( $traffic['hits'] );

		$this->visitors_map = VisitorsMap::make()
			->withData( BotBlockerStats::getVisitorsMapData( $period_days ) );

		$this->top_ips       = TopList::make()->withTitle( __( 'Top IPs', 'botblocker-security' ) )->withItems( BotBlockerStats::getTopData( 'ip', 10, $period_days ) )->withType( 'ip' );
		$this->top_countries = TopList::make()->withTitle( __( 'Top Countries', 'botblocker-security' ) )->withItems( BotBlockerStats::getTopData( 'country', 10, $period_days ) )->withType( 'country' );

		$this->kpi_cloud_ips = $this->fetch_cloud_ips_count();

		$llm_status     = BotBlockerLlmSync::getStatus();
		$rugov_status   = BotBlockerRugov::getStatus();
		$tls_status     = BotBlockerTlsFingerprintsSync::getStatus();
		$asn_status     = BotBlockerAsnDb::getStatus();
		$this->kpi_llm_providers    = (string) ( $llm_status['range_count'] ?? 0 );
		$this->kpi_rkn_ranges       = (string) ( $rugov_status['range_count'] ?? 0 );
		$this->kpi_tls_fingerprints = (string) ( $tls_status['fingerprint_count'] ?? 0 );
		$this->kpi_asn_signatures   = (string) ( $asn_status['node_count'] ?? 0 );
		$this->kpi_signatories_total = (string) (
			( $llm_status['range_count'] ?? 0 )
			+ ( $rugov_status['range_count'] ?? 0 )
			+ ( $tls_status['fingerprint_count'] ?? 0 )
			+ ( $asn_status['node_count'] ?? 0 )
		);

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- intentional uncached stats queries
		$this->kpi_blocked_ips = (string) (
			(int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}`" )
			+ (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}`" )
		);
		$this->kpi_total_rules = (string) (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}`" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	private function build_donut( string $metric ): DonutChart {
		$data = BotBlockerStats::getDonutPieChartData( $metric, 'today' );
		return DonutChart::make()
			->withLabels( $data['labels'] )
			->withValues( $data['values'] )
			->withTitle( $data['title'] )
			->withId( $data['container_id'] );
	}

	private function fetch_cloud_ips_count(): string {
		if ( BOTBLOCKER_CACHE_SIDEBAR_STATS ) {
			$cached = get_transient( 'bbcs_cloud_ips_kpi' );
			if ( false !== $cached ) {
				return $cached;
			}
		}
		$url  = BOTBLOCKER_BASE_TOTAL;
		$args = array(
			'method'      => 'GET',
			'timeout'     => 3,
			'redirection' => 0,
			'httpversion' => '1.1',
			'headers'     => array(
				'User-Agent' => method_exists( 'BotBlockerMultisite', 'getCurrentUserAgent' ) ? BotBlockerMultisite::getCurrentUserAgent() : 'BotBlocker/Stats',
			),
		);
		$response     = wp_remote_get( $url, $args );
		$cloud_count  = '0';
		if ( ! is_wp_error( $response ) ) {
			$http_code = wp_remote_retrieve_response_code( $response );
			$body      = wp_remote_retrieve_body( $response );
			if ( 200 === $http_code && ! empty( $body ) ) {
				$cloud_count = (string) intval( $body );
			}
		}
		if ( BOTBLOCKER_CACHE_SIDEBAR_STATS ) {
			set_transient( 'bbcs_cloud_ips_kpi', $cloud_count, BOTBLOCKER_CACHE_SIDEBAR_STATS_TIME );
		}
		return $cloud_count;
	}
}
