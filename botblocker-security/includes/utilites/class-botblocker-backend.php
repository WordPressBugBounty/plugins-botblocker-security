<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cloud API backend registry.
 *
 * Ordered URL list (BOTBLOCKER_API_URLS), fallback across backends in
 * sendToCloud(), preflight reachability probe via non-blocking TCP connects
 * (stream_socket_client + stream_select; no ext-sockets), and a hook point
 * for a future AWS balancer (BOTBLOCKER_BALANCER_URL).
 */
class BotBlockerBackend {

	const PREFERRED_TRANSIENT = 'bbcs_backend_url';
	const PREFLIGHT_TRANSIENT = 'bbcs_backend_preflight_at';
	const BALANCER_TRANSIENT  = 'bbcs_balancer_urls';
	const COOLDOWN_TRANSIENT  = 'bbcs_backend_cooldown';
	const PREFERRED_TTL       = 6 * HOUR_IN_SECONDS;
	const PREFLIGHT_TTL       = HOUR_IN_SECONDS;
	const BALANCER_FAIL_TTL   = 5 * MINUTE_IN_SECONDS;
	const CONNECT_TIMEOUT     = 1.5;
	const COOLDOWN_TTL        = 60;

	public static function urls(): array {
		$urls = defined( 'BOTBLOCKER_API_URLS' ) ? (array) constant( 'BOTBLOCKER_API_URLS' ) : array();
		$urls = apply_filters( 'bbcs_backend_urls', array_merge( self::fetchFromBalancer(), $urls ) );
		$clean = array();
		foreach ( $urls as $url ) {
			if ( is_string( $url ) && $url !== '' ) {
				$clean[] = untrailingslashit( $url );
			}
		}
		return array_values( array_unique( $clean ) );
	}

	public static function preferred(): string {
		$preferred = get_transient( self::PREFERRED_TRANSIENT );
		if ( ! is_string( $preferred ) || $preferred === '' ) {
			return '';
		}
		return in_array( $preferred, self::urls(), true ) ? $preferred : '';
	}

	/**
	 * Sends $data to the first backend that answers without an error payload.
	 * Returns the decoded response, the last error payload received (when
	 * every backend answered but rejected), or false when no backend could
	 * be reached at all.
	 */
	public static function sendToCloud( array $data, string $endpoint = '', int $timeout_override = 0 ) {
		$urls = self::urls();
		if ( $urls === array() ) {
			return false;
		}
		$preferred = self::preferred();
		if ( $preferred === '' ) {
			self::maybePreflight();
			$preferred = self::preferred();
		}
		$breaker = apply_filters( 'bbcs_backend_breaker', true );
		$cooldown = array();
		if ( $breaker ) {
			$cached = get_transient( self::COOLDOWN_TRANSIENT );
			$cooldown = is_array( $cached ) ? $cached : array();
		}
		$now      = time();
		$ordered  = array();
		if ( $preferred !== '' && ( ! isset( $cooldown[ $preferred ] ) || $cooldown[ $preferred ] <= $now ) ) {
			$ordered[] = $preferred;
		}
		foreach ( $urls as $url ) {
			if ( $url === $preferred ) {
				continue;
			}
			if ( isset( $cooldown[ $url ] ) && $cooldown[ $url ] > $now ) {
				continue;
			}
			$ordered[] = $url;
		}
		if ( $ordered === array() ) {
			return false;
		}
		$last = false;
		foreach ( $ordered as $url ) {
			$result = BotBlockerWpRequest::send_to_cloud( $data, $url, $endpoint, $timeout_override );
			if ( is_array( $result ) ) {
				$last = $result;
				if ( ! isset( $result['error'] ) ) {
					if ( $breaker ) {
						delete_transient( self::COOLDOWN_TRANSIENT );
					}
					if ( $url !== self::preferred() ) {
						set_transient( self::PREFERRED_TRANSIENT, $url, self::PREFERRED_TTL );
					}
					return $result;
				}
			} elseif ( $breaker ) {
				$cooldown[ $url ] = $now + self::COOLDOWN_TTL;
				set_transient( self::COOLDOWN_TRANSIENT, $cooldown, self::COOLDOWN_TTL );
			}
		}
		self::maybePreflight();
		return $last;
	}

