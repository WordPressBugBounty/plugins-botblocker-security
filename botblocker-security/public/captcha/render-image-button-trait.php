<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BBCS_RenderImageButtonTrait {

	private function getImageButtonData() {
		$color_ids = array(
			'RED'    => '1',
			'BLACK'  => '2',
			'YELLOW' => '3',
			'GRAY'   => '4',
			'BLUE'   => '5',
			'GREEN'  => '6',
			'MAROON' => '7',
			'PURPLE' => '8',
		);

		$colors = $this->BBCS->list_of_colors_for_captcha;
		shuffle( $colors );
		$color     = $colors[0];
		$colorhash = hash( 'sha256', $this->BBCS->settings->salt . $color . $this->BBCS->time . $this->BBCS->settings->cloud_api_pass . $this->BBCS->ip );

		$img_dir = $this->BBCS->dirs['public'] . 'img/' . $this->BBCS->settings->bbcs_captcha_img_pack . '/';

		$inline_mode = isset( $this->BBCS->settings->bbcs_captcha_img_inline )
			? (int) $this->BBCS->settings->bbcs_captcha_img_inline
			: 1;

		if ( $inline_mode === 1 ) {
			$button_images = array();
			foreach ( $colors as $btn_color ) {
				$hash_for_id = md5( $this->BBCS->time . $this->BBCS->settings->salt . $color_ids[ $btn_color ] );
				$img_path    = $img_dir . $color_ids[ $btn_color ] . '.jpg';
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$raw = file_exists( $img_path ) ? file_get_contents( $img_path ) : '';

				$button_images[] = array(
					'id'        => $hash_for_id,
					'imageData' => base64_encode( $raw ),
					'clickHash' => $btn_color . '|' . $colorhash,
				);
			}
			shuffle( $button_images );
		} else {
			/*
			 * Legacy mode: images loaded via separate AJAX requests.
			 * JS uses fetchAndSetImage() to load each image from the server.
			 */
			$button_elements = array();
			$image_requests  = array();
			foreach ( $colors as $btn_color ) {
				$hash_for_id       = md5( $this->BBCS->time . $this->BBCS->settings->salt . $color_ids[ $btn_color ] );
				$button_elements[] = '<span id="' . $hash_for_id . '" style="cursor: pointer;" onclick="' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $btn_color . '|' . $colorhash . '\')"></span>';
				$image_requests[]  = array(
					'imageParam' => $color_ids[ $btn_color ],
					'elementId'  => $hash_for_id,
				);
			}
			shuffle( $button_elements );
			shuffle( $image_requests );
		}

		$red   = wp_rand( 10, 50 );
		$green = wp_rand( 10, 50 );
		$blue  = wp_rand( 10, 50 );

		$image_for_check = imagecreatefromjpeg( $img_dir . $color_ids[ $color ] . '.jpg' );
		imagefilter( $image_for_check, IMG_FILTER_COLORIZE, $red, $green, $blue );
		imagefilter( $image_for_check, IMG_FILTER_BRIGHTNESS, wp_rand( -50, 50 ) );
		imagefilter( $image_for_check, IMG_FILTER_CONTRAST, wp_rand( -50, 50 ) );

		for ( $i = 0; $i < 5; $i++ ) {
			$line_color = imagecolorallocate( $image_for_check, wp_rand( 0, 255 ), wp_rand( 0, 255 ), wp_rand( 0, 255 ) );
			imageline(
				$image_for_check,
				wp_rand( 0, imagesx( $image_for_check ) ),
				wp_rand( 0, imagesy( $image_for_check ) ),
				wp_rand( 0, imagesx( $image_for_check ) ),
				wp_rand( 0, imagesy( $image_for_check ) ),
				$line_color
			);
		}

		imagefilter( $image_for_check, IMG_FILTER_GAUSSIAN_BLUR );
		imagefilter( $image_for_check, IMG_FILTER_MEAN_REMOVAL );
		ob_start();
		imagepng( $image_for_check );
		$image_data = ob_get_contents();
		if ( PHP_VERSION_ID < 80000 ) {
			imagedestroy( $image_for_check );
		}
		ob_end_clean();

		if ( $inline_mode === 1 ) {
			return array(
				'mode'   => 2,
				'params' => array(
					'targetImageData' => base64_encode( $image_data ),
					'instruction'     => __( 'If you are human, click on the similar image', 'botblocker-security' ),
					'buttonImages'    => $button_images,
				),
			);
		}

		return array(
			'mode'   => 2,
			'params' => array(
				'targetImageData'   => base64_encode( $image_data ),
				'instruction'       => __( 'If you are human, click on the similar image', 'botblocker-security' ),
				'buttons'           => $button_elements,
				'imageRequests'     => $image_requests,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'botblocker_nonce' ),
				'time'              => $this->BBCS->time,
				'selectRequestMode' => $this->BBCS->select_request_mode,
			),
		);
	}
}
