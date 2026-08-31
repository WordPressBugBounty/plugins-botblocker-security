<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (
	Botblocker_AddonMarketCardData $card,
	bool $addons_locked,
	string $cloud_api_url,
	bool $local_mode
): void {
	$slug = $card->slug;
	?>
	<div class="bbcs-card bbcs-addon<?php echo $card->is_active ? ' bbcs-addon--active' : ''; ?>" data-addon-slug="<?php echo esc_attr( $slug ); ?>">
		<div class="bbcs-addon-head">
			<span class="bbcs-tile <?php echo $card->is_active ? 'bbcs-acc-green' : 'bbcs-acc-blue'; ?>">
				<?php if ( $card->icon !== '' ) : ?>
					<img src="<?php echo esc_url( $card->icon ); ?>" alt="" class="bbcs-tile-img">
				<?php else : ?>
					<svg class="bbcs-ico"><use href="#bbcs-i-puzzle"></use></svg>
				<?php endif; ?>
			</span>
			<div class="bbcs-addon-info">
				<div class="bbcs-addon-title-row">
					<span class="bbcs-addon-name"><?php echo esc_html( $card->name ); ?></span>
					<span class="bbcs-addon-ver">
						<?php if ( $card->is_installed && $card->version !== '' ) : ?>
							v<?php echo esc_html( $card->version ); ?>
							<?php if ( $card->remote_ver !== '' && $card->version !== $card->remote_ver ) : ?>
								<span class="bbcs-dim"> &rarr; v<?php echo esc_html( $card->remote_ver ); ?></span>
							<?php endif; ?>
						<?php elseif ( $card->remote_ver !== '' ) : ?>
							v<?php echo esc_html( $card->remote_ver ); ?>
						<?php endif; ?>
					</span>
				</div>
			</div>
			<div class="bbcs-addon-head-actions">
				<?php if ( $card->is_active ) : ?>
					<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e( 'Active', 'botblocker-security' ); ?></span>
				<?php elseif ( $card->is_installed ) : ?>
					<span class="bbcs-tag bbcs-tag--blue"><?php esc_html_e( 'Disabled', 'botblocker-security' ); ?></span>
				<?php elseif ( $card->show_pro_badge ) : ?>
					<span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro"><?php esc_html_e( 'PRO', 'botblocker-security' ); ?></span>
				<?php endif; ?>
				<?php if ( $card->is_incompatible ) : ?>
					<span class="bbcs-tag bbcs-tag--red"><?php esc_html_e( 'Incompatible', 'botblocker-security' ); ?></span>
				<?php endif; ?>
				<?php if ( $card->has_update ) : ?>
					<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e( 'Update', 'botblocker-security' ); ?></span>
				<?php endif; ?>
				<?php if ( $card->show_local_incompatible ) : ?>
					<span class="bbcs-tag bbcs-tag--red"><?php esc_html_e( 'Incompatible', 'botblocker-security' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<div class="bbcs-addon-body">
			<div class="bbcs-addon-desc"><?php echo esc_html( $card->description ); ?></div>
		</div>
		<div class="bbcs-addon-divider"></div>
		<div class="bbcs-addon-footer">
			<div class="bbcs-addon-footer-left"<?php echo $card->footer_left_full ? ' style="flex:1;"' : ''; ?>>
				<?php if ( $card->is_installed ) : ?>
					<?php if ( $card->show_toggle ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
							<input type="hidden" name="action" value="bbcs_toggle_addon">
							<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
							<?php wp_nonce_field( 'bbcs_toggle_addon', 'bbcs_toggle_addon_nonce' ); ?>
							<div class="bbcs-toggle-wrap">
								<button class="bbcs-toggle<?php echo $card->is_active ? ' is-on' : ''; ?>" role="switch" type="submit" aria-checked="<?php echo $card->is_active ? 'true' : 'false'; ?>" title="<?php echo $card->is_active ? esc_attr__( 'Deactivate', 'botblocker-security' ) : esc_attr__( 'Activate', 'botblocker-security' ); ?>"<?php echo ( ! $card->is_active && $addons_locked ) ? ' disabled' : ''; ?>><span class="bbcs-toggle-knob"></span></button>
								<span class="bbcs-toggle-label"><?php echo $card->is_active ? esc_html__( 'Enabled', 'botblocker-security' ) : esc_html__( 'Disabled', 'botblocker-security' ); ?></span>
							</div>
						</form>
					<?php endif; ?>
				<?php elseif ( $card->show_install ) : ?>
					<?php if ( ! $addons_locked ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-addon-install-form">
							<input type="hidden" name="action" value="bbcs_install_addon">
							<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
							<input type="hidden" name="url" value="<?php echo esc_attr( $card->install_url ); ?>">
							<?php wp_nonce_field( 'bbcs_install_addon', 'bbcs_install_addon_nonce' ); ?>
							<button class="bbcs-btn bbcs-btn--icon bbcs-btn--full" type="submit"><?php esc_html_e( 'Install', 'botblocker-security' ); ?></button>
						</form>
					<?php else : ?>
						<a href="<?php echo esc_url( $cloud_api_url ); ?>" class="bbcs-btn bbcs-btn--icon bbcs-btn--full" title="<?php esc_attr_e( 'Get BotBlocker PRO', 'botblocker-security' ); ?>"><?php esc_html_e( 'Requires PRO', 'botblocker-security' ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<div class="bbcs-addon-footer-right">
				<?php if ( $card->is_installed ) : ?>
					<?php if ( $card->is_broken ) : ?>
						<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e( 'Broken', 'botblocker-security' ); ?></button>
					<?php elseif ( $card->is_footer_incompatible ) : ?>
						<button class="bbcs-btn bbcs-btn--icon" disabled><?php esc_html_e( 'Incompatible', 'botblocker-security' ); ?></button>
					<?php else : ?>
						<?php if ( $card->has_update ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
								<input type="hidden" name="action" value="bbcs_update_addon">
								<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
								<input type="hidden" name="url" value="<?php echo esc_attr( $card->update_url ); ?>">
								<input type="hidden" name="requires_core" value="<?php echo esc_attr( $card->update_requires_core ); ?>">
								<?php wp_nonce_field( 'bbcs_update_addon', 'bbcs_update_addon_nonce' ); ?>
								<button type="submit" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e( 'Update', 'botblocker-security' ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg></button>
							</form>
						<?php endif; ?>

						<?php if ( $card->settings_link !== '' ) : ?>
							<a href="<?php echo esc_url( $card->settings_link ); ?>" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg></a>
						<?php endif; ?>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0" data-bbcs-confirm="<?php echo esc_attr__( 'Are you sure you want to delete this add-on?', 'botblocker-security' ); ?>">
							<input type="hidden" name="action" value="bbcs_delete_addon">
							<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
							<?php wp_nonce_field( 'bbcs_delete_addon', 'bbcs_delete_addon_nonce' ); ?>
							<button type="submit" class="bbcs-btn bbcs-btn--icon bbcs-btn--danger" title="<?php esc_attr_e( 'Delete', 'botblocker-security' ); ?>"<?php echo $local_mode ? ' disabled' : ''; ?>><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-trash"></use></svg></button>
						</form>
					<?php endif; ?>
				<?php elseif ( $card->settings_link !== '' ) : ?>
					<a href="<?php echo esc_url( $card->settings_link ); ?>" class="bbcs-btn bbcs-btn--icon" title="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
};
