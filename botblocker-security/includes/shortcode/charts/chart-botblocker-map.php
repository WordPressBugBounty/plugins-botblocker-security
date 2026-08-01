<?php
declare(strict_types=1);

use BotBlocker\Component\VisitorsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_display_visitors_jsvectormap( $atts ): string {
	$BBCS = BotBlocker::getInstance();
	$cache_key = 'bbcs_display_visitors_jsvectormap';

	if ( $BBCS->settings->cache_ui_data == 1 ) {
		$cached = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}
	}

	$atts = shortcode_atts(
		array(
			'days' => 30,
		),
		$atts
	);

	$days   = min( max( (int) $atts['days'], 1 ), 365 );
	$data   = BotBlockerStats::getVisitorsMapData( $days );
	$output = VisitorsMap::make()
		->withData( $data )
		->render( true );

	if ( $BBCS->settings->cache_ui_data == 1 ) {
		set_transient( $cache_key, $output, (int) $BBCS->settings->cache_ui_duration );
	}

	return $output;
}
add_shortcode( 'bbcs_visitors_jsvectormap', 'bbcs_display_visitors_jsvectormap' );
