<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerWpRequest {

	public static function send_to_cloud( array $data, string $url, string $endpoint = '' ) {
		$base = untrailingslashit( $url );
		$path = 'botblocker';
		if ( ! empty( $endpoint ) ) {
			$path .= '/' . ltrim( $endpoint, '/' );
		}
		$fullURL = $base . '/' . $path;

		$timeout = 5;
		if ( class_exists( 'BotBlocker' ) && method_exists( 'BotBlocker', 'getInstance' ) ) {
			$inst = BotBlocker::getInstance();
			if ( isset( $inst->settings->cloud_api_timeout ) ) {
				$timeout = max( 1, min( 30, (int) $inst->settings->cloud_api_timeout ) );
			}
		}

		$args = array(
			'method'      => 'POST',
			'timeout'     => $timeout,
			'redirection' => 0,
			'httpversion' => '1.1',
			'headers'     => array(
				'Content-Type' => 'application/json; charset=utf-8',
				'Referer'      => BotBlockerMultisite::getCurrentSiteUrl(), //BBCS-MULTISITE
				'User-Agent'   => BotBlockerMultisite::getCurrentUserAgent(), //BBCS-MULTISITE
			),
			'body'        => wp_json_encode( $data ),
		);

		$response = wp_remote_post( $fullURL, $args );

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// REVIEWER NOTE: Conditional debug logging; gated behind BBCS_DEBUG and disabled in production.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
			error_log( '[BBCS DEBUG] [Request] ' . $fullURL . '-' . $http_code . "\r\n" . print_r( $data, true ) . "\r\n" . 'Response:' . "\r\n" . $body . "\r\n\r\n" );
		}

		if ( $http_code === 200 && ! empty( $body ) && self::is_json( $body ) ) {
			delete_transient( 'bbcs_cloud_connection_failed_alert' );
			return json_decode( trim( $body ), true, 512, JSON_BIGINT_AS_STRING );
		}

		BotBlockerAlerts::setCloudConnectionFailed();
		return false;
	}

	public static function is_json( string $string ): bool {
		json_decode( $string );
		return ( json_last_error() == JSON_ERROR_NONE );
	}

	public static function download_to_file( string $url, string $destination, array $args_overrides = array() ): array {
		$defaults = array(
			'method'      => 'GET',
			'timeout'     => 600,
			'redirection' => 5,
			'httpversion' => '1.1',
			'sslverify'   => true,
			'stream'      => true,
			'filename'    => $destination,
			'headers'     => array(
				'Accept'     => 'application/octet-stream',
				'Referer'    => method_exists( 'BotBlockerMultisite', 'getCurrentSiteUrl' ) ? BotBlockerMultisite::getCurrentSiteUrl() : home_url(),
				'User-Agent' => method_exists( 'BotBlockerMultisite', 'getCurrentUserAgent' ) ? BotBlockerMultisite::getCurrentUserAgent() : 'BotBlocker',
			),
		);

		$args = array_replace_recursive( $defaults, $args_overrides );

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			if ( file_exists( $destination ) ) {
				@unlink( $destination );  // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
			}
			return array(
				'ok'    => false,
				'error' => 'wp_error:' . $response->get_error_code(),
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $http_code !== 200 ) {
			if ( file_exists( $destination ) ) {
				@unlink( $destination );  // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
			}
			return array(
				'ok'    => false,
				'error' => 'http_' . $http_code,
			);
		}

		if ( ! file_exists( $destination ) || filesize( $destination ) === 0 ) {
			if ( file_exists( $destination ) ) {
				@unlink( $destination );  // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
			}
			return array(
				'ok'    => false,
				'error' => 'empty_file',
			);
		}

		return array(
			'ok'           => true,
			'http_code'    => $http_code,
			'size'         => filesize( $destination ),
			'content_type' => (string) wp_remote_retrieve_header( $response, 'content-type' ),
		);
	}

	public static function ip2c( string $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return false;
		}

		$cache_key = 'bbcs_ip2c_' . md5( $ip );

		if ( class_exists( 'BotBlockerCache' ) ) {
			$cached = BotBlockerCache::getCacheData( $cache_key );
			if ( $cached !== null ) {
				return $cached['country'] ? $cached['country'] : false;
			}
		}

		$url      = 'https://ip2c.org/?ip=' . rawurlencode( (string) $ip );
		$args     = array(
			'method'      => 'GET',
			'timeout'     => 3,
			'redirection' => 0,
			'httpversion' => '1.1',
			'headers'     => array(
				'User-Agent' => method_exists( 'BotBlockerMultisite', 'getCurrentUserAgent' ) ? BotBlockerMultisite::getCurrentUserAgent() : 'BotBlocker/IP2C', //BBCS-MULTISITE
			),
		);
		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			if ( class_exists( 'BotBlockerCache' ) ) {
				BotBlockerCache::setCacheData( $cache_key, array( 'country' => false ), DAY_IN_SECONDS );
			}
			return false;
		}
		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );
		if ( $http_code === 200 && ! empty( $body ) ) {
			$reply_ip2c = explode( ';', trim( $body ) );
			if ( isset( $reply_ip2c[0] ) && $reply_ip2c[0] === '1' && isset( $reply_ip2c[1] ) ) {
				$country = mb_strtoupper( $reply_ip2c[1] );
				if ( class_exists( 'BotBlockerCache' ) ) {
					BotBlockerCache::setCacheData( $cache_key, array( 'country' => $country ), WEEK_IN_SECONDS );
				}
				return $country;
			}
		}
		if ( class_exists( 'BotBlockerCache' ) ) {
			BotBlockerCache::setCacheData( $cache_key, array( 'country' => false ), DAY_IN_SECONDS );
		}
		return false;
	}
}
