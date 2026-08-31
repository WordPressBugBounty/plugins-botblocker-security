<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'GMP_MSW_FIRST' ) ) {
	define( 'GMP_MSW_FIRST', 1 );
}
if ( ! defined( 'GMP_LSW_FIRST' ) ) {
	define( 'GMP_LSW_FIRST', 2 );
}
if ( ! defined( 'GMP_BIG_ENDIAN' ) ) {
	define( 'GMP_BIG_ENDIAN', 4 );
}
if ( ! defined( 'GMP_LITTLE_ENDIAN' ) ) {
	define( 'GMP_LITTLE_ENDIAN', 8 );
}

class BotBlockerEnv {

	public static function check_curl(): int {
		return extension_loaded( 'curl' ) ? 1 : 0;
	}

	public static function check_zip(): int {
		return extension_loaded( 'zip' ) ? 1 : 0;
	}

	public static function check_gd(): int {
		return extension_loaded( 'gd' ) ? 1 : 0;
	}

	public static function check_gmp(): int {
		return extension_loaded( 'gmp' ) ? 1 : 0;
	}

	public static function check_bcmath(): int {
		return extension_loaded( 'bcmath' ) ? 1 : 0;
	}

	public static function check_mbstring(): int {
		return extension_loaded( 'mbstring' ) ? 1 : 0;
	}

	public static function check_iconv(): int {
		return extension_loaded( 'iconv' ) ? 1 : 0;
	}

	public static function check_xml(): int {
		return extension_loaded( 'xml' ) ? 1 : 0;
	}

	public static function check_gd_func(): int {
		return function_exists( 'imagecreatetruecolor' ) ? 1 : 0;
	}

	public static function check_json(): int {
		return function_exists( 'json_encode' ) ? 1 : 0;
	}

	public static function check_openssl(): int {
		return ( extension_loaded( 'openssl' ) && function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ) ) ? 1 : 0;
	}

	public static function prefly_check(): array {
		return array(
			'curl'     => self::check_curl(),
			'zip'      => self::check_zip(),
			'gd'       => self::check_gd(),
			'gmp'      => self::check_gmp(),
			'bcmath'   => self::check_bcmath(),
			'mbstring' => self::check_mbstring(),
			'iconv'    => self::check_iconv(),
			'xml'      => self::check_xml(),
			'gd_func'  => self::check_gd_func(),
			'json'     => self::check_json(),
			'openssl'  => self::check_openssl(),
		);
	}

	public static function format_gmt_offset( $offset ): string {
		$sign    = ( $offset >= 0 ) ? '+' : '-';
		$hours   = floor( abs( $offset ) );
		$minutes = ( abs( $offset ) - $hours ) * 60;
		return sprintf( '%s%02d:%02d', $sign, $hours, $minutes );
	}

	public static function fixHttpHost(): void {
		if ( ! empty( $_SERVER['HTTP_HOST'] ) || empty( $_SERVER['SERVER_NAME'] ) ) {
			return;
		}

		$bbcs_port = '';
		if (
			! empty( $_SERVER['SERVER_PORT'] )
			&& ! in_array( $_SERVER['SERVER_PORT'], array( '80', '443' ), true )
		) {
			$bbcs_port = ':' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) );
		}

		$_SERVER['HTTP_HOST'] = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) . $bbcs_port;
	}
}

// Some servers expose SERVER_NAME only; HTTP_HOST is required by WP and the plugin.
BotBlockerEnv::fixHttpHost();
