<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderSimpleButtonTrait {

	private function getSimpleButtonData(): array {
		$nonce    = $this->createChallenge( 'confirm', 0 );
		$hash0    = $this->answerHash( $nonce, 'confirm' );
		$btnClass = 's' . md5( 'botblocker-btn-success' . $this->BBCS->time );

		return array(
			'mode'   => 0,
			'params' => array(
				'buttonHash'  => $hash0,
				'buttonClass' => $btnClass,
				'confirmText' => __( 'Confirm that you are human:', 'botblocker-security' ),
				'buttonText'  => __( "I'm not a robot", 'botblocker-security' ),
			),
		);
	}
}
