<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="modal fade" id="confirmLogClearModal" tabindex="-1" aria-labelledby="confirmLogClearModalLabel" aria-hidden="true">
<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="confirmLogClearModalLabel"><?php esc_html_e( 'Clear Log File', 'botblocker-security' ); ?></h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>
		<div class="modal-body">
			<p class="bbcs-black"><strong><?php esc_html_e( 'Attention!', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'The WordPress log file (debug.log) will be cleared:', 'botblocker-security' ); ?></p>
			<ul class="bbcs-black bbcs-modal-ul">
				<li class="bbcs-modal-li"><?php esc_html_e( 'All previous error records will be deleted.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'This action cannot be undone.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'New errors will be logged as they occur.', 'botblocker-security' ); ?></li>
			</ul>
			<p class="bbcs-black"><?php esc_html_e( 'Are you sure you want to clear the log file?', 'botblocker-security' ); ?></p>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
			<button type="button" id="confirmLogClearButton" class="btn btn-primary btn-xs"><?php esc_html_e( 'Clear the log', 'botblocker-security' ); ?></button>
		</div>
	</div>
</div>
</div>
