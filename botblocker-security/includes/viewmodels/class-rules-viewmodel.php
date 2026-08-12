<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
final class Botblocker_RulesViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;

	/** @var array */
	public $geo_countries;
	/** @var int */
	public $geo_countries_count;

	/** @var array{name: string, label: string, active: int, disabled: int, attention: int}[] */
	public $table_counts;

	/** @var bool */
	public $has_pro;
	/** @var bool */
	public $early_geo_available;
	/** @var string */
	public $mu_geo_url;
	/** @var string */
	public $early_geo_url;

	public function __construct() {
		$BBCSA = Botblocker_Admin::getInstance();

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

		$geo_countries = BotBlockerMultisite::getOption( 'bbcs_blocked_countries', array() );
		if ( is_string( $geo_countries ) ) {
			$decoded = json_decode( $geo_countries, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$geo_countries = $decoded;
			} else {
				$geo_countries = array_filter( array_map( 'trim', explode( ',', $geo_countries ) ) );
			}
		}
		if ( is_array( $geo_countries ) ) {
			$geo_countries = array_map( 'strtoupper', $geo_countries );
			$geo_countries = array_filter(
				$geo_countries,
				static function ( $item ): bool {
					return preg_match( '/^[A-Z]{2}$/', (string) $item ) === 1;
				}
			);
			$geo_countries = array_values( array_unique( $geo_countries ) );
		} else {
			$geo_countries = array();
		}

		$this->geo_countries       = $geo_countries;
		$this->geo_countries_count = count( $geo_countries );

		$this->has_pro           = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
		$this->early_geo_available = $this->has_pro
			&& class_exists( 'BotBlockerGateway' )
			&& BotBlockerGateway::isRegistered( 'early_init' );
		$this->mu_geo_url   = $BBCSA->pages_integrations . '#mu';
		$this->early_geo_url = $BBCSA->pages_addons . '#bbcs-early-init';

		$this->table_counts = $this->computeTableCounts();
	}

	/**
	 * @return array{name: string, active: int, disabled: int, attention: int}[]
	 */
	private function computeTableCounts(): array {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$now     = time();
		$exp_inf = defined( 'BOTBLOCKER_EXP_INF' ) ? BOTBLOCKER_EXP_INF : 9999999999;

		$results = array();

		$results[] = $this->queryDisableExpires( $wpdb->bbcs_rules, 'Rules', __( 'Rules', 'botblocker-security' ), $now, $exp_inf );
		$results[] = $this->queryDisableOnly( $wpdb->bbcs_path, 'Paths', __( 'Paths', 'botblocker-security' ) );
		$results[] = $this->queryDisableOnly( $wpdb->bbcs_se, 'Trusted Bots', __( 'Trusted Bots', 'botblocker-security' ) );
		$results[] = $this->queryDisableExpires( $wpdb->bbcs_ipv4rules, 'IPv4', __( 'IPv4', 'botblocker-security' ), $now, $exp_inf );
		$results[] = $this->queryDisableExpires( $wpdb->bbcs_ipv6rules, 'IPv6', __( 'IPv6', 'botblocker-security' ), $now, $exp_inf );

		$proxy_count          = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_proxy}`" );
		$results[]            = array(
			'name'      => 'Proxy',
			'label'     => __( 'Proxy', 'botblocker-security' ),
			'active'    => $proxy_count,
			'disabled'  => 0,
			'attention' => 0,
		);

		$results[] = $this->queryDisableOnly( $wpdb->bbcs_asn, 'ASN', __( 'ASN', 'botblocker-security' ) );

		$llm_active   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_llm_trusted}` WHERE `disabled` = %d", 0 ) );
		$llm_disabled = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_llm_trusted}` WHERE `disabled` = %d", 1 ) );
		$results[]    = array(
			'name'      => 'LLM',
			'label'     => __( 'LLM', 'botblocker-security' ),
			'active'    => $llm_active,
			'disabled'  => $llm_disabled,
			'attention' => 0,
		);

		$geo_active   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_countries}` WHERE `disable` = %d", 0 ) );
		$geo_disabled = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_countries}` WHERE `disable` = %d", 1 ) );
		$results[]    = array(
			'name'      => 'GEO',
			'label'     => __( 'GEO', 'botblocker-security' ),
			'active'    => $geo_active,
			'disabled'  => $geo_disabled,
			'attention' => 0,
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $results;
	}

	/**
	 * @return array{name: string, label: string, active: int, disabled: int, attention: int}
	 */
	private function queryDisableExpires( string $table, string $name, string $label, int $now, int $exp_inf ): array {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table name from \$wpdb, all user values bound via prepare
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(CASE WHEN `disable` = 0 AND (`expires` >= %d OR `expires` > %d) THEN 1 ELSE 0 END) AS active,
					SUM(CASE WHEN `disable` = 1 THEN 1 ELSE 0 END) AS disabled,
					SUM(CASE WHEN `disable` = 0 AND `expires` > 0 AND `expires` < %d THEN 1 ELSE 0 END) AS attention
				FROM `{$table}`",
				$exp_inf,
				$now,
				$now
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return array(
			'name'      => $name,
			'label'     => $label,
			'active'    => $row ? (int) $row->active : 0,
			'disabled'  => $row ? (int) $row->disabled : 0,
			'attention' => $row ? (int) $row->attention : 0,
		);
	}

	/**
	 * @return array{name: string, label: string, active: int, disabled: int, attention: int}
	 */
	private function queryDisableOnly( string $table, string $name, string $label ): array {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table name from \$wpdb, no user input in query
		$row = $wpdb->get_row(
			"SELECT
				SUM(CASE WHEN `disable` = 0 THEN 1 ELSE 0 END) AS active,
				SUM(CASE WHEN `disable` = 1 THEN 1 ELSE 0 END) AS disabled
			FROM `{$table}`"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return array(
			'name'      => $name,
			'label'     => $label,
			'active'    => $row ? (int) $row->active : 0,
			'disabled'  => $row ? (int) $row->disabled : 0,
			'attention' => 0,
		);
	}
}
