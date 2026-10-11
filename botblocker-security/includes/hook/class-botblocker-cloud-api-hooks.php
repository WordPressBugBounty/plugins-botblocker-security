<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerCloudApiHooks {

	public static function connectApi(): void {
		check_ajax_referer( 'bbcs_connect_cloud_api_action', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is required.', 'botblocker-security' ) ) );
		}
		$data  = array(
			'cloud_api_key' => $api_key,
		);
		$cloud = BotBlockerBackend::sendToCloud( $data, 'validate_api_key' );

		if ( ! is_array( $cloud ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to connect to Cloud API. Try again later.', 'botblocker-security' ) ) );
			return;
		}
		if ( isset( $cloud['expired'] ) && (int) $cloud['expired'] === 1 ) {
			wp_send_json_error( array( 'message' => __( 'This license has expired. Renew it to connect.', 'botblocker-security' ) ) );
			return;
		}
		if ( isset( $cloud['error'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API key. Cloud API responded with: ', 'botblocker-security' ) . sanitize_text_field( (string) $cloud['error'] ) ) );
			return;
		}
		if ( empty( $cloud['api_key'] ) || empty( $cloud['api_secret'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API key.', 'botblocker-security' ) ) );
			return;
		}

		$cloud_api_tier = isset( $cloud['api_tier'] ) ? sanitize_text_field( $cloud['api_tier'] ) : '';
		if ( ! BotBlockerPro::isValidTier( $cloud_api_tier ) ) {
			$cloud_api_tier = '';
		}

		// BBCS-MULTISITE
		$bbcs_propagate = array(
			'cloud_api_type'   => BotBlockerPro::TYPE_EXTENDED,
			'cloud_api_email'  => $cloud['email'],
			'cloud_api_key'    => $cloud['api_key'],
			'cloud_api_secret' => $cloud['api_secret'],
			'cloud_api_tier'   => $cloud_api_tier,
			'check'            => 1,
		);
		if ( $cloud_api_tier !== BotBlockerPro::TIER_ULTIMATE ) {
			$bbcs_propagate['force_cloud_validation'] = 0;
		}
		BotBlockerMultisite::syncCloudSettingsNetwork( $bbcs_propagate );

		wp_send_json_success( array( 'message' => __( 'Cloud API key connected and validated.', 'botblocker-security' ) ) );
	}

	public static function refreshApi(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }

		$res = self::refreshApiInternal();

		if ( $res === false ) {
			wp_send_json_error( array( 'error' => __( 'Failed to refresh Cloud API', 'botblocker-security' ) ) );
		} elseif ( is_string( $res ) ) {
			wp_send_json_error( array( 'error' => $res ) );
		}

		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-pro-status-viewmodel.php';
		$response_data = array(
			'remaining_hits'      => $res['hits'],
			'remaining_hits_text' => html_entity_decode( number_format_i18n( (int) $res['hits'] ), ENT_QUOTES, 'UTF-8' ),
			'remaining_days'      => $res['days'],
			'alert_level'         => Botblocker_ProStatusViewModel::calcAlertLevel( true, $res['hits'], $res['days'] ),
		);

		wp_send_json_success( $response_data );
	}

	public static function deactivateApi(): void {
		check_ajax_referer( 'bbcs_deactivate_cloud_api_action', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }

		// BBCS-MULTISITE
		BotBlockerMultisite::syncCloudSettingsNetwork( BotBlockerPro::disconnectedSettings() );

		BotBlockerPro::clearCache();

		wp_send_json_success( array( 'message' => __( 'Cloud API connection deactivated.', 'botblocker-security' ) ) );
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
