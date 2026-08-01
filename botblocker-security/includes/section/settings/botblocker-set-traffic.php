<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
  
<div class="tab-pane container fade" id="traffic"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/traffic.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Traffic and Referrer Settings', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Manage referrer analysis, UTM processing, and iframe restrictions to control traffic sources.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Use noindex and noarchive directives to prevent search engines from indexing blocked or UTM pages.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/UTM_parameters" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'UTM', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Query_string" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Query string', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Frame_(World_Wide_Web)" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'iFrame', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Traffic and Referrer Settings', 'botblocker-security' ); ?></h3>
			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="utm_referrer" value="1" <?php checked( 1, isset( $bbcs_settings['utm_referrer'] ) ? $bbcs_settings['utm_referrer'] : 1 ); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'UTM Referrer Processing', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Track traffic sources and filter suspicious referrers via UTM parameters.', 'botblocker-security' ); ?>">
				</i>
			</div>


			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="check_get_ref" value="1" <?php checked( 1, isset( $bbcs_settings['check_get_ref'] ) ? $bbcs_settings['check_get_ref'] : 1 ); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Check GET Parameters in Referrer', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
				data-bs-original-title="<?php esc_attr_e( 'Scan the URL referrer for specific GET parameters to detect unwanted or suspicious traffic sources.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="iframe_stop" value="1" <?php checked( 1, isset( $bbcs_settings['iframe_stop'] ) ? $bbcs_settings['iframe_stop'] : 0 ); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Block Cross-Origin Iframes', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block cross-origin iframes to prevent clickjacking.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Header Settings', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="noarchive" value="1" <?php checked( 1, isset( $bbcs_settings['noarchive'] ) ? $bbcs_settings['noarchive'] : 0 ); ?>>        			
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Add noarchive to Blocked Pages', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
				data-bs-original-title="<?php esc_attr_e( 'Prevent search engines from caching blocked pages.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="utm_noindex" value="1" <?php checked( 1, isset( $bbcs_settings['utm_noindex'] ) ? $bbcs_settings['utm_noindex'] : 0 ); ?>>        	
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Add noindex to UTM Pages', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
				data-bs-original-title="<?php esc_attr_e( 'Prevent search engines from indexing UTM pages to avoid duplicate content.', 'botblocker-security' ); ?>">
				</i>
			</div>
		</div>
	</div>
</div>
