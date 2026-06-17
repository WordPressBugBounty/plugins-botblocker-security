<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderImageButtonTrait {

	/**
	 * Mode 2: Select similar image
	 *
	 * @return string JavaScript code
	 */
	private function renderImageButton(): string {
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
		$img_dir   = $this->BBCS->dirs['public'] . 'img/' . $this->BBCS->settings->bbcs_captcha_img_pack . '/';
		$fn        = $this->botblocker_check_function_name;

		$inline_mode = isset( $this->BBCS->settings->bbcs_captcha_img_inline )
			? (int) $this->BBCS->settings->bbcs_captcha_img_inline
			: 1;

		$red   = wp_rand( 10, 50 );
		$green = wp_rand( 10, 50 );
		$blue  = wp_rand( 10, 50 );

		$image_file = $img_dir . $color_ids[ $color ] . '.jpg';
		if ( ! file_exists( $image_file ) ) {
			return $this->renderSimpleButton();
		}
		$image_for_check = imagecreatefromjpeg( $image_file );
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

		$target_b64 = base64_encode( $image_data );

		if ( $inline_mode === 1 ) {
			$buttons_js = array();
			foreach ( $colors as $btn_color ) {
				$hash_for_id = md5( $this->BBCS->time . $this->BBCS->settings->salt . $color_ids[ $btn_color ] );
				$img_path    = $img_dir . $color_ids[ $btn_color ] . '.jpg';
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$raw        = file_exists( $img_path ) ? file_get_contents( $img_path ) : '';
				$b64        = base64_encode( $raw );
				$click_hash = $btn_color . '|' . $colorhash;

				$buttons_js[] = '{id:"' . $hash_for_id . '",d:"' . $b64 . '",h:"' . $click_hash . '"}';
			}
			shuffle( $buttons_js );

            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			return '
            (function() {
                var c = document.getElementById("content");
                c.innerHTML = "";
                var tImg = document.createElement("img");
                tImg.src = "data:image/png;base64,' . $target_b64 . '";
                c.appendChild(tImg);
                var p = document.createElement("p");
                p.textContent = "' . esc_js( __( 'Click on the matching image', 'botblocker-security' ) ) . '";
                c.appendChild(p);
                var row = document.createElement("p");
                row.style.maxWidth = "500px";
                var items = [' . implode( ',', $buttons_js ) . '];
                for (var i = 0; i < items.length; i++) {
                    (function(item) {
                        var span = document.createElement("span");
                        span.id = item.id;
                        span.style.cursor = "pointer";
                        var img = document.createElement("img");
                        img.src = "data:image/jpeg;base64," + item.d;
                        span.appendChild(img);
                        span.addEventListener("click", function() {
                            ' . $fn . '("post", data, item.h);
                        });
                        row.appendChild(span);
                    })(items[i]);
                }
                c.appendChild(row);
            })();
            ';
		}

		/*
		 * Legacy mode: button images loaded via separate AJAX requests
		 * using fetchAndSetImage() for each of 8 images.
		 */
		$buttons            = array();
		$javaScriptFunction = array();

		foreach ( $colors as $btnColor ) {
			$hashForID            = md5( $this->BBCS->time . $this->BBCS->settings->salt . $color_ids[ $btnColor ] );
			$buttons[]            = '<span id=\"' . $hashForID . '\" style=\"cursor: pointer;\" onclick=\"' . $fn . '(\'post\', data, \'' . $btnColor . '|' . $colorhash . '\')\">' . '</span> ';
			$javaScriptFunction[] = 'fetchAndSetImage("' . $color_ids[ $btnColor ] . '", "' . $hashForID . '");';
		}

		shuffle( $buttons );
		shuffle( $javaScriptFunction );
		$buttons = '<p style=\"max-width: 500px;\">' . implode( '', $buttons ) . '</p>';

		$output = '';
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
		$output .= 'document.getElementById("content").innerHTML = "<img src=\"data:image/png;base64,' . $target_b64 . '\" /><p>' . esc_js( __( 'Click on the matching image', 'botblocker-security' ) ) . '</p>' . $buttons . '";';

		$output .= 'function fetchAndSetImage(param, imageId) {
        var url = \'' . admin_url( 'admin-ajax.php' ) . '\';
        var formData = new FormData();
        formData.append(\'action\', \'bbcs_botblocker_check\');
        formData.append(\'nonce\', \'' . wp_create_nonce( 'botblocker_nonce' ) . '\');
        formData.append(\'img\', param);
        formData.append(\'time\', "' . $this->BBCS->time . '");
        formData.append(\'' . $this->BBCS->select_request_mode . '\', \'img\');

        var requestOptions = {
            method: \'POST\',
            body: formData
        };

        fetch(url, requestOptions)
            .then(function(response) {
                if (!response.ok) { throw new Error("HTTP " + response.status); }
                return response.blob();
            })
            .then(function(blob) {
                var imageUrl = URL.createObjectURL(blob);
                var img = document.createElement(\'img\'); 
                img.src = imageUrl; 
                var span = document.getElementById(imageId);
                if (span) { span.appendChild(img); }
            })
            .catch(function(error) { console.error(\'Retrieve image error:\', error); });
        }
        ' . implode( "\n", $javaScriptFunction );

		return $output;
	}
}
