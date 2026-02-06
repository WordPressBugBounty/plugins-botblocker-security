<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$BBCS = BotBlocker::getInstance();
$BBCSA = Botblocker_Admin::getInstance();


$bbcs_cloud_api_active = (function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive());
$bbcs_early_addon_active = function_exists('bbcs_is_addon_active') ? bbcs_is_addon_active('bbcs-early-init') : false;
$bbcs_early_available = $bbcs_cloud_api_active && $bbcs_early_addon_active;

?><section class="card bbcs-card-border-left ">
    <header class="card-header bbcs_small_header">
        <div class="card-actions bbcs_header_controls">
            <a href="<?php echo esc_url($BBCSA->pages_settings); ?>" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                data-bs-original-title="<?php esc_html_e('BotBlocker Settings', 'botblocker-security'); ?>"><i
                    class="fa-solid fa-gear bbcs-h-btn-gray"></i></a>
        </div>
        <h2 class="card-title"><?php esc_html_e('Status', 'botblocker-security'); ?></h2>
        <!--<p class="card-subtitle"></p>-->
    </header>
    <div class="card-body">
        <div class="bbcs_status_main">
            <i class="fa-solid fa-2x fa-shield-halved bbcs_color_green"></i>
            <span class="bbcs_status_text"><?php esc_html_e('Active', 'botblocker-security'); ?></span>
        </div>

        <div class="bbcs_switch_container">
            <label class="bbcs_switch">
                <?php 
         
                $bbcs_early_checked = 0;
                if (isset($bbcs_settings)) {
                    $bbcs_early_checked = $bbcs_early_available ? (isset($bbcs_settings['early_init_enable']) ? (int)$bbcs_settings['early_init_enable'] : 0) : 0;
                } else {
                    $bbcs_early_checked = $bbcs_early_available ? (isset($BBCS->settings->early_init_enable) ? (int)$BBCS->settings->early_init_enable : 0) : 0;
                }
                ?>
                <input <?php checked(1, $bbcs_early_checked); ?>
                    type="checkbox" id="bbcs_switch_early_init"
                    <?php echo $bbcs_early_available ? '' : 'disabled'; ?>
                    data-early-available="<?php echo (int) $bbcs_early_available; ?>"
                    data-addons-url="<?php echo esc_url($BBCSA->pages_addons); ?>"
                    data-pro-url="<?php echo esc_url($BBCSA->pages_cloud_api); ?>">
                <span class="bbcs_slider"></span>
            </label> 
            <span class="bbcs_switch_label"><?php esc_html_e('Early initialization', 'botblocker-security'); ?>
                <a href="<?php echo esc_url($BBCSA->pages_setup); ?>"><i class="fa-solid fa-person-running bbcs-gray"></i></a>
                <a href="#">
                    <i class="fas fa-info-circle bbcs-gray ms-1" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_html_e('Early initialization via wp-config allows black/white IP lists to work before the WordPress core loads', 'botblocker-security'); ?>">
                    </i>
                </a>
            </span>
        </div>

        <div class="text-muted bbcs-sidebar-pro-text " <?php echo $bbcs_early_available ? 'hidden' : ''; ?>>
            <?php 
            if (!$bbcs_cloud_api_active && !$bbcs_early_addon_active) {
                echo esc_html__('Requires active Cloud API Connection and Early Init addon.', 'botblocker-security');
            } elseif (!$bbcs_cloud_api_active) {
                echo esc_html__('Requires active PRO', 'botblocker-security');
            } else {
                echo esc_html__('Requires Early Init addon to be enabled.', 'botblocker-security');
            }
            ?>
            (<a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>"><?php esc_html_e('Buy PRO now!', 'botblocker-security'); ?></a>
            <?php esc_html_e('or', 'botblocker-security'); ?>
            <a href="<?php echo esc_url($BBCSA->pages_addons); ?>"><?php esc_html_e('manage addons', 'botblocker-security'); ?></a>)
        </div>
        
        <div class="bbcs_switch_container">
            <label class="bbcs_switch">
                <?php if (isset($bbcs_settings)): ?>
                <input <?php checked(1, isset($bbcs_settings['mu_enable']) ? $bbcs_settings['mu_enable'] : 0); ?> type="checkbox"
                    id="bbcs_switch_mu_plugin">
                <?php else: ?>
                <input <?php checked(1, isset($BBCS->settings->mu_enable) ? $BBCS->settings->mu_enable : 0); ?>
                    type="checkbox" id="bbcs_switch_mu_plugin">
                <?php endif ?>
                <span class="bbcs_slider"></span>
            </label> <span class="bbcs_switch_label"><?php esc_html_e('MU plugin', 'botblocker-security'); ?>
                <a href="<?php echo esc_url($BBCSA->pages_setup); ?>"><i class="fa-solid fa-person-running bbcs-gray"></i></a>
                <a href="#">
                    <i class="fas fa-info-circle bbcs-gray ms-1" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_html_e('Running as an MU plugin allows black/white IP lists to work before regular plugins and the WordPress core are fully loaded', 'botblocker-security'); ?>">
                    </i>
                </a>
            </span>
        </div>

        <div class="bbcs_switch_container">
            <label class="bbcs_switch">
                <?php if (isset($bbcs_settings)): ?>
                <input <?php checked(1, isset($bbcs_settings['redis_enable']) ? $bbcs_settings['redis_enable'] : 1); ?>
                    type="checkbox" value="1" id="bbcs_switch_redis">
                <?php else: ?>
                <input <?php checked(1, isset($BBCS->settings->redis_enable) ? $BBCS->settings->redis_enable : 1); ?>
                    type="checkbox" value="1" id="bbcs_switch_redis">
                <?php endif ?>
                <span class="bbcs_slider"></span>
            </label> <span class="bbcs_switch_label"><?php esc_html_e('Redis', 'botblocker-security'); ?>
                <a href="<?php echo esc_url($BBCSA->pages_integrations); ?>#bbcs_redis"><i class="fas fa-gear bbcs-gray ms-1"></i></a>
                <a href="#">
                    <i class="fas fa-info-circle bbcs-gray ms-1" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top" 
                        data-bs-original-title="<?php esc_html_e('Redis  used to speed up the work of long stages of processing site visitors', 'botblocker-security'); ?>">
                    </i>
                </a>
            </span>
        </div>

        <div class="bbcs_switch_container">
            <label class="bbcs_switch">
                <?php if (isset($bbcs_settings)): ?>
                <input type="checkbox"
                    <?php checked(1, isset($bbcs_settings['memcached_enable']) ? $bbcs_settings['memcached_enable'] : 1); ?>
                    value="1" id="bbcs_switch_memcached">
                <?php else: ?>
                <input type="checkbox"
                    <?php checked(1, isset($BBCS->settings->memcached_enable) ? $BBCS->settings->memcached_enable : 1); ?>
                    value="1" id="bbcs_switch_memcached">
                <?php endif ?>
                <span class="bbcs_slider"></span>
            </label> <span class="bbcs_switch_label"><?php esc_html_e('Memcached', 'botblocker-security'); ?>
                <a href="<?php echo esc_url($BBCSA->pages_integrations); ?>#bbcs_memcached"><i class="fas fa-gear bbcs-gray ms-1"></i></a>
                <a href="#">
                    <i class="fas fa-info-circle bbcs-gray ms-1" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_html_e('Memcached used to speed up the work of long stages of processing site visitors', 'botblocker-security'); ?>">
                    </i>
                </a>
            </span>
        </div>

        <div class="bbcs_switch_container">
            <label class="bbcs_switch">
                <input type="checkbox"
                    <?php checked(1, isset($BBCS->settings->ptr_cache_in_db) ? $BBCS->settings->ptr_cache_in_db : 1); ?>
                    value="1" id="bbcs_switch_apcu">
                <span class="bbcs_slider"></span>
            </label> <span class="bbcs_switch_label"><?php esc_html_e('PTR Cache', 'botblocker-security'); ?>
                <a href="#">
                    <i class="fas fa-info-circle bbcs-gray ms-1" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_html_e('Enabling this option will significantly speed up repeat user checks within 24 hours', 'botblocker-security'); ?>">
                    </i>
                </a>
            </span>
        </div>

    </div>
    <div class="card-footer">
        <small>
            <?php esc_html_e('Today blocked:', 'botblocker-security'); ?> <b><?php echo do_shortcode('[bbcs_blocked_today]') ?></b>
            <br>
            <?php esc_html_e('Total blocked:', 'botblocker-security'); ?> <b><?php echo do_shortcode('[bbcs_blocked_total]') ?></b>
        </small>
    </div>

