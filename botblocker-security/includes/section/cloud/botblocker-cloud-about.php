<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane fade" id="cloud-about">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/info.svg'); ?>"
					alt="<?php esc_attr_e('GLOBUS.studio / BotBlocker API', 'botblocker-security'); ?>"
					class="img-fluid bbcs-info-image mb-3">				
					
				<p class="bbcs-info-text">
					<?php esc_html_e('BotBlocker is a comprehensive security plugin designed to protect your WordPress site from bots, spam, and malicious activity. With advanced filtering and blocking technologies, it helps keep your website safe and your data secure.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('By using BotBlocker, you can effectively reduce unwanted traffic, prevent automated attacks, and ensure that only real visitors have access to your content. The plugin is easy to configure and works seamlessly with any WordPress site.', 'botblocker-security'); ?>
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
