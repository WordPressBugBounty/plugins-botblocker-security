<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_AddonsViewModel $data): void {
	if ( ! $data->addons_locked ) {
		return;
	}
	?>
	<div class="bbcs-card bbcs-card-pad bbcs-amber-card bbcs-mt-5h bbcs-mb-5h" id="bbcs-locked-notice">
		<div class="bbcs-row bbcs-row--between bbcs-row--wrap bbcs-g-3">
			<div class="bbcs-row bbcs-g-3">
				<span class="bbcs-tile bbcs-acc-amber"><svg class="bbcs-ico">
						<use href="#bbcs-i-crown"></use>
					</svg></span>
				<div>
					<div class="bbcs-fw-bold bbcs-fs-md"><?php esc_html_e('Premium Addons', 'botblocker-security'); ?></div>
					<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('included with BotBlocker PRO', 'botblocker-security'); ?></div>
				</div>
			</div>
			<div class="bbcs-row bbcs-g-2 bbcs-row--wrap">
				<a class="bbcs-btn bbcs-btn--pri" href="<?php echo esc_url($data->urls->cloud_api); ?>"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-crown"></use>
					</svg><?php esc_html_e('Get BotBlocker PRO', 'botblocker-security'); ?></a>
				<a class="bbcs-btn" href="<?php echo esc_url('https://botblocker.top/pricing/'); ?>" target="_blank"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-copy"></use>
					</svg><?php esc_html_e('Compare Plans', 'botblocker-security'); ?></a>
			</div>
		</div>
	</div>
	<?php
};
