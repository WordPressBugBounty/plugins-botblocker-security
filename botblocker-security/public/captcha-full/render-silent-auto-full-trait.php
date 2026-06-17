<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderSilentAutoTrait {

	private function renderSilentAuto(): string {
		$nonce = $this->createChallenge( 'silent_auto', 8 );
		$hash  = $this->answerHash( $nonce, 'silent_auto' );

		$denied_text = esc_js( __( 'Access denied. Please enable JavaScript and cookies.', 'botblocker-security' ) );
		$challenge   = 'window.bbcs_challenge_token = "' . esc_js( $this->getChallengeToken() ) . '";';

		return $challenge . '
    var bbcsM8r = 0;
    try { bbcsM8r = parseInt(sessionStorage.getItem("bbcsMode8Retries") || "0", 10); } catch(e) {}
    if (bbcsM8r < 2) {
        try { sessionStorage.setItem("bbcsMode8Retries", String(bbcsM8r + 1)); } catch(e) {}
        ' . $this->botblocker_check_function_name . '("post", data, "' . esc_js( $hash ) . '");
    } else {
        try { sessionStorage.removeItem("bbcsMode8Retries"); } catch(e) {}
        var bbcsM8c = document.getElementById("content");
        if (bbcsM8c) { bbcsM8c.innerHTML = \'<p style="text-align:center;color:#c0392b;">' . $denied_text . '</p>\'; }
    }';
	}
}
