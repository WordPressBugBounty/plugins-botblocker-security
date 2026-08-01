<?php

use BotBlocker\Component\Card;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SetupGuideViewModel $data ): void {
	$pro_body = require __DIR__ . '/pro-body.php';
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
	echo Card::make()
		->withClass( 'mb-4' )
		->withTitle( __( 'PRO Features', 'botblocker-security' ) )
		->block(
			static function () use ( $data, $pro_body ): void {
				$pro_body( $data );
			}
		);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
};
