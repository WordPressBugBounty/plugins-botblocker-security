<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="tab-pane container active" id="report_dashboard">

    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
            <div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/report.svg'); ?>"
                    alt="<?php esc_attr_e('Report dashboard', 'botblocker-security'); ?>" class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('BotBlocker provides detailed reports about every visitor to your website, including parameters such as IP address, country, browser, operating system, and more. This information helps you better understand user behavior and quickly identify suspicious activity related to bots or malicious requests.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('You can create custom blocking rules based on any property shown in the report. For example, you can block a specific IP address, country, or device type if they appear suspicious. This gives you flexibility and full control over your website’s security, allowing you to respond promptly to new threats.', 'botblocker-security'); ?>
                </p>
                <hr class="bbcs-info-hr">
                <div class="bbcs-info-footer">
                    <i class="fa-regular fa-circle-question"></i>
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/what-data-does-botblocker-collect-and-how-is-it-stored-and-deleted/" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Data collection and privacy', 'botblocker-security'); ?></a>

                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/log-retention-in-botblocker-how-to-manage-storage-period-time-zone-and-analytics/" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Log retention and analytics', 'botblocker-security'); ?></a>

                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('Report list', 'botblocker-security'); ?></h3>

            <div class="bbcs-report-links d-flex flex-column gap-2 mb-3">
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#frontend" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('Site visitors','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Live & historical traffic (IP, country, device, behavior markers).','botblocker-security'); ?></span>
                </a>
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#admin" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('WordPress admin area log','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Admin logins, failed attempts, sensitive panel access traces.','botblocker-security'); ?></span>
                </a>
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#wordpress" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('WordPress actions','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Core / plugin / theme level events relevant to security posture.','botblocker-security'); ?></span>
                </a>
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#full" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('Full log','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Unified raw event stream for deep forensics & rule tuning.','botblocker-security'); ?></span>
                </a>
            </div>
            <p class="bbcs-info-text mb-0 small">
                <?php esc_html_e('Open any report to drill down and create instant block / allow rules based on real traffic attributes.','botblocker-security'); ?>
            </p>
        </div>

        <div class="col-xxl-6 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('Create rule from any report item', 'botblocker-security'); ?></h3>

            <div class="ratio ratio-16x9 mb-2 bbcs-report-video-wrapper">
                <video controls preload="metadata" class="w-100 bbcs-report-video">
                    <!--<source src="<?php //echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/create-rule.mp4' ); ?>" type="video/mp4">-->
                    <source src="<?php echo esc_url( BOTBLOCKER_URL . 'public/video/create-rule.mp4');?>" type="video/mp4">
                    <?php esc_html_e('Your browser does not support the video tag.','botblocker-security'); ?>
                </video>
            </div>
            <p class="small bbcs-wizard-muted mb-0">
                <?php esc_html_e('Short demo: open a log entry, inspect parameters, generate a precise rule (IP / country / UA / signature) and apply instantly.','botblocker-security'); ?>
            </p>
        </div>        

    </div>

</div>
