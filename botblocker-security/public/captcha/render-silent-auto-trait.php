<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BBCS_RenderSilentAutoTrait {

	private function getSilentAutoData() {
		$nonce = $this->createChallenge( 'silent_auto', 8 );
		$hash  = $this->answerHash( $nonce, 'silent_auto' );

		return array(
			'mode'   => 8,
			'params' => array(
				'deniedText' => self::t( 'Access denied. Please enable JavaScript and cookies.' ),
				'silentHash' => $hash,
			),
		);
	}
}
