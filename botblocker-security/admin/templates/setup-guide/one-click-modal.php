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
						<h3><?php esc_html_e( 'Light Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag"><?php esc_html_e( 'Basic', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Maximum compatibility for testing and low-impact protection.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Minimal protection', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Maximum compatibility', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Low resource usage', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--block bbcs-apply-profile" type="button" data-mode="light">
						<span class="bbcs-btn-text"><?php esc_html_e( 'Apply now', 'botblocker-security' ); ?></span>
						<span class="bbcs-oneclick-spinner spinner-border d-none" role="status" aria-hidden="true"></span>
					</button>
				</article>
				<article class="bbcs-profile-choice bbcs-oneclick-preset bbcs-oneclick-preset--strong" data-mode="strong">
					<div class="bbcs-oneclick-preset-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
					<div class="bbcs-oneclick-preset-head">
						<h3><?php esc_html_e( 'Strong Protection', 'botblocker-security' ); ?></h3>
						<span class="bbcs-tag bbcs-tag--blue"><?php esc_html_e( 'Recommended', 'botblocker-security' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Balanced security and compatibility with safe defaults.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Blocks common threats', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Safe defaults', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'One-click activation', 'botblocker-security' ); ?></li>
					</ul>
					<button class="bbcs-btn bbcs-btn--pri bbcs-btn--block bbcs-apply-profile" type="button" data-mode="strong">
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
					<p><?php esc_html_e( 'Maximum hardening with cloud threat intelligence.', 'botblocker-security' ); ?></p>
					<ul class="bbcs-oneclick-features">
						<li><?php esc_html_e( 'Advanced bot detection', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Cloud threat intelligence', 'botblocker-security' ); ?></li>
						<li><?php esc_html_e( 'Strongest protection mode', 'botblocker-security' ); ?></li>
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
				<article class="bbcs-oneclick-preset bbcs-oneclick-preset--wizard">
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
