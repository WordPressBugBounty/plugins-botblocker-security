<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><section class="card">
	<header class="card-header">
		<div class="card-actions">
			<a href="<?php echo esc_url( $BBCSA->pages_reports ); ?>#full" class="bbcs-icon-button bbcs-card-action"
				data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
				data-bs-original-title="<?php esc_attr_e( 'Full report', 'botblocker-security' ); ?>">
				<i class="fa-solid fa-chart-line"></i>
			</a>
			<a href="<?php echo esc_url( $BBCSA->pages_settings ); ?>" class="bbcs-icon-button" data-bs-toggle="tooltip"
				data-bs-html="true" data-bs-placement="top"
				data-bs-original-title="<?php esc_html_e( 'Settings', 'botblocker-security' ); ?>">
				<i class="bbcs-card-action fa-solid fa-gear"></i>
			</a>
		</div>
		<h2 class="card-title"> 
		<?php
		esc_html_e( 'Traffic geo data', 'botblocker-security' );
		echo wp_kses_post( BotBlockerUI::is_realtime() );
		?>
		</h2>
		<p class="card-subtitle">
			<?php esc_html_e( 'Real-time visitor geo statistics. View period:', 'botblocker-security' ); ?><?php echo esc_html( $BBCS->settings->admin_report_period ); ?>
			<?php esc_html_e( 'days', 'botblocker-security' ); ?> (<a
				href="<?php echo esc_url( $BBCSA->pages_settings ); ?>#settings-ui"><?php esc_html_e( 'Change', 'botblocker-security' ); ?></a>).</p>
	</header>
	<div class="card-body">
		<div class="row">
			<div class="col-lg-4 bbcs-border-right">
				<!-- PERIOD -->
				<?php echo do_shortcode( '[bbcs_top_countries limit="10" days="' . $BBCS->settings->admin_report_period . '"]' ); ?>
			</div>
			<div class="col-lg-8">
				<?php echo do_shortcode( '[bbcs_visitors_jsvectormap days="' . $BBCS->settings->admin_report_period . '" height="300px"]' ); ?>
			</div>
		</div>
	</div>
</section>
