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

		if ( defined( 'BBCS_NEW_ADMIN_UI' ) && BBCS_NEW_ADMIN_UI ) {
			$this->build_status_data_full( $bbcs );
		} else {
			$this->build_status_data( $bbcs );
		}

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

	private function build_status_data( BotBlocker $bbcs ): void {
		$settings = $bbcs->settings;
		$recaptchaReady = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );

		// Group 0: Detection and connectivity
		$group0 = array();
		$g0_items = array(
			array( 'key' => 'bbcs_captcha_mode', 'label' => __( 'Captcha enabled', 'botblocker-security' ), 'func' => 'bbcs_captcha_mode' ),
			array( 'key' => 'block_empty_ua', 'label' => __( 'Block empty User-Agent', 'botblocker-security' ), 'func' => 'block_empty_ua' ),
			array( 'key' => 'block_simplebot_ua', 'label' => __( 'Block simple bots by UA', 'botblocker-security' ), 'func' => 'block_simplebot_ua' ),
			array( 'key' => 'block_nojs_users', 'label' => __( 'Block no-JavaScript', 'botblocker-security' ), 'func' => 'block_nojs_users' ),
			array( 'key' => 'block_ip_ptr_match', 'label' => __( 'PTR / DNS anomalies', 'botblocker-security' ), 'func' => 'block_ip_ptr_match' ),
			array( 'key' => 'block_proxy_users', 'label' => __( 'Classic proxies', 'botblocker-security' ), 'func' => 'block_proxy_users' ),
			array( 'key' => 'block_ipv6_users', 'label' => __( 'IPv6 connections', 'botblocker-security' ), 'func' => 'block_ipv6_users' ),
			array( 'key' => 'block_http10_users', 'label' => __( 'HTTP/1.0 users', 'botblocker-security' ), 'func' => 'block_http10_users' ),
		);
		foreach ( $g0_items as $gi ) {
			$item = new Botblocker_StatusItemData( $gi['label'], BotBlockerHealthService::isEnabled( $gi['func'], $settings, $recaptchaReady ) );
			$item->key = $gi['key'];
			$group0[] = $item;
		}

		// Group 1: Browser and protection
		$group1 = array();
		$g1_items = array(
			array( 'key' => 'block_simple_antidetect', 'label' => __( 'JS consistency checks', 'botblocker-security' ), 'func' => 'block_simple_antidetect', 'pro' => false ),
			array( 'key' => 'iframe_stop', 'label' => __( 'Clickjacking protection', 'botblocker-security' ), 'func' => 'iframe_stop', 'pro' => false ),
			array( 'key' => 'samesite', 'label' => __( 'SameSite cookies', 'botblocker-security' ), 'func' => 'samesite', 'pro' => false ),
			array( 'key' => 'check', 'label' => __( 'Cloud threat verification', 'botblocker-security' ), 'func' => 'check', 'pro' => false ),
			array( 'key' => 'block_vpn_users', 'label' => __( 'VPN blocking', 'botblocker-security' ), 'func' => 'block_vpn_users', 'pro' => true ),
			array( 'key' => 'block_tor_users', 'label' => __( 'Tor blocking', 'botblocker-security' ), 'func' => 'block_tor_users', 'pro' => true ),
			array( 'key' => 'block_override', 'label' => __( 'Spoofing detection', 'botblocker-security' ), 'func' => 'block_override', 'pro' => true ),
			array( 'key' => 'block_device_options', 'label' => __( 'Device API verification', 'botblocker-security' ), 'func' => 'block_device_options', 'pro' => true ),
		);
		foreach ( $g1_items as $gi ) {
			$item = new Botblocker_StatusItemData( $gi['label'], BotBlockerHealthService::isEnabled( $gi['func'], $settings, $recaptchaReady ), false, $gi['pro'] );
			$item->key = $gi['key'];
			$group1[] = $item;
		}

		// Group 2: Data and notifications
		$group2 = array();
		$g2_items = array(
			array( 'key' => 'get_browser_type', 'label' => __( 'Browser data collection', 'botblocker-security' ), 'func' => 'get_browser_type' ),
			array( 'key' => 'get_os_type', 'label' => __( 'OS data collection', 'botblocker-security' ), 'func' => 'get_os_type' ),
			array( 'key' => 'telegram_notifications', 'label' => __( 'Telegram notifications', 'botblocker-security' ), 'func' => null ),
			array( 'key' => 'email_notifications', 'label' => __( 'Email notifications', 'botblocker-security' ), 'func' => 'email_notifications' ),
			array( 'key' => 'autosave_admin_ip', 'label' => __( 'Save admin IPs', 'botblocker-security' ), 'func' => 'autosave_admin_ip' ),
			array( 'key' => 'allow_self_ip_req', 'label' => __( 'Requests from server IP', 'botblocker-security' ), 'func' => null ),
		);
		foreach ( $g2_items as $gi ) {
			if ( $gi['key'] === 'telegram_notifications' ) {
				$item = new Botblocker_StatusItemData( $gi['label'], ! empty( $settings->telegram_bot_token ) && ! empty( $settings->telegram_chat_id ) );
			} elseif ( $gi['key'] === 'allow_self_ip_req' ) {
				$item = new Botblocker_StatusItemData( $gi['label'], ! empty( $settings->allow_self_ip_req ), true );
			} else {
				$item = new Botblocker_StatusItemData( $gi['label'], BotBlockerHealthService::isEnabled( $gi['func'], $settings, $recaptchaReady ) );
			}
			$item->key = $gi['key'];
			$group2[] = $item;
		}

		$this->status_groups       = array( $group0, $group1, $group2 );
		$this->status_group_titles = array(
			__( 'Detection and connectivity', 'botblocker-security' ),
			__( 'Browser and protection', 'botblocker-security' ),
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
