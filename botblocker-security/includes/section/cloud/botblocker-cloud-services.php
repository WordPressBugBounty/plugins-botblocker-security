<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane fade" id="cloud-services">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/networking.svg'); ?>"
					alt="<?php esc_attr_e('Outsourcing', 'botblocker-security'); ?>"
					class="img-fluid bbcs-info-image mb-3">				
					
				<p class="bbcs-info-text">
					<?php esc_html_e('Our team is ready to assist you with any WordPress development needs, including optimization, plugin and theme customization, and system administration. We offer professional support to help your website run smoothly and efficiently.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('We also provide custom PHP development and automation services tailored to your specific requirements. Whether you need unique features, integration with third-party services, or advanced automation, our experts are here to deliver effective solutions.', 'botblocker-security'); ?>
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
