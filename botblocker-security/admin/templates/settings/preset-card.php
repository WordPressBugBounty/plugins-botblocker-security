<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_SettingsViewModel $data): void {
?>
	<div class="bbcs-card bbcs-preset">
		<span class="bbcs-tx-green">
			<svg class="bbcs-ico bbcs-ico--lg">
				<use href="#bbcs-i-shieldCheck"></use>
			</svg>
		</span>
		<div class="bbcs-fill">
			<div class="bbcs-preset-title">
				<?php echo esc_html($data->preset_name); ?>
				&middot;
				<?php echo esc_html($data->mode_label); ?>
			</div>
			<div class="bbcs-preset-sub"><?php esc_html_e('Balanced ruleset suitable for most websites.', 'botblocker-security'); ?></div>
		</div>
		<a class="bbcs-btn bbcs-btn--surface" href="<?php echo esc_url($data->urls->setup); ?>"><?php esc_html_e('Change preset', 'botblocker-security'); ?></a>
	</div>
<?php
};
