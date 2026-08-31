<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_DashboardViewModel $data ): void {

	$link = static function ( string $url, string $label, string $desc, string $type ): string {
		return '<div class="bbcs-col bbcs-g-0h">'
			. '<div class="bbcs-fw-semibold bbcs-fs-sm">' . esc_html( $label ) . '</div>'
			. '<div class="bbcs-row bbcs-g-1h">'
			. '<input type="text" class="bbcs-input bbcs-input--mono bbcs-fill" value="' . esc_url( $url ) . '" readonly onclick="this.select()" data-url-type="' . esc_attr( $type ) . '" />'
			. '<button type="button" class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon bbcs-btn--sm bbcs-copy-btn" onclick="copyToClipboard(this)" title="' . esc_attr__( 'Copy to clipboard', 'botblocker-security' ) . '">'
			. '<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-copy"></use></svg>'
			. '</button>'
			. '</div>'
			. '<div class="bbcs-dim bbcs-fs-xs">' . esc_html( $desc ) . '</div>'
			. '</div>';
	};

	?>
	<div class="bbcs-modal-overlay" id="bbcsSecretLinksModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title">
					<svg class="bbcs-ico bbcs-ico--sm" style="margin-right:var(--bbcs-sp-1);"><use href="#bbcs-i-key"></use></svg>
					<?php esc_html_e( 'Security action links', 'botblocker-security' ); ?>
				</div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<div class="bbcs-col bbcs-g-3">
					<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					<?php echo $link( $data->secret->disable_url, __( 'Disable current check', 'botblocker-security' ), __( 'Temporarily disable BotBlocker checks for this session.', 'botblocker-security' ), 'disable' ); ?>
					<?php echo $link( $data->secret->off_url, __( 'Fully disable BotBlocker', 'botblocker-security' ), __( 'Completely disable BotBlocker protection.', 'botblocker-security' ), 'off' ); ?>
					<?php echo $link( $data->secret->on_url, __( 'Re-enable BotBlocker', 'botblocker-security' ), __( 'Re-enable BotBlocker protection.', 'botblocker-security' ), 'on' ); ?>
					<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" id="bbcs-regenerate-secret-links" title="<?php esc_attr_e( 'Generate new links and invalidate the old ones', 'botblocker-security' ); ?>">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg>
					<?php esc_html_e( 'Regenerate links', 'botblocker-security' ); ?>
				</button>
				<button type="button" class="bbcs-btn bbcs-btn--ghost" id="bbcs-send-email" title="<?php esc_attr_e( 'Send email with BotBlocker management links', 'botblocker-security' ); ?>">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-headset"></use></svg>
					<?php esc_html_e( 'Send via email', 'botblocker-security' ); ?>
				</button>
				<button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>

	<script>
	(function() {
		'use strict';
		var trigger = document.getElementById('bbcs-secret-links-trigger');
		var overlay = document.getElementById('bbcsSecretLinksModal');
		if (!trigger || !overlay) return;

		// Open modal
		trigger.addEventListener('click', function(e) {
			e.preventDefault();
			overlay.style.display = 'flex';
		});

		// Close on data-modal-close click
		overlay.addEventListener('click', function(e) {
			var btn = e.target.closest('[data-modal-close]');
			if (btn) {
				overlay.style.display = 'none';
				return;
			}
			// Close on backdrop click
			if (e.target === overlay) {
				overlay.style.display = 'none';
			}
		});

		// Close on Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && overlay.style.display === 'flex') {
				overlay.style.display = 'none';
			}
		});
	})();
	</script>
	<?php
};
