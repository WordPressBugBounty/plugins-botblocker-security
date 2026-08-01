<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\SidebarNav;

return static function (Botblocker_Addons_View $view, Botblocker_AddonsViewModel $data): void {
	$active_tab = $data->active_tab_id;
	$is_addon_tab = isset( $data->addon_tabs[ $active_tab ] );

	// Upload section (hidden by default)
	$view->upload_section();

	// Locked/PRO notice (only on Marketplace, not on addon settings tabs)
	if ( ! $is_addon_tab ) {
		$view->locked_notice();
	}

	?>
	<div class="bbcs-settings-layout">
		<?php
		SidebarNav::make()
			->withGroups( $data->nav_groups )
			->withAriaLabel( __( 'Addons sections', 'botblocker-security' ) )
			->withSearchPlaceholder( __( 'Find addon…', 'botblocker-security' ) )
			->withDefaultIcon( 'store' )
			->withDefaultLabel( __( 'Marketplace', 'botblocker-security' ) )
			->render();
		?>

		<div class="bbcs-settings-content">
			<?php
			// Marketplace tabpanel (always rendered, shown/hidden by snav JS)
			$is_marketplace = ('Marketplace' === $active_tab);
			?>
			<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Marketplace"<?php echo $is_marketplace ? '' : ' hidden' ?>>
				<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
					<?php (require BOTBLOCKER_DIR . 'admin/templates/addons/marketplace-panel.php')($data, $is_marketplace); ?>
				</div>
			</div>

			<?php
			// Per-addon settings tabpanels (shown/hidden by snav JS)
			foreach ($data->addon_tabs as $slug => $addon) :
				$isActive = ($slug === $active_tab);
			?>
			<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="<?php echo esc_attr( $slug ); ?>"<?php echo $isActive ? '' : ' hidden' ?>>
				<form method="post" class="bbcs-addon-settings-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'save_botblocker_addon_settings', 'botblocker_addon_settings_nonce' ); ?>
					<input type="hidden" name="action" value="save_botblocker_addon_settings">
					<input type="hidden" name="bbcs_anchor" value="">

					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php echo esc_html( $addon->name ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Add-on settings', 'botblocker-security' ); ?></div></div>
						<div class="bbcs-pagehead-actions">
							<button class="bbcs-btn" type="button" data-bbcs-back-to-marketplace>
								<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg>
								<?php esc_html_e( 'Back to Marketplace', 'botblocker-security' ); ?>
							</button>
							<span class="bbcs-unsaved-label" style="display:none"><?php echo esc_html( _x( 'Not saved!', 'unsaved changes indicator', 'botblocker-security' ) ); ?></span>
							<button class="bbcs-btn bbcs-btn--pri" type="submit" name="save_settings"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-check"></use></svg><?php esc_html_e( 'Save', 'botblocker-security' ); ?></button>
						</div>
					</div>

					<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
						<?php $view->render_addon_settings( $slug ); ?>
					</div>
				</form>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php
};
