<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderColorButtonTrait {

	private function getColorButtonData(): array {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Captcha] getColorButtonData: GD=' . ( function_exists( 'imagecreatetruecolor' ) ? 'yes' : 'no' ) . ' openssl=' . ( function_exists( 'openssl_encrypt' ) ? 'yes' : 'no' ) );
		}
		$color_base64 = array(
			'RED'    => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==',
			'BLACK'  => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
			'YELLOW' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5/hPwAIAgL/4d1j8wAAAABJRU5ErkJggg==',
			'GRAY'   => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNs+A8AAgUBgQvw1B0AAAAASUVORK5CYII=',
			'BLUE'   => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPj/HwADBwIAMCbHYQAAAABJRU5ErkJggg==',
			'GREEN'  => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkaGD4DwACiQGBU29HsgAAAABJRU5ErkJggg==',
			'MAROON' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY2hgYAAAAYQAgVMkorQAAAAASUVORK5CYII=',
			'PURPLE' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY/jP8B8ABAAB/4jQ/cwAAAAASUVORK5CYII=',
		);

		$colors = $this->BBCS->list_of_colors_for_captcha;
		shuffle( $colors );
		$color = $colors[0];

		$nonce = $this->createChallenge( $color, 1 );

		shuffle( $colors );
		$colorClass = 's' . md5( 'botblocker-btn-color' . $this->BBCS->time );
		$buttonData = array();

		foreach ( $colors as $btnColor ) {
			$buttonData[] = array(
				'image' => $color_base64[ $btnColor ],
				'hash'  => $this->answerHash( $nonce, $btnColor ),
			);
		}
		shuffle( $buttonData );

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return $this->getSimpleButtonData();
		}

		$imageForCheck = imagecreatetruecolor( wp_rand( 1, 30 ), wp_rand( 1, 30 ) );

		$color_code = array(
			'RED'    => imagecolorallocate( $imageForCheck, wp_rand( 220, 255 ), wp_rand( 0, 30 ), wp_rand( 0, 30 ) ),
			'BLACK'  => imagecolorallocate( $imageForCheck, wp_rand( 0, 15 ), wp_rand( 0, 25 ), wp_rand( 0, 25 ) ),
			'YELLOW' => imagecolorallocate( $imageForCheck, wp_rand( 245, 255 ), wp_rand( 220, 255 ), wp_rand( 0, 25 ) ),
			'GRAY'   => imagecolorallocate( $imageForCheck, wp_rand( 120, 130 ), wp_rand( 125, 135 ), wp_rand( 125, 135 ) ),
			'BLUE'   => imagecolorallocate( $imageForCheck, wp_rand( 0, 30 ), wp_rand( 0, 30 ), wp_rand( 155, 255 ) ),
			'GREEN'  => imagecolorallocate( $imageForCheck, wp_rand( 0, 30 ), wp_rand( 125, 250 ), wp_rand( 0, 30 ) ),
			'MAROON' => imagecolorallocate( $imageForCheck, wp_rand( 120, 130 ), wp_rand( 0, 20 ), wp_rand( 0, 20 ) ),
			'PURPLE' => imagecolorallocate( $imageForCheck, wp_rand( 120, 130 ), wp_rand( 0, 20 ), wp_rand( 120, 130 ) ),
		);

		imagefill( $imageForCheck, 0, 0, $color_code[ $color ] );
		ob_start();
		imagepng( $imageForCheck );
		if ( PHP_VERSION_ID < 80000 ) {
			imagedestroy( $imageForCheck );
		}
		$targetImgData = base64_encode( ob_get_clean() );

		return array(
			'mode'   => 1,
			'params' => array(
				'buttons'        => $buttonData,
				'instruction'    => self::t( 'Click on the matching color' ),
				'colorImageData' => $targetImgData,
				'colorClass'     => $colorClass,
			),
		);
	}
}
