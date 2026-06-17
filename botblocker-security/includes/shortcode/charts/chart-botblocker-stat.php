<?php
declare(strict_types=1);

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

	if ( ! isset( $BBCS->counters[ $atts['period'] ] ) ) {
		return esc_html__( 'No data available for the specified period.', 'botblocker-security' );
	}

	$data   = $BBCS->counters[ $atts['period'] ];
	$labels = array();
	$values = array();
	$title  = '';

	switch ( $atts['data'] ) {
		case 'ip_hits_hosts':
			$labels = array(
				_x( 'Hits', 'statistics label', 'botblocker-security' ),
				_x( 'Unique IPs', 'statistics label', 'botblocker-security' ),
			);
			$values = array( (int) ( isset( $data['hits'] ) ? $data['hits'] : 0 ), (int) ( isset( $data['uniques'] ) ? $data['uniques'] : 0 ) );
			$title  = __( 'IP Hits and Unique IPs', 'botblocker-security' );
			break;

		case 'cookie_hits_hosts':
			$labels = array(
				_x( 'Hits', 'statistics label', 'botblocker-security' ),
				_x( 'Unique Visitors', 'statistics label', 'botblocker-security' ),
			);
			$values = array( (int) ( isset( $data['hit_count'] ) ? $data['hit_count'] : 0 ), (int) ( isset( $data['hit_hosts'] ) ? $data['hit_hosts'] : 0 ) );
			$title  = __( 'Cookie Hits and Visitors', 'botblocker-security' );
			break;

		case 'device_types':
			$labels = array(
				_x( 'PC', 'device type label', 'botblocker-security' ),
				_x( 'Set-top Box', 'device type label', 'botblocker-security' ),
				_x( 'Phone', 'device type label', 'botblocker-security' ),
				_x( 'Tablet', 'device type label', 'botblocker-security' ),
				_x( 'TV', 'device type label', 'botblocker-security' ),
			);
			$values = array( (int) ( isset( $data['pc'] ) ? $data['pc'] : 0 ), (int) ( isset( $data['box'] ) ? $data['box'] : 0 ), (int) ( isset( $data['phone'] ) ? $data['phone'] : 0 ), (int) ( isset( $data['tablet'] ) ? $data['tablet'] : 0 ), (int) ( isset( $data['tv'] ) ? $data['tv'] : 0 ) );
			$title  = __( 'Device Types', 'botblocker-security' );
			break;

		case 'browsers':
			if ( ! empty( $data['browsers'] ) ) {
				foreach ( $data['browsers'] as $browser => $count ) {
					$labels[] = sanitize_text_field( (string) $browser );
					$values[] = (int) $count;
				}
			}
			$title = __( 'Browsers', 'botblocker-security' );
			break;

		case 'operating_systems':
			if ( ! empty( $data['operating_systems'] ) ) {
				foreach ( $data['operating_systems'] as $os => $count ) {
					$labels[] = sanitize_text_field( (string) $os );
					$values[] = (int) $count;
				}
			}
			$title = __( 'Operating Systems', 'botblocker-security' );
			break;

		default:
			return esc_html__( 'Unknown chart data type.', 'botblocker-security' );
	}

	$container_id = 'bbcs_statistics_chart_' . sanitize_key( $atts['data'] );

	ob_start();
	?>
	<div class="bbcs-statistics-chart-title-div">
		<span class="bbcs-statistics-chart-title">
			<?php echo esc_html( $title ); ?>
		</span>
	</div>
	<div id="<?php echo esc_attr( $container_id ); ?>"
		class="bbcs-statistics-chart"
		data-bbcs-type="<?php echo esc_attr( $atts['type'] ); ?>"
		data-bbcs-title="<?php echo esc_attr( $title ); ?>"
		data-bbcs-labels='<?php echo wp_json_encode( array_values( $labels ) ); ?>'
		data-bbcs-values='<?php echo wp_json_encode( array_values( $values ) ); ?>'
		style="width: <?php echo esc_attr( $atts['width'] ); ?>; height: <?php echo esc_attr( $atts['height'] ); ?>;"></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'bbcs_statistics_chart', 'bbcs_display_statistics_chart' );
