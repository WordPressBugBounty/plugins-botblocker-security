<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderSimpleButtonTrait {

	private function renderSimpleButton(): string {
		$nonce    = $this->createChallenge( 'confirm', 0 );
		$hash0    = $this->answerHash( $nonce, 'confirm' );
		$btnClass = 's' . md5( 'botblocker-btn-success' . $this->BBCS->time );

		return 'document.getElementById("content").innerHTML = clean_and_decode_base64_to_utf8("' . base64_encode( '<p>' . __( 'Confirm that you are human:', 'botblocker-security' ) . '</p><div style="cursor:pointer;" class="' . $btnClass . '" onclick="' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $hash0 . '\')">' . __( "I'm not a robot", 'botblocker-security' ) . '</div>' ) . '");';
	}
}
