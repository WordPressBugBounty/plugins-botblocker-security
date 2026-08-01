<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-changelog-entry-data.php';

final class Botblocker_AboutViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;
	/** @var string */
	public $plugin_version;
	/** @var string */
	public $system_status_text;
	/** @var string */
	public $telegram_support_url;
	/** @var string */
	public $support_forum_url;
	/** @var string */
	public $email_support_url;

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
		$this->urls->about     = $BBCSA->pages_about;

		$this->docs_url = BOTBLOCKER_DOCS_URL;
		$this->telegram_support_url = defined( 'BOTBLOCKER_TELEGRAM_SUPPORT' ) ? BOTBLOCKER_TELEGRAM_SUPPORT : '';
		$this->support_forum_url    = defined( 'BOTBLOCKER_SUPPORT_FORUM' ) ? BOTBLOCKER_SUPPORT_FORUM : '';
		$this->email_support_url    = defined( 'BOTBLOCKER_MAILTO_LINK' ) ? BOTBLOCKER_MAILTO_LINK : '';

		$this->plugin_version = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '';

		$info = BotBlockerSystemInfoData::getInstance();
		$this->system_status_text  = __( 'OS:', 'botblocker-security' ) . ' ' . $info->os . "\n";
		$this->system_status_text .= __( 'Web:', 'botblocker-security' ) . ' ' . $info->web . "\n";
		/* translators: 1: DB version, 2: PHP version */
		$this->system_status_text .= sprintf( '%1$s%2$s · %3$s%4$s', __( 'DB: v', 'botblocker-security' ), $info->db_version, __( 'PHP: v', 'botblocker-security' ), $info->php ) . "\n";
		$this->system_status_text .= __( 'WordPress: v', 'botblocker-security' ) . $info->wp . "\n";
		if ( $info->bb_version !== '' ) {
			$this->system_status_text .= __( 'BotBlocker: v', 'botblocker-security' ) . $info->bb_version . "\n";
		}
		$this->system_status_text .= "\n";
		$this->system_status_text .= 'memory_limit: ' . $info->memory . "\n";
		$this->system_status_text .= 'max_execution_time: ' . $info->max_exec . "\n";
		$this->system_status_text .= 'upload_max_filesize: ' . $info->upload_max;

	}
}
