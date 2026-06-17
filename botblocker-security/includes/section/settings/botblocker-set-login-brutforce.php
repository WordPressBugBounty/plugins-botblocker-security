<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="tab-pane container fade" id="login_brutforce">
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
				// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/security.svg' ); ?>"
					alt="<?php esc_attr_e( 'Login Brute-Force Protection', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Prevent password guessing attacks with temporary IP lockouts after failed login attempts.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Adjust attempt limits and block times to balance security and usability.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/Brute-force_attack" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Brute-force attack', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Login Brute-Force Protection', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="login_brutforce_enabled" class="bbcs_checkbox_input_input" value="1" <?php checked( 1, isset( $bbcs_settings['login_brutforce_enabled'] ) ? $bbcs_settings['login_brutforce_enabled'] : 1 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable Login Brute-Force Protection', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Activate brute-force login protection.', 'botblocker-security' ); ?>"></i>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Failed Attempts Before Blocking:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Failed attempts allowed before blocking the IP.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_number_input_inner">
					<input type="number" min="1" class="bbcs_number_input_input" name="login_brutforce_attempts" value="<?php echo isset( $bbcs_settings['login_brutforce_attempts'] ) ? esc_attr( $bbcs_settings['login_brutforce_attempts'] ) : 5; ?>">
				</div>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Failed Attempt Time Window (seconds):', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Time period for counting failed login attempts.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_number_input_inner">
					<input type="number" min="1" class="bbcs_number_input_input" name="login_brutforce_period" value="<?php echo isset( $bbcs_settings['login_brutforce_period'] ) ? esc_attr( $bbcs_settings['login_brutforce_period'] ) : 900; ?>">
				</div>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Primary Block Time (seconds):', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Block duration for first-time offenders.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_number_input_inner">
					<input type="number" min="1" class="bbcs_number_input_input" name="login_brutforce_primary_block_time" value="<?php echo isset( $bbcs_settings['login_brutforce_primary_block_time'] ) ? esc_attr( $bbcs_settings['login_brutforce_primary_block_time'] ) : 900; ?>">
				</div>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Secondary Block Time (seconds):', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Block duration for repeat offenders.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_number_input_inner">
					<input type="number" min="1" class="bbcs_number_input_input" name="login_brutforce_secondary_block_time" value="<?php echo isset( $bbcs_settings['login_brutforce_secondary_block_time'] ) ? esc_attr( $bbcs_settings['login_brutforce_secondary_block_time'] ) : 1800; ?>">
				</div>
			</div>
		</div>
	</div>
</div>
