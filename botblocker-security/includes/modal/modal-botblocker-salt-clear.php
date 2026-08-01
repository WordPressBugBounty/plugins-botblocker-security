<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="modal fade" id="confirmSaltClearModal" tabindex="-1" aria-labelledby="confirmSaltClearModalLabel" aria-hidden="true">
<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="confirmSaltClearModalLabel"><?php esc_html_e( 'Reset Authentication Cookies', 'botblocker-security' ); ?></h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>
		<div class="modal-body">
			<p class="bbcs-black"><strong><?php esc_html_e( 'Attention!', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'Updating the salt resets all authentication cookies:', 'botblocker-security' ); ?></p>
			<ul class="bbcs-black bbcs-modal-ul">
				<li class="bbcs-modal-li"><?php esc_html_e( 'All existing cookies will become invalid.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'All users will need to re-authenticate.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'Takes effect immediately.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'This process is irreversible.', 'botblocker-security' ); ?></li>
			</ul>
			<p class="bbcs-black"><?php esc_html_e( 'Are you sure you want to clear the security salt?', 'botblocker-security' ); ?></p>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
			<button type="button" id="confirmSaltClearButton" class="btn btn-danger btn-xs"><?php esc_html_e( 'Reset cookies', 'botblocker-security' ); ?></button>
		</div>
	</div>
</div>
</div>
