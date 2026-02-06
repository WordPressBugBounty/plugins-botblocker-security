<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
 
<div class="tab-pane container fade" id="log"> 	
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/logging-settings.svg'); ?>" 
					alt="<?php esc_attr_e('Logging Settings', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('Logging settings control what security events and visitor activities are recorded for analysis and troubleshooting. These options determine the level of detail captured in your security logs, helping you monitor threats, analyze traffic patterns, and maintain comprehensive security records for your website.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Selective logging allows you to focus on specific security events while managing storage space and performance. Enable logging for verification requests, allowed visitors, and blocked attempts to build a complete security picture. Administrative and WordPress action logging provides insight into backend activities and potential security issues.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/wordpress-self-requests-how-they-work-and-why-they-matter/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('WordPress self requests', 'botblocker-security'); ?></a>
					<a href="https://en.wikipedia.org/wiki/Command-line_interface" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('CLI requests', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Visitor logging settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_tests" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_tests']) ? $bbcs_settings['botblocker_log_tests'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Manual Verification Requests', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of users who are required to pass manual verification.', 'botblocker-security'); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2"> 
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_local" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_local']) ? $bbcs_settings['botblocker_log_local'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Verified Local Visitors', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of users who have previously passed verification and are allowed based on IP or cookies.', 'botblocker-security'); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_allow" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_allow']) ? $bbcs_settings['botblocker_log_allow'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Allowed Visitors', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of visitors who meet security criteria and are allowed access.', 'botblocker-security'); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_fake" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_fake']) ? $bbcs_settings['botblocker_log_fake'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Suspected Fake Bots', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of visitors suspected to be bots using fake or spoofed data.', 'botblocker-security'); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_goodip" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_goodip']) ? $bbcs_settings['botblocker_log_goodip'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Known Good IPs', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of visits from trusted or whitelisted IP addresses.', 'botblocker-security'); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_block" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_block']) ? $bbcs_settings['botblocker_log_block'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Blocked Visitors', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of visitors who were blocked based on security rules.', 'botblocker-security'); ?>">
			</i>
			</div>

		</div>
		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Admin & WP logging settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_admin" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_admin']) ? $bbcs_settings['botblocker_log_admin'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log Actions in WordPress Admin Panel', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of actions performed within the WordPress admin panel by authorized users.', 'botblocker-security'); ?>">
			</i>
			</div>			

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_bbcs" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_bbcs']) ? $bbcs_settings['botblocker_log_bbcs'] : 0); ?>>
    			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log BotBlocker pages visits', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of visits to BotBlocker verification and protection pages for monitoring purposes.', 'botblocker-security'); ?>">
			</i>
			</div> 
			
			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_wp" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_wp']) ? $bbcs_settings['botblocker_log_wp'] : 1); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log WordPress Actions', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of actions on the website, including blocked access attempts based on security rules.', 'botblocker-security'); ?>">
			</i>
			</div>  

			<h3 class="bbcs_settings_h3"><?php esc_html_e('Errors logging settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_error" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_error']) ? $bbcs_settings['botblocker_log_error'] : 1); ?>>
    			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log BotBlocker errors', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of BotBlocker internal errors and system issues for debugging purposes.', 'botblocker-security'); ?>">
			</i>
			</div>   

			<h3 class="bbcs_settings_h3"><?php esc_html_e('Extra logging settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_cli" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_cli']) ? $bbcs_settings['botblocker_log_cli'] : 1); ?>>
    			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log CLI requests', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging of command-line interface requests and automated scripts accessing your site.', 'botblocker-security'); ?>">
			</i>
			</div>        

 			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
    			<input type="checkbox" name="botblocker_log_disabled" value="1" <?php checked(1, isset($bbcs_settings['botblocker_log_disabled']) ? $bbcs_settings['botblocker_log_disabled'] : 0); ?>>
    			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Log visits if BotBlocker processor is off', 'botblocker-security'); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e('Enable logging when BotBlocker protection is disabled to monitor unprotected traffic.', 'botblocker-security'); ?>">
			</i>
			</div> 			

		</div>
	</div> 
</div>
