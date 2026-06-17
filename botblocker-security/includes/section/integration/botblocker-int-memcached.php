<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="tab-pane fade" id="bbcs_memcached">
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/memcached.svg' ); ?>"
					alt="<?php esc_attr_e( 'Memcached', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">				
					<p class="bbcs-info-text">
					<?php esc_html_e( 'Memcached provides distributed memory caching for security data and visitor analytics, reducing database queries on busy sites.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Configure Memcached server details and cache key prefixes for proper data organization.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-memcached-and-how-does-it-help-botblocker-cache-resource-intensive-checks-in-wordpress/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'About memcached', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/redis-vs-memcached-for-botblocker-which-cache-is-better-for-wordpress-security/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Redis vs Memcached', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Memcached Cache Integration', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input id="bbcs_integrations_switch_memcached" type="checkbox" name="memcached_enable" value="1"                                                                                                                     
					<?php checked( 1, isset( $bbcs_settings['memcached_enable'] ) ? $bbcs_settings['memcached_enable'] : 1 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable Memcached counters', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Cache security counters and visitor data in Memcached instead of the database.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Memcached Host Address:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Specify the Memcached server hostname or IP address. Default is localhost (127.0.0.1) for local installations.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="memcached_host" value="<?php echo isset( $bbcs_settings['memcached_host'] ) ? esc_html( $bbcs_settings['memcached_host'] ) : '127.0.0.1'; ?>">
				</div>
			</div>


			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Memcached Port Number:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Enter the Memcached server port number. Standard port is 11211 for most Memcached installations.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="number" class="bbcs_number_input_input" name="memcached_port" value="<?php echo isset( $bbcs_settings['memcached_port'] ) ? esc_html( $bbcs_settings['memcached_port'] ) : 11211; ?>">
				</div>
			</div>



			<div class="bbcs_text_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs-label-input"><?php esc_html_e( 'Cache Key Prefix:', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Define a unique prefix for all Memcached keys to avoid conflicts with other applications using the same cache server.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="memcached_prefix" value="<?php echo isset( $bbcs_settings['memcached_prefix'] ) ? esc_html( $bbcs_settings['memcached_prefix'] ) : esc_html( BOTBLOCKER_PREFIX ); ?>">
				</div>
			</div>
		</div>
	</div>
</div>
