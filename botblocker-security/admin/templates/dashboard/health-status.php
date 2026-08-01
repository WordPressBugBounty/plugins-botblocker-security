<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_DashboardViewModel $data ): void {
	?>
<div class="bbcs-card bbcs-card-pad">
	<div class="bbcs-section-head">
		<div class="bbcs-section-title"><?php esc_html_e( 'Protection status', 'botblocker-security' ); ?></div>
		<a class="bbcs-link" href="<?php echo esc_url( $data->urls->setup ); ?>">
			<?php esc_html_e( 'All checks', 'botblocker-security' ); ?>
			<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-arrowR"></use></svg>
		</a>
	</div>
	<div class="bbcs-grid bbcs-grid--2 bbcs-g-2h-7">
		<?php foreach ( $data->health_checks as $check ) : ?>
		<div class="bbcs-status <?php echo $check->ok ? 'bbcs-status--ok' : ''; ?>">
			<span class="bbcs-status-ic">
				<svg class="bbcs-ico"><use href="#bbcs-i-<?php echo $check->ok ? 'check' : 'x'; ?>"></use></svg>
			</span>
			<span class="bbcs-status-label"><?php echo esc_html( $check->label ); ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<a class="bbcs-btn bbcs-btn--pri bbcs-mt-4" href="<?php echo esc_url( $data->urls->settings ); ?>">
		<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg>
		<?php esc_html_e( 'Enable remaining', 'botblocker-security' ); ?>
	</a>
</div>
	<?php
};
