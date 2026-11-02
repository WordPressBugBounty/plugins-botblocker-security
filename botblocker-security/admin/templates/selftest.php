<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SelfTest_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
		<div class="bbcs-wrap">

			<section class="bbcs-page" data-page="selftest">
				<div class="bbcs-pagehead">
					<div>
						<div class="bbcs-pagehead-title"><?php esc_html_e( 'Protection Test', 'botblocker-security' ); ?></div>
						<div class="bbcs-pagehead-sub"><?php esc_html_e( 'See your site exactly as a new, unverified visitor sees it.', 'botblocker-security' ); ?></div>
					</div>
					<div class="bbcs-pagehead-actions">
						<button type="button" class="button button-primary bbcs-st-run" id="bbcs-selftest-run"><?php esc_html_e( 'Run test', 'botblocker-security' ); ?></button>
					</div>
				</div>
				<?php $view->body(); ?>
			</section>

		</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
<?php
};
