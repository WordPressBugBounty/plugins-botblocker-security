<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerLocalTrait {

	public function processLocalRequest() {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( isset( $_COOKIE[ $this->settings->cookie ] ) ) {
			$this->uid = preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ $this->settings->cookie ] ) ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Local] processLocalRequest: entry. post_data_keys=' . implode( ',', array_keys( $_POST ) ) );
		}

		global $wpdb;
		$this->post_start_time      = microtime( true );
		$this->post_recaptcha_score = 0;

		$message = 'Local verification passed';

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			$payload = array( 'error' => 'Error NoPost' );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Local] EXIT: Error NoPost' );
			}
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( isset( $_POST['cid'] ) ) {
			$this->cid = trim( preg_replace( '/[^0-9\.]/', '', sanitize_text_field( wp_unslash( $_POST['cid'] ) ) ) );
		} else {
			$payload = array( 'error' => 'Empty CID' );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Local] EXIT: Empty CID' );
			}
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		$this->processPostData();
		$this->processServerData();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Local] after processPostData/processServerData. cookie_disabled=' . $this->post_cookie_disabled );
		}

		if ( $this->post_cookie_disabled == 1 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Cookies disabled', '' );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Local] EXIT: Cookies disabled' );
			}
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		// Reject tokens older than 1 hour. $this->date is the page-generation timestamp
		// sent by the client; $this->time is the current server time (set at request start).
		if ( $this->time - (int) $this->date > 3600 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Token Expired', '' );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Local] EXIT: Token Expired' );
			}
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		$this->processTimeZone();
		$this->processRequiredParams();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Local] after processRequiredParams (passed all checks)' );
		}

		$force_cloud = ( $this->settings->force_cloud_validation == 1 && BotBlockerPro::isUltimate() );

		if ( $this->settings->check == 1 && $force_cloud ) {
			$this->processCloudCheck();
		}

		$this->processAdblockerDetect();
		$this->processAntidetect();

		if ( $this->settings->fingerprint_sticky_block == 1
			&& is_array( $this->post_antidetect_scope )
			&& ! empty( $this->post_antidetect_scope['browserFingerprint'] )
		) {
			$fp_record = BotBlockerStore::checkFingerprint( $this->post_antidetect_scope['browserFingerprint'] );
			if ( $fp_record !== null && (string) $fp_record['status'] === 'blocked' ) {
				$payload = BotBlockerStore::localCheckResult(
					BBCS_LOCAL_RESULT_ERROR,
					'Fingerprint block (sticky): ' . ( $fp_record['last_block_reason'] ?? '' ),
					''
				);
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Local] EXIT: Fingerprint sticky-block. fp=' . substr( $this->post_antidetect_scope['browserFingerprint'], 0, 8 ) );
				}
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( (array) $payload );
				}
				wp_send_json( $payload );
			}
		}

		$this->processReCaptchaV3();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Local] after processReCaptchaV3. post_recaptcha_score=' . $this->post_recaptcha_score );
		}

		if ( $this->settings->check == 1 && ! $force_cloud ) {
			$this->processCloudCheck();
		}

		if ( $this->settings->botblocker_force_check == 1 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Force check captcha required', '' );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Local] EXIT: Force check required' );
			}
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		$this->post_hash_cookie = $this->create_session_token() . '-' . $this->time;
		$payload                = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_COOKIE, $message, $this->post_hash_cookie );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Local] EXIT: SUCCESS. Setting cookie.' );
		}
		if ( $this->settings->bbcs_ddos_resilience == 1 ) {
			$payload = $this->sign_response_payload( (array) $payload );
			if ( ! headers_sent() && ! empty( $this->uid ) ) {
				header( 'X-BBCS-' . $this->uid . ': ' . $this->post_hash_cookie, false );
			}
		}
		wp_send_json( $payload );
	}
}
