<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerInstallIp {
	const SERVER_POOL_MARKER     = 'BotBlocker Server';
	const SERVER_POOL_IPV4_LABEL = 'IPv4 BotBlocker Server (for license activation, can be removed)';
	const SERVER_POOL_IPV6_LABEL = 'IPv6 BotBlocker Server (for license activation, can be removed)';
	const PUBLIC_IPV4_LABEL      = 'Server Public IPv4';
	const PUBLIC_IPV6_LABEL      = 'Server Public IPv6';

	public static function addServerIPs(): void {
		$localhostIPv4 = '127.0.0.1';
		$localhostIPv6 = '::1';

		self::addIPv4Rule( $localhostIPv4, 'Local IP' );
		if ( isset( $_SERVER['SERVER_ADDR'] ) && ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$server_addr = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
			if ( filter_var( $server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && ! BotBlockerIp::isPublicIp( $server_addr ) ) {
				self::addIPv4Rule( $server_addr, 'Local IP from SERVER_ADDR' );
			}
		}

		self::addIPv6Rule( $localhostIPv6, 'Local IPv6' );
		if ( isset( $_SERVER['SERVER_ADDR'] ) && ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$server_addr = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
			if ( filter_var( $server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && ! BotBlockerIp::isPublicIp( $server_addr ) ) {
				self::addIPv6Rule( $server_addr, 'Local IP from SERVER_ADDR' );
			}
		}
	}

	public static function scheduleSync( string $reason = 'retry', int $delay = 60 ): bool {
		$args = array( $reason );
		if ( wp_next_scheduled( 'bbcs_server_ips_sync_event', $args ) !== false ) {
			return false;
		}
		return (bool) wp_schedule_single_event( time() + max( 60, $delay ), 'bbcs_server_ips_sync_event', $args );
	}

	public static function syncServerIPs( string $reason = '' ): bool {
		try {
			self::fetchAndStoreParentIPs();
			$ipv4 = self::getServerIPv4();
			$ipv6 = self::getServerIPv6();
			global $wpdb;
			if ( $ipv4 ) {
				$wpdb->delete( $wpdb->bbcs_ipv4rules, array( 'readonly' => 1, 'comment' => self::PUBLIC_IPV4_LABEL ) );
				self::addIPv4Rule( $ipv4, self::PUBLIC_IPV4_LABEL );
			}
			if ( $ipv6 ) {
				$wpdb->delete( $wpdb->bbcs_ipv6rules, array( 'readonly' => 1, 'comment' => self::PUBLIC_IPV6_LABEL ) );
				self::addIPv6Rule( $ipv6, self::PUBLIC_IPV6_LABEL );
			}
			BotBlockerFileRenderer::renderIps();
			BotBlockerCache::clearFileCache();
			// IPv6-only absence is normal on IPv4 hosts; retry only when IPv4 is unknown.
			if ( ! $ipv4 ) {
				return self::recordSyncFailure();
			}
			BotBlockerMultisite::deleteOption( 'bbcs_server_ips_sync_failures' );
			return true;
		} catch ( Exception $e ) {
			return self::recordSyncFailure();
		}
	}

	private static function recordSyncFailure(): bool {
		$failures = (int) BotBlockerMultisite::getOption( 'bbcs_server_ips_sync_failures', 0 ) + 1;
		BotBlockerMultisite::updateOption( 'bbcs_server_ips_sync_failures', $failures, false );
		if ( $failures < 7 ) {
			self::scheduleSync( 'retry', min( 6, $failures ) * HOUR_IN_SECONDS );
		}
		return false;
	}

	public static function addAdminIPs( string $ip = '' ): void {
		if ( $ip === '' ) {
			if ( ! isset( $_SERVER['REMOTE_ADDR'] ) || empty( $_SERVER['REMOTE_ADDR'] ) ) {
				return;
			}
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		$adminIP = $ip;
		if ( filter_var( $adminIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			self::addIPv4Rule( $adminIP, 'Admin IP' );
		} elseif ( filter_var( $adminIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			self::addIPv6Rule( $adminIP, 'Admin IP' );
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $adminIP is already validated via filter_var above, exception goes to logs only.
			throw new \Exception( "Invalid IP: $adminIP" );
		}
	}

	public static function addIPv4Rule( string $ip, string $comment ): void {
		global $wpdb;
		$numeric = BotBlockerIp::toNumeric( $ip );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `{$wpdb->bbcs_ipv4rules}` (`priority`, `search`, `ip1`, `ip2`, `rule`, `comment`, `expires`, `readonly`) VALUES (%d, %s, %d, %d, %s, %s, %d, %d)",
				10,
				$ip,
				$numeric,
				$numeric,
				'allow',
				$comment,
				BOTBLOCKER_EXP_INF,
				1
			)
		);
	}

	public static function addIPv6Rule( string $ip, string $comment ): void {
		global $wpdb;

		$expandedIP = BotBlockerIp::expandIPv6( $ip );
		$binIP      = BotBlockerIp::toBinary( $expandedIP );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `{$wpdb->bbcs_ipv6rules}` (`priority`, `search`, `ip1`, `ip2`, `rule`, `comment`, `expires`, `readonly`) VALUES (%d, %s, %s, %s, %s, %s, %d, %d)",
				10,
				$expandedIP,
				$binIP,
				$binIP,
				'allow',
				$comment,
				BOTBLOCKER_EXP_INF,
				1
			)
		);
	}

	public static function getServerIPv4() {
		foreach ( BotBlockerBackend::urls() as $base ) {
			$url      = $base . '/ip?v=4';
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 10,
					'redirection' => 0,
					'httpversion' => '1.1',
					'user-agent'  => BotBlockerMultisite::getCurrentUserAgent(),
				)
			);

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				continue;
			}

			$body       = wp_remote_retrieve_body( $response );
			$serverIPv4 = trim( wp_strip_all_tags( $body ) );

			if ( filter_var( $serverIPv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return $serverIPv4;
			}
		}
		return self::getServerIPFallback( 'ipv4' );
	}

	public static function getServerIPv6() {
		foreach ( BotBlockerBackend::urls() as $base ) {
			$url      = $base . '/ip?v=6';
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 10,
					'redirection' => 0,
					'httpversion' => '1.1',
					'user-agent'  => BotBlockerMultisite::getCurrentUserAgent(),
					'headers'     => array(
						'Accept' => 'text/plain',
					),
				)
			);

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				continue;
			}

			$body       = wp_remote_retrieve_body( $response );
			$serverIPv6 = trim( wp_strip_all_tags( $body ) );

			if ( filter_var( $serverIPv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				return $serverIPv6;
			}
		}
		return self::getServerIPFallback( 'ipv6' );
	}

	public static function getServerIPFallback( string $version ) {
		if ( ! function_exists( 'shell_exec' ) ) {
			return null;
		}

		$os       = php_uname( 's' );
		$commands = array();

		if ( $version === 'ipv4' ) {
			if ( $os === 'Linux' ) {
				$commands = array(
					"timeout 2 hostname -I | awk '{print $1}'",
					"timeout 2 ip -4 addr show | grep inet | awk '{print $2}' | cut -d/ -f1",
					"timeout 2 ifconfig | grep 'inet ' | awk '{print $2}'",
				);
			} elseif ( stripos( $os, 'Windows' ) !== false ) {
				$commands = array(
					'ipconfig | findstr /R /C:"IPv4"',
				);
			}
		} elseif ( $version === 'ipv6' ) {
			if ( $os === 'Linux' ) {
				$commands = array(
					"timeout 2 hostname -I | awk '{print $2}'",
					"timeout 2 ip -6 addr show | grep inet6 | awk '{print $2}' | cut -d/ -f1",
					"timeout 2 ifconfig | grep 'inet6 ' | awk '{print $2}'",
				);
			} elseif ( stripos( $os, 'Windows' ) !== false ) {
				$commands = array(
					'ipconfig | findstr /R /C:"IPv6"',
				);
			}
		}

		foreach ( $commands as $command ) {
			$output = shell_exec( $command ) ?? false;
			if ( $output === false || trim( $output ) === '' ) {
				continue;
			}

			$ipArray = explode( "\n", trim( $output ) );
			foreach ( $ipArray as $ip ) {
				$ip = trim( $ip );
				// ipconfig/findstr output carries a label before the address
				// ("IPv4 Address. . . : 192.168.1.5"); extract the token first.
				if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					if ( $version === 'ipv6' ) {
						$pattern = '/[0-9a-fA-F]{0,4}(?::[0-9a-fA-F]{0,4}){2,7}/';
					} else {
						$pattern = '/\d{1,3}(?:\.\d{1,3}){3}/';
					}
					if ( preg_match( $pattern, $ip, $matches ) ) {
						$ip = $matches[0];
					}
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, $version === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4 ) ) {
					return $ip;
				}
			}
		}

		return null;
	}

	private static function fetchParentIPsFromUrl( string $url ): ?array {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => BotBlockerMultisite::getCurrentUserAgent(),
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > 512 * 1024 ) {
			return null;
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return $decoded;
	}

	public static function fetchAndStoreParentIPs(): void {
		$ipAddresses = null;
		foreach ( BotBlockerBackend::urls() as $base ) {
			$parts = wp_parse_url( $base );
			if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
				continue;
			}
			$pool_url = $parts['scheme'] . '://' . $parts['host'];
			if ( isset( $parts['port'] ) ) {
				$pool_url .= ':' . $parts['port'];
			}
			$ipAddresses = self::fetchParentIPsFromUrl( $pool_url . '/pool' );
			if ( $ipAddresses !== null ) {
				break;
			}
		}

		if ( ! is_array( $ipAddresses ) ) {
			throw new \Exception( 'Failed to fetch parent IPs from any configured backend.' );
		}

		if ( count( $ipAddresses ) > 10000 ) {
			throw new \Exception( 'Too many IP entries in response (max 10000).' );
		}

		$valid = array_filter( $ipAddresses, static function ( $ip ) {
			return is_string( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP );
		} );
		if ( empty( $valid ) ) {
			throw new Exception( 'Empty or invalid server IP pool.' );
		}
		global $wpdb;
		$marker = '%' . $wpdb->esc_like( self::SERVER_POOL_MARKER ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->bbcs_ipv4rules}` WHERE readonly = 1 AND comment LIKE %s", $marker ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->bbcs_ipv6rules}` WHERE readonly = 1 AND comment LIKE %s", $marker ) );

		foreach ( $valid as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				self::addIPv4Rule( $ip, self::SERVER_POOL_IPV4_LABEL );
			} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				self::addIPv6Rule( $ip, self::SERVER_POOL_IPV6_LABEL );
			} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG == true ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Install] INVALID IP: ' . $ip );

			}
		}
	}
}

add_action( 'bbcs_server_ips_sync_event', array( 'BotBlockerInstallIp', 'syncServerIPs' ), 10, 1 );
