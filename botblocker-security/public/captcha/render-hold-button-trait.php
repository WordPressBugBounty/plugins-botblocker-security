<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BBCS_RenderHoldButtonTrait {

	private function getHoldButtonData() {
		$duration   = wp_rand( 2500, 3500 );
		$zone_width = wp_rand( 12, 18 );
		$zone_start = wp_rand( 40, 85 - $zone_width );
		$zone_end   = $zone_start + $zone_width;

		$nonce        = $this->createChallenge( 'hold_confirm', 7 );
		$correct_hash = $this->answerHash( $nonce, 'hold_confirm' );

		return array(
			'mode'   => 7,
			'params' => array(
				'duration'        => $duration,
				'zoneStart'       => $zone_start,
				'zoneEnd'         => $zone_end,
				'correctHash'     => $correct_hash,
				'instructionText' => __( 'Hold the button and release in the green zone', 'botblocker-security' ),
				'buttonText'      => __( 'HOLD', 'botblocker-security' ),
				'tooEarlyText'    => __( 'Too early. Try again.', 'botblocker-security' ),
				'tooLateText'     => __( 'Too late. Try again.', 'botblocker-security' ),
				'successText'     => __( 'Verifying...', 'botblocker-security' ),
				'failText'        => __( 'Verification failed.', 'botblocker-security' ),
				'maxAttempts'     => 3,
			),
		);
	}
}
