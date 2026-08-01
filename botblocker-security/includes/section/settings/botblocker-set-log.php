<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
 
<div class="tab-pane container fade" id="log"> 	
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/logging-settings.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Logging Settings', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Choose which security events and visitor activities to log.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Log verification requests, allowed visitors, blocked attempts, and admin actions.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/wordpress-self-requests-how-they-work-and-why-they-matter/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'WordPress self requests', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Command-line_interface" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'CLI requests', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Visitor Logging Settings', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_tests" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_tests'] ) ? $bbcs_settings['botblocker_log_tests'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Manual Verification Requests', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record visitors sent to Captcha challenges. Useful for analysing bot detection effectiveness.', 'botblocker-security' ); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2"> 
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_local" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_local'] ) ? $bbcs_settings['botblocker_log_local'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Verified Local Visitors', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Track returning verified visitors. Helps identify repeat traffic patterns and false positives.', 'botblocker-security' ); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_allow" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_allow'] ) ? $bbcs_settings['botblocker_log_allow'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Allowed Visitors', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record visitors who pass all security checks. Useful for traffic analysis and audit trails.', 'botblocker-security' ); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_fake" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_fake'] ) ? $bbcs_settings['botblocker_log_fake'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Suspected Fake Bots', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record visitors detected as likely bots via fingerprinting inconsistencies.', 'botblocker-security' ); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_goodip" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_goodip'] ) ? $bbcs_settings['botblocker_log_goodip'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Known Good IPs', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record traffic from whitelisted IPs to verify rule effectiveness.', 'botblocker-security' ); ?>">
			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_block" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_block'] ) ? $bbcs_settings['botblocker_log_block'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Blocked Visitors', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record blocked visitor data for attack pattern analysis and threat intelligence.', 'botblocker-security' ); ?>">
			</i>
			</div>

		</div>
		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Admin and WordPress Logging', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_admin" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_admin'] ) ? $bbcs_settings['botblocker_log_admin'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Actions in WordPress Admin Panel', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Track admin panel activity for security auditing and multi-user accountability.', 'botblocker-security' ); ?>">
			</i>
			</div>			

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_bbcs" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_bbcs'] ) ? $bbcs_settings['botblocker_log_bbcs'] : 0 ); ?>>
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log BotBlocker Page Visits', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record challenge page visits. Helps monitor bot traffic that reaches verification stage.', 'botblocker-security' ); ?>">
			</i>
			</div> 
			
			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_wp" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_wp'] ) ? $bbcs_settings['botblocker_log_wp'] : 1 ); ?>>    			
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log WordPress Actions', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record WordPress events such as logins, password resets, and blocked access attempts.', 'botblocker-security' ); ?>">
			</i>
			</div>  

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Error Logging', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_error" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_error'] ) ? $bbcs_settings['botblocker_log_error'] : 1 ); ?>>
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log BotBlocker Errors', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record internal errors for debugging and support troubleshooting.', 'botblocker-security' ); ?>">
			</i>
			</div>   

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Extra Logging', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_cli" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_cli'] ) ? $bbcs_settings['botblocker_log_cli'] : 1 ); ?>>
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log CLI requests', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Track WP-CLI and cron execution. Helps detect automated exploitation attempts.', 'botblocker-security' ); ?>">
			</i>
			</div>        

			<div class="bbcs_checkbox_input mb-2">
			<div class="bbcs_label_checkbox_box">
				<input type="checkbox" name="botblocker_log_disabled" value="1" <?php checked( 1, isset( $bbcs_settings['botblocker_log_disabled'] ) ? $bbcs_settings['botblocker_log_disabled'] : 0 ); ?>>
				<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Visits When BotBlocker Protection is Disabled', 'botblocker-security' ); ?></span>
			</div>
			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
			data-bs-original-title="<?php esc_attr_e( 'Record traffic during maintenance windows or when protection is intentionally disabled.', 'botblocker-security' ); ?>">
			</i>
			</div> 			

		</div>
	</div> 
</div>
