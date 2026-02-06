<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="tab-pane container fade  show active" id="tools-wordpress">
    <div class="row">
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
            <div class="bbcs-info-inner">
                <?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/wordpress.svg'); ?>"
                    alt="<?php esc_attr_e('Wordpress core tuning', 'botblocker-security'); ?>"
                    class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('BotBlocker offers a set of tools designed to enhance the security and performance of your WordPress site. These tools help you block unwanted traffic, filter suspicious requests, and optimize resource usage, ensuring your website remains protected and runs smoothly.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('With BotBlocker, you can easily configure advanced security settings and automate the detection of harmful bots. This not only strengthens your site’s defenses but also improves loading speed by reducing unnecessary server load.', 'botblocker-security'); ?>
                </p>
                <hr class="bbcs-info-hr">
                <div class="bbcs-info-footer">
                    <i class="fa-regular fa-circle-question"></i>
                    <a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Debug Wordpress', 'botblocker-security'); ?></a>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('Wordpress Service', 'botblocker-security'); ?></h3>
            <div class="bbcs_settings_button">
                <button type="button" id="bbcs-site-health" class="mb-1 btn btn-xs btn-default">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <?php esc_html_e('Site Health', 'botblocker-security'); ?>
                </button>
                <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-placement="top"
                    data-bs-original-title="<?php esc_attr_e('Go to the built-in WordPress tool for diagnosing issues', 'botblocker-security'); ?>"></i>
            </div>
            <div class="bbcs_settings_button">
                <button type="button" id="bbcs-clear-wp-log" class="mb-1 btn btn-xs btn-default">
                    <i class="fa-regular fa-trash-can"></i>
                    <?php esc_html_e('Clear Wordpress log', 'botblocker-security'); ?>
                </button>
                <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-placement="top"
                    data-bs-original-title="<?php esc_html_e('Clear Wordpress log if it exists', 'botblocker-security'); ?>"></i>
            </div>
            <div class="bbcs_settings_button">
                <button type="button" id="bbcs-download-wp-log" class="mb-1 btn btn-xs btn-default">
                    <i class="fa-solid fa-download"></i>
                    <?php esc_html_e('Download the WordPress log', 'botblocker-security'); ?>
                </button>
                <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-placement="top"
                    data-bs-original-title="<?php esc_attr_e('Download the current WordPress log file for analysis', 'botblocker-security'); ?>"></i>
            </div>
        </div>

    </div>
</div>
