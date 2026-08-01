<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="tab-pane container fade" id="general"> 
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/general.svg' ); ?>" 
					alt="<?php esc_attr_e( 'General settings', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Control how BotBlocker verifies visitors, applies security rules, and manages hit limits.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'PTR cache improves DNS lookup speed. Auto-save admin IPs prevents accidental lockout.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/HTTP_cookie" target="_blank"
						class="bbcs-info-footer-a"><?php esc_html_e( 'Cookies', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/ptr-record-checks-detecting-fake-bots-with-reverse-dns-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'PTR', 'botblocker-security' ); ?></a>
					<!--<a href="<?php //echo esc_url(BOTBLOCKER_DOCS_URL) ?>/administrator-IPs/" target="_blank" class="bbcs-info-footer-a"><?php //esc_html_e('Administrator IPs', 'botblocker-security'); ?></a>-->
					<!--<a href="<?php //echo esc_url(BOTBLOCKER_DOCS_URL) ?>/General-settings/" target="_blank" class="bbcs-info-footer-a"><?php //esc_html_e('General settings', 'botblocker-security'); ?></a>-->
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'General', 'botblocker-security' ); ?></h3>
			
			<div class="bbcs_radio_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Security Check Mode', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question"
						data-bs-toggle="tooltip" data-bs-html="true"
						data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Full Mode: Inspect all requests (frontend, admin, API, AJAX, cron). Frontend Mode: Inspect public-facing requests only.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<label class="bbcs-radio-inline">
						<input type="radio" name="secure_mode" value="2" <?php checked( 2, isset( $bbcs_settings['secure_mode'] ) ? $bbcs_settings['secure_mode'] : 2 ); ?> />
						<?php esc_html_e( 'Full Mode (Check all requests)', 'botblocker-security' ); ?>
					</label>
				</div>
				<div class="bbcs_text_input_inner">
					<label class="bbcs-radio-inline">
						<input type="radio" name="secure_mode" value="1" <?php checked( 1, isset( $bbcs_settings['secure_mode'] ) ? $bbcs_settings['secure_mode'] : 2 ); ?> />
						<?php esc_html_e( 'Frontend Mode (Check frontend only)', 'botblocker-security' ); ?>
					</label>
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'Hits Per User', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Requests allowed per verified visitor before re-verification.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="hits_per_user" value="<?php echo isset( $bbcs_settings['hits_per_user'] ) ? esc_html( $bbcs_settings['hits_per_user'] ) : 500; ?>">
				</div>
			</div>
			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'PTR Cache Lifetime', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question"
			data-bs-toggle="tooltip" data-bs-html="true"
			data-bs-placement="top"
			data-bs-original-title="<?php esc_attr_e( 'How long to cache DNS lookup results.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="ptrcache_time">
						<?php foreach ( bbcs_get_ptr_lifetimes() as $bbcs_seconds => $bbcs_label ) : ?>
				<option value="<?php echo esc_attr( $bbcs_seconds ); ?>" <?php selected( $bbcs_seconds, isset( $bbcs_settings['ptrcache_time'] ) ? $bbcs_settings['ptrcache_time'] : 86400 ); ?>>
							<?php echo esc_html( $bbcs_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
				</div>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'PTR Rule Subnet Mask', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question"
			data-bs-toggle="tooltip" data-bs-html="true"
			data-bs-placement="top"
			data-bs-original-title="<?php esc_attr_e( 'Subnet size for verified bot allow-rules (IPv4/IPv6). Smaller = more secure, larger = fewer DNS lookups.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="ptrcache_subnet">
						<?php foreach ( bbcs_get_subnet_mask_options() as $bbcs_val => $bbcs_label ) : ?>
				<option value="<?php echo esc_attr( $bbcs_val ); ?>" <?php selected( $bbcs_val, isset( $bbcs_settings['ptrcache_subnet'] ) ? $bbcs_settings['ptrcache_subnet'] : '24-64' ); ?>>
							<?php echo esc_html( $bbcs_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
				</div>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'PTR Rule Lifetime', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question"
			data-bs-toggle="tooltip" data-bs-html="true"
			data-bs-placement="top"
			data-bs-original-title="<?php esc_attr_e( 'How long to keep allow-rules for verified bots before they expire.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="ptrcache_rule_ttl">
						<?php foreach ( bbcs_get_ptrcache_rule_ttl_options() as $bbcs_days => $bbcs_label ) : ?>
				<option value="<?php echo esc_attr( $bbcs_days ); ?>" <?php selected( $bbcs_days, isset( $bbcs_settings['ptrcache_rule_ttl'] ) ? (int) $bbcs_settings['ptrcache_rule_ttl'] : 90 ); ?>>
							<?php echo esc_html( $bbcs_label ); ?>
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

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Administrator Settings', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="autosave_admin_ip" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['autosave_admin_ip'] ) ? $bbcs_settings['autosave_admin_ip'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Auto-save administrator IPs', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Automatically save admin IPs to prevent lockout when changing settings.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="skip_logged_in_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['skip_logged_in_users'] ) ? (int) $bbcs_settings['skip_logged_in_users'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Skip checks for all logged-in users', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'When enabled, all authenticated WordPress users (including subscribers, authors, contributors) bypass BotBlocker checks. By default, only administrators, editors and moderators are bypassed.', 'botblocker-security' ); ?>">
				</i>
			</div>
		</div>
	</div>
</div>
