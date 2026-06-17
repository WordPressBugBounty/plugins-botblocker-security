<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
	<div class="col-lg-6">
		<section class="card">
			<header class="card-header">
				<div class="card-actions">
					<a href="<?php echo esc_url( $BBCSA->pages_settings ); ?>" class="bbcs-icon-button"
						data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>">
						<i class="bbcs-card-action fa-solid fa-gear"></i>
					</a>
				</div>
				<h2 class="card-title"><?php esc_html_e( 'Statistics', 'botblocker-security' ); ?></h2>
			</header>
			<div class="card-body">
				<?php echo do_shortcode( '[bbcs_counters_grid]' ); ?>
			</div>
		</section>


		<section class="card">
			<?php $bbcs_is_cloud_api_active = ( ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ) ); ?>
			<header class="card-header<?php echo $bbcs_is_cloud_api_active ? '' : ' bbcs-cloud-api-color-bg'; ?>">
				<div class="card-actions">
					<?php $bbcs_iconColor = $bbcs_is_cloud_api_active ? '' : ' bbcs-color-white'; ?>
					<a href="<?php echo esc_url( $BBCSA->pages_addons ); ?>#bbcs-installed" class="bbcs-icon-button bbcs-card-action" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Add-ons manager', 'botblocker-security' ); ?>">
						<i class="fa-solid fa-puzzle-piece<?php echo esc_attr( $bbcs_iconColor ); ?>"></i>
					</a>
				</div>
				<h2 class="card-title<?php echo $bbcs_is_cloud_api_active ? '' : ' bbcs-color-white'; ?>"><?php esc_html_e( 'Add-ons', 'botblocker-security' ); ?></h2>
			</header>

			<div class="card-body">
				<?php BotBlockerUI::render_dashboard_addons_summary(); ?>
			</div>
		</section>
	</div>
