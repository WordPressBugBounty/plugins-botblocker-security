<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerLocalReCaptchaTrait {

	private function processReCaptchaV3() {
		if ( $this->settings->recaptcha_check == 1 && ! empty( $this->settings->recaptcha_secret3 ) && ! empty( $this->post_recaptcha_token ) && ! ( $this->settings->recaptcha_v3_ipv6_block == 1 && $this->ip_version == 6 ) ) {

			$args = array(
				'body'      => array(
					'secret'   => $this->settings->recaptcha_secret3,
					'response' => $this->post_recaptcha_token,
					'remoteip' => $this->ip,
				),
				'timeout'   => 10,
				'headers'   => array(
					'Referer'    => $this->referer,
					'User-Agent' => $this->useragent,
				),
				'sslverify' => true,
			);

			$resp = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', $args );

			if ( is_wp_error( $resp ) ) {
				$this->post_recaptcha_score = 0;
				$payload                    = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'reCaptcha request failed', '' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( (array) $payload );
				}
				wp_send_json( $payload );
			}

			$body = wp_remote_retrieve_body( $resp );
			$re   = json_decode( $body, true );

			if ( isset( $re['score'] ) ) {
				$this->post_recaptcha_score = trim( (string) $re['score'] );
			} else {
				$this->post_recaptcha_score = 0;
				$payload                    = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'reCaptcha Error', '' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( (array) $payload );
				}
				wp_send_json( $payload );
			}

			$force_cloud_active = ( $this->settings->force_cloud_validation == 1 && $this->settings->cloud_api_tier === BBCS_CLOUD_TIER_ULTIMATE );
			if ( $this->post_recaptcha_score <= $this->settings->recaptcha_tresshold && ! $force_cloud_active ) {
				$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'reCaptcha threshold failed', '' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( (array) $payload );
				}
				wp_send_json( $payload );
			}
		} else {
			$this->post_recaptcha_score = 0;
		}
	}
}
