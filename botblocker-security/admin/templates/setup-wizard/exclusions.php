<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="3">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Who always gets access?', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Trusted users and systems that bypass checks', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizexcl">
			<div class="bbcs-wizcheck is-on" data-key="exclude-admins">
				<div class="bbcs-wizcheck-box"></div>
				<div class="bbcs-wizcheck-body">
					<div class="bbcs-wizcheck-title"><?php esc_html_e( 'Allow administrator login', 'botblocker-security' ); ?></div>
					<div class="bbcs-wizcheck-desc"><?php esc_html_e( 'Admins will never be blocked while authenticated', 'botblocker-security' ); ?></div>
				</div>
			</div>
			<div class="bbcs-wizcheck is-on" data-key="exclude-current-ip">
				<div class="bbcs-wizcheck-box"></div>
				<div class="bbcs-wizcheck-body">
					<div class="bbcs-wizcheck-title"><?php esc_html_e( 'Trust your current IP', 'botblocker-security' ); ?> <span class="bbcs-dim bbcs-mono" id="bbcs-wiz-myip"><?php echo esc_html( $d->current_ip ); ?></span></div>
					<div class="bbcs-wizcheck-desc"><?php esc_html_e( 'Your IP address will be permanently added to the whitelist', 'botblocker-security' ); ?></div>
				</div>
			</div>
			<div class="bbcs-wizcheck is-on" data-key="exclude-cron">
				<div class="bbcs-wizcheck-box"></div>
				<div class="bbcs-wizcheck-body">
					<div class="bbcs-wizcheck-title"><?php esc_html_e( 'Allow WordPress Cron and server requests', 'botblocker-security' ); ?></div>
					<div class="bbcs-wizcheck-desc"><?php esc_html_e( 'Required for scheduled tasks and backups', 'botblocker-security' ); ?></div>
				</div>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-4h bbcs-blue-card">
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'Tip:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'Auto-save administrator IPs is available in Settings. Role-based 2FA is in Integrations.', 'botblocker-security' ); ?></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back3"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next3"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
		</div>
	</div>
<?php };
