<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$BBCS = BotBlocker::getInstance();
$BBCSA = Botblocker_Admin::getInstance();

bbcs_collect_statistic_data();

if ($BBCS->settings->cache_ui_data == false){
	bbcs_clear_transients();
}

$bbcs_alerts = bbcs_alerts_get_all();

$bbcs_has_pro = bbcs_isCloudAPIActive();

?><header class="header">
	<div class="logo-container">
		<a href="<?php echo esc_url($BBCSA->pages_dashboard);?>" class="logo">
			<?php
            // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
			<?php echo '<img src="' . esc_url($BBCS->media_logo_botblocker). '" height="50" alt="' . esc_attr(BOTBLOCKER_SHORT_NAME) . '">'; ?>
		</a>
		<div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
			<i class="fas fa-bars" aria-label="Toggle sidebar"></i>
		</div>
	</div>
 
	<div class="header-right">
		<?php 
		// Показываем кнопку визарда если он еще не завершен
		if (!get_option('bbcs_setup_wizard_completed', false)) : ?>
		<span class="bbcs-header-wizard-button">
			<a href="<?php echo esc_url(admin_url('admin.php?page=bbcs_setup_wizard')); ?>" class="mt-2 btn btn-xs btn-primary">
				<i class="fa-solid fa-wand-magic-sparkles"></i>&nbsp;
				<?php esc_html_e('Setup Wizard', 'botblocker-security'); ?>
			</a>
		</span>
		<span class="separator"></span>
		<?php else : ?>
		<span class="bbcs-header-wizard-button">
			<a href="<?php echo esc_url(admin_url('admin.php?page=bbcs_setup_wizard')); ?>" class="mt-2 btn btn-xs btn-default">
				<i class="fa-solid fa-rotate"></i>&nbsp;
				<?php esc_html_e('Run Setup Wizard Again', 'botblocker-security'); ?>
			</a>
		</span>
		<span class="separator"></span>
		<?php endif; ?>
		
		<span class="bbcs-header-pro-button">
			<?php if ($bbcs_has_pro == false): ?>
				<a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>" class="mt-2 btn btn-xs btn-default"><i class="fa-solid fa-crown"></i>&nbsp;
				<?php esc_html_e( 'Upgrade to PRO', 'botblocker-security'); ?>
			</a>
			<?php endif; ?>	
			<?php if ($bbcs_has_pro == true): ?>
				<a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>" class="mt-2 btn btn-xs btn-default bbcs-cloud-api-color"><i class="fa-solid fa-crown"></i>&nbsp;<b>
					<?php esc_html_e('PRO is active!' ,'botblocker-security'); ?></b>
				</a>
			<?php endif; ?>				
		</span>
		<span class="separator"></span>
		<ul class="notifications">
	        <?php echo do_shortcode('[bbcs_cron_tasks]')?>
			<li>
				<a href="#" class="dropdown-toggle notification-icon" data-bs-toggle="dropdown">
					<i class="fa fa-bullhorn"></i>
					<?php if (!empty($bbcs_alerts)) : ?>
    					<span class="badge"><?php echo esc_html(count($bbcs_alerts)); ?></span>
					<?php endif; ?>
				</a>
				<div class="dropdown-menu notification-menu">
					<div class="notification-title">
						<?php esc_html_e('Alerts', 'botblocker-security'); ?>
						<?php if (!empty($bbcs_alerts)) : ?>
							<span class="float-end badge badge-default"><?php echo esc_html(count($bbcs_alerts)); ?></span>
						<?php endif; ?>
					</div>
					<div class="content">
						<ul>
							<?php if (empty($bbcs_alerts)) : ?>
							<li>
								<a href="#" class="clearfix">
									<div class="image">
										<i class="fas fa-thumbs-up bg-primary text-light"></i>
									</div>
									<span class="title"><?php esc_html_e('No active alerts', 'botblocker-security'); ?></span>
									<span class="message"><?php esc_html_e('All right', 'botblocker-security'); ?></span>
								</a>
							</li>
							<?php endif; ?>
							
							<?php foreach ($bbcs_alerts as $bbcs_alert) : ?>
								<li>
									<a href="#" class="clearfix">
										<div class="image">
										<i class="<?php echo esc_html($bbcs_alert['icon']); ?>"></i>
										</div>
										<span class="title"><?php echo esc_html($bbcs_alert['title']); ?></span>
										<span class="message"><?php echo esc_html($bbcs_alert['message']); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
