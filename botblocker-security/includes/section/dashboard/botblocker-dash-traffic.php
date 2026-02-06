<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><section class="card">
    <header class="card-header">
        <div class="card-actions">
            <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#frontend" class="bbcs-icon-button bbcs-card-action" 
                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                data-bs-original-title="Website visitors report">
                <i class="fa-solid fa-chart-line"></i>
            </a>
            <a href="<?php echo esc_url($BBCSA->pages_settings); ?>" class="bbcs-icon-button" data-bs-toggle="tooltip"
                data-bs-html="true" data-bs-placement="top"
                data-bs-original-title="<?php esc_html_e('Settings', 'botblocker-security'); ?>">
                <i class="bbcs-card-action fa-solid fa-gear"></i>
            </a>
        </div>
        <h2 class="card-title"><?php esc_html_e('Website Traffic', 'botblocker-security'); echo wp_kses_post(BotBlockerUI::is_realtime());?>
        </h2>
        <p class="card-subtitle">
            <?php esc_html_e('Real-time website visitor statistics. View period - ', 'botblocker-security'); ?><?php echo esc_html($BBCS->settings->admin_report_period); ?>
            <?php esc_html_e('days', 'botblocker-security'); ?> 
            (<a href="<?php echo esc_url($BBCSA->pages_settings); ?>#settings_ui"><?php esc_html_e('Change', 'botblocker-security'); ?></a>).</p>
    </header>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-8">
                <?php echo do_shortcode('[bbcs_hits_and_uniques_chart width="100%" height="230px" days="' . esc_html($BBCS->settings->admin_report_period) . '"]'); ?>
            </div>
            <div class="col-lg-4">
                <?php echo do_shortcode('[bbcs_top_ips limit="10" days="' . esc_html($BBCS->settings->admin_report_period) . '"]'); ?>
                <div class="mt-1"></div>
                <?php echo do_shortcode('[bbcs_top_devices limit="3" days="' . esc_html($BBCS->settings->admin_report_period) . '"]'); ?>
            </div>
        </div>
    </div>
</section>
