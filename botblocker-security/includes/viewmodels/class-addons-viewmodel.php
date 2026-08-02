<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-market-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-installed-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-tab-data.php';
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

		$ctx              = BotBlockerUI::get_addons_context();
		$this->active     = $ctx['active'];
		$this->addons_locked = $ctx['addons_locked'];
		$this->has_cloud_api = $ctx['has_cloud_api'];
		$this->addons_local_mode = ! empty( $ctx['addons_local_mode'] );
		$this->updates_count = $ctx['updates_count'];

		// Convert raw market arrays to typed DTOs.
		$this->market = array();
		foreach ( $ctx['market'] as $raw ) {
			$this->market[] = new Botblocker_AddonMarketItemData( $raw );
		}

		$this->marketBySlug = array();
		foreach ( $this->market as $item ) {
			$this->marketBySlug[ $item->slug ] = $item;
		}

		// Convert raw installed addons to typed DTOs.
		$this->addons = array();
		foreach ( $ctx['addons'] as $slug => $raw ) {
			$this->addons[ $slug ] = new Botblocker_AddonInstalledItemData( $slug, $raw );
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
		}

		// Vertical sidebar nav: per-addon settings tabs + market items (non-PRO).
		$items = array();

		$tabpanels = array(
			'Marketplace' => BOTBLOCKER_DIR . 'admin/templates/addons/marketplace-panel.php',
		);

		foreach ( $this->addon_tabs as $slug => $addon ) {
			if ( ! apply_filters( 'bbcs_addon_show_in_nav', true, $slug, $addon ) ) {
				continue;
			}
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
