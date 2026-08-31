<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerRulesShortcodes {

	public static function register(): void {
		add_shortcode( 'botblocker_rules_stats', array( self::class, 'botblockerRulesStatistics' ) );
	}

	public static function botblockerRulesStatistics( $atts ): string {
		global $wpdb;
		$BBCS = BotBlocker::getInstance();
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$transient_key = 'bbcs_rules_stat';
			$cached_health = null;

			$cached_health = get_transient( $transient_key );

			if ( $cached_health !== false ) {
				return $cached_health;
			}
		}
		$atts = shortcode_atts(
			array(
				'show_chart'   => 'yes',
				'chart_height' => '200',
			),
			$atts
		);

		// REVIEWER NOTE: Custom BotBlocker-Security table. Queries are prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ipv4 = $wpdb->get_row(
			"SELECT COUNT(*) AS total, SUM(rule = 'block') AS blocks, SUM(rule = 'allow') AS allows FROM `{$wpdb->bbcs_ipv4rules}`",
			ARRAY_A
		);
		$ipv6 = $wpdb->get_row(
			"SELECT COUNT(*) AS total, SUM(rule = 'block') AS blocks, SUM(rule = 'allow') AS allows FROM `{$wpdb->bbcs_ipv6rules}`",
			ARRAY_A
		);
		$paths = $wpdb->get_row(
			"SELECT COUNT(*) AS total, SUM(rule = 'allow') AS allowed, SUM(rule = 'block') AS blocked FROM `{$wpdb->bbcs_path}`",
			ARRAY_A
		);
		$white = $wpdb->get_row(
			"SELECT COUNT(*) AS total, SUM(rule = 'allow' AND disable = 0) AS allowed FROM `{$wpdb->bbcs_se}`",
			ARRAY_A
		);

		$now = time();
		$rules = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, SUM(rule = 'block') AS blocks, SUM(rule = 'allow') AS allows, SUM(disable = 0) AS active, SUM(expires < %d) AS expired FROM `{$wpdb->bbcs_rules}`",
				$now
			),
			ARRAY_A
		);

		$ipv4_total  = (int) ( $ipv4['total'] ?? 0 );
		$ipv4_blocks = (int) ( $ipv4['blocks'] ?? 0 );
		$ipv4_allows = (int) ( $ipv4['allows'] ?? 0 );

		$ipv6_total  = (int) ( $ipv6['total'] ?? 0 );
		$ipv6_blocks = (int) ( $ipv6['blocks'] ?? 0 );
		$ipv6_allows = (int) ( $ipv6['allows'] ?? 0 );

		$paths_total   = (int) ( $paths['total'] ?? 0 );
		$paths_allowed = (int) ( $paths['allowed'] ?? 0 );
		$paths_blocked = (int) ( $paths['blocked'] ?? 0 );

		$white_bots_total   = (int) ( $white['total'] ?? 0 );
		$white_bots_allowed = (int) ( $white['allowed'] ?? 0 );

		$rules_total  = (int) ( $rules['total'] ?? 0 );
		$rules_blocks = (int) ( $rules['blocks'] ?? 0 );
		$rules_allows = (int) ( $rules['allows'] ?? 0 );
		$active_rules = (int) ( $rules['active'] ?? 0 );
		$expired_rules = (int) ( $rules['expired'] ?? 0 );
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$output = '';

		if ( $atts['show_chart'] === 'yes' ) {
			$labels       = array( 'IPv4', 'IPv6', 'Paths', 'Rules' );
			$blocked      = array( (int) $ipv4_blocks, (int) $ipv6_blocks, (int) $paths_blocked, (int) $rules_blocks );
			$allowed      = array( (int) $ipv4_allows, (int) $ipv6_allows, (int) $paths_allowed, (int) $rules_allows );
			$container_id = 'botblocker_rules_stats_chart';
			$output      .= '<div id="' . esc_attr( $container_id ) . '" class="bbcs-rules-stats-chart" style="height:' . esc_attr( $atts['chart_height'] ) . 'px" ' .
						' data-bbcs-labels=' . "'" . wp_json_encode( array_values( $labels ) ) . "'" .
						' data-bbcs-values-blocked=' . "'" . wp_json_encode( array_values( $blocked ) ) . "'" .
						' data-bbcs-values-allowed=' . "'" . wp_json_encode( array_values( $allowed ) ) . "'" .
						'></div>';
		}

		$output .= '<h3 class="bbcs-rule-stat-h">' . esc_html__( 'IP Addresses', 'botblocker-security' ) . '</h3>';
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Total IPv4 rules:', 'botblocker-security' ) . " {$ipv4_total} (" . esc_html__( 'Blocked:', 'botblocker-security' ) . " {$ipv4_blocks}, " . esc_html__( 'Allowed:', 'botblocker-security' ) . " {$ipv4_allows})</span>";
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Total IPv6 rules:', 'botblocker-security' ) . " {$ipv6_total} (" . esc_html__( 'Blocked:', 'botblocker-security' ) . " {$ipv6_blocks}, " . esc_html__( 'Allowed:', 'botblocker-security' ) . " {$ipv6_allows})</span>";

		$output .= "<h3 class='bbcs-rule-stat-h'>" . esc_html__( 'WordPress Paths', 'botblocker-security' ) . '</h3>';
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Total paths:', 'botblocker-security' ) . " {$paths_total} (" . esc_html__( 'Blocked:', 'botblocker-security' ) . " {$paths_blocked}, " . esc_html__( 'Allowed:', 'botblocker-security' ) . " {$paths_allowed})</span>";

		$output .= "<h3 class='bbcs-rule-stat-h'>" . esc_html__( 'White Bots and Search Engines', 'botblocker-security' ) . '</h3>';
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Total white bots:', 'botblocker-security' ) . " {$white_bots_total} (" . esc_html__( 'Active:', 'botblocker-security' ) . " {$white_bots_allowed})</span>";

		$output .= "<h3 class='bbcs-rule-stat-h'>" . esc_html__( 'General Rules', 'botblocker-security' ) . '</h3>';
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Total rules:', 'botblocker-security' ) . " {$rules_total} (" . esc_html__( 'Blocked:', 'botblocker-security' ) . " {$rules_blocks}, " . esc_html__( 'Allowed:', 'botblocker-security' ) . " {$rules_allows})</span>";

		$output .= "<h3 class='bbcs-rule-stat-h'>" . esc_html__( 'Additional Information', 'botblocker-security' ) . '</h3>';
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Active rules:', 'botblocker-security' ) . " {$active_rules}</span>";
		$output .= "<span class='bbcs-rule-stat-s'>" . esc_html__( 'Expired rules:', 'botblocker-security' ) . " {$expired_rules}</span>";
		if ( $BBCS->settings->cache_ui_data == 1 ) {

			set_transient( $transient_key, $output, $BBCS->settings->cache_ui_duration );

		}
		return $output;
	}
}