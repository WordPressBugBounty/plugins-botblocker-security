<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-addons-market.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-market-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-installed-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-tab-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-market-card-data.php';
require_once BOTBLOCKER_DIR . 'includes/components/class-botblocker-tab-item.php';

use BotBlocker\Component\TabItem;

final class Botblocker_AddonsViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;

	/** @var array<string,Botblocker_AddonInstalledItemData> */
	public $addons;
	/** @var array<string,bool> */
	public $active;
	/** @var Botblocker_AddonMarketItemData[] */
	public $market;
	/** @var array<string,Botblocker_AddonMarketItemData> */
	public $marketBySlug;
	/** @var bool */
	public $addons_locked;
	/** @var bool */
	public $has_cloud_api;
	/** @var bool */
	public $addons_local_mode;

	/** @var bool */
	public $market_lazy;

	/** @var int */
	public $updates_count;

	/** @var array */
	public $nav_groups;

	/** @var array<string,string> */
	public $tabpanels;

	/** @var string */
	public $active_tab_id;

	/** @var Botblocker_AddonTabData[] */
	public $addon_tabs;

	/** @var Botblocker_AddonMarketCardData[] */
	public $marketplace_installed_cards;

	/** @var Botblocker_AddonMarketCardData[] */
	public $marketplace_available_cards;

	public function __construct() {
		if ( ! class_exists( 'Botblocker_Admin' ) ) {
			require_once BOTBLOCKER_DIR . 'admin/class-botblocker-admin.php';
		}
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

		$this->addons_local_mode = BotBlockerAddons::isLocalMode();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$force_requested = isset( $_GET['force'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['force'] ) );
		if ( $force_requested ) {
			BotBlockerAddonsMarket::flushActiveChannelCacheIfDev();
		}

		$this->market_lazy      = ! $this->addons_local_mode && ! BotBlockerAddonsMarket::hasCache();

		$ctx                 = BotBlockerAddonsMarket::getContext( $this->market_lazy );
		$this->active        = $ctx->active;
		$this->addons_locked = $ctx->addons_locked;
		$this->has_cloud_api = $ctx->has_cloud_api;
		$this->updates_count = $ctx->updates_count;

		// Convert raw market arrays to typed DTOs.
		$this->market = array();
		foreach ( $ctx->market as $raw ) {
			$this->market[] = new Botblocker_AddonMarketItemData( $raw );
		}

		$this->marketBySlug = array();
		foreach ( $this->market as $item ) {
			$this->marketBySlug[ $item->slug ] = $item;
		}

		// Convert raw installed addons to typed DTOs.
		$this->addons = array();
		foreach ( $ctx->addons as $slug => $raw ) {
			$this->addons[ $slug ] = new Botblocker_AddonInstalledItemData( $slug, $raw );
		}

		$installed_cards = array();
		$addons_url      = $this->urls->addons;
		foreach ( $this->market as $item ) {
			if ( ! $item->is_installed ) {
				continue;
			}
			$local               = isset( $this->addons[ $item->slug ] ) ? $this->addons[ $item->slug ] : null;
			$card                = Botblocker_AddonMarketCardData::from_installed_market( $item, $local, $addons_url );
			$installed_cards[]   = array(
				'name' => $card->name,
				'card' => $card,
			);
		}
		foreach ( $this->addons as $slug => $addon ) {
			if ( isset( $this->marketBySlug[ $slug ] ) ) {
				continue;
			}
			$card              = Botblocker_AddonMarketCardData::from_local_installed( $addon, $addons_url );
			$installed_cards[] = array(
				'name' => $card->name,
				'card' => $card,
			);
		}
		usort(
			$installed_cards,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);
		$this->marketplace_installed_cards = array();
		foreach ( $installed_cards as $row ) {
			$this->marketplace_installed_cards[] = $row['card'];
		}

		$available_cards = array();
		foreach ( $this->market as $item ) {
			if ( $item->is_installed ) {
				continue;
			}
			$card              = Botblocker_AddonMarketCardData::from_available_market( $item );
			$available_cards[] = array(
				'name' => $card->name,
				'card' => $card,
			);
		}
		usort(
			$available_cards,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);
		$this->marketplace_available_cards = array();
		foreach ( $available_cards as $row ) {
			$this->marketplace_available_cards[] = $row['card'];
		}

		// Build addon settings tabs - only when add-ons are unlocked (PRO or local mode).
		// Locked: skip entirely, no addon hooks triggered.
		$this->addon_tabs = array();
		if ( ! $this->addons_locked ) {
			$bbcs_addons = BotBlockerAddons::scanAll();
			$bbcs_active = BotBlockerAddons::getActive();

			foreach ( $bbcs_active as $bbcs_slug ) {
				if ( ! isset( $bbcs_addons[ $bbcs_slug ] ) || ! $bbcs_addons[ $bbcs_slug ]['valid'] ) {
					continue;
				}
				if ( empty( $bbcs_addons[ $bbcs_slug ]['has_settings'] ) ) {
					continue;
				}
				$bbcs_name = $bbcs_addons[ $bbcs_slug ]['name'] ? $bbcs_addons[ $bbcs_slug ]['name'] : $bbcs_slug;
				$bbcs_tab  = new Botblocker_AddonTabData( $bbcs_slug, $bbcs_name );
				if ( ! empty( $bbcs_addons[ $bbcs_slug ]['icon'] ) ) {
					$bbcs_tab->withIconImage( $bbcs_addons[ $bbcs_slug ]['icon'] );
				} else {
					$bbcs_tab->withIconImage( BOTBLOCKER_URL . 'public/icons/plugins.svg' );
				}
				$this->addon_tabs[ $bbcs_slug ] = $bbcs_tab;
			}

			uasort(
				$this->addon_tabs,
				static function ( $a, $b ) {
					return strcasecmp( $a->name, $b->name );
				}
			);
		}

		// Vertical sidebar nav: per-addon settings tabs + market items (non-PRO).
		$items = array();

		$tabpanels = array(
			'Marketplace' => BOTBLOCKER_DIR . 'admin/templates/addons/marketplace-panel.php',
		);

		foreach ( $this->addon_tabs as $slug => $addon ) {			
			$tab_item = ( new TabItem( $slug, '', false, '', '', $addon->name, 'puzzle' ) )
				->withIconImage( BOTBLOCKER_URL . 'public/icons/plugins.svg' );
			if ( $addon->icon_image !== '' ) {
				$tab_item->withIconImage( $addon->icon_image );
			}
			$items[] = $tab_item;
		}

		// For non-PRO users, add marketplace addons as searchable snav items.
		if ( ! $this->has_cloud_api ) {
			foreach ( $this->market as $m ) {
				$tab = new TabItem(
					'market-' . $m->slug,
					'',
					false,
					'',
					'',
					$m->name ?: $m->slug,
					'puzzle'
				);
				if ( $m->icon ) {
					$tab->withIconImage( $m->icon );
				}
				$items[] = $tab;
			}
		}

		usort(
			$items,
			static function ( $a, $b ) {
				return strcasecmp( $a->label, $b->label );
			}
		);

		$this->nav_groups = array(
			array(
				'title' => __( 'Addons', 'botblocker-security' ),
				'icon'  => 'puzzle',
				'items' => $items,
			),
		);

		$this->tabpanels = $tabpanels;

		$this->active_tab_id = 'Marketplace';
	}
}
