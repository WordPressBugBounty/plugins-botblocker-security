<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="tab-pane fade" id="bbcs_api">
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/api.svg' ); ?>"
					alt="<?php esc_attr_e( 'GLOBUS.studio / BotBlocker API', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">				
					<p class="bbcs-info-text">
					<?php esc_html_e( 'BotBlocker API connects your site to cloud threat intelligence for real-time security data beyond local detection.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Configure API endpoints for BotBlocker and GLOBUS.studio services to access threat databases and security analytics.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/how-botblocker-pros-cloud-verification-defeats-bots/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Cloud verification', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'BotBlocker API Integration', 'botblocker-security' ); ?></h3>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input">
						<?php esc_html_e( 'BotBlocker API URL:', 'botblocker-security' ); ?>
					</span>
					<i class="fa-regular fa-circle-question"
						data-bs-toggle="tooltip"
						data-bs-html="true"
						data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'BotBlocker API endpoint for threat intelligence and security updates.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="bbcs_api_url"
						value="<?php echo isset( $bbcs_settings['bbcs_api_url'] ) ? esc_url( $bbcs_settings['bbcs_api_url'] ) : ''; ?>"
						readonly>
				</div>
			</div>


			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input">
						<?php esc_html_e( 'Additional API URL:', 'botblocker-security' ); ?>
					</span>
					<i class="fa-regular fa-circle-question"
						data-bs-toggle="tooltip"
						data-bs-html="true"
						data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Enter the additional API endpoint URL for advanced security analytics and threat monitoring services. This integration provides comprehensive security insights.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="bbcs_api_gs_url"
						value="<?php echo isset( $bbcs_settings['bbcs_api_gs_url'] ) ? esc_url( $bbcs_settings['bbcs_api_gs_url'] ) : ''; ?>"
						readonly>
				</div>
			</div>

		</div>
	</div>
</div>
