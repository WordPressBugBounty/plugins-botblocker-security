<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="tab-pane container fade" id="tools-maintenance"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/database.svg'); ?>" 
					alt="<?php esc_attr_e('Maintenance', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('The BotBlocker maintenance section provides tools to manage the plugin’s database, temporary files, logs, and other service data. These features help you keep your WordPress site clean, organized, and running efficiently.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('You can easily clear outdated logs, remove unnecessary temporary files, and optimize the BotBlocker database. Regular maintenance ensures stable plugin operation and helps prevent potential issues related to data storage.', 'botblocker-security'); ?>
                </p>
				
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/tools/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Tools', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Database', 'botblocker-security'); ?></h3>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-reinstall-database" class="mb-1 btn btn-xs btn-danger">
					<i class="fas fa-sync"></i>
					<?php esc_html_e('Re-install Database', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_html_e('Clear all tables of BotBlocker and install initial settings', 'botblocker-security'); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-db-repair-info" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-screwdriver-wrench"></i>
					<?php esc_html_e('Database repair and optimization', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Access to the built-in WordPress database repair and optimization feature', 'botblocker-security'); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-hits-database" class="mb-1 btn btn-xs btn-default">
					<i class="fa-regular fa-trash-can"></i>
					<?php esc_html_e('Clear all visitors data', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_html_e('Clear all visitors and statistics from DB', 'botblocker-security'); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-transients" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-broom"></i>
					<?php esc_html_e('Cleanup of transients', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Cleanup of temporary data in the database accumulated during WordPress operation', 'botblocker-security'); ?>"></i>
			</div>
			
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Features', 'botblocker-security'); ?></h3>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-cookies" class="mb-1 btn btn-xs btn-default">
					<i class="fa-regular fa-trash-can"></i>
					<?php esc_html_e('Clear visitor cookies', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Resets all visitor cookies — all users will be required to go through verification again', 'botblocker-security'); ?>"></i>
			</div>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-flush-rewrite-rules" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-link"></i>
					<?php esc_html_e('Reset URL rewrite rules', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Helps resolve 404 errors after changing the permalink structure', 'botblocker-security'); ?>"></i>
			</div>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-flush-object-cache" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-memory"></i>
					<?php esc_html_e('Object cache cleanup', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Resets the internal WordPress cache and external caching systems (Redis, Memcached)', 'botblocker-security'); ?>"></i>
			</div>			
        </div>
		
	</div>
</div>
