<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_Addons_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="addons">
					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'Addons', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Modules that enhance BotBlocker', 'botblocker-security' ); ?></div></div>
						<div class="bbcs-pagehead-actions">
							<button class="bbcs-btn" id="bbcs-toggle-upload"<?php echo $view->getData()->addons_local_mode ? ' disabled' : ''; ?>><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Upload ZIP', 'botblocker-security' ); ?></button>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
								<input type="hidden" name="action" value="bbcs_update_all_addons">
								<?php wp_nonce_field( 'bbcs_update_all_addons', 'bbcs_update_all_addons_nonce' ); ?>
								<button type="submit" class="bbcs-btn bbcs-btn--pri" id="bbcs-update-all"<?php echo $view->updates_count() > 0 ? '' : ' hidden'; ?>><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg><?php echo esc_html( sprintf( /* translators: %d: number of available addon updates */ __( 'Update All (%d)', 'botblocker-security' ), $view->updates_count() ) ); ?></button>
							</form>
						</div>
					</div>
					<?php $view->addons_content(); ?>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
<?php
};
