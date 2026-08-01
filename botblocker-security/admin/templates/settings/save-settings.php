<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SettingsViewModel $data ): void {
	?>
	<span class="bbcs-unsaved-label" id="bbcs-unsaved-label" style="display:none"><?php echo esc_html( _x( 'Not saved!', 'unsaved changes indicator', 'botblocker-security' ) ); ?></span>
	<button type="submit" name="save_settings" id="bbcs-save-settings-btn" value="Save Settings" class="bbcs-btn bbcs-btn--pri">
		<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-check"></use></svg>
		<?php esc_html_e( 'Save', 'botblocker-security' ); ?>
	</button>
	<?php
};
