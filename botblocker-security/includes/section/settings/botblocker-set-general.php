<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="tab-pane container fade" id="general"> 
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/general.svg'); ?>" 
					alt="<?php esc_attr_e('General settings', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('General settings control the core operational parameters of BotBlocker\'s security system. These fundamental configurations determine how aggressively the plugin monitors visitor behavior, manages system resources, and applies security rules. The hit limit settings prevent abuse while allowing legitimate users normal access to your site.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('PTR cache timing optimizes DNS lookup performance without compromising security accuracy. The last rule setting determines the default action when no specific security rule matches a visitor. Administrator IP auto-saving ensures that site administrators never accidentally block themselves during security configuration changes.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/HTTP_cookie" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Cookies', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/ptr-record-checks-detecting-fake-bots-with-reverse-dns-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('PTR', 'botblocker-security'); ?></a>
					<!--<a href="<?php //echo esc_url(BOTBLOCKER_DOCS_URL)?>/administrator-IPs/" target="_blank" class="bbcs-info-footer-a"><?php //esc_html_e('Administrator IPs', 'botblocker-security'); ?></a>-->
					<!--<a href="<?php //echo esc_url(BOTBLOCKER_DOCS_URL)?>/General-settings/" target="_blank" class="bbcs-info-footer-a"><?php //esc_html_e('General settings', 'botblocker-security'); ?></a>-->
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('General', 'botblocker-security'); ?></h3>
			
			<div class="bbcs_radio_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e('Security Check Mode', 'botblocker-security'); ?></span>
					<i class="fa-regular fa-circle-question"
						data-bs-toggle="tooltip" data-bs-html="true"
						data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e('Full Mode enables request-level inspection across all entry points (public frontend, authenticated/admin endpoints, REST/API, AJAX, and background/cron tasks). It can enforce immediate blocking and terminate execution for detected threats, providing earlier containment of malicious activity. Frontend Mode restricts inspection to public frontend routes and template rendering; it does not intercept or fully inspect admin, authenticated, REST/API, AJAX, or background/internal requests, so attacks that use non-frontend vectors may go unchecked.', 'botblocker-security'); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<label class="bbcs-radio-inline">
						<input type="radio" name="secure_mode" value="2" <?php checked(2, isset($bbcs_settings['secure_mode']) ? $bbcs_settings['secure_mode'] : 2); ?> />
						<?php esc_html_e('Full Mode (Check all requests)', 'botblocker-security'); ?>
					</label>
				</div>
				<div class="bbcs_text_input_inner">
					<label class="bbcs-radio-inline">
						<input type="radio" name="secure_mode" value="1" <?php checked(1, isset($bbcs_settings['secure_mode']) ? $bbcs_settings['secure_mode'] : 2); ?> />
						<?php esc_html_e('Frontend Mode (Check frontend only)', 'botblocker-security'); ?>
					</label>
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
    			<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e('Hits Per User', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e('Maximum allowed requests per verified visitor before new verification.', 'botblocker-security'); ?>"></i>
    			</div>
    			<div class="bbcs_text_input_inner">
        			<input type="number" class="bbcs_text_input_input" name="hits_per_user" value="<?php echo isset($bbcs_settings['hits_per_user']) ? esc_html($bbcs_settings['hits_per_user']) : 500; ?>">
    			</div>
			</div>
			<div class="bbcs_number_input mb-2">
    			<div class="bbcs_label_input_box">
        			<span class="bbcs-label-input"><?php esc_html_e('PTR Cache Lifetime', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question"
           data-bs-toggle="tooltip" data-bs-html="true"
           data-bs-placement="top"
           data-bs-original-title="<?php esc_attr_e('Select how long PTR / reverse DNS lookup results are cached.', 'botblocker-security'); ?>">
        			</i>
    			</div>
    			<div class="bbcs_text_input_inner">
        			<select class="bbcs_select_input_input" name="ptrcache_time">
            			<?php foreach (bbcs_get_ptr_lifetimes() as $bbcs_seconds => $bbcs_label) : ?>
                <option value="<?php echo esc_attr($bbcs_seconds); ?>" <?php selected($bbcs_seconds, isset($bbcs_settings['ptrcache_time']) ? $bbcs_settings['ptrcache_time'] : 86400); ?>>
                    <?php echo esc_html($bbcs_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    			</div>
			</div>

			<!--
			<div class="bbcs_text_input mb-2">
    			<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php //esc_html_e('Last Applied Rule', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php //esc_attr_e('What to do if the user has passed all automatic checks: let him through to the site, show him a page where he must pass manual check (blocking) or ban him?', 'botblocker-security'); ?>"></i>
    			</div>
    			<div class="bbcs_text_input_inner">
        			<select class="bbcs_text_input_input" name="last_rule">
            			<option value="" <?php //selected('', isset($bbcs_settings['last_rule']) ? $bbcs_settings['last_rule'] : ''); ?>></option>
            			<option value="allow" <?php //selected('allow', isset($bbcs_settings['last_rule']) ? $bbcs_settings['last_rule'] : ''); ?>><?php //esc_html_e('Allow', 'botblocker-security'); ?></option>
            			<option value="block" <?php //selected('block', isset($bbcs_settings['last_rule']) ? $bbcs_settings['last_rule'] : ''); ?>><?php //esc_html_e('Block', 'botblocker-security'); ?></option>
            			<option value="dark" <?php //selected('dark', isset($bbcs_settings['last_rule']) ? $bbcs_settings['last_rule'] : ''); ?>><?php //esc_html_e('Check as dangerous', 'botblocker-security'); ?></option>
            			<option value="gray" <?php //selected('gray', isset($bbcs_settings['last_rule']) ? $bbcs_settings['last_rule'] : ''); ?>><?php //esc_html_e('Check as suspicious', 'botblocker-security'); ?></option>
        			</select>
    			</div>
			</div>
			-->

			<h3 class="bbcs_settings_h3"><?php esc_html_e('Administrators settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="autosave_admin_ip" class="bbcs_checkbox_input_input" value="1"
            			<?php checked(1, isset($bbcs_settings['autosave_admin_ip']) ? $bbcs_settings['autosave_admin_ip'] : 0); ?>>
        			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Automatic save administrator IPs', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" 
        			data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Automatically save administrator IP addresses to prevent accidental self-blocking during security configuration.', 'botblocker-security'); ?>">
    			</i>
			</div>
		</div>
	</div>
</div>
