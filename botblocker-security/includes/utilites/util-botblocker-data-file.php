<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_data_file_sign' ) ) :

	function bbcs_data_file_sign( string $content ): string {
		$content = rtrim( $content );
		return $content . "\n// HASH: " . hash( 'sha256', $content ) . "\n";
	}

	function bbcs_data_file_verify( string $content ): bool {
		$pos = strrpos( $content, '// HASH:' );
		if ( $pos === false ) {
			return false;
		}
		if ( ! preg_match( '~//\s*HASH:\s*([a-f0-9]{64})\s*$~', $content, $m ) ) {
			return false;
		}
		$hash     = $m[1];
		$stripped = rtrim( substr( $content, 0, $pos ) );
		$computed = hash( 'sha256', $stripped );
		return hash_equals( $computed, $hash );
	}

	function bbcs_safe_load_data_file( string $file ): array {
		if ( ! file_exists( $file ) ) {
			return array();
		}

		$content = file_get_contents( $file );
		if ( $content === false ) {
			return array();
		}

		$verified = bbcs_data_file_verify( $content );
		if ( ! $verified ) {
			if ( strrpos( $content, '// HASH:' ) !== false ) {
				// Has hash but verification failed -> tampered
				return array();
			}
			// No hash -> legacy format -> auto-migrate: sign and save
			$signed = bbcs_data_file_sign( $content );
			if ( @file_put_contents( $file, $signed, LOCK_EX ) !== false ) {
				if ( function_exists( 'opcache_invalidate' ) ) {
					@opcache_invalidate( $file, true );
				}
				$data = include $file;
				return is_array( $data ) ? $data : array();
			}
			return array();
			/*
			$data = include $file;
			return is_array( $data ) ? $data : array();
			*/
		}

		// Hash verified.
		$data = include $file;
		return is_array( $data ) ? $data : array();
	}

endif;
