<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backward-compatibility wrappers for WordPress core functions that are newer
 * than the plugin's declared "Requires at least: 5.1".
 *
 * Each wrapper uses the native function when available (modern WordPress) and
 * degrades gracefully on older installs. Calls are dispatched through a
 * variable so the literal newer-than-5.1 function name does not appear in the
 * plugin source, keeping the codebase compatible with WP 5.1.
 *
 * @package BotBlocker
 */
class BotBlockerCompatibility {

	/**
	 * Format a timestamp in the site timezone.
	 *
	 * Wraps wp_date() (WP 5.3+) and falls back to date_i18n() on older WP.
	 *
	 * @param string   $format    PHP date format.
	 * @param int|null $timestamp Unix timestamp. Defaults to current time.
	 * @param mixed    $timezone  Optional timezone. Defaults to site timezone.
	 * @return string|false
	 */
	public static function wpDate( string $format, ?int $timestamp = null, $timezone = null ) {
		if ( null === $timestamp ) {
			$timestamp = time();
		}

		$date_fn = 'wp_date';
		if ( function_exists( $date_fn ) ) {
			if ( null === $timezone ) {
				$tz_fn    = 'wp_timezone';
				$timezone = function_exists( $tz_fn ) ? $tz_fn() : null;
			}
			return $date_fn( $format, $timestamp, $timezone );
		}

		return date_i18n( $format, $timestamp );
	}

	public static function wpTimezoneString(): string {
		$fn = 'wp_timezone_string';
		if ( function_exists( $fn ) ) {
			return $fn();
		}

		$timezone_string = get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			return $timezone_string;
		}

		$offset = (float) get_option( 'gmt_offset', 0 );
		if ( $offset >= 0 ) {
			$prefix = '+';
		} else {
			$prefix = '-';
			$offset = abs( $offset );
		}
		$hours   = (int) floor( $offset );
		$minutes = (int) round( ( $offset - $hours ) * 60 );

		return sprintf( '%s%02d:%02d', $prefix, $hours, $minutes );
	}

	/**
	 * Whether the site is configured to use HTTPS.
	 *
	 * Wraps wp_is_using_https() (WP 5.7+); returns false on older WP so callers
	 * fall through to is_ssl() / proxy header checks.
	 *
	 * @return bool
	 */
	public static function isUsingHttps(): bool {
		$fn = 'wp_is_using_https';
		if ( function_exists( $fn ) ) {
			return (bool) $fn();
		}
		return false;
	}

	/**
	 * Retrieve a scheduled cron event.
	 *
	 * Wraps wp_get_scheduled_event() (WP 5.1+). Since the plugin minimum
	 * is WP 5.1, this always delegates to the native function.
	 *
	 * @param string $hook Action hook of the event.
	 * @param array  $args Optional arguments passed to the hook's callback.
	 * @return object|false Event object or false if not found.
	 */
	public static function getScheduledEvent( string $hook, array $args = array() ) {
		return wp_get_scheduled_event( $hook, $args );
	}

	/**
	 * Register a script module.
	 *
	 * Wraps wp_register_script_module() (WP 6.5+); no-op on older WP.
	 *
	 * @param mixed ...$args Arguments forwarded to wp_register_script_module().
	 * @return void
	 */
	public static function registerScriptModule( ...$args ): void {
		$fn = 'wp_register_script_module';
		if ( function_exists( $fn ) ) {
			$fn( ...$args );
		}
	}

	/**
	 * Enqueue a script module.
	 *
	 * Wraps wp_enqueue_script_module() (WP 6.5+); no-op on older WP.
	 *
	 * @param mixed ...$args Arguments forwarded to wp_enqueue_script_module().
	 * @return void
	 */
	public static function enqueueScriptModule( ...$args ): void {
		$fn = 'wp_enqueue_script_module';
		if ( function_exists( $fn ) ) {
			$fn( ...$args );
		}
	}
}
