<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require 'botblocker-section-header.php';

global $wpdb;

function bbcs_load_settings() {
	global $wpdb;

	// REVIEWER NOTE: custom table operations require direct database queries.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$results = $wpdb->get_results( "SELECT `key`, `value` FROM `{$wpdb->bbcs_settings}`", ARRAY_A );

	$bbcs_settings = array();
	foreach ( (array) $results as $row ) {
		$decoded                      = json_decode( $row['value'], true );
		$bbcs_settings[ $row['key'] ] = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $row['value'];
	}

	return $bbcs_settings;
}

$bbcs_settings = bbcs_load_settings();

$bbcs_notice = get_transient( 'bbcs_notice_settings_' . get_current_user_id() );
if ( is_array( $bbcs_notice ) && isset( $bbcs_notice['message'], $bbcs_notice['type'] ) ) {
	add_settings_error( 'botblocker_messages', 'botblocker_message', $bbcs_notice['message'], $bbcs_notice['type'] );
	delete_transient( 'bbcs_notice_settings_' . get_current_user_id() );
}

settings_errors( 'botblocker_messages' );
?>
<section role="main" class="content-body">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="save_botblocker_settings">
		<?php wp_nonce_field( 'save_botblocker_settings', 'botblocker_settings_nonce' ); ?>
		<input type="hidden" name="bbcs_anchor" id="bbcs_anchor" value="">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 col-xl-9 col-xxl-10">
				<section class="card">
					<header class="card-header">
						<div class="card-actions">
							<button type="submit" name="save_settings" value="Save Settings" class="bbcs-icon-button">
								<i class="bbcs-card-action fa-regular fa-xl fa-floppy-disk"></i>
							</button>
						</div>
						<h2 class="card-title"><?php esc_html_e( 'Settings', 'botblocker-security' ); ?></h2>
					</header>
					<div class="card-body">						
						<ul class="nav nav-tabs">
							<li class="nav-item">
								<a class="nav-link active" data-bs-toggle="tab" href="#simple_bots"><?php esc_html_e( 'Simple Bot Detection', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#connect_types"><?php esc_html_e( 'Connection Types', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#browser_plugins"><?php esc_html_e( 'Browser Plugins', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#data_log_and_processing"><?php esc_html_e( 'Data Log and Processing', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link bbcs-cloud-api-color" data-bs-toggle="tab" href="#advanced_protection"><?php esc_html_e( 'Advanced Protection', 'botblocker-security' ); ?></a>
							</li>
						</ul>
						<div class="tab-content">
							<?php
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-simple.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-connect.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-browser.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-data.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-adv.php';
							?>
						</div>
					</div>
				</section>

				<section class="card">
					<header class="card-header">
						<div class="card-actions">
							<button type="submit" name="save_settings" value="Save Settings" class="bbcs-icon-button">
								<i class="bbcs-card-action fa-regular fa-xl fa-floppy-disk"></i>
							</button>
						</div>
						<h2 class="card-title"><?php esc_html_e( 'Advanced Settings', 'botblocker-security' ); ?></h2>
					</header>
					<div class="card-body">						
						<ul class="nav nav-tabs">
							<li class="nav-item">
								<a class="nav-link active" data-bs-toggle="tab" href="#cookie"><?php esc_html_e( 'Cookie Settings', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#general"><?php esc_html_e( 'General', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#error"><?php esc_html_e( 'Error and Access Settings', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#login_brutforce"><?php esc_html_e( 'Login Brute-Force', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#captcha"><?php esc_html_e( 'BotBlocker Captcha', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#traffic"><?php esc_html_e( 'Traffic and Referrer Settings', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#payment"><?php esc_html_e( 'Payment Gateways', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#log"><?php esc_html_e( 'Logging Settings', 'botblocker-security' ); ?></a>
							</li>						
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#cron"><?php esc_html_e( 'Cron Jobs', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#settings_ui"><?php esc_html_e( 'UI Settings', 'botblocker-security' ); ?></a>
							</li>                              
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#notifications"><?php esc_html_e( 'Notifications', 'botblocker-security' ); ?></a>
							</li>
						</ul>
						<div class="tab-content">
							<?php
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-cookie.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-general.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-error.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-login-brutforce.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-captcha.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-traffic.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-payment.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-log.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-cron.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-ui.php';
								require_once BOTBLOCKER_DIR . 'includes/section/settings/botblocker-set-notif.php';
							?>
						</div>
					</div> 
				</section> 
			</div>
			<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-xxl-2">
				<?php require 'botblocker-section-right-sidebar.php'; ?>
			</div>
		</div>
	</form>
</section>
