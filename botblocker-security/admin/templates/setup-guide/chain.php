<?php

use BotBlocker\Component\Card;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SetupGuideViewModel $data ): void {
	$chain_body = require __DIR__ . '/chain-body.php';
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
	echo Card::make()
		->withTitle( __( 'Request Handling Chain', 'botblocker-security' ) )
		->block(
			static function () use ( $data, $chain_body ): void {
				$chain_body( $data );
				?>
				<div class="bbcs-mt-3"><a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( BotBlockerDocs::page( 'wordpress-lifecycle' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How the request chain works', 'botblocker-security' ); ?> &#8599;</a></div>
				<?php
			}
		);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
};
