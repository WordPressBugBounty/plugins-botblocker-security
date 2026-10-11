<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\Base;

return static function ( Botblocker_CloudApiViewModel $data ): void {

	?>
	<div class="bbcs-grid bbcs-grid--2 bbcs-mb-5h">
		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-row bbcs-g-2h bbcs-mb-4">
				<span class="bbcs-tile bbcs-tile--sm bbcs-acc-amber"><svg class="bbcs-ico"><use href="#bbcs-i-crown"></use></svg></span>
				<div>
					<div class="bbcs-section-title bbcs-fs-xl"><?php esc_html_e( 'PRO Status', 'botblocker-security' ); ?></div>
					<div class="bbcs-pill <?php echo $data->is_cloud_api_active ? 'bbcs-pill--green' : 'bbcs-pill--amber'; ?> bbcs-mt-xs"><?php echo $data->is_cloud_api_active ? esc_html__( 'Active', 'botblocker-security' ) : esc_html__( 'Inactive', 'botblocker-security' ); ?></div>
				</div>
			</div>

			<p class="bbcs-fs-sm bbcs-muted bbcs-mb-2"><?php esc_html_e( 'Shows your BotBlocker PRO connection and subscription status. An active subscription delivers live security updates, firewall rules, and premium bot-detection from the BotBlocker network.', 'botblocker-security' ); ?></p>
			<p class="bbcs-fs-sm bbcs-muted bbcs-mb-3"><?php esc_html_e( 'Compare plan features below. PRO unlocks full per-visit verification, advanced blocking rules, performance add-ons, and priority support.', 'botblocker-security' ); ?></p>

			<form onsubmit="return false;">
			<div class="bbcs-field">
				<div class="bbcs-field-label"><?php esc_html_e( 'License key', 'botblocker-security' ); ?><?php echo Base::help( '', BotBlockerDocs::url( 'cloud_api_key' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?></div>
				<div class="bbcs-row bbcs-g-2h">
					<input type="text" class="bbcs-input bbcs-input--mono" id="bbcs_cloud_api_key" name="bbcs_cloud_api_key" value="<?php echo esc_attr( $data->cloud_api_key ); ?>" placeholder="<?php esc_attr_e( 'Enter license key', 'botblocker-security' ); ?>" style="flex:1;min-width:0;">
					<button type="submit" id="bbcs_toggle_cloud_api_btn" class="bbcs-btn <?php echo $data->is_cloud_api_active ? 'bbcs-btn--danger' : 'bbcs-btn--pri'; ?>" data-is-active="<?php echo $data->is_cloud_api_active ? '1' : '0'; ?>" style="padding-block:var(--bbcs-sp-2h);">
						<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-<?php echo $data->is_cloud_api_active ? 'ban' : 'link'; ?>"></use></svg>
						<?php echo $data->is_cloud_api_active ? esc_html__( 'Disconnect', 'botblocker-security' ) : esc_html__( 'Connect', 'botblocker-security' ); ?>
					</button>
				</div>
			</div>
			</form>
			<div id="cloud-status" class="active">
			<?php
			$bbcs_fetching = $data->is_cloud_api_active && $data->should_fetch;
			$bbcs_hits_txt = $data->remaining_hits === false ? ( $bbcs_fetching ? number_format_i18n( 123456 ) : '–' ) : number_format_i18n( (int) $data->remaining_hits );
			$bbcs_days_txt = $data->remaining_days === false ? ( $bbcs_fetching ? '30' : '–' ) : (string) (int) $data->remaining_days;
			?>
			<div class="bbcs-grid bbcs-grid--2 bbcs-mt-1 bbcs-mb-4">
				<div class="bbcs-inner bbcs-statbox">
					<div class="bbcs-stat bbcs-stat--sm<?php echo $bbcs_fetching && $data->remaining_hits === false ? ' is-loading is-empty' : ''; ?>" id="bbcs_stat_hits"<?php echo $data->is_cloud_api_active ? ' data-bbcs-remaining="hits"' : ''; ?>><?php echo esc_html( $bbcs_hits_txt ); ?></div>
					<div class="bbcs-statbox-lbl"><?php esc_html_e( 'requests remaining', 'botblocker-security' ); ?></div>
				</div>
				<div class="bbcs-inner bbcs-statbox">
					<div class="bbcs-stat bbcs-stat--sm<?php echo $bbcs_fetching && $data->remaining_days === false ? ' is-loading is-empty' : ''; ?>" id="bbcs_stat_days"<?php echo $data->is_cloud_api_active ? ' data-bbcs-remaining="days"' : ''; ?>><?php echo esc_html( $bbcs_days_txt ); ?></div>
					<div class="bbcs-statbox-lbl"><?php esc_html_e( 'days remaining', 'botblocker-security' ); ?></div>
				</div>
			</div>
			</div>
			<?php if ( $data->is_cloud_api_active ) : ?>
			<div class="bbcs-row bbcs-g-2">
				<button type="button" id="bbcs_refresh_cloud_api" class="bbcs-btn bbcs-btn--pri"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg><?php esc_html_e( 'Refresh status', 'botblocker-security' ); ?></button>
			</div>
			<?php endif; ?>

			<?php wp_nonce_field( 'bbcs_connect_cloud_api_action', 'bbcs_connect_cloud_api_nonce' ); ?>
			<?php wp_nonce_field( 'bbcs_deactivate_cloud_api_action', 'bbcs_deactivate_cloud_api_nonce' ); ?>
		</div>

		<?php if ( ! $data->is_cloud_api_active ) : ?>
		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e( "What's in PRO", 'botblocker-security' ); ?></div>
			<div class="bbcs-col bbcs-g-2h bbcs-mb-3h">
				<?php foreach ( $data->pro_features as $feature ) : ?>
					<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php echo esc_html( $feature ); ?></div>
				<?php endforeach; ?>
			</div>
			<div class="bbcs-mt-auto bbcs-mt-3h">
				<a class="bbcs-btn bbcs-btn--surface" href="<?php echo esc_url( $data->addons_url ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-puzzle"></use></svg><?php esc_html_e( 'Browse premium add-ons', 'botblocker-security' ); ?></a>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( ! $data->is_cloud_api_active ) : ?>
	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
		<div class="bbcs-section-head bbcs-mb-4">
			<div><div class="bbcs-section-title"><?php esc_html_e( 'Free vs BotBlocker PRO', 'botblocker-security' ); ?></div><div class="bbcs-muted bbcs-fs-xs bbcs-mt-1"><?php esc_html_e( 'Compare features across plans', 'botblocker-security' ); ?></div></div>
		</div>
		<table class="bbcs-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Feature', 'botblocker-security' ); ?></th>
					<th style="text-align:center"><?php esc_html_e( 'Free', 'botblocker-security' ); ?></th>
					<th style="text-align:center"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-crown"></use></svg>&nbsp;<?php esc_html_e( 'PRO', 'botblocker-security' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data->pro_comparison as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->feature ); ?></td>
						<td style="text-align:center">
							<?php if ( $row->free ) : ?>
								<svg class="bbcs-ico bbcs-ico--sm bbcs-tx-green" aria-label="<?php esc_attr_e( 'Included', 'botblocker-security' ); ?>"><use href="#bbcs-i-check"></use></svg>
							<?php else : ?>
								<span class="bbcs-dim" aria-label="<?php esc_attr_e( 'Not included', 'botblocker-security' ); ?>">-</span>
							<?php endif; ?>
						</td>
						<td style="text-align:center">
							<?php if ( $row->pro ) : ?>
								<svg class="bbcs-ico bbcs-ico--sm bbcs-tx-green" aria-label="<?php esc_attr_e( 'Included', 'botblocker-security' ); ?>"><use href="#bbcs-i-check"></use></svg>
							<?php else : ?>
								<span class="bbcs-dim" aria-label="<?php esc_attr_e( 'Not included', 'botblocker-security' ); ?>">-</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="bbcs-row bbcs-g-2 bbcs-mt-3">
			<a class="bbcs-btn bbcs-btn--pri" href="<?php echo esc_url( $data->pricing_url ); ?>" target="_blank"><?php esc_html_e( 'See pricing', 'botblocker-security' ); ?></a>
			<a class="bbcs-btn" href="<?php echo esc_url( BotBlockerDocs::page( 'licensing' ) ); ?>" target="_blank"><?php esc_html_e( 'Read full comparison', 'botblocker-security' ); ?></a>
		</div>
	</div>
	<?php endif; ?>

	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
		<div class="bbcs-section-head bbcs-mb-4">
			<div><div class="bbcs-section-title"><?php esc_html_e( 'Development services', 'botblocker-security' ); ?></div><div class="bbcs-muted bbcs-fs-xs bbcs-mt-1"><?php esc_html_e( 'Need help with WordPress security?', 'botblocker-security' ); ?></div></div>
		</div>
		<div class="bbcs-col bbcs-g-2h bbcs-mb-4">
			<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'WordPress security audit', 'botblocker-security' ); ?></div>
			<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Server-side protection setup', 'botblocker-security' ); ?></div>
			<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'CDN and caching integration', 'botblocker-security' ); ?></div>
			<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Custom development', 'botblocker-security' ); ?></div>
			<div class="bbcs-feature"><span class="bbcs-status-ic--inline"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></span><?php esc_html_e( 'Migration and optimization', 'botblocker-security' ); ?></div>
		</div>
		<a class="bbcs-btn bbcs-btn--pri" href="#"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-headset"></use></svg><?php esc_html_e( 'Contact us', 'botblocker-security' ); ?></a>
	</div>
	<?php
};
