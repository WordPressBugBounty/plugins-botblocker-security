<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderRecaptchaWithButtonTrait {

	private function renderRecaptchaWithButton(): string {
		$nonce        = $this->createChallenge( 'confirm', 3 );
		$hash0        = $this->answerHash( $nonce, 'confirm' );
		$btnClass     = 's' . md5( 'botblocker-btn-success' . $this->BBCS->time );
		$i18n_confirm = esc_js( __( 'Confirm that you are human:', 'botblocker-security' ) );
		$i18n_loading = esc_js( __( 'Loading...', 'botblocker-security' ) );

		return '
        var script = document.createElement("script");
        script.src = "https://www.google.com/recaptcha/api.js";
        document.body.appendChild(script);
        script.onload = function() {
            document.getElementById("content").innerHTML = "<div style=\"max-width: 302px; text-align: center;margin: 0 auto;\"><p>' . $i18n_confirm . '</p><p class=\"g-recaptcha\" style=\"display: inline-block;\" data-sitekey=\"' . esc_attr( $this->BBCS->settings->recaptcha_key2 ) . '\" data-callback=\"onRecaptchaSuccess\">' . $i18n_loading . '</p></div>";
        }

        window.onRecaptchaSuccess = function(token) {
            data += "&g-recaptcha-response=" + token;
            document.getElementById("content").innerHTML = clean_and_decode_base64_to_utf8("' . base64_encode( '<div style="max-width: 302px; text-align: center;margin: 0 auto;"><div style="cursor: pointer;" class="' . $btnClass . '" onclick="' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $hash0 . '\')">' . esc_html__( 'Go to website', 'botblocker-security' ) . '</div></div>' ) . '");
        }
        ';
	}
}
