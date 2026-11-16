<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One identity record for the current request.
 *
 * The shield fills the singleton fields during read_ip(). Callers outside the
 * shield (abilities, audit, addons) get the same record by re-resolving the
 * request with the same trust maps.
 */
class BotBlockerIdentity {

	/** @var array<string,string>|null */
	private static $cache = null;

	/**
	 * @return array{peer:string,client:string,claimed:string,role:string,state:string,header:string,subject:string,subject_source:string,verified_by:string}
	 */
	public static function current(): array {
		$BBCS   = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		if ( is_object( $BBCS ) && isset( $BBCS->peer_ip ) && $BBCS->peer_ip !== '' && self::sameIp( (string) $BBCS->peer_ip, $remote ) ) {
			return array(
				'peer'           => (string) $BBCS->peer_ip,
				'client'         => (string) $BBCS->edge_client,
				'claimed'        => (string) $BBCS->edge_claimed,
				'role'           => (string) $BBCS->edge_role,
				'state'          => (string) $BBCS->edge_state,
				'header'         => (string) $BBCS->edge_header,
				'subject'        => (string) $BBCS->ip,
				'subject_source' => (string) $BBCS->edge_subject_source,
				'verified_by'    => (string) $BBCS->edge_verified_by,
			);
		}

		if ( self::$cache !== null && self::sameIp( (string) self::$cache['peer'], $remote ) ) {
			return self::$cache;
		}

		$resolved    = self::resolveFresh();
		self::$cache = $resolved;

		return self::$cache;
	}

	/**
	 * Resolve the current request from $_SERVER regardless of singleton state.
	 *
	 * @return array{peer:string,client:string,claimed:string,role:string,state:string,header:string,subject:string,subject_source:string,verified_by:string}
	 */
	public static function resolveFresh(): array {
		$maps     = self::trustMaps();
		$resolved = BotBlockerIp::resolveClientIp( $maps['proxy'], $_SERVER, $maps['bot_networks'] );

		return array(
			'peer'           => (string) $resolved['peer'],
			'client'         => (string) $resolved['client'],
			'claimed'        => (string) $resolved['claimed'],
			'role'           => (string) $resolved['role'],
			'state'          => (string) $resolved['state'],
			'header'         => (string) $resolved['header'],
			'subject'        => (string) $resolved['subject'],
			'subject_source' => (string) $resolved['subject_source'],
			'verified_by'    => '',
		);
	}

	/**
	 * Manual map merged with auto-trusted entries, and the bot-network map.
	 *
	 * @return array{proxy:array<string,mixed>,bot_networks:array<string,mixed>}
	 */
	public static function trustMaps(): array {
		$proxy = class_exists( 'BotBlockerIp' ) ? BotBlockerIp::currentProxyMap() : array();
		if ( class_exists( 'BotBlockerEdgeStats' ) ) {
			foreach ( BotBlockerEdgeStats::autoTrustMap() as $mask => $entry ) {
				if ( ! isset( $proxy[ $mask ] ) ) {
					$proxy[ $mask ] = $entry;
				}
			}
		}
		if ( class_exists( 'BotBlockerIp' ) ) {
			foreach ( BotBlockerIp::currentSharedProxy() as $mask => $is_shared ) {
				if ( empty( $is_shared ) || ! isset( $proxy[ $mask ] ) || is_array( $proxy[ $mask ] ) ) {
					continue;
				}
				$proxy[ $mask ] = array(
					'header' => (string) $proxy[ $mask ],
					'scope'  => 'manual',
					'shared' => true,
				);
			}
		}

		return array(
			'proxy'        => $proxy,
			'bot_networks' => class_exists( 'BotBlockerIp' ) ? BotBlockerIp::currentBotNetworks() : array(),
		);
	}

	private static function sameIp( string $a, string $b ): bool {
		if ( $a === '' || $b === '' ) {
			return false;
		}
		$bin_a = @inet_pton( $a );
		$bin_b = @inet_pton( $b );
		if ( $bin_a === false || $bin_b === false ) {
			return $a === $b;
		}
		return $bin_a === $bin_b;
	}

	public static function reset(): void {
		self::$cache = null;
	}
}
