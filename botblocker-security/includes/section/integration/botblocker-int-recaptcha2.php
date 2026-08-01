<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="tab-pane fade show active" id="bbcs_recaptchav2">
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/google.svg' ); ?>"
					alt="<?php esc_attr_e( 'Google reCaptcha 2', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">				<p class="bbcs-info-text">
					<?php esc_html_e( 'Google reCaptcha v2 displays interactive puzzles to verify human users before form submission or page access.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Enter your Google reCaptcha v2 site and secret keys below.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/recaptcha-v2-in-botblocker-an-additional-user-verification-method-and-how-to-set-up-keys/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'About reCaptcha v2', 'botblocker-security' ); ?></a>
					<a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Create reCaptcha keys', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>        
		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'reCaptcha v2', 'botblocker-security' ); ?></h3>
			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'reCaptcha v2 Site Key:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true" 
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Enter your Google reCaptcha v2 site key obtained from Google reCaptcha console. This key is used to display the Captcha challenge on your website forms.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner"> 
					<input type="text" class="bbcs_text_input_input" name="recaptcha_key2" 
					value="<?php echo isset( $bbcs_settings['recaptcha_key2'] ) ? esc_html( $bbcs_settings['recaptcha_key2'] ) : ''; ?>">
					<?php if ( empty( $BBCS->settings->recaptcha_key2 ) ) : ?>
						<small class="text-muted">
							<?php esc_html_e( 'Site key not set.', 'botblocker-security' ); ?> 
							<a href="https://www.google.com/recaptcha/admin/create" target="_blank">
								<?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a>
						</small>
					<?php endif; ?>   
				</div>
			</div>
			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'reCaptcha v2 Secret Key:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true" 
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Enter your Google reCaptcha v2 secret key. This private key is used to verify user responses on the server side and should be kept confidential.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="recaptcha_secret2" 
					value="<?php echo isset( $bbcs_settings['recaptcha_secret2'] ) ? esc_html( $bbcs_settings['recaptcha_secret2'] ) : ''; ?>">
					<?php if ( empty( $BBCS->settings->recaptcha_secret2 ) ) : ?>
						<small class="text-muted">
							<?php esc_html_e( 'Secret key not set.', 'botblocker-security' ); ?> 
							<a href="https://www.google.com/recaptcha/admin/create" target="_blank">
								<?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a>
						</small>
					<?php endif; ?>   
				</div>
			</div>
		</div>

	</div>
</div>
