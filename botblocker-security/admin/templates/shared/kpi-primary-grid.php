<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( object $data, bool $swap_enabled = false ): void {
	?>
<div class="bbcs-grid bbcs-grid--4 bbcs-mb-5h">
	<?php if ( $swap_enabled ) : ?>
	<div class="bbcs-card bbcs-kpi bbcs-kpi-swap" data-bbcs-kpi-swap>
		<div class="bbcs-kpi-swap__stage">
			<div class="bbcs-kpi-swap__layer bbcs-kpi-swap__layer--today">
				<div class="bbcs-kpi-label"><?php esc_html_e( 'Allowed today', 'botblocker-security' ); ?></div>
				<div class="bbcs-stat bbcs-kpi-n"><?php echo esc_html( $data->kpi_requests_today ); ?></div>
				<div class="bbcs-kpi-sub"><?php echo esc_html( $data->kpi_allowed_percent ); ?>% <?php esc_html_e( 'of traffic', 'botblocker-security' ); ?></div>
			</div>
			<div class="bbcs-kpi-swap__layer bbcs-kpi-swap__layer--total">
				<div class="bbcs-kpi-label"><?php esc_html_e( 'Total allowed', 'botblocker-security' ); ?></div>
				<div class="bbcs-stat bbcs-kpi-n"><?php echo esc_html( $data->kpi_requests_total ); ?></div>
				<div class="bbcs-kpi-sub"><?php echo esc_html( $data->kpi_allowed_percent_total ); ?>% <?php esc_html_e( 'of traffic', 'botblocker-security' ); ?></div>
			</div>
		</div>
	</div>
	<div class="bbcs-card bbcs-kpi bbcs-kpi-swap" data-bbcs-kpi-swap>
		<div class="bbcs-kpi-swap__stage">
			<div class="bbcs-kpi-swap__layer bbcs-kpi-swap__layer--today">
				<div class="bbcs-kpi-label"><?php esc_html_e( 'Blocked today', 'botblocker-security' ); ?></div>
				<div class="bbcs-stat bbcs-kpi-n bbcs-tx-red"><?php echo esc_html( $data->kpi_blocked_today ); ?></div>
				<div class="bbcs-kpi-sub"><?php echo esc_html( $data->kpi_blocked_percent ); ?>% <?php esc_html_e( 'of traffic', 'botblocker-security' ); ?></div>
			</div>
			<div class="bbcs-kpi-swap__layer bbcs-kpi-swap__layer--total">
				<div class="bbcs-kpi-label"><?php esc_html_e( 'Total blocked', 'botblocker-security' ); ?></div>
				<div class="bbcs-stat bbcs-kpi-n bbcs-tx-red"><?php echo esc_html( $data->kpi_blocked_total ); ?></div>
				<div class="bbcs-kpi-sub"><?php echo esc_html( $data->kpi_blocked_percent_total ); ?>% <?php esc_html_e( 'of traffic', 'botblocker-security' ); ?></div>
			</div>
		</div>
	</div>
	<?php else : ?>
	<div class="bbcs-card bbcs-kpi">
		<div class="bbcs-kpi-label"><?php esc_html_e( 'Allowed today', 'botblocker-security' ); ?></div>
		<div class="bbcs-stat bbcs-kpi-n" data-kpi="requests-today"><?php echo esc_html( $data->kpi_requests_today ); ?></div>
		<div class="bbcs-kpi-sub"><span data-kpi="allowed-percent"><?php echo esc_html( $data->kpi_allowed_percent ); ?></span>% <?php esc_html_e( 'of traffic', 'botblocker-security' ); ?></div>
	</div>
	<div class="bbcs-card bbcs-kpi">
		<div class="bbcs-kpi-label"><?php esc_html_e( 'Blocked today', 'botblocker-security' ); ?></div>
		<div class="bbcs-stat bbcs-kpi-n bbcs-tx-red" data-kpi="blocked-today"><?php echo esc_html( $data->kpi_blocked_today ); ?></div>
		<div class="bbcs-kpi-sub"><span data-kpi="blocked-percent"><?php echo esc_html( $data->kpi_blocked_percent ); ?></span>% <?php esc_html_e( 'of traffic', 'botblocker-security' ); ?></div>
	</div>
	<?php endif; ?>
	<div class="bbcs-card bbcs-kpi">
		<div class="bbcs-kpi-label"><?php esc_html_e( 'Search Engines', 'botblocker-security' ); ?></div>
		<div class="bbcs-stat bbcs-kpi-n bbcs-tx-green"><?php echo esc_html( $data->kpi_search_engines ); ?></div>
		<div class="bbcs-kpi-sub"><?php esc_html_e( 'verified and passed', 'botblocker-security' ); ?></div>
	</div>
	<div class="bbcs-card bbcs-kpi">
		<div class="bbcs-kpi-label"><?php esc_html_e( 'Protection Score', 'botblocker-security' ); ?></div>
		<div class="bbcs-stat bbcs-kpi-n bbcs-tx-violet"><?php echo esc_html( $data->kpi_health_score ); ?>%</div>
		<div class="bbcs-kpi-sub"><?php echo esc_html( $data->health_label ); ?></div>
	</div>
</div>
	<?php
};
