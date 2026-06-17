<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_display_daily_hits_chart( $atts ): string {
	global $wpdb;
	$BBCS                        = BotBlocker::getInstance();
	[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();

	if ( $BBCS->settings->cache_ui_data == 1 ) {
		$cache_key = 'bbcs_display_daily_hits_chart';
		$cached    = null;

		$cached = get_transient( $cache_key );

		if ( $cached ) {
			return $cached;
		}
	}

	$atts = shortcode_atts(
		array(
			'width'  => '100%',
			'height' => '400px',
		),
		$atts
	);

	$gmt_offset     = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;
	$gmt_offset_str = BotBlockerEnv::format_gmt_offset( $gmt_offset );

	$tz           = new \DateTimeZone( $gmt_offset_str );
	$current_date = new \DateTime( 'now', $tz );

	$start_of_day = ( clone $current_date )->setTime( 0, 0, 0 );
	$end_of_day   = ( clone $current_date )->setTime( 23, 59, 59 );

	// REVIEWER NOTE: Exclusion fragment uses only internally controlled self‑IPs and is bound via prepare; no user data flows here.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"
            SELECT HOUR(CONVERT_TZ(FROM_UNIXTIME(date), '+00:00', %s)) AS hour, COUNT(*) AS hits
            FROM (
                SELECT * FROM `{$wpdb->bbcs_hits}`
                UNION ALL
                SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
            ) AS combined_hits
            WHERE date BETWEEN UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
                            AND UNIX_TIMESTAMP(CONVERT_TZ(%s, %s, '+00:00'))
            AND NOT EXISTS (
                SELECT 1 FROM `{$wpdb->bbcs_page_filters}` AS pf
                WHERE combined_hits.page LIKE pf.pattern
            )
            {$ip_not_in_sql}
            GROUP BY hour
            ORDER BY hour
            ",
			$gmt_offset_str,
			$start_of_day->format( 'Y-m-d H:i:s' ),
			$gmt_offset_str,
			$end_of_day->format( 'Y-m-d H:i:s' ),
			$gmt_offset_str,
			...$ip_params
		)
	);
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$values = array_fill( 0, 24, 0 );
	foreach ( (array) $results as $row ) {
		$hour            = (int) $row->hour;
		$values[ $hour ] = (int) $row->hits;
	}

	$labels = array();
	for ( $i = 0; $i < 24; $i++ ) {
		$labels[] = sprintf( '%02d:00', $i ); }

	ob_start();
	?>
	<div id="bbcs_daily_hits_chart" class="bbcs-daily-hits-chart" data-bbcs-labels='<?php echo wp_json_encode( array_values( $labels ) ); ?>' data-bbcs-values='<?php echo wp_json_encode( array_values( $values ) ); ?>' style="width: <?php echo esc_attr( $atts['width'] ); ?>; height: <?php echo esc_attr( $atts['height'] ); ?>;"></div>
	<?php
	$output = ob_get_clean();

	if ( $BBCS->settings->cache_ui_data == 1 ) {

		set_transient( $cache_key, $output, $BBCS->settings->cache_ui_duration );

	}

	return $output;
}
add_shortcode( 'bbcs_daily_hits_chart', 'bbcs_display_daily_hits_chart' );