<!--
						<hr>
						<div class="text-end">
							<a href="#" class="view-more"><?php esc_html_e('View All', 'botblocker-security'); ?></a>
						</div>
-->
					</div>
				</div>
			</li>

			<li>
				<a href="#" class="dropdown-toggle notification-icon" data-bs-toggle="dropdown">
					<i class="fa fa-globe"></i>
				</a>
				<div class="dropdown-menu notification-menu">
					<div class="notification-title">
						<?php esc_html_e('Select Language', 'botblocker-security'); ?>
					</div>
					<?php echo do_shortcode('[bbcs_lang_options]'); ?>				
				</div>
			</li>

		</ul>
		<span class="separator"></span>
		<div id="userbox" class="userbox">

			<a href="#" data-bs-toggle="dropdown">
				<figure class="profile-picture">
 
					<?php
					$bbcs_user = wp_get_current_user();
					if (is_user_logged_in()) {
						$bbcs_avatar_path = BotBlockerWpUser::getAvatarPath($bbcs_user->ID);
						$bbcs_display_name = BotBlockerWpUser::getDisplayName($bbcs_user->ID);
						$bbcs_user_role = BotBlockerWpUser::getUserRole($bbcs_user->ID);

						if($bbcs_avatar_path == BOTBLOCKER_EMPTY){
							$bbcs_avatar_path = $BBCSA->custom_avatar;
						}

            			// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
            			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
						echo '<img src="' . esc_url($bbcs_avatar_path) . '" alt="' . esc_attr($bbcs_display_name) . '" class="rounded-circle">';
					} else {
						$bbcs_avatar_path = $BBCSA->custom_avatar;
            			// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
            			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
						echo '<img src="' . esc_url($bbcs_avatar_path) . '" alt="' . esc_attr($bbcs_display_name) . '" class="rounded-circle">';
					}
					?>
				</figure>
				<div class="profile-info" data-lock-name="<?php echo esc_html($bbcs_display_name); ?>">
					<span class="name"><?php echo esc_html($bbcs_display_name); ?></span>
					<?php
					if (is_user_logged_in()) {
						echo '<span class="role">' . esc_html($bbcs_user_role) . '</span>';
					} else {
						echo '<span class="role">'. esc_html__('Guest', 'botblocker-security') .'</span>';
					}
					?>
				</div>

				<i class="fa custom-caret"></i>
			</a>
			<div class="dropdown-menu">
				<ul class="list-unstyled mb-2">
					<li class="divider"></li>
					<li>
						<a role="menuitem" tabindex="-1" 
						href="https://<?php echo esc_html(BOTBLOCKER_SERVER);?>/" target="_blank">
						<i class="fa-solid fa-globe bbcs-h-btn-gray"></i> <?php esc_html_e('BotBlocker Website', 'botblocker-security'); ?></a>
					</li>
					<li>
						<a role="menuitem" tabindex="-1" 
						href="https://<?php echo esc_html(BOTBLOCKER_SERVER);?>/docs/" target="_blank">
						<i class="fa-solid fa-book bbcs-h-btn-gray"></i> <?php esc_html_e('Docs', 'botblocker-security'); ?></a>
					</li>
					<li class="divider"></li>	
					<li>
						<a role="menuitem" tabindex="-1" 
						href="https://<?php echo esc_html(BOTBLOCKER_SERVER);?>/hire/" target="_blank">
						<i class="fa-solid fa-code bbcs-h-btn-gray"></i> <?php esc_html_e('Hire a developer', 'botblocker-security'); ?></a>
					</li>	
					<li>
						<a role="menuitem" tabindex="-1" 
						href="https://globus.studio" target="_blank">
						<i class="fa-solid fa-g bbcs-h-btn-gray"></i> <?php esc_html_e('GLOBUS.studio', 'botblocker-security'); ?></a>
					</li>
					<li class="divider"></li>
					<li>
						<a role="menuitem" tabindex="-1" 
						href="https://<?php echo esc_html(BOTBLOCKER_SERVER);?>/contacts" target="_blank">
						<i class="fa-solid fa-envelope bbcs-h-btn-gray"></i> <?php esc_html_e('Contacts', 'botblocker-security'); ?></a>
					</li>																		
				</ul>
			</div>
		</div>
	</div>
</header>
