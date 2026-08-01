<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="5">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Initialization mode', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'When BotBlocker activates. Earlier = stronger protection.', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizcards">
			<div class="bbcs-wizcard is-sel" data-init="regular">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-plug"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Regular plugin', 'botblocker-security' ); ?> <span class="bbcs-pill bbcs-pill--green bbcs-pill--pro"><?php esc_html_e( 'Default', 'botblocker-security' ); ?></span></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Loads with standard plugins. Suitable for most sites.', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Standard loading', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Compatible with all configurations', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'No special setup required', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard" data-init="mu">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-bolt"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'MU-plugin', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Loads before regular plugins. Faster threat response.', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Activates before other plugins', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'IP whitelists/blacklists work earlier', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Improved login page protection', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard bbcs-wizcard--pro" data-init="early">
				<?php if ( ! $d->early_available ) : ?>
				<div class="bbcs-wizcard-pro-overlay">
					<?php if ( ! $d->has_pro ) : ?>
					<div class="bbcs-wizcard-pro-badge"><svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-lock"></use></svg> <?php esc_html_e( 'PRO only', 'botblocker-security' ); ?></div>
					<a class="bbcs-wizcard-pro-link" href="<?php echo esc_url( $d->pro_url ); ?>"><?php esc_html_e( 'View plans', 'botblocker-security' ); ?></a>
					<?php else : ?>
					<div class="bbcs-wizcard-pro-badge"><svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-lock"></use></svg> <?php esc_html_e( 'Addon required', 'botblocker-security' ); ?></div>
					<a class="bbcs-wizcard-pro-link" href="<?php echo esc_url( $d->addons_url . '&focus=bbcs-early-init' ); ?>"><?php esc_html_e( 'Enable Early Init addon', 'botblocker-security' ); ?></a>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-crown"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Early Init', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--xs bbcs-tx-amber"><use href="#bbcs-i-crown"></use></svg></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Blocks threats before WordPress loads. Maximum security.', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Instant IP blocking', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Protection before WP starts', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Minimal server load', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Maximum protection level', 'botblocker-security' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-4h bbcs-blue-card">
			<?php $rules_url = $d->rules_url; ?>
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'Tip:', 'botblocker-security' ); ?></b> <?php printf( /* translators: %s: URL to Rules settings page */ esc_html__( 'Early Init provides instant IP bans. Custom IPv4/IPv6 lists can be uploaded in any mode - see %s.', 'botblocker-security' ), '<a class="bbcs-link" href="' . esc_url( $rules_url ) . '">' . esc_html__( 'Rules', 'botblocker-security' ) . '</a>' ); ?></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back5"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next5"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
		</div>
	</div>
<?php };
