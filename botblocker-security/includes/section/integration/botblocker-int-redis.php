<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="tab-pane fade" id="bbcs_redis">
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/redis.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Redis', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">				
					<p class="bbcs-info-text">
					<?php esc_html_e( 'Redis provides in-memory caching for security counters, visitor statistics, and threat detection data.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Configure Redis host, port, authentication, and key prefix isolation.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-redis-and-how-does-it-power-botblockers-fast-checks-in-wordpress/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'About Redis', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/redis-vs-memcached-for-botblocker-which-cache-is-better-for-wordpress-security/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Redis vs Memcached', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Redis Cache Integration', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input id="bbcs_integrations_switch_redis" type="checkbox" name="redis_enable" value="1" <?php checked( 1, isset( $bbcs_settings['redis_enable'] ) ? $bbcs_settings['redis_enable'] : 1 ); ?>
					<?php
					if ( ! BotBlockerPro::isActive() ) {
						echo 'disabled';}
					?>
					>
					<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'Enable Redis counters', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Cache security counters and visitor data in Redis instead of the database.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Redis Server Host:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Specify the Redis server hostname or IP address. Default is localhost (127.0.0.1) for local Redis installations.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="redis_host" value="<?php echo isset( $bbcs_settings['redis_host'] ) ? esc_html( $bbcs_settings['redis_host'] ) : ''; ?>">
				</div>
			</div>


			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Redis Key Prefix:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Define a unique prefix for all Redis keys to organize data and prevent conflicts with other applications using the same Redis instance.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="redis_prefix" value="<?php echo isset( $bbcs_settings['redis_prefix'] ) ? esc_html( $bbcs_settings['redis_prefix'] ) : ''; ?>">
				</div>
			</div>


			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Redis Authentication Password:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Enter Redis server authentication password if required. Leave empty if Redis server does not require authentication.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="redis_password" value="<?php echo isset( $bbcs_settings['redis_password'] ) ? esc_html( $bbcs_settings['redis_password'] ) : ''; ?>">
				</div>
			</div>


			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Redis Server Port:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Enter the Redis server port number. Standard port is 6379 for most Redis installations.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_number_input_input" name="redis_port" value="<?php echo isset( $bbcs_settings['redis_port'] ) ? esc_html( $bbcs_settings['redis_port'] ) : 0; ?>">
				</div>
			</div>
		</div>
	</div>
</div>
