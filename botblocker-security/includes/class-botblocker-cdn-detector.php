<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pilot CDN / edge-cache detector.
 *
 * Pure detection: reads request headers, hosting constants and (optionally)
 * response headers that the host already added. It never sends a network
 * request and never changes runtime behaviour.
 */
class BotBlockerCdnDetector {

	private static function rules(): array {
		return array(
			'cloudflare'  => array(
				'label'   => 'Cloudflare',
				'request' => array( 'HTTP_CF_RAY', 'HTTP_CF_CONNECTING_IP', 'HTTP_CDN_LOOP' ),
				'response' => array( 'cf-ray', 'cf-cache-status' ),
				'value'   => array( 'HTTP_CDN_LOOP' => 'cloudflare' ),
			),
			'cloudfront'  => array(
				'label'   => 'Amazon CloudFront',
				'request' => array( 'HTTP_X_AMZ_CF_ID' ),
				'response' => array( 'x-amz-cf-id', 'x-amz-cf-pop' ),
				'value'   => array(),
			),
			'fastly'      => array(
				'label'   => 'Fastly',
				'request' => array( 'HTTP_X_SERVED_BY', 'HTTP_X_FASTLY_REQUEST_ID' ),
				'response' => array( 'x-fastly-request-id', 'x-served-by' ),
				'value'   => array(),
			),
			'akamai'      => array(
				'label'   => 'Akamai',
				'request' => array( 'HTTP_X_AKAMAI_TRANSFORMED', 'HTTP_X_AKAMAI_REQUEST_ID' ),
				'response' => array( 'x-akamai-request-id', 'x-akamai-transformed' ),
				'value'   => array(),
			),
			'bunny'       => array(
				'label'   => 'Bunny CDN',
				'request' => array( 'HTTP_X_BUNNY_CDN', 'HTTP_X_BUNNY_REQUEST_ID' ),
				'response' => array( 'x-bunny-request-id' ),
				'value'   => array(),
			),
			'sucuri'      => array(
				'label'   => 'Sucuri',
				'request' => array( 'HTTP_X_SUCURI_ID', 'HTTP_X_SUCURI_CLIENTIP' ),
				'response' => array( 'x-sucuri-id' ),
				'value'   => array(),
			),
			'litespeed'   => array(
				'label'   => 'LiteSpeed Cache',
				'request' => array(),
				'response' => array( 'x-litespeed-cache', 'x-litespeed-cache-control' ),
				'value'   => array(),
			),
			'varnish'     => array(
				'label'   => 'Varnish',
				'request' => array( 'HTTP_X_VARNISH' ),
				'response' => array( 'x-varnish' ),
				'value'   => array(),
			),
		);
	}

	private static function hostingRules(): array {
		return array(
			'kinsta'     => array(
				'label'     => 'Kinsta',
				'constants' => array( 'KINSTAMU_VERSION' ),
			),
			'wpengine'   => array(
				'label'     => 'WP Engine',
				'constants' => array( 'WPE_APIKEY', 'IS_WPE' ),
			),
			'pantheon'   => array(
				'label'     => 'Pantheon',
				'constants' => array( 'PANTHEON_ENVIRONMENT' ),
			),
			'siteground' => array(
				'label'     => 'SiteGround',
				'constants' => array( 'SG_CACHEPRESS_ENABLED' ),
			),
		);
	}

	public static function detect( ?array $server = null, ?array $response_headers = null ): array {
		$server           = $server ?? $_SERVER;
		$response_headers = $response_headers ?? self::responseHeaders();
		$found            = array();

		foreach ( self::rules() as $id => $rule ) {
			$evidence = self::matchRule( $rule, $server, $response_headers );
			if ( '' !== $evidence ) {
				$found[ $id ] = array(
					'id'       => $id,
					'label'    => (string) $rule['label'],
					'source'   => $evidence,
					'edge'     => true,
				);
			}
		}

		foreach ( self::hostingRules() as $id => $rule ) {
			foreach ( (array) $rule['constants'] as $constant ) {
				if ( defined( $constant ) ) {
					$found[ $id ] = array(
						'id'     => $id,
						'label'  => (string) $rule['label'],
						'source' => 'constant:' . $constant,
						'edge'   => true,
					);
					break;
				}
			}
		}

		$software = (string) ( $server['SERVER_SOFTWARE'] ?? '' );
		if ( '' !== $software && stripos( $software, 'litespeed' ) !== false && ! isset( $found['litespeed'] ) ) {
			$found['litespeed'] = array(
				'id'     => 'litespeed',
				'label'  => 'LiteSpeed Cache',
				'source' => 'server:litespeed',
				'edge'   => false,
			);
		}

		$found = apply_filters( 'bbcs_cdn_detected', $found, $server, $response_headers );
		return is_array( $found ) ? $found : array();
	}

	private static function matchRule( array $rule, array $server, array $response_headers ): string {
		foreach ( (array) $rule['request'] as $key ) {
			if ( empty( $server[ $key ] ) ) {
				continue;
			}
			$expected = (string) ( $rule['value'][ $key ] ?? '' );
			if ( '' !== $expected && stripos( (string) $server[ $key ], $expected ) === false ) {
				continue;
			}
			return 'request-header:' . $key;
		}
		foreach ( (array) $rule['response'] as $name ) {
			$value = self::responseHeader( $response_headers, (string) $name );
			if ( '' !== $value ) {
				return 'response-header:' . $name;
			}
		}
		return '';
	}

	private static function responseHeaders(): array {
		$headers = array();
		if ( function_exists( 'headers_list' ) ) {
			foreach ( (array) headers_list() as $line ) {
				$parts = explode( ':', (string) $line, 2 );
				if ( count( $parts ) === 2 ) {
					$headers[ strtolower( trim( $parts[0] ) ) ] = trim( $parts[1] );
				}
			}
		}
		return $headers;
	}

	private static function responseHeader( array $headers, string $name ): string {
		$name = strtolower( $name );
		return isset( $headers[ $name ] ) ? (string) $headers[ $name ] : '';
	}

	public static function cacheSystems(): array {
		$systems = array();
		foreach ( self::detect() as $id => $item ) {
			$systems[ 'cdn_' . $id ] = array(
				'label'   => (string) ( $item['label'] ?? $id ),
				'state'   => 'installed',
				'warning' => '',
				'note'    => sprintf(
					/* translators: %s: detection source */
					__( 'Detected via %s. Verify that the BotBlocker cookie bypasses the edge cache.', 'botblocker-security' ),
					(string) ( $item['source'] ?? '' )
				),
			);
		}
		return $systems;
	}
}
