<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerSecurity {

	protected $loader;
	protected $plugin_name;
	protected $version;
	public function __construct() {
		if ( defined( 'BOTBLOCKER_VERSION' ) ) {
			$this->version = BOTBLOCKER_VERSION;
		} else {
			$this->version = '1.1.0';
		}
		$this->plugin_name = 'botblocker-security';

		$this->loadDependencies();
		add_filter( 'load_textdomain_mofile', array( $this, 'forceLocalTranslations' ), 20, 2 );
		add_action( 'init', array( $this, 'setLocale' ), 0 );
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			$this->defineAdminHooks();
		}
	}

	private function loadDependencies(): void {
		$this->loadShieldDependencies();
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			$this->loadAdminDependencies();
		}
	}

	private function loadShieldDependencies(): void {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-botblocker-loader.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-botblocker-i18n.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/botblocker/class-botblocker.php';
		$this->loader = new Botblocker_Loader();
	}

	private function loadAdminDependencies(): void {
		require_once plugin_dir_path( __DIR__ ) . 'includes/components/component-loader.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-botblocker-admin.php';
	}

	public function setLocale(): void {
		unload_textdomain( $this->plugin_name );

		$plugin_i18n = new Botblocker_i18n();

		if ( isset( $_COOKIE['bbcs_preferred_language'] ) ) {
			$locale = preg_replace( '/[^a-zA-Z0-9_.-]/', '', sanitize_text_field( wp_unslash( $_COOKIE['bbcs_preferred_language'] ) ) );

			$wp_lang_path = WP_LANG_DIR . '/plugins/botblocker-security-' . $locale . '.mo';
			if ( file_exists( $wp_lang_path ) ) {
				load_textdomain( 'botblocker-security', $wp_lang_path );
			} else {
				$local_path = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo';
				if ( file_exists( $local_path ) ) {
					load_textdomain( 'botblocker-security', $local_path );
				} else {
					load_textdomain(
						'botblocker-security',
						BOTBLOCKER_DIR . 'languages/botblocker-security-en_US.mo'
					);
				}
			}
		} else {
			$plugin_i18n->load_plugin_textdomain();
		}
	}

	private function defineAdminHooks(): void {
		$plugin_admin = Botblocker_Admin::getInstance();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 999 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 999 );
	}

	public function run(): void {
		if ( $this->loader !== null ) {
			$this->loader->run();
		}
		$botBlocker = BotBlocker::getInstance();
		$botBlocker->init_visitor_pages();
		$botBlocker->initialize();
	}

	public function getPluginName(): string {
		return $this->plugin_name;
	}

	public function getLoader() {
		return $this->loader;
	}

	public function getVersion(): string {
		return $this->version;
	}

	public function forceLocalTranslations( string $mofile, string $domain ): string {
		if ( $domain !== $this->plugin_name ) {
			return $mofile;
		}

		if ( isset( $_COOKIE['bbcs_preferred_language'] ) ) {
			$preferred      = preg_replace( '/[^a-zA-Z0-9_.-]/', '', sanitize_text_field( wp_unslash( $_COOKIE['bbcs_preferred_language'] ) ) );

			$wp_preferred = WP_LANG_DIR . '/plugins/botblocker-security-' . $preferred . '.mo';
			if ( file_exists( $wp_preferred ) ) {
				return $wp_preferred;
			}

			$local_preferred = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $preferred . '.mo';
			if ( file_exists( $local_preferred ) ) {
				return $local_preferred;
			}
		}

		$locale = determine_locale();

		$wp_mofile = WP_LANG_DIR . '/plugins/botblocker-security-' . $locale . '.mo';
		if ( file_exists( $wp_mofile ) ) {
			return $wp_mofile;
		}

		$local_mofile = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo';
		if ( file_exists( $local_mofile ) ) {
			return $local_mofile;
		}

		$default = BOTBLOCKER_DIR . 'languages/botblocker-security-en_US.mo';
		if ( file_exists( $default ) ) {
			return $default;
		}

		return $mofile;
	}
}
