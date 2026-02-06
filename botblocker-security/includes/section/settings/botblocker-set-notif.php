<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="tab-pane container fade" id="notifications">
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/notification.svg'); ?>"
					alt="<?php esc_attr_e('Notification Settings', 'botblocker-security'); ?>"
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e('Notification settings allow you to customize how and when you receive alerts about bot activity on your site.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Each notification can be customized to include specific information about the bot activity, making them a powerful tool for site management. However, misconfigured notifications can lead to information overload or missed alerts, so it\'s important to monitor and manage them carefully.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://pusher.com/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Pusher', 'botblocker-security'); ?></a>
					<a href="https://t.me/BotFather" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Telegram BotFather', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Notification Types', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="email_notifications" class="bbcs_checkbox_input_input" <?php checked(1, isset($bbcs_settings['email_notifications']) ? $bbcs_settings['email_notifications'] : 0); ?> value="1">
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Email', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr__('Enable email notifications for security alerts and bot blocking events.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="telegram_notifications" class="bbcs_checkbox_input_input" <?php checked(1, isset($bbcs_settings['telegram_notifications']) ? $bbcs_settings['telegram_notifications'] : 0); ?> value="1" disabled>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Telegram', 'botblocker-security'); ?></span>
					<small class="text-muted bbcs-ps-5">
                		<?php esc_html_e('Coming soon', 'botblocker-security'); ?> (<a href="<?php echo esc_url($BBCSA->pages_addons); ?>"><?php esc_html_e('Addons', 'botblocker-security'); ?></a>)
            		</small>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr__('Enable Telegram notifications for security alerts and bot blocking events.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="pusher_notifications" class="bbcs_checkbox_input_input" <?php checked(1, isset($bbcs_settings['pusher_notifications']) ? $bbcs_settings['pusher_notifications'] : 0); ?> value="1" disabled>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Pusher', 'botblocker-security'); ?></span>
					<small class="text-muted bbcs-ps-5">
                		<?php esc_html_e('Coming soon', 'botblocker-security'); ?> (<a href="<?php echo esc_url($BBCSA->pages_addons); ?>"><?php esc_html_e('Addons', 'botblocker-security'); ?></a>)
            		</small>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr__('Enable Pusher notifications for real-time security alerts and bot blocking events.', 'botblocker-security'); ?>">
				</i>
			</div>

		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Notification Settings', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="critical_load_notifications" class="bbcs_checkbox_input_input" <?php checked(1, isset($bbcs_settings['critical_load_notifications']) ? $bbcs_settings['critical_load_notifications'] : 0); ?> value="1">
					<span class="bbcs_label_input_checkbox"><?php esc_html_e('Send notification when critical load', 'botblocker-security'); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr__('Send notifications when server load reaches critical levels or unusual bot activity is detected.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div class="bbcs_select_input mb-2 mt-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e('Send regular notifications', 'botblocker-security'); ?></span>
					<i class="fa-regular fa-circle-question"
						data-bs-toggle="tooltip" data-bs-html="true"
						data-bs-placement="top"
						data-bs-original-title="<?php echo esc_attr__('Choose how often to receive regular status reports and summaries.', 'botblocker-security'); ?>">
					</i>
				</div>
				<div class="bbcs_select_input_inner">
					<select class="bbcs_select_input_input" name="regular_notifications_frequency">
						<option value="disabled" <?php selected('disabled', $bbcs_settings['regular_notifications_frequency']); ?>><?php esc_html_e('Disabled', 'botblocker-security'); ?></option>
						<option value="daily" <?php selected('daily', $bbcs_settings['regular_notifications_frequency']); ?>><?php esc_html_e('Every day', 'botblocker-security'); ?></option>
						<option value="twice_week" <?php selected('twice_week', $bbcs_settings['regular_notifications_frequency']); ?>><?php esc_html_e('Twice a week', 'botblocker-security'); ?></option>
						<option value="monthly" <?php selected('monthly', $bbcs_settings['regular_notifications_frequency']); ?>><?php esc_html_e('Once a month', 'botblocker-security'); ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>
</div>
