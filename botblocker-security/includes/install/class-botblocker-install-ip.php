<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerInstallIp {

	public static function addServerIPs(): void {
		$localhostIPv4 = '127.0.0.1';
		$localhostIPv6 = '::1';

		self::addIPv4Rule( $localhostIPv4, 'Local IP' );
		if ( isset( $_SERVER['SERVER_ADDR'] ) && ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$server_addr = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
			if ( filter_var( $server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				self::addIPv4Rule( $server_addr, 'Local IP from SERVER_ADDR' );
			}
		}
		$serverIPv4 = self::getServerIPv4();
		if ( $serverIPv4 ) {
			self::addIPv4Rule( $serverIPv4, 'Server IPv4' );
		}

		self::addIPv6Rule( $localhostIPv6, 'Local IPv6' );
		if ( isset( $_SERVER['SERVER_ADDR'] ) && ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$server_addr = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
			if ( filter_var( $server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				self::addIPv6Rule( $server_addr, 'Local IP from SERVER_ADDR' );
			}
		}
		$serverIPv6 = self::getServerIPv6();
		if ( $serverIPv6 ) {
			self::addIPv6Rule( $serverIPv6, 'Server IPv6' );
		}
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
		$urls = array(
			BOTBLOCKER_API_URL . '/ip?v=4',
			BOTBLOCKER_API_GS_URL . '/ip?v=4',
		);
		foreach ( $urls as $url ) {
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
		$urls = array(
			BOTBLOCKER_API_URL . '/ip?v=6',
			BOTBLOCKER_API_GS_URL . '/ip?v=6',
		);
		foreach ( $urls as $url ) {
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
			} elseif ( $os === 'Windows' ) {
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
			} elseif ( $os === 'Windows' ) {
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
				if ( filter_var( $ip, FILTER_VALIDATE_IP, $version === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4 ) ) {
					return $ip;
				}
			}
		}

		return null;
	}

	public static function fetchAndStoreParentIPs(): void {
		$response = wp_remote_get(
			BOTBLOCKER_PARENT_IPS_URL,
			array(
				'timeout'    => 10,
				'user-agent' => BotBlockerMultisite::getCurrentUserAgent(), //BBCS-MULTISITE
			// 'sslverify'  => false
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( wp_kses_post( 'Error loading URL: ' . esc_url( BOTBLOCKER_PARENT_IPS_URL ) . ' | WP Error: ' . $response->get_error_message() ) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > 512 * 1024 ) {
			throw new \Exception( 'Response too large from ' . esc_url( BOTBLOCKER_PARENT_IPS_URL ) );
		}

		$ipAddresses = json_decode( $body, true );

		if ( ! is_array( $ipAddresses ) ) {
			throw new \Exception( 'Invalid JSON format in response.' );
		}

		if ( count( $ipAddresses ) > 10000 ) {
			throw new \Exception( 'Too many IP entries in response (max 10000).' );
		}

		foreach ( $ipAddresses as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				self::addIPv4Rule( $ip, 'IPv4 BotBlocker Server' );
			} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				self::addIPv6Rule( $ip, 'IPv6 BotBlocker Server' );
			} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG == true ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Install] INVALID IP: ' . $ip );

			}
		}
	}
}
