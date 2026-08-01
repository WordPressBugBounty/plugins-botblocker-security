<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SetupGuide_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="status">
					<?php $view->status_pagehead(); ?>
					<div class="bbcs-home">
						<?php $view->status(); ?>
					</div>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
	<?php
	$view->wizard_modal();
};
