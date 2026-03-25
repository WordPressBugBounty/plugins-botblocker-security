<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="tab-pane container fade show active" id="simple_bots"> 
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/simple-bot-detection.svg'); ?>" 
					alt="<?php esc_attr_e('Simple Bot Detection', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('Simple bot detection analyzes basic browser characteristics to catch bots that fail to mimic real browsers.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Each method targets a specific bot weakness. Some privacy tools may trigger false positives.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/simple-bot-blocking-settings-you-should-enable/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Simple Bot Detection', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/understanding-user-agent-strings-methods-for-bot-detection/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('User-Agent', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/accept-language-header-basic-bot-detection-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Accept-Language', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/how-javascript-support-check-in-botblocker-pro-detects-bots/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Javascript Support', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/geoip-language-mismatch-advanced-bot-filtering-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('GeoIP Language mismatch', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/ptr-record-checks-detecting-fake-bots-with-reverse-dns-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('PTR', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/ptr-equals-ip-optional-blocking-for-generic-reverse-dns-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('PTR anomalies', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/what-is-the-referer-header-filtering-fake-dangerous-and-invalid-referer-traffic-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Referer', 'botblocker-security'); ?></a>
				</div>				
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Simple Bot Detection', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_empty_ua" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, isset($bbcs_settings['block_empty_ua']) ? $bbcs_settings['block_empty_ua'] : 0); ?>>					
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('Empty User-Agent', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Block requests with missing User-Agent header.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_simplebot_ua" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, isset($bbcs_settings['block_simplebot_ua']) ? $bbcs_settings['block_simplebot_ua'] : 0); ?>>					
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('User-Agent Anomalies', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Block known anti-detect and malformed User-Agent strings.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_empty_lang" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, isset($bbcs_settings['block_empty_lang']) ? $bbcs_settings['block_empty_lang'] : 0); ?>>					
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('Empty Accept-Language', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Block requests with missing Accept-Language header.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_nojs_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, isset($bbcs_settings['block_nojs_users']) ? $bbcs_settings['block_nojs_users'] : 0); ?>>					
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('No JavaScript Support', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Block visitors without JavaScript support.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">				
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_fake_ref" class="bbcs_checkbox_input_input" value="1" <?php checked(1, isset($bbcs_settings['block_fake_ref']) ? $bbcs_settings['block_fake_ref'] : 1); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Fake Referer', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Block requests with spoofed Referer headers.', 'botblocker-security'); ?>">
				</i>
			</div>

		<h3 class="bbcs_settings_h3"><?php esc_html_e('PTR Options', 'botblocker-security'); ?></h3>
			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_ip_ptr_match" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, isset($bbcs_settings['block_ip_ptr_match']) ? $bbcs_settings['block_ip_ptr_match'] : 0); ?>>					
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('PTR / DNS Anomalies', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Block IPs where forward and reverse DNS records don&#39;t match.', 'botblocker-security'); ?>">
				</i>
			</div>
		<h3 class="bbcs_settings_h3"><?php esc_html_e('Extra Options', 'botblocker-security'); ?></h3>
			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_incorrect_lang_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, isset($bbcs_settings['block_incorrect_lang_users']) ? $bbcs_settings['block_incorrect_lang_users'] : 0); ?>>					
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('Geo IP / Language Mismatch', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Flag visitors whose browser language doesn&#39;t match their GeoIP location. May affect travelers and VPN users.', 'botblocker-security'); ?>">
				</i>
			</div>

		</div>
	</div>
</div>
