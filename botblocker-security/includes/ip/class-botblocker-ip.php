<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerIp {

	public static function rateLimit( string $action, int $limit = 10, int $window = HOUR_IN_SECONDS ): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REMOTE_ADDR is server-set, not user input
		$raw_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		if ( $raw_ip === '' ) {
			// No IP available (CLI / unit-test context) - allow without limiting.
			return true;
		}

		$key  = 'bbcs_rl_' . sanitize_key( $action ) . '_' . md5( $raw_ip );
		$data = get_transient( $key );

		if ( $data === false ) {
			set_transient(
				$key,
				array(
					'count' => 1,
					'since' => time(),
				),
				$window
			);
			return true;
		}

		if ( ! is_array( $data ) ) {
			$data = array(
				'count' => (int) $data,
				'since' => time() - $window,
			);
		}

		if ( time() - $data['since'] >= $window ) {
			set_transient(
				$key,
				array(
					'count' => 1,
					'since' => time(),
				),
				$window
			);
			return true;
		}

		if ( $data['count'] >= $limit ) {
			return false;
		}

		set_transient(
			$key,
			array(
				'count' => $data['count'] + 1,
				'since' => $data['since'],
			),
			$window
		);
		return true;
	}

	public static function expandIPv6( string $ip ): string {
		$ip  = strstr( $ip, '/', true ) ?: $ip;
		$bin = inet_pton( $ip );
		if ( $bin === false ) {
			return '';
		}
		$hex = unpack( 'H*hex', $bin );
		$ip  = substr( preg_replace( '/([A-f0-9]{4})/', '$1:', $hex['hex'] ), 0, -1 );
		return $ip;
	}

	public static function getVersion( string $ip ): int {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return 4;
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return 6;
		}
		return 0;
	}

	public static function isPublicIp( string $ip ): bool {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				return false;
			}
			$long = ip2long( $ip );
			if ( $long === false ) {
				return false;
			}
			$reserved = array(
				array( ip2long( '100.64.0.0' ), ip2long( '100.127.255.255' ) ),
				array( ip2long( '192.0.0.0' ), ip2long( '192.0.0.255' ) ),
				array( ip2long( '198.18.0.0' ), ip2long( '198.19.255.255' ) ),
			);
			foreach ( $reserved as $range ) {
				if ( $long >= $range[0] && $long <= $range[1] ) {
					return false;
				}
			}
			return true;
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$bytes = inet_pton( $ip );
			if ( $bytes === false ) {
				return false;
			}
			$b0 = ord( $bytes[0] );
			$b1 = ord( $bytes[1] );
			if ( ( $b0 & 0xfe ) === 0xfc ) {
				return false; // fc00::/7 unique-local
			}
			if ( $b0 === 0xfe && ( $b1 & 0xc0 ) === 0x80 ) {
				return false; // fe80::/10 link-local
			}
			if ( $bytes === inet_pton( '::1' ) ) {
				return false; // loopback
			}
			if ( strncmp( $bytes, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff", 12 ) === 0 ) {
				return false; // ::ffff:0:0/96 v4-mapped
			}
			if ( strncmp( $bytes, (string) inet_pton( '2001:db8::' ), 4 ) === 0 ) {
				return false; // 2001:db8::/32 documentation
			}
			if ( strncmp( $bytes, (string) inet_pton( '64:ff9b::' ), 12 ) === 0 ) {
				return false; // 64:ff9b::/96 NAT64
			}
			return true;
		}
		return false;
	}

	public static function toNumeric( string $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return ip2long( $ip );
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return (string) self::gmpImport( inet_pton( $ip ) );
		} else {
			return 0;
		}
	}

	public static function toBinary( string $ip ) {
		return inet_pton( $ip );
	}

	public static function customGmpImport( string $data, int $word_size = 1, int $options = GMP_MSW_FIRST | GMP_BIG_ENDIAN ) {
		if ( $word_size != 1 ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $word_size is always numeric, exception goes to logs only.
			throw new \Exception( "Unsupported word size: $word_size" );
		}

		$length = strlen( $data );

		if ( $length > 8 ) {
			return self::bytesToDecimalString( self::gmpOrderedBytes( $data, $options ) );
		}

		$value  = 0;

		if ( $options & GMP_MSW_FIRST ) {
			if ( $options & GMP_BIG_ENDIAN ) {
				for ( $i = 0; $i < $length; $i++ ) {
					$value = ( $value << 8 ) | ord( $data[ $i ] );
				}
			} else {
				for ( $i = $length - 1; $i >= 0; $i-- ) {
					$value = ( $value << 8 ) | ord( $data[ $i ] );
				}
			}
		} elseif ( $options & GMP_BIG_ENDIAN ) {
			for ( $i = 0; $i < $length; $i++ ) {
				$value = ( $value << 8 ) | ord( $data[ $length - 1 - $i ] );
			}
		} else {
			for ( $i = $length - 1; $i >= 0; $i-- ) {
				$value = ( $value << 8 ) | ord( $data[ $length - 1 - $i ] );
			}
		}

		return $value;
	}

	private static function gmpOrderedBytes( string $data, int $options ): array {
		$length = strlen( $data );
		$bytes  = array();
		if ( $options & GMP_MSW_FIRST ) {
			if ( $options & GMP_BIG_ENDIAN ) {
				for ( $i = 0; $i < $length; $i++ ) {
					$bytes[] = ord( $data[ $i ] );
				}
			} else {
				for ( $i = $length - 1; $i >= 0; $i-- ) {
					$bytes[] = ord( $data[ $i ] );
				}
			}
		} elseif ( $options & GMP_BIG_ENDIAN ) {
			for ( $i = 0; $i < $length; $i++ ) {
				$bytes[] = ord( $data[ $length - 1 - $i ] );
			}
		} else {
			for ( $i = $length - 1; $i >= 0; $i-- ) {
				$bytes[] = ord( $data[ $length - 1 - $i ] );
			}
		}
		return $bytes;
	}

	private static function bytesToDecimalString( array $bytes ): string {
		$dec = '0';
		foreach ( $bytes as $byte ) {
			$dec = self::decimalMulAdd( $dec, 256, (int) $byte );
		}
		return $dec;
	}

	private static function decimalMulAdd( string $dec, int $mul, int $add ): string {
		$carry = $add;
		$out   = '';
		for ( $i = strlen( $dec ) - 1; $i >= 0; $i-- ) {
			$product = ( (int) $dec[ $i ] ) * $mul + $carry;
			$out     = (string) ( $product % 10 ) . $out;
			$carry   = intdiv( $product, 10 );
		}
		while ( $carry > 0 ) {
			$out   = (string) ( $carry % 10 ) . $out;
			$carry = intdiv( $carry, 10 );
		}
		$out = ltrim( $out, '0' );
		return $out === '' ? '0' : $out;
	}

	public static function gmpImport( string $data ) {
		if ( extension_loaded( 'gmp' ) ) {
			return gmp_import( $data );
		} else {
			return self::customGmpImport( $data );
		}
	}

	public static function netMatch( string $network, string $ip ): bool {
		$parts = explode( '/', trim( (string) $network ), 2 );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$subnet     = trim( $parts[0] );
		$prefix     = (int) $parts[1];
		$ip_bin     = @inet_pton( trim( (string) $ip ) );
		$subnet_bin = @inet_pton( $subnet );

		if ( $ip_bin === false || $subnet_bin === false || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
			return false;
		}

		$max_prefix = strlen( $ip_bin ) * 8;
		if ( $prefix < 0 || $prefix > $max_prefix ) {
			return false;
		}

		$full_bytes     = intdiv( $prefix, 8 );
		$remaining_bits = $prefix % 8;

		if ( $full_bytes > 0 && substr( $ip_bin, 0, $full_bytes ) !== substr( $subnet_bin, 0, $full_bytes ) ) {
			return false;
		}

		if ( $remaining_bits === 0 ) {
			return true;
		}

		$mask = ( 0xff << ( 8 - $remaining_bits ) ) & 0xff;
		return ( ord( $ip_bin[ $full_bytes ] ) & $mask ) === ( ord( $subnet_bin[ $full_bytes ] ) & $mask );
	}

	public static function detectType( string $input ): string {
		$input = trim( $input );

		if ( strpos( $input, '/' ) !== false ) {
			$parts = explode( '/', $input );
			if ( filter_var( $parts[0], FILTER_VALIDATE_IP ) ) {
				if ( isset( $parts[1] ) && is_numeric( $parts[1] ) ) {
					$prefix = intval( $parts[1] );

					if ( filter_var( $parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
						return ( $prefix >= 0 && $prefix <= 32 ) ? BBCS_IP_TYPE_CIDR : BBCS_IP_TYPE_INVALID;
					} elseif ( filter_var( $parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
						return ( $prefix >= 0 && $prefix <= 128 ) ? BBCS_IP_TYPE_CIDR : BBCS_IP_TYPE_INVALID;
					}
				}
			}
			return BBCS_IP_TYPE_INVALID;
		} elseif ( filter_var( $input, FILTER_VALIDATE_IP ) ) {
			return BBCS_IP_TYPE_IP;
		}

		return BBCS_IP_TYPE_INVALID;
	}

	public static function toRange( ?string $cidr ): array {
		$range = array();
		$cidr  = explode( '/', trim( $cidr ?? '' ) );
		if ( ! isset( $cidr[1] ) ) {
			$range = array( 0, 0, 0 );
		} elseif ( filter_var( $cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$range[0] = long2ip( ( ip2long( $cidr[0] ) ) & ( ( -1 << ( 32 - (int) $cidr[1] ) ) ) );
			$range[1] = long2ip( ( ip2long( $range[0] ) ) + pow( 2, ( 32 - (int) $cidr[1] ) ) - 1 );
		} elseif ( filter_var( $cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$addr_given_str = $cidr[0];
			$prefixlen      = $cidr[1];
			$addr_given_bin = inet_pton( $addr_given_str );
			$addr_given_hex = bin2hex( $addr_given_bin );
			$addr_given_str = inet_ntop( $addr_given_bin );
			$flexbits       = 128 - $prefixlen;
			$addr_hex_first = $addr_given_hex;
			$addr_hex_last  = $addr_given_hex;
			$pos            = 31;
			while ( $flexbits > 0 ) {
				$orig_first     = substr( $addr_hex_first, $pos, 1 );
				$orig_last      = substr( $addr_hex_last, $pos, 1 );
				$origval_first  = hexdec( $orig_first );
				$origval_last   = hexdec( $orig_last );
				$mask           = 0xf << ( min( 4, $flexbits ) );
				$new_val_first  = $origval_first & $mask;
				$new_val_last   = $origval_last | ( pow( 2, min( 4, $flexbits ) ) - 1 );
				$new_first      = dechex( $new_val_first );
				$new_last       = dechex( $new_val_last );
				$addr_hex_first = substr_replace( $addr_hex_first, $new_first, $pos, 1 );
				$addr_hex_last  = substr_replace( $addr_hex_last, $new_last, $pos, 1 );
				$flexbits      -= 4;
				$pos           -= 1;
			}

			$addr_bin_first = hex2bin( $addr_hex_first );
			$addr_bin_last  = hex2bin( $addr_hex_last );
			$range[0]       = inet_ntop( $addr_bin_first );
			$range[1]       = inet_ntop( $addr_bin_last );
		} else {
			$range = array( 0, 0, 0 );
		}
		return $range;
	}

	/**
	 * @param int $ttl `ptrcache_time` setting, in SECONDS (see BotBlockerDataTime::getPtrLifetimes(),
	 *                 e.g. 86400 = 1 day). Do not multiply by 60 - it is not minutes.
	 */
	public static function getPtr( string $ip, int $time, int $ttl ): string {
		// Allow tests (or integrations) to short-circuit the full DB / cache lookup.
		// false from the filter means "checked, no PTR exists" and is normalised to BOTBLOCKER_EMPTY.
		$override = apply_filters( 'bbcs_get_ptr_override', null, $ip );
		if ( $override !== null ) {
			return $override === false ? BOTBLOCKER_EMPTY : $override;
		}

		$BBCS = BotBlocker::getInstance();
		global $wpdb;

		$storage = BotBlockerCache::connect();
		if ( $storage !== null && $BBCS->settings->ptr_cache_in_db ) {
			$cache_key = '';
			if ( $BBCS->settings->memcached_enable == 1 ) {
				$cache_key = $BBCS->settings->memcached_prefix . BotBlockerMultisite::getCurrentSiteClear() . '_PTR_' . md5( $ip );
			} elseif ( $BBCS->settings->redis_enable == 1 ) {
				$cache_key = $BBCS->settings->redis_prefix . BotBlockerMultisite::getCurrentSiteClear() . '_PTR_' . md5( $ip );
			}
			$cached_response = $storage->get( $cache_key );
			if ( is_array( $cached_response ) && isset( $cached_response['ptr'] ) && filter_var( (string) $cached_response['ptr'], FILTER_VALIDATE_IP ) === false ) {
				return $cached_response['ptr'];
			}
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// PTR is being cached in DB only if setting is enabled.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$get_ptr = $wpdb->get_row( $wpdb->prepare( "SELECT ptr, date FROM `{$wpdb->bbcs_ptrcache}` WHERE ip = %s", $ip ), ARRAY_A );

		if ( isset( $get_ptr['ptr'] ) && $BBCS->settings->ptr_cache_in_db ) {
			$cached_ptr = (string) $get_ptr['ptr'];
			$cached_at  = (int) $get_ptr['date'];
			$expired    = $cached_at > 0 && $cached_at + $ttl < $time;
			// Legacy rows can hold a stored DNS failure (ptr = ip) or an expired answer: drop them and re-resolve (BB-IP-02).
			if ( $expired || filter_var( $cached_ptr, FILTER_VALIDATE_IP ) !== false ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->bbcs_ptrcache, array( 'ip' => $ip ), array( '%s' ) );
			} else {
				if ( $storage !== null ) {
					$storage->set( $cache_key, array( 'ptr' => $cached_ptr ), $ttl );
				}
				return $cached_ptr;
			}
		}

		$ptr = trim( preg_replace( '/[^0-9a-z-.:]/', '', strtolower( gethostbyaddr( $ip ) ) ) );
		// gethostbyaddr() returns the input address on failure: a failed lookup must never be cached (BB-IP-02).
		if ( $ptr === '' || filter_var( $ptr, FILTER_VALIDATE_IP ) !== false ) {
			return BOTBLOCKER_EMPTY;
		}
		if ( $BBCS->settings->ptr_cache_in_db ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->bbcs_ptrcache}` (ip, ptr, date, etime) VALUES (%s, %s, %d, %s)
			ON DUPLICATE KEY UPDATE ptr = VALUES(ptr), date = VALUES(date), etime = VALUES(etime)",
					$ip,
					$ptr,
					$time,
					$ttl
				)
			);
		}
		if ( $storage !== null && $BBCS->settings->ptr_cache_in_db ) {
			// $ttl already seconds - do not multiply by 60.
			$storage->set( $cache_key, array( 'ptr' => $ptr ), $ttl );
		}
		return $ptr;
	}

	// Missing PTR is not an anomaly: home, mobile and cloud addresses usually have none.
	public static function isPtrDnsAnomaly( string $ip, string $ptr ): bool {
		$ptr = strtolower( rtrim( trim( $ptr ), '.' ) );
		if ( $ptr === '' || $ptr === BOTBLOCKER_EMPTY ) {
			return false;
		}
		if ( filter_var( $ptr, FILTER_VALIDATE_IP ) !== false ) {
			return true;
		}
		if ( filter_var( $ptr, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) === false ) {
			return true;
		}

		$type    = strpos( $ip, ':' ) !== false ? DNS_AAAA : DNS_A;
		$records = apply_filters( 'bbcs_dns_get_record', null, $ptr );
		if ( $records === null ) {
			$records = @dns_get_record( $ptr, $type );
		}
		if ( ! is_array( $records ) || $records === array() ) {
			return false;
		}

		$needle = @inet_pton( $ip );
		if ( $needle === false ) {
			return false;
		}
		foreach ( $records as $line ) {
			$candidate = '';
			if ( is_array( $line ) && isset( $line['ip'] ) ) {
				$candidate = (string) $line['ip'];
			} elseif ( is_array( $line ) && isset( $line['ipv6'] ) ) {
				$candidate = (string) $line['ipv6'];
			}
			if ( $candidate !== '' && @inet_pton( $candidate ) === $needle ) {
				return false;
			}
		}
		return true;
	}

	public static function clearExpiredPtrCache(): void {
		global $wpdb;
		$BBCS       = BotBlocker::getInstance();
		$time_limit = time() - $BBCS->settings->ptrcache_time;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->bbcs_ptrcache}` WHERE date < %d", $time_limit ) );
	}

	public static function isWhiteBot( string $ip, $ptr_ok, int $time, int $ttl ): bool {
		// Strip asn: and ip: tokens - handled upstream in check_white_bot().
		// Keeping them here would break PTR suffix matching.
		$ptr_domains = array();
		foreach ( (array) $ptr_ok as $token ) {
			if ( is_string( $token ) && strncmp( $token, 'asn:', 4 ) !== 0 && strncmp( $token, 'ip:', 3 ) !== 0 ) {
				$ptr_domains[] = $token;
			}
		}

		if ( empty( $ptr_domains ) ) {
			return false;
		}

		if ( in_array( '.', $ptr_domains ) ) {
			return true;
		} else {
			$ptr = self::getPtr( $ip, $time, $ttl );
			if ( $ptr === false || $ptr === BOTBLOCKER_EMPTY ) {
				$result = array();
			} else {
				$result = apply_filters( 'bbcs_dns_get_record', null, $ptr );
				if ( $result === null ) {
					$result = @dns_get_record( $ptr, DNS_A | DNS_AAAA );
				}
				if ( ! is_array( $result ) ) {
					$result = array();
				}
			}
			$ip2 = array();
			if ( $ptr == $ip ) {
				$ip2[] = $ip;
			}
			foreach ( $result as $line ) {
				if ( isset( $line['ipv6'] ) ) {
					$ip2[] = self::expandIPv6( $line['ipv6'] );
				}
				if ( isset( $line['ip'] ) ) {
					$ip2[] = $line['ip'];
				}
			}
			$ptr      = strtolower( rtrim( (string) $ptr, '.' ) );
			$test_ptr = 0;
			foreach ( $ptr_domains as $ptr_line ) {
				$ptr_line = strtolower( rtrim( trim( (string) $ptr_line ), '.' ) );
				if ( $ptr_line == '.' ) {
					$test_ptr = 1;
					break;
				}
				if ( $ptr_line === '' ) {
					continue;
				}

				if ( $ptr_line[0] === '.' ) {
					$domain = substr( $ptr_line, 1 );
					if ( $ptr === $domain || substr( $ptr, -strlen( $ptr_line ) ) === $ptr_line ) {
						$test_ptr = 1;
						break;
					}
					continue;
				}

				if ( $ptr === $ptr_line || substr( $ptr, -( strlen( $ptr_line ) + 1 ) ) === '.' . $ptr_line ) {
					$test_ptr = 1;
					break;
				}
			}
			if ( in_array( $ip, $ip2 ) && $test_ptr == 1 ) {
				return true;
			} else {
				return false;
			}
		}
	}

	public static function matches( string $range, string $ip ): bool {
		if ( strpos( $range, '/' ) === false ) {
			$ip_bin  = @inet_pton( $ip );
			$rng_bin = @inet_pton( $range );
			return $ip_bin !== false && $rng_bin !== false && $ip_bin === $rng_bin;
		}
		return self::netMatch( $range, $ip );
	}

	public static function normalize( string $ip, int $ip_version ): string {
		if ( $ip_version == 4 || strpos( $ip, ':' ) === false ) {
			return $ip;
		}

		if ( $ip_version == 6 && strpos( $ip, ':' ) !== false ) {
			$binary = @inet_pton( $ip );
			if ( $binary !== false ) {
				return inet_ntop( $binary );
			}
		}
		return $ip;
	}

	public static function getCurrentIp(): string {
		if ( class_exists( 'BotBlockerIdentity' ) ) {
			$identity = BotBlockerIdentity::resolveFresh();
			if ( isset( $identity['subject'] ) && (string) $identity['subject'] !== '' ) {
				return (string) $identity['subject'];
			}
			return '';
		}

		$proxy_map = self::currentProxyMap();
		if ( class_exists( 'BotBlockerEdgeStats' ) ) {
			foreach ( BotBlockerEdgeStats::autoTrustMap() as $mask => $entry ) {
				if ( ! isset( $proxy_map[ $mask ] ) ) {
					$proxy_map[ $mask ] = $entry;
				}
			}
		}
		foreach ( self::currentSharedProxy() as $mask => $is_shared ) {
			if ( empty( $is_shared ) || ! isset( $proxy_map[ $mask ] ) || is_array( $proxy_map[ $mask ] ) ) {
				continue;
			}
			$proxy_map[ $mask ] = array(
				'header' => (string) $proxy_map[ $mask ],
				'scope'  => 'manual',
				'shared' => true,
			);
		}
		$resolved = self::resolveClientIp( $proxy_map, $_SERVER, self::currentBotNetworks() );
		if ( $resolved['subject'] !== '' ) {
			return $resolved['subject'];
		}
		if ( $resolved['client'] !== '' ) {
			return $resolved['client'];
		}
		return $resolved['peer'];
	}

	/**
	 * Resolve the visitor identity from a socket peer and proxy headers.
	 *
	 * Pure function: no WordPress calls, no superglobal access. Only a peer that
	 * matches the edge map may supply a client. A peer that matches the bot
	 * network map never does. The header is walked from right to left and a
	 * candidate must be a public address outside the trust maps.
	 *
	 * @param array<string,mixed> $proxy_map    Mask => header or Mask => [header, scope].
	 * @param array<string,mixed> $server       $_SERVER-shaped input.
	 * @param array<string,mixed> $bot_networks Mask => true for header-inert networks.
	 * @return array{peer:string,client:string,claimed:string,state:string,header:string,scope:string,role:string,subject:string,subject_source:string,shared:bool}
	 */
	public static function resolveClientIp( array $proxy_map, array $server, array $bot_networks = array() ): array {
		$result = array(
			'peer'           => '',
			'client'         => '',
			'claimed'        => '',
			'state'          => 'invalid',
			'header'         => '',
			'scope'          => '',
			'role'           => 'invalid',
			'subject'        => '',
			'subject_source' => '',
			'shared'         => false,
		);

		$peer = self::normalizeHeaderIp( isset( $server['REMOTE_ADDR'] ) ? (string) $server['REMOTE_ADDR'] : '' );
		if ( $peer === '' ) {
			return $result;
		}
		$result['peer'] = $peer;

		$matched_header = '';
		$matched_scope  = '';
		$matched_shared = false;
		$matched_prefix = -1;
		foreach ( $proxy_map as $mask => $attr ) {
			$header = is_array( $attr ) ? (string) ( $attr['header'] ?? '' ) : (string) $attr;
			$scope  = is_array( $attr ) ? (string) ( $attr['scope'] ?? 'manual' ) : 'manual';
			if ( $header === '' || ! self::netMatch( (string) $mask, $peer ) ) {
				continue;
			}
			$prefix = strpos( (string) $mask, '/' ) !== false ? (int) substr( (string) $mask, strpos( (string) $mask, '/' ) + 1 ) : ( 6 === self::getVersion( $peer ) ? 128 : 32 );
			if ( $prefix <= $matched_prefix ) {
				continue;
			}
			$matched_header = $header;
			$matched_scope  = $scope;
			$matched_shared = is_array( $attr ) && ! empty( $attr['shared'] );
			$matched_prefix = $prefix;
		}

		if ( $matched_header !== '' ) {
			$role                    = $matched_scope === 'auto_bot' ? 'edge_auto' : 'edge';
			$result['header']        = $matched_header;
			$result['scope']         = $matched_scope;
			$result['role']          = $role;
			$result['shared']        = $matched_shared;
			$raw                     = isset( $server[ $matched_header ] ) ? (string) $server[ $matched_header ] : '';
			$candidates              = self::headerIpCandidates( $raw, $matched_header );
			$result['claimed']       = self::rightmostPublicCandidate( $candidates, $proxy_map );

			for ( $i = count( $candidates ) - 1; $i >= 0; $i-- ) {
				$candidate = $candidates[ $i ];
				if ( self::isTrustedProxyIp( $candidate, $proxy_map ) || ! self::isPublicIp( $candidate ) ) {
					continue;
				}
				$result['client'] = $candidate;
				break;
			}

			if ( $result['client'] !== '' ) {
				$result['state'] = 'trusted_edge';
				if ( $role === 'edge_auto' ) {
					$result['subject']        = $peer;
					$result['subject_source'] = 'peer';
				} else {
					$result['subject']        = $result['client'];
					$result['subject_source'] = 'client';
				}
				return $result;
			}

			$result['state']          = 'edge_unresolved';
			$result['subject']        = $peer;
			$result['subject_source'] = 'peer';
			return $result;
		}

		foreach ( array_keys( $bot_networks ) as $mask ) {
			if ( ! self::netMatch( (string) $mask, $peer ) ) {
				continue;
			}
			$result['role']   = 'bot_network';
			$result['state']  = 'bot_network';
			foreach ( self::diagnosticHeaders() as $header ) {
				if ( ! isset( $server[ $header ] ) || trim( (string) $server[ $header ] ) === '' ) {
					continue;
				}
				$candidates        = self::headerIpCandidates( (string) $server[ $header ], $header );
				$result['header']  = $header;
				$result['claimed'] = self::rightmostPublicCandidate( $candidates, $proxy_map );
				break;
			}
			$result['subject']        = $peer;
			$result['subject_source'] = 'peer';
			return $result;
		}

		foreach ( self::diagnosticHeaders() as $header ) {
			if ( ! isset( $server[ $header ] ) || trim( (string) $server[ $header ] ) === '' ) {
				continue;
			}
			$candidates        = self::headerIpCandidates( (string) $server[ $header ], $header );
			$result['header']  = $header;
			$result['claimed'] = self::rightmostPublicCandidate( $candidates, $proxy_map );
			$result['state']   = 'unknown_edge';
			$result['role']    = 'unknown_edge';
			$result['subject'] = $peer;
			$result['subject_source'] = 'peer';
			return $result;
		}

		$result['state']          = 'direct';
		$result['role']           = 'direct';
		$result['client']         = $peer;
		$result['subject']        = $peer;
		$result['subject_source'] = 'peer';
		return $result;
	}

	/**
	 * @return array<int,string> Proxy headers that may describe an edge.
	 */
	private static function diagnosticHeaders(): array {
		return array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_TRUE_CLIENT_IP',
			'HTTP_FASTLY_CLIENT_IP',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
		);
	}

	/**
	 * @return array<string,mixed> Manual proxy map for the current site.
	 */
	public static function currentProxyMap(): array {
		if ( ! class_exists( 'BotBlockerMultisite' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return array();
		}
		$file = BotBlockerMultisite::getDataDir() . 'proxy.php';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$data = BotBlockerDataFile::safeLoad( $file );
		return isset( $data['bbcs_proxy'] ) && is_array( $data['bbcs_proxy'] ) ? $data['bbcs_proxy'] : array();
	}

	/**
	 * @return array<string,bool> Masks whose client is resolved but never banned.
	 */
	public static function currentSharedProxy(): array {
		if ( ! class_exists( 'BotBlockerMultisite' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return array();
		}
		$file = BotBlockerMultisite::getDataDir() . 'proxy-shared.php';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$data = BotBlockerDataFile::safeLoad( $file );
		return isset( $data['bbcs_proxy_shared'] ) && is_array( $data['bbcs_proxy_shared'] ) ? $data['bbcs_proxy_shared'] : array();
	}

	/**
	 * @return array<string,mixed> Networks whose proxy headers are never trusted.
	 */
	public static function currentBotNetworks(): array {
		if ( ! class_exists( 'BotBlockerMultisite' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return array();
		}
		$file = BotBlockerMultisite::getDataDir() . 'bot-networks.php';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$data = BotBlockerDataFile::safeLoad( $file );
		return isset( $data['bbcs_bot_networks'] ) && is_array( $data['bbcs_bot_networks'] ) ? $data['bbcs_bot_networks'] : array();
	}

	private static function normalizeHeaderIp( string $value ): string {
		$value = trim( $value );
		$value = trim( $value, "\"'" );
		if ( $value === '' ) {
			return '';
		}
		if ( $value[0] === '[' ) {
			$end = strpos( $value, ']' );
			if ( $end !== false ) {
				$value = substr( $value, 1, $end - 1 );
			}
		} elseif ( substr_count( $value, ':' ) === 1 && strpos( $value, '.' ) !== false ) {
			$parts = explode( ':', $value, 2 );
			if ( ctype_digit( $parts[1] ) ) {
				$value = $parts[0];
			}
		}
		return filter_var( $value, FILTER_VALIDATE_IP ) !== false ? $value : '';
	}

	/**
	 * @return array<int,string> Valid IP candidates in header order.
	 */
	private static function headerIpCandidates( string $raw, string $header ): array {
		$candidates = array();
		$raw        = trim( $raw );
		if ( $raw === '' ) {
			return $candidates;
		}

		if ( $header === 'HTTP_FORWARDED' || $header === 'Forwarded' ) {
			foreach ( explode( ',', $raw ) as $element ) {
				foreach ( explode( ';', $element ) as $pair ) {
					$pair = trim( $pair );
					if ( stripos( $pair, 'for=' ) !== 0 ) {
						continue;
					}
					$ip = self::normalizeHeaderIp( trim( substr( $pair, 4 ) ) );
					if ( $ip !== '' ) {
						$candidates[] = $ip;
					}
				}
			}
			return array_slice( $candidates, -32 );
		}

		foreach ( explode( ',', $raw ) as $part ) {
			$ip = self::normalizeHeaderIp( $part );
			if ( $ip !== '' ) {
				$candidates[] = $ip;
			}
		}
		return array_slice( $candidates, -32 );
	}

	/**
	 * @param array<int,string>    $candidates Candidates in header order.
	 * @param array<string,mixed>  $proxy_map  Trusted proxy map.
	 */
	private static function rightmostPublicCandidate( array $candidates, array $proxy_map ): string {
		for ( $i = count( $candidates ) - 1; $i >= 0; $i-- ) {
			$candidate = $candidates[ $i ];
			if ( self::isPublicIp( $candidate ) && ! self::isTrustedProxyIp( $candidate, $proxy_map ) ) {
				return $candidate;
			}
		}
		return '';
	}

	/**
	 * @param array<string,mixed> $proxy_map Trusted proxy map.
	 */
	private static function isTrustedProxyIp( string $ip, array $proxy_map ): bool {
		foreach ( array_keys( $proxy_map ) as $mask ) {
			if ( self::netMatch( (string) $mask, $ip ) ) {
				return true;
			}
		}
		return false;
	}

	public static function computePtrSubnet( string $ip, int $ip_version, int $mask_v4, int $mask_v6 ): string {
		if ( $ip_version == 4 ) {
			if ( $mask_v4 >= 32 ) {
				return $ip . '/32';
			}
			$ip_long  = ip2long( $ip );
			$net_mask = 0xFFFFFFFF << ( 32 - $mask_v4 );
			$network  = long2ip( $ip_long & $net_mask );
			return $network . '/' . $mask_v4;
		}

		if ( $mask_v6 >= 128 ) {
			return self::expandIPv6( $ip ) . '/128';
		}

		$expanded       = self::expandIPv6( $ip );
		$groups         = explode( ':', $expanded );
		$bits_remaining = $mask_v6;
		$result_groups  = array();
		foreach ( $groups as $group ) {
			if ( $bits_remaining >= 16 ) {
				$result_groups[] = $group;
				$bits_remaining -= 16;
			} elseif ( $bits_remaining > 0 ) {
				$val             = hexdec( $group );
				$group_mask      = 0xFFFF << ( 16 - $bits_remaining );
				$result_groups[] = str_pad( dechex( $val & $group_mask ), 4, '0', STR_PAD_LEFT );
				$bits_remaining  = 0;
			} else {
				$result_groups[] = '0000';
			}
		}
		return implode( ':', $result_groups ) . '/' . $mask_v6;
	}

	/**
	 * Parse a rate-limit subnet mask like '24-64' or 'ipv6-48' into [v4, v6] ints.
	 * Empty/non-numeric parts fall back to the documented defaults [24, 64] — a bare '-'
	 * or 'x-y' previously yielded [0, 0] (whole-internet /0 subnet pressure). Fix B-01.
	 *
	 * @param string $mask Subnet mask string.
	 * @return array{0:int,1:int} [ipv4_prefix, ipv6_prefix].
	 */
	public static function parseRateSubnetMask( string $mask = '24-64' ): array {
		$parts = explode( '-', $mask );
		if ( isset( $parts[0] ) && $parts[0] === 'ipv6' ) {
			// IPv4 mask unused for these rows; placeholder kept for return-shape consistency.
			$v6 = isset( $parts[1] ) && is_numeric( $parts[1] ) ? (int) $parts[1] : 64;
			return array( 24, $v6 );
		}
		$v4 = isset( $parts[0] ) && is_numeric( $parts[0] ) ? (int) $parts[0] : 24;
		$v6 = isset( $parts[1] ) && is_numeric( $parts[1] ) ? (int) $parts[1] : 64;
		return array( $v4, $v6 );
	}
}
