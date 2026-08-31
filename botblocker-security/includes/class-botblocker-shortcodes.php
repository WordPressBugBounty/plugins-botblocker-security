<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use BotBlocker\Component\TopList;
use \BotBlocker\Component\LatestHitsTable;

class BotBlockerShortcodes {

	public static function register(): void {
		require_once BOTBLOCKER_DIR . 'includes/shortcode/class-botblocker-tasks-shortcodes.php';
		BotBlockerTasksShortcodes::register();
		require_once BOTBLOCKER_DIR . 'includes/shortcode/class-botblocker-health-shortcodes.php';
		BotBlockerHealthShortcodes::register();
		require_once BOTBLOCKER_DIR . 'includes/shortcode/class-botblocker-recommendations-shortcodes.php';
		BotBlockerRecommendationsShortcodes::register();
		require_once BOTBLOCKER_DIR . 'includes/shortcode/class-botblocker-sidebar-shortcodes.php';
		BotBlockerSidebarShortcodes::register();
		require_once BOTBLOCKER_DIR . 'includes/shortcode/class-botblocker-rules-shortcodes.php';
		BotBlockerRulesShortcodes::register();

		require_once BOTBLOCKER_DIR . 'includes/shortcode/class-botblocker-chart-shortcodes.php';
		BotBlockerChartShortcodes::register();

		add_shortcode( 'bbcs_lang_options', array( 'BotBlockerLangOptions', 'getOptionsHtml' ) );

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
				$output = self::renderTopList( 'ip', intval( $atts['limit'] ), intval( $atts['days'] ) );
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
				$output = self::renderTopList( 'country', intval( $atts['limit'] ), intval( $atts['days'] ) );
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
				$output = self::renderTopList( 'device', intval( $atts['limit'] ), intval( $atts['days'] ) );
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
				$output = self::renderTopList( 'browser', intval( $atts['limit'] ), intval( $atts['days'] ) );
				if ( $BBCS->settings->cache_ui_data == 1 ) {
					set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );
				}
				return $output;
			}
		);

		add_shortcode( 'bbcs_latest_hits', array( self::class, 'latestHitsShortcode' ) );
	}

	public static function renderTopList( string $type, int $limit, int $days ): string {
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

	public static function latestHitsShortcode(): string {
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
}
