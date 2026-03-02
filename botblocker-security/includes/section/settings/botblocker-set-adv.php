<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$bbcs_has_pro = bbcs_isCloudAPIActive();
$bbcs_is_ultimate = bbcs_isCloudAPIUltimate();
?>

<div class="tab-pane container fade" id="advanced_protection"> 
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/advanced-protection.svg'); ?>" 
					alt="<?php esc_attr_e('Advanced Protection', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('Advanced Protection provides enhanced security against sophisticated bots by using cloud-based validation and intelligent verification techniques. These settings enable multi-layered protection that can detect even the most advanced automated threats. Cloud validation analyzes suspicious traffic patterns in real-time, while verification settings enforce security measures appropriate to your site\'s needs. Together, these tools create a comprehensive defense system that adapts to emerging threats while minimizing impact on legitimate users.', 'botblocker-security'); ?>				
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/how-botblocker-pros-cloud-verification-defeats-bots/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Cloud validation', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/cloud-verification-in-botblocker-database-types-used-for-advanced-threat-detection/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Cloud databases of threats', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/botblocker-free-vs-pro-which-version-to-choose/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('PRO vs Free', 'botblocker-security'); ?></a>
				</div>	
			</div>
		</div>
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Advanced Protection', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="check" class="bbcs_checkbox_input_input" value="1" <?php checked(1, isset($bbcs_settings['check']) ? $bbcs_settings['check'] : 0); ?> <?php if (!$bbcs_has_pro) echo 'disabled'; ?>>
        			<span class="bbcs-cloud-api-column">
            			<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e('Cloud Validation', 'botblocker-security'); ?></span>
            			<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
                			<?php esc_html_e('PRO option', 'botblocker-security'); ?> (<a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>"><?php esc_html_e('Connect now!', 'botblocker-security'); ?></a>)
            			</small>
        			</span>
    			</div>
    			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" 
        			data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Sends suspicious requests to BotBlocker Cloud for in-depth analysis.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="unresponsive" value="1" <?php checked(1, isset($bbcs_settings['unresponsive']) ? $bbcs_settings['unresponsive'] : 1); ?> <?php if (!$bbcs_has_pro) echo 'disabled'; ?>>
        			<span class="bbcs-cloud-api-column">
        			<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e('Ban Unresponsive Clients', 'botblocker-security'); ?></span>
        			<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
                			<?php esc_html_e('PRO option', 'botblocker-security'); ?> (<a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>"><?php esc_html_e('Connect now!', 'botblocker-security'); ?></a>)
            			</small>
        			</span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Instantly blocks clients that fail to respond to cloud validation.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="botblocker_force_check" value="1" <?php checked(1, isset($bbcs_settings['botblocker_force_check']) ? $bbcs_settings['botblocker_force_check'] : 1); ?>>
        			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Force Verification for All', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Always present captcha, regardless of other checks.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="force_cloud_validation" class="bbcs_checkbox_input_input" value="1" <?php checked(1, isset($bbcs_settings['force_cloud_validation']) ? $bbcs_settings['force_cloud_validation'] : 0); ?> <?php if (!$bbcs_is_ultimate) echo 'disabled'; ?>>
        			<span class="bbcs-cloud-api-column">
            			<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e('Force Cloud Validation', 'botblocker-security'); ?></span>
            			<small class="text-muted bbcs-ps-5" <?php echo $bbcs_is_ultimate ? 'hidden' : ''; ?>>
                			<?php esc_html_e('Ultimate tier only', 'botblocker-security'); ?> (<a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>"><?php esc_html_e('Upgrade now!', 'botblocker-security'); ?></a>)
            			</small>
        			</span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Forces cloud validation for every visitor, regardless of other checks. Available only for Ultimate tier licenses.', 'botblocker-security'); ?>">
    			</i>
			</div>
		</div>
	</div>
</div>
