<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Base;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="cloud"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/cloud-api.svg'); ?>"
					alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('BotBlocker API connects your site to cloud threat intelligence for real-time security data beyond local detection.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-doc"><a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( BotBlockerDocs::page( 'cloud-api' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('PRO Status', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg">
					<?php if ( $data->is_expired ) : ?>
					<span class="bbcs-pill bbcs-pill--red"><?php esc_html_e('Expired', 'botblocker-security'); ?></span>
					<span class="bbcs-option-label"><?php esc_html_e('PRO Status: Expired', 'botblocker-security'); ?></span>
					<?php else : ?>
					<span class="bbcs-pill bbcs-pill--<?php echo $data->is_cloud_api_active ? 'green' : 'amber'; ?>"><?php echo $data->is_cloud_api_active ? esc_html__('Active', 'botblocker-security') : esc_html__('Inactive', 'botblocker-security'); ?></span>
					<span class="bbcs-option-label"><?php echo $data->is_cloud_api_active ? esc_html__('PRO Status: Active', 'botblocker-security') : esc_html__('PRO Status: Inactive', 'botblocker-security'); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('License', 'botblocker-security'); ?></div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('License key', 'botblocker-security'); ?><?php echo Base::help( '', BotBlockerDocs::url( 'cloud_api_key' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?></div>
					<div class="bbcs-field-box">
						<input type="text" class="bbcs-input bbcs-input--mono" id="bbcs_cloud_api_key" name="bbcs_cloud_api_key" value="<?php echo esc_attr( $data->cloud_api_key ); ?>" placeholder="<?php esc_attr_e( 'Enter license key', 'botblocker-security' ); ?>">
					</div>
				</div>
				<div class="bbcs-row bbcs-g-2 bbcs-mt-2">
					<button type="button" id="bbcs_toggle_cloud_api_btn" class="bbcs-btn <?php echo $data->is_cloud_api_active ? 'bbcs-btn--danger' : 'bbcs-btn--pri'; ?>" data-is-active="<?php echo $data->is_cloud_api_active ? '1' : '0'; ?>">
						<?php echo $data->is_cloud_api_active ? esc_html__( 'Disconnect', 'botblocker-security' ) : esc_html__( 'Connect', 'botblocker-security' ); ?>
					</button>
					<?php if ( ! $data->is_cloud_api_active ) : ?>
					<a id="bbcs_cloud_api_account_link" class="bbcs-btn" href="<?php echo esc_url( $data->account_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Already have a license? Get your key', 'botblocker-security' ); ?>
					</a>
					<button type="button" id="bbcs_request_license_push_btn" class="bbcs-btn">
						<?php esc_html_e( 'Load license from cloud', 'botblocker-security' ); ?>
					</button>
					<?php endif; ?>
				</div>
				<?php wp_nonce_field( 'bbcs_connect_cloud_api_action', 'bbcs_connect_cloud_api_nonce' ); ?>
				<?php wp_nonce_field( 'bbcs_deactivate_cloud_api_action', 'bbcs_deactivate_cloud_api_nonce' ); ?>
				<?php wp_nonce_field( 'bbcs_request_license_push_action', 'bbcs_request_license_push_nonce' ); ?>
			</div>

			<?php if ( $data->is_cloud_api_active ) : ?>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Usage', 'botblocker-security'); ?></div>
				<div class="bbcs-grid bbcs-grid--2" style="margin: var(--bbcs-sp-2) var(--bbcs-sp-3); gap: var(--bbcs-sp-3h);">
					<div class="bbcs-statbox">
						<div class="bbcs-stat bbcs-stat--sm<?php echo $data->remaining_hits === false ? ' is-loading is-empty' : ''; ?>" id="bbcs_stat_hits" data-bbcs-remaining="hits"><?php echo esc_html( $data->remaining_hits === false ? number_format_i18n( 123456 ) : number_format_i18n( (int) $data->remaining_hits ) ); ?></div>
						<div class="bbcs-statbox-lbl"><?php esc_html_e( 'requests remaining', 'botblocker-security' ); ?></div>
					</div>
					<div class="bbcs-statbox">
						<div class="bbcs-stat bbcs-stat--sm<?php echo $data->remaining_days === false ? ' is-loading is-empty' : ''; ?>" id="bbcs_stat_days" data-bbcs-remaining="days"><?php echo esc_html( $data->remaining_days === false ? '30' : BotBlockerPro::remainingDaysText( $data->remaining_days ) ); ?></div>
						<div class="bbcs-statbox-lbl" data-bbcs-remaining-label="days"<?php echo BotBlockerPro::isExpired() ? ' hidden' : ''; ?>><?php echo esc_html( $data->remaining_days === false ? __( 'days remaining', 'botblocker-security' ) : BotBlockerPro::remainingDaysLabel( $data->remaining_days ) ); ?></div>
					</div>
				</div>
				<div class="bbcs-field">
					<button type="button" id="bbcs_refresh_cloud_api" class="bbcs-btn bbcs-btn--surface">
						<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg>
						<?php esc_html_e( 'Refresh PRO status', 'botblocker-security' ); ?>
					</button>
				</div>
			</div>
			<?php endif; ?>

		</div>
	</div>
<?php
};