</section>

<?php if (bbcs_isCloudAPIActive() == false): ?>

<section class="card bbcs-card-border-left ">
    <header class="card-header bbcs_small_header bbcs-cloud-api-color-bg">
        <div class="card-actions bbcs_header_controls">
            <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/cloud-verification-in-botblocker-database-types-used-for-advanced-threat-detection/" 
            target="_blank" 
            data-bs-toggle="tooltip"
            class="me-2"
            data-bs-html="true" data-bs-placement="top"
            data-bs-original-title="<?php esc_html_e('About Cloud Verification', 'botblocker-security'); ?>">
            <i class="fa-solid fa-globe bbcs-color-white"></i>
            </a>

            <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/how-botblocker-pros-cloud-verification-defeats-bots/" 
            target="_blank" 
            data-bs-toggle="tooltip"
            data-bs-html="true" data-bs-placement="top"
            data-bs-original-title="<?php esc_html_e('BotBlocker PRO', 'botblocker-security'); ?>">
            <i class="fa-solid fa-globe bbcs-color-white"></i>
            </a>

        </div>
        <h2 class="card-title bbcs-color-white"><?php esc_html_e('PRO Features', 'botblocker-security'); ?></h2>

    </header>
    <div class="card-body">
        <ul class="bbcs-cloud-api-features">
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Hide login URL', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Speed Optimizations', 'botblocker-security'); ?></li>            
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Cloud Bot Detection', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('VPN & Proxy Blocking', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Behavioral Analysis', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Threat Intelligence', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Daily Updates', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('All Addons Access', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Early Initialization', 'botblocker-security'); ?></li>
            <li><i class="fa-solid fa-check me-1"></i><?php esc_html_e('Priority Support', 'botblocker-security'); ?></li>
        </ul>
        <a href="<?php echo esc_url($BBCSA->pages_cloud_api); ?>" class="mt-2 btn btn-sm bbcs-btn-primary-cta"><i
                class="fa-solid fa-crown"></i>&nbsp;<?php esc_html_e('Get PRO Now', 'botblocker-security'); ?>
        </a>
    </div>
</section>

<?php endif; ?>

<!-- Recommendations Section (Comming Soon)
<section class="card bbcs-card-border-left">
    <header class="card-header bbcs_small_header">
        <div class="card-actions bbcs_header_controls">
            <a href="<?php //echo esc_url($BBCSA->pages_setup); ?>" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                data-bs-original-title="<?php //esc_html_e('Setup Guide', 'botblocker-security'); ?>">
                <i class="fa-solid fa-lightbulb"></i>
            </a>
        </div>
        <h2 class="card-title"><?php //esc_html_e('Recommendations', 'botblocker-security'); ?></h2>
    </header>
    <div class="card-body">
        <?php //echo do_shortcode('[bbcs_recommendations]'); ?>
    </div>
</section>
-->

<?php if (defined('BOTBLOCKER_DISPLAY_NEWS') && BOTBLOCKER_DISPLAY_NEWS): ?>
<section class="card bbcs-card-border-left ">
    <header class="card-header bbcs_small_header">
        <div class="card-actions bbcs_header_controls">
            <a href="<?php echo esc_url(BOTBLOCKER_NEWS_URL); ?>" target="_blank" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                data-bs-original-title="<?php esc_html_e('BotBlocker News', 'botblocker-security'); ?>"><i
                    class="fa-solid fa-globe bbcs-h-btn-gray"></i></a>
        </div>
        <h2 class="card-title"><?php esc_html_e('News', 'botblocker-security'); ?></h2>
        <!--<p class="card-subtitle"></p>-->
    </header>
    <div class="card-body">
        <?php echo do_shortcode('[bbcs_botblocker_news count="5"]'); ?>
    </div>
    <div class="card-footer">
        <small>
            <?php echo do_shortcode('[bbcs_database_update]'); ?>
            <br>
            <?php echo do_shortcode('[bbcs_database_total]'); ?>
        </small>
    </div>
</section>
<?php endif; ?>

<section class="card bbcs-card-border-left ">
    <header class="card-header bbcs_small_header">
        <div class="card-actions bbcs_header_controls">
            <a href="<?php echo esc_url($BBCSA->pages_settings); ?>" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                data-bs-original-title="<?php esc_html_e('BotBlocker Settings', 'botblocker-security'); ?>"><i
                    class="fa-solid fa-gear bbcs-h-btn-gray"></i></a>
        </div>
        <h2 class="card-title"><?php esc_html_e('System Status', 'botblocker-security'); ?></h2>
        <!--<p class="card-subtitle"></p>-->
    </header>
    <div class="card-body">
        <?php echo do_shortcode('[bbcs_system_status]'); ?>
    </div>
</section>
