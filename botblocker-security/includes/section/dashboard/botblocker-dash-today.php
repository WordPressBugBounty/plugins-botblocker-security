<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><section class="card">
    <header class="card-header">
        <div class="card-actions">
            <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#full" class="bbcs-icon-button bbcs-card-action"
                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                data-bs-original-title="Full report">
                <i class="fa-solid fa-chart-line"></i>
            </a>
        </div>
        <h2 class="card-title"><?php esc_html_e('Today statistics', 'botblocker-security'); echo wp_kses_post(BotBlockerUI::is_realtime());?>
        </h2>
    </header>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-8">
                <div class="bbcs-statistics-chart-title-div"><span
                        class="bbcs-statistics-chart-title"><?php esc_html_e('Hourly visitors chart', 'botblocker-security'); ?></span>
                </div>
                <?php echo do_shortcode('[bbcs_daily_hits_chart width="100%" height="200px"]'); ?>
            </div>
            <div class="col-lg-4">
                <?php echo do_shortcode('[bbcs_top_browsers limit="10" days="1"]'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <?php echo do_shortcode('[bbcs_statistics_chart type="donut" period="today" data="ip_hits_hosts" height="90px"]'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo do_shortcode('[bbcs_statistics_chart type="donut" period="today" data="device_types" height="90px"]'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo do_shortcode('[bbcs_statistics_chart type="donut" period="today" data="browsers" height="90px"]'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo do_shortcode('[bbcs_statistics_chart type="donut" period="today" data="operating_systems" height="90px"]'); ?>
            </div>
        </div>
    </div>
</section>
