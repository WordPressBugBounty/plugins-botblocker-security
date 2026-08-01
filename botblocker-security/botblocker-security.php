<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * The BotBlocker Security bootstrap file
 *
 * This file is responsible for loading the necessary files and initializing the plugin functionality.
 * It defines various constants and includes the main class file, which is responsible for running the plugin.
 * The plugin is activated and deactivated using the activate_botblocker() and deactivate_botblocker() functions.
 * The bbcs_run_botblocker_shield() function initializes the plugin and its admin functionality.
 *
 * @link              https://globus.studio
 * @package           botblocker-security
 * @version           1.7.1
 *
 * @wordpress-plugin
 * Plugin Name:       BotBlocker Security - Firewall & Bot Protection
 * Plugin URI:        https://botblocker.top/
 * Description:       Blocks bots, brute force attacks, spam and automated threats in real time. Captcha, IP rules, proxy/vpn/tor detection, login protection, reCAPTCHA, customizable security rules - all in one plugin. Maximum Security for WordPress.
 * Version:           1.7.1
 * Author:            Yevhen Leonidov
 * Author URI:        https://leonidov.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.1
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Text Domain:       botblocker-security
 * Domain Path:       /languages
 */


// Check minimum requirements (PHP)
if ( version_compare( phpversion(), '7.4.0', '<' ) ) {
	function bbcs_minimum_php_version_notice(): void {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'BotBlocker requires PHP 7.4 or higher.', 'botblocker-security' ) . '</p></div>';
	}
	add_action( 'admin_notices', 'bbcs_minimum_php_version_notice' );
	return;
}

// Check minimum requirements (WordPress)
if ( version_compare( $GLOBALS['wp_version'], '5.1', '<' ) ) {
	function bbcs_minimum_wp_version_notice(): void {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'BotBlocker requires WordPress 5.1 or later.', 'botblocker-security' ) . '</p></div>';
	}
	add_action( 'admin_notices', 'bbcs_minimum_wp_version_notice' );
	return;
}

/**
 * Constants for the BotBlocker plugin.
 * These constants define various settings and values used throughout the plugin.
 */
if ( ! defined( 'BOTBLOCKER' ) ) {
	define( 'BOTBLOCKER', true );
}
define( 'BOTBLOCKER_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOTBLOCKER_URL', plugin_dir_url( __FILE__ ) );
define( 'BOTBLOCKER_BASENAME', plugin_basename( __FILE__ ) );

// Include the helper functions file
require_once BOTBLOCKER_DIR . 'helpers.php';
// Include the core helpers file
require_once BOTBLOCKER_DIR . 'core-helpers.php';
if ( is_admin() || wp_doing_cron() ) {
	require_once BOTBLOCKER_DIR . 'helpers-admin.php';
}

// Include license functionality
bbcs_handleBotblockerPro();

// Marketing blocks (changelog parser, in-plugin update message, social proof, etc.)
add_action( 'in_plugin_update_message-' . BOTBLOCKER_BASENAME, 'bbcs_render_in_plugin_update_message', 10, 2 );
$bbcs_relative_basename = basename( __DIR__ ) . '/' . basename( __FILE__ );
if ( BOTBLOCKER_BASENAME !== $bbcs_relative_basename ) {
	add_action( 'in_plugin_update_message-' . $bbcs_relative_basename, 'bbcs_render_in_plugin_update_message', 10, 2 );
}

// Guard: fix nonce_user_logged_out uid at 0 for unauthenticated AJAX requests.
// Without this, a third-party plugin hooking the filter could return a different
// uid during nonce creation vs verification, causing nopriv nonce mismatch.
add_filter( 'nonce_user_logged_out', '__return_zero', 0 );

/**
 * Checks if the request is an AJAX request and performs logic.
 *
 * This function is responsible for checking if the current request is an AJAX request and performing the necessary bot blocking logic.
 * It prevents automated bots from accessing or submitting data through AJAX requests.
 *
 * @return void
 */
function bbcs_botblocker_ajax_check(): void {
	check_ajax_referer( 'botblocker_nonce', 'nonce' );

	// Rate-limit: max 100 check-requests per IP per hour.
	/*
	if ( ! BotBlockerIp::rateLimit( 'bbcs_botblocker_check', 100, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'botblocker-security' ) ), 429 );
	}
	*/

	/* Include the BotBlocker main class file */
	require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker.php';
	BotBlockerAddons::includePreRunAddons();
	$botBlocker = new BotBlocker();
	$botBlocker->init_visitor_pages();
	$botBlocker->initialize();
	wp_die();
}
add_action( 'wp_ajax_bbcs_botblocker_check', 'bbcs_botblocker_ajax_check' );
add_action( 'wp_ajax_nopriv_bbcs_botblocker_check', 'bbcs_botblocker_ajax_check' );

