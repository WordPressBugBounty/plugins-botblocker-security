<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderColorButtonTrait {

	/**
	 * Mode 1: Click on similar color
	 *
	 * @return string JavaScript code
	 */
	private function renderColorButton(): string {
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
		$tags = array( 'div', 'span', 'b', 'strong', 'i', 'em' );
		shuffle( $tags );
		$buttons = array();

		foreach ( $colors as $btnColor ) {
			$hash      = $this->answerHash( $nonce, $btnColor );
			$buttons[] = '<' . $tags[0] . ' style=\"background-image: url(data:image/png;base64,' . $color_base64[ $btnColor ] . ');\" class=\"' . 's' . md5( 'botblocker-btn-color' . $this->BBCS->time ) . '\" onclick=\"' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $hash . '\')\"></' . $tags[0] . '> ';
		}
		shuffle( $buttons );
		$buttons = '<div style=\"max-width: 200px;\">' . implode( '', $buttons ) . '</div>';

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
		$image_data = ob_get_contents();
		ob_end_clean();

		return 'document.getElementById("content").innerHTML = "<div class=\"s' . md5( 'botblocker-btn-color' . $this->BBCS->time ) . '\" style=\"cursor: none; pointer-events: none; background-image: url(data:image/png;base64,' . base64_encode( $image_data ) . ');\" /></div><p>' . esc_js( __( 'Click on the matching color', 'botblocker-security' ) ) . '</p>' . $buttons . '";';
	}
}
