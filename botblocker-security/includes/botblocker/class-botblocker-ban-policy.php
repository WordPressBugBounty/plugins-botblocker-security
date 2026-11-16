<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single gate for every automatic ban or block rule.
 *
 * Ban eligibility follows the address that actually sent the packet. A header
 * may move the decision subject for a configured edge, but it never removes the
 * sender from eligibility. Shared infrastructure and unresolved edges are
 * refused; the remaining peer rules are time-clamped.
 */
class BotBlockerBanPolicy {

	/**
	 * @param array<string,mixed> $context Identity and settings context.
	 * @return array{allowed:bool,target:string,reason:string,clamp:int}
	 */
	public static function check( string $target, array $context ): array {
		$peer        = (string) ( $context['peer'] ?? '' );
		$role        = (string) ( $context['role'] ?? '' );
		$edge_state  = (string) ( $context['edge_state'] ?? '' );
		$is_proxy    = (string) ( $context['isProxy'] ?? BOTBLOCKER_EMPTY );
		$block_proxy = (bool) ( $context['block_proxy_users'] ?? false );
		$proxy_map   = isset( $context['proxy_map'] ) && is_array( $context['proxy_map'] ) ? $context['proxy_map'] : array();
		$bot_nets    = isset( $context['bot_networks'] ) && is_array( $context['bot_networks'] ) ? $context['bot_networks'] : array();

		$role = self::normalizeRole( $role, $edge_state );

		if ( $target === '' || filter_var( $target, FILTER_VALIDATE_IP ) === false || ! BotBlockerIp::isPublicIp( $target ) ) {
			return self::deny( 'target' );
		}

		if ( ! ( $role === 'edge_auto' && self::sameIp( $target, $peer ) ) && ( self::inTrustMap( $target, $proxy_map ) || self::inTrustMap( $target, $bot_nets ) ) ) {
			return self::deny( 'mapped' );
		}

		if ( strtoupper( $is_proxy ) === 'DETECTED' && ! $block_proxy ) {
			return self::deny( 'proxy' );
		}

		if ( $role === 'bot_network' || $role === 'invalid' ) {
			return self::deny( 'role' );
		}

		if ( $edge_state === 'edge_unresolved' || $edge_state === 'invalid' ) {
			return self::deny( 'state' );
		}

		$time   = (int) ( $context['time'] ?? time() );
		$ttl    = (int) ( $context['ttl'] ?? DAY_IN_SECONDS );
		$tokens = self::normalizePtrTokens(
			isset( $context['ptr_tokens'] ) && is_array( $context['ptr_tokens'] )
				? $context['ptr_tokens']
				: self::configuredPtrTokens()
		);

		if ( $tokens !== array() && BotBlockerIp::isWhiteBot( $target, $tokens, $time, $ttl ) === true ) {
			return self::deny( 'bot_ptr' );
		}

		if ( $role === 'edge_auto' ) {
			if ( ! self::sameIp( $target, $peer ) ) {
				return self::deny( 'auto_edge_target' );
			}
			$clamp = self::clamp();
			if ( $clamp < 1 ) {
				return self::deny( 'clamp' );
			}
			return self::allow( $target, 'ok_clamped', $clamp );
		}

		if ( $role === 'edge' ) {
			if ( ! empty( $context['edge_shared'] ) ) {
				if ( $peer === '' || filter_var( $peer, FILTER_VALIDATE_IP ) === false ) {
					return self::deny( 'target' );
				}
				$clamp = self::clamp();
				if ( $clamp < 1 ) {
					return self::deny( 'clamp' );
				}
				return self::allow( $peer, 'ok_clamped', $clamp );
			}
			return self::allow( $target, 'ok', 0 );
		}

		if ( $role === 'unknown_edge' ) {
			if ( ! self::sameIp( $target, $peer ) ) {
				return self::deny( 'unknown_target' );
			}
			if ( BotBlockerInfraClassifier::isShared( $target, self::infraHints( $context ) ) ) {
				return self::deny( 'shared_edge' );
			}
			$clamp = self::clamp();
			if ( $clamp < 1 ) {
				return self::deny( 'clamp' );
			}
			return self::allow( $target, 'ok_clamped', $clamp );
		}

		return self::allow( $target, 'ok', 0 );
	}

