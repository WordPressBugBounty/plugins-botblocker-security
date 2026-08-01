<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?>
	<div class="bbcs-modal-overlay" id="confirmSaltClearModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Reset Authentication Cookies', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<p><strong><?php esc_html_e( 'Attention!', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'Updating the salt resets all authentication cookies:', 'botblocker-security' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'All existing cookies will become invalid.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'All users will need to re-authenticate.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Takes effect immediately.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'This process is irreversible.', 'botblocker-security' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'Are you sure you want to clear the security salt?', 'botblocker-security' ); ?></p>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" data-modal-close><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
				<button type="button" id="confirmSaltClearButton" class="bbcs-btn bbcs-btn--danger"><?php esc_html_e( 'Reset cookies', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
