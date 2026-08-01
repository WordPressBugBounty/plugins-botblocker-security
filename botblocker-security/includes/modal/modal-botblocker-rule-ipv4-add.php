<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="modal fade" id="addIPv4Modal" tabindex="-1" aria-labelledby="addIPv4ModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addIPv4ModalLabel"><?php esc_html_e( 'Add IPv4 Rule', 'botblocker-security' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="addIPv4Form">
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Priority', 'botblocker-security' ); ?></span>
									<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Priority (1-100)', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="number" class="bbcs_text_input_input" id="addPriority" name="priority" min="1" max="100" value="50" required>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'IP Address', 'botblocker-security' ); ?></span>
									<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'IPv4 address or subnet (CIDR)', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="text" class="bbcs_text_input_input" id="addIp" name="ip" placeholder="192.168.1.0/24 or 192.168.1.1" required>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Rule', 'botblocker-security' ); ?></span>
									<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Allow or block this IP/subnet', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<select class="bbcs_select_input_input" id="addRule" name="rule" required>
										<option value="allow"><?php esc_html_e( 'Allow', 'botblocker-security' ); ?></option>
										<option value="block"><?php esc_html_e( 'Block', 'botblocker-security' ); ?></option>
									</select>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Expires', 'botblocker-security' ); ?></span>
									<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Expiration date/time for this rule', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="datetime-local" class="bbcs_text_input_input" id="addExpires" name="expires" required>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-12">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Comment', 'botblocker-security' ); ?></span>
									<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Optional comment for this rule', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<textarea class="bbcs_text_input_input" id="addComment" name="comment" rows="3"></textarea>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
				<button type="submit" form="addIPv4Form" class="btn btn-primary btn-xs"><?php esc_html_e( 'Add', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
</div>
