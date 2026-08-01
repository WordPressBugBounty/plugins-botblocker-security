<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerMuIP {

	private function read_ip_rules(): array {
		$res = array();
		if ( file_exists( $ip_file = $this->dirs['data'] . 'ip.php' ) ) {
			$ip_rule_list = bbcs_safe_load_data_file( $ip_file );
			if ( is_array( $ip_rule_list ) ) {
				$res = $ip_rule_list[ 'ipv' . $this->ip_version ] ?? array();
				unset( $ip_rule_list['ipv4'], $ip_rule_list['ipv6'] );
				foreach ( $ip_rule_list as $category => $rules ) {
					$res = array_merge( $res, $rules );
				}
			}
		}

		$hotBansFile = $this->dirs['data'] . 'hot-bans.php';
		if ( file_exists( $hotBansFile ) ) {
			$hotData = bbcs_safe_load_data_file( $hotBansFile );
			if ( is_array( $hotData ) ) {
				$now = time();
				$family = 'ipv' . $this->ip_version;
				$entries = isset( $hotData[ $family ] ) && is_array( $hotData[ $family ] ) ? $hotData[ $family ] : array();
				foreach ( $entries as $ip => $entry ) {
					if ( is_array( $entry ) && ! empty( $entry[1] ) && $entry[1] > $now ) {
						$res[ $ip ] = $entry[0];
					}
				}
			}
		}

		return $res;
	}

	private function is_ip_blocked( array $net, string $ip ): bool {
		$ip = trim( $ip );

		foreach ( $net as $rule => $action ) {
			$rule = trim( $rule );

			$is_match = false;

			if ( $ip === $rule ) {
				$is_match = true;
			} elseif ( strpos( $rule, '/' ) !== false ) {
				$is_match = $this->is_ip_in_cidr( $ip, $rule );
			} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) &&
					filter_var( $rule, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

					$ip_bin   = inet_pton( $ip );
					$rule_bin = inet_pton( $rule );
				if ( $ip_bin && $rule_bin && $ip_bin === $rule_bin ) {
					$is_match = true;
				}
			}

			if ( $is_match ) {
				if ( $action === BBCS_RULE_ALLOW ) {
					return false;
				} elseif ( $action === BBCS_RULE_BLOCK ) {
					return true;
				}
			}
		}

		return false;
	}

	private function is_ip_in_cidr( string $ip, string $cidr ): bool {
		$net_ex     = explode( '/', $cidr );
		$subnet     = trim( $net_ex[0] );
		$prefix_len = intval( $net_ex[1] );

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$range_start = ip2long( $subnet );
			$range_end   = $range_start + pow( 2, 32 - $prefix_len ) - 1;
			$ip_long     = ip2long( $ip );
			return ( $ip_long >= $range_start && $ip_long <= $range_end );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$ip_bin                            = inet_pton( $ip );
			list($first_addr_str, $prefix_len) = $net_ex;
			$first_addr_bin                    = inet_pton( $first_addr_str );
			$first_addr_hex                    = unpack( 'H*', $first_addr_bin );
			$first_addr_hex                    = reset( $first_addr_hex );
			$flex_bits                         = 128 - (int) $prefix_len;
			$last_addr_hex                     = $first_addr_hex;
			$n_pos                             = 31;
			while ( $flex_bits > 0 ) {
				$orig          = substr( $last_addr_hex, $n_pos, 1 );
				$orig_val      = hexdec( $orig );
				$new_val       = $orig_val | ( pow( 2, min( 4, $flex_bits ) ) - 1 );
				$new           = dechex( $new_val );
				$last_addr_hex = substr_replace( $last_addr_hex, $new, $n_pos, 1 );
				$flex_bits    -= 4;
				$n_pos        -= 1;
			}
			$last_addr_bin = pack( 'H*', $last_addr_hex );
			return ( $ip_bin >= $first_addr_bin && $ip_bin <= $last_addr_bin );
		}

		return false;
	}

	private function read_ip(): void {
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			$ip  = trim( wp_strip_all_tags( $raw ) );
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$this->ip         = $ip;
				$this->ip_version = '4';
			} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$this->ip         = $this->expand_ipv6( $ip );
				$this->ip_version = '6';
			} else {
				$this->increment_blocked_hit();
				die();
			}
		} else {
			$this->increment_blocked_hit();
			die();
		}
	}

	private function expand_ipv6( string $ip ): string {
		$hex = unpack( 'H*hex', inet_pton( $ip ) );
		$ip  = substr( preg_replace( '/([A-f0-9]{4})/', '$1:', $hex['hex'] ), 0, -1 );
		return $ip;
	}
}
