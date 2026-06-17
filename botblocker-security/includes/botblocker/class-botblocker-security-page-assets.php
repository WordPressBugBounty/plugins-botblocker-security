<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerSecurityPageAssets {

	public static function read( string $public_dir, string $relative_path ): string {
		$path = $public_dir . $relative_path;
		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			return '';
		}
		$contents = file_get_contents( $path );
		return is_string( $contents ) ? $contents : '';
	}

	public static function json( array $data ): string {
		$json = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		return is_string( $json ) ? $json : '{}';
	}
}
