<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-pro-comparison-row-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-pro-features.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-pro-plans.php';

final class Botblocker_CloudApiViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var bool */
	public $is_cloud_api_active;
	/** @var bool */
	public $is_expired;
	/** @var string */
	public $cloud_api_key;
	/** @var bool */
	public $managed_by_freemius;
	/** @var string */
	public $freemius_account_url;
	/** @var bool|string */
	public $remaining_hits;
	/** @var bool|int */
	public $remaining_days;
	/** @var bool */
	public $should_fetch;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	/** @var string */
	public $pricing_url;
	/** @var array<int, array<string, mixed>> */
	public $pro_plans;
	/** @var string */
	public $addons_url;
	/** @var string */
	public $account_url;
	/** @var string */
	public $connect_nonce;
	/** @var string */
	public $deactivate_nonce;
	/** @var string[] */
	public $pro_features;
	/** @var Botblocker_ProComparisonRowData[] */
	public $pro_comparison;

	public function __construct() {
		$BBCSA = Botblocker_Admin::getInstance();

		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();

		$this->is_cloud_api_active = BotBlockerPro::isActive();
		$this->is_expired          = $this->is_cloud_api_active && BotBlockerPro::isExpired();
		$this->cloud_api_key       = $this->is_cloud_api_active ? BotBlockerPro::getKey() : '';
		$this->managed_by_freemius  = $this->is_cloud_api_active && BotBlockerFreemius::hasLicense();
		$this->freemius_account_url = BotBlockerFreemius::accountUrl();
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

		$this->pricing_url = 'https://botblocker.top/pricing/';
		$this->pro_plans = $this->buildProPlans( BotBlockerProPlans::get() );
		$this->addons_url  = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		$this->account_url = BOTBLOCKER_ACCOUNT_URL;

		$this->connect_nonce    = '';
		$this->deactivate_nonce = '';

		$this->pro_features = BotBlockerProFeatures::getProFeatures();

		$this->pro_comparison = array();
		foreach ( BotBlockerProFeatures::getProComparison() as $row ) {
			$this->pro_comparison[] = new Botblocker_ProComparisonRowData(
				$row['feature'],
				! empty( $row['free'] ),
				! empty( $row['pro'] )
			);
		}
	}

	private function buildProPlans( array $plans ): array {
		$cards = array();
		foreach ( $plans as $plan ) {
			$features = array();
			foreach ( $plan['features'] as $feature ) {
				$label = BotBlockerProPlans::featureLabel( $feature );
				if ( '' !== $label ) {
					$features[] = $label;
				}
			}
			$modifier = '';
			if ( 'premium' !== $plan['tier'] ) {
				$modifier = 'bbcs-plan--' . $plan['tier'];
			}
			$cards[] = array(
				'name'             => $plan['name'],
				'modifier'         => $modifier,
				'monthly'          => BotBlockerProPlans::formatPrice( $plan['monthly'] ),
				'annual'           => BotBlockerProPlans::formatPrice( $plan['annual'] ),
				'annual_monthly'   => BotBlockerProPlans::formatPrice( round( $plan['annual'] / 12, 2 ) ),
				'features'         => $features,
				'checkout_monthly' => BotBlockerFreemius::checkoutUrl( $plan['plan_id'], 'monthly' ),
				'checkout_annual'  => BotBlockerFreemius::checkoutUrl( $plan['plan_id'], 'annual' ),
			);
		}

		return $cards;
	}
}
