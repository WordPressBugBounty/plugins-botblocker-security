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

if (!empty($wpdb->bbcs_self_ips)) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query("DROP VIEW IF EXISTS `{$wpdb->bbcs_self_ips}`");
}