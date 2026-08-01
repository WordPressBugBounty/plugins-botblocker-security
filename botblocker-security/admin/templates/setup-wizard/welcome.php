<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep is-active" data-step="0">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Welcome to BotBlocker', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Protection from bots, scanners and brute-force in ', 'botblocker-security' ); ?><b class="bbcs-tx"><?php esc_html_e( '30 seconds', 'botblocker-security' ); ?></b></p>

		<div class="bbcs-wizfeats">
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-shieldCheck"></use></svg></span><?php esc_html_e( 'Block brute-force, scanners and scrapers before WordPress', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-globe"></use></svg></span><?php esc_html_e( 'No DNS/NS required: works inside WordPress, no external services needed', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-eye"></use></svg></span><?php esc_html_e( 'Real-time visitor statistics (IP, PTR, ASN, GEO, OS)', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-sparkle"></use></svg></span><?php esc_html_e( 'Recommended preset in one click (~30 seconds)', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-ban"></use></svg></span><?php esc_html_e( 'Block hosting bots, proxies and Tor', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-bolt"></use></svg></span><?php esc_html_e( 'Reduce server load - junk is blocked before WP loads', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-bot"></use></svg></span><?php esc_html_e( 'Search engine verification, SEO protection from fake crawlers', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-lock"></use></svg></span><?php esc_html_e( 'Early protection: bad requests are rejected before WordPress loads', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-chart"></use></svg></span><?php esc_html_e( 'What was blocked and why: reason + URL + IP', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-sliders"></use></svg></span><?php esc_html_e( 'Custom rules: IP, User-Agent, PTR, country, paths and headers', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-fly"></use></svg></span><?php esc_html_e( 'Daily cloud threat updates (signatures + networks)', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizfeat"><span class="bbcs-wizfeat-ic"><svg class="bbcs-ico"><use href="#bbcs-i-star"></use></svg></span><?php esc_html_e( 'Safe defaults: compatible with WooCommerce, Elementor, caches', 'botblocker-security' ); ?></div>
		</div>

		<?php if ( ! $d->contact_collected ) : ?>
		<div class="bbcs-wizemail">
			<div class="bbcs-wizemail-title"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-fly"></use></svg> <?php esc_html_e( 'Security news and offers', 'botblocker-security' ); ?></div>
			<input id="bbcs-wiz-contact-email" class="bbcs-wizemail-input" type="email" value="<?php echo esc_attr( $d->contact_email ); ?>" placeholder="you@example.com" autocomplete="email" />
			<div class="bbcs-wizemail-hint"><?php esc_html_e( 'For important security notifications and special offers.', 'botblocker-security' ); ?></div>
		</div>
		<?php endif; ?>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-start"><?php esc_html_e( 'Start setup', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-skip"><?php esc_html_e( 'Skip (use defaults)', 'botblocker-security' ); ?></button>
		</div>
	</div>
<?php };
