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
                    <?php esc_html_e('Manage the plugin database, temporary files, logs, and service data to keep your site running efficiently.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('Clear outdated logs, remove temporary files, and optimize the database. Regular maintenance prevents storage-related issues.', 'botblocker-security'); ?>
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
					<?php esc_html_e('Reinstall Database', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_html_e('Reset all BotBlocker tables to default settings', 'botblocker-security'); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-db-repair-info" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-screwdriver-wrench"></i>
					<?php esc_html_e('Repair and Optimize Database', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Open WordPress database repair and optimization tool', 'botblocker-security'); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-hits-database" class="mb-1 btn btn-xs btn-default">
					<i class="fa-regular fa-trash-can"></i>
					<?php esc_html_e('Clear All Visitor Data', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_html_e('Delete all visitor records and statistics', 'botblocker-security'); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-transients" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-broom"></i>
					<?php esc_html_e('Clear transients', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Clear expired transients from the database', 'botblocker-security'); ?>"></i>
			</div>
			
			<h3 class="bbcs_settings_h3"><?php esc_html_e('Features', 'botblocker-security'); ?></h3>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-cookies" class="mb-1 btn btn-xs btn-default">
					<i class="fa-regular fa-trash-can"></i>
					<?php esc_html_e('Clear visitor cookies', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Resets all visitor cookies - visitors must re-verify', 'botblocker-security'); ?>"></i>
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
					<?php esc_html_e('Clear Object Cache', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e('Resets the internal WordPress cache and external caching systems (Redis, Memcached)', 'botblocker-security'); ?>"></i>
			</div>			
        </div>
		
	</div>
</div>
