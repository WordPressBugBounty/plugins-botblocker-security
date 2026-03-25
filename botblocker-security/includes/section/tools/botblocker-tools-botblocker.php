<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="tab-pane container fade" id="tools-botblocker">
    <div class="row">
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
            <div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/rocket.svg'); ?>"
                    alt="<?php esc_attr_e('Traffic and Referrer Settings', 'botblocker-security'); ?>"
                    class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('Tools for blocking bots, filtering suspicious traffic, and optimizing server resources.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('Configure protection rules and automate threat detection. Reducing junk traffic also improves load times.', 'botblocker-security'); ?>
                </p>
                <hr class="bbcs-info-hr">
                <div class="bbcs-info-footer">
                    <i class="fa-regular fa-circle-question"></i>
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/tools/" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Tools', 'botblocker-security'); ?></a>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('BotBlocker Settings', 'botblocker-security'); ?></h3>
            <div class="bbcs_settings_button">
                <button type="button" id="bbcs-backup-data-settings" class="mb-1 btn btn-xs btn-default">
                    <i class="fa-solid fa-download"></i>
                    <?php esc_html_e('Export data and settings', 'botblocker-security'); ?>
                </button>
                <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-placement="top"
                    data-bs-original-title="<?php esc_html_e('Export current settings and data.', 'botblocker-security'); ?>"></i>
            </div>
            <div class="bbcs_settings_button">
                <button type="button" id="bbcs-import-data-settings" class="mb-1 btn btn-xs btn-default">
                    <i class="fa-solid fa-upload"></i>
                    <?php esc_html_e('Import data and settings', 'botblocker-security'); ?>
                </button>
                <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-placement="top"
                    data-bs-original-title="<?php esc_html_e('Import settings and data from a backup.', 'botblocker-security'); ?>"></i>
            </div>
        </div>

    </div>
</div>
