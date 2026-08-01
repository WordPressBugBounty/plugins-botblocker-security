<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use BotBlocker\Component\TopList;
use \BotBlocker\Component\LatestHitsTable;

require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-header.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-tasks.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-health.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-health-full.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-tooltips.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-sidebar.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-rules.php';

require_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-daily.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-hits.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-map.php';
require_once BOTBLOCKER_DIR . 'includes/shortcode/charts/chart-botblocker-stat.php';

function bbcs_render_top_list( string $type, int $limit, int $days ): string {
	$data = BotBlockerStats::getTopData( $type, $limit, $days );

	$title = '';
	switch ( $type ) {
		case 'ip':
			// translators: %d is the number of top IP addresses to display.
			$title = sprintf( __( 'Top-%d IPs', 'botblocker-security' ), $limit );
			break;
		case 'country':
			// translators: %d is the number of top countries to display.
			$title = sprintf( __( 'Top %d Countries', 'botblocker-security' ), $limit );
			break;
		case 'device':
			// translators: %d is the number of top devices to display.
			$title = sprintf( __( 'Top %d Devices', 'botblocker-security' ), $limit );
			break;
		case 'browser':
			// translators: %d is the number of top browsers to display.
			$title = sprintf( __( 'Top %d Browsers', 'botblocker-security' ), $limit );
			break;
	}

	return TopList::make()
		->withTitle( $title )
		->withItems( $data )
		->withType( $type )
		->render( true );
}

add_shortcode(
	'bbcs_top_ips',
	function ( $atts ) {
		$BBCS = BotBlocker::getInstance();
		$cache_key = 'bbcs_top_ips';
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );
			if ( $cached ) {
				return $cached;
			}
		}
		$atts   = shortcode_atts( array( 'limit' => 5, 'days' => 7 ), $atts, 'bbcs_top_ips' );
		$output = bbcs_render_top_list( 'ip', intval( $atts['limit'] ), intval( $atts['days'] ) );
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
		}
		return $output;
	}
);

add_shortcode(
	'bbcs_top_countries',
	function ( $atts ) {
		$BBCS = BotBlocker::getInstance();

		$cache_key          = 'bbcs_top_countries';

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );
			if ( $cached ) {
				return $cached;
			}
		}
		$atts   = shortcode_atts( array( 'limit' => 5, 'days' => 7 ), $atts, 'bbcs_top_countries' );
		$output = bbcs_render_top_list( 'country', intval( $atts['limit'] ), intval( $atts['days'] ) );
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
		}
		return $output;
	}
);

add_shortcode(
	'bbcs_top_devices',
	function ( $atts ) {
		$BBCS = BotBlocker::getInstance();
		$cache_key          = 'bbcs_top_devices';

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );
			if ( $cached ) {
				return $cached;
			}
		}
		$atts   = shortcode_atts( array( 'limit' => 5, 'days' => 7 ), $atts, 'bbcs_top_devices' );
		$output = bbcs_render_top_list( 'device', intval( $atts['limit'] ), intval( $atts['days'] ) );
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
		}
		return $output;
	}
);

add_shortcode(
	'bbcs_top_browsers',
	function ( $atts ) {
		$BBCS = BotBlocker::getInstance();
		$cache_key          = 'bbcs_top_browsers';

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$cached = get_transient( $cache_key );
			if ( $cached ) {
				return $cached;
			}
		}
		$atts   = shortcode_atts( array( 'limit' => 5, 'days' => 7 ), $atts, 'bbcs_top_browsers' );
		$output = bbcs_render_top_list( 'browser', intval( $atts['limit'] ), intval( $atts['days'] ) );
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
		}
		return $output;
	}
);

function bbcs_latest_hits_shortcode(): string {
	$BBCS = BotBlocker::getInstance();
	$cache_key		  = 'bbcs_latest_hits_shortcode';
	if ( $BBCS->settings->cache_ui_data == 1 ) {
		$cached = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}
	}

	$rows       = BotBlockerStats::getLatestHitsData();
	$gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? floatval( $BBCS->settings->admin_gmt_offset ) : 0;

	$output = LatestHitsTable::make()
		->withRows( $rows )
		->withGmtOffset( $gmt_offset )
		->render( true );

	if ( $BBCS->settings->cache_ui_data == 1 ) {
		set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
	}
	return $output;
}
add_shortcode( 'bbcs_latest_hits', 'bbcs_latest_hits_shortcode' );
