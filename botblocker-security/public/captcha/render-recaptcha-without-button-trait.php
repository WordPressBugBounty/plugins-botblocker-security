<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderRecaptchaWithoutButtonTrait {

	private function getRecaptchaWithoutButtonData() {
		$nonce = $this->createChallenge( 'confirm', 4 );
		$hash0 = $this->answerHash( $nonce, 'confirm' );

		return array(
			'mode'   => 4,
			'params' => array(
				'confirmText'  => __( 'Confirm that you are human:', 'botblocker-security' ),
				'recaptchaKey' => $this->BBCS->settings->recaptcha_key2,
				'hash'         => $hash0,
				'loadingText'  => __( 'Loading...', 'botblocker-security' ),
			),
		);
	}
}
