<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

BotBlockerUI::enforce_recaptcha_v3_dependencies();
$bbcs_keys_ready = class_exists('BotBlockerUI') ? BotBlockerUI::recaptcha_v3_keys_ready() : false;
?>
<div class="tab-pane fade" id="bbcs_recaptchav3">
    <div class="row">

    	<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/google.svg'); ?>" 
					alt="<?php esc_attr_e('Google Recaptcha 3', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">              	<p class="bbcs-info-text">
					<?php esc_html_e('Google reCAPTCHA v3 offers invisible bot protection by analyzing user behavior patterns and generating risk scores without interrupting the user experience. This advanced system continuously monitors interactions to detect suspicious activities.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Set your threshold level to determine when to block or allow visitors based on their risk scores. Lower thresholds provide stricter protection while higher values allow more permissive access for borderline cases.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/recaptcha-v3-in-botblocker-user-verification-and-key-setup-guide/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('About Recaptcha v3', 'botblocker-security'); ?></a>
                    <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Create Recaptcha keys', 'botblocker-security'); ?></a>
                </div>
			</div>
		</div>        
        
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('reCAPTCHA v3', 'botblocker-security'); ?></h3>

            <div class="bbcs_checkbox_input mb-2">
                <div class="bbcs_label_checkbox_box">
                    <input type="checkbox" name="recaptcha_check" value="1" <?php echo $bbcs_keys_ready ? checked(1, isset($bbcs_settings['recaptcha_check']) ? $bbcs_settings['recaptcha_check'] : 1, false) : ''; ?> <?php disabled(!$bbcs_keys_ready); ?>>
                    <span class="bbcs_label_input_checkbox"><?php esc_html_e('Enable reCAPTCHA v3 protection', 'botblocker-security'); ?></span>
                </div>
                <i class="fa-regular fa-circle-question" 
                data-bs-toggle="tooltip" 
                data-bs-html="true" 
                data-bs-placement="top" 
                data-bs-original-title="<?php esc_attr_e('Enable invisible reCAPTCHA v3 protection to automatically analyze user behavior and block suspicious bot traffic without user interaction.', 'botblocker-security'); ?>">
                </i>
            </div>

            <div class="bbcs_checkbox_input mb-2">
                <div class="bbcs_label_checkbox_box">
                    <input type="checkbox" name="recaptcha_v3_ipv6_block" value="1" <?php echo $bbcs_keys_ready ? checked(1, isset($bbcs_settings['recaptcha_v3_ipv6_block']) ? $bbcs_settings['recaptcha_v3_ipv6_block'] : 1, false) : ''; ?> <?php disabled(!$bbcs_keys_ready); ?>>
                    <span class="bbcs_label_input_checkbox"><?php esc_html_e('Block IPv6 connections', 'botblocker-security'); ?></span>
                </div>
                <i class="fa-regular fa-circle-question" 
                data-bs-toggle="tooltip" 
                data-bs-html="true" 
                data-bs-placement="top" 
                data-bs-original-title="<?php esc_attr_e('Enable to automatically block visitors using IPv6 addresses. This can help reduce bot traffic but may affect legitimate users with IPv6 connections.', 'botblocker-security'); ?>">
                </i>
            </div>                                    

            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('reCAPTCHA v3 Site Key:', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" 
                    data-bs-toggle="tooltip" 
                    data-bs-html="true" 
                    data-bs-placement="top" 
                    data-bs-original-title="<?php esc_attr_e('Enter your Google reCAPTCHA v3 site key. This key enables invisible bot protection by analyzing user interactions across your website.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <input type="text" class="bbcs_text_input_input" name="recaptcha_key3" 
                    value="<?php echo isset($bbcs_settings['recaptcha_key3']) ? esc_html($bbcs_settings['recaptcha_key3']) : ''; ?>">
        			<?php if (empty($BBCS->settings->recaptcha_key3)): ?>
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
                    <span class="bbcs-label-input"><?php esc_html_e('reCAPTCHA v3 Secret Key:', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" 
                    data-bs-toggle="tooltip" 
                    data-bs-html="true" 
                    data-bs-placement="top" 
                    data-bs-original-title="<?php esc_attr_e('Enter your Google reCAPTCHA v3 secret key. This private key verifies user authenticity scores and determines bot likelihood for enhanced security.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <input type="text" class="bbcs_text_input_input" name="recaptcha_secret3" 
                    value="<?php echo isset($bbcs_settings['recaptcha_secret3']) ? esc_html($bbcs_settings['recaptcha_secret3']) : ''; ?>">
        			<?php if (empty($BBCS->settings->recaptcha_secret3)): ?>
            			<small class="text-muted">
                			<?php esc_html_e('Secret key not set.', 'botblocker-security'); ?> 
                            <a href="https://www.google.com/recaptcha/admin/create" target="_blank">
                                <?php esc_html_e('Create now', 'botblocker-security'); ?></a>
            			</small>
        			<?php endif; ?>   
                </div>
            </div>

            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input">
                        <?php esc_html_e( 'Set the recaptcha treshold level', 'botblocker-security' ); ?>
                        - <span id="bbcs_recaptcha_tresshold_value">
                            <?php echo isset( $bbcs_settings['recaptcha_tresshold'] ) ? esc_html( $bbcs_settings['recaptcha_tresshold'] ) : '0.5'; ?>
                        </span>
                    </span>
                    <i class="fa-regular fa-circle-question" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        data-bs-original-title="<?php esc_attr_e( 'Set the recaptcha treshold level (0.1 - 1.0)', 'botblocker-security' ); ?>">
                    </i>
                </div>
                <div class="bbcs_text_input_inner">
                    <small class="text-muted">
                        <?php esc_html_e( 'Move the slider to adjust the threshold level', 'botblocker-security' ); ?>
                    </small>
                    <input type="range"
                        class="form-range"
                        id="bbcs_recaptcha_tresshold"
                        name="recaptcha_tresshold"
                        min="0.1"
                        max="1"
                        step="0.1"
                        value="<?php echo isset( $bbcs_settings['recaptcha_tresshold'] ) ? esc_attr( $bbcs_settings['recaptcha_tresshold'] ) : '0.5'; ?>"
                        required>
                </div>
            </div>

        </div>
    </div>
</div>
