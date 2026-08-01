<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_ReportsViewModel $data): void {
	( require BOTBLOCKER_DIR . 'admin/templates/shared/kpi-primary-grid.php' )( $data );
?>
	<div class="bbcs-grid bbcs-grid--4 bbcs-mb-5h">
		<div class="bbcs-card bbcs-kpi">
			<div class="bbcs-kpi-label"><?php esc_html_e( 'Total requests', 'botblocker-security' ); ?></div>
			<div class="bbcs-stat bbcs-kpi-n" data-kpi="all-requests-total"><?php echo esc_html( $data->kpi_all_requests_total ); ?></div>
			<div class="bbcs-kpi-sub"><?php esc_html_e( 'all time', 'botblocker-security' ); ?></div>
		</div>
		<div class="bbcs-card bbcs-kpi">
			<div class="bbcs-kpi-label"><?php esc_html_e( 'Total blocked', 'botblocker-security' ); ?></div>
			<div class="bbcs-stat bbcs-kpi-n bbcs-tx-red" data-kpi="blocked-total"><?php echo esc_html( $data->kpi_blocked_total ); ?></div>
			<div class="bbcs-kpi-sub"><?php esc_html_e( 'all time', 'botblocker-security' ); ?></div>
		</div>
		<div class="bbcs-card bbcs-kpi">
			<div class="bbcs-kpi-label"><?php esc_html_e( 'Cloud IPs', 'botblocker-security' ); ?></div>
			<div class="bbcs-stat bbcs-kpi-n bbcs-tx-amber"><?php echo esc_html( $data->kpi_cloud_ips ); ?></div>
			<div class="bbcs-kpi-sub"><?php esc_html_e( 'malicious IPs in database', 'botblocker-security' ); ?></div>
		</div>
		<div class="bbcs-card bbcs-kpi">
			<div class="bbcs-kpi-label"><?php esc_html_e( 'Verification Sources', 'botblocker-security' ); ?></div>
			<div class="bbcs-stat bbcs-kpi-n bbcs-tx-violet"><?php echo esc_html( $data->kpi_signatories_total ); ?></div>
			<div class="bbcs-kpi-sub"><?php esc_html_e( 'LLM', 'botblocker-security' ); ?>: <?php echo esc_html( $data->kpi_llm_providers ); ?> · <?php esc_html_e( 'RKN', 'botblocker-security' ); ?>: <?php echo esc_html( $data->kpi_rkn_ranges ); ?> · <?php esc_html_e( 'JA3/4', 'botblocker-security' ); ?>: <?php echo esc_html( $data->kpi_tls_fingerprints ); ?> · <?php esc_html_e( 'ASN', 'botblocker-security' ); ?>: <?php echo esc_html( $data->kpi_asn_signatures ); ?></div>
		</div>
	</div>
	<div class="bbcs-grid bbcs-grid--4 bbcs-mb-3h">
		<div class="bbcs-card bbcs-card-pad">
			<?php $data->donut_hosts->render(); ?>
		</div>
		<div class="bbcs-card bbcs-card-pad">
			<?php $data->donut_devices->render(); ?>
		</div>
		<div class="bbcs-card bbcs-card-pad">
			<?php $data->donut_browsers->render(); ?>
		</div>
		<div class="bbcs-card bbcs-card-pad">
			<?php $data->donut_os->render(); ?>
		</div>
	</div>
	<div class="bbcs-grid bbcs-grid--report bbcs-mb-3h">
		<div class="bbcs-card bbcs-card-pad">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3"><?php esc_html_e('Site Traffic', 'botblocker-security'); ?></div>
			<?php $data->traffic_chart->render(); ?>
		</div>
		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3"><?php esc_html_e('Geo Data', 'botblocker-security'); ?></div>
			<?php $data->visitors_map->render(); ?>
		</div>
	</div>
	<div class="bbcs-grid bbcs-grid--2 bbcs-mb-5h">
		<div class="bbcs-card bbcs-card-pad">
			<?php $data->top_ips->render(); ?>
		</div>
		<div class="bbcs-card bbcs-card-pad">
			<?php $data->top_countries->render(); ?>
		</div>
	</div>
<?php
};
