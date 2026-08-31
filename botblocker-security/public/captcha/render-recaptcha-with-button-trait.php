<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderRecaptchaWithButtonTrait {

	private function getRecaptchaWithButtonData() {
		if ( (int) $this->BBCS->settings->recaptcha_v3_ipv6_block === 1 && $this->BBCS->ip_version == 6 ) {
			return $this->getSimpleButtonData();
		}
		$nonce       = $this->createChallenge( 'confirm', 3 );
		$correctHash = $this->answerHash( $nonce, 'confirm' );
		$style0      = 'o' . md5( $correctHash );
		$onestyle    = array();
		$onebtns     = array();

		$onestyle[] = '.' . $style0 . ' {} ';
		$onebtns[]  = array(
			'html'    => '<div style="cursor: pointer;" class="' . $style0 . ' ' . 's' . md5( 'botblocker-btn-success' . $this->BBCS->time ) . '" onclick="' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $correctHash . '\')">' . self::t( 'Go to website' ) . '</div>',
			'visible' => true,
		);

		for ( $i = 0; $i < wp_rand( 2, 6 ); $i++ ) {
			$fakeHash   = $this->answerHash( $nonce, 'decoy_' . wp_rand( 1, 99999 ) );
			$fakeStyle  = 'o' . md5( $fakeHash );
			$onestyle[] = '.' . $fakeStyle . ' {display: none;} ';
			$onebtns[]  = array(
				'html'    => '<div style="cursor: pointer;" class="' . $fakeStyle . ' ' . 's' . md5( 'botblocker-btn-success' . $this->BBCS->time ) . '" onclick="' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $fakeHash . '\')">' . self::t( 'Go to website' ) . '</div>',
				'visible' => false,
			);
		}

		shuffle( $onebtns );
		shuffle( $onestyle );

		return array(
			'mode'   => 3,
			'params' => array(
				'buttons'      => $onebtns,
				'styles'       => $onestyle,
				'confirmText'  => self::t( 'Confirm that you are human:' ),
				'loadingText'  => self::t( 'Loading...' ),
				'recaptchaKey' => $this->BBCS->settings->recaptcha_key2,
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			),
		);
	}
}
