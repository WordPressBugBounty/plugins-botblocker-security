<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerHeaderGuard {

	/** Diagnostic counter for callers that cannot observe header() in CLI. */
	public static $baselineCalls = 0;

	public static function register( $settings ): void {
		if ( self::shouldStrip( $settings ) ) {
			add_action( 'send_headers', array( __CLASS__, 'stripPhpVersion' ), 1 );
		}
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'restHeaders' ), 20, 4 );
		add_action( 'template_redirect', array( __CLASS__, 'notFoundHeaders' ), 20 );
	}

	public static function shouldStrip( $settings ): bool {
		if ( ! is_object( $settings ) ) {
			return true;
		}
		return (int) ( $settings->bbcs_strip_php_version ?? 1 ) === 1;
	}

	public static function stripPhpVersion(): void {
		if ( ! self::shouldStrip( self::currentSettings() ) ) {
			return;
		}
		if ( static::canSendHeaders() ) {
			static::removeHeader( 'X-Powered-By' );
		}
	}

	private static function currentSettings() {
		if ( ! class_exists( 'BotBlocker' ) ) {
			return null;
		}
		$bbcs = BotBlocker::getInstance();
		return is_object( $bbcs ) && isset( $bbcs->settings ) ? $bbcs->settings : null;
	}

	public static function restHeaders( $served, $result, $request, $server ) {
		unset( $result, $request, $server );
		static::sendBaselineHeaders();
		return $served;
	}

	public static function notFoundHeaders(): void {
		if ( ! is_404() || is_admin() ) {
			return;
		}
		static::sendBaselineHeaders();
	}

	public static function sendBaselineHeaders(): void {
		++self::$baselineCalls;
		if ( ! static::canSendHeaders() ) {
			return;
		}
		self::stripPhpVersion();
		static::emitHeader( 'X-Content-Type-Options: nosniff' );
		static::emitHeader( 'X-Frame-Options: SAMEORIGIN' );
		static::emitHeader( 'Referrer-Policy: no-referrer' );
		static::emitHeader( "Content-Security-Policy: frame-ancestors 'self'" );
	}

	protected static function canSendHeaders(): bool {
		return ! headers_sent();
	}

	protected static function emitHeader( string $line ): void {
		header( $line, true );
	}

	protected static function removeHeader( string $name ): void {
		header_remove( $name );
	}
}
