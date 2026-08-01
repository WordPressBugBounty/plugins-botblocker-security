<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerCloudApiHooks {

	public static function registerRewriteRules(): void {
		add_rewrite_tag( '%botblocker_cloud_api%', '([01])' );
		add_rewrite_rule( '^botblocker_cloud_api/?$', 'index.php?botblocker_cloud_api=1', 'top' );
	}

	public static function addQueryVars( array $vars ): array {
		$vars[] = 'botblocker_cloud_api';
		return $vars;
	}

	public static function parseRequest( $wp ): void {
	    /* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	    /* phpcs:enable WordPress.Security.NonceVerification.Recommended */
		$path      = wp_parse_url( $request_uri, PHP_URL_PATH );
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$rel       = '/' . ltrim( substr( (string) $path, strlen( (string) $home_path ) ), '/' );
		if ( rtrim( (string) $rel, '/' ) === '/botblocker_cloud_api' ) {
			$wp->query_vars['botblocker_cloud_api'] = '1';
		}
	}

	public static function fetchApiKey(): void {
		check_ajax_referer( 'bbcs_fetch_cloud_api_key_action', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }

		$data = array();

		$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_URL, 'fetch_api_key' );
		if ( $cloud === false || isset( $cloud['error'] ) ) {
			$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_GS_URL, 'fetch_api_key' );
		}

		if ( $cloud === false ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve API key. Try again later.', 'botblocker-security' ) ) );
		} elseif ( isset( $cloud['error'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Cloud API responded with error: ', 'botblocker-security' ) . $cloud['error'] ) );
		} elseif ( empty( $cloud['api_key'] ) || empty( $cloud['api_secret'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No API key found for this domain.', 'botblocker-security' ) ) );
		}

		BotBlockerPro::setRemainingHits( $cloud['hits'] );
		BotBlockerPro::setRemainingDays( $cloud['days'] );

		// BBCS-MULTISITE
		$bbcs_propagate = array(
			'cloud_api_type'   => BBCS_CLOUD_TYPE_EXTENDED,
			'cloud_api_email'  => $cloud['email'],
			'cloud_api_key'    => $cloud['api_key'],
			'cloud_api_secret' => $cloud['api_secret'],
			'cloud_api_tier'   => $cloud['tier'],
			'check'            => 1,
		);
		if ( ! isset( $cloud['tier'] ) || $cloud['tier'] !== BBCS_CLOUD_TIER_ULTIMATE ) {
			$bbcs_propagate['force_cloud_validation'] = 0;
		}
		BotBlockerMultisite::syncCloudSettingsNetwork( $bbcs_propagate );

		wp_send_json_success( array( 'message' => __( 'API key retrieved successfully.', 'botblocker-security' ) ) );
	}

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
		$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_URL, 'validate_api_key' );
		if ( $cloud === false || isset( $cloud['error'] ) ) {
			$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_GS_URL, 'validate_api_key' );
		}

		if ( $cloud === false ) {
			wp_send_json_error( array( 'message' => __( 'Failed to connect to Cloud API. Try again later.', 'botblocker-security' ) ) );
		} elseif ( isset( $cloud['error'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API key. Cloud API responded with: ', 'botblocker-security' ) . $cloud['error'] ) );
		}

		$cloud_api_tier = isset( $cloud['api_tier'] ) ? sanitize_text_field( $cloud['api_tier'] ) : '';
		if ( ! BotBlockerPro::isValidTier( $cloud_api_tier ) ) {
			$cloud_api_tier = '';
		}

		// BBCS-MULTISITE
		$bbcs_propagate = array(
			'cloud_api_type'   => BBCS_CLOUD_TYPE_EXTENDED,
			'cloud_api_email'  => $cloud['email'],
			'cloud_api_key'    => $cloud['api_key'],
			'cloud_api_secret' => $cloud['api_secret'],
			'cloud_api_tier'   => $cloud_api_tier,
			'check'            => 1,
		);
		if ( $cloud_api_tier !== BBCS_CLOUD_TIER_ULTIMATE ) {
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

		$response_data = array(
			'remaining_hits' => $res['hits'],
			'remaining_days' => $res['days'],
		);

		wp_send_json_success( $response_data );
	}

	public static function deactivateApi(): void {
		check_ajax_referer( 'bbcs_deactivate_cloud_api_action', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }

		// BBCS-MULTISITE
		BotBlockerMultisite::syncCloudSettingsNetwork(
			array(
				'cloud_api_type'   => '',
				'cloud_api_email'  => '',
				'cloud_api_key'    => '',
				'cloud_api_secret' => '',
				'cloud_api_tier'   => '',
			)
		);

		BotBlockerPro::clearCache();

		wp_send_json_success( array( 'message' => __( 'Cloud API connection deactivated.', 'botblocker-security' ) ) );
	}

	public static function refreshApiInternal() {
		if ( ! BotBlockerPro::isActive() ) {
			return false;
		}

		$BBCS         = BotBlocker::getInstance();
		$request_data = BotBlockerPro::buildAuthPayload( $BBCS->settings );
		$cloud        = BotBlockerWpRequest::send_to_cloud( $request_data, BOTBLOCKER_API_URL, 'refresh_cloud_api' );
		if ( $cloud === false || isset( $cloud['error'] ) ) {
			$cloud = BotBlockerWpRequest::send_to_cloud( $request_data, BOTBLOCKER_API_GS_URL, 'refresh_cloud_api' );
		}

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

add_action( 'init', array( 'BotBlockerCloudApiHooks', 'registerRewriteRules' ) );
add_filter( 'query_vars', array( 'BotBlockerCloudApiHooks', 'addQueryVars' ) );
add_action( 'parse_request', array( 'BotBlockerCloudApiHooks', 'parseRequest' ), 0 );
add_action( 'wp_ajax_bbcs_fetch_cloud_api_key', array( 'BotBlockerCloudApiHooks', 'fetchApiKey' ) );
add_action( 'wp_ajax_bbcs_connect_cloud_api', array( 'BotBlockerCloudApiHooks', 'connectApi' ) );
add_action( 'wp_ajax_bbcs_refresh_cloud_api', array( 'BotBlockerCloudApiHooks', 'refreshApi' ) );
add_action( 'wp_ajax_bbcs_deactivate_cloud_api', array( 'BotBlockerCloudApiHooks', 'deactivateApi' ) );
