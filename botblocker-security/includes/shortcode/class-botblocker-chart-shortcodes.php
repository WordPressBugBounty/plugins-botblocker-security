<?php
declare(strict_types=1);

use BotBlocker\Component\DailyHitsChart;
use BotBlocker\Component\DonutChart;
use BotBlocker\Component\HistoryChart;
use BotBlocker\Component\VisitorsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerChartShortcodes {

	public static function register(): void {
		add_shortcode( 'bbcs_statistics_chart', array( self::class, 'displayStatisticsChart' ) );
		add_shortcode( 'bbcs_visitors_jsvectormap', array( self::class, 'displayVisitorsJsvectormap' ) );
		add_shortcode( 'bbcs_hits_and_uniques_chart', array( self::class, 'displayHitsAndUniquesChart' ) );
		add_shortcode( 'bbcs_daily_hits_chart', array( self::class, 'displayDailyHitsChart' ) );
	}

	public static function displayStatisticsChart( $atts ): string {
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

	public static function displayVisitorsJsvectormap( $atts ): string {
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

	public static function displayHitsAndUniquesChart( $atts ): string {
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

	public static function displayDailyHitsChart( $atts ): string {
		$data = BotBlockerStats::getDailyHitsChartData();

		return DailyHitsChart::make()
			->withLabels( $data['labels'] )
			->withValues( $data['values'] )
			->render( true );
	}
}