<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_CloudApi_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="pro">
					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'BotBlocker PRO', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Cloud protection and premium features', 'botblocker-security' ); ?></div></div>
					</div>
					<?php $view->status(); ?>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
<?php
};
