<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
  
<div class="tab-pane container fade" id="rate_limiting"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/rate-limiting.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Rate Limiting', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Control request velocity per IP with configurable thresholds, sliding windows, and subnet aggregation.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Rate limiting protects against brute force, DDoS, and distributed proxy attacks by dynamically adjusting thresholds based on subnet pressure.', 'botblocker-security' ); ?>
				</p>
				<?php
				$bbcs_has_pro = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
				if ( ! $bbcs_has_pro ) {
					?>
					<p class="bbcs-info-text mt-3" style="font-size:0.82rem; border-left: 2px solid #ffc107; padding-left: 8px; margin-top: 15px;">
						<strong><?php esc_html_e( 'PRO Feature:', 'botblocker-security' ); ?></strong>
						<?php esc_html_e( 'Upgrade to Pro for the Behavioral Engine. It features advanced multi-signal scoring and decay-based reputation, preventing bots from spamming your site by tightening thresholds for repeat offenders.', 'botblocker-security' ); ?>
						<br>
						<a href="<?php echo esc_url( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) . '&focus=bbcs-behavior' ); ?>" class="btn btn-outline-warning btn-sm mt-2" style="font-size: 0.75rem; padding: 2px 8px; margin-top: 8px; display: inline-block;"><?php esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); ?></a>
					</p>
					<?php
				} else {
					$bbcs_behavior_active = class_exists( 'BotBlockerAddons' ) && BotBlockerAddons::hasActiveFeature( 'behavioral_analysis_engine' );
					if ( $bbcs_behavior_active ) {
						?>
						<p class="bbcs-info-text mt-3" style="font-size:0.82rem; border-left: 2px solid #20c997; padding-left: 8px; margin-top: 15px;">
							<strong><?php esc_html_e( 'Pro Feature Active:', 'botblocker-security' ); ?></strong>
							<?php esc_html_e( 'The Behavioral Engine is installed and active. You can customize the multi-signal thresholds, session limits, and IP reputation decay.', 'botblocker-security' ); ?>
							<br>
							<a href="<?php echo esc_url( BotBlockerMultisite::getAdminPageUrl( 'bbcs_tools' ) . '#addon-bbcs-behavior' ); ?>" class="btn btn-outline-success btn-sm mt-2" style="font-size: 0.75rem; padding: 2px 8px; margin-top: 8px; display: inline-block;"><?php esc_html_e( 'Configure Behavioral Engine', 'botblocker-security' ); ?></a>
						</p>
						<?php
					} else {
						?>
						<p class="bbcs-info-text mt-3" style="font-size:0.82rem; border-left: 2px solid #ffc107; padding-left: 8px; margin-top: 15px;">
							<strong><?php esc_html_e( 'Pro Feature Available:', 'botblocker-security' ); ?></strong>
							<?php esc_html_e( 'Activate the Behavioral Engine addon to get advanced multi-signal scoring and IP/subnet reputation protection.', 'botblocker-security' ); ?>
							<br>
							<a href="<?php echo esc_url( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) ); ?>" class="btn btn-outline-warning btn-sm mt-2" style="font-size: 0.75rem; padding: 2px 8px; margin-top: 8px; display: inline-block;"><?php esc_html_e( 'Go to Add-ons', 'botblocker-security' ); ?></a>
						</p>
						<?php
					}
				}
				?>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/Rate_limiting" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Rate limiting', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Subnet" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Subnet', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Rate Limiting', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="bbcs_rate_check_enabled" value="1" <?php checked( 1, isset( $bbcs_settings['bbcs_rate_check_enabled'] ) ? $bbcs_settings['bbcs_rate_check_enabled'] : 1 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable Rate Limiting', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Track request velocity per IP and issue Captcha or block when thresholds are exceeded.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Captcha Threshold', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Average requests per minute over the sliding window that trigger a Captcha challenge. Total hits in window / window minutes. Default: 30.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="bbcs_rate_captcha_rpm" min="1" step="1" value="<?php echo isset( $bbcs_settings['bbcs_rate_captcha_rpm'] ) ? esc_attr( $bbcs_settings['bbcs_rate_captcha_rpm'] ) : 30; ?>">
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Block Threshold', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Average requests per minute over the sliding window that trigger an immediate block. Total hits in window / window minutes. Default: 50.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="bbcs_rate_block_rpm" min="1" step="1" value="<?php echo isset( $bbcs_settings['bbcs_rate_block_rpm'] ) ? esc_attr( $bbcs_settings['bbcs_rate_block_rpm'] ) : 50; ?>">
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Block Time', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'How long the IP stays blocked after exceeding the rate limit. Default: 600 (10 minutes).', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="bbcs_rate_block_duration" min="60" max="86400" step="1" value="<?php echo isset( $bbcs_settings['bbcs_rate_block_duration'] ) ? esc_attr( $bbcs_settings['bbcs_rate_block_duration'] ) : 600; ?>">
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Window', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Sliding window size in minutes. RPM = total hits in window / window minutes. Default: 5.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="bbcs_rate_window_minutes" min="1" step="1" value="<?php echo isset( $bbcs_settings['bbcs_rate_window_minutes'] ) ? esc_attr( $bbcs_settings['bbcs_rate_window_minutes'] ) : 5; ?>">
				</div>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Subnet Aggregation', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="bbcs_rate_subnet_enabled" value="1" <?php checked( 1, isset( $bbcs_settings['bbcs_rate_subnet_enabled'] ) ? $bbcs_settings['bbcs_rate_subnet_enabled'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Subnet Aggregation', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Aggregate RPM across the subnet. High subnet pressure dynamically lowers per-IP thresholds to catch distributed proxy attacks.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Subnet Multiplier', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Multiplier applied to block RPM for subnet threshold. Higher = more tolerant. Default: 3.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="bbcs_rate_subnet_multiplier" min="1" step="0.1" value="<?php echo isset( $bbcs_settings['bbcs_rate_subnet_multiplier'] ) ? esc_attr( $bbcs_settings['bbcs_rate_subnet_multiplier'] ) : 3; ?>">
				</div>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Floor %', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Minimum threshold as a percentage of block RPM, preventing thresholds from dropping to zero under high subnet pressure. Default: 10%.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_text_input_input" name="bbcs_rate_floor_percent" min="1" max="100" step="1" value="<?php echo isset( $bbcs_settings['bbcs_rate_floor_percent'] ) ? esc_attr( (float) $bbcs_settings['bbcs_rate_floor_percent'] * 100 ) : 10; ?>">
				</div>
			</div>

			<div class="bbcs_number_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Subnet Mask', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'CIDR mask pair for subnet aggregation (v4_v6). Tighter masks target attackers more precisely. Default: 24-64.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<select class="bbcs_select_input_input" name="bbcs_rate_subnet_mask">
						<?php foreach ( bbcs_get_rate_subnet_mask_options() as $bbcs_val => $bbcs_label ) : ?>
							<option value="<?php echo esc_attr( $bbcs_val ); ?>" <?php selected( $bbcs_val, isset( $bbcs_settings['bbcs_rate_subnet_mask'] ) ? $bbcs_settings['bbcs_rate_subnet_mask'] : '24-64' ); ?>><?php echo esc_html( $bbcs_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

		</div>
	</div>
</div>
