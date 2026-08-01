<?php
if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Card;

return static function (Botblocker_SetupGuideViewModel $data, string $mode): void {
	if ($mode === 'pagehead') {
?>
		<div class="bbcs-pagehead">
			<div>
				<div class="bbcs-pagehead-title"><?php esc_html_e('System Status', 'botblocker-security'); ?></div>
				<div class="bbcs-pagehead-sub"><?php esc_html_e('Full protection checklist, PRO and request processing chain', 'botblocker-security'); ?></div>
			</div>
			<div class="bbcs-pagehead-actions">
				<button class="bbcs-btn bbcs-btn--pri" type="button" id="bbcsOpenOneClickSetup">
					<svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-bolt"></use>
					</svg>
					<?php esc_html_e('One-click setup', 'botblocker-security'); ?>
				</button>
			</div>
		</div>
	<?php
		return;
	}

	// ── Main content: cards in bbcs-col bbcs-g-3h ──
	?>
	<div class="bbcs-col bbcs-g-3h">

		<!-- System security status card -->
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
		<?php echo Card::make()->block( static function () use ( $data ): void {
		?>
			<div class="bbcs-status-head">
				<div class="bbcs-status-main">
					<div class="bbcs-gauge <?php echo esc_attr( Botblocker_HealthScoreHelper::getColorClass( $data->health_score ) ); ?>" style="background: conic-gradient(var(--bbcs-gauge-color) 0 <?php echo (int) $data->health_score; ?>%, var(--bbcs-s3) <?php echo (int) $data->health_score; ?>% 100%);">
						<div class="bbcs-gauge-inner">
							<span class="bbcs-gauge-n"><?php echo esc_html($data->health_score); ?>%</span>
							<span class="bbcs-gauge-lbl"><?php echo esc_html($data->health_label); ?></span>
						</div>
					</div>
					<div class="bbcs-fill">
						<div class="bbcs-section-title bbcs-fs-xl"><?php esc_html_e('System security status', 'botblocker-security'); ?></div>
						<div class="bbcs-muted bbcs-mt-2 bbcs-fs-sm bbcs-max-desc"><?php defined('BBCS_NEW_ADMIN_UI') && BBCS_NEW_ADMIN_UI ? esc_html_e('Complete protection checklist. Review each item below to ensure full coverage.', 'botblocker-security') : esc_html_e('Most checks are active. Enable the highlighted options to maximize protection.', 'botblocker-security'); ?></div>
					</div>
				</div>
				<div class="bbcs-status-stats">
					<div class="status-stat">
						<div class="bbcs-stat bbcs-stat--sm bbcs-tx-green"><?php echo esc_html((string) $data->status_active_count); ?></div>
						<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1"><?php esc_html_e('Active', 'botblocker-security'); ?></div>
					</div>
					<div class="status-stat">
						<div class="bbcs-stat bbcs-stat--sm"><?php echo esc_html((string) $data->status_disabled_count); ?></div>
						<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1"><?php esc_html_e('Disabled', 'botblocker-security'); ?></div>
					</div>
					<div class="status-stat">
						<div class="bbcs-stat bbcs-stat--sm bbcs-tx-amber"><?php echo esc_html((string) $data->status_attention_count); ?></div>
						<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1"><?php esc_html_e('Attention', 'botblocker-security'); ?></div>
					</div>
				</div>
			</div>
			<div class="bbcs-divider bbcs-mt-4h"></div>
			<div class="bbcs-statusgrid bbcs-mt-4h">
				<?php
				$group_titles = isset($data->status_group_titles) ? $data->status_group_titles : array(
					__('Detection and connectivity', 'botblocker-security'),
					__('Browser and protection', 'botblocker-security'),
					__('Data and notifications', 'botblocker-security'),
				);
				foreach ($data->status_groups as $gidx => $group) :
				?>
					<div class="bbcs-col bbcs-g-1">
						<div class="bbcs-fw-semibold bbcs-fs-xs bbcs-tx2 bbcs-mb-2"><?php echo esc_html($group_titles[$gidx] ?? ''); ?></div>
						<?php foreach ($group as $item) : ?>
							<?php
							$css_class = 'bbcs-status';
							$icon_name = 'x';
							$is_link   = false;
							$link_url  = '';
							if ($item->warn) {
								$css_class .= ' bbcs-status--warn';
								$icon_name  = 'warn';
								$is_link    = true;
								$link_url   = $item->key ? $data->getItemTabUrl($item->key) : $data->settings_url;
							} elseif ($item->ok) {
								$css_class .= ' bbcs-status--ok';
								$icon_name  = 'check';
							} elseif ($item->pro) {
								// PRO feature not active - link to upgrade
								$is_link  = true;
								$link_url = $data->cloud_api_url;
							} else {
								// Free feature disabled - link to specific tab if key exists
								$is_link  = true;
								$link_url = $item->key ? $data->getItemTabUrl($item->key) : $data->settings_url;
							}
							?>
							<?php if ($is_link) : ?>
								<a href="<?php echo esc_url($link_url); ?>" class="<?php echo esc_attr($css_class); ?>" style="cursor:pointer;text-decoration:none;">
							<?php else : ?>
								<div class="<?php echo esc_attr($css_class); ?>">
							<?php endif; ?>
								<span class="bbcs-status-ic"><svg class="bbcs-ico">
										<use href="#bbcs-i-<?php echo esc_attr($icon_name); ?>"></use>
									</svg></span>
								<span class="bbcs-status-label"><?php echo esc_html($item->label); ?><?php if ($item->pro) : ?> <span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro"><?php esc_html_e('PRO', 'botblocker-security'); ?></span><?php endif; ?></span>
							<?php if ($is_link) : ?>
								<svg class="bbcs-ico bbcs-ico--sm bbcs-ml-auto" style="flex-shrink:0;color:var(--bbcs-tx3);"><use href="#bbcs-i-arrowR"></use></svg>
							<?php endif; ?>
							<?php if ($is_link) : ?>
								</a>
							<?php else : ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php } ); ?>

		<!-- PRO Features card -->
		<?php
		$pro_render = require __DIR__ . '/pro.php';
		$pro_render( $data );
		?>

	</div>

	<!-- ── Rail: 3 cards ── -->
	<div class="bbcs-rail">

		<!-- System toggles card (toggle switches like sidebar) -->
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
		<?php echo Card::make()->block( static function () use ( $data ): void { ?>
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3"><?php esc_html_e( 'System', 'botblocker-security' ); ?></div>
			<?php
			$render_toggles = require BOTBLOCKER_DIR . 'admin/templates/shared/system-toggles.php';
			$render_toggles( $data->sidebar->toggles );
			?>
		<?php } ); ?>

		<!-- Request processing chain card -->
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
		<?php echo Card::make()->block( static function () use ( $data ): void { ?>
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3"><?php esc_html_e('Request processing chain', 'botblocker-security'); ?></div>
			<?php
			$render_chain = require BOTBLOCKER_DIR . 'admin/templates/shared/request-chain.php';
			$render_chain( new Botblocker_ChainContextData(
				(bool) $data->early_init_active,
				(bool) $data->mu_active,
				true,
				true,
				(bool) $data->early_available,
				$data->cloud_api_url,
				$data->addons_url
			) );
			?>
		<?php } ); ?>

		<!-- Add-ons card -->
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
		<?php echo Card::make()->block( static function () use ( $data ): void { ?>
			<div class="bbcs-row bbcs-g-2h bbcs-mb-2h">
				<span class="bbcs-section-title bbcs-fs-md"><?php esc_html_e( 'Add-ons', 'botblocker-security' ); ?></span>
				<a class="bbcs-link bbcs-fs-xs bbcs-ml-auto" href="<?php echo esc_url( $data->addons_url ); ?>"><?php esc_html_e( 'View all', 'botblocker-security' ); ?></a>
			</div>
			<?php if ( empty( $data->market_addons ) ) : ?>
				<div class="bbcs-muted bbcs-fs-sm"><?php esc_html_e( 'No add-ons available at the moment.', 'botblocker-security' ); ?></div>
			<?php else : ?>
				<div class="bbcs-col bbcs-g-1">
					<?php if ( defined( 'BBCS_NEW_ADMIN_UI' ) && BBCS_NEW_ADMIN_UI ) : ?>
						<?php foreach ( $data->market_addons as $addon ) : ?>
							<?php $settings_link = $addon->is_active ? $data->addons_url . '#' . $addon->slug : ''; ?>
							<div class="bbcs-status">
								<span class="bbcs-status-ic">
									<?php if ( $addon->icon ) : ?>
										<img src="<?php echo esc_url( $addon->icon ); ?>" alt="" class="bbcs-tile-img">
									<?php else : ?>
										<svg class="bbcs-ico"><use href="#bbcs-i-puzzle"></use></svg>
									<?php endif; ?>
								</span>
								<span class="bbcs-status-label">
									<?php if ( $settings_link ) : ?>
										<a href="<?php echo esc_url( $settings_link ); ?>" class="bbcs-dim"><?php echo esc_html( $addon->name ?: $addon->slug ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $addon->name ?: $addon->slug ); ?>
									<?php endif; ?>
									<?php if ( $addon->is_installed ) : ?>
										<span class="bbcs-pill bbcs-pill--green bbcs-pill--pro bbcs-ml-1"><?php esc_html_e( 'Installed', 'botblocker-security' ); ?></span>
									<?php endif; ?>
								</span>
								<?php if ( $settings_link ) : ?>
									<a href="<?php echo esc_url( $settings_link ); ?>" class="bbcs-dim bbcs-pointer bbcs-d-flex bbcs-ml-auto" title="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>">
										<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg>
									</a>
								<?php else : ?>
									<a href="<?php echo esc_url( $data->addons_url ); ?>" class="bbcs-dim bbcs-d-flex bbcs-ml-auto">
										<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg>
									</a>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( $data->market_addons as $addon ) : ?>
							<a href="<?php echo esc_url( $data->addons_url ); ?>" class="bbcs-status" style="cursor:pointer;text-decoration:none;">
								<span class="bbcs-status-ic">
									<?php if ( $addon->icon ) : ?>
										<img src="<?php echo esc_url( $addon->icon ); ?>" alt="" style="width:1.5em;height:1.5em;border-radius:4px;">
									<?php else : ?>
										<svg class="bbcs-ico"><use href="#bbcs-i-puzzle"></use></svg>
									<?php endif; ?>
								</span>
								<span class="bbcs-status-label">
									<?php echo esc_html( $addon->name ?: $addon->slug ); ?>
									<?php if ( $addon->is_installed ) : ?>
										<span class="bbcs-pill bbcs-pill--green bbcs-pill--pro" style="margin-left:0.5em;"><?php esc_html_e( 'Installed', 'botblocker-security' ); ?></span>
									<?php endif; ?>
								</span>
								<svg class="bbcs-ico bbcs-ico--sm bbcs-ml-auto" style="flex-shrink:0;color:var(--bbcs-tx3);"><use href="#bbcs-i-arrowR"></use></svg>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<div class="bbcs-mt-3h">
					<a class="bbcs-btn bbcs-btn--surface bbcs-btn--block" href="<?php echo esc_url( $data->addons_url ); ?>">
						<?php esc_html_e( 'Browse add-ons', 'botblocker-security' ); ?>
						<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg>
					</a>
				</div>
			<?php endif; ?>
		<?php } ); ?>

	</div>
<?php
};
