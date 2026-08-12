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

final class Botblocker_IntegrationsViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var array<string,mixed> */
	private $settings;

	/** @var bool */
	public $has_pro;
	/** @var bool */
	public $has_cloud_api;
	/** @var bool */
	public $has_recaptcha_v2;
	/** @var bool */
	public $has_recaptcha_v3;
	/** @var bool */
	public $recaptcha_v3_keys_ready;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var string */
	public $docs_url;

	/** @var array */
	public $nav_groups;

	/** @var array<string,string> */
	public $tabpanels;

	/** @var array<string, array{state:string, warning:string, label:string}> */
	public $cache_systems;

	/** @var string */
	public $active_tab_id;

	/** @var string */
	public $qr_url;
	/** @var array */
	public $backup_codes;
	/** @var bool */
	public $is_2fa_verified;
	/** @var array */
	public $wp_roles;

	/** @var string */
	public $integrations_url;
	/** @var string */
	public $settings_url;

	/** @var bool */
	public $has_redis_ext;
	/** @var bool */
	public $has_memcached_ext;

	/** @var bool */
	public $is_cloud_api_active;
	/** @var string */
	public $cloud_api_key;
	/** @var int|false */
	public $remaining_hits;
	/** @var int|false */
	public $remaining_days;
	/** @var string */
	public $connect_nonce;
	/** @var string */
	public $deactivate_nonce;
	/** @var string */
	public $fetch_key_nonce;

	/** @var bool */
	public $mu_active;
	/** @var bool */
	public $early_active;
	/** @var bool */
	public $mu_geo_active;
	/** @var string */
	public $mu_loader_path;
	/** @var bool */
	public $mu_loader_exists;
	/** @var string */
	public $data_dir;
	/** @var bool */
	public $ip_file_exists;
	/** @var bool */
	public $hotbans_file_exists;

	public function __construct() {
		$BBCS  = BotBlocker::getInstance();
		$BBCSA = Botblocker_Admin::getInstance();

		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();

		$this->settings = is_object( $BBCS->settings ) ? get_object_vars( $BBCS->settings ) : array();

		$this->has_pro        = BotBlockerPro::isActive();
		$this->has_cloud_api  = BotBlockerPro::isActive();

		if ( isset( $BBCS->settings ) ) {
			$this->has_recaptcha_v2 = ! empty( $BBCS->settings->recaptcha_key2 ) && ! empty( $BBCS->settings->recaptcha_secret2 );
			$this->has_recaptcha_v3 = ! empty( $BBCS->settings->recaptcha_key3 ) && ! empty( $BBCS->settings->recaptcha_secret3 );
		} else {
			$this->has_recaptcha_v2 = false;
			$this->has_recaptcha_v3 = false;
		}

		$this->recaptcha_v3_keys_ready = class_exists( 'BotBlockerUI' ) ? BotBlockerUI::recaptcha_v3_keys_ready() : false;

		$this->urls            = new Botblocker_DashboardUrls();
		$this->urls->cloud_api = $BBCSA->pages_cloud_api;
		$this->urls->settings  = $BBCSA->pages_settings;
		$this->urls->setup     = $BBCSA->pages_setup;
		$this->urls->reports   = $BBCSA->pages_reports;
		$this->urls->addons    = $BBCSA->pages_addons;
		$this->urls->wizard    = $BBCSA->pages_wizard;
		$this->urls->pricing   = 'https://botblocker.com/pricing/';

		$this->docs_url          = BOTBLOCKER_DOCS_URL;
		$this->integrations_url  = $BBCSA->pages_integrations;
		$this->settings_url      = $BBCSA->pages_settings;

		$this->has_redis_ext     = extension_loaded( 'redis' );
		$this->has_memcached_ext = extension_loaded( 'memcached' );

		$this->is_cloud_api_active = BotBlockerPro::isActive();
		$this->cloud_api_key       = $this->is_cloud_api_active ? BotBlockerPro::getKey() : '';
		$this->remaining_hits      = BotBlockerPro::getRemainingHits();
		$this->remaining_days      = BotBlockerPro::getRemainingDays();
		$this->connect_nonce       = wp_create_nonce( 'bbcs_connect_cloud_api_action' );
		$this->deactivate_nonce    = wp_create_nonce( 'bbcs_deactivate_cloud_api_action' );
		$this->fetch_key_nonce     = wp_create_nonce( 'bbcs_fetch_cloud_api_key_action' );

		// Vertical sidebar nav - single group with all integration tabs
		$this->nav_groups = array(
			array(
				'title' => __( 'Integrations', 'botblocker-security' ),
				'icon'  => 'plug',
				'items' => array(
					( new TabItem( 'recaptcha-v2',  '', true,  '', '', __( 'reCaptcha v2', 'botblocker-security' ),      'recaptcha' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/google.svg' ),
					( new TabItem( 'recaptcha-v3',  '', false, '', '', __( 'reCaptcha v3', 'botblocker-security' ),      'recaptcha' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/google.svg' ),
					( new TabItem( 'transients',    '', false, '', '', __( 'Transients', 'botblocker-security' ),        'transient' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/transient.svg' ),
					( new TabItem( 'memcached',     '', false, '', '', __( 'Memcached', 'botblocker-security' ),         'memory' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/memcached.svg' ),
					( new TabItem( 'redis',         '', false, '', '', __( 'Redis', 'botblocker-security' ),             'server' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/redis.svg' ),
					( new TabItem( 'cloud',         '', false, '', '', __( 'BotBlocker Cloud', 'botblocker-security' ),  'sync' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/cloud-api.svg' ),
					( new TabItem( 'cache',         '', false, '', '', __( 'Cache', 'botblocker-security' ),            'rocket' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/rocket.svg' ),
					( new TabItem( 'bbcs-2fa',      '', false, '', '', __( 'BotBlocker 2FA', 'botblocker-security' ),    '2fa' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/qrcode.svg' ),
					( new TabItem( 'wp-connectors', '', false, '', '', __( 'WordPress Connectors', 'botblocker-security' ), 'plug' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/wordpress.svg' ),
					( new TabItem( 'mu',             '', false, '', '', __( 'MU-plugin', 'botblocker-security' ),             'tools' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/tools.svg' ),
				),
			),
		);

		$this->tabpanels = array(
			'recaptcha-v2'  => BOTBLOCKER_DIR . 'admin/templates/integrations/recaptcha-v2.php',
			'recaptcha-v3'  => BOTBLOCKER_DIR . 'admin/templates/integrations/recaptcha-v3.php',
			'transients'    => BOTBLOCKER_DIR . 'admin/templates/integrations/transients.php',
			'memcached'     => BOTBLOCKER_DIR . 'admin/templates/integrations/memcached.php',
			'redis'         => BOTBLOCKER_DIR . 'admin/templates/integrations/redis.php',
			'cloud'         => BOTBLOCKER_DIR . 'admin/templates/integrations/botblocker-api.php',
			'cache'         => BOTBLOCKER_DIR . 'admin/templates/integrations/cache.php',
			'bbcs-2fa'      => BOTBLOCKER_DIR . 'admin/templates/integrations/2fa.php',
			'wp-connectors' => BOTBLOCKER_DIR . 'admin/templates/integrations/wp-connectors.php',
			'mu'            => BOTBLOCKER_DIR . 'admin/templates/integrations/mu.php',
		);

		$this->active_tab_id = 'recaptcha-v2';

		$this->cache_systems = class_exists( 'BotBlockerAlerts' ) ? BotBlockerAlerts::detectCacheSystems() : array();

		$user_id = get_current_user_id();
		$user    = wp_get_current_user();

		$has_secret = get_user_meta( $user_id, '_2fa_secret', true );
		$is_verified = get_user_meta( $user_id, '_2fa_verified', true );
		$this->is_2fa_verified = ( ! empty( $has_secret ) && ! empty( $is_verified ) );

		$secret = get_user_meta( $user_id, '_2fa_secret_temp', true );
		if ( empty( $secret ) ) {
			$secret = get_user_meta( $user_id, '_2fa_secret', true );
		}

		if ( ! empty( $secret ) ) {
			global $bbcs_google_auth;
			if ( isset( $bbcs_google_auth ) ) {
				$this->qr_url = $bbcs_google_auth->getQRCodeUrl( $user->user_email, $secret );
			} else {
				$this->qr_url = '';
			}
		} else {
			$this->qr_url = '';
		}

		$this->backup_codes = array();
		$codes = get_user_meta( $user_id, '_2fa_backup_codes_temp', true );
		if ( ! empty( $codes ) && is_array( $codes ) ) {
			$this->backup_codes = $codes;
		}

		$this->wp_roles = wp_roles()->roles;

		$this->mu_active    = ! empty( $this->settings['mu_enable'] );
		$this->early_active = ! empty( $this->settings['early_init_enable'] );
		$this->mu_geo_active = ! empty( $this->settings['mu_geo_enable'] );
		$this->data_dir     = BotBlockerMultisite::getDataDir();
		$this->mu_loader_path = ( defined( 'WPMU_PLUGIN_DIR' ) && WPMU_PLUGIN_DIR !== '' )
			? trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php'
			: '';
		$this->mu_loader_exists  = $this->mu_loader_path !== '' && file_exists( $this->mu_loader_path );
		$this->ip_file_exists    = file_exists( $this->data_dir . 'ip.php' );
		$this->hotbans_file_exists = file_exists( $this->data_dir . 'hot-bans.php' );
	}

	public function get( string $key, $default = '' ) {
		return $this->settings[ $key ] ?? $default;
	}

	public function is_checked( string $key, $expected = '1' ): bool {
		$val = $this->settings[ $key ] ?? '0';
		return (string) $val === (string) $expected;
	}
}
