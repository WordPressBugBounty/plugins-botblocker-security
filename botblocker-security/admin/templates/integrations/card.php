<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_IntegrationsViewModel $data ): void {
	?>
	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h bbcs-tabcard">
		<div class="bbcs-fieldcard-title bbcs-fs-xl bbcs-fw-black"><?php esc_html_e( 'Integrations', 'botblocker-security' ); ?></div>
		<div class="bbcs-tabs" role="tablist">
			<div role="tab" aria-selected="true" class="bbcs-tab is-active" data-tab="recaptcha-v2" tabindex="0"><?php esc_html_e( 'reCaptcha v2', 'botblocker-security' ); ?></div>
			<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="recaptcha-v3" tabindex="0"><?php esc_html_e( 'reCaptcha v3', 'botblocker-security' ); ?></div>
			<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="transients" tabindex="0"><?php esc_html_e( 'Transients', 'botblocker-security' ); ?></div>
			<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="memcached" tabindex="0"><?php esc_html_e( 'Memcached', 'botblocker-security' ); ?></div>
			<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="redis" tabindex="0"><?php esc_html_e( 'Redis', 'botblocker-security' ); ?></div>
			<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="cloud" tabindex="0"><?php esc_html_e( 'BotBlocker Cloud', 'botblocker-security' ); ?></div>
		</div>
		<div>
			<?php
			( require __DIR__ . '/recaptcha-v2.php' )( $data );
			( require __DIR__ . '/recaptcha-v3.php' )( $data );
			( require __DIR__ . '/transients.php' )( $data );
			( require __DIR__ . '/memcached.php' )( $data );
			( require __DIR__ . '/redis.php' )( $data );
			( require __DIR__ . '/botblocker-api.php' )( $data );
			?>
		</div>
	</div>
	<?php
};
