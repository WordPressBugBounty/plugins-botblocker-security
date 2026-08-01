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
			}
		);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
};
