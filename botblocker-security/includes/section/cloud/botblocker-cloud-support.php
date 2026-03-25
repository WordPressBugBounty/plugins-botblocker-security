<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane fade" id="cloud-support">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/chat.svg'); ?>"
					alt="<?php esc_attr_e('BotBlocker Support', 'botblocker-security'); ?>"
					class="img-fluid bbcs-info-image mb-3">				
					
				<p class="bbcs-info-text">
					<?php esc_html_e('Our support team can help with BotBlocker installation and configuration.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Our developers can also help with PHP development and WordPress integration issues.', 'botblocker-security'); ?>
				</p>

				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/botblocker-api/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('About BotBlocker API', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">

            <h3 class="bbcs_settings_h3"><?php esc_html_e('BotBlocker API Integration', 'botblocker-security'); ?></h3>

        </div>
    </div>
</div>
