<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly
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
 * @version           1.6.16
 *
 * @wordpress-plugin
 * Plugin Name:       BotBlocker Security - Firewall & Bot Protection
 * Plugin URI:        https://botblocker.top/
 * Description:       Blocks bots, scrapers and automated threats in real time. CAPTCHA, IP rules, proxy detection, login protection, reCAPTCHA, cloud API and customizable security rules - all in one plugin.
 * Version:           1.6.16
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
if (version_compare(phpversion(), '7.4.0', '<')) {
    function bbcs_minimum_php_version_notice()
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('BotBlocker requires PHP 7.4 or higher.', 'botblocker-security') . '</p></div>';
    }
    add_action('admin_notices', 'bbcs_minimum_php_version_notice');
    return;
}

// Check minimum requirements (WordPress)
if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
    function bbcs_minimum_wp_version_notice()
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('BotBlocker requires WordPress 5.0 or later.', 'botblocker-security') . '</p></div>';
    }
    add_action('admin_notices', 'bbcs_minimum_wp_version_notice');
    return;
}

/**
 * Constants for the BotBlocker plugin.
 * These constants define various settings and values used throughout the plugin.
 */
if (!defined('BOTBLOCKER')) {
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
//BBCS-MULTISITE
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-multisite.php';

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
function bbcs_activate_botblocker($network_wide = false)
{
    //BBCS-MULTISITE
    if (is_multisite() && $network_wide) {
        bbcs_activate_network_wide();
        return;
    }

    // Open output buffering to prevent premature output
    ob_start();

    $is_fresh_install = !bbcs_tablesExist();

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

    if ($is_fresh_install) {
        set_transient('bbcs_just_activated', true, 60);
        bbcs_update_option('bbcs_activation_redirect', true);
    }

    // Clean up output buffering
    ob_end_clean();
}
register_activation_hook(__FILE__, 'bbcs_activate_botblocker');

//BBCS-MULTISITE
function bbcs_activate_network_wide()
{
    if (! is_multisite()) {
        return;
    }
    ob_start();
    $site_ids = get_sites(array('fields' => 'ids', 'number' => 0));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
        $is_fresh_install = !bbcs_tablesExist();
        bbcs_check_install();
        require_once BOTBLOCKER_DIR . 'includes/class-botblocker-activator.php';
        Botblocker_Activator::activate();
        bbcs_register_cron_tasks();
        bbcs_rewrite_rules();
        if (function_exists('bbcs_register_2fa_rewrite_rules')) {
            bbcs_register_2fa_rewrite_rules();
        }
        flush_rewrite_rules(true);
        if ($is_fresh_install) {
            set_transient('bbcs_just_activated', true, 60);
            bbcs_update_option('bbcs_activation_redirect', true);
        }
        restore_current_blog();
    }
    require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
    if (defined('BOTBLOCKER_INTEGRATE_MU_PLUGINS') && BOTBLOCKER_INTEGRATE_MU_PLUGINS) {
        bbcs_installMuPlugin();
    }
    ob_end_clean();
}

/**
 * Deactivates the BotBlocker plugin.
 *
 * This function is called when the plugin is deactivated.
 * It includes the necessary files and performs cleanup operations.
 *
 * @return void
 */
function bbcs_deactivate_botblocker($network_wide = false)
{
    //BBCS-MULTISITE
    if (is_multisite() && $network_wide) {
        bbcs_deactivate_network_wide();
        return;
    }

    require_once BOTBLOCKER_DIR . 'includes/class-botblocker-deactivator.php';
    Botblocker_Deactivator::deactivate();

    // Uninstall mu-plugin
    if (defined('BOTBLOCKER_INTEGRATE_MU_PLUGINS') && BOTBLOCKER_INTEGRATE_MU_PLUGINS) {
        bbcs_uninstallMuPlugin();
    }

    $sec_headers_mu = trailingslashit(WPMU_PLUGIN_DIR) . 'botblocker-security-headers.php';
    if (file_exists($sec_headers_mu)) {
        wp_delete_file($sec_headers_mu);
        clearstatcache(true);
    }

    if (! is_multisite() && function_exists('bbcs_removeWpConfigEarlyInitCode')) {
        bbcs_removeWpConfigEarlyInitCode();
    }

    bbcs_remove_cron_tasks();

    flush_rewrite_rules(true);
}
register_deactivation_hook(__FILE__, 'bbcs_deactivate_botblocker');

