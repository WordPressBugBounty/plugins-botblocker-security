<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?>
	<div class="bbcs-modal-overlay" id="confirmPtrCacheClearModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Clear PTR cache', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<p><?php esc_html_e( 'Every cached reverse-DNS (PTR) result and the plugin cache generation will be reset.', 'botblocker-security' ); ?></p>
				<p><?php esc_html_e( 'Crawler verification will run a fresh lookup on the next hit. Are you sure you want to clear the PTR cache?', 'botblocker-security' ); ?></p>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" data-modal-close><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
				<button type="button" id="confirmPtrCacheClearButton" class="bbcs-btn bbcs-btn--danger"><?php esc_html_e( 'Clear', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
