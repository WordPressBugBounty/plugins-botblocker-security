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
 * The BotBlockerBootstrap::runShield() method initializes the plugin and its admin functionality.
 *
 * @link              https://globus.studio
 * @package           botblocker-security
 * @version           1.7.5
 *
 * @wordpress-plugin
 * Plugin Name:       BotBlocker Security - Complete security platform
 * Plugin URI:        https://botblocker.top/
 * Description:       Complete security platform for everyday: block bots, brute-force, viruses, spam, fake crawlers. WAF, 2FA, no-malware, proactive defense, 200+ tools
 * Version:           1.7.5
 * Author:            Yevhen Leonidov
 * Author URI:        https://leonidov.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.1
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Text Domain:       botblocker-security
 * Domain Path:       /languages
 */

require_once __DIR__ . '/includes/class-botblocker-bootstrap.php';
if ( ! BotBlockerBootstrap::register() ) {
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
require_once BOTBLOCKER_DIR . 'helpers-shield.php';
// Include the core helpers file
require_once BOTBLOCKER_DIR . 'core-helpers-shield.php';

if ( is_admin() || wp_doing_cron() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once BOTBLOCKER_DIR . 'helpers-admin-deferred.php';
	require_once BOTBLOCKER_DIR . 'core-helpers-admin.php';
	require_once BOTBLOCKER_DIR . 'helpers-admin.php';
}

// Include license functionality
if ( class_exists( 'BotBlockerCloudBb' ) ) {
	BotBlockerCloudBb::register();
}

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

/* End of file botblocker-security.php */
