<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerBanTargetResolver {

	public static function resolve( string $resolvedIp, string $remoteAddr, string $isProxy, bool $blockProxyUsers, string $edgeState = '', array $proxyMap = array(), array $botNetworks = array(), string $role = '' ): string {
		if ( $role === 'bot_network' ) {
			return '';
		}
		if ( in_array( $edgeState, array( 'unknown_edge', 'edge_unresolved', 'invalid' ), true ) ) {
			return '';
		}
		if ( $isProxy === 'DETECTED' && ! $blockProxyUsers ) {
			return '';
		}
		if ( self::isValidIp( $resolvedIp ) ) {
			if ( ! BotBlockerIp::isPublicIp( $resolvedIp ) ) {
				return '';
			}
			foreach ( array_keys( $proxyMap ) as $mask ) {
				if ( BotBlockerIp::netMatch( (string) $mask, $resolvedIp ) ) {
					return '';
				}
			}
			foreach ( array_keys( $botNetworks ) as $mask ) {
				if ( BotBlockerIp::netMatch( (string) $mask, $resolvedIp ) ) {
					return '';
				}
			}
			return $resolvedIp;
		}
		if ( $edgeState !== '' && $edgeState !== 'direct' ) {
			return '';
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
