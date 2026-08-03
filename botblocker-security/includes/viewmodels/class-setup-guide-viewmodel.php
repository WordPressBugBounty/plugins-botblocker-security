<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\HealthGauge;
use BotBlocker\Component\HealthItemData;

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once __DIR__ . '/class-chain-context-data.php';
require_once BOTBLOCKER_DIR . 'includes/services/class-botblocker-health-service.php';
require_once BOTBLOCKER_DIR . 'includes/helpers/class-health-score-helper.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-status-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-addon-market-item-data.php';

final class Botblocker_SetupGuideViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var int */
	public $health_score;
	/** @var string */
	public $health_label;
	/** @var HealthItemData[][] */
	public $raw_items_chunked;
	/** @var bool */
	public $has_pro;
	/** @var bool */
	public $has_cloud_api;
	/** @var bool */
	public $early_addon_active;
	/** @var bool */
	public $early_available;
	/** @var Botblocker_ChainContextData */
	public $chain_context;

	/** @var string */
	public $cloud_api_url;
	/** @var string */
	public $setup_url;
	/** @var string */
	public $settings_url;
	/** @var string */
	public $integrations_url;
	/** @var string */
	public $addons_url;
	/** @var string */
	public $wizard_url;

	/** @var HealthGauge */
	public $health_gauge;

	/** @var Botblocker_StatusItemData[][] Groups of status items */
	public $status_groups;

	/** @var string[] Titles for the status groups */
	public $status_group_titles;
	/** @var int */
	public $status_active_count;
	/** @var int */
	public $status_disabled_count;
	/** @var int */
	public $status_attention_count;

	/** @var string */
	public $system_os;
	/** @var string */
	public $system_web;
	/** @var string */
	public $system_db_version;
	/** @var string */
	public $system_php_version;
	/** @var string */
	public $system_wp_version;
	/** @var string */
	public $system_bb_version;
	/** @var string */
	public $system_memory_limit;
	/** @var string */
	public $system_max_execution_time;
	/** @var string */
	public $system_upload_max_filesize;

	/** @var string HTML-formatted changelog */
	public $changelog_html;

	/** @var bool */
	public $early_init_active;
	/** @var bool */
	public $mu_active;

	/** @var string */
	public $wp_version;
	/** @var string */
	public $bb_version;
	/** @var string */
	public $php_version;
	/** @var string */
	public $mysql_version;

	/** @var Botblocker_AddonMarketItemData[] Marketplace add-ons available for install */
	public $market_addons;



	public function __construct() {
		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();

		$bbcs  = BotBlocker::getInstance();
		$bbcsa = Botblocker_Admin::getInstance();

		$this->cloud_api_url    = $bbcsa->pages_cloud_api;
		$this->setup_url        = $bbcsa->pages_setup;
		$this->settings_url     = $bbcsa->pages_settings;
		$this->integrations_url = $bbcsa->pages_integrations;
		$this->addons_url       = $bbcsa->pages_addons;
		$this->wizard_url       = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' );

		$this->has_pro       = BotBlockerPro::isActive();
		$this->has_cloud_api = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();

		$early_addon               = class_exists( 'BotBlockerAddons' )
			? BotBlockerAddons::hasActiveProvider( 'early_init_provider', 'bbcs_early_init_provider_active' )
			: false;
		$this->early_addon_active  = $early_addon;
		$this->early_available     = $this->has_cloud_api && $early_addon;

		$this->health_score = bbcs_calculateSiteHealth();
		$this->health_label = Botblocker_HealthScoreHelper::getLabel( $this->health_score );

		$this->health_gauge = HealthGauge::make()
			->withValue( $this->health_score )
			->withMax( 100 )
			->withLabel( $this->health_label );

		$settings              = isset( $bbcs->settings ) ? $bbcs->settings : (object) array();
		$recaptcha_ready       = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );
		$this->raw_items_chunked = BotBlockerHealthService::getChunkedHealthItems( $settings, $recaptcha_ready );

		$this->build_status_data_full( $bbcs );

		$this->chain_context = new Botblocker_ChainContextData(
			$this->early_init_active,
			$this->mu_active,
			true,
			true,
			$this->early_available,
			$this->cloud_api_url,
			$this->addons_url
		);

		// Fetch marketplace add-ons for the add-ons card
		$addons_ctx = BotBlockerUI::get_addons_context();
		$this->market_addons = array();
		foreach ( $addons_ctx['market'] as $raw ) {
			$this->market_addons[] = new Botblocker_AddonMarketItemData( $raw );
		}

	}

	public function is_early_available_for_display(): bool {
		return $this->early_available;
	}

	private function build_status_data_full( BotBlocker $bbcs ): void {
		$settings = $bbcs->settings;
		$recaptchaReady = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );

		// Use shared definitions from the health service (matches old render path exactly)
		$definitions = BotBlockerHealthService::getDefinitions();

		// Build Botblocker_StatusItemData items from definitions
		$all_items = array();
		foreach ( $definitions as $def ) {
			$enabled = BotBlockerHealthService::isEnabled( $def['key'], $settings, $recaptchaReady );
			$ok      = false;
			$warn    = false;
			$pro     = false;

			switch ( $def['type'] ) {
				case 'negative':
					// Negative: enabled → warn (attention), disabled → ok
					if ( $enabled ) {
						$warn = true;
					} else {
						$ok = true;
					}
					break;
				case 'cloud_extended':
					// PRO features
					$ok  = $enabled;
					$pro = true;
					break;
				default:
					// core and neutral: enabled → ok
					$ok = $enabled;
					break;
			}

			$item = new Botblocker_StatusItemData( $def['label'], $ok, $warn, $pro );
			$item->key = $def['key'];
			$all_items[] = $item;
		}

		// Split into 5 groups
		$this->status_groups = array(
			array_slice( $all_items, 0, 12 ),   // Group 0: Detection and connectivity
			array_slice( $all_items, 12, 6 ),    // Group 1: Browser checks
			array_slice( $all_items, 18, 6 ),    // Group 2: Cookie / Session
			array_slice( $all_items, 24, 7 ),    // Group 3: Cloud / PRO
			array_slice( $all_items, 31 ),       // Group 4: Data and notifications
		);

		$this->status_group_titles = array(
			__( 'Detection and connectivity', 'botblocker-security' ),
			__( 'Browser checks', 'botblocker-security' ),
			__( 'Cookie and session', 'botblocker-security' ),
			__( 'Cloud / PRO', 'botblocker-security' ),
			__( 'Data and notifications', 'botblocker-security' ),
		);

		// Compute counts
		$active    = 0;
		$disabled  = 0;
		$attention = 0;
		foreach ( $this->status_groups as $group ) {
			foreach ( $group as $item ) {
				if ( $item->warn ) {
					$attention++;
				} elseif ( $item->ok ) {
					$active++;
				} else {
					$disabled++;
				}
			}
		}
		$this->status_active_count    = $active;
		$this->status_disabled_count  = $disabled;
		$this->status_attention_count = $attention;

		// System information
		global $wpdb;
		$this->system_os               = PHP_OS_FAMILY . ' ' . php_uname( 'r' );
		$this->system_web              = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$this->system_db_version       = $wpdb->db_version();
		$this->system_php_version      = PHP_VERSION;
		$this->system_wp_version       = get_bloginfo( 'version' );
		$this->system_bb_version       = BOTBLOCKER_VERSION;
		$this->system_memory_limit     = ini_get( 'memory_limit' );
		$this->system_max_execution_time = ini_get( 'max_execution_time' );
		$this->system_upload_max_filesize = ini_get( 'upload_max_filesize' );

		// Changelog
		$this->changelog_html = $this->build_changelog_html();

		// Chain active flags
		$this->early_init_active = $this->has_cloud_api && $this->early_addon_active && ! empty( $settings->early_init_enable );
		$this->mu_active         = ! empty( $settings->mu_enable );

		// Version strings for rail
		$this->wp_version   = get_bloginfo( 'version' );
		$this->bb_version   = BOTBLOCKER_VERSION;
		$this->php_version  = PHP_VERSION;
		$this->mysql_version = $wpdb->db_version();
	}

	/**
	 * Build a tab URL for a given health key, including &focus=key
	 * so the JS (checkUrlFocusAndJump) can scroll directly to the specific field.
	 *
	 * Uses the centralized bbcs_get_setting_link() which resolves the correct
	 * admin page (Settings, Integrations, Tools, etc.) from the global search index.
	 */
	public function getItemTabUrl( string $key ): string {
		return bbcs_get_setting_link( $key, true );
	}

	private function build_changelog_html(): string {
		$changelog_file = BOTBLOCKER_DIR . 'readme.md';
		if ( ! file_exists( $changelog_file ) ) {
			return '';
		}
		$content = file_get_contents( $changelog_file );
		if ( $content === false ) {
			return '';
		}

		// Extract the most recent version entries (max 2) from the changelog section
		$lines   = explode( "\n", $content );
		$ver_idx = 0;
		$result  = array();
		$buffer  = array();

		foreach ( $lines as $line ) {
			$trimmed = trim( $line );
			if ( preg_match( '/^= \d+\.\d+\.\d+ =$/', $trimmed ) ) {
				// Flush previous buffer if we already have one version collected
				if ( count( $buffer ) > 0 && $ver_idx >= 1 ) {
					$result   = array_merge( $result, $buffer );
					$result[] = '';
				}
				$buffer = array( $trimmed );
				$ver_idx++;
				if ( $ver_idx > 2 ) {
					break;
				}
				continue;
			}
			if ( $ver_idx > 0 && $trimmed !== '' && substr( $trimmed, 0, 2 ) !== '==' ) {
				$buffer[] = '* ' . $trimmed;
			}
		}

		// Flush remaining buffer (last version we were collecting)
		if ( count( $buffer ) > 0 && $ver_idx <= 2 ) {
			$result = array_merge( $result, $buffer );
		}

		return implode( "\n", $result );
	}

	/**
	 * @return array{active: int, disabled: int, attention: int}
	 */
	public static function getCounts(): array {
		$bbcs  = BotBlocker::getInstance();
		$settings = $bbcs->settings;
		$recaptchaReady = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );

		$definitions = BotBlockerHealthService::getDefinitions();

		$active    = 0;
		$disabled  = 0;
		$attention = 0;
		foreach ( $definitions as $def ) {
			$enabled = BotBlockerHealthService::isEnabled( $def['key'], $settings, $recaptchaReady );
			switch ( $def['type'] ) {
				case 'negative':
					if ( $enabled ) {
						$attention++;
					} else {
						$active++;
					}
					break;
				default:
					if ( $enabled ) {
						$active++;
					} else {
						$disabled++;
					}
					break;
			}
		}

		return array(
			'active'    => $active,
			'disabled'  => $disabled,
			'attention' => $attention,
		);
	}
}
