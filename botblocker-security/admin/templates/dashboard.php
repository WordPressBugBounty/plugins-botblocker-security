<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_Dashboard_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="home">
					<div class="bbcs-home">
						<div class="bbcs-fill">

							<?php $view->hero(); ?>

							<div class="bbcs-kpi-row">
								<?php $view->kpi(); ?>

								<?php $view->activity(); ?>
							</div>

							<?php $view->quick_links(); ?>

							<?php $view->health_status(); ?>

						</div>

						<?php $layout->sidebar(); ?>
					</div>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
<?php
	$view->modals();
};
