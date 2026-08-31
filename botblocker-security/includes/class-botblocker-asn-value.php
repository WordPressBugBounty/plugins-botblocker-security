<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class BotBlockerAsnValue {

	public const MAX_UNSIGNED = '18446744073709551615';

	public static function normalize( $value ): ?string {
		if ( is_int( $value ) ) {
			if ( $value < 1 ) {
				return null;
			}
			$value = (string) $value;
		}

		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( $value === '' || preg_match( '/\A[0-9]+\z/', $value ) !== 1 ) {
			return null;
		}

		$value = ltrim( $value, '0' );
		if ( $value === '' ) {
			return null;
		}

		$max_length = strlen( self::MAX_UNSIGNED );
		if (
			strlen( $value ) > $max_length
			|| ( strlen( $value ) === $max_length && strcmp( $value, self::MAX_UNSIGNED ) > 0 )
		) {
			return null;
		}

		return $value;
	}
}
