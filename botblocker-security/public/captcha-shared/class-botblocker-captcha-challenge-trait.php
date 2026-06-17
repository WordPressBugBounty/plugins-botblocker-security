<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BBCS_CaptchaChallengeTrait {

	protected function createChallenge( string $correctAnswer, int $mode ): string {
		$nonce = bin2hex( random_bytes( 16 ) );
		$ttl   = ( $mode === 8 ) ? 3600 : 600;

		if ( function_exists( 'openssl_encrypt' ) ) {
			$key                  = hash( 'sha256', $this->BBCS->settings->salt, true );
			$iv                   = random_bytes( 16 );
			$payload              = wp_json_encode(
				array(
					'n' => $nonce,
					'a' => (string) $correctAnswer,
					't' => (string) $this->BBCS->time,
					'i' => $this->BBCS->ip,
					'm' => $mode,
				)
			);
			$encrypted            = openssl_encrypt( $payload, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
			$this->challengeToken = base64_encode( $iv . $encrypted );
			set_transient( 'bbcs_ct_' . $nonce, 1, $ttl );
		} else {
			$payload              = wp_json_encode(
				array(
					'n' => $nonce,
					't' => (string) $this->BBCS->time,
					'i' => $this->BBCS->ip,
					'm' => $mode,
				)
			);
			$data                 = base64_encode( $payload );
			$hmac                 = hash_hmac( 'sha256', $data, $this->BBCS->settings->salt );
			$this->challengeToken = $data . '.' . $hmac;
			set_transient( 'bbcs_ct_' . $nonce, array( 'a' => (string) $correctAnswer ), $ttl );
		}

		return $nonce;
	}

	protected function answerHash( string $nonce, string $answer ): string {
		return hash( 'sha256', $this->BBCS->settings->salt . $nonce . (string) $answer . $this->BBCS->time . $this->BBCS->ip );
	}

	public function getChallengeToken(): string {
		return $this->challengeToken;
	}
}
