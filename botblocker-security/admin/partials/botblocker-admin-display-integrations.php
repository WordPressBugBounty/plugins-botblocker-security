<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

function bbcs_load_integrations() {
    global $wpdb;

    // REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $results = $wpdb->get_results( "SELECT `key`, `value` FROM `{$wpdb->bbcs_settings}`", ARRAY_A );

    $bbcs_settings = array(); 
    foreach ( (array) $results as $row ) {
        $decoded = json_decode( $row['value'], true );
        $bbcs_settings[ $row['key'] ] = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $row['value'];
    }

    return $bbcs_settings;
}

$bbcs_settings = bbcs_load_integrations();

include('botblocker-section-header.php');

$bbcs_notice = get_transient( 'bbcs_notice_integrations_' . get_current_user_id() );
if ( is_array( $bbcs_notice ) && isset( $bbcs_notice['message'], $bbcs_notice['type'] ) ) {
    add_settings_error( 'botblocker_messages', 'botblocker_message', $bbcs_notice['message'], $bbcs_notice['type'] );
    delete_transient( 'bbcs_notice_integrations_' . get_current_user_id() );
}

settings_errors('botblocker_messages');
?> 
<section role="main" class="content-body">    
    <form method="post" id="bbcs-integrations-form" action="<?php echo esc_url(admin_url('admin-post.php')) ?>">
        <input type="hidden" name="action" value="save_botblocker_integrations">
        <?php wp_nonce_field('save_botblocker_integrations', 'botblocker_integrations_nonce'); ?>
        <input type="hidden" name="bbcs_anchor" id="bbcs_anchor" value="">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-9 сol-lg-9 col-xl-9 col-xxl-10">
                <section class="card">
                    <header class="card-header">
                        <div class="card-actions">
                            <button type="submit" name="save_settings" value="Save Settings" class="bbcs-icon-button">
                                <i class="bbcs-card-action fa-regular fa-xl fa-floppy-disk"></i>
                            </button>
                        </div>
                        <h2 class="card-title"><?php esc_html_e('Integrations', 'botblocker-security'); ?></h2>
                    </header>
                    <div class="card-body">
					<ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#bbcs_recaptchav2"><?php esc_html_e('ReCaptcha v2', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#bbcs_recaptchav3"><?php esc_html_e('ReCaptcha v3', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#bbcs_transients"><?php esc_html_e('Transients', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#bbcs_memcached"><?php esc_html_e('Memcached', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#bbcs_redis"><?php esc_html_e('Redis', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#bbcs_api"><?php esc_html_e('BotBlocker Cloud', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#bbcs_2fa"><?php esc_html_e('BotBlocker 2FA', 'botblocker-security'); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3">
                        <?php 
							include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-recaptcha2.php';
                            include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-recaptcha3.php';
                            include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-transients.php';
                            include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-memcached.php';
                            include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-redis.php';
                            include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-botblocker.php';
                            include_once BOTBLOCKER_DIR . 'includes/section/integration/botblocker-int-2fa.php';
                        ?>    
                    </div>
                    </div>
                </section>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-3 сol-lg-3 col-xl-3 col-xxl-2">
                <?php include('botblocker-section-right-sidebar.php'); ?>
            </div>
        </div>
    </form>
</section>
