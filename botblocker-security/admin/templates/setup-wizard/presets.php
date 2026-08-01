<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="1">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Choose protection level', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Can be changed at any time with one click', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizcards">
			<div class="bbcs-wizcard" data-preset="light">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-sparkle"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Light protection', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Minimal impact, works on any site', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Basic bot blocking', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Zero impact on visitors', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Ideal for testing', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Compatible with all plugins', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard is-sel" data-preset="strong">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-shieldCheck"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Strong protection', 'botblocker-security' ); ?> <span class="bbcs-pill bbcs-pill--green bbcs-pill--pro"><?php esc_html_e( 'Recommended', 'botblocker-security' ); ?></span></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Optimal balance of security and convenience', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Advanced threat detection', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Block most known bots', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Real visitors unaffected', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Recommended for most sites', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard bbcs-wizcard--pro" data-preset="full">
				<?php if ( ! $d->has_pro ) : ?>
				<div class="bbcs-wizcard-pro-overlay">
					<div class="bbcs-wizcard-pro-badge"><svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-lock"></use></svg> <?php esc_html_e( 'PRO only', 'botblocker-security' ); ?></div>
					<a class="bbcs-wizcard-pro-link" href="<?php echo esc_url( $d->pro_url ); ?>"><?php esc_html_e( 'View plans', 'botblocker-security' ); ?></a>
				</div>
				<?php endif; ?>
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-crown"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Full protection', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--xs bbcs-tx-amber"><use href="#bbcs-i-crown"></use></svg></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Maximum protection with PRO features', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Early Init - before WP loads', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Zero-day botnet updates', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'WordPress acceleration', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'All addons included', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( '5M+ bot signatures', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Emergency support (24h)', 'botblocker-security' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-4h">
			<div class="bbcs-row"><span class="bbcs-tx-green bbcs-fw-bold bbcs-fs-sm"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-check"></use></svg> <?php esc_html_e( '"Recommended" protection active', 'botblocker-security' ); ?></span></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back1"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-apply-preset"><?php esc_html_e( 'Apply preset', 'botblocker-security' ); ?></button>
		</div>
	</div>
<?php };
