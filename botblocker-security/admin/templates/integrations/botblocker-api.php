<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="cloud"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/cloud-api.svg'); ?>"
					alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('BotBlocker API connects your site to cloud threat intelligence for real-time security data beyond local detection.', 'botblocker-security'); ?></div>
				<div class="bbcs-doclist">
					<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span><?php esc_html_e('Documentation', 'botblocker-security'); ?></div><a
						href="<?php echo esc_url($data->docs_url); ?>/how-botblocker-pros-cloud-verification-defeats-bots/"
						target="_blank"
						class="bbcs-link bbcs-fs-xs"><?php esc_html_e('Cloud verification', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('PRO Status', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg">
					<span class="bbcs-pill bbcs-pill--<?php echo $data->is_cloud_api_active ? 'green' : 'amber'; ?>"><?php echo $data->is_cloud_api_active ? esc_html__('Active', 'botblocker-security') : esc_html__('Inactive', 'botblocker-security'); ?></span>
					<span class="bbcs-option-label"><?php echo $data->is_cloud_api_active ? esc_html__('PRO Status: Active', 'botblocker-security') : esc_html__('PRO Status: Inactive', 'botblocker-security'); ?></span>
				</div>
			</div>

			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('License', 'botblocker-security'); ?></div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('License key', 'botblocker-security'); ?></div>
					<div class="bbcs-field-box">
						<input type="text" class="bbcs-input bbcs-input--mono" id="bbcs_cloud_api_key" name="bbcs_cloud_api_key" value="<?php echo esc_attr( $data->cloud_api_key ); ?>" placeholder="<?php esc_attr_e( 'Enter license key', 'botblocker-security' ); ?>">
					</div>
				</div>
				<div class="bbcs-row bbcs-g-2 bbcs-mt-2">
					<button type="button" id="bbcs_toggle_cloud_api_btn" class="bbcs-btn <?php echo $data->is_cloud_api_active ? 'bbcs-btn--danger' : 'bbcs-btn--pri'; ?>" data-is-active="<?php echo $data->is_cloud_api_active ? '1' : '0'; ?>">
						<?php echo $data->is_cloud_api_active ? esc_html__( 'Disconnect', 'botblocker-security' ) : esc_html__( 'Connect', 'botblocker-security' ); ?>
					</button>
					<button type="button" id="bbcs_fetch_cloud_api_key_btn" class="bbcs-btn">
						<?php esc_html_e( 'Fetch key from cloud', 'botblocker-security' ); ?>
					</button>
				</div>
				<?php wp_nonce_field( 'bbcs_connect_cloud_api_action', 'bbcs_connect_cloud_api_nonce' ); ?>
				<?php wp_nonce_field( 'bbcs_deactivate_cloud_api_action', 'bbcs_deactivate_cloud_api_nonce' ); ?>
				<?php wp_nonce_field( 'bbcs_fetch_cloud_api_key_action', 'bbcs_fetch_cloud_api_key_nonce' ); ?>
			</div>

			<?php if ( $data->is_cloud_api_active ) : ?>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Usage', 'botblocker-security'); ?></div>
				<div class="bbcs-grid bbcs-grid--2" style="margin: var(--bbcs-sp-2) var(--bbcs-sp-3); gap: var(--bbcs-sp-3h);">
					<div class="bbcs-statbox">
						<div class="bbcs-stat bbcs-stat--sm" id="bbcs_stat_hits"><?php echo esc_html( number_format_i18n( (int) $data->remaining_hits ) ); ?></div>
						<div class="bbcs-statbox-lbl"><?php esc_html_e( 'requests remaining', 'botblocker-security' ); ?></div>
					</div>
					<div class="bbcs-statbox">
						<div class="bbcs-stat bbcs-stat--sm" id="bbcs_stat_days"><?php echo esc_html( (string) $data->remaining_days ); ?></div>
						<div class="bbcs-statbox-lbl"><?php esc_html_e( 'days remaining', 'botblocker-security' ); ?></div>
					</div>
				</div>
				<div class="bbcs-field">
					<button type="button" id="bbcs_refresh_cloud_api" class="bbcs-btn bbcs-btn--surface">
						<?php esc_html_e( 'Refresh PRO status', 'botblocker-security' ); ?>
					</button>
				</div>
			</div>
			<?php endif; ?>

			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('API Endpoints', 'botblocker-security'); ?></div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('BotBlocker API URL:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('BotBlocker API endpoint for threat intelligence and security updates. Displayed for reference - not editable.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono"
						name="bbcs_api_url"
						value="<?php echo esc_attr($data->get('bbcs_api_url')); ?>"
						readonly></div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Additional API URL:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Reserve Globus Studio API endpoint used as a fallback. Displayed for reference - not editable.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono"
						name="bbcs_api_gs_url"
						value="<?php echo esc_attr($data->get('bbcs_api_gs_url')); ?>"
						readonly></div>
				</div>
			</div>
		</div>
	</div>
<?php
};
