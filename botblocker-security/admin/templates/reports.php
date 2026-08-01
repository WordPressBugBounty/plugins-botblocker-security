<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_Reports_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="log">
					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'Log and Statistics', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Who visited the site and who was blocked', 'botblocker-security' ); ?></div></div>
						<div class="bbcs-pagehead-actions" data-bbcs-active-table="">
							<button class="bbcs-btn bbcs-btn--copy" type="button"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-copy"></use></svg><?php esc_html_e( 'Copy', 'botblocker-security' ); ?></button>
							<button class="bbcs-btn bbcs-btn--csv" type="button"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Export CSV', 'botblocker-security' ); ?></button>
							<button class="bbcs-btn bbcs-btn--excel" type="button"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Export Excel', 'botblocker-security' ); ?></button>
						</div>
					</div>
					<?php $view->body(); ?>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
	<?php
	$view->modals();
};
