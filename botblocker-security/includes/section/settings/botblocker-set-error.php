<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>  
<div class="tab-pane container fade" id ="error"> 
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/error-access.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Error and Access Settings', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Configure HTTP response codes for blocked visitors, ban durations, and search engine directives.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Repeated violations result in progressively longer bans. X-Robots-Tag directives prevent SEO penalties on blocked pages.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/HTTP" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'HTTP', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-http-understanding-protocol-versions-and-blocking-http-1-0-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Old HTTP versions', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'HTTP Codes', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-a-proxy-types-of-proxies-and-how-botblocker-detects-them/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'HTTP proxy', 'botblocker-security' ); ?></a>
					<!--<a href="<?php //echo esc_url(BOTBLOCKER_DOCS_URL) ?>/Ban-time/" target="_blank" class="bbcs-info-footer-a"><?php //esc_html_e('Ban time', 'botblocker-security'); ?></a>-->
					<a href="https://en.wikipedia.org/wiki/List_of_HTTP_header_fields/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Headers', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Error and Access Settings', 'botblocker-security' ); ?></h3>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'Test Response Code:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'HTTP response code returned for verification test requests.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="header_test_code">
						<?php
						foreach ( $BBCS->error_headers as $bbcs_code => $bbcs_description ) {
							$bbcs_selected = ( isset( $bbcs_settings['header_test_code'] ) && $bbcs_settings['header_test_code'] == $bbcs_code ) ? 'selected' : '';
							echo '<option value="' . esc_attr( $bbcs_code ) . '" ' . esc_html( $bbcs_selected ) . '>' . esc_html( $bbcs_description ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'Block Response Code:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'HTTP response code returned for blocked requests.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="header_error_code">
						<?php
						foreach ( $BBCS->error_headers as $bbcs_code => $bbcs_description ) {
							$bbcs_selected = ( isset( $bbcs_settings['header_error_code'] ) && $bbcs_settings['header_error_code'] == $bbcs_code ) ? 'selected' : '';
							echo '<option value="' . esc_attr( $bbcs_code ) . '" ' . esc_html( $bbcs_selected ) . '>' . esc_html( $bbcs_description ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'Block Time (seconds):', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'How long to block a visitor before allowing a retry.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" 
					class="bbcs_text_input_input" 
					name="time_ban" 
					value="<?php echo isset( $bbcs_settings['time_ban'] ) ? esc_html( $bbcs_settings['time_ban'] ) : 200; ?>">
				</div>
			</div>
			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'Repeat Block Time (seconds):', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Longer ban applied after repeated failures.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" 
					class="bbcs_text_input_input" 
					name="time_ban_2" 
					value="<?php echo isset( $bbcs_settings['time_ban_2'] ) ? esc_html( $bbcs_settings['time_ban_2'] ) : 400; ?>">
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Headers for Search Engines', 'botblocker-security' ); ?></h3>

			<?php
			$bbcs_available_directives = BotBlockerData::getXRobotTags();

			$bbcs_selected_directives = isset( $bbcs_settings['x_robots_directives'] ) ?
				( is_array( $bbcs_settings['x_robots_directives'] ) ? $bbcs_settings['x_robots_directives'] :
				json_decode( $bbcs_settings['x_robots_directives'], true ) ) : array();

			if ( ! is_array( $bbcs_selected_directives ) ) {
				$bbcs_selected_directives = array();
			}

			foreach ( $bbcs_available_directives as $bbcs_directive => $bbcs_default_value ) {
				$bbcs_checked           = in_array( $bbcs_directive, $bbcs_selected_directives ) ? 1 : 0;
				$bbcs_directive_display = ! empty( $bbcs_default_value ) ? $bbcs_directive . ':' . $bbcs_default_value : $bbcs_directive;
				?>
			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="x_robots_directives[]" class="bbcs_checkbox_input_input" value="<?php echo esc_html( $bbcs_directive ); ?>"
						<?php checked( 1, $bbcs_checked ); ?>>
					<span class="bbcs_label_input_checkbox"><?php echo esc_html( $bbcs_directive_display ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					<?php /* translators: %s: X-Robots-Tag directive name */ ?>
					data-bs-original-title="<?php printf( esc_html__( 'Enable %s directive in X-Robots-Tag headers', 'botblocker-security' ), esc_html( $bbcs_directive_display ) ); ?>">
				</i>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
