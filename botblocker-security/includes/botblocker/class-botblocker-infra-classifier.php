<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared-infrastructure heuristic for ban eligibility.
 */
class BotBlockerInfraClassifier {

	public const DEFAULT_KEYWORDS = array(
		'amazon',
		'amazonaws',
		'aws',
		'cloudfront',
		'google',
		'googleapis',
		'googleusercontent',
		'microsoft',
		'microsoftonline',
		'azure',
		'oracle',
		'alibaba',
		'tencent',
		'digitalocean',
		'hetzner',
		'contabo',
		'ovh',
		'leaseweb',
		'linode',
		'vultr',
		'scaleway',
		'hosting',
		'hoster',
		'datacenter',
		'colo',
		'vps',
		'vds',
		'cloud',
	);

	public static function isShared( string $ip, array $hints = array() ): bool {
		if ( $ip === '' ) {
			return false;
		}

		$hosting = $hints['hosting'] ?? '';
		if ( true === $hosting || 1 === $hosting || '1' === (string) $hosting ) {
			return true;
		}

		$haystack = strtolower( trim( (string) ( $hints['ptr'] ?? '' ) . ' ' . (string) ( $hints['asname'] ?? '' ) ) );
		if ( $haystack === '' ) {
			return false;
		}

		$keywords = apply_filters( 'bbcs_shared_infra_keywords', self::DEFAULT_KEYWORDS );
		if ( ! is_array( $keywords ) ) {
			$keywords = self::DEFAULT_KEYWORDS;
		}

		$tokens = preg_split( '/[^a-z0-9]+/', $haystack, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $tokens ) || $tokens === array() ) {
			return false;
		}
		$tokens = array_fill_keys( $tokens, true );

		foreach ( $keywords as $keyword ) {
			if ( ! is_string( $keyword ) ) {
				continue;
			}
			$keyword = strtolower( trim( $keyword ) );
			if ( $keyword === '' ) {
				continue;
			}
			if ( isset( $tokens[ $keyword ] ) ) {
				return true;
			}
		}

		return false;
	}
}
