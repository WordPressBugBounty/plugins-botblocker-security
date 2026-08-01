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

							<?php $view->kpi(); ?>

							<?php $view->quick_links(); ?>

							<?php $view->activity(); ?>

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
