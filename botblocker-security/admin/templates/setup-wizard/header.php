<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void {
	?><div class="bbcs-plughead bbcs-plughead--wiz">
		<div class="bbcs-plughead-left">
			<a class="bbcs-logo" href="<?php echo esc_url( $d->dashboard_url ); ?>">
				<img class="bbcs-logo-img" src="<?php echo esc_url( $d->logo_url ); ?>" alt="BotBlocker" />
			</a>
			<span class="bbcs-chip bbcs-chip--kbd">v<?php echo esc_html( $d->version ); ?></span>
		</div>
		<div class="bbcs-plughead-right">
			<a class="bbcs-btn bbcs-btn--ghost" href="<?php echo esc_url( $d->dashboard_url ); ?>"><?php esc_html_e( 'Skip', 'botblocker-security' ); ?></a>
		</div>
	</div>
	<?php
};
