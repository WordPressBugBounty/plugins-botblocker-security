<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerResponseSigningTrait {

	private function sign_response_payload( array $payload ): array {
		$json                 = wp_json_encode( $payload );
		$payload['bbcs_sig']  = hash_hmac( 'sha256', $json, $this->settings->salt );
		return $payload;
	}
}
