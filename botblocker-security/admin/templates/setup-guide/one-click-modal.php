<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( $data ): void {
	$has_pro = $data->has_pro;
	?>
<div class="bbcs-oneclick-modal" id="bbcsOneClickSetupModal" hidden aria-hidden="true" data-pro="<?php echo $has_pro ? '1' : '0'; ?>">
	<div class="bbcs-oneclick-modal-backdrop" data-bbcs-oneclick-close></div>
	<section class="bbcs-oneclick-dialog" role="dialog" aria-modal="true" aria-labelledby="bbcsOneClickSetupLabel">
		<header class="bbcs-oneclick-header">
			<div class="bbcs-oneclick-heading">
				<span class="bbcs-oneclick-heading-icon"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span>
				<div>
					<h2 class="bbcs-oneclick-title" id="bbcsOneClickSetupLabel"><?php esc_html_e( 'One-Click Security Setup', 'botblocker-security' ); ?></h2>
					<p class="bbcs-oneclick-subtitle"><?php esc_html_e( 'Choose the protection level that fits your site.', 'botblocker-security' ); ?></p>
				</div>
			</div>
			<button class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon bbcs-oneclick-close" type="button" data-bbcs-oneclick-close aria-label="<?php esc_attr_e( 'Close', 'botblocker-security' ); ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
		</header>
		<div class="bbcs-oneclick-body">
			<div class="bbcs-oneclick-grid">
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--light" data-mode="light">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-feather" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Balanced Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag bbcs-tag--blue"><?php esc_html_e( 'Recommended', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Safe defaults for shops, forms and integrations.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'No ad-blocker or proxy checks', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Payment callbacks allowed', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Low impact on visitors', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--pri bbcs-btn--block bbcs-apply-profile" type="button" data-mode="light">
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
				</article>
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--medium" data-mode="medium">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Medium Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag"><?php esc_html_e( 'Standard', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Extra bot checks with a lighter touch than Strong.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Fake-referrer and honeypot checks', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Rate limit 80 / 40 per minute', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'No ad-blocker or anti-detect checks', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--block bbcs-apply-profile" type="button" data-mode="medium">
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
				</article>
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--strong" data-mode="strong">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Strong Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag"><?php esc_html_e( 'Strict', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Strict rules for sites under attack. Keep an allowlist for APIs and clients without Accept-Language.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Ad-blocker and anti-detect checks', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Cloudflare-origin blocking', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Rate limit 50 / 30 per minute', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--block bbcs-apply-profile" type="button" data-mode="strong">
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
				</article>
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--strong-api" data-mode="strong_api">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-plug" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Strong API Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag"><?php esc_html_e( 'API', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Strong rules that keep REST and automation clients working.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Allows empty Accept-Language', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'No admin-IP auto-allowlist', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'For MCP and monitoring agents', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--block bbcs-apply-profile" type="button" data-mode="strong_api">
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
				</article>
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--full" data-mode="full">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-shield" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Full Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e( 'PRO', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Maximum hardening for sites that need the strictest rules.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'VPN, Tor and hosting blocks', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Device and engine checks', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Strictest rate limits', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--block bbcs-apply-profile" type="button" data-mode="full"<?php disabled( ! $has_pro ); ?>>
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
					<?php if ( ! $has_pro ) : ?>
						<div class="bbcs-oneclick-pro-lock">
							<i class="fa-solid fa-lock" aria-hidden="true"></i>
							<strong><?php esc_html_e( 'Requires PRO', 'botblocker-security' ); ?></strong>
							<a class="bbcs-btn bbcs-btn--amber bbcs-btn--sm" href="<?php echo esc_url( $data->cloud_api_url ); ?>"><?php esc_html_e( 'Get PRO', 'botblocker-security' ); ?></a>
						</div>
					<?php endif; ?>
				</article>
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--emergency" data-mode="emergency">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Emergency Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag bbcs-tag--amber"><?php esc_html_e( 'PRO', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Temporary lockdown while an attack is in progress.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Rate limit 20 / 10 per minute', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Server DDoS resilience on', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Re-apply Strong after the attack', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--block bbcs-apply-profile" type="button" data-mode="emergency"<?php disabled( ! $has_pro ); ?>>
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
					<?php if ( ! $has_pro ) : ?>
						<div class="bbcs-oneclick-pro-lock">
							<i class="fa-solid fa-lock" aria-hidden="true"></i>
							<strong><?php esc_html_e( 'Requires PRO', 'botblocker-security' ); ?></strong>
							<a class="bbcs-btn bbcs-btn--amber bbcs-btn--sm" href="<?php echo esc_url( $data->cloud_api_url ); ?>"><?php esc_html_e( 'Get PRO', 'botblocker-security' ); ?></a>
						</div>
					<?php endif; ?>
				</article>
			</div>
			<article class="bbcs-oneclick-preset bbcs-oneclick-preset--wizard bbcs-oneclick-wizardbar">
				<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></div>
				<div class="bbcs-oneclick-preset-head">
					<h3><?php esc_html_e( 'Guided Setup', 'botblocker-security' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Fine-tune every protection option in the full setup wizard.', 'botblocker-security' ); ?></p>
				<ul class="bbcs-oneclick-features">
					<li><?php esc_html_e( 'Compatibility tests', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Configure exclusions', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Review security score', 'botblocker-security' ); ?></li>
				</ul>
				<a class="bbcs-btn bbcs-btn--amber bbcs-btn--block" href="<?php echo esc_url( $data->wizard_url ); ?>"><?php esc_html_e( 'Start wizard', 'botblocker-security' ); ?></a>
			</article>
			</div>
		</div>
	</section>
</div>
	<?php
};
