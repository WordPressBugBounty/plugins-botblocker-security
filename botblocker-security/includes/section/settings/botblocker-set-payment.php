<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="tab-pane container fade" id="payment">
	<div class="row">
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: Static plugin asset, not user-uploaded.
				// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/tarifs.svg' ); ?>"
					alt="<?php esc_attr_e( 'Payment Gateways', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Bypass BotBlocker checks for incoming payment gateway callbacks (webhooks / IPN / postbacks) so legitimate payment notifications are never blocked.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Covered: WooCommerce wc-api / wc-ajax, WC REST, EDD, Give, Memberpress, RCP, PMPro, Surecart, plus payment-specific paths and admin-ajax actions for Stripe, PayPal, Mollie, Adyen, Braintree, Square, Razorpay, YooKassa, Tinkoff, CloudPayments, Robokassa, Sberbank, WayForPay, LiqPay, Fondy, PayU, Klarna, Paystack, Flutterwave, GoCardless, Paddle, Authorize.Net, 2Checkout and many others.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Only safe HTTP methods (GET, POST, HEAD) are bypassed. Hard IP blacklists and country blocks still apply.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://en.wikipedia.org/wiki/Webhook" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Webhook', 'botblocker-security' ); ?></a>
					<a href="https://en.wikipedia.org/wiki/Instant_payment_notification" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'IPN', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Payment Gateways', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="payment_bypass_enable" value="1" <?php checked( 1, isset( $bbcs_settings['payment_bypass_enable'] ) ? (int) $bbcs_settings['payment_bypass_enable'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Allow Payment Gateway Callbacks', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'When enabled, requests matching well-known payment callback patterns (paths, query keys, admin-ajax actions, signature headers) skip JS-challenge, CAPTCHA and rate-limit. Recommended for any site with WooCommerce or another e-commerce plugin.', 'botblocker-security' ); ?>"></i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="payment_bypass_log" value="1" <?php checked( 1, isset( $bbcs_settings['payment_bypass_log'] ) ? (int) $bbcs_settings['payment_bypass_log'] : 1 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Log Payment Bypass Events', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Write a log entry every time a payment callback bypass is applied. Useful for auditing.', 'botblocker-security' ); ?>"></i>
			</div>

			<?php if ( bbcs_payment_detect_ecommerce() ) : ?>
				<div class="alert alert-info p-2 mt-2 mb-0" role="status">
					<i class="fa-solid fa-shopping-cart me-1"></i>
					<?php esc_html_e( 'E-commerce software detected on this site. Enabling this option is strongly recommended.', 'botblocker-security' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
