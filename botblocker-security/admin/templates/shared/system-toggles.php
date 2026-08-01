<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\Botblocker_StatusToggles;

return static function ( Botblocker_StatusToggles $toggles ): void {
	$t = $toggles;

	$statline = static function ( string $label, int $checked, bool $disabled = false, string $tooltip = '', string $color_class = '', string $action = '', string $setting = '', bool $pro_badge = false, string $warning_badge = '', string $settings_link = '', bool $addon_badge = false ): string {
		$on         = $checked ? ' is-on' : '';
		$color_cls  = $color_class !== '' ? ' ' . $color_class : '';
		$dis        = $disabled ? ' disabled' : '';
		$aria       = $checked ? 'true' : 'false';
		$data_attrs = $action !== '' ? ' data-bbcs-toggle="1" data-action="' . esc_attr( $action ) . '" data-setting="' . esc_attr( $setting ) . '" data-value="' . (int) $checked . '"' : '';
		$tip_html   = $tooltip !== '' ? '<span class="bbcs-help" style="display:inline-flex"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip">' . esc_html( $tooltip ) . '</span></span>' : '';
		$badge_html = $pro_badge ? ' <span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro">' . esc_html__( 'PRO', 'botblocker-security' ) . '</span>' : '';
		$addon_html = $addon_badge ? ' <span class="bbcs-pill bbcs-pill--addon bbcs-pill--pro">' . esc_html__( 'Add-on', 'botblocker-security' ) . '</span>' : '';
		$warn_html  = $warning_badge !== '' ? ' <span class="bbcs-pill bbcs-pill--amber bbcs-pill--pro">' . esc_html( $warning_badge ) . '</span>' : '';
		
		$settings_html = '';
		if ( $settings_link !== '' ) {
			$settings_html = ' <a class="bbcs-dim bbcs-pointer" href="' . esc_url( $settings_link ) . '" style="display:inline-flex; align-items:center; margin-left:var(--bbcs-sp-1);" title="' . esc_attr__( 'Settings', 'botblocker-security' ) . '">'
				. '<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg>'
				. '</a>';
		}

		return '<div class="bbcs-statline">'
			. '<button class="bbcs-toggle' . $color_cls . $on . '" role="switch" aria-checked="' . $aria . '"' . $dis . $data_attrs . '><span class="bbcs-toggle-knob"></span></button>'
			. '<span class="bbcs-statline-label">' . esc_html( $label ) . '</span>'
			. $badge_html
			. $addon_html
			. $warn_html
			. $settings_html
			. $tip_html
			. '</div>';
	};
	?>
	<div class="bbcs-col">
		<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
		<?php echo $statline( __( 'Early Init', 'botblocker-security' ), (int) $t->early_init_checked, (bool) $t->early_init_disabled, __( 'Loads black/white IP lists via wp-config before WordPress core starts', 'botblocker-security' ), 'bbcs-toggle--amber', 'bbcs_toggle_early_phase_in_db', 'early_init_enable', true, '', '', (bool) $t->early_init_disabled ); ?>
		<?php echo $statline( __( 'MU-plugin', 'botblocker-security' ), (int) $t->mu_checked, false, __( 'MU mode loads black/white IP lists before regular plugins and WordPress core', 'botblocker-security' ), '', 'bbcs_toggle_early_phase_in_db', 'mu_enable' ); ?>
		<?php echo $statline( __( 'Redis', 'botblocker-security' ), (int) $t->redis_checked, (bool) $t->redis_disabled, __( 'Speeds up visitor processing via Redis', 'botblocker-security' ), '', 'bbcs_toggle_redis_and_memcached', 'redis_enable', false, $t->redis_disabled ? __( 'Extension Missing', 'botblocker-security' ) : '', function_exists( 'bbcs_get_setting_link' ) ? bbcs_get_setting_link( 'redis_enable' ) : '' ); ?>
		<?php echo $statline( __( 'Memcached', 'botblocker-security' ), (int) $t->memcached_checked, (bool) $t->memcached_disabled, __( 'Speeds up visitor processing via Memcached', 'botblocker-security' ), '', 'bbcs_toggle_redis_and_memcached', 'memcached_enable', false, $t->memcached_disabled ? __( 'Extension Missing', 'botblocker-security' ) : '', function_exists( 'bbcs_get_setting_link' ) ? bbcs_get_setting_link( 'memcached_enable' ) : '' ); ?>
		<?php echo $statline( __( 'PTR cache', 'botblocker-security' ) . ' (' . esc_html( $t->ptrcache_time_label ) . ')', (int) $t->ptr_cache_checked, false, __( 'Caches PTR lookups to speed up repeat visitor checks', 'botblocker-security' ), '', 'bbcs_switch_ptr_cache_in_db', 'ptr_cache_in_db', false, '', function_exists( 'bbcs_get_setting_link' ) ? bbcs_get_setting_link( 'ptr_cache_in_db' ) : '' ); ?>
		<?php echo $statline( __( 'UI Cache', 'botblocker-security' ) . ' (' . esc_html( $t->cache_ui_duration_label ) . ')', (int) $t->cache_ui_checked, false, __( 'Cache the admin interface for faster loading', 'botblocker-security' ), '', 'bbcs_switch_ui_cache_in_db', 'cache_ui_data', false, '', function_exists( 'bbcs_get_setting_link' ) ? bbcs_get_setting_link( 'cache_ui_data' ) : '' ); ?>
		<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
};
