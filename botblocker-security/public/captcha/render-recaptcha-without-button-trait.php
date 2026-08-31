<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderRecaptchaWithoutButtonTrait {

	private function getRecaptchaWithoutButtonData() {
		if ( (int) $this->BBCS->settings->recaptcha_v3_ipv6_block === 1 && $this->BBCS->ip_version == 6 ) {
			return $this->getSimpleButtonData();
		}
		$nonce = $this->createChallenge( 'confirm', 4 );
		$hash0 = $this->answerHash( $nonce, 'confirm' );

		return array(
			'mode'   => 4,
			'params' => array(
				'confirmText'  => self::t( 'Confirm that you are human:' ),
				'recaptchaKey' => $this->BBCS->settings->recaptcha_key2,
				'hash'         => $hash0,
				'loadingText'  => self::t( 'Loading...' ),
			),
		);
	}
}
