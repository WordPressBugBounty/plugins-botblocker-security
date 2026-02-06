<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
 
<div class="tab-pane container fade" id="data_log_and_processing"> 	
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/data-log-processing.svg'); ?>" 
					alt="<?php esc_attr_e('Simple Bot Detection', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('Data logging and processing settings define what BotBlocker records about each visit to detect automated threats. Enable collection of browser, operating system, and device type to improve detection accuracy and diagnostics.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Use Store Period to control how long raw logs are kept. If your site observes daylight saving time, enable automatic time adjustment for accurate timestamps.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i> 
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/how-botblocker-detects-browser-version-os-and-device-type-pc-mobile-or-tablet/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('How BotBlocker Detects Browser Version, OS, and Device Type', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/log-retention-in-botblocker-how-to-manage-storage-period-time-zone-and-analytics/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Store logs', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Data Log & Processing', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="get_browser_type" class="bbcs_checkbox_input_input" value="1"
            			<?php checked(1, isset($bbcs_settings['get_browser_type']) ? $bbcs_settings['get_browser_type'] : 0); ?>>        		
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('Get Browser Type', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question"
        		data-bs-toggle="tooltip" data-bs-html="true" 
        		data-bs-placement="top"
        		data-bs-original-title="<?php esc_attr_e('Collects visitor\'s browser type for analytics and troubleshooting.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="get_os_type" class="bbcs_checkbox_input_input" value="1"
            			<?php checked(1, isset($bbcs_settings['get_os_type']) ? $bbcs_settings['get_os_type'] : 0); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('Get OS Type', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" 
        			data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Collects visitor\'s operating system for analytics and troubleshooting.', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="get_device_type" class="bbcs_checkbox_input_input" value="1"
            			<?php checked(1, isset($bbcs_settings['get_device_type']) ? $bbcs_settings['get_device_type'] : 0); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e('Get Device Type', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" 
        			data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('Collects visitor\'s device type (mobile/desktop/tablet).', 'botblocker-security'); ?>">
    			</i>
			</div>

			<div class="bbcs_text_input mb-2">
    			<div class="bbcs_label_input_box">        			
					<span class="bbcs-label-input"><?php esc_html_e('Store Period:', 'botblocker-security'); ?></span>
        			<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"  data-bs-placement="top" 
					data-bs-original-title="<?php esc_attr_e('Duration to store raw data before purging.', 'botblocker-security'); ?>"></i>
    			</div>
    			<div class="bbcs_text_input_inner">
        			<select class="bbcs_select_input_input" name="admin_store_period">
            			<?php
            			$bbcs_periods = array(3, 5, 7, 10, 14, 30);
            			foreach ($bbcs_periods as $bbcs_days) {
                			$bbcs_selected = (isset($bbcs_settings['admin_store_period']) && $bbcs_settings['admin_store_period'] == $bbcs_days) ? 'selected' : '';
							echo '<option value="' . esc_attr($bbcs_days) . '" ' . esc_html($bbcs_selected) . '>' . esc_html($bbcs_days) . ' ' . esc_html__('days', 'botblocker-security') . '</option>';
            			}
            			?>
        			</select>
    			</div>
			</div>

			<div class="bbcs_checkbox_input mb-2">
    			<div class="bbcs_label_checkbox_box">
        			<input type="checkbox" name="daylight_saving_time" class="bbcs_checkbox_input_input" value="1"
            			<?php checked(1, isset($bbcs_settings['daylight_saving_time']) ? $bbcs_settings['daylight_saving_time'] : 0); ?>>
        			<span class="bbcs_label_input_checkbox"><?php esc_html_e('Daylight Saving Time', 'botblocker-security'); ?></span>
    			</div>
    			<i class="fa-regular fa-circle-question"
        			data-bs-toggle="tooltip" data-bs-html="true" 
        			data-bs-placement="top"
        			data-bs-original-title="<?php esc_attr_e('During daylight saving time, the time is adjusted by one hour. If this option is enabled, the plugin will automatically adjust the time based on the current date.', 'botblocker-security'); ?>">
    			</i>
			</div>

		
		</div>
	</div>
</div>
