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
			set_transient( $key, array( 'count' => 1, 'since' => time() ), $window );
			return true;
		}

		if ( ! is_array( $data ) ) {
			$data = array( 'count' => (int) $data, 'since' => time() - $window );
		}

		if ( time() - $data['since'] >= $window ) {
			set_transient( $key, array( 'count' => 1, 'since' => time() ), $window );
			return true;
		}

		if ( $data['count'] >= $limit ) {
			return false;
		}

		set_transient( $key, array( 'count' => $data['count'] + 1, 'since' => $data['since'] ), $window );
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

		$value  = 0;
		$length = strlen( $data );

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
			if ( is_array( $cached_response ) && isset( $cached_response['ptr'] ) ) {
				return $cached_response['ptr'];
			}
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// PTR is being cached in DB only if setting is enabled.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$get_ptr = $wpdb->get_row( $wpdb->prepare( "SELECT ptr, date FROM `{$wpdb->bbcs_ptrcache}` WHERE ip = %s", $ip ), ARRAY_A );

		if ( isset( $get_ptr['ptr'] ) && $BBCS->settings->ptr_cache_in_db ) {
			if ( $storage !== null ) {
				$storage->set( $cache_key, array( 'ptr' => $get_ptr['ptr'] ), $ttl * 60 );
			}
			return $get_ptr['ptr'];
		} else {
			$ptr = trim( preg_replace( '/[^0-9a-z-.:]/', '', strtolower( gethostbyaddr( $ip ) ) ) );
			if ( $BBCS->settings->ptr_cache_in_db ) {
				$time = $time + ( $ttl * 60 );
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->bbcs_ptrcache}` (ip, ptr, date, etime) VALUES (%s, %s, %d, %s)
				ON DUPLICATE KEY UPDATE ptr = VALUES(ptr), date = VALUES(date), etime = VALUES(etime)",
						$ip, $ptr, $time, $ttl
					)
				);
			}
			if ( $storage !== null && $BBCS->settings->ptr_cache_in_db ) {
				$storage->set( $cache_key, array( 'ptr' => $ptr ), $ttl * 60 );
			}
			return $ptr;
		}
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

		$expanded = self::expandIPv6( $ip );
		$groups   = explode( ':', $expanded );
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
}
