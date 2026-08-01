<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-pro-comparison-row-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-pro-features.php';

final class Botblocker_CloudApiViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var bool */
	public $is_cloud_api_active;
	/** @var string */
	public $cloud_api_key;
	/** @var bool|string */
	public $remaining_hits;
	/** @var bool|int */
	public $remaining_days;
	/** @var bool */
	public $should_fetch;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;
	/** @var string */
	public $pricing_url;
	/** @var string */
	public $addons_url;
	/** @var string */
	public $connect_nonce;
	/** @var string */
	public $deactivate_nonce;
	/** @var string */
	public $fetch_key_nonce;
	/** @var string[] */
	public $pro_features;
	/** @var Botblocker_ProComparisonRowData[] */
	public $pro_comparison;

	public function __construct() {
		$BBCSA = Botblocker_Admin::getInstance();

		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();

		$this->is_cloud_api_active = BotBlockerPro::isActive();
		$this->cloud_api_key       = $this->is_cloud_api_active ? BotBlockerPro::getKey() : '';
		$this->remaining_hits      = BotBlockerPro::getRemainingHits();
		$this->remaining_days      = BotBlockerPro::getRemainingDays();
		$this->should_fetch         = ( $this->remaining_hits === false || $this->remaining_days === false );

		$this->urls            = new Botblocker_DashboardUrls();
		$this->urls->cloud_api = $BBCSA->pages_cloud_api;
		$this->urls->settings  = $BBCSA->pages_settings;
		$this->urls->setup     = $BBCSA->pages_setup;
		$this->urls->reports   = $BBCSA->pages_reports;
		$this->urls->addons    = $BBCSA->pages_addons;
		$this->urls->wizard    = $BBCSA->pages_wizard;
		$this->urls->pricing   = 'https://botblocker.com/pricing/';

		$this->docs_url    = BOTBLOCKER_DOCS_URL;
		$this->pricing_url = 'https://botblocker.top/pricing/';
		$this->addons_url  = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );

		$this->connect_nonce    = '';
		$this->deactivate_nonce = '';
		$this->fetch_key_nonce  = '';

		$this->pro_features = bbcs_get_pro_features();

		$this->pro_comparison = array();
		foreach ( bbcs_get_pro_comparison() as $row ) {
			$this->pro_comparison[] = new Botblocker_ProComparisonRowData(
				$row['feature'],
				! empty( $row['free'] ),
				! empty( $row['pro'] )
			);
		}
	}
}
