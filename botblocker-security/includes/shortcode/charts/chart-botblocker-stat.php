<?php
declare(strict_types=1);

use BotBlocker\Component\DonutChart;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_display_statistics_chart( $atts ): string {
	$BBCS = BotBlocker::getInstance();

	$defaults = array(
		'type'   => 'pie', // pie or donut
		'period' => 'today',
		'data'   => 'ip_hits_hosts',
		'width'  => 'auto',
		'height' => 'auto',
	);
	$atts     = shortcode_atts( $defaults, $atts, 'bbcs_statistics_chart' );

	if ( ! preg_match( '/^(ip_hits_hosts|cookie_hits_hosts|device_types|browsers|operating_systems)$/', $atts['data'] ) ) {
		return esc_html__( 'Unknown chart data type.', 'botblocker-security' );
	}

	if ( ! isset( $BBCS->counters[ $atts['period'] ] ) ) {
		return esc_html__( 'No data available for the specified period.', 'botblocker-security' );
	}

	$chart_data = BotBlockerStats::getDonutPieChartData( $atts['data'], $atts['period'] );

	return DonutChart::make()
		->withId( $chart_data['container_id'] )
		->withLabels( $chart_data['labels'] )
		->withValues( $chart_data['values'] )
		->withTitle( $chart_data['title'] )
		->withType( $atts['type'] )
		->withWidth( (string) $atts['width'] )
		->withHeight( (string) $atts['height'] )
		->render( true );
}
add_shortcode( 'bbcs_statistics_chart', 'bbcs_display_statistics_chart' );