//BBCS-MULTISITE
function bbcs_deactivate_network_wide()
{
    if (! is_multisite()) {
        return;
    }
    $site_ids = get_sites(array('fields' => 'ids', 'number' => 0));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
        require_once BOTBLOCKER_DIR . 'includes/class-botblocker-deactivator.php';
        Botblocker_Deactivator::deactivate();
        bbcs_remove_cron_tasks();
        flush_rewrite_rules(true);
        restore_current_blog();
    }
    require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
    if (defined('BOTBLOCKER_INTEGRATE_MU_PLUGINS') && BOTBLOCKER_INTEGRATE_MU_PLUGINS) {
        bbcs_uninstallMuPlugin();
    }
    $sec_headers_mu = trailingslashit(WPMU_PLUGIN_DIR) . 'botblocker-security-headers.php';
    if (file_exists($sec_headers_mu)) {
        wp_delete_file($sec_headers_mu);
        clearstatcache(true);
    }
    if (function_exists('bbcs_removeWpConfigEarlyInitCode')) {
        bbcs_removeWpConfigEarlyInitCode();
    }
}

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

//BBCS-MULTISITE
function bbcs_on_wp_initialize_site($new_site)
{
    if (! function_exists('is_plugin_active_for_network')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (! is_plugin_active_for_network(BOTBLOCKER_BASENAME)) {
        return;
    }
    switch_to_blog((int) $new_site->blog_id);
    require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
    bbcs_check_install();
    require_once BOTBLOCKER_DIR . 'includes/class-botblocker-activator.php';
    Botblocker_Activator::activate();
    bbcs_register_cron_tasks();
    bbcs_rewrite_rules();
    if (function_exists('bbcs_register_2fa_rewrite_rules')) {
        bbcs_register_2fa_rewrite_rules();
    }
    flush_rewrite_rules(true);
    set_transient('bbcs_just_activated', true, 60);
    bbcs_update_option('bbcs_activation_redirect', true);
    restore_current_blog();
    require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
}
add_action('wp_initialize_site', 'bbcs_on_wp_initialize_site', 200);

//BBCS-MULTISITE
function bbcs_on_wp_uninitialize_site($old_site)
{
    if (! function_exists('is_plugin_active_for_network')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (! is_plugin_active_for_network(BOTBLOCKER_BASENAME)) {
        return;
    }

    switch_to_blog((int) $old_site->blog_id);
    require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';
    require_once BOTBLOCKER_DIR . 'includes/class-botblocker-deactivator.php';
    Botblocker_Deactivator::deactivate();
    bbcs_remove_cron_tasks();
    restore_current_blog();
    require BOTBLOCKER_DIR . 'includes/inc-botblocker-tables.php';

    update_site_option('bbcs_early_sites_map_dirty', 1);
}
add_action('wp_uninitialize_site', 'bbcs_on_wp_uninitialize_site', 1);

// Multisite: flag sites map for regeneration when site URLs change.
if (is_multisite()) {
    add_action('update_option_siteurl', function () {
        update_site_option('bbcs_early_sites_map_dirty', 1);
    });

    add_action('wp_update_site', function ($new_site, $old_site) {
        if ($new_site->domain !== $old_site->domain || $new_site->path !== $old_site->path) {
            update_site_option('bbcs_early_sites_map_dirty', 1);
        }
    }, 10, 2);
}

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
function bbcs_plugin_row_meta($links, $file)
{
    if (BOTBLOCKER_BASENAME === $file) {
        $row_meta = array(
            'docs'    => '<a href="https://botblocker.top/docs/" target="_blank">' . esc_html__('Docs and FAQs', 'botblocker-security') . '</a>',
            'video'   => '<a href="https://globus.studio/contact-us-to-develop-agency-solutions/" target="_blank">' . esc_html__('Hire Developers', 'botblocker-security') . '</a>',
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'bbcs_plugin_row_meta', 10, 2);

/* End of file botblocker-security.php */