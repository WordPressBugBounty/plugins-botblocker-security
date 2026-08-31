<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerBanTargetResolver {

	public static function resolve( string $resolvedIp, string $remoteAddr, string $isProxy, bool $blockProxyUsers ): string {
		if ( $isProxy === 'DETECTED' && ! $blockProxyUsers ) {
			return '';
		}
		if ( self::isValidIp( $resolvedIp ) ) {
			return $resolvedIp;
		}
		if ( self::isValidIp( $remoteAddr ) ) {
			return $remoteAddr;
		}
		return '';
	}

	public static function hasValidIp( string $a, string $b ): bool {
		return self::isValidIp( $a ) || self::isValidIp( $b );
	}

	private static function isValidIp( string $ip ): bool {
		return $ip !== '' && filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}
}
