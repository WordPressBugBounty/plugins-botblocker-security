<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?>
	<div class="bbcs-modal-overlay" id="confirmLogClearModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Clear Log File', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<p><strong><?php esc_html_e( 'Attention!', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'The WordPress log file (debug.log) will be cleared:', 'botblocker-security' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'All previous error records will be deleted.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'This action cannot be undone.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'New errors will be logged as they occur.', 'botblocker-security' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'Are you sure you want to clear the log file?', 'botblocker-security' ); ?></p>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" data-modal-close><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
				<button type="button" id="confirmLogClearButton" class="bbcs-btn bbcs-btn--danger"><?php esc_html_e( 'Clear the log', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
