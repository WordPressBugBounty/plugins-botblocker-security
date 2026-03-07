<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
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
 * @version           1.6.13
 *
 * @wordpress-plugin
 * Plugin Name:       BotBlocker Security - Firewall & Bot Protection
 * Plugin URI:        https://botblocker.top/
 * Description:       BotBlocker Security is a powerful WordPress plugin designed to safeguard your website from unwanted bots and malicious activities. With advanced detection algorithms, BotBlocker identifies and blocks harmful bots, reducing spam and protecting your site's resources. The plugin provides real-time monitoring and customizable rules, allowing you to control access and enhance site security effortlessly. Easy to install and configure, BotBlocker ensures a smooth user experience while keeping your site safe from automated threats. Keep your WordPress site secure and running efficiently with BotBlocker.
 * Version:           1.6.13
 * Author:            Yevhen Leonidov
 * Author URI:        https://leonidov.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.0
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Text Domain:       botblocker-security
 * Domain Path:       /languages
 */


// Check minimum requirements (PHP)
if ( version_compare( phpversion(), '7.4.0', '<' ) ) {
	function bbcs_minimum_php_version_notice() {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'BotBlocker requires PHP 7.4 or higher.', 'botblocker-security') . '</p></div>';
	}
	add_action( 'admin_notices', 'bbcs_minimum_php_version_notice' );
	return;
}

// Check minimum requirements (WordPress)
if ( version_compare( $GLOBALS['wp_version'], '5.0', '<' ) ) {
	function bbcs_minimum_wp_version_notice() {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'BotBlocker requires WordPress 5.0 or later.', 'botblocker-security') . '</p></div>';
	}
	add_action( 'admin_notices', 'bbcs_minimum_wp_version_notice' );
	return;
}

/**
 * Constants for the BotBlocker plugin.
 * These constants define various settings and values used throughout the plugin.
 */
if(!defined('BOTBLOCKER')) {
	define('BOTBLOCKER', true); 
}
define('BOTBLOCKER_DIR',        plugin_dir_path(__FILE__)); 
define('BOTBLOCKER_URL',        plugin_dir_url(__FILE__)); 
define('BOTBLOCKER_BASENAME',   plugin_basename(__FILE__));

// Include the defines file
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-define.php';

// Include the helper functions file
require_once BOTBLOCKER_DIR . 'helpers.php';
// Include the core helpers file
require_once BOTBLOCKER_DIR . 'core-helpers.php';

// Error handler for critical errors
if (BBCS_DEBUG == true) bbcs_errorHandlerSet();

// Include the installation file
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-install.php';

// Include license functionality
bbcs_handleBotblockerCloudAPI();

/**
 * Checks if the request is an AJAX request and performs logic.
 *
 * This function is responsible for checking if the current request is an AJAX request and performing the necessary bot blocking logic. 
 * It prevents automated bots from accessing or submitting data through AJAX requests.
 *
 * @return void
 */
function bbcs_botblocker_ajax_check()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    /* Include the BotBlocker main class file */
    require_once(BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker.php');
    $botBlocker = new BotBlocker();
    $botBlocker->init_visitor_pages();
    $botBlocker->initialize();

    wp_die();
}
add_action('wp_ajax_bbcs_botblocker_check', 'bbcs_botblocker_ajax_check');
add_action('wp_ajax_nopriv_bbcs_botblocker_check', 'bbcs_botblocker_ajax_check');

/**
 * Activates the BotBlocker plugin.
 *
 * This function is called when the plugin is activated.
 * It includes the necessary files, performs database operations, and creates rule files.
 *
 * @return void
 */
function bbcs_activate_botblocker()
{
    // Open output buffering to prevent premature output
    ob_start();
    
    /* Check installation and create tables if necessary */
    bbcs_check_install();

    require_once BOTBLOCKER_DIR . 'includes/class-botblocker-activator.php';
    Botblocker_Activator::activate();

    // Install mu-plugin
    if (defined('BOTBLOCKER_INTEGRATE_MU_PLUGINS') && BOTBLOCKER_INTEGRATE_MU_PLUGINS) {
        bbcs_installMuPlugin();
    }
 
    bbcs_register_cron_tasks();

    // License URL
    bbcs_rewrite_rules();

    // Ensure plugin-specific rewrite rules are registered before flushing.
    if (function_exists('bbcs_register_2fa_rewrite_rules')) {
        bbcs_register_2fa_rewrite_rules();
    }
    
    flush_rewrite_rules(true);

	set_transient('bbcs_just_activated', true, 60 );
	// Add transient to trigger redirect to the Setup Wizard.
	set_transient('bbcs_activation_redirect', true, 30 );

    // Clean up output buffering
    ob_end_clean();
}
register_activation_hook(__FILE__, 'bbcs_activate_botblocker');

/**
 * Deactivates the BotBlocker plugin.
 *
 * This function is called when the plugin is deactivated.
 * It includes the necessary files and performs cleanup operations.
 *
 * @return void
 */
function bbcs_deactivate_botblocker()
{
    require_once BOTBLOCKER_DIR . 'includes/class-botblocker-deactivator.php';
    Botblocker_Deactivator::deactivate();

    // Uninstall mu-plugin
    if (defined('BOTBLOCKER_INTEGRATE_MU_PLUGINS') && BOTBLOCKER_INTEGRATE_MU_PLUGINS) {
        bbcs_uninstallMuPlugin();
    }

    bbcs_remove_cron_tasks();

    flush_rewrite_rules(true);
}
register_deactivation_hook(__FILE__, 'bbcs_deactivate_botblocker');

// Include the main class file
if (! class_exists('Cyber_Secure_Botblocker')) {
    require_once BOTBLOCKER_DIR . 'includes/class-cyber-secure-botblocker.php';
}

/**
 * Runs the BotBlocker plugin.
 *
 * This function initializes the plugin and its admin functionality.
 *
 * @return void
 */
function bbcs_run_botblocker_shield()
{
    /* Check installation and create tables if necessary (for corrupted installations) */
    bbcs_check_install();

    /* Include the BotBlocker main interface class file */
    $plugin = new Cyber_Secure_Botblocker();
    $plugin->run();

    /* Include active addons after database is ready */
    bbcs_include_active_addons();

    // Initialize the admin functionality
    $bbcs_admin = Botblocker_Admin::getInstance();
    add_action('admin_menu', array($bbcs_admin, 'add_admin_menu'));
    $bbcs_admin->run();
    
    // Initialize Setup Wizard (only in admin)
    if (is_admin()) {
        require_once BOTBLOCKER_DIR . 'admin/class-botblocker-setup-wizard.php';
        $bbcs_wizard = new BotBlocker_SetupWizard();
        $bbcs_wizard->hooks();
    }
}

add_action('plugins_loaded', 'bbcs_run_botblocker_shield', -9998);

/* Add preconnect for Google Fonts and Google Charts*/
function bbcs_add_google_fonts_preconnect()
{
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}
add_action('wp_head', 'bbcs_add_google_fonts_preconnect');

/**
 * Add custom links to plugin meta row
 */
function bbcs_plugin_row_meta($links, $file) {
    if (BOTBLOCKER_BASENAME === $file) {
        $row_meta = array(
            'docs'    => '<a href="https://botblocker.top/docs/" target="_blank">' . esc_html__('Docs & FAQs', 'botblocker-security') . '</a>',
            'video'   => '<a href="https://globus.studio/contacts/" target="_blank">' . esc_html__('Hire Developers', 'botblocker-security') . '</a>',
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'bbcs_plugin_row_meta', 10, 2);

/* End of file botblocker-security.php */
