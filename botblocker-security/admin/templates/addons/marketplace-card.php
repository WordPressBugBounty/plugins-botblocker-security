<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (
	Botblocker_AddonsViewModel $data,
	string $slug,
	?Botblocker_AddonMarketItemData $bbcs_item,
	?Botblocker_AddonInstalledItemData $local
): void {
	$is_installed = $bbcs_item ? $bbcs_item->is_installed : ( $local !== null );
	$is_active    = $local ? $local->is_active : ( $bbcs_item ? $bbcs_item->is_active : false );
	$bbcs_settings_link = ( $is_active && $local && ! empty( $local->has_settings ) && ! empty( $data->urls->addons ) )
		? esc_url( $data->urls->addons . '#' . $slug )
		: ( ( $is_active && ! empty( $data->urls->addons ) ) ? esc_url( $data->urls->addons . '#' . $slug ) : '' );

	$show_toggle     = $local && ! $local->broken && ! $local->incompatible && ! $local->incompatible_remote && ( ! $bbcs_item || ! $bbcs_item->is_incompatible );
	$show_install    = $bbcs_item && ! $is_installed && ! $bbcs_item->is_incompatible;
	$is_broken       = $local && $local->broken;
	$is_incompatible = ( $bbcs_item && $bbcs_item->is_incompatible ) || ( $local && ( $local->incompatible || $local->incompatible_remote ) );
	$has_update      = $local && $local->update_avail;
	$settings_link   = $bbcs_settings_link;
	$local_mode      = $data->addons_local_mode;
	$item_name       = $bbcs_item ? ( $bbcs_item->name ?: $slug ) : ( $local ? ( $local->name ?: $slug ) : $slug );
	$item_icon       = $bbcs_item && $bbcs_item->icon ? $bbcs_item->icon : ( $local ? $local->icon : '' );
	$item_desc       = $bbcs_item ? $bbcs_item->description : ( $local ? $local->description : '' );
	$remote_ver      = $bbcs_item ? $bbcs_item->remote_ver : '';
?>
	<div class="bbcs-card bbcs-addon<?php echo $is_active ? ' bbcs-addon--active' : ''; ?>" data-addon-slug="<?php echo esc_attr($slug); ?>">
		<div class="bbcs-addon-head">
			<span class="bbcs-tile <?php echo $is_active ? 'bbcs-acc-green' : 'bbcs-acc-blue'; ?>">
				<?php if ($item_icon) : ?>
					<img src="<?php echo esc_url($item_icon); ?>" alt="" class="bbcs-tile-img">
				<?php else : ?>
					<svg class="bbcs-ico"><use href="#bbcs-i-puzzle"></use></svg>
				<?php endif; ?>
			</span>
			<div class="bbcs-addon-info">
				<div class="bbcs-addon-title-row">
					<span class="bbcs-addon-name"><?php echo esc_html($item_name); ?></span>
					<span class="bbcs-addon-ver">
						<?php if ($local) : ?>
							v<?php echo esc_html($local->version ?: ''); ?>
							<?php if ($remote_ver && $local->version !== $remote_ver) : ?>
								<span class="bbcs-dim"> → v<?php echo esc_html($remote_ver); ?></span>
							<?php endif; ?>
						<?php else : ?>
							v<?php echo esc_html($remote_ver ?: ''); ?>
						<?php endif; ?>
					</span>
				</div>
			</div>
			<div class="bbcs-addon-head-actions">
				<?php if ($is_active) : ?>
					<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e('Active', 'botblocker-security'); ?></span>
				<?php elseif ($is_installed) : ?>
					<span class="bbcs-tag bbcs-tag--blue"><?php esc_html_e('Disabled', 'botblocker-security'); ?></span>
				<?php elseif ($bbcs_item && $bbcs_item->show_installed) : ?>
					<span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro"><?php esc_html_e('PRO', 'botblocker-security'); ?></span>
				<?php endif; ?>
				<?php if ($bbcs_item && $bbcs_item->is_incompatible) : ?>
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
			<div class="bbcs-addon-desc"><?php echo esc_html( apply_filters( 'bbcs_addon_description', $item_desc, $slug ) ); ?></div>
		</div>
		<div class="bbcs-addon-divider"></div>
		<div class="bbcs-addon-footer">
			<div class="bbcs-addon-footer-left"<?php echo (! $is_installed) ? ' style="flex:1;"' : ''; ?>>
				<?php if ($local && $is_installed) : ?>
					<?php if ($show_toggle) : ?>
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
							<input type="hidden" name="action" value="bbcs_toggle_addon">
							<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
							<?php wp_nonce_field('bbcs_toggle_addon', 'bbcs_toggle_addon_nonce'); ?>
							<div class="bbcs-toggle-wrap">
								<button class="bbcs-toggle<?php echo $is_active ? ' is-on' : ''; ?>" role="switch" type="submit" aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>" title="<?php $is_active ? esc_attr_e('Deactivate', 'botblocker-security') : esc_attr_e('Activate', 'botblocker-security'); ?>"<?php echo ( ! $is_active && ! ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ) ) ? ' disabled' : ''; ?>><span class="bbcs-toggle-knob"></span></button>
								<span class="bbcs-toggle-label"><?php echo $is_active ? esc_html__('Enabled', 'botblocker-security') : esc_html__('Disabled', 'botblocker-security'); ?></span>
							</div>
						</form>
					<?php endif; ?>
				<?php elseif ($show_install && $bbcs_item) : ?>
					<?php if (! $data->addons_locked) : ?>
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
			</div>
			<div class="bbcs-addon-footer-right">
				<?php if ($local && $is_installed) : ?>
					<?php if ($is_broken) : ?>
						<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e('Broken', 'botblocker-security'); ?></button>
					<?php elseif ($is_incompatible) : ?>
						<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e('Incompatible', 'botblocker-security'); ?></button>
					<?php else : ?>
						<?php if ($has_update) : ?>
							<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
								<input type="hidden" name="action" value="bbcs_update_addon">
								<input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
								<input type="hidden" name="url" value="<?php echo esc_attr($bbcs_item ? $bbcs_item->url : $local->update_url); ?>">
								<input type="hidden" name="requires_core" value="<?php echo esc_attr($bbcs_item ? $bbcs_item->requires_core : $local->update_requires_core); ?>">
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
							<button type="submit" class="bbcs-btn bbcs-btn--icon bbcs-btn--danger" title="<?php esc_attr_e('Delete', 'botblocker-security'); ?>"<?php echo $local_mode ? ' disabled' : ''; ?>><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-trash"></use></svg></button>
						</form>
					<?php endif; ?>
				<?php elseif ($settings_link) : ?>
					<a href="<?php echo esc_url($settings_link); ?>" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e('Settings', 'botblocker-security'); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php
};
