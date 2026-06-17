<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerCheck {

	public static function isEmptyUA( string $useragent ): bool {
		$useragent = trim( $useragent );
		if ( $useragent === '' || $useragent === BOTBLOCKER_EMPTY || empty( $useragent ) ) {
			return false;
		}
		if ( preg_match( '/^[\s\x00-\x1F\x7F]*$/', $useragent ) ) {
			return false;
		}
		return true;
	}

	public static function isValidMethod( string $method ): bool {
		// CONNECT and TRACE are intentionally not in allowed_methods (security risk / XST).
		// OPTIONS is excluded — controlled separately via isValidOptionsRequest() for CORS preflight
		// and admin REST nonce (WooCommerce wizard / WP Connections).
		$allowed_methods = array( 'GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH' );
		$method = strtoupper( trim( $method ) );
		if ( in_array( $method, $allowed_methods, true ) ) {
			return true;
		}
		return false;
	}

	public static function isValidOptionsRequest(): bool {
		if (
			isset( $_SERVER['HTTP_ORIGIN'] )
			&& isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] )
		) {
			return true;
		}

		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$nonce   = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';

		if ( ! empty( $referer ) && ! empty( $nonce ) && self::isWpAdminReferer( $referer ) ) {
			$valid_rest_nonce = function_exists( 'wp_verify_nonce' ) && wp_verify_nonce( $nonce, 'wp_rest' );
			if ( $valid_rest_nonce || self::isWpAdminPhpReferer( $referer ) ) {
				return true;
			}
		}

		return false;
	}

	public static function isWpAdminReferer( string $referer ): bool {
		$referer_path = wp_parse_url( $referer, PHP_URL_PATH );
		if ( ! is_string( $referer_path ) || $referer_path === '' ) {
			return false;
		}

		$admin_urls = array( admin_url() );
		if ( is_multisite() ) {
			$admin_urls[] = network_admin_url();
		}

		foreach ( $admin_urls as $admin_url ) {
			$admin_host   = wp_parse_url( $admin_url, PHP_URL_HOST );
			$referer_host = wp_parse_url( $referer, PHP_URL_HOST );
			if ( $admin_host && $referer_host && strtolower( $admin_host ) !== strtolower( $referer_host ) ) {
				continue;
			}

			$admin_path = wp_parse_url( $admin_url, PHP_URL_PATH );
			if ( ! is_string( $admin_path ) || $admin_path === '' ) {
				continue;
			}

			if ( strpos( trailingslashit( $referer_path ), trailingslashit( $admin_path ) ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	public static function isWpAdminPhpReferer( string $referer ): bool {
		$referer_base    = untrailingslashit( explode( '?', $referer, 2 )[0] );
		$allowed_admin   = untrailingslashit( admin_url( 'admin.php' ) );
		$allowed_network = is_multisite() ? untrailingslashit( network_admin_url( 'admin.php' ) ) : '';

		return $referer_base === $allowed_admin || ( $allowed_network !== '' && $referer_base === $allowed_network );
	}

	public static function isEmptyLanguage( ?string $accept_lang, ?string $lang ): bool {
		if ( empty( $accept_lang ) || $accept_lang === null ||
					$accept_lang === '' || $accept_lang === BOTBLOCKER_EMPTY || empty( $lang ) || $lang === null || $lang === '' || $lang === BOTBLOCKER_EMPTY ) {
			return false;
		}
		if ( ! preg_match( '/^[a-z]{2}$/i', $lang ) ) {
			return false;
		}
		return true;
	}

	public static function languageToCountryCompare( string $country_code, string $lang_code ): bool {
		$country_lang_map = bbcs_CountrylanguageMap();
		$country_code     = strtoupper( $country_code );
		$lang_code        = strtolower( $lang_code );
		if ( isset( $country_lang_map[ $country_code ] ) ) {
			return in_array( $lang_code, $country_lang_map[ $country_code ] );
		}
		return false;
	}
}