	/**
	 * Configured maximum TTL for a peer rule. Zero or negative disables the
	 * writer through a denial instead of silently writing a full-TTL rule.
	 */
	public static function clamp(): int {
		$value = apply_filters( 'bbcs_unknown_edge_ban_ttl', BBCS_UNKNOWN_EDGE_BAN_TTL );
		if ( ! is_numeric( $value ) ) {
			return (int) BBCS_UNKNOWN_EDGE_BAN_TTL;
		}
		return (int) $value;
	}

	/**
	 * PTR suffixes of every enabled trusted-bot row.
	 *
	 * @return array<int,string>
	 */
	public static function configuredPtrTokens(): array {
		if ( ! class_exists( 'BotBlockerMultisite' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return array();
		}
		$file = BotBlockerMultisite::getDataDir() . 'search_engines.php';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$data = BotBlockerDataFile::safeLoad( $file );
		$se   = isset( $data['bbcs_se'] ) && is_array( $data['bbcs_se'] ) ? $data['bbcs_se'] : array();
		$rule = isset( $data['bbcs_rule'] ) && is_array( $data['bbcs_rule'] ) ? $data['bbcs_rule'] : array();

		$tokens = array();
		foreach ( $se as $name => $row_tokens ) {
			if ( isset( $rule[ $name ] ) && strtolower( (string) $rule[ $name ] ) !== BBCS_RULE_ALLOW ) {
				continue;
			}
			foreach ( (array) $row_tokens as $token ) {
				if ( ! is_string( $token ) || $token === '' || $token === '.' ) {
					continue;
				}
				if ( strncmp( $token, 'asn:', 4 ) === 0 || strncmp( $token, 'ip:', 3 ) === 0 ) {
					continue;
				}
				$tokens[] = $token;
			}
		}
		return array_values( array_unique( $tokens ) );
	}

	/**
	 * @param array<int,mixed> $tokens
	 * @return array<int,string>
	 */
	private static function normalizePtrTokens( array $tokens ): array {
		$out = array();
		foreach ( $tokens as $token ) {
			if ( ! is_string( $token ) || $token === '' || $token === '.' ) {
				continue;
			}
			if ( strncmp( $token, 'asn:', 4 ) === 0 || strncmp( $token, 'ip:', 3 ) === 0 ) {
				continue;
			}
			$out[] = $token;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param array<string,mixed> $context
	 * @return array<string,mixed>
	 */
	private static function infraHints( array $context ): array {
		return array(
			'ptr'     => (string) ( $context['ptr'] ?? '' ),
			'asname'  => (string) ( $context['asname'] ?? '' ),
			'hosting' => $context['hosting'] ?? '',
		);
	}

	private static function normalizeRole( string $role, string $state ): string {
		if ( $role !== '' ) {
			return strtolower( $role );
		}
		if ( $state === 'unknown_edge' ) {
			return 'unknown_edge';
		}
		if ( $state === 'edge_unresolved' || $state === 'trusted_edge' ) {
			return 'edge';
		}
		if ( $state === 'bot_network' ) {
			return 'bot_network';
		}
		return 'direct';
	}

	/**
	 * @param array<string,mixed> $map
	 */
	private static function inTrustMap( string $ip, array $map ): bool {
		foreach ( array_keys( $map ) as $mask ) {
			if ( BotBlockerIp::netMatch( (string) $mask, $ip ) ) {
				return true;
			}
		}
		return false;
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

	/**
	 * @return array{allowed:bool,target:string,reason:string,clamp:int}
	 */
	private static function deny( string $reason ): array {
		return array(
			'allowed' => false,
			'target'  => '',
			'reason'  => $reason,
			'clamp'   => 0,
		);
	}

	/**
	 * @return array{allowed:bool,target:string,reason:string,clamp:int}
	 */
	private static function allow( string $target, string $reason, int $clamp ): array {
		return array(
			'allowed' => true,
			'target'  => $target,
			'reason'  => $reason,
			'clamp'   => $clamp,
		);
	}
}
