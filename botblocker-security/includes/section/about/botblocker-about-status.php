<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/status.svg'); ?>" 
					alt="<?php esc_attr_e('System status', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('Overview of your WordPress environment and BotBlocker core: plugin state, themes, plugins, and server parameters.', 'botblocker-security'); ?>
                </p>
				<p class="bbcs-info-text">
                    <?php esc_html_e('Identify configuration issues, check software versions, and verify your site runs smoothly.', 'botblocker-security'); ?>
                </p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/apache-web-server-and-its-use-with-wordpress-a-detailed-guide/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e('Apache', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/nginx-web-server-and-its-use-with-wordpress-a-detailed-guide/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e('Nginx', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/nginx-vs-apache-and-php-fpm-for-wordpress-concise-comparison/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e('Nginx vs Apache', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/server-operating-systems-types-features-and-their-role-in-web-hosting/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e('Server OS', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/php-and-its-modern-versions-why-it-matters-for-wordpress-and-botblocker/" target="_blank"
					class="bbcs-info-footer-a"><?php esc_html_e('PHP versions', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/servers-clouds-and-hosting-for-wordpress-operating-systems-requirements-and-key-choices/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e('Hosting and system requirements', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div class="col-xxl-9 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('System status', 'botblocker-security'); ?></h3>
				<?php echo do_shortcode('[bbcs_system_status]'); ?>
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Software versions', 'botblocker-security'); ?></h3>
				<?php echo do_shortcode('[bbcs_plugins_themes]'); ?>
			<?php if ( defined('BBCS_DEBUG') && BBCS_DEBUG ) : ?>
            <h3 class="bbcs_settings_h3"><?php esc_html_e('BotBlocker Hive Snapshot', 'botblocker-security'); ?></h3>
                <?php $BBCS->print_hive(); ?>
            <?php endif; ?>
        </div>
	</div>
