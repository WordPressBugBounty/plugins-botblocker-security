<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SetupGuideViewModel $data ): void {
	?>
	<div class="bbcs-row bbcs-g-2h bbcs-mb-3">
		<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/cloud-api.svg' ); ?>"
			alt="<?php esc_attr_e( 'BotBlocker PRO', 'botblocker-security' ); ?>"
			style="width:2em;height:2em;" />
		<span class="bbcs-pill bbcs-pill--<?php echo $data->has_cloud_api ? 'green' : 'amber'; ?>">
			<?php echo $data->has_cloud_api ? esc_html__( 'Active', 'botblocker-security' ) : esc_html__( 'Not Active', 'botblocker-security' ); ?>
		</span>
	</div>
	<p class="bbcs-fs-sm bbcs-muted bbcs-mb-3">
		<?php esc_html_e( 'Monthly or annual subscription with cloud intelligence and premium features.', 'botblocker-security' ); ?>
	</p>
	<div class="bbcs-col bbcs-g-1 bbcs-mb-3">
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Real-time cloud threat checks (bots, bad ASN, dynamic proxies)', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Proxy, VPN, and Tor detection', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Extended heuristic and behavioral rules', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Hide admin login URL', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Early init: filtering before WordPress loads', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'WordPress speed optimization', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Automatic signature and AI model updates', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Priority support SLA', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'All official BotBlocker add-ons', 'botblocker-security' ); ?></div>
		<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'And more', 'botblocker-security' ); ?></div>
	</div>
	<?php if ( ! $data->has_cloud_api ) : ?>
		<div class="bbcs-row bbcs-g-2">
			<a href="<?php echo esc_url( $data->cloud_api_url ); ?>" class="bbcs-btn bbcs-btn--pri bbcs-btn--sm" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'My PRO status', 'botblocker-security' ); ?>
			</a>
			<a href="https://botblocker.top/pricing/" class="bbcs-btn bbcs-btn--surface bbcs-btn--sm" target="_blank" rel="noopener noreferrer">
				<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-star"></use></svg>
				<?php esc_html_e( 'Compare Plans', 'botblocker-security' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="bbcs-status bbcs-status--ok">
			<span class="bbcs-status-ic"><svg class="bbcs-ico"><use href="#bbcs-i-check"></use></svg></span>
			<span class="bbcs-status-label"><?php esc_html_e( 'Cloud API is active. Full protection enabled.', 'botblocker-security' ); ?></span>
		</div>
	<?php endif; ?>
	<div class="bbcs-fs-xs bbcs-muted bbcs-mt-2">
		<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-help"></use></svg>
		<?php esc_html_e( 'Cloud API provides extended protection with continuous threat intelligence and automation.', 'botblocker-security' ); ?>
	</div>
	<?php
};
