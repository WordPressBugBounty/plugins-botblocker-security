<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane fade show active" id="bbcs_recaptchav2">
    <div class="row">

    	<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
                <?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/google.svg'); ?>"
					alt="<?php esc_attr_e('Google Recaptcha 2', 'botblocker-security'); ?>"
					class="img-fluid bbcs-info-image mb-3">				<p class="bbcs-info-text">
					<?php esc_html_e('Google reCAPTCHA v2 provides visible CAPTCHA challenges to verify human users and prevent automated bot attacks. This integration displays interactive puzzles that users must solve before submitting forms or accessing protected areas of your website.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Configure your Google reCAPTCHA v2 site and secret keys to enable this protection layer. The visible challenges help distinguish between legitimate users and malicious bots while maintaining user experience for genuine visitors.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/recaptcha-v2-in-botblocker-an-additional-user-verification-method-and-how-to-set-up-keys/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('About Recaptcha v2', 'botblocker-security'); ?></a>
                    <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Create Recaptcha keys', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>        
        
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('reCAPTCHA v2', 'botblocker-security'); ?></h3>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('reCAPTCHA v2 Site Key:', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" 
                    data-bs-toggle="tooltip" 
                    data-bs-html="true" 
                    data-bs-placement="top" 
                    data-bs-original-title="<?php esc_attr_e('Enter your Google reCAPTCHA v2 site key obtained from Google reCAPTCHA console. This key is used to display the CAPTCHA challenge on your website forms.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner"> 
                    <input type="text" class="bbcs_text_input_input" name="recaptcha_key2" 
                    value="<?php echo isset($bbcs_settings['recaptcha_key2']) ? esc_html($bbcs_settings['recaptcha_key2']) : ''; ?>">
        			<?php if (empty($BBCS->settings->recaptcha_key2)): ?>
            			<small class="text-muted">
                			<?php esc_html_e('Site key not set.', 'botblocker-security'); ?> 
                            <a href="https://www.google.com/recaptcha/admin/create" target="_blank">
                                <?php esc_html_e('Create now', 'botblocker-security'); ?></a>
            			</small>
        			<?php endif; ?>   
                </div>
            </div>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('reCAPTCHA v2 Secret Key:', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" 
                    data-bs-toggle="tooltip" 
                    data-bs-html="true" 
                    data-bs-placement="top" 
                    data-bs-original-title="<?php esc_attr_e('Enter your Google reCAPTCHA v2 secret key. This private key is used to verify user responses on the server side and should be kept confidential.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <input type="text" class="bbcs_text_input_input" name="recaptcha_secret2" 
                    value="<?php echo isset($bbcs_settings['recaptcha_secret2']) ? esc_html($bbcs_settings['recaptcha_secret2']) : ''; ?>">
        			<?php if (empty($BBCS->settings->recaptcha_secret2)): ?>
            			<small class="text-muted">
                			<?php esc_html_e('Secret key not set.', 'botblocker-security'); ?> 
                            <a href="https://www.google.com/recaptcha/admin/create" target="_blank">
                                <?php esc_html_e('Create now', 'botblocker-security'); ?></a>
            			</small>
        			<?php endif; ?>   
                </div>
            </div>
        </div>

    </div>
</div>
