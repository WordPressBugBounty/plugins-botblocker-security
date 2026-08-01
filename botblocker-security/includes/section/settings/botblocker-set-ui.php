<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
 
<div class="tab-pane container fade" id="settings-ui"> 	
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/ui.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Interface Settings', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Control admin interface caching and statistics display settings.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Configure report period, timezone, and how statistics are counted.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i> 
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/log-retention-in-botblocker-how-to-manage-storage-period-time-zone-and-analytics/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Log retention', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Time_zone" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'GMT and Time Zone', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/interface-caching-in-botblocker-configurable-cache-time-real-time-mode-and-wordpress-transients/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Cache UI', 'botblocker-security' ); ?></a>
					<!--<a href="<?php //echo esc_url(BOTBLOCKER_DOCS_URL) ?>/Unique-types/" target="_blank" class="bbcs-info-footer-a"><?php //esc_html_e('Unique types', 'botblocker-security'); ?></a>-->
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Interface Caching', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="cache_ui_data" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['cache_ui_data'] ) ? $bbcs_settings['cache_ui_data'] : 0 ); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Cache Plugin Interface', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Cache the admin interface for faster loading.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_select_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Cache Duration', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question"
						data-bs-toggle="tooltip" data-bs-html="true" 
						data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'How long to cache the interface (in seconds).', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="cache_ui_duration">
						<?php foreach ( bbcs_get_cache_durations() as $bbcs_duration => $bbcs_label ) : ?>
							<option value="<?php echo esc_attr( $bbcs_duration ); ?>" 
								<?php selected( $bbcs_duration, isset( $bbcs_settings['cache_ui_duration'] ) ? $bbcs_settings['cache_ui_duration'] : 300 ); ?>>
								<?php echo esc_html( $bbcs_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Reports and Statistics', 'botblocker-security' ); ?></h3>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'Report Period:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Days to include in reports.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="admin_report_period">
						<?php
						$bbcs_periods = array( 3, 5, 7, 10, 14, 30 );
						foreach ( $bbcs_periods as $bbcs_days ) {
							$bbcs_selected = ( isset( $bbcs_settings['admin_report_period'] ) && $bbcs_settings['admin_report_period'] == $bbcs_days ) ? 'selected' : '';
							echo '<option value="' . esc_attr( (string) $bbcs_days ) . '" ' . esc_html( $bbcs_selected ) . '>' . esc_html( (string) $bbcs_days ) . ' ' . esc_html__( 'days', 'botblocker-security' ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e( 'GMT Offset for Reports:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true"  
					data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Timezone for report timestamps.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="admin_gmt_offset">
						<?php
						$bbcs_gmt_offsets = array( -12, -11, -10, -9.5, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 8, 8.75, 9, 9.5, 10, 10.5, 11, 12, 13, 14 );


						foreach ( $bbcs_gmt_offsets as $bbcs_offset ) {
							$bbcs_selected = ( isset( $bbcs_settings['admin_gmt_offset'] ) && $bbcs_settings['admin_gmt_offset'] == $bbcs_offset ) ? 'selected' : '';
							$bbcs_label    = ( $bbcs_offset == 0 ) ? 'GMT' : ( ( $bbcs_offset > 0 ) ? "GMT+$bbcs_offset" : "GMT$bbcs_offset" );
							echo '<option value="' . esc_attr( (string) $bbcs_offset ) . '" ' . esc_html( $bbcs_selected ) . '>' . esc_html( $bbcs_label ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Statistics Display Mode', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e( 'Show unique visitors (by IP) or all requests.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="admin_uniq_type">
						<?php
						$bbcs_uniq_types = array(
							'host' => __( 'Unique Visitors (by IP)', 'botblocker-security' ),
							'hit'  => __( 'All hits', 'botblocker-security' ),
						);
						foreach ( $bbcs_uniq_types as $bbcs_value => $bbcs_label ) {
							$bbcs_selected = ( isset( $bbcs_settings['admin_uniq_type'] ) && $bbcs_settings['admin_uniq_type'] == $bbcs_value ) ? 'selected' : '';
							echo '<option value="' . esc_attr( $bbcs_value ) . '" ' . esc_html( $bbcs_selected ) . '>' . esc_html( $bbcs_label ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>

		</div>
	</div>
</div>
