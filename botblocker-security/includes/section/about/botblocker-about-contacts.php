<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="row">		
		<div class="col-xxl-6 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/chat.svg'); ?>" 
					alt="<?php esc_attr_e('Contacts and Support', 'botblocker-security'); ?>" 
					class="img-fluid bbcs-info-image mb-3">
                <p class="bbcs-info-text">
                    <?php esc_html_e('If you have any questions, suggestions, or need assistance with BotBlocker, 
					please feel free to reach out to us. We are here to help you make the most of our plugin and 
					ensure your website is protected from unwanted bots and malicious traffic.', 'botblocker-security'); ?>
                </p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/technical-support-for-botblocker-crm-ticket-system-and-email-assistance/" 
					target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('About technical support', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/privacy-and-confidentiality-of-customer-data-in-botblocker-technical-support/" 
					target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Privacy of customer data', 'botblocker-security'); ?></a>
					
					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/system-administration-support-for-wordpress-and-botblocker-full-service-assistance/" 
					target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('System administration', 'botblocker-security'); ?></a>

					<a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/custom-plugin-and-theme-development-for-wordpress-any-complexity-any-technology/" 
					target="_blank" class="bbcs-info-footer-a"><?php esc_html_e('Wordpress development', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div class="col-xxl-6 col-xl-6 col-lg-6 col-sm-12 col-md-12">
		<h3 class="bbcs_settings_h3"><?php esc_html_e('Support via tickets', 'botblocker-security'); ?></h3>
            <?php
            $bbcs_crm_host = defined('BOTBLOCKER_SERVER') ? BOTBLOCKER_SERVER : '';
            $bbcs_crm_host = preg_replace('~^https?://~i','', $bbcs_crm_host); 
            $bbcs_crm_url  = 'https://crm.' . ltrim( $bbcs_crm_host, '.' );
            ?>
            <a href="<?php echo esc_url( $bbcs_crm_url ); ?>"
            class="btn btn-xs"><i class="fa-regular fa-square-check"></i>&nbsp;
				<?php esc_html_e('BotBlocker CRM', 'botblocker-security'); ?>
			</a>
		<h3 class="bbcs_settings_h3"><?php esc_html_e('BotBlocker pages', 'botblocker-security'); ?></h3>

			<!-- <a href="<?php //echo esc_url(BOTBLOCKER_ENVATO_URL)?>" 
			class="btn btn-xs"><i class="fa-solid fa-cart-shopping"></i>&nbsp;
				<?php //esc_html_e('Envato item page' ,'botblocker-security'); ?>
			</a>
			<br> -->
			<a href="<?php echo esc_url(BOTBLOCKER_WORDPRESS_URL)?>"
			class="btn btn-xs"><i class="fa-brands fa-wordpress-simple"></i>&nbsp;
				<?php esc_html_e('Wordpress plugin page', 'botblocker-security'); ?>
			</a>
			<br>
			<a href="<?php echo esc_url(BOTBLOCKER_MAILTO_LINK)?>" class="btn btn-xs">
				<i class="fa-solid fa-envelope"></i>&nbsp;
				<?php esc_html_e('admin@botblocker.top', 'botblocker-security'); ?>
			</a>
			<br>
			<a href="<?php echo esc_url(BOTBLOCKER_TELEGRAM_SUPPORT)?>" target="_blank" class="btn btn-xs">
				<i class="fa-brands fa-telegram"></i>&nbsp;
				<?php esc_html_e('Support in Telegram', 'botblocker-security'); ?>
			</a>
			<br>
			<a href="<?php echo esc_url(BOTBLOCKER_SUPPORT_FORUM)?>" target="_blank" class="btn btn-xs">
				<i class="fa-solid fa-users"></i>&nbsp;
				<?php esc_html_e('Support Forum', 'botblocker-security'); ?>
			</a>
        </div>
	</div>
