<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use BotBlocker\Component\Base;
?><div class="modal fade" id="editIPv6Modal" tabindex="-1" aria-labelledby="editIPv6ModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editIPv6ModalLabel"><?php esc_html_e( 'Edit IPv6 Rule', 'botblocker-security' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="editIPv6Form">
					<input type="hidden" name="id">
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Priority', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'Priority (1-100)', 'botblocker-security' ), BotBlockerDocs::url( 'ipv6-priority' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="number" class="bbcs_text_input_input" id="priority" name="priority" min="1" max="100" required>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'IP Address', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'IPv6 address or subnet (CIDR)', 'botblocker-security' ), BotBlockerDocs::url( 'ipv6-ip-address' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="text" class="bbcs_text_input_input" id="ip" name="ip" required>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Rule', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'Allow or block this IP/subnet', 'botblocker-security' ), BotBlockerDocs::url( 'ipv6-rule' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<select class="bbcs_select_input_input" id="rule" name="rule" required>
										<option value="allow"><?php esc_html_e( 'Allow', 'botblocker-security' ); ?></option>
										<option value="block"><?php esc_html_e( 'Block', 'botblocker-security' ); ?></option>
									</select>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Expires', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'Expiration date/time for this rule', 'botblocker-security' ), BotBlockerDocs::url( 'ipv6-expires' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="datetime-local" class="bbcs_text_input_input" id="expires" name="expires" required>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-12">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Comment', 'botblocker-security' ); ?></span><?php echo Base::help( __( 'Optional comment for this rule', 'botblocker-security' ), BotBlockerDocs::url( 'ipv6-comment' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
								</div>
								<div class="bbcs_text_input_inner">
									<textarea class="bbcs_text_input_input" id="comment" name="comment" rows="3"></textarea>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
				<button type="submit" form="editIPv6Form" class="btn btn-primary btn-xs"><?php esc_html_e( 'Save', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
</div>
