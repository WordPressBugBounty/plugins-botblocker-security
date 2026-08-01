<?php
declare(strict_types=1);

use BotBlocker\Component\HistoryChart;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_display_hits_and_uniques_chart( $atts ): string {
	$BBCS = BotBlocker::getInstance();
	$cache_key = 'bbcs_display_hits_and_uniques_chart';

	if ( $BBCS->settings->cache_ui_data == 1 ) {
		$cached = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}
	}

	$defaults = array(
		'days' => 7,
	);
	$atts     = shortcode_atts( $defaults, $atts, 'bbcs_hits_and_uniques_chart' );
	$days     = min( max( (int) $atts['days'], 1 ), 31 );

	$data = BotBlockerStats::getHitsAndUniquesChartData( $days );

	$output = HistoryChart::make()
		->withLabels( $data['labels'] )
		->withUniques( $data['uniques'] )
		->withHits( $data['hits'] )
		->render( true );

	if ( $BBCS->settings->cache_ui_data == 1 ) {
		set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
	}

	return $output;
}
add_shortcode( 'bbcs_hits_and_uniques_chart', 'bbcs_display_hits_and_uniques_chart' );
