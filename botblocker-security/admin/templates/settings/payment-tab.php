<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;

$bbcs_payment_md = '';
$bbcs_payment_md_path = BOTBLOCKER_DIR . 'docs/PAYMENT-BYPASS.md';
if ( file_exists( $bbcs_payment_md_path ) ) {
	$bbcs_payment_md = (string) file_get_contents( $bbcs_payment_md_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

return static function (Botblocker_SettingsViewModel $data, bool $isActive) use ( $bbcs_payment_md ): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="payment"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/tarifs.svg', __( 'Payment Gateways', 'botblocker-security' ) )
			->withDescription( __( 'Bypass BotBlocker checks for incoming payment gateway callbacks (webhooks / IPN / postbacks) so legitimate payment notifications are never blocked.', 'botblocker-security' ) )
			->withDescription( __( 'Covered: WooCommerce wc-api / wc-ajax, WC REST, EDD, Give, Memberpress, RCP, PMPro, Surecart, plus payment-specific paths and admin-ajax actions for Stripe, PayPal, Mollie, Adyen, Braintree, Square, Razorpay, CloudPayments, WayForPay, LiqPay, Fondy, PayU, Klarna, Paystack, Flutterwave, GoCardless, Paddle, Authorize.Net, 2Checkout and many others.', 'botblocker-security' ) )
			->withDescription( __( 'Only safe HTTP methods (GET, POST, HEAD) are bypassed. Hard IP blacklists and country blocks still apply.', 'botblocker-security' ) )
			->withDocLink( 'https://en.wikipedia.org/wiki/Webhook', __( 'Webhook', 'botblocker-security' ) )
			->withDocLink( 'https://en.wikipedia.org/wiki/Instant_payment_notification', __( 'IPN', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Payment Gateways', 'botblocker-security' ) )
				->withItems( static function () use ( $data ): void {
					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'payment_bypass_enable' )->withChecked( $data->is_checked( 'payment_bypass_enable' ) )->withLabel( __( 'Allow Payment Gateway Callbacks', 'botblocker-security' ) )->withTooltip( __( 'When enabled, requests matching well-known payment callback patterns (paths, query keys, admin-ajax actions, signature headers) skip JS-challenge, Captcha and rate-limit. Recommended for any site with WooCommerce or another e-commerce plugin.', 'botblocker-security' ) )->render();
							ToggleOption::make()->withName( 'payment_bypass_log' )->withChecked( $data->is_checked( 'payment_bypass_log' ) )->withLabel( __( 'Log Payment Bypass Events', 'botblocker-security' ) )->withTooltip( __( 'Write a log entry every time a payment callback bypass is applied. Useful for auditing.', 'botblocker-security' ) )->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'payment_strict_method' )->withChecked( $data->is_checked( 'payment_strict_method' ) )->withLabel( __( 'Strict Webhook Validation (POST only)', 'botblocker-security' ) )->withTooltip( __( 'Signature headers are always verified for format (min 8 chars, POST only). When enabled, payment bypass additionally applies to POST requests only: GET requests with payment query keys or ajax actions will not bypass the shield. Callbacks matched by generic markers (webhook, ipn, callback_handler) always keep IP and rate-limit rules active. Reduces attack surface while keeping real webhooks working.', 'botblocker-security' ) )->render();
							ToggleOption::make()->withName( 'payment_keep_ip_rules' )->withChecked( $data->is_checked( 'payment_keep_ip_rules' ) )->withLabel( __( 'Enforce IP / ASN Rules for Payment Callbacks', 'botblocker-security' ) )->withTooltip( __( 'When enabled, payment callbacks still skip Captcha but IP blacklists, ASN rules, path rules and country blocks remain active. A blocked IP cannot use a payment callback to bypass the block.', 'botblocker-security' ) )->render();
						} )
						->render();
					?>
					<div class="bbcs-option bbcs-hoverbg">
						<button type="button" class="bbcs-btn" id="bbcs-payment-bypass-guide-trigger">
							<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
							<?php esc_html_e( 'Open PAYMENT-BYPASS.md', 'botblocker-security' ); ?>
						</button>
						<span class="bbcs-option-label"><?php esc_html_e( 'Full guide: recognition layers, bypass modes and hardening recommendations for payment callbacks.', 'botblocker-security' ); ?></span>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/PAYMENT-BYPASS.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
					</div>
					<?php
				} )
				->render();

			if ($data->has_ecommerce) : ?>
				<div class="bbcs-card bbcs-card-pad bbcs-amber-card">
					<div class="bbcs-row bbcs-g-2h">
						<svg class="bbcs-ico bbcs-ico--md bbcs-tx-amber">
							<use href="#bbcs-i-warning"></use>
						</svg>
						<span class="bbcs-fs-xs bbcs-tx-amber"><?php esc_html_e('E-commerce software detected on this site. Enabling this option is strongly recommended.', 'botblocker-security'); ?></span>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="bbcs-modal-overlay" id="bbcsPaymentBypassModal" style="display:none;">
		<div class="bbcs-modal bbcs-modal--wide">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title">
					<svg class="bbcs-ico bbcs-ico--sm" style="margin-right:var(--bbcs-sp-1);"><use href="#bbcs-i-doc"></use></svg>
					<?php esc_html_e( 'PAYMENT-BYPASS.md', 'botblocker-security' ); ?>
				</div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<pre class="bbcs-md-view"><?php echo esc_html( $bbcs_payment_md ); ?></pre>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>

	<script>
	(function() {
		'use strict';
		var trigger = document.getElementById('bbcs-payment-bypass-guide-trigger');
		var overlay = document.getElementById('bbcsPaymentBypassModal');
		if (!trigger || !overlay) return;

		trigger.addEventListener('click', function(e) {
			e.preventDefault();
			overlay.style.display = 'flex';
		});

		overlay.addEventListener('click', function(e) {
			var btn = e.target.closest('[data-modal-close]');
			if (btn) {
				overlay.style.display = 'none';
				return;
			}
			if (e.target === overlay) {
				overlay.style.display = 'none';
			}
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && overlay.style.display === 'flex') {
				overlay.style.display = 'none';
			}
		});
	})();
	</script>
<?php
};
