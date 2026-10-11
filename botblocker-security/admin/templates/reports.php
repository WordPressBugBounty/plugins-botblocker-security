<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\Base;

return static function ( Botblocker_Reports_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="log">
					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'Log and Statistics', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Who visited the site and who was blocked', 'botblocker-security' ); ?></div><a class="bbcs-link bbcs-pagehead-doc" href="<?php echo esc_url( BotBlockerDocs::page( 'log-and-reports' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a></div>
						<div class="bbcs-pagehead-actions" data-bbcs-active-table="">
							<button class="bbcs-btn bbcs-btn--copy" type="button"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-copy"></use></svg><?php esc_html_e( 'Copy', 'botblocker-security' ); ?></button>
							<button class="bbcs-btn bbcs-btn--csv" type="button"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Export CSV', 'botblocker-security' ); ?></button>
							<button class="bbcs-btn bbcs-btn--excel" type="button"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Export Excel', 'botblocker-security' ); ?></button>
							<?php echo Base::help( __( 'Exported files contain IP addresses, host names and user agents.', 'botblocker-security' ), BotBlockerDocs::page( 'log-and-reports', 'handle-exports-with-care' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
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
