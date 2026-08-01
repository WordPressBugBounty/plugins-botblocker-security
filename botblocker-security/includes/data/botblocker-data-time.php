<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_get_cache_durations(): array {
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

function bbcs_get_cookie_lifetimes(): array {
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

function bbcs_get_ptr_lifetimes(): array {
	return array(
		86400   => __( '1 day', 'botblocker-security' ),
		172800  => __( '2 days', 'botblocker-security' ),
		259200  => __( '3 days', 'botblocker-security' ),
		604800  => __( '1 week', 'botblocker-security' ),
		1209600 => __( '2 weeks', 'botblocker-security' ),
		2592000 => __( '1 month', 'botblocker-security' ),
	);
}

function bbcs_get_subnet_mask_options(): array {
	return array(
		'32-128' => __( '/32 - /128 (single IP)', 'botblocker-security' ),
		'28-64'  => __( '/28 - /64 (small subnet)', 'botblocker-security' ),
		'24-64'  => __( '/24 - /64 (standard)', 'botblocker-security' ),
	);
}

function bbcs_get_rate_subnet_mask_options(): array {
	return array(
		'28-64'   => __( '/28 - /64 (small subnet)', 'botblocker-security' ),
		'24-64'   => __( '/24 - /64 (standard)', 'botblocker-security' ),
		'20-64'   => __( '/20 - /64 (large ISP range)', 'botblocker-security' ),
		'ipv6-56' => __( 'IPv6 /56 (ISP site)', 'botblocker-security' ),
		'ipv6-48' => __( 'IPv6 /48 (large allocation)', 'botblocker-security' ),
	);
}

function bbcs_parse_rate_subnet_mask( string $mask = '24-64' ): array {
	$parts = explode( '-', $mask );
	if ( isset( $parts[0] ) && $parts[0] === 'ipv6' ) {
		// IPv4 mask unused for these rows; placeholder kept for return-shape consistency.
		return array( 24, isset( $parts[1] ) ? (int) $parts[1] : 64 );
	}
	return array( isset( $parts[0] ) ? (int) $parts[0] : 24, isset( $parts[1] ) ? (int) $parts[1] : 64 );
}

function bbcs_get_ptrcache_rule_ttl_options(): array {
	return array(
		10  => __( '10 days', 'botblocker-security' ),
		30  => __( '30 days', 'botblocker-security' ),
		60  => __( '60 days', 'botblocker-security' ),
		90  => __( '90 days', 'botblocker-security' ),
		120 => __( '120 days', 'botblocker-security' ),
	);
}
