<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerCloudApiHooks {

	const RETRY_HOOK  = 'bbcs_retry_connect';
	const RETRY_DELAY = 3600;
	const FIRM_REFUSAL_CODES = array( 400, 403, 404, 409 );

	public static function connectApi(): void {
		check_ajax_referer( 'bbcs_connect_cloud_api_action', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is required.', 'botblocker-security' ) ) );
		}
		if ( BotBlockerFreemius::ownsKey( $api_key ) ) {
			$error = BotBlockerFreemius::activateKey( $api_key );
		} else {
			$error = self::connectKey( $api_key, 0 );
		}
		if ( null !== $error ) {
			wp_send_json_error( array( 'message' => $error ) );
		}

		wp_send_json_success( array( 'message' => __( 'Cloud API key connected and validated.', 'botblocker-security' ) ) );
	}

	public static function connectKey( string $key, int $license_id ): ?string {
		wp_unschedule_hook( self::RETRY_HOOK );
		$result = self::connect( $key, $license_id );
		if ( ! $result['retry'] ) {
			return $result['error'];
		}

		wp_schedule_single_event( time() + self::RETRY_DELAY, self::RETRY_HOOK, array( $key, $license_id ) );
		return __( 'Could not reach the license server. BotBlocker will retry automatically every hour.', 'botblocker-security' );
	}

	public static function retryConnect( $key, $license_id ): void {
		self::connectKey( (string) $key, (int) $license_id );
	}

	public static function connect( string $key, int $license_id ): array {
		$cloud = BotBlockerBackend::sendToCloud( array( 'cloud_api_key' => $key ), 'validate_api_key' );
		if ( is_array( $cloud ) && isset( $cloud['error'] ) && empty( $cloud['expired'] ) ) {
			$activated = self::activateKey( $key, $license_id );
			if ( isset( $activated['license_key'] ) ) {
				$cloud = BotBlockerBackend::sendToCloud( array( 'cloud_api_key' => $activated['license_key'] ), 'validate_api_key' );
			} elseif ( isset( $activated['message'] ) ) {
				return array(
					'error' => $activated['message'],
					'retry' => ! $activated['refused'],
				);
			} else {
				return array(
					'error' => __( 'Failed to connect to Cloud API. Try again later.', 'botblocker-security' ),
					'retry' => true,
				);
			}
		}

		$error = self::storeCloud( $cloud );
		$retry = false;
		if ( null !== $error && ( ! is_array( $cloud ) || empty( $cloud['expired'] ) ) ) {
			$retry = true;
		}
		return array(
			'error' => $error,
			'retry' => $retry,
		);
	}

	private static function storeCloud( $cloud ): ?string {
		if ( ! is_array( $cloud ) ) {
			return __( 'Failed to connect to Cloud API. Try again later.', 'botblocker-security' );
		}
		if ( isset( $cloud['expired'] ) && (int) $cloud['expired'] === 1 ) {
			return __( 'This license has expired. Renew it to connect.', 'botblocker-security' );
		}
		if ( isset( $cloud['error'] ) ) {
			return __( 'Invalid API key. Cloud API responded with: ', 'botblocker-security' ) . sanitize_text_field( (string) $cloud['error'] );
		}
		if ( empty( $cloud['api_key'] ) || empty( $cloud['api_secret'] ) ) {
			return __( 'Invalid API key.', 'botblocker-security' );
		}

		$cloud_api_tier = isset( $cloud['api_tier'] ) ? sanitize_text_field( $cloud['api_tier'] ) : '';
		if ( ! BotBlockerPro::isValidTier( $cloud_api_tier ) ) {
			$cloud_api_tier = '';
		}

		// BBCS-MULTISITE
		$bbcs_propagate = array(
			'cloud_api_type'   => BotBlockerPro::TYPE_EXTENDED,
			'cloud_api_key'    => $cloud['api_key'],
			'cloud_api_secret' => $cloud['api_secret'],
			'cloud_api_tier'   => $cloud_api_tier,
			'check'            => 1,
		);
		// An email-less validate response must not clobber the stored email (AUD-37).
		if ( isset( $cloud['email'] ) ) {
			$bbcs_propagate['cloud_api_email'] = $cloud['email'];
		}
		if ( $cloud_api_tier !== BotBlockerPro::TIER_ULTIMATE ) {
			$bbcs_propagate['force_cloud_validation'] = 0;
		}
		BotBlockerMultisite::syncCloudSettingsNetwork( $bbcs_propagate );

		return null;
	}

	public static function refreshApi(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }

		$res = self::refreshApiInternal();

		if ( $res === false ) {
			wp_send_json_error( array( 'error' => __( 'Failed to refresh Cloud API', 'botblocker-security' ) ) );
		} elseif ( is_string( $res ) ) {
			wp_send_json_error( array( 'error' => $res ) + self::remainingData() );
		}

		wp_send_json_success( self::remainingData() );
	}

	public static function deactivateApi(): void {
		check_ajax_referer( 'bbcs_deactivate_cloud_api_action', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }

		self::disconnect();

		wp_send_json_success( array( 'message' => __( 'Cloud API connection deactivated.', 'botblocker-security' ) ) );
	}

	public static function disconnect(): void {
		wp_unschedule_hook( self::RETRY_HOOK );
		// BBCS-MULTISITE
		BotBlockerMultisite::syncCloudSettingsNetwork( BotBlockerPro::disconnectedSettings() );

		BotBlockerPro::clearCache();
	}

	private static function activateKey( string $key, int $license_id ): array {
		$response = wp_remote_post(
			BOTBLOCKER_ACTIVATE_ENDPOINT,
			array(
				'timeout' => 10,
				'body'    => array(
					'license_key' => $key,
					'domain'      => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
					'license_id'  => $license_id,
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return array();
		}
		if ( 200 === (int) wp_remote_retrieve_response_code( $response ) && ! empty( $body['license_key'] ) && is_string( $body['license_key'] ) ) {
			return array( 'license_key' => sanitize_text_field( $body['license_key'] ) );
		}
		if ( ! empty( $body['message'] ) && is_string( $body['message'] ) ) {
			return array(
				'message' => sanitize_text_field( $body['message'] ),
				'refused' => in_array( (int) wp_remote_retrieve_response_code( $response ), self::FIRM_REFUSAL_CODES, true ),
			);
		}
		return array();
	}

	private static function checkKeyAfterRefreshError(): ?string {
		$cloud = BotBlockerBackend::sendToCloud( array( 'cloud_api_key' => BotBlockerPro::getKey() ), 'validate_api_key' );
		if ( ! is_array( $cloud ) ) {
			return null;
		}
		if ( ! empty( $cloud['rejected'] ) ) {
			self::disconnect();
			return null;
		}
		if ( ! empty( $cloud['expired'] ) ) {
			BotBlockerPro::setExpired();
			BotBlockerPro::checkExpiry();
			return __( 'This license has expired. Renew it to connect.', 'botblocker-security' );
		}
		return null;
	}

	private static function remainingData(): array {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-pro-status-viewmodel.php';
		$hits = BotBlockerPro::getRemainingHits();
		$days = BotBlockerPro::getRemainingDays();
		$data = array( 'alert_level' => Botblocker_ProStatusViewModel::calcAlertLevel( true, $hits, $days ) );
		if ( $hits !== false ) {
			$data['remaining_hits']      = (int) $hits;
			$data['remaining_hits_text'] = html_entity_decode( number_format_i18n( (int) $hits ), ENT_QUOTES, 'UTF-8' );
		}
		if ( $days !== false ) {
			$data['remaining_days']       = (int) $days;
			$data['remaining_days_text']  = BotBlockerPro::remainingDaysText( $days );
			$data['remaining_days_unit']  = BotBlockerPro::remainingDaysUnit( $days );
			$data['remaining_days_label'] = BotBlockerPro::remainingDaysLabel( $days );
		}
		return $data;
	}

	public static function refreshApiInternal() {
		if ( ! BotBlockerPro::isActive() ) {
			return false;
		}

		$BBCS         = BotBlocker::getInstance();
		$request_data = BotBlockerPro::buildAuthPayload( $BBCS->settings );
		$cloud = BotBlockerBackend::sendToCloud( $request_data, 'refresh_cloud_api' );

		if ( $cloud === false ) {
			return false;
		} elseif ( isset( $cloud['error'] ) ) {
			$expired = self::checkKeyAfterRefreshError();
			if ( null !== $expired ) {
				return $expired;
			}
			return $cloud['error'];
		}

		BotBlockerPro::setRemainingHits( $cloud['hits'] );
		BotBlockerPro::setRemainingDays( $cloud['days'] );

		BotBlockerPro::checkExpiry();

		return array(
			'hits' => $cloud['hits'],
			'days' => $cloud['days'],
		);
	}
}

add_action( 'wp_ajax_bbcs_connect_cloud_api', array( 'BotBlockerCloudApiHooks', 'connectApi' ) );
add_action( 'wp_ajax_bbcs_refresh_cloud_api', array( 'BotBlockerCloudApiHooks', 'refreshApi' ) );
add_action( 'wp_ajax_bbcs_deactivate_cloud_api', array( 'BotBlockerCloudApiHooks', 'deactivateApi' ) );
add_action( BotBlockerCloudApiHooks::RETRY_HOOK, array( 'BotBlockerCloudApiHooks', 'retryConnect' ), 10, 2 );
