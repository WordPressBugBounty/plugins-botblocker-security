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
							<button class="bbcs-btn" id="bbcs-toggle-upload"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Upload ZIP', 'botblocker-security' ); ?></button>
							<button class="bbcs-btn bbcs-btn--pri" id="bbcs-update-all" hidden><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg><?php echo esc_html( sprintf( /* translators: %d: number of available addon updates */ __( 'Update All (%d)', 'botblocker-security' ), $view->updates_count() ) ); ?></button>
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
