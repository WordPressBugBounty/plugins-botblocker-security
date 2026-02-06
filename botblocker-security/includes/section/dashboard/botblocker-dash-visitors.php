<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><section class="card">
    <header class="card-header">
        <div class="card-actions">
            <a href="<?php echo esc_url($BBCSA->pages_reports); ?>#full" class="bbcs-icon-button bbcs-card-action"
                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                data-bs-original-title="Full events report">
                <i class="fa-solid fa-chart-line"></i>
            </a>
        </div>
        <h2 class="card-title"><?php esc_html_e('Visitors Log', 'botblocker-security'); echo wp_kses_post(BotBlockerUI::is_realtime());?></h2>
    </header>
    <div class="card-body">
        <?php echo do_shortcode('[bbcs_latest_hits]') ?>
    </div>
</section>
