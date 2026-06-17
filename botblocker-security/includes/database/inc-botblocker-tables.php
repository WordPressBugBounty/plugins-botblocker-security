<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;

$bbcs_tables = array(
	'bbcs_hits'            => 'hits',
	'bbcs_hits_suspicious' => 'hits_suspicious',
	'bbcs_hits_cloud'      => 'hits_cloud',
	'bbcs_se'              => 'se',
	'bbcs_path'            => 'path',
	'bbcs_rules'           => 'rules',
	'bbcs_settings'        => 'settings',
	'bbcs_ptrcache'        => 'ptrcache',
	'bbcs_proxy'           => 'proxy',
	'bbcs_counters'        => 'counters',
	'bbcs_ipv4rules'       => 'ipv4rules',
	'bbcs_ipv6rules'       => 'ipv6rules',
	'bbcs_page_filters'    => 'page_filters',
	'bbcs_daily_summary'   => 'daily_summary',
	'bbcs_asn'             => 'asn',
	'bbcs_self_ips'        => 'self_ips', //! Deprecated in 2.2.0, only used to drop in migrations
	'bbcs_llm_trusted'     => 'llm_trusted',
);

foreach ( $bbcs_tables as $bbcs_prop => $bbcs_suffix ) {
	if ( empty( $wpdb->tables ) || ! in_array( $bbcs_prop, $wpdb->tables, true ) ) {
		$wpdb->tables[] = $bbcs_prop;
	}
	$wpdb->{$bbcs_prop} = $wpdb->prefix . BOTBLOCKER_TABLE_PREFIX . $bbcs_suffix;
}
