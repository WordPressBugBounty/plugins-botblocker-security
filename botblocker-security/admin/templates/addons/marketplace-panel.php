<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_AddonsViewModel $data, bool $isActive): void {
	// Collect slugs of locally installed addons that are also in the market
	$market_slugs = array();
	foreach ($data->market as $item) {
		if ($item->slug !== '') {
			$market_slugs[$item->slug] = true;
		}
	}
?>
	<div class="bbcs-grid bbcs-grid--3">
		<?php
		// ── Market items ──
		foreach ($data->market as $bbcs_item) :
			$slug = $bbcs_item->slug;
			$is_installed = $bbcs_item->is_installed;
			$is_active    = $bbcs_item->is_active;
			$bbcs_settings_link = ($is_active && ! empty($data->urls->addons)) ? esc_url($data->urls->addons . '#' . $slug) : '';

			// Look up local addon data for toggle/delete/update info
			$local = isset($data->addons[$slug]) ? $data->addons[$slug] : null;

			// Determine states
			$show_toggle     = $is_installed && $local && ! $local->broken && ! $local->incompatible && ! $local->incompatible_remote && ! $bbcs_item->is_incompatible;
			$show_install    = ! $is_installed && ! $bbcs_item->is_incompatible;
			$is_broken       = $is_installed && $local && $local->broken;
			$is_incompatible = $bbcs_item->is_incompatible || ($local && ($local->incompatible || $local->incompatible_remote));
			$has_update      = $local && $local->update_avail;
			$settings_link   = $bbcs_settings_link;
		?>
			<div class="bbcs-card bbcs-addon<?php echo $is_active ? ' bbcs-addon--active' : ''; ?>" data-addon-slug="<?php echo esc_attr($slug); ?>">
				<div class="bbcs-addon-head">
					<span class="bbcs-tile <?php echo $is_active ? 'bbcs-acc-green' : 'bbcs-acc-blue'; ?>">
						<?php if ($bbcs_item->icon) : ?>
							<img src="<?php echo esc_url($bbcs_item->icon); ?>" alt="" class="bbcs-tile-img">
						<?php else : ?>
							<svg class="bbcs-ico"><use href="#bbcs-i-puzzle"></use></svg>
						<?php endif; ?>
					</span>
					<div class="bbcs-addon-info">
						<div class="bbcs-addon-title-row">
							<span class="bbcs-addon-name"><?php echo esc_html($bbcs_item->name ?: $slug); ?></span>
							<span class="bbcs-addon-ver">
								<?php if ($is_installed && $local) : ?>
									v<?php echo esc_html($local->version ?: ''); ?>
									<?php if ($bbcs_item->remote_ver && $local->version !== $bbcs_item->remote_ver) : ?>
										<span class="bbcs-dim"> → v<?php echo esc_html($bbcs_item->remote_ver); ?></span>
									<?php endif; ?>
								<?php else : ?>
									v<?php echo esc_html($bbcs_item->remote_ver ?: ''); ?>
								<?php endif; ?>
							</span>
						</div>
					</div>
					<div class="bbcs-addon-head-actions">
						<?php if ($is_active) : ?>
							<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e('Active', 'botblocker-security'); ?></span>
						<?php elseif ($is_installed) : ?>
							<span class="bbcs-tag bbcs-tag--blue"><?php esc_html_e('Disabled', 'botblocker-security'); ?></span>
						<?php elseif ($bbcs_item->show_installed) : ?>
							<span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro"><?php esc_html_e('PRO', 'botblocker-security'); ?></span>
						<?php endif; ?>
						<?php if ($bbcs_item->is_incompatible) : ?>
							<span class="bbcs-tag bbcs-tag--red"><?php esc_html_e('Incompatible', 'botblocker-security'); ?></span>
						<?php endif; ?>
						<?php if ($has_update) : ?>
							<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e('Update', 'botblocker-security'); ?></span>
						<?php endif; ?>
						<?php if ($local && ($local->incompatible || $local->incompatible_remote)) : ?>
							<span class="bbcs-tag bbcs-tag--red"><?php esc_html_e('Incompatible', 'botblocker-security'); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="bbcs-addon-body">
					<div class="bbcs-addon-desc"><?php echo esc_html($bbcs_item->description); ?></div>
				</div>
				<div class="bbcs-addon-divider"></div>
				<div class="bbcs-addon-footer">
					<div class="bbcs-addon-footer-left"<?php echo (! $is_installed) ? ' style="flex:1;"' : ''; ?>>
						<?php if ($is_installed && $local) : ?>
							<?php if ($show_toggle) : ?>
								<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
									<input type="hidden" name="action" value="bbcs_toggle_addon">
									<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
									<?php wp_nonce_field('bbcs_toggle_addon', 'bbcs_toggle_addon_nonce'); ?>
									<div class="bbcs-toggle-wrap">
										<button class="bbcs-toggle<?php echo $is_active ? ' is-on' : ''; ?>" role="switch" type="submit" aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>" title="<?php $is_active ? esc_attr_e('Deactivate', 'botblocker-security') : esc_attr_e('Activate', 'botblocker-security'); ?>"><span class="bbcs-toggle-knob"></span></button>
										<span class="bbcs-toggle-label"><?php echo $is_active ? esc_html__('Enabled', 'botblocker-security') : esc_html__('Disabled', 'botblocker-security'); ?></span>
									</div>
								</form>
							<?php endif; ?>
						<?php else : ?>
							<?php if ($show_install) : ?>
								<?php if (! $data->addons_locked && $data->has_cloud_api) : ?>
									<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-addon-install-form">
										<input type="hidden" name="action" value="bbcs_install_addon">
										<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
										<input type="hidden" name="url" value="<?php echo esc_attr($bbcs_item->url); ?>">
										<?php wp_nonce_field('bbcs_install_addon', 'bbcs_install_addon_nonce'); ?>
										<button class="bbcs-btn bbcs-btn--icon bbcs-btn--full" type="submit"><?php esc_html_e('Install', 'botblocker-security'); ?></button>
									</form>
								<?php else : ?>
									<a href="<?php echo esc_url($data->urls->cloud_api); ?>" class="bbcs-btn bbcs-btn--icon bbcs-btn--full" title="<?php esc_attr_e('Get BotBlocker PRO', 'botblocker-security'); ?>"><?php esc_html_e('Requires PRO', 'botblocker-security'); ?></a>
								<?php endif; ?>
							<?php endif; ?>
						<?php endif; ?>
					</div>
					<div class="bbcs-addon-footer-right">
						<?php if ($is_installed && $local) : ?>
							<?php if ($is_broken) : ?>
								<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e('Broken', 'botblocker-security'); ?></button>
							<?php elseif ($is_incompatible) : ?>
								<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e('Incompatible', 'botblocker-security'); ?></button>
							<?php else : ?>
								<?php if ($has_update) : ?>
									<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
										<input type="hidden" name="action" value="bbcs_update_addon">
										<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
										<input type="hidden" name="url" value="<?php echo esc_attr($bbcs_item->url); ?>">
										<input type="hidden" name="requires_core" value="<?php echo esc_attr($bbcs_item->requires_core); ?>">
										<?php wp_nonce_field('bbcs_update_addon', 'bbcs_update_addon_nonce'); ?>
										<button type="submit" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e('Update', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg></button>
									</form>
								<?php endif; ?>

								<?php if ($settings_link) : ?>
									<a href="<?php echo esc_url($settings_link); ?>" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e('Settings', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg></a>
								<?php endif; ?>

								<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0" data-bbcs-confirm="<?php echo esc_attr__('Are you sure you want to delete this add-on?', 'botblocker-security'); ?>">
									<input type="hidden" name="action" value="bbcs_delete_addon">
									<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
									<?php wp_nonce_field('bbcs_delete_addon', 'bbcs_delete_addon_nonce'); ?>
									<button type="submit" class="bbcs-btn bbcs-btn--icon bbcs-btn--danger" title="<?php esc_attr_e('Delete', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-trash"></use></svg></button>
								</form>
							<?php endif; ?>
						<?php else : ?>
							<?php if ($settings_link) : ?>
								<a href="<?php echo esc_url($settings_link); ?>" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e('Settings', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg></a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<?php
		// ── Locally installed addons NOT in the market ──
		foreach ($data->addons as $slug => $bbcs_addon) :
			if (isset($market_slugs[$slug])) {
				continue; // already shown above
			}
			$bbcs_settings_link = ($bbcs_addon->is_active && ! empty($bbcs_addon->has_settings) && ! empty($data->urls->addons)) ? esc_url($data->urls->addons . '#' . $slug) : '';

			$show_toggle     = ! $bbcs_addon->broken && ! $bbcs_addon->incompatible && ! $bbcs_addon->incompatible_remote;
			$is_broken       = $bbcs_addon->broken;
			$is_incompatible = $bbcs_addon->incompatible || $bbcs_addon->incompatible_remote;
			$is_active       = $bbcs_addon->is_active;
		?>
			<div class="bbcs-card bbcs-addon<?php echo $is_active ? ' bbcs-addon--active' : ''; ?>" data-addon-slug="<?php echo esc_attr($slug); ?>">
				<div class="bbcs-addon-head">
					<span class="bbcs-tile <?php echo $is_active ? 'bbcs-acc-green' : 'bbcs-acc-blue'; ?>">
						<?php if ($bbcs_addon->icon) : ?>
							<img src="<?php echo esc_url($bbcs_addon->icon); ?>" alt="" class="bbcs-tile-img">
						<?php else : ?>
							<svg class="bbcs-ico"><use href="#bbcs-i-puzzle"></use></svg>
						<?php endif; ?>
					</span>
					<div class="bbcs-addon-info">
						<div class="bbcs-addon-title-row">
							<span class="bbcs-addon-name"><?php echo esc_html($bbcs_addon->name ?: $slug); ?></span>
							<span class="bbcs-addon-ver">v<?php echo esc_html($bbcs_addon->version ?: ''); ?></span>
						</div>
					</div>
					<div class="bbcs-addon-head-actions">
						<?php if ($is_active) : ?>
							<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e('Active', 'botblocker-security'); ?></span>
						<?php else : ?>
							<span class="bbcs-tag bbcs-tag--blue"><?php esc_html_e('Disabled', 'botblocker-security'); ?></span>
						<?php endif; ?>
						<?php if ($bbcs_addon->update_avail) : ?>
							<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e('Update', 'botblocker-security'); ?></span>
						<?php endif; ?>
						<?php if ($bbcs_addon->incompatible) : ?>
							<span class="bbcs-tag bbcs-tag--red"><?php esc_html_e('Incompatible', 'botblocker-security'); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="bbcs-addon-body">
					<div class="bbcs-addon-desc"><?php echo esc_html($bbcs_addon->description); ?></div>
				</div>
				<div class="bbcs-addon-divider"></div>
				<div class="bbcs-addon-footer">
					<div class="bbcs-addon-footer-left">
						<?php if ($show_toggle) : ?>
							<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
								<input type="hidden" name="action" value="bbcs_toggle_addon">
								<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
								<?php wp_nonce_field('bbcs_toggle_addon', 'bbcs_toggle_addon_nonce'); ?>
								<div class="bbcs-toggle-wrap">
									<button class="bbcs-toggle<?php echo $is_active ? ' is-on' : ''; ?>" role="switch" type="submit" aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>" title="<?php $is_active ? esc_attr_e('Deactivate', 'botblocker-security') : esc_attr_e('Activate', 'botblocker-security'); ?>"><span class="bbcs-toggle-knob"></span></button>
									<span class="bbcs-toggle-label"><?php echo $is_active ? esc_html__('Enabled', 'botblocker-security') : esc_html__('Disabled', 'botblocker-security'); ?></span>
								</div>
							</form>
						<?php endif; ?>
					</div>
					<div class="bbcs-addon-footer-right">
						<?php if ($is_broken) : ?>
							<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e('Broken', 'botblocker-security'); ?></button>
						<?php elseif ($is_incompatible) : ?>
							<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e('Incompatible', 'botblocker-security'); ?></button>
						<?php else : ?>
							<?php if ($bbcs_addon->update_avail) : ?>
								<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
									<input type="hidden" name="action" value="bbcs_update_addon">
									<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
									<input type="hidden" name="url" value="<?php echo esc_attr($bbcs_addon->update_url); ?>">
									<input type="hidden" name="requires_core" value="<?php echo esc_attr($bbcs_addon->update_requires_core); ?>">
									<?php wp_nonce_field('bbcs_update_addon', 'bbcs_update_addon_nonce'); ?>
									<button type="submit" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e('Update', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg></button>
								</form>
							<?php endif; ?>

							<?php if ($bbcs_settings_link) : ?>
								<a href="<?php echo esc_url($bbcs_settings_link); ?>" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e('Settings', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg></a>
							<?php endif; ?>

							<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0" data-bbcs-confirm="<?php echo esc_attr__('Are you sure you want to delete this add-on?', 'botblocker-security'); ?>">
								<input type="hidden" name="action" value="bbcs_delete_addon">
								<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
								<?php wp_nonce_field('bbcs_delete_addon', 'bbcs_delete_addon_nonce'); ?>
								<button type="submit" class="bbcs-btn bbcs-btn--icon bbcs-btn--danger" title="<?php esc_attr_e('Delete', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-trash"></use></svg></button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php
};
