<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_Tools_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="tools">
					<form method="post" id="bbcs-tools-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'save_botblocker_tools', 'botblocker_tools_nonce' ); ?>
						<input type="hidden" name="action" value="save_botblocker_tools">
						<input type="hidden" name="bbcs_anchor" id="bbcs_anchor" value="">

						<div class="bbcs-pagehead">
							<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'Tools', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Performance, maintenance, and service parameters', 'botblocker-security' ); ?></div></div>
							<div class="bbcs-pagehead-actions">
							<button class="bbcs-btn" type="button" onclick="window.location.href=window.location.href.split('#')[0]" data-bbcs-reset>
								<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg>
								<?php esc_html_e( 'Reset', 'botblocker-security' ); ?>
							</button>
							<span class="bbcs-unsaved-label" id="bbcs-unsaved-label" style="display:none"><?php echo esc_html( _x( 'Not saved!', 'unsaved changes indicator', 'botblocker-security' ) ); ?></span>
							<button class="bbcs-btn bbcs-btn--pri" type="submit" name="save_settings"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-check"></use></svg><?php esc_html_e( 'Save', 'botblocker-security' ); ?></button>
						</div>
						</div>

						<?php $view->tools_content(); ?>
					</form>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
	<?php
	$view->modals();
};
