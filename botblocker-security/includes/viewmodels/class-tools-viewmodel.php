<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/components/class-botblocker-tab-item.php';

use BotBlocker\Component\TabItem;

final class Botblocker_ToolsViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;

	/** @var array */
	public $asn_status;
	/** @var bool */
	public $asn_present;

	/** @var array */
	public $rugov_status;
	/** @var bool */
	public $rugov_present;

	/** @var array */
	public $llm_status;

	/** @var string */
	public $tools_url;

	/** @var array */
	public $nav_groups;

	/** @var array<string,string> */
	public $tabpanels;

	/** @var string */
	public $active_tab_id;

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

		$this->docs_url  = BOTBLOCKER_DOCS_URL;
		$this->tools_url = BotBlockerMultisite::getAdminPageUrl( 'bbcs_tools' );

		$this->asn_status  = class_exists( 'BotBlockerAsnDb' ) ? BotBlockerAsnDb::getStatus() : array();
		$this->asn_present = class_exists( 'BotBlockerAsnDb' ) ? BotBlockerAsnDb::isPresent() : false;

		$this->rugov_status  = class_exists( 'BotBlockerRugov' ) ? BotBlockerRugov::getStatus() : array();
		$this->rugov_present = class_exists( 'BotBlockerRugov' ) ? BotBlockerRugov::isFilePresent() : false;

		$this->llm_status = BotBlockerLlmSync::getStatus();

		// Vertical sidebar nav for tools - 3 built-in tabs only.
		$items = array(
			( new TabItem( 'WordPress',   '', true,  '', '', __( 'WordPress Core', 'botblocker-security' ), 'wordpress-core' ) )
				->withIconImage( BOTBLOCKER_URL . 'public/icons/wordpress.svg' ),
			( new TabItem( 'BotBlocker',  '', false, '', '', __( 'BotBlocker', 'botblocker-security' ),      'bolt' ) )
				->withIconImage( BOTBLOCKER_URL . 'public/icons/rocket.svg' ),
			( new TabItem( 'Maintenance', '', false, '', '', __( 'Maintenance', 'botblocker-security' ),     'broom' ) )
				->withIconImage( BOTBLOCKER_URL . 'public/icons/database.svg' ),
		);

		$this->tabpanels = array(
			'WordPress'   => BOTBLOCKER_DIR . 'admin/templates/tools/wordpress-tab.php',
			'BotBlocker'  => BOTBLOCKER_DIR . 'admin/templates/tools/botblocker-tab.php',
			'Maintenance' => BOTBLOCKER_DIR . 'admin/templates/tools/maintenance-tab.php',
		);

		$this->nav_groups = array(
			array(
				'title' => __( 'Tools', 'botblocker-security' ),
				'icon'  => 'broom',
				'items' => $items,
			),
		);

		$this->active_tab_id = 'WordPress';
	}
}
