<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use BotBlocker\Component\Base;
?><div class="modal fade" id="createProxyModal" tabindex="-1" aria-labelledby="createProxyModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="createProxyModalLabel"><?php esc_html_e( 'Add Proxy Rule', 'botblocker-security' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="createProxyForm">
					<div class="row mb-3">
						<div class="col-md-12">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Network Mask', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'Enter the network mask (e.g., 173.245.48.0/20)', 'botblocker-security' ), BotBlockerDocs::url( 'proxy-network-mask' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="text" class="bbcs_text_input_input" id="key" name="key" required placeholder="e.g., 173.245.48.0/20">
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-12">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'HTTP Header', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'HTTP header for forwarding the real IP', 'botblocker-security' ), BotBlockerDocs::url( 'proxy-http-header' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<select class="bbcs_select_input_input" id="value" name="value" required>
										<option value="HTTP_CF_CONNECTING_IP">HTTP_CF_CONNECTING_IP</option>
										<option value="HTTP_X_FORWARDED_FOR">HTTP_X_FORWARDED_FOR</option>
										<option value="HTTP_X_REAL_IP">HTTP_X_REAL_IP</option>
										<option value="HTTP_CLIENT_IP">HTTP_CLIENT_IP</option>
										<option value="HTTP_FORWARDED">HTTP_FORWARDED</option>
										<option value="HTTP_VIA">HTTP_VIA</option>
										<option value="HTTP_TRUE_CLIENT_IP">HTTP_TRUE_CLIENT_IP</option>
										<option value="HTTP_FASTLY_CLIENT_IP">HTTP_FASTLY_CLIENT_IP</option>
										<option value="HTTP_X_PROXYUSER_IP">HTTP_X_PROXYUSER_IP</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-12">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Comment', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'Comment for this proxy (e.g., Cloudflare IPv4)', 'botblocker-security' ), BotBlockerDocs::url( 'proxy-comment' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="text" class="bbcs_text_input_input" id="comment" name="comment" placeholder="e.g., Cloudflare IPv4">
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
				<button type="submit" form="createProxyForm" class="btn btn-primary btn-xs"><?php esc_html_e( 'Add', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
</div>
