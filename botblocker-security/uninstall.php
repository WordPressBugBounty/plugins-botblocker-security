<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

global $wpdb;

if (
	file_exists(plugin_dir_path(__FILE__) . 'includes/inc-botblocker-define.php')
	&& file_exists(plugin_dir_path(__FILE__) . 'includes/inc-botblocker-tables.php')
) {
	define('BOTBLOCKER', true);
    require_once plugin_dir_path(__FILE__) . 'includes/inc-botblocker-define.php';
	require_once plugin_dir_path(__FILE__) . 'includes/inc-botblocker-tables.php';
} else {
    // error_log('Uninstall.php: Define or tables file missing.');
    exit;
}

$bbcs_tables = [
	$wpdb->bbcs_hits,
	$wpdb->bbcs_hits_suspicious,
	$wpdb->bbcs_hits_cloud,
	$wpdb->bbcs_se,
    $wpdb->bbcs_proxy, 
    $wpdb->bbcs_path,
    $wpdb->bbcs_rules,
    $wpdb->bbcs_settings,
    $wpdb->bbcs_ptrcache,
	$wpdb->bbcs_ipv4rules,
	$wpdb->bbcs_ipv6rules,
	$wpdb->bbcs_counters,
	$wpdb->bbcs_page_filters,
	$wpdb->bbcs_daily_summary,
];

/** REVIEWER NOTE:
 * Direct DROP TABLE statements are necessary in uninstall — wpdb::prepare()
 * cannot bind identifiers. Suppressing Sniffs for schema-level queries.
 */
foreach ( $bbcs_tables as $bbcs_table ) {
	if ( empty( $bbcs_table ) ) {
		continue;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared 
	$wpdb->query( "DROP TABLE IF EXISTS `{$bbcs_table}`" );
}

// Clean up plugin options
delete_option('bbcs_db_version');
delete_option('bbcs_wizard_completed');
delete_option('bbcs_setup_wizard_completed');
delete_option('bbcs_setup_wizard_completed_at');

delete_option('bbcs_wizard_cache_type');
delete_option('bbcs_wizard_preset');
delete_option('bbcs_wizard_ux_mode');
delete_option('bbcs_wizard_captcha_mode');
delete_option('bbcs_wizard_init_mode');

delete_option('bbcs_contact_email_collected');
delete_option('bbcs_support_data');

delete_option('bbcs_2fa_rules_version');

delete_option('bbcs_activation_prevent_redirect');
delete_option('bbcs_initial_version');

delete_option('botblocker_active_addons');
delete_option('botblocker_tools_core_settings');
delete_option('botblocker_tools_login_settings');
