<?php
declare(strict_types=1);

use BotBlocker\Component\DailyHitsChart;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_display_daily_hits_chart( $atts ): string {
	$data = BotBlockerStats::getDailyHitsChartData();

	return DailyHitsChart::make()
		->withLabels( $data['labels'] )
		->withValues( $data['values'] )
		->render( true );
}
add_shortcode( 'bbcs_daily_hits_chart', 'bbcs_display_daily_hits_chart' );