/**
 * Activates the BotBlocker plugin.
 *
 * This function is called when the plugin is activated.
 * It includes the necessary files, performs database operations, and creates rule files.
 *
 * @return void
 */
register_activation_hook( __FILE__, array( 'Botblocker_Activator', 'activate' ) );

/**
 * Deactivates the BotBlocker plugin.
 *
 * This function is called when the plugin is deactivated.
 * It includes the necessary files and performs cleanup operations.
 *
 * @return void
 */
register_deactivation_hook( __FILE__, array( 'Botblocker_Deactivator', 'deactivate' ) );

/**
 * Runs the BotBlocker plugin.
 *
 * This function initializes the plugin and its admin functionality.
 *
 * @return void
 */
function bbcs_run_botblocker_shield(): void {
	/* Check installation and create tables if necessary (for corrupted installations) */
	BotBlockerInstall::checkInstall();

	/* Include the BotBlocker main interface class file */
	$plugin = new Cyber_Secure_Botblocker();
	BotBlockerAddons::includePreRunAddons();
	$plugin->run();
	/* Include active addons after database is ready */
	BotBlockerAddons::includeAll();

	if ( is_admin() ) {
		// Admin UI, menu registration, plugin action links
		$bbcs_admin = Botblocker_Admin::getInstance();
		add_action( 'admin_menu', array( $bbcs_admin, 'add_admin_menu' ) );
		$bbcs_admin->run();

		// Notification system
		require_once BOTBLOCKER_DIR . 'includes/class-botblocker-toastify.php';
		BBCS_Toastify::init();

		// Setup Wizard
		require_once BOTBLOCKER_DIR . 'admin/class-botblocker-setup-wizard.php';
		$bbcs_wizard = new BotBlocker_SetupWizard();
		$bbcs_wizard->hooks();
	}
}

add_action( 'plugins_loaded', 'bbcs_run_botblocker_shield', -9998 );

//BBCS-MULTISITE
function bbcs_on_wp_initialize_site( $new_site ): void {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! is_plugin_active_for_network( BOTBLOCKER_BASENAME ) ) {
		return;
	}
	switch_to_blog( (int) $new_site->blog_id );
	try {
		require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
		Botblocker_Activator::activate();
	} finally {
		restore_current_blog();
	}
	require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
}
add_action( 'wp_initialize_site', 'bbcs_on_wp_initialize_site', 200 );

//BBCS-MULTISITE
function bbcs_on_wp_uninitialize_site( $old_site ): void {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! is_plugin_active_for_network( BOTBLOCKER_BASENAME ) ) {
		return;
	}

	switch_to_blog( (int) $old_site->blog_id );
	try {
		require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
		if ( class_exists( 'BotBlockerAddons' ) ) {
			BotBlockerAddons::deactivateAll();
		}
		BotBlockerCron::removeTasks();
	} finally {
		restore_current_blog();
	}
	require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';

	if ( defined( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS' ) && BOTBLOCKER_INTEGRATE_MU_PLUGINS && method_exists( 'BotBlockerInstall', 'uninstallMuPlugin' ) ) {
		BotBlockerInstall::uninstallMuPlugin();
	}

	update_site_option( 'bbcs_sites_map_dirty', 1 );
}
add_action( 'wp_uninitialize_site', 'bbcs_on_wp_uninitialize_site', 1 );

// Multisite: flag sites map for regeneration when site URLs change.
if ( is_multisite() ) {
	add_action(
		'update_option_siteurl',
		function () {
			update_site_option( 'bbcs_sites_map_dirty', 1 );
		}
	);

	add_action(
		'wp_update_site',
		function ( $new_site, $old_site ) {
			if ( $new_site->domain !== $old_site->domain || $new_site->path !== $old_site->path ) {
				update_site_option( 'bbcs_sites_map_dirty', 1 );
			}
		},
		10,
		2
	);
}

/* Add preconnect for Google Fonts and Google Charts*/
function bbcs_add_google_fonts_preconnect(): void {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}
add_action( 'wp_head', 'bbcs_add_google_fonts_preconnect' );

/**
 * Add custom links to plugin meta row
 */
function bbcs_plugin_row_meta( array $links, string $file ): array {
	if ( BOTBLOCKER_BASENAME === $file ) {
		$row_meta = array(
			'docs'  => '<a href="https://botblocker.top/docs/" target="_blank">' . esc_html__( 'Docs and FAQs', 'botblocker-security' ) . '</a>',
			'video' => '<a href="https://globus.studio/contact-us-to-develop-agency-solutions/" target="_blank">' . esc_html__( 'Hire Developers', 'botblocker-security' ) . '</a>',
		);
		return array_merge( $links, $row_meta );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'bbcs_plugin_row_meta', 10, 2 );

/* End of file botblocker-security.php */