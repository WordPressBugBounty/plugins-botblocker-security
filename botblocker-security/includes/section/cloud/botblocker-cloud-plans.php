<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane fade show active" id="cloud-plans">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
            <div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/tarifs.svg'); ?>"
                    alt="<?php esc_attr_e('BotBlocker subscription plans', 'botblocker-security'); ?>"
                    class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('BotBlocker offers subscription plans for sites of any size — from personal blogs to large corporate websites.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('Each plan includes a specific set of features and protection levels. Choose the one that matches your requirements.', 'botblocker-security'); ?>
                </p>

                <?php 
                $BBCSA = class_exists('Botblocker_Admin') ? Botblocker_Admin::getInstance() : null; 
                $bbcs_pages_cloud_api = $BBCSA && isset($BBCSA->pages_cloud_api) ? $BBCSA->pages_cloud_api : bbcs_admin_page_url('bbcs_cloud_api');
                if ( function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive() ) : ?>
                    <div class="alert alert-success p-2 mb-3" style="font-size:11px; line-height:14px;">
                        <strong><?php esc_html_e('PRO active:', 'botblocker-security'); ?></strong> <?php esc_html_e('Manage your subscription details below.', 'botblocker-security'); ?>
                        <a class="ms-1 bbcs-simple-link" href="<?php echo esc_url( $bbcs_pages_cloud_api . '#cloud-status'); ?>">
                            <?php esc_html_e('Subscription status', 'botblocker-security'); ?>
                        </a>
                    </div>
                <?php endif; ?>


                <hr class="bbcs-info-hr">
                <div class="bbcs-info-footer">
                    <i class="fa-regular fa-circle-question"></i>
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

        <div class="col-xxl-9 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <div class="row">
                <div class="col-md-12 bbcs-price-container">
                    <?php 
                    
                    //echo do_shortcode( '[bbcs_price_list]' ); // Off price list
                    
                    ?> 
                </div>
            </div>
        </div>
    </div>
</div>
