<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?>
	<div class="bbcs-modal-overlay" id="confirmHitsClearModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Clear All Visitor Data', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<p><strong><?php esc_html_e( 'Attention!', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'All visitor data will be permanently deleted:', 'botblocker-security' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Regular hits will be removed from the database.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Dashboard counters and daily statistics will be reset.', 'botblocker-security' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'Are you sure you want to clear all visitor data?', 'botblocker-security' ); ?></p>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" data-modal-close><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
				<button type="button" id="confirmHitsClearButton" class="bbcs-btn bbcs-btn--danger"><?php esc_html_e( 'Clear All', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
