<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?> 
 
<div class="tab-pane container fade" id="captcha"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/captcha.svg'); ?>" 
					alt="<?php esc_attr_e('BotBlocker Captcha', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('BotBlocker CAPTCHA provides multiple verification options that balance security with user experience. Choose from simple button clicks, color-based challenges, our unique image recognition system, or integrate with Google reCAPTCHA. Our exclusive Dynamic Shape and Digit CAPTCHAs are specially designed to be easy for humans but extremely difficult for bots to solve automatically. These proprietary verification methods analyze user interaction patterns while requiring minimal effort from legitimate visitors, effectively blocking automated systems without frustrating real users.', 'botblocker-security'); ?>				
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/all-captcha-types-in-botblocker-maximum-flexibility-and-reliable-protection/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Botblocker Captcha', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/recaptcha-v2-in-botblocker-an-additional-user-verification-method-and-how-to-set-up-keys/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('reCaptcha v2', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/recaptcha-v3-in-botblocker-user-verification-and-key-setup-guide/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('reCaptcha v3', 'botblocker-security'); ?></a>
					<a href="https://en.wikipedia.org/wiki/CAPTCHA" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Captcha', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('BotBlocker Captcha', 'botblocker-security'); ?></h3>

			<div class="bbcs_text_input mb-2">
    			<div class="bbcs_label_input_box">
        			<span class="bbcs-label-input"><?php esc_html_e('Captcha Mode:', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question"
            			data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
            			data-bs-original-title="<?php esc_attr_e('Select the captcha type to use for verifying users. Options include simple button clicks, color matching, image captchas, or reCAPTCHA verification.', 'botblocker-security'); ?>">
        			</i>
    			</div>    			
				<div class="bbcs_text_input_inner">
        			<select class="bbcs_select_input_input" name="bbcs_captcha_mode">
            			<option value="0" <?php selected('0', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); ?>>
                			<?php esc_html_e('Button - "I am not a robot"', 'botblocker-security'); ?>
            			</option>
            			<option value="1" <?php 
                			selected('1', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT);
                			echo ($BBCS->prefly['gd'] === 0 ? 'disabled' : ''); ?>> 
                			<?php esc_html_e('Color Buttons', 'botblocker-security'); ?>
            			</option>
            			<option value="2" <?php 
                			selected('2', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); 
                			echo ($BBCS->prefly['gd'] === 0 ? 'disabled' : ''); ?>
                			>
                			<?php esc_html_e('BotBlocker Image Captcha', 'botblocker-security'); ?>
            			</option>

            			<option value="3" <?php selected('3', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); 
            			echo (empty($BBCS->settings->recaptcha_key2) || empty($BBCS->settings->recaptcha_secret2) ? 'disabled' : '');
            			?>>
                			<?php esc_html_e('reCAPTCHA v2 "I am not a robot"', 'botblocker-security'); ?>
            			</option>
            			<option value="4" <?php selected('4', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); 
            			echo (empty($BBCS->settings->recaptcha_key2) || empty($BBCS->settings->recaptcha_secret2) ? 'disabled' : '');
            			?>>
                			<?php esc_html_e('reCAPTCHA v2', 'botblocker-security'); ?>
            			</option>

            			<option value="5" <?php selected('5', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); ?>>
                			<?php esc_html_e('Dynamic Shape Captcha', 'botblocker-security'); ?>
            			</option>    
            			<option value="6" <?php selected('6', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); ?>>
                			<?php esc_html_e('Dynamic Digit Captcha', 'botblocker-security'); ?>
            			</option>
            			<option value="7" <?php selected('7', isset($bbcs_settings['bbcs_captcha_mode']) ? $bbcs_settings['bbcs_captcha_mode'] : BOTBLOCKER_CAPTCHA_MODE_DEFAULT); ?>>
                			<?php esc_html_e('Hold Button Captcha', 'botblocker-security'); ?>
            			</option>

        			</select>
        			<?php if (isset($BBCS->prefly['gd']) && $BBCS->prefly['gd'] === 0): ?>
            			<small class="text-muted"><?php esc_html_e('GD not enabled', 'botblocker-security'); ?> (<a href="https://www.php.net/manual/en/book.image.php" target="_blank"><?php esc_html_e('Read more', 'botblocker-security'); ?></a>)</small>
        			<?php endif; ?>

        			<?php if (empty($BBCS->settings->recaptcha_key2) || empty($BBCS->settings->recaptcha_secret2)): ?>
            			<small class="text-muted">
                			<?php esc_html_e('reCaptcha v2', 'botblocker-security'); ?> <a href="<?php echo esc_url($BBCSA->pages_integrations); ?>#bbcs_recaptchav2"><?php esc_html_e('not set', 'botblocker-security'); ?></a>
                			(<a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e('Create key and secret', 'botblocker-security'); ?></a>)
            			</small>
        			<?php endif; ?>
    			</div>
			</div>

			<div class="bbcs_text_input mb-2" id="bbcs_captcha_img_inline_block">
    			<div class="bbcs_label_input_box">
        			<span class="bbcs-label-input"><?php esc_html_e('Image Delivery Mode:', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Choose how captcha images are delivered to the browser. Inline Base64 embeds all images directly in the page data (faster, more reliable, no extra requests). Separate Requests loads each image via an individual AJAX call (legacy method).', 'botblocker-security'); ?>">
        			</i>
    			</div>
    			<div class="bbcs_text_input_inner">
        			<select class="bbcs_select_input_input" name="bbcs_captcha_img_inline" id="bbcs_captcha_img_inline" <?php echo ( isset( $bbcs_settings['bbcs_captcha_mode'] ) && $bbcs_settings['bbcs_captcha_mode'] == '2' ) ? '' : 'disabled'; ?>>
            			<option value="1" <?php selected( '1', isset( $bbcs_settings['bbcs_captcha_img_inline'] ) ? $bbcs_settings['bbcs_captcha_img_inline'] : '1' ); ?>><?php esc_html_e('Inline Base64 (Recommended)', 'botblocker-security'); ?></option>
            			<option value="0" <?php selected( '0', isset( $bbcs_settings['bbcs_captcha_img_inline'] ) ? $bbcs_settings['bbcs_captcha_img_inline'] : '1' ); ?>><?php esc_html_e('Separate Requests (Legacy)', 'botblocker-security'); ?></option>
        			</select>
    			</div>
			</div>

			<div class="bbcs_text_input mb-2" id="bbcs_captcha_img_pack_block">
    			<div class="bbcs_label_input_box">
        			<span class="bbcs-label-input"><?php esc_html_e('Image Captcha Pack:', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Choose the set of images displayed in the image captcha. Different packs include animal themes to make verification engaging and unique.', 'botblocker-security'); ?>">
        			</i>
    			</div>
    			<div class="bbcs_text_input_inner">
        			<select class="bbcs_select_input_input" name="bbcs_captcha_img_pack" id="bbcs_captcha_img_pack" <?php echo (isset($bbcs_settings['bbcs_captcha_mode']) && $bbcs_settings['bbcs_captcha_mode'] == '2') ? '' : 'disabled'; ?>>
            			<option value="1" <?php selected('1', isset($bbcs_settings['bbcs_captcha_img_pack']) ? $bbcs_settings['bbcs_captcha_img_pack'] : '1'); ?>><?php esc_html_e('Eagle', 'botblocker-security'); ?></option>
            			<option value="2" <?php selected('2', isset($bbcs_settings['bbcs_captcha_img_pack']) ? $bbcs_settings['bbcs_captcha_img_pack'] : '1'); ?>><?php esc_html_e('Horse', 'botblocker-security'); ?></option>
            			<option value="3" <?php selected('3', isset($bbcs_settings['bbcs_captcha_img_pack']) ? $bbcs_settings['bbcs_captcha_img_pack'] : '1'); ?>><?php esc_html_e('Raccoon', 'botblocker-security'); ?></option>
            			<option value="4" <?php selected('4', isset($bbcs_settings['bbcs_captcha_img_pack']) ? $bbcs_settings['bbcs_captcha_img_pack'] : '1'); ?>><?php esc_html_e('Dog', 'botblocker-security'); ?></option>
            			<option value="5" <?php selected('5', isset($bbcs_settings['bbcs_captcha_img_pack']) ? $bbcs_settings['bbcs_captcha_img_pack'] : '1'); ?>><?php esc_html_e('Cat', 'botblocker-security'); ?></option>
        			</select>
    			</div>
			</div>			
			
			<div class="bbcs_number_input mb-2">
    			<div class="bbcs_label_input_box">
        			<span class="bbcs-label-input"><?php esc_html_e('Captcha Timeout (seconds):', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Set the time limit for completing the captcha. Users need to verify within this duration to avoid re-verification.', 'botblocker-security'); ?>">
        			</i>
    			</div>
    			<div class="bbcs_number_input_inner">
        			<input type="number" 
					class="bbcs_number_input_input" 
					name="bbcs_captcha_wait" 
					value="<?php echo isset($bbcs_settings['bbcs_captcha_wait']) ? esc_html($bbcs_settings['bbcs_captcha_wait']) : 30; ?>">
    			</div>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e('Extended Captcha Check', 'botblocker-security'); ?></h3>
				<p class="bbcs_info_paragraph">
				<?php
					printf(
						wp_kses_post(
							// translators: %s is the URL to the reCaptcha v3 integration configuration page.
							__('Any selected captcha type can be combined with verification using reCaptcha v3. Please <a href="%s">configure integration</a> with reCaptcha.', 'botblocker-security')
						),
						esc_url($BBCSA->pages_integrations) . '#bbcs_recaptchav3'
					);
					?>
				</p>
		</div>
	</div>
</div>
