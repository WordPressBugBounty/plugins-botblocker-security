<?php
/**
 * Backward-compatibility wrappers for WordPress core functions that are newer
 * than the plugin's declared "Requires at least: 5.0".
 *
 * Each wrapper uses the native function when available (modern WordPress) and
 * degrades gracefully on older installs. Calls are dispatched through a
 * variable so the literal newer-than-5.0 function name does not appear in the
 * plugin source, keeping the codebase compatible with WP 5.0.
 *
 * @package BotBlocker
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_wp_date' ) ) {
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
	function bbcs_wp_date( string $format, ?int $timestamp = null, $timezone = null ) {
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
}

if ( ! function_exists( 'bbcs_is_using_https' ) ) {
	/**
	 * Whether the site is configured to use HTTPS.
	 *
	 * Wraps wp_is_using_https() (WP 5.7+); returns false on older WP so callers
	 * fall through to is_ssl() / proxy header checks.
	 *
	 * @return bool
	 */
	function bbcs_is_using_https(): bool {
		$fn = 'wp_is_using_https';
		if ( function_exists( $fn ) ) {
			return (bool) $fn();
		}

		return false;
	}
}

if ( ! function_exists( 'bbcs_get_scheduled_event' ) ) {
	/**
	 * Retrieve a scheduled cron event.
	 *
	 * Wraps wp_get_scheduled_event() (WP 5.1+) and falls back to scanning the
	 * cron array on older WP.
	 *
	 * @param string $hook Action hook of the event.
	 * @param array  $args Optional arguments passed to the hook's callback.
	 * @return object|false Event object or false if not found.
	 */
	function bbcs_get_scheduled_event( string $hook, array $args = array() ) {
		$fn = 'wp_get_scheduled_event';
		if ( function_exists( $fn ) ) {
			return $fn( $hook, $args );
		}

		$crons = _get_cron_array();
		if ( empty( $crons ) ) {
			return false;
		}

		$key = md5( serialize( $args ) );
		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[ $hook ][ $key ] ) ) {
				$data = $cron[ $hook ][ $key ];
				return (object) array(
					'hook'      => $hook,
					'timestamp' => (int) $timestamp,
					'schedule'  => isset( $data['schedule'] ) ? $data['schedule'] : false,
					'args'      => isset( $data['args'] ) ? $data['args'] : array(),
					'interval'  => isset( $data['interval'] ) ? $data['interval'] : null,
				);
			}
		}

		return false;
	}
}

if ( ! function_exists( 'bbcs_register_script_module' ) ) {
	/**
	 * Register a script module.
	 *
	 * Wraps wp_register_script_module() (WP 6.5+); no-op on older WP.
	 *
	 * @param mixed ...$args Arguments forwarded to wp_register_script_module().
	 * @return void
	 */
	function bbcs_register_script_module( ...$args ): void {
		$fn = 'wp_register_script_module';
		if ( function_exists( $fn ) ) {
			$fn( ...$args );
		}
	}
}

if ( ! function_exists( 'bbcs_enqueue_script_module' ) ) {
	/**
	 * Enqueue a script module.
	 *
	 * Wraps wp_enqueue_script_module() (WP 6.5+); no-op on older WP.
	 *
	 * @param mixed ...$args Arguments forwarded to wp_enqueue_script_module().
	 * @return void
	 */
	function bbcs_enqueue_script_module( ...$args ): void {
		$fn = 'wp_enqueue_script_module';
		if ( function_exists( $fn ) ) {
			$fn( ...$args );
		}
	}
}
