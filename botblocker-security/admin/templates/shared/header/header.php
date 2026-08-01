<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_HeaderViewModel $h ): void {
	?><header class="header">
	<div class="logo-container">
		<a href="<?php echo esc_url( $h->dashboard_url ); ?>" class="logo">
			<?php
			// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
			<?php echo '<img src="' . esc_url( $h->logo_url ) . '" height="50" alt="' . esc_attr( $h->site_name ) . '">'; ?>
		</a>
		<div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
			<i class="fas fa-bars" aria-label="<?php echo esc_attr__( 'Toggle sidebar', 'botblocker-security' ); ?>"></i>
		</div>
	</div>

	<div class="header-right">
		<?php if ( ! $h->wizard_completed ) : ?>
		<span class="bbcs-header-wizard-button">
			<a href="<?php echo esc_url( $h->wizard_url ); ?>" class="mt-2 btn btn-xs btn-primary">
				<i class="fa-solid fa-wand-magic-sparkles"></i>&nbsp;
				<?php esc_html_e( 'Setup Wizard', 'botblocker-security' ); ?>
			</a>
		</span>
		<span class="separator"></span>
		<?php else : ?>
		<span class="bbcs-header-wizard-button">
			<a href="<?php echo esc_url( $h->wizard_url ); ?>" class="mt-2 btn btn-xs btn-default">
				<i class="fa-solid fa-rotate"></i>&nbsp;
				<?php esc_html_e( 'Run Setup Wizard Again', 'botblocker-security' ); ?>
			</a>
		</span>
		<span class="separator"></span>
		<?php endif; ?>

		<span class="bbcs-header-pro-button">
			<?php if ( $h->has_pro == false ) : ?>
				<a href="<?php echo esc_url( $h->cloud_api_url ); ?>" class="mt-2 btn btn-xs btn-warning bbcs-header-upgrade-cta"><i class="fa-solid fa-crown"></i>&nbsp;<b>
				<?php esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); ?></b>
			</a>
			<?php endif; ?>
			<?php if ( $h->has_pro == true ) : ?>
				<a href="<?php echo esc_url( $h->cloud_api_url ); ?>" class="mt-2 btn btn-xs btn-default bbcs-cloud-api-color"><i class="fa-solid fa-crown"></i>&nbsp;<b>
					<?php esc_html_e( 'PRO is active', 'botblocker-security' ); ?></b>
				</a>
			<?php endif; ?>
		</span>
		<span class="separator"></span>
		<ul class="notifications">
			<?php
			( require BOTBLOCKER_DIR . 'admin/templates/shared/header/cron-tasks.php' )();
			( require BOTBLOCKER_DIR . 'admin/templates/shared/header/alerts.php' )( $h );
			( require BOTBLOCKER_DIR . 'admin/templates/shared/header/lang-options.php' )( $h );
			?>
		</ul>
		<span class="separator"></span>
		<?php
		( require BOTBLOCKER_DIR . 'admin/templates/shared/header/user-menu.php' )( $h );
		?>
	</div>
</header>
	<?php
};
