<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="tab-pane container fade" id="cron">
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
				// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/cron.svg' ); ?>"
					alt="<?php esc_attr_e( 'Cron Jobs', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'BotBlocker uses cron jobs for log cleanup and data processing.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'If WP-Cron is unreliable, set up a system cron job using the commands below.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://developer.wordpress.org/plugins/cron/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'WP-Cron', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Cron" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Cron', 'botblocker-security' ); ?></a>
					<a href="https://docs.cpanel.net/cpanel/advanced/cron-jobs/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'cPanel Cron Jobs', 'botblocker-security' ); ?></a>
					<a href="https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/scheduling-tasks.64993/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Plesk Cron', 'botblocker-security' ); ?></a>
					<a href="https://www.ispmanager.com/docs/ispmanager/scheduler-cron" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'ISPmanager scheduler', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'WP Cron Jobs', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input disabled type="checkbox" name="wp_cron_enabled" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, BOTBLOCKER_WP_CRON_ENABLED ? 1 : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'WP Cron Enabled', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr__( 'WP-Cron status (read-only, managed automatically).', 'botblocker-security' ); ?>">
				</i>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'System Cron Job Commands', 'botblocker-security' ); ?></h3>
			<?php
			$bbcs_wp_cron_url = rtrim( site_url(), '/' ) . '/wp-cron.php?doing_wp_cron';

			$bbcs_curl_cmd = BOTBLOCKER_CRON_SCHEDULE . ' curl -s "' . $bbcs_wp_cron_url . '" > /dev/null 2>&1';
			$bbcs_wget_cmd = BOTBLOCKER_CRON_SCHEDULE . ' wget -q -O - "' . $bbcs_wp_cron_url . '" > /dev/null 2>&1';
			?>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'cURL Command:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Copy this command to your server crontab.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner bbcs-input-copy">
					<input type="text" 
					class="bbcs_text_input_input" 
					name="cron_curl" 
					id="cron-curl" 
					value="<?php echo esc_attr( $bbcs_curl_cmd ); ?>" readonly style="width:100%">
						<button type="button" class="bbcs_copy_button" onclick="copyToClipboard(this)" title="<?php esc_attr_e( 'Copy to clipboard', 'botblocker-security' ); ?>">
							<i class="fa-regular fa-copy"></i>
						</button>
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Wget Command:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Copy this command to your server crontab.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner bbcs-input-copy">
					<input type="text" 
					class="bbcs_text_input_input" 
					name="cron_wget" 
					id="cron-wget" 
					value="<?php echo esc_attr( $bbcs_wget_cmd ); ?>" readonly style="width:100%">
						<button type="button" class="bbcs_copy_button" onclick="copyToClipboard(this)" title="<?php esc_attr_e( 'Copy to clipboard', 'botblocker-security' ); ?>">
							<i class="fa-regular fa-copy"></i>
						</button>
				</div>
			</div>
		</div>
	</div>
</div>