	/**
	 * One-shot reachability probe of every backend: all TCP connects are
	 * opened in parallel (non-blocking) and awaited via stream_select.
	 * Returns reachable URLs ordered by backend priority and caches the
	 * winner as preferred().
	 */
	public static function preflight(): array {
		$urls = self::urls();
		if ( count( $urls ) < 2 ) {
			return $urls;
		}
		$sockets = array();
		foreach ( $urls as $i => $url ) {
			$sock = self::openProbe( $url );
			if ( $sock !== null ) {
				$sockets[ $i ] = $sock;
			}
		}
		$alive    = array();
		$deadline = microtime( true ) + self::CONNECT_TIMEOUT;
		while ( $sockets !== array() ) {
			$remaining = $deadline - microtime( true );
			if ( $remaining <= 0 ) {
				break;
			}
			$read   = null;
			$except = null;
			$write  = array_values( $sockets );
			$sec    = (int) floor( $remaining );
			$usec   = (int) ( ( $remaining - $sec ) * 1000000 );
			$ready  = @stream_select( $read, $write, $except, $sec, $usec );
			if ( $ready === false || $ready === 0 ) {
				break;
			}
			foreach ( $write as $sock ) {
				$idx = array_search( $sock, $sockets, true );
				unset( $sockets[ $idx ] );
				if ( @stream_socket_get_name( $sock, true ) !== false ) {
					$alive[] = $idx;
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				@fclose( $sock );
			}
		}
		foreach ( $sockets as $sock ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			@fclose( $sock );
		}
		sort( $alive );
		$ordered = array();
		foreach ( $alive as $i ) {
			$ordered[] = $urls[ $i ];
		}
		if ( isset( $ordered[0] ) ) {
			set_transient( self::PREFERRED_TRANSIENT, $ordered[0], self::PREFERRED_TTL );
		}
		return $ordered;
	}

	/**
	 * Runs preflight() at most once per PREFLIGHT_TTL; disabled via the
	 * bbcs_backend_auto_preflight filter (test environments).
	 */
	public static function maybePreflight(): void {
		if ( ! apply_filters( 'bbcs_backend_auto_preflight', true ) ) {
			return;
		}
		if ( get_transient( self::PREFLIGHT_TRANSIENT ) !== false ) {
			return;
		}
		set_transient( self::PREFLIGHT_TRANSIENT, time(), self::PREFLIGHT_TTL );
		self::preflight();
	}

	/**
	 * Future AWS balancer: when BOTBLOCKER_BALANCER_URL is set, fetches the
	 * ordered backend URL list from it. Empty URL = disabled. Failures and
	 * empty replies are negative-cached so a dead balancer does not stall
	 * every urls() call. The URL is filterable (bbcs_balancer_url) for
	 * test/deploy override without a define.
	 */
	public static function fetchFromBalancer(): array {
		$balancer = defined( 'BOTBLOCKER_BALANCER_URL' ) ? untrailingslashit( (string) constant( 'BOTBLOCKER_BALANCER_URL' ) ) : '';
		$balancer = untrailingslashit( (string) apply_filters( 'bbcs_balancer_url', $balancer ) );
		if ( $balancer === '' ) {
			return array();
		}
		$cached = get_transient( self::BALANCER_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$response = wp_remote_get(
			$balancer,
			array(
				'timeout'     => 3,
				'redirection' => 0,
				'httpversion' => '1.1',
			)
		);
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			set_transient( self::BALANCER_TRANSIENT, array(), self::BALANCER_FAIL_TTL );
			return array();
		}
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			set_transient( self::BALANCER_TRANSIENT, array(), self::BALANCER_FAIL_TTL );
			return array();
		}
		$urls = array();
		foreach ( $decoded as $url ) {
			if ( is_string( $url ) && $url !== '' ) {
				$urls[] = untrailingslashit( $url );
			}
		}
		if ( $urls !== array() ) {
			set_transient( self::BALANCER_TRANSIENT, $urls, self::PREFERRED_TTL );
		} else {
			set_transient( self::BALANCER_TRANSIENT, array(), self::BALANCER_FAIL_TTL );
		}
		return $urls;
	}

	private static function openProbe( string $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || $host === '' ) {
			return null;
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$port   = wp_parse_url( $url, PHP_URL_PORT );
		if ( ! is_int( $port ) ) {
			$port = ( $scheme === 'http' ) ? 80 : 443;
		}
		$errno  = 0;
		$errstr = '';
		$sock   = @stream_socket_client(
			'tcp://' . $host . ':' . $port,
			$errno,
			$errstr,
			self::CONNECT_TIMEOUT,
			STREAM_CLIENT_ASYNC_CONNECT
		);
		return is_resource( $sock ) ? $sock : null;
	}
}
