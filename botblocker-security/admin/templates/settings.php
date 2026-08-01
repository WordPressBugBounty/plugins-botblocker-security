<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_Settings_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="settings">

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="bbcs-settings-form">
						<input type="hidden" name="action" value="save_botblocker_settings">
						<?php wp_nonce_field( 'save_botblocker_settings', 'botblocker_settings_nonce' ); ?>
						<input type="hidden" name="bbcs_anchor" id="bbcs_anchor" value="">

						<?php $view->page_head(); ?>

						<?php $view->settings_content(); ?>
					</form>

					<?php $view->wizard_modal(); ?>

				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
<?php
};
