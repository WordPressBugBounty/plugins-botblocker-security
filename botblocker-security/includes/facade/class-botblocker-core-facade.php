<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BotBlockerCoreFacade — static facade over core procedural helpers.
 *
 * Phase 1 (OOP migration): each method holds the implementation; the legacy
 * procedural function below delegates to it. Tests target this facade, so the
 * OOP migration only changes method internals, never the test call sites.
 *
 * Contract: same input/output as the legacy function it replaces.
 */
class BotBlockerCoreFacade {

	/**
	 * Resolve country name from a 2-letter code (lowercased internally).
	 *
	 * @param mixed $code Country code.
	 * @return string Country name or BOTBLOCKER_EMPTY marker.
	 */
	public static function getCountryByCode( $code ) {
		$code = strtolower( (string) $code );
		return BBCS_COUNTRIES[ $code ] ?? BOTBLOCKER_EMPTY;
	}

	/**
	 * Resolve language name from a 2-letter code (lowercased internally).
	 *
	 * @param mixed $code Language code.
	 * @return string Language name or BOTBLOCKER_EMPTY marker.
	 */
	public static function getLanguageByCode( $code ) {
		$code = strtolower( (string) $code );
		return BBCS_LANGUAGES[ $code ] ?? BOTBLOCKER_EMPTY;
	}

	/**
	 * Parse a rate-limit subnet mask like '24-64' or 'ipv6-48' into [v4, v6] ints.
	 * Empty/non-numeric parts fall back to the documented defaults [24, 64] — a bare '-'
	 * or 'x-y' previously yielded [0, 0] (whole-internet /0 subnet pressure). Fix B-01.
	 *
	 * @param string $mask Subnet mask string.
	 * @return array{0:int,1:int} [ipv4_prefix, ipv6_prefix].
	 */
	public static function parseRateSubnetMask( string $mask = '24-64' ): array {
		$parts = explode( '-', $mask );
		if ( isset( $parts[0] ) && $parts[0] === 'ipv6' ) {
			// IPv4 mask unused for these rows; placeholder kept for return-shape consistency.
			$v6 = isset( $parts[1] ) && is_numeric( $parts[1] ) ? (int) $parts[1] : 64;
			return array( 24, $v6 );
		}
		$v4 = isset( $parts[0] ) && is_numeric( $parts[0] ) ? (int) $parts[0] : 24;
		$v6 = isset( $parts[1] ) && is_numeric( $parts[1] ) ? (int) $parts[1] : 64;
		return array( $v4, $v6 );
	}

	/**
	 * Whether the site is configured to use HTTPS.
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
}
