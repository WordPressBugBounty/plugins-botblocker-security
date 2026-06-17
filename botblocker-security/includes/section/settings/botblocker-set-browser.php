<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$bbcs_has_pro = BotBlockerPro::isActive();
?>

<div class="tab-pane container fade" id="browser_plugins"> 
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/browser-plugins.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Browser Detection', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Browser detection identifies bots using spoofing, anti-detect tools, and privacy modes. Advanced options verify browser engine consistency.', 'botblocker-security' ); ?>				
				</p>
				<!--
				<ul class="bbcs-info-list">
					<li class="bbcs-info-item">Consectetur</li>
				</ul>	
				-->
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-adblock-ublock-detection-why-botblocker-identifies-and-blocks-ad-blockers/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'AdBlockers', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-are-antidetect-browsers-how-botblocker-identifies-stealth-browsers-and-fake-fingerprints/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Antidetect browsers', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-incognito-mode-why-botblocker-detects-it-and-why-detection-isnt-always-possible/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Incognito mode', 'botblocker-security' ); ?></a>
				</div>

			</div>
		</div>
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Browser Modes', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_incognito_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_incognito_users'] ) ? $bbcs_settings['block_incognito_users'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Incognito / Private', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block visitors using private/incognito browsing mode.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Browser Plugins', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_adblocker_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_adblocker_users'] ) ? $bbcs_settings['block_adblocker_users'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'AdBlock / uBlock', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Detect active ad blockers. May affect legitimate users with ad-blocking extensions.', 'botblocker-security' ); ?>">
				</i>
			</div>

		</div>		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Browser Options', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_simple_antidetect" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_simple_antidetect'] ) ? $bbcs_settings['block_simple_antidetect'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Simple JS Consistency', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Verify browser API behavior (canvas, WebGL).', 'botblocker-security' ); ?>">
				</i>

			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_override" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_override'] ) ? $bbcs_settings['block_override'] : 0 ); ?>
						<?php
						if ( ! $bbcs_has_pro ) {
							echo 'disabled';}
						?>
						>
					<span class="bbcs-cloud-api-column">    
					<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'Override Detection', 'botblocker-security' ); ?></span>
					<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'PRO option', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>)
						</small>
					</span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Detect tampered browser properties (navigator, plugins).', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_web_engine_options" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_web_engine_options'] ) ? $bbcs_settings['block_web_engine_options'] : 0 ); ?>
						<?php
						if ( ! $bbcs_has_pro ) {
							echo 'disabled';}
						?>
						>
					<span class="bbcs-cloud-api-column">    
					<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'Engine Parameter Checks', 'botblocker-security' ); ?></span>
					<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'PRO option', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>)
						</small>
					</span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Verify browser engine parameters against known browser signatures.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_device_options" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_device_options'] ) ? $bbcs_settings['block_device_options'] : 0 ); ?>
						<?php
						if ( ! $bbcs_has_pro ) {
							echo 'disabled';}
						?>
						>
						<span class="bbcs-cloud-api-column">    
					<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'Device API Verification', 'botblocker-security' ); ?></span>
					<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
					<?php esc_html_e( 'PRO option', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>)
						</small>
					</span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Verify device APIs (touch, motion sensors) to detect headless browsers.', 'botblocker-security' ); ?>">
				</i>
			</div>
		</div>
	</div>
</div>
