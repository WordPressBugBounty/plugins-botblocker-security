<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

require 'botblocker-section-header.php';

?><section role="main" class="content-body">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'save_botblocker_tools', 'botblocker_tools_nonce' ); ?>
		<input type="hidden" name="action" value="save_botblocker_tools">
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
						<h2 class="card-title"><?php esc_html_e( 'Tools', 'botblocker-security' ); ?></h2>
					</header>
					<div class="card-body">

						<ul class="nav nav-tabs">
							<li class="nav-item">
								<a class="nav-link active" data-bs-toggle="tab" href="#tools-wordpress"><?php esc_html_e( 'WordPress Core', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#tools-botblocker"><?php esc_html_e( 'BotBlocker', 'botblocker-security' ); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#tools-maintenance"><?php esc_html_e( 'Maintenance', 'botblocker-security' ); ?></a>
							</li>
							<?php
							$bbcs_addons = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::scanAll() : array();
							$bbcs_active = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::getActive() : array();
							foreach ( $bbcs_active as $bbcs_slug ) {
								if ( ! isset( $bbcs_addons[ $bbcs_slug ] ) || ! $bbcs_addons[ $bbcs_slug ]['valid'] ) {
									continue;
								}
								if ( empty( $bbcs_addons[ $bbcs_slug ]['has_settings'] ) ) {
									continue;
								}
								$bbcs_name = $bbcs_addons[ $bbcs_slug ]['name'] ? $bbcs_addons[ $bbcs_slug ]['name'] : esc_html( $bbcs_slug );
								$bbcs_href = '#addon-' . esc_attr( $bbcs_slug );
								?>
								<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" href="<?php echo esc_attr( $bbcs_href ); ?>">
										<i class="fa-solid fa-puzzle-piece" style="margin-right:6px;"></i><?php echo esc_html( $bbcs_name ); ?>
									</a>
								</li>
								<?php
							}
							?>
						</ul>
						<div class="tab-content">
							<?php
							require_once BOTBLOCKER_DIR . 'includes/section/tools/botblocker-tools-wordpress.php';
							require_once BOTBLOCKER_DIR . 'includes/section/tools/botblocker-tools-botblocker.php';
							require_once BOTBLOCKER_DIR . 'includes/section/tools/botblocker-tools-maintenance.php';

							foreach ( $bbcs_active as $bbcs_slug ) {
								if ( ! isset( $bbcs_addons[ $bbcs_slug ] ) || ! $bbcs_addons[ $bbcs_slug ]['valid'] ) {
									continue;
								}
								if ( empty( $bbcs_addons[ $bbcs_slug ]['has_settings'] ) ) {
									continue;
								}
								echo '<div class="tab-pane container fade" id="addon-' . esc_attr( $bbcs_slug ) . '">';
								$bbcs_settingsPath = $bbcs_addons[ $bbcs_slug ]['settings'];
								if ( file_exists( $bbcs_settingsPath ) ) {
									include $bbcs_settingsPath;
								}
								echo '</div>';
							}
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

<?php
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-salt-clear.php';
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-log-clear.php';
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-db-repair.php';
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-hits-clear.php';
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-transient-clear.php';
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rewrite-rules.php';
require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-object-cache.php';
?>
