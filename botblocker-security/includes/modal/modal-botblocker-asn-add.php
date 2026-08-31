<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><div class="modal fade" id="createAsnModal" tabindex="-1" aria-labelledby="createAsnModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="createAsnModalLabel"><?php esc_html_e( 'Add ASN Rule', 'botblocker-security' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="createAsnForm">
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Priority', 'botblocker-security' ); ?></span>
								<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'ASN rule priority (1-100)', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="range" class="bbcs_text_input_input" id="asnPriority" name="priority" min="1" max="100" value="50" required>
									<output for="asnPriority" id="asnPriorityValue">50</output>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Rule', 'botblocker-security' ); ?></span>
								<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Action for this ASN', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<select class="bbcs_select_input_input" id="asnRule" name="rule" required>
										<option value="allow"><?php esc_html_e( 'Allow', 'botblocker-security' ); ?></option>
										<option value="gray"><?php esc_html_e( 'Gray', 'botblocker-security' ); ?></option>
										<option value="dark"><?php esc_html_e( 'Dark', 'botblocker-security' ); ?></option>
										<option value="block" selected><?php esc_html_e( 'Block', 'botblocker-security' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'ASN Number', 'botblocker-security' ); ?></span>
								<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Autonomous System Number', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="text" inputmode="numeric" pattern="[0-9]{1,20}" maxlength="20" class="bbcs_text_input_input" id="asnNum" name="asnum" required>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'AS Name', 'botblocker-security' ); ?></span>
								<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Autonomous System Name (optional)', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<input type="text" class="bbcs_text_input_input" id="asnName" name="asname">
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-12">
							<div class="bbcs_text_input">
								<div class="bbcs_label_input_box">
									<span class="bbcs-label-input"><?php esc_html_e( 'Comment', 'botblocker-security' ); ?></span>
								<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Optional comment for this ASN rule', 'botblocker-security' ); ?></span></span>
								</div>
								<div class="bbcs_text_input_inner">
									<textarea class="bbcs_text_input_input" id="asnComment" name="comment" rows="2"></textarea>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
				<button type="submit" form="createAsnForm" class="btn btn-primary btn-xs"><?php esc_html_e( 'Add', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
</div>
