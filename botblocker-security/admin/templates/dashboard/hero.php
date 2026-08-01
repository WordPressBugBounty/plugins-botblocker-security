<?php
if ( ! defined( "ABSPATH" ) ) {
	exit;
}
return static function ( Botblocker_DashboardViewModel $data ): void {
	?>
<div class="bbcs-card bbcs-hero">
	<div class="bbcs-hero-main">
		<span class="bbcs-tile bbcs-tile--lg <?php echo $data->is_active ? "bbcs-acc-green" : "bbcs-acc-amber"; ?>">
			<svg class="bbcs-ico"><use href="#bbcs-i-<?php echo $data->is_active ? "shieldCheck" : "shield"; ?>"></use></svg>
		</span>
		<div class="bbcs-fill">
			<div class="bbcs-hero-title"><?php echo esc_html( $data->hero_status_text ); ?></div>
			<div class="bbcs-hero-sub"><?php echo wp_kses_post( $data->hero_subtitle ); ?></div>
		</div>
		<div class="bbcs-hero-toggle">
			<span class="bbcs-fw-bold"><?php echo $data->is_active ? esc_html__( "Enabled", "botblocker-security" ) : esc_html__( "Disabled", "botblocker-security" ); ?></span>
			<button class="bbcs-toggle <?php echo $data->is_active ? "is-on" : ""; ?>" role="switch" aria-checked="<?php echo $data->is_active ? "true" : "false"; ?>" id="bbcs-hero-toggle" data-bbcs-toggle="1" data-action="bbcs_toggle_early_phase_in_db" data-setting="disable" data-value="<?php echo $data->is_active ? "1" : "0"; ?>">
				<span class="bbcs-toggle-knob"></span>
			</button>
		</div>
	</div>
	<div class="bbcs-hero-actions">
		<a class="bbcs-btn" href="<?php echo esc_url( $data->urls->wizard ); ?>">
			<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg>
			<?php esc_html_e( "Setup Wizard", "botblocker-security" ); ?>
		</a>
		<button type="button" class="bbcs-btn" id="bbcs-secret-links-trigger" title="<?php esc_attr_e( 'Security action links', 'botblocker-security' ); ?>">
			<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-key"></use></svg>
			<span class="bbcs-btn-label"><?php esc_html_e( 'Secret links', 'botblocker-security' ); ?></span>
		</button>
	</div>
</div>
	<?php
};
