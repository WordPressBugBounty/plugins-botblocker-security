<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?>
	<div class="bbcs-modal-overlay" id="dbRepairInfoModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Database Repair and Optimization', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<p><strong><?php esc_html_e( 'To enable database repair and optimization:', 'botblocker-security' ); ?></strong></p>
				<ol>
					<li><?php esc_html_e( 'Open the file', 'botblocker-security' ); ?> <code>wp-config.php</code> <?php esc_html_e( 'in the root of your site.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Add the following line before', 'botblocker-security' ); ?> <code>/* That\'s all, stop editing! Happy publishing. */</code>:<br>
					<code>define( 'WP_ALLOW_REPAIR', true );</code></li>
					<li><?php esc_html_e( 'Save the file.', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Follow the link:', 'botblocker-security' ); ?> <a href="<?php echo esc_url( admin_url( 'maint/repair.php' ) ); ?>" target="_blank"><?php echo esc_url( admin_url( 'maint/repair.php' ) ); ?></a></li>
					<li><?php esc_html_e( 'After the repair is completed,', 'botblocker-security' ); ?> <strong><?php esc_html_e( 'delete', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'the added line from wp-config.php.', 'botblocker-security' ); ?></li>
				</ol>
				<p style="color:var(--bbcs-amber);font-weight:var(--bbcs-fw-bold);">
					<?php esc_html_e( 'Attention!', 'botblocker-security' ); ?> <?php esc_html_e( 'The repair page is publicly accessible without authentication while enabled. Disable it after use.', 'botblocker-security' ); ?>
				</p>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
