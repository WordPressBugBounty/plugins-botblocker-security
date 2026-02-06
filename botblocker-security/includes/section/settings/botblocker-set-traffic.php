<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
  
<div class="tab-pane container fade" id="traffic"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/traffic.svg'); ?>" 
					alt="<?php esc_attr_e('Traffic & Referrer Settings', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('Traffic and referrer settings optimize how BotBlocker handles various types of incoming traffic while maintaining proper SEO practices. These configurations help distinguish between legitimate traffic sources and potential security threats by analyzing referrer patterns, UTM parameters, and request origins.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('UTM processing enables comprehensive traffic source tracking while filtering suspicious campaigns. The noindex and noarchive directives prevent search engines from indexing security-related pages or caching blocked content. Iframe restrictions protect against clickjacking attacks by controlling which domains can embed your content.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/UTM_parameters" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('UTM', 'botblocker-security'); ?></a>
					<a href="https://en.wikipedia.org/wiki/Query_string" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Query string', 'botblocker-security'); ?></a>
					<a href="https://en.wikipedia.org/wiki/Frame_(World_Wide_Web)" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('iFrame', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Traffic & Referrer Settings', 'botblocker-security'); ?></h3>
			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="utm_referrer" value="1" <?php checked(1, isset($bbcs_settings['utm_referrer']) ? $bbcs_settings['utm_referrer'] : 1); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('UTM Referrer Processing', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Enabling helps track traffic sources, retain the original referrer across page transitions, personalize content for users, and filter suspicious traffic.', 'botblocker-security'); ?>">
    			</i>
			</div>


			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="check_get_ref" value="1" <?php checked(1, isset($bbcs_settings['check_get_ref']) ? $bbcs_settings['check_get_ref'] : 1); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Check GET Parameters in Referrer', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
    			data-bs-original-title="<?php esc_attr_e('Scan the URL referrer for specific GET parameters to detect unwanted or suspicious traffic sources.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="iframe_stop" value="1" <?php checked(1, isset($bbcs_settings['iframe_stop']) ? $bbcs_settings['iframe_stop'] : 0); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Stop Loading Iframes', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Stop loading iframes for domains different from the origin to prevent potential security risks like clickjacking. This restriction allows only same-origin content to be embedded in iframes, enhancing site security by limiting unauthorized content framing.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e('Headers settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="noarchive" value="1" <?php checked(1, isset($bbcs_settings['noarchive']) ? $bbcs_settings['noarchive'] : 0); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('noarchive for Blocked Pages', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
    			data-bs-original-title="<?php esc_attr_e('Apply a no-archive tag to blocked pages, preventing search engines from storing cached versions of these pages.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="utm_noindex" value="1" <?php checked(1, isset($bbcs_settings['utm_noindex']) ? $bbcs_settings['utm_noindex'] : 0); ?>>        	
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('noindex on UTM Pages', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
    			data-bs-original-title="<?php esc_attr_e('Add a noindex tag to prevent search engines from indexing pages with UTM tracking parameters. This helps avoid duplicate content issues.', 'botblocker-security'); ?>">
    			</i>
			</div>

		</div>
	</div>
</div>
