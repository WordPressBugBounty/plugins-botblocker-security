<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_DashboardViewModel $data ): void {
	?>
<div class="bbcs-card bbcs-card-pad bbcs-mb-3h">
	<div class="bbcs-section-head">
		<div class="bbcs-section-title"><?php esc_html_e( "Today's activity", 'botblocker-security' ); ?></div>
		<a class="bbcs-link" href="<?php echo esc_url( $data->urls->reports ); ?>">
			<?php esc_html_e( 'Log', 'botblocker-security' ); ?>
			<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-arrowR"></use></svg>
		</a>
	</div>

	<div class="bbcs-statistics-chart-title-div"><span class="bbcs-statistics-chart-title"><?php esc_html_e( 'Hourly visitors chart', 'botblocker-security' ); ?></span></div>
	<?php $data->daily_hits_chart->render(); ?>

	<div class="bbcs-grid bbcs-grid--4 bbcs-mt-3h bbcs-ta-center">
		<div class="bbcs-ta-center"><?php $data->donut_hosts->render(); ?></div>
		<div class="bbcs-ta-center"><?php $data->donut_devices->render(); ?></div>
		<div class="bbcs-ta-center"><?php $data->donut_browsers->render(); ?></div>
		<div class="bbcs-ta-center"><?php $data->donut_os->render(); ?></div>
	</div>
</div>
	<?php
};
