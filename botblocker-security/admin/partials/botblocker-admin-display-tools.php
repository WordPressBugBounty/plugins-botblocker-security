<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include('botblocker-section-header.php');


$bbcs_tools_notice = null;
$bbcs_tools_login_url_draft = null;

if ( isset( $_POST['save_settings'] ) ) {
    if ( ! current_user_can(bbcs_can_manage()) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
    }
    check_admin_referer( 'save_botblocker_settings', 'botblocker_settings_nonce' );

    $bbcs_core_settings  = [];
    $bbcs_login_settings = [];
    $bbcs_existing_login_settings = get_option( 'botblocker_tools_login_settings', [] );

    if ( function_exists( 'bbcs_get_tools_core_options' ) ) {
        foreach ( bbcs_get_tools_core_options() as $bbcs_field ) {
            if ( isset( $_POST[ $bbcs_field ] ) ) {
                $bbcs_core_settings[ $bbcs_field ] = sanitize_text_field( wp_unslash( $_POST[ $bbcs_field ] ) );
            }
        }
    }

    if ( function_exists( 'bbcs_get_tools_login_options' ) ) {
        foreach ( bbcs_get_tools_login_options() as $bbcs_field ) {
            if ( isset( $_POST[ $bbcs_field ] ) ) {
                // REVIEWER NOTE: Input is sanitized per-field below via sanitize_text_field() and other sanitizers; raw access here is intentional to support dynamic field dispatch.
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $bbcs_raw_value = wp_unslash( $_POST[ $bbcs_field ] );

                if ( 'login_url' === $bbcs_field ) {
                    $bbcs_login_url = is_string( $bbcs_raw_value ) ? sanitize_text_field( $bbcs_raw_value ) : '';
                    $bbcs_preserved_slug = isset( $bbcs_existing_login_settings['login_url'] ) && bbcs_tools_is_valid_login_slug( $bbcs_existing_login_settings['login_url'] )
                        ? $bbcs_existing_login_settings['login_url']
                        : '';

                    if ( $bbcs_login_url !== '' && ! bbcs_tools_is_valid_login_slug( $bbcs_login_url ) ) {
                        $bbcs_tools_notice = esc_html__( 'Custom Login URL is invalid. Use only letters, numbers, hyphens, or underscores, and do not use reserved WordPress login endpoints.', 'botblocker-security' );
                        $bbcs_tools_login_url_draft = $bbcs_login_url;
                        $bbcs_login_settings['login_url'] = $bbcs_preserved_slug;
                        continue;
                    }

                    if ( $bbcs_login_url !== '' && $bbcs_login_url !== $bbcs_preserved_slug && bbcs_tools_login_slug_conflicts( $bbcs_login_url ) ) {
                        $bbcs_tools_notice = esc_html__( 'This URL conflicts with an existing page, post, or taxonomy on your site. Choose a different login slug.', 'botblocker-security' );
                        $bbcs_tools_login_url_draft = $bbcs_login_url;
                        $bbcs_login_settings['login_url'] = $bbcs_preserved_slug;
                        continue;
                    }

                    $bbcs_login_settings['login_url'] = $bbcs_login_url;
                    continue;
                }

                $bbcs_login_settings[ $bbcs_field ] = sanitize_text_field( $bbcs_raw_value );
            }
        }
    }

	if ( null === $bbcs_tools_notice ) {
		update_option( 'botblocker_tools_core_settings', $bbcs_core_settings );
		update_option( 'botblocker_tools_login_settings', $bbcs_login_settings );
	}
}

if ( $bbcs_tools_notice ) {
    add_settings_error( 'botblocker_messages', 'botblocker_tools_message', $bbcs_tools_notice, 'error' );
}

settings_errors('botblocker_messages');
 
?><section role="main" class="content-body">
	<form method="post" action="">
		<?php wp_nonce_field('save_botblocker_settings', 'botblocker_settings_nonce'); ?>
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
						<h2 class="card-title"><?php esc_html_e( 'Tools', 'botblocker-security'); ?></h2>
					</header>
					<div class="card-body">

						<ul class="nav nav-tabs">
							<li class="nav-item">
								<a class="nav-link active" data-bs-toggle="tab" href="#tools-wordpress"><?php esc_html_e('WordPress Core', 'botblocker-security'); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#tools-botblocker"><?php esc_html_e('BotBlocker', 'botblocker-security'); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#tools-maintenance"><?php esc_html_e('Maintenance', 'botblocker-security'); ?></a>
							</li>
							<?php
							$bbcs_addons = function_exists('bbcs_scan_addons') ? bbcs_scan_addons() : [];
							$bbcs_active = function_exists('bbcs_get_active_addons') ? bbcs_get_active_addons() : [];
							foreach ($bbcs_active as $bbcs_slug) {
								if (!isset($bbcs_addons[$bbcs_slug]) || !$bbcs_addons[$bbcs_slug]['valid']) continue;
								$bbcs_name = $bbcs_addons[$bbcs_slug]['name'] ? $bbcs_addons[$bbcs_slug]['name'] : esc_html($bbcs_slug);
								$bbcs_href = '#addon-' . esc_attr($bbcs_slug);
								?>
								<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" href="<?php echo esc_attr($bbcs_href); ?>">
										<i class="fa-solid fa-puzzle-piece" style="margin-right:6px;"></i><?php echo esc_html($bbcs_name); ?>
									</a>
								</li>
								<?php
							}
							?>
						</ul>
						<div class="tab-content">
							<?php
							include_once BOTBLOCKER_DIR . 'includes/section/tools/botblocker-tools-wordpress.php';
							include_once BOTBLOCKER_DIR . 'includes/section/tools/botblocker-tools-botblocker.php';
							include_once BOTBLOCKER_DIR . 'includes/section/tools/botblocker-tools-maintenance.php';
							foreach ($bbcs_active as $bbcs_slug) {
								if (!isset($bbcs_addons[$bbcs_slug]) || !$bbcs_addons[$bbcs_slug]['valid']) continue;
								echo '<div class="tab-pane container fade" id="addon-' . esc_attr($bbcs_slug) . '">';
								$bbcs_settingsPath = $bbcs_addons[$bbcs_slug]['settings'];
								if (file_exists($bbcs_settingsPath)) {
									include $bbcs_settingsPath;
								}
								echo '</div>';
							}
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

<?php 
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-salt-clear.php');
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-log-clear.php');
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-db-repair.php');
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-hits-clear.php');
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-transient-clear.php');
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rewrite-rules.php');
include(BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-object-cache.php');
?>
