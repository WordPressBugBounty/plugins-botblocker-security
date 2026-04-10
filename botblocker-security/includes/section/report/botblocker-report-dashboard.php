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
                    <?php esc_html_e('Detailed reports on every visitor: IP address, country, browser, OS, and more. Use them to spot suspicious bot activity and malicious requests.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('Create blocking rules from any report property - IP, country, device type, and more. React to new threats instantly with full control over what gets blocked.', 'botblocker-security'); ?>
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
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Live and historical traffic (IP, country, device, behavior markers).','botblocker-security'); ?></span>
                </a>
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#admin" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('WordPress admin area log','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Admin logins, failed attempts, sensitive panel access traces.','botblocker-security'); ?></span>
                </a>
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#wordpress" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('WordPress actions','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Core, plugin, and theme security events.','botblocker-security'); ?></span>
                </a>
                <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#full" class="btn btn-default btn-sm text-start">
                    <strong><?php esc_html_e('Full log','botblocker-security'); ?></strong><br>
                    <span class="small bbcs-wizard-muted"><?php esc_html_e('Raw event log for forensics and rule tuning.','botblocker-security'); ?></span>
                </a>
            </div>
            <p class="bbcs-info-text mb-0 small">
                <?php esc_html_e('Open any report to drill down and create block/allow rules from real traffic data.','botblocker-security'); ?>
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
                <?php esc_html_e('Demo: open a log entry, inspect parameters, generate a rule (IP / country / UA / signature) and apply it.','botblocker-security'); ?>
            </p>
        </div>        

    </div>

</div>
