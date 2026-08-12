<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\ToggleOption;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
	$mu_on = (bool) $data->mu_active;
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="mu"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/tools.svg', __( 'MU-plugin', 'botblocker-security' ) )
			->withDescription( __( 'MU-plugin mode loads BotBlocker IP protection before regular plugins and WordPress core initialise.', 'botblocker-security' ) )
			->withDescription( __( 'A small loader file is installed into wp-content/mu-plugins and pulls the main plugin code on every request.', 'botblocker-security' ) )
			->withDocLink( 'https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/', __( 'Must-Use Plugins (WordPress.org)', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'MU-plugin', 'botblocker-security' ); ?></div>
				<div class="bbcs-option bbcs-hoverbg">
					<?php
					ToggleOption::make()
						->withName( 'mu_enable' )
						->withChecked( $mu_on )
						->withLabel( __( 'Enable MU-plugin', 'botblocker-security' ) )
						->withTooltip( __( 'Loads black/white IP lists before regular plugins and WordPress core', 'botblocker-security' ) )
						->withAjax( 'bbcs_toggle_early_phase_in_db', 'mu_enable' )
						->render();
					?>
				</div>
				<?php if ( $data->early_active ) : ?>
					<div class="bbcs-infocol-note bbcs-infocol-note--warn">
						<strong><?php esc_html_e( 'Early Init is enabled', 'botblocker-security' ); ?>:</strong>
						<?php esc_html_e( 'It currently takes precedence. Enabling the MU-plugin switches the protection layer to MU mode.', 'botblocker-security' ); ?>
					</div>
				<?php endif; ?>
				<div class="bbcs-option bbcs-hoverbg">
					<?php
					ToggleOption::make()
						->withName( 'mu_geo_enable' )
						->withChecked( (bool) $data->mu_geo_active )
						->withLabel( __( 'Country blocking in the MU layer', 'botblocker-security' ) )
						->withTooltip( __( 'Rejects visitors from the blocked-country list before plugins load. Off by default - any lookup uncertainty passes traffic to the main plugin.', 'botblocker-security' ) )
						->withAjax( 'bbcs_toggle_early_phase_in_db', 'mu_geo_enable' )
						->render();
					?>
				</div>
				<div class="bbcs-fs-xs bbcs-dim bbcs-mb-2">
					<?php esc_html_e( 'Manage the blocked-country list on the', 'botblocker-security' ); ?>
					<a class="bbcs-link" href="<?php echo esc_url( BotBlockerMultisite::getAdminPageUrl( 'bbcs_rules' ) ); ?>#geo"><?php esc_html_e( 'Rules page - GEO tab', 'botblocker-security' ); ?></a>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e( 'MU loader file', 'botblocker-security' ); ?></div>
					<div class="bbcs-field-box">
						<code class="bbcs-input bbcs-input--mono"><?php echo esc_html( $data->mu_loader_path ); ?></code>
						<span class="bbcs-pill<?php echo $data->mu_loader_exists ? ' bbcs-pill--green' : ' bbcs-pill--red'; ?>" id="bbcs-mu-loader-pill" data-present="<?php echo $data->mu_loader_exists ? '1' : '0'; ?>">
							<?php echo $data->mu_loader_exists ? esc_html__( 'present', 'botblocker-security' ) : esc_html__( 'missing', 'botblocker-security' ); ?>
						</span>
					</div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e( 'Protection data files', 'botblocker-security' ); ?></div>
					<div class="bbcs-field-box bbcs-fs-xs bbcs-dim">
						<?php echo esc_html( $data->data_dir ); ?>
						<br />
						<?php esc_html_e( 'IP list', 'botblocker-security' ); ?> (ip.php): <?php echo $data->ip_file_exists ? esc_html__( 'present', 'botblocker-security' ) : esc_html__( 'missing', 'botblocker-security' ); ?>
						&middot;
						<?php esc_html_e( 'Hot bans', 'botblocker-security' ); ?> (hot-bans.php): <?php echo $data->hotbans_file_exists ? esc_html__( 'present', 'botblocker-security' ) : esc_html__( 'missing', 'botblocker-security' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
};
