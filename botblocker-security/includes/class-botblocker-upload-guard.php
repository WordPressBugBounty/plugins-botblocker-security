<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rejects uploads that a browser can execute as same-origin markup and turns
 * WordPress' generic `rest_upload_unknown_error` 500 into an honest 400 for
 * every disallowed media type.
 */
class BotBlockerUploadGuard {

	public static function register(): void {
		if ( ! apply_filters( 'bbcs_upload_guard_enabled', true ) ) {
			return;
		}
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'restPreDispatch' ), 10, 3 );
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'prefilter' ), 10, 1 );
		add_filter( 'wp_handle_sideload_prefilter', array( __CLASS__, 'prefilter' ), 10, 1 );
	}

	/**
	 * @return array<int,string>
	 */
	public static function blockedExtensions(): array {
		$extensions = array( 'html', 'htm', 'shtml', 'xhtml', 'xht', 'mht', 'mhtml', 'svg', 'svgz' );
		$filtered = apply_filters( 'bbcs_blocked_upload_extensions', $extensions );
		if ( ! is_array( $filtered ) ) {
			$filtered = $extensions;
		}
		$normalized = array();
		foreach ( $filtered as $extension ) {
			if ( is_string( $extension ) && $extension !== '' ) {
				$normalized[] = strtolower( $extension );
			}
		}
		return $normalized;
	}

	public static function rejectionReason( string $filename ): string {
		$filename = strtolower( trim( $filename ) );
		if ( $filename === '' ) {
			return '';
		}
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( in_array( $extension, self::blockedExtensions(), true ) ) {
			return __( 'This file type is not allowed for security reasons.', 'botblocker-security' );
		}

		$check = wp_check_filetype( $filename, get_allowed_mime_types() );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return __( 'Sorry, you are not allowed to upload this file type.', 'botblocker-security' );
		}
		return '';
	}

	/**
	 * @param mixed $result
	 * @param mixed $server
	 * @param mixed $request
	 * @return mixed
	 */
	public static function restPreDispatch( $result, $server, $request ) {
		unset( $server );
		if ( null !== $result || ! is_object( $request ) || ! method_exists( $request, 'get_route' ) || ! method_exists( $request, 'get_method' ) ) {
			return $result;
		}
		if ( strtoupper( (string) $request->get_method() ) !== 'POST' ) {
			return $result;
		}
		$route = rtrim( (string) $request->get_route(), '/' );
		if ( $route !== '/wp/v2/media' ) {
			return $result;
		}
		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) || ! isset( $_FILES['file']['name'] ) || ! is_string( $_FILES['file']['name'] ) ) {
			return $result;
		}

		$name   = sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) );
		$reason = self::rejectionReason( $name );
		if ( '' === $reason ) {
			return $result;
		}
		return new WP_Error( 'rest_upload_invalid_type', $reason, array( 'status' => 400 ) );
	}

	/**
	 * @param mixed $file
	 * @return mixed
	 */
	public static function prefilter( $file ) {
		if ( ! is_array( $file ) || empty( $file['name'] ) || ! is_string( $file['name'] ) ) {
			return $file;
		}
		$reason = self::rejectionReason( $file['name'] );
		if ( '' !== $reason && empty( $file['error'] ) ) {
			$file['error'] = $reason;
		}
		return $file;
	}
}
