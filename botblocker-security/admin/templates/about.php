<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_About_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="support">
					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'Support & About', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Contacts, documentation and server status', 'botblocker-security' ); ?></div></div>
					</div>
					<?php $view->body(); ?>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
<?php
};
