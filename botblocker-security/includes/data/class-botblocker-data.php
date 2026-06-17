<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerData {

	public static function getRestrictedCountries(): array {
		return array( 'CN', 'KP', 'IR', 'SY', 'CU', 'RU', 'BY', 'MM', 'TM', 'SD', 'ER', 'AF', 'VE' );
	}

	public static function getBlockedCountries(): array {
		$blockedCountries = BotBlockerMultisite::getOption( 'bbcs_blocked_countries', array() );
		if ( is_string( $blockedCountries ) ) {
			$decoded = json_decode( $blockedCountries, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$blockedCountries = $decoded;
			} else {
				$blockedCountries = array_filter( array_map( 'trim', explode( ',', $blockedCountries ) ) );
			}
		}

		if ( is_array( $blockedCountries ) ) {
			$blockedCountries = array_map( 'strtoupper', $blockedCountries );
			$blockedCountries = array_filter(
				$blockedCountries,
				function ( $item ) {
					return preg_match( '/^[A-Z]{2}$/', (string) $item );
				}
			);
			$blockedCountries = array_values( array_unique( $blockedCountries ) );
		} else {
			$blockedCountries = array();
		}

		if ( ! empty( $blockedCountries ) ) {
			return $blockedCountries;
		}

		return array();
	}

	public static function getXRobotTags(): array {
		return array(
			'noindex'           => '',
			'noarchive'         => '',
			'nosnippet'         => '',
			'noimageindex'      => '',
			'notranslate'       => '',
			'unavailable_after' => '',
			'none'              => '',
			'max-snippet'       => '-1',
			'max-image-preview' => 'large',
			'max-video-preview' => '-1',
			'nofollow'          => '',
		);
	}

	public static function getHeadersArray(): array {
		return array(
			200 => '200 OK',
			201 => '201 Created',
			202 => '202 Accepted',
			301 => '301 Moved Permanently',
			302 => '302 Found',
			303 => '303 See Other',
			304 => '304 Not Modified',
			305 => '305 Use Proxy',
			306 => '306 Switch Proxy',
			307 => '307 Temporary Redirect',
			308 => '308 Permanent Redirect',
			400 => '400 Bad Request',
			401 => '401 Unauthorized',
			403 => '403 Forbidden',
			404 => '404 Not Found',
			410 => '410 Gone',
			429 => '429 Too Many Requests',
			451 => '451 Unavailable For Legal Reasons',
			500 => '500 Internal Server Error',
			502 => '502 Bad Gateway',
			503 => '503 Service Unavailable',
			504 => '504 Gateway Time-out',
			505 => '505 HTTP Version Not Supported',
			507 => '507 Insufficient Storage',
			508 => '508 Loop Detected',
			510 => '510 Not Extended',
			511 => '511 Network Authentication Required',
			520 => '520 Unknown Error',
			521 => '521 Web Server Is Down',
			522 => '522 Connection Timed Out',
			523 => '523 Origin Is Unreachable',
			524 => '524 A Timeout Occurred',
			525 => '525 SSL Handshake Failed',
			526 => '526 Invalid SSL Certificate',
			527 => '527 Railgun Error',
			530 => '530 Origin DNS Error',
		);
	}

	public static function getProxyHeaders(): array {
		$proxy_headers = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
			'HTTP_FORWARDED',
			'HTTP_VIA',
			'HTTP_TRUE_CLIENT_IP',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_FASTLY_CLIENT_IP',
			'HTTP_X_PROXYUSER_IP',
			'X-Forwarded-For',
			'X-Real-IP',
			'Forwarded',
			'Via',
			'Client-IP',
			'True-Client-IP',
			'CF-Connecting-IP',
			'Fastly-Client-IP',
			'X-ProxyUser-IP',
		);

		return $proxy_headers;
	}

	public static function getBotSignatures(): array {
		$bot_signatures = include BOTBLOCKER_DIR . 'data/base/bot-signatures.php';
		return $bot_signatures;
	}
}
