<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="1">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Choose protection level', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Can be changed at any time with one click', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizcards">
			<div class="bbcs-wizcard is-sel" data-preset="light">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-sparkle"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Balanced protection', 'botblocker-security' ); ?> <span class="bbcs-pill bbcs-pill--green bbcs-pill--pro"><?php esc_html_e( 'Recommended', 'botblocker-security' ); ?></span></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Safe defaults for shops, forms and integrations', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Blocks simple bots', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'No ad-blocker or proxy checks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Payment callbacks allowed', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Works with most plugins', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard" data-preset="medium">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-shield"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Medium protection', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'More checks than Balanced, less friction than Strong', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Fake-referrer and honeypot checks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Rate limit 80 / 40 per minute', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'No ad-blocker or anti-detect checks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'For privacy-minded audiences', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard" data-preset="strong">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-shieldCheck"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Strong protection', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Strict protection for sites under attack', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Advanced threat detection', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Ad-blocker and anti-detect checks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Cloudflare-origin blocking', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Needs an allowlist for APIs', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard" data-preset="strong_api">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-shieldCheck"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Strong API protection', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Strong rules without breaking API clients and MCP agents', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Allows empty Accept-Language', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Hides user enumeration', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'No admin-IP auto-allowlist', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'For sites with REST, monitoring or MCP', 'botblocker-security' ); ?></li>
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
					<li><?php esc_html_e( 'VPN, Tor and hosting blocks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Private-browsing and device checks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Cloud and unresponsive checks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Strictest rate limits', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Massive bot signature database', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard bbcs-wizcard--pro" data-preset="emergency">
				<?php if ( ! $d->has_pro ) : ?>
				<div class="bbcs-wizcard-pro-overlay">
					<div class="bbcs-wizcard-pro-badge"><svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-lock"></use></svg> <?php esc_html_e( 'PRO only', 'botblocker-security' ); ?></div>
					<a class="bbcs-wizcard-pro-link" href="<?php echo esc_url( $d->pro_url ); ?>"><?php esc_html_e( 'View plans', 'botblocker-security' ); ?></a>
				</div>
				<?php endif; ?>
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-bolt"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Emergency protection', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Temporary lockdown during an attack', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Rate limit 20 / 10 per minute', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'VPN, Tor and hosting blocks', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Server DDoS resilience on', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Re-apply Strong after the attack', 'botblocker-security' ); ?></li>
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
