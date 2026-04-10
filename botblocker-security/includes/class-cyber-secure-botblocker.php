<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Cyber_Secure_Botblocker
{
	protected $loader;
	protected $plugin_name;
	protected $version;
	public function __construct()
	{
		if (defined('BOTBLOCKER_VERSION')) {
			$this->version = BOTBLOCKER_VERSION;
		} else {
			$this->version = '1.1.0';
		}
		$this->plugin_name = 'botblocker-security';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-botblocker-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-botblocker-i18n.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-botblocker-admin.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/botblocker/class-botblocker.php';
		$this->loader = new Botblocker_Loader();
	}

	private function set_locale()
	{
		$plugin_i18n = new Botblocker_i18n();

		if (isset($_COOKIE['bbcs_preferred_language'])) {
			$f_path = BOTBLOCKER_DIR . 'languages/botblocker-security-' . sanitize_text_field(wp_unslash($_COOKIE['bbcs_preferred_language'])) . '.mo';
			if(file_exists($f_path)){
				load_textdomain(
					'botblocker-security',
					BOTBLOCKER_DIR . 'languages/botblocker-security-' . sanitize_text_field(wp_unslash($_COOKIE['bbcs_preferred_language'])) . '.mo'
				);

			} else{
				load_textdomain(
					'botblocker-security',
					BOTBLOCKER_DIR . 'languages/botblocker-security-en_US.mo'
				);
			}
		} else {
			$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
		}
	}

	private function define_admin_hooks()
	{
		$plugin_admin = new Botblocker_Admin($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
	}

	public function run()
	{
		$this->loader->run();
		$botBlocker = BotBlocker::getInstance();
		$botBlocker->init_visitor_pages();
		$botBlocker->initialize();
	}

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_loader()
	{
		return $this->loader;
	}

	public function get_version()
	{
		return $this->version;
	}
}