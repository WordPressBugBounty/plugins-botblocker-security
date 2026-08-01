<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Cyber_Secure_Botblocker {

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

		$this->load_dependencies();
		add_filter( 'load_textdomain_mofile', array( $this, 'bbcs_force_local_translations' ), 20, 2 );
		add_action( 'init', array( $this, 'set_locale' ), 0 );
		$this->define_admin_hooks();
	}

	private function load_dependencies(): void {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-botblocker-loader.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-botblocker-i18n.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/components/component-loader.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-botblocker-admin.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/botblocker/class-botblocker.php';
		$this->loader = new Botblocker_Loader();
	}

	public function set_locale(): void {
		unload_textdomain( $this->plugin_name );

		$plugin_i18n = new Botblocker_i18n();

		if ( isset( $_COOKIE['bbcs_preferred_language'] ) ) {
			$locale = preg_replace( '/[^a-zA-Z0-9_.-]/', '', sanitize_text_field( wp_unslash( $_COOKIE['bbcs_preferred_language'] ) ) );
			$f_path = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo';
			if ( file_exists( $f_path ) ) {
				load_textdomain(
					'botblocker-security',
					BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo'
				);

			} else {
				load_textdomain(
					'botblocker-security',
					BOTBLOCKER_DIR . 'languages/botblocker-security-en_US.mo'
				);
			}
		} else {
			$plugin_i18n->load_plugin_textdomain();
		}
	}

	private function define_admin_hooks(): void {
		$plugin_admin = Botblocker_Admin::getInstance();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 999 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 999 );
	}

	public function run(): void {
		$this->loader->run();
		$botBlocker = BotBlocker::getInstance();
		$botBlocker->init_visitor_pages();
		$botBlocker->initialize();
	}

	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version(): string {
		return $this->version;
	}

	public function bbcs_force_local_translations( string $mofile, string $domain ): string {
		if ( $domain !== $this->plugin_name ) {
			return $mofile;
		}

		if ( isset( $_COOKIE['bbcs_preferred_language'] ) ) {
			$preferred      = preg_replace( '/[^a-zA-Z0-9_.-]/', '', sanitize_text_field( wp_unslash( $_COOKIE['bbcs_preferred_language'] ) ) );
			$preferred_path = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $preferred . '.mo';
			if ( file_exists( $preferred_path ) ) {
				return $preferred_path;
			}
		}

		$locale       = determine_locale();
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
