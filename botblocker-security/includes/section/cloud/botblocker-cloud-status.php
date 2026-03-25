<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$bbcs_is_cloud_api_active = bbcs_isCloudAPIActive();
$bbcs_cloud_api_key = $bbcs_is_cloud_api_active ? bbcs_getCloudAPIKey() : '';
$bbcs_cached_remaining_hits = bbcs_get_remaining_hits();
$bbcs_cached_remaining_days = bbcs_get_remaining_days();

?><div class="tab-pane fade show active" id="cloud-status">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/cloud-api.svg'); ?>"
					alt="<?php esc_attr_e('BotBlocker PRO status', 'botblocker-security'); ?>"
					class="img-fluid bbcs-info-image mb-3">				
				
				<p class="bbcs-info-text">
					<?php esc_html_e('Shows your Cloud API connection and subscription status. An active connection delivers live security updates, firewall rules, and premium bot-detection from the BotBlocker cloud.', 'botblocker-security'); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e('Compare plan features below. PRO unlocks full cloud checks per visit, advanced blocking rules, performance add-ons, and priority support.', 'botblocker-security'); ?>
				</p>

				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/botblocker-api/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('About BotBlocker API', 'botblocker-security'); ?></a>
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/cloud-based-visitor-verification-in-botblocker-pro/" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Cloud based verification', 'botblocker-security'); ?></a>
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/botblocker-free-vs-pro-which-version-to-choose/" target="_blank" 
                    class="bbcs-info-footer-a"><?php esc_html_e('Free vs PRO', 'botblocker-security'); ?></a>  
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/complete-list-of-botblocker-features/" target="_blank" 
                    class="bbcs-info-footer-a"><?php esc_html_e('BotBlocker Features', 'botblocker-security'); ?></a>  
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/how-botblocker-pros-cloud-verification-defeats-bots/" target="_blank" 
                    class="bbcs-info-footer-a"><?php esc_html_e('BotBlocker PRO', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<?php if ( ! $bbcs_is_cloud_api_active ) : ?>
			<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/img/promo/botblocker-pro.webp'); ?>"
					alt="<?php esc_attr_e('BotBlocker PRO status', 'botblocker-security'); ?>"
					class="img-fluid mb-3 mt-3">	
				<a href="https://botblocker.top/pricing/" class="btn btn-sm bbcs-btn-upgrade w-100" target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-star"></i>
                    <?php esc_html_e( 'Compare Plans', 'botblocker-security' ); ?>
                </a>	
			</div>
		<?php endif; ?>	

		<?php if ( ! $bbcs_is_cloud_api_active ) : ?>
			<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
				<h3 class="bbcs_settings_h3"><?php esc_html_e('PRO features', 'botblocker-security'); ?></h3>
				<?php $bbcs_addons_page = ($BBCSA && isset($BBCSA->pages_addons)) ? $BBCSA->pages_addons : bbcs_admin_page_url('bbcs_addons');?>
				<ul class="bbcs-info-list">
					<li class="bbcs-info-item"><?php esc_html_e('Early init - blocks before WP loads', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('WordPress Acceleration', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Hide Login URL and more', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('VPN and Tor Blocking', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('AI Behavioral analysis', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Zero-day botnet updates', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('5 Million+ bot signatures', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('All Premium Add-ons', 'botblocker-security'); ?> (<a href="<?php echo esc_url( $bbcs_addons_page ); ?>"><?php echo esc_html__('view', 'botblocker-security'); ?></a>)</li>
					<li class="bbcs-info-item"><?php esc_html_e('Priority Support', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Emergency help (24h)', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Attack log analysis and forensics', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Auto-update PTR and UA signatures', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Advanced reporting and analytics', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('Custom security rules engine', 'botblocker-security'); ?></li>
					<li class="bbcs-info-item"><?php esc_html_e('SEO bots whitelist management', 'botblocker-security'); ?></li>
				</ul>
			</div>
		<?php endif; ?>			

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('PRO Status', 'botblocker-security'); ?></h3>

			<div class="bbcs_checkbox_input mb-2 mt-2">
				<div class="bbcs_label_checkbox_box">
					<input disabled type="checkbox" name="bbcs_cloud_status" class="bbcs_checkbox_input_input" value="1"
						<?php checked(1, $bbcs_is_cloud_api_active ? 1 : 0); ?>>
					<span class="bbcs_label_input_checkbox">
						<?php echo $bbcs_is_cloud_api_active
								? esc_html__('PRO Status: Active', 'botblocker-security')
								: esc_html__('PRO Status: Inactive', 'botblocker-security');
						?>
					</span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" 
					data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr__('Shows whether PRO is active. Managed automatically.', 'botblocker-security'); ?>">
				</i>
			</div>

			<div id="bbcs_cloud_api_connect_box" class="mb-3">
				<div class="input-group mb-2">
					<input value="<?php echo esc_attr($bbcs_cloud_api_key); ?>" type="text" class="form-control" id="bbcs_cloud_api_key" name="bbcs_cloud_api_key" placeholder="<?php echo esc_attr__('Enter Cloud API key', 'botblocker-security'); ?>" autocomplete="off">
				<button type="button" id="bbcs_toggle_cloud_api_btn" class="btn <?php echo $bbcs_is_cloud_api_active ? 'btn-danger' : 'btn-primary'; ?>" data-is-active="<?php echo $bbcs_is_cloud_api_active ? '1' : '0'; ?>">
					<?php echo $bbcs_is_cloud_api_active ? esc_html__('Disconnect', 'botblocker-security') : esc_html__('Connect', 'botblocker-security'); ?>
					</button>
				</div>
				<?php wp_nonce_field('bbcs_connect_cloud_api_action', 'bbcs_connect_cloud_api_nonce'); ?>
				<?php wp_nonce_field('bbcs_deactivate_cloud_api_action', 'bbcs_deactivate_cloud_api_nonce'); ?>
				<?php wp_nonce_field('bbcs_fetch_cloud_api_key_action', 'bbcs_fetch_cloud_api_key_nonce'); ?>
			</div>

			<?php if($bbcs_is_cloud_api_active): ?>
				<div class="bbcs_text_input mb-2">
					<div class="bbcs_label_input_box">        			
						<span class="bbcs-label-input"><?php esc_html_e('Remaining Requests', 'botblocker-security'); ?></span>
						<i class="fa-regular fa-circle-question" 
							data-bs-toggle="tooltip" 
							data-bs-html="true" 
							data-bs-placement="top" 
							data-bs-original-title="<?php echo esc_attr__('Cloud requests remaining in your plan.', 'botblocker-security'); ?>">
						</i>
					</div>
					<div class="bbcs_text_input_inner">
						<input 
							class="bbcs_text_input_input" 
							id="bbcs_remaining_hits" 
							name="bbcs_remaining_hits" 
							value="<?php echo esc_attr($bbcs_cached_remaining_hits ?? 0); ?>" 
							data-should-fetch="<?php echo esc_attr(($bbcs_cached_remaining_hits === false || $bbcs_cached_remaining_days === false) ? 'true' : 'false'); ?>"
							disabled="" 
							type="text"
						>
					</div>
				</div>
 
				<div class="bbcs_text_input mb-2">
					<div class="bbcs_label_input_box">        			
						<span class="bbcs-label-input"><?php esc_html_e('Remaining days', 'botblocker-security'); ?></span>
						<i class="fa-regular fa-circle-question" 
							data-bs-toggle="tooltip" 
							data-bs-html="true" 
							data-bs-placement="top" 
							data-bs-original-title="<?php echo esc_attr__('Days until your Cloud API subscription expires.', 'botblocker-security'); ?>">
					    </i>
					</div>
					<div class="bbcs_text_input_inner">
						<input 
							class="bbcs_text_input_input" 
							id="bbcs_remaining_days" 
							name="bbcs_remaining_days" 
							value="<?php echo esc_attr($bbcs_cached_remaining_days !== false ? $bbcs_cached_remaining_days : ''); ?>" 
							disabled="" 
							type="text"
						>
					</div>
				</div>

				<div class="bbcs_settings_button">
					   <button type="button" id="bbcs_refresh_cloud_api" class="mb-1 btn btn-xs btn-default">
						   <i class="fa-solid fa-arrows-rotate"></i>
						   <?php esc_html_e('Refresh Cloud API', 'botblocker-security'); ?>
					   </button>
					   <i class="fa-regular fa-circle-question" 
						   data-bs-toggle="tooltip" 
						   data-bs-html="true" 
						   data-bs-placement="top" 
						   data-bs-original-title="<?php esc_html_e('Re-checks Cloud API status and updates connection details.', 'botblocker-security'); ?>">
					   </i>
				</div>
			<?php endif; ?>

			<h3 class="bbcs_settings_h3"><?php esc_html_e('Already have a license key?', 'botblocker-security'); ?></h3>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs_fetch_cloud_api_key_btn" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-cloud-arrow-down"></i>
					<?php esc_html_e('Fetch API key from cloud', 'botblocker-security'); ?>
				</button>
				<i class="fa-regular fa-circle-question" 
					data-bs-toggle="tooltip" 
					data-bs-html="true" 
					data-bs-placement="top" 
					data-bs-original-title="<?php echo esc_attr__('Retrieves your API key from the cloud.', 'botblocker-security'); ?>">
				</i>
			</div>
        </div>
    </div>
</div>
 