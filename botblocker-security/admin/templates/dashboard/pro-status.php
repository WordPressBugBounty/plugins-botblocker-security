<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_ProStatusViewModel $data ): void {
	$alert_class = $data->alert_level === 'critical' ? 'bbcs-tx-red' : ( $data->alert_level === 'warn' ? 'bbcs-tx-amber' : '' );
	?>
<div class="bbcs-card bbcs-card-pad">
	<div class="bbcs-section-head">
		<div class="bbcs-row bbcs-g-2h">
			<span class="bbcs-tile bbcs-tile--sm bbcs-acc-amber"><svg class="bbcs-ico"><use href="#bbcs-i-crown"></use></svg></span>
			<div>
				<div class="bbcs-section-title">
					<?php echo $data->has_pro ? esc_html__( 'PRO Status', 'botblocker-security' ) : esc_html__( 'BotBlocker PRO', 'botblocker-security' ); ?>
				</div>
				<?php if ( $data->has_pro && $data->tier !== '' ) : ?>
				<span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro bbcs-mt-xs"><?php echo esc_html( strtoupper( $data->tier ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<a class="bbcs-link" href="<?php echo esc_url( $data->upgrade_url ); ?>">
			<?php if ( $data->has_pro ) { esc_html_e( 'Manage plan', 'botblocker-security' ); } else { esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); } ?>
			<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-arrowR"></use></svg>
		</a>
	</div>

	<?php if ( $data->has_pro ) : ?>
	<div class="bbcs-grid bbcs-grid--2 bbcs-mb-4">
		<div class="bbcs-inner bbcs-statbox">
			<div class="bbcs-row bbcs-g-2h">
				<span class="bbcs-tile bbcs-tile--sm bbcs-acc-violet"><svg class="bbcs-ico"><use href="#bbcs-i-gauge"></use></svg></span>
				<div class="bbcs-fill">
					<div class="bbcs-stat bbcs-stat--sm <?php echo esc_attr( $alert_class ); ?><?php echo $data->remaining_hits === false ? ' is-loading is-empty' : ''; ?>" data-bbcs-remaining="hits"><?php echo esc_html( $data->remaining_hits === false ? number_format_i18n( 123456 ) : number_format_i18n( (int) $data->remaining_hits ) ); ?></div>
					<div class="bbcs-statbox-lbl"><?php esc_html_e( 'requests remaining', 'botblocker-security' ); ?></div>
				</div>
			</div>
		</div>
		<div class="bbcs-inner bbcs-statbox">
			<div class="bbcs-row bbcs-g-2h">
				<span class="bbcs-tile bbcs-tile--sm bbcs-acc-amber"><svg class="bbcs-ico"><use href="#bbcs-i-clock"></use></svg></span>
				<div class="bbcs-fill">
					<div class="bbcs-stat bbcs-stat--sm <?php echo esc_attr( $alert_class ); ?><?php echo $data->remaining_days === false ? ' is-loading is-empty' : ''; ?>" data-bbcs-remaining="days"><?php echo esc_html( $data->remaining_days === false ? '30' : (string) (int) $data->remaining_days ); ?></div>
					<div class="bbcs-statbox-lbl"><?php esc_html_e( 'days remaining', 'botblocker-security' ); ?></div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="bbcs-divider bbcs-mb-3"></div>

	<div class="bbcs-fw-semibold bbcs-fs-sm bbcs-mb-2h">
		<?php esc_html_e( 'Included and available PRO options', 'botblocker-security' ); ?>
	</div>

	<div class="bbcs-grid bbcs-grid--2 bbcs-g-2h-7">
		<?php foreach ( $data->options as $option ) : ?>
		<?php
		$is_on     = 'on' === $option->status;
		$is_off    = 'off' === $option->status;
		$is_locked = ! $is_on && ! $is_off;
		$ic        = $is_on ? 'bbcs-i-check' : ( $is_off ? 'bbcs-i-x' : 'bbcs-i-lock' );
		?>
		<a class="bbcs-pro-opt bbcs-pro-opt--<?php echo esc_attr( $option->status ); ?>" href="<?php echo esc_url( $option->section_url ); ?>" title="<?php echo esc_attr( $option->tooltip ); ?>">
			<span class="bbcs-pro-opt-ic"><svg class="bbcs-ico"><use href="#<?php echo esc_attr( $ic ); ?>"></use></svg></span>
			<span class="bbcs-pro-opt-label"><?php echo esc_html( $option->label ); ?></span>
			<span class="bbcs-pill <?php echo esc_attr( $is_on ? 'bbcs-pill--green' : ( $is_off ? 'bbcs-pill--amber' : 'bbcs-pill--violet bbcs-pill--pro' ) ); ?>">
				<?php
				if ( $is_locked ) {
					echo esc_html( strtoupper( $option->tier ) );
				} elseif ( $is_on ) {
					esc_html_e( 'On', 'botblocker-security' );
				} else {
					esc_html_e( 'Off', 'botblocker-security' );
				}
				?>
			</span>
		</a>
		<?php endforeach; ?>
	</div>
</div>
	<?php
};
