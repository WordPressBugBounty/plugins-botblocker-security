<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane fade" id="bbcs_transients">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/transient.svg'); ?>" 
					alt="<?php esc_attr_e('Transient', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">				
                    <p class="bbcs-info-text">
					<?php esc_html_e('WordPress transients provide built-in temporary data storage for caching security requests and visitor information. This lightweight caching mechanism improves performance without requiring external cache servers.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Enable transient caching for cloud requests to reduce external API calls and database queries. This option is ideal for smaller websites or when dedicated cache servers are not available for enhanced performance optimization.', 'botblocker-security'); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/interface-caching-in-botblocker-configurable-cache-time-real-time-mode-and-wordpress-transients/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('About Wordpress transients in BotBlocker', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>        
        
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('WordPress Transients', 'botblocker-security'); ?></h3>

            <div class="bbcs_checkbox_input mb-2">                
                <div class="bbcs_label_checkbox_box">
                    <input  type="checkbox" name="use_transients_for_cloud" value="1" 
                    <?php checked(1, isset($bbcs_settings['use_transients_for_cloud']) ? $bbcs_settings['use_transients_for_cloud'] : 1); ?>>
                    <span class="bbcs_label_input_checkbox"><?php esc_html_e('Use transients for cache cloud requests', 'botblocker-security'); ?></span>
                </div>                
                <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" 
                data-bs-html="true" data-bs-placement="top" 
                data-bs-original-title="<?php esc_attr_e('Use transients for cache requests. This will reduce the amount of database queries and improve performance. This option is recommended for sites with high traffic. Transient storage may be redefined by other plugins.', 'botblocker-security'); ?>">
                </i>
            </div>                                    
        </div>
    </div>
</div>
