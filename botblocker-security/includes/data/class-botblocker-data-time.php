<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerDataTime {

	public static function getCacheDurations(): array {
		return array(
			300   => __( '5 minutes', 'botblocker-security' ),
			900   => __( '15 minutes', 'botblocker-security' ),
			1800  => __( '30 minutes', 'botblocker-security' ),
			3600  => __( '1 hour', 'botblocker-security' ),
			7200  => __( '2 hours', 'botblocker-security' ),
			21600 => __( '6 hours', 'botblocker-security' ),
			43200 => __( '12 hours', 'botblocker-security' ),
		);
	}

	public static function getCookieLifetimes(): array {
		return array(
			86400    => __( '1 day', 'botblocker-security' ),
			172800   => __( '2 days', 'botblocker-security' ),
			259200   => __( '3 days', 'botblocker-security' ),
			604800   => __( '1 week', 'botblocker-security' ),
			1209600  => __( '2 weeks', 'botblocker-security' ),
			2592000  => __( '1 month', 'botblocker-security' ),
			7776000  => __( '3 months', 'botblocker-security' ),
			15552000 => __( '6 months', 'botblocker-security' ),
			23328000 => __( '9 months', 'botblocker-security' ),
			31536000 => __( '1 year', 'botblocker-security' ),
		);
	}

	public static function getPtrLifetimes(): array {
		return array(
			86400   => __( '1 day', 'botblocker-security' ),
			172800  => __( '2 days', 'botblocker-security' ),
			259200  => __( '3 days', 'botblocker-security' ),
			604800  => __( '1 week', 'botblocker-security' ),
			1209600 => __( '2 weeks', 'botblocker-security' ),
			2592000 => __( '1 month', 'botblocker-security' ),
		);
	}

	public static function getSubnetMaskOptions(): array {
		return array(
			'32-128' => __( '/32 - /128 (single IP)', 'botblocker-security' ),
			'28-64'  => __( '/28 - /64 (small subnet)', 'botblocker-security' ),
			'24-64'  => __( '/24 - /64 (standard)', 'botblocker-security' ),
		);
	}

	public static function getRateSubnetMaskOptions(): array {
		return array(
			'28-64'   => __( '/28 - /64 (small subnet)', 'botblocker-security' ),
			'24-64'   => __( '/24 - /64 (standard)', 'botblocker-security' ),
			'20-64'   => __( '/20 - /64 (large ISP range)', 'botblocker-security' ),
			'ipv6-56' => __( 'IPv6 /56 (ISP site)', 'botblocker-security' ),
			'ipv6-48' => __( 'IPv6 /48 (large allocation)', 'botblocker-security' ),
		);
	}

	public static function getPtrcacheRuleTtlOptions(): array {
		return array(
			10  => __( '10 days', 'botblocker-security' ),
			30  => __( '30 days', 'botblocker-security' ),
			60  => __( '60 days', 'botblocker-security' ),
			90  => __( '90 days', 'botblocker-security' ),
			120 => __( '120 days', 'botblocker-security' ),
		);
	}

	/**
	 * Format seconds into a human-readable time remaining string.
	 * Matches the format used in the legacy header JS (bbcs-common.js).
	 */
	public static function formatTimeRemaining( int $seconds ): string {
		if ( $seconds < 60 ) {
			// translators: %d: seconds count, e.g. "34s"
			return sprintf( _n( '%ds', '%ds', $seconds, 'botblocker-security' ), $seconds );
		}
		if ( $seconds < 3600 ) {
			// translators: %1$d minutes, %2$d seconds, e.g. "12m 30s"
			return sprintf( __( '%1$dm %2$ds', 'botblocker-security' ), intdiv( $seconds, 60 ), $seconds % 60 );
		}
		if ( $seconds < 86400 ) {
			// translators: %1$d hours, %2$d minutes, e.g. "12h 30m"
			return sprintf( __( '%1$dh %2$dm', 'botblocker-security' ), intdiv( $seconds, 3600 ), intdiv( $seconds % 3600, 60 ) );
		}
		// translators: %1$d days, %2$d hours, e.g. "2d 5h"
		return sprintf( __( '%1$dd %2$dh', 'botblocker-security' ), intdiv( $seconds, 86400 ), intdiv( $seconds % 86400, 3600 ) );
	}
}
