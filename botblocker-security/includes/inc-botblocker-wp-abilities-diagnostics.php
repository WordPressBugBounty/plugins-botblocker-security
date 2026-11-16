<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_get_wp_ability_diagnostics_definitions' ) ) {

	function bbcs_ability_diag_settings() {
		$bbcs = BotBlocker::getInstance();
		return is_object( $bbcs->settings ) ? $bbcs->settings : null;
	}

	function bbcs_ability_diag_int_setting( string $key, int $default ): int {
		$settings = bbcs_ability_diag_settings();
		if ( ! $settings || ! isset( $settings->$key ) || ! is_numeric( $settings->$key ) ) {
			return $default;
		}
		return (int) $settings->$key;
	}

	function bbcs_ability_diag_float_setting( string $key, float $default ): float {
		$settings = bbcs_ability_diag_settings();
		if ( ! $settings || ! isset( $settings->$key ) || ! is_numeric( $settings->$key ) ) {
			return $default;
		}
		return (float) $settings->$key;
	}

	function bbcs_ability_diag_string_setting( string $key, string $default = '' ): string {
		$settings = bbcs_ability_diag_settings();
		if ( ! $settings || ! isset( $settings->$key ) || ! is_scalar( $settings->$key ) ) {
			return $default;
		}
		return (string) $settings->$key;
	}

	function bbcs_ability_diag_window(): int {
		$window = bbcs_ability_diag_int_setting( 'bbcs_rate_window_minutes', 5 );
		return max( 1, $window );
	}

	function bbcs_ability_diag_bucket( string $prefix, string $value, int $window ): array {
		$key    = $prefix . md5( $value );
		$bucket = class_exists( 'BotBlockerCache' ) ? BotBlockerCache::fileGet( $key ) : null;
		$now_m  = (int) floor( time() / 60 );
		$total  = 0;
		$list   = array();

		if ( is_array( $bucket ) && isset( $bucket['b'] ) && is_array( $bucket['b'] ) ) {
			$cutoff = $now_m - $window + 1;
			foreach ( $bucket['b'] as $minute => $count ) {
				if ( (int) $minute < $cutoff ) {
					continue;
				}
				$total += (int) $count;
				$list[] = array(
					'minute' => (int) $minute,
					'count'  => (int) $count,
				);
			}
			usort(
				$list,
				static function ( $a, $b ) {
					return $a['minute'] <=> $b['minute'];
				}
			);
		}

		return array(
			'key'     => $key,
			'total'   => $total,
			'rpm'     => round( $total / $window, 4 ),
			'buckets' => $list,
		);
	}

	function bbcs_ability_diag_subnet( string $ip ): string {
		if ( ! class_exists( 'BotBlockerIp' ) ) {
			return '';
		}
		$version = BotBlockerIp::getVersion( $ip );
		if ( 4 !== $version && 6 !== $version ) {
			return '';
		}
		$mask      = BotBlockerIp::parseRateSubnetMask( bbcs_ability_diag_string_setting( 'bbcs_rate_subnet_mask', '24-64' ) );
		$subnet    = BotBlockerIp::computePtrSubnet( $ip, $version, (int) $mask[0], (int) $mask[1] );
		return is_string( $subnet ) ? $subnet : '';
	}

	function bbcs_ability_diag_rate_state( string $ip ): array {
		$window    = bbcs_ability_diag_window();
		$captcha   = bbcs_ability_diag_int_setting( 'bbcs_rate_captcha_rpm', 30 );
		$block     = bbcs_ability_diag_int_setting( 'bbcs_rate_block_rpm', 50 );
		$individual = bbcs_ability_diag_bucket( 'bbcs_fr_', $ip, $window );

		$subnet_enabled = bbcs_ability_diag_int_setting( 'bbcs_rate_subnet_enabled', 0 ) === 1;
		$subnet         = $subnet_enabled ? bbcs_ability_diag_subnet( $ip ) : '';
		$subnet_bucket  = bbcs_ability_diag_bucket( 'bbcs_fr_subnet_', $subnet, $window );
		$multiplier     = bbcs_ability_diag_float_setting( 'bbcs_rate_subnet_multiplier', 3.0 );
		$floor_percent  = bbcs_ability_diag_float_setting( 'bbcs_rate_floor_percent', 0.1 );
		$pressure       = 0.0;
		$effective_block   = (float) $block;
		$effective_captcha = (float) $captcha;

		if ( $subnet_enabled ) {
			$threshold = (float) $block * max( 0.0, $multiplier );
			$floor_rpm = (float) $block * max( 0.0, $floor_percent );
			if ( $threshold > 0 ) {
				$pressure          = min( 1.0, ( $subnet_bucket['rpm'] / $threshold ) ** 2 );
				$effective_block   = max( $floor_rpm, (float) $block * ( 1.0 - $pressure ) );
				$effective_captcha = max( $floor_rpm * 0.5, (float) $captcha * ( 1.0 - $pressure ) );
			}
		}

		$ban = bbcs_ability_diag_rate_ban( $ip );

		return array(
			'bucket'                => $individual['key'],
			'rpm'                   => $individual['rpm'],
			'total_hits'            => $individual['total'],
			'window_minutes'        => $window,
			'counters'              => $individual['buckets'],
			'effective_block_rpm'   => round( $effective_block, 4 ),
			'effective_captcha_rpm' => round( $effective_captcha, 4 ),
			'check_enabled'         => bbcs_ability_diag_int_setting( 'bbcs_rate_check_enabled', 0 ) === 1,
			'subnet'                => array(
				'enabled'  => $subnet_enabled,
				'cidr'     => $subnet,
				'bucket'   => $subnet_bucket['key'],
				'rpm'      => $subnet_bucket['rpm'],
				'pressure' => round( $pressure, 6 ),
				'counters' => $subnet_bucket['buckets'],
			),
			'ban'                   => $ban,
		);
	}

	function bbcs_ability_diag_rate_ban( string $ip ): array {
		global $wpdb;

		$version = BotBlockerIp::getVersion( $ip );
		$table   = 6 === $version ? ( $wpdb->bbcs_ipv6rules ?? '' ) : ( $wpdb->bbcs_ipv4rules ?? '' );
		if ( ! is_string( $table ) || '' === $table ) {
			return array( 'active' => false, 'expires' => 0, 'source' => '' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT `expires` FROM `{$table}` WHERE `search` = %s AND `rule` = %s AND `comment` = %s ORDER BY `expires` DESC LIMIT 1", $ip, BBCS_RULE_BLOCK, 'Rate Limit Block' ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return array( 'active' => false, 'expires' => 0, 'source' => '' );
		}
		$expires = (int) $row['expires'];
		return array(
			'active'  => $expires > time(),
			'expires' => $expires,
			'source'  => 'rate-limit-block',
		);
	}

	function bbcs_ability_diag_ua_family( string $ua ): string {
		$ua = strtolower( $ua );
		if ( '' === $ua ) {
			return 'unknown';
		}
		if ( false !== strpos( $ua, 'python' ) || false !== strpos( $ua, 'curl' ) || false !== strpos( $ua, 'wget' ) || false !== strpos( $ua, 'httpx' ) || false !== strpos( $ua, 'go-http-client' ) ) {
			return 'automation';
		}
		if ( false !== strpos( $ua, 'headless' ) || false !== strpos( $ua, 'phantomjs' ) || false !== strpos( $ua, 'playwright' ) || false !== strpos( $ua, 'puppeteer' ) ) {
			return 'headless';
		}
		if ( false !== strpos( $ua, 'edg' ) ) {
			return 'edge';
		}
		if ( false !== strpos( $ua, 'opr' ) || false !== strpos( $ua, 'opera' ) ) {
			return 'opera';
		}
		if ( false !== strpos( $ua, 'chrome' ) ) {
			return 'chrome';
		}
		if ( false !== strpos( $ua, 'firefox' ) ) {
			return 'firefox';
		}
		if ( false !== strpos( $ua, 'safari' ) ) {
			return 'safari';
		}
		if ( false !== strpos( $ua, 'bot' ) || false !== strpos( $ua, 'crawl' ) || false !== strpos( $ua, 'spider' ) ) {
			return 'bot';
		}
		return 'unknown';
	}

	function bbcs_ability_diag_bot_lists(): array {
		$signatures = array( 'substrings' => array(), 'patterns' => array() );
		$engines    = array( 'rules' => array(), 'domains' => array() );

		if ( class_exists( 'BotBlockerDataFile' ) && class_exists( 'BotBlockerMultisite' ) ) {
			$dir = BotBlockerMultisite::getDataDir();

			$file = $dir . 'bot-signatures-processed.php';
			if ( file_exists( $file ) ) {
				$data = BotBlockerDataFile::safeLoad( $file );
				if ( is_array( $data ) ) {
					$signatures['substrings'] = isset( $data['substrings'] ) && is_array( $data['substrings'] ) ? array_values( array_map( 'strval', $data['substrings'] ) ) : array();
					$signatures['patterns']   = isset( $data['patterns'] ) && is_array( $data['patterns'] ) ? array_values( array_map( 'strval', $data['patterns'] ) ) : array();
				}
			}

			$file = $dir . 'search_engines.php';
			if ( file_exists( $file ) ) {
				$data = BotBlockerDataFile::safeLoad( $file );
				if ( is_array( $data ) ) {
					$engines['rules']   = isset( $data['bbcs_rule'] ) && is_array( $data['bbcs_rule'] ) ? $data['bbcs_rule'] : array();
					$engines['domains'] = isset( $data['bbcs_se'] ) && is_array( $data['bbcs_se'] ) ? $data['bbcs_se'] : array();
				}
			}
		}

		return array( 'signatures' => $signatures, 'engines' => $engines );
	}

	function bbcs_ability_diag_bot_match( string $ua ): array {
		$lists    = bbcs_ability_diag_bot_lists();
		$variants = class_exists( 'BotBlockerCheck' ) ? BotBlockerCheck::userAgentVariants( $ua ) : array( $ua );

		foreach ( $lists['engines']['rules'] as $token => $rule ) {
			$token = (string) $token;
			if ( '' === $token ) {
				continue;
			}
			$engine_hit = false;
			foreach ( $variants as $variant ) {
				if ( false !== stripos( $variant, $token ) ) {
					$engine_hit = true;
					break;
				}
			}
			if ( ! $engine_hit ) {
				continue;
			}
			$verification = isset( $lists['engines']['domains'][ $token ] ) && is_array( $lists['engines']['domains'][ $token ] )
				? array_values( array_map( 'strval', $lists['engines']['domains'][ $token ] ) )
				: array();
			$ptr   = array();
			$asn   = array();
			$ips   = array();
			foreach ( $verification as $entry ) {
				if ( 0 === strncmp( $entry, 'asn:', 4 ) ) {
					$asn[] = $entry;
				} elseif ( 0 === strncmp( $entry, 'ip:', 3 ) ) {
					$ips[] = $entry;
				} else {
					$ptr[] = $entry;
				}
			}
			$rule = (string) $rule;
			return array(
				'matched_signature' => $token,
				'matched_pattern'   => $token,
				'source'            => 'search_engine',
				'list_type'         => 'allowlist',
				'action'            => $rule,
				'verified_by'       => array(
					'ptr' => ! empty( $ptr ),
					'asn' => ! empty( $asn ),
					'ip'  => ! empty( $ips ),
				),
				'verdict'           => BBCS_RULE_BLOCK === $rule ? 'deny' : ( BBCS_RULE_ALLOW === $rule ? 'allow' : 'listed' ),
			);
		}

		foreach ( $lists['signatures']['substrings'] as $signature ) {
			if ( '' === $signature ) {
				continue;
			}
			foreach ( $variants as $variant ) {
				if ( false === stripos( $variant, $signature ) ) {
					continue;
				}
				return array(
					'matched_signature' => $signature,
					'matched_pattern'   => $signature,
					'source'            => 'bot_signature',
					'list_type'         => 'blocklist',
					'action'            => BBCS_RULE_BLOCK,
					'verified_by'       => array( 'ptr' => false, 'asn' => false, 'ip' => false ),
					'verdict'           => 'deny',
				);
			}
		}

		foreach ( $lists['signatures']['patterns'] as $pattern ) {
			if ( '' === $pattern ) {
				continue;
			}
			$regex = '#' . str_replace( '#', '\\#', $pattern ) . '#i';
			foreach ( $variants as $variant ) {
				$match = @preg_match( $regex, $variant );
				if ( is_int( $match ) && 1 === $match ) {
					return array(
						'matched_signature' => $pattern,
						'matched_pattern'   => $pattern,
						'source'            => 'bot_signature',
						'list_type'         => 'blocklist',
						'action'            => BBCS_RULE_BLOCK,
						'verified_by'       => array( 'ptr' => false, 'asn' => false, 'ip' => false ),
						'verdict'           => 'deny',
					);
				}
			}
		}

		if ( class_exists( 'BotBlockerCheck' ) && BotBlockerCheck::userAgentHasNonAscii( $ua ) ) {
			return array(
				'matched_signature' => 'non-ascii-user-agent',
				'matched_pattern'   => 'non-ascii-user-agent',
				'source'            => 'bot_signature',
				'list_type'         => 'blocklist',
				'action'            => BBCS_RULE_BLOCK,
				'verified_by'       => array( 'ptr' => false, 'asn' => false, 'ip' => false ),
				'verdict'           => 'deny',
			);
		}

		return array(
			'matched_signature' => '',
			'matched_pattern'   => '',
			'source'            => 'none',
			'list_type'         => 'none',
			'action'            => '',
			'verified_by'       => array( 'ptr' => false, 'asn' => false, 'ip' => false ),
			'verdict'           => 'none',
		);
	}

	function bbcs_ability_diag_tls_match( string $ja3, string $ja4, string $ua ): array {
		$fingerprint = '' !== $ja4 ? $ja4 : $ja3;
		$fingerprint = strtolower( trim( $fingerprint ) );
		$fingerprint = (string) preg_replace( '/[^a-z0-9,._-]/', '', $fingerprint );
		$ua_family   = bbcs_ability_diag_ua_family( $ua );

		$category = 'unknown';
		$expected = '';
		$source   = 'unavailable';

		if ( '' !== $fingerprint && class_exists( 'BotBlockerDataFile' ) && class_exists( 'BotBlockerMultisite' ) ) {
			$file = BotBlockerMultisite::getDataDir() . 'tls_fingerprints.php';
			if ( file_exists( $file ) ) {
				$data  = BotBlockerDataFile::safeLoad( $file );
				$items = is_array( $data ) && isset( $data['bbcs_tls_fingerprints'] ) && is_array( $data['bbcs_tls_fingerprints'] ) ? $data['bbcs_tls_fingerprints'] : array();
				if ( isset( $items[ $fingerprint ] ) && is_array( $items[ $fingerprint ] ) ) {
					$category = isset( $items[ $fingerprint ]['category'] ) ? (string) $items[ $fingerprint ]['category'] : 'unknown';
					$expected = isset( $items[ $fingerprint ]['ua_family'] ) ? (string) $items[ $fingerprint ]['ua_family'] : '';
					$source   = 'tls_fingerprints';
				} else {
					$source = 'not_listed';
				}
			}
		}

		$match = null;
		if ( '' !== $expected ) {
			$match = strtolower( $expected ) === strtolower( $ua_family );
		}

		return array(
			'fingerprint'     => $fingerprint,
			'category'        => $category,
			'ua_family'       => $ua_family,
			'expected_family' => $expected,
			'match'           => $match,
			'source'          => $source,
		);
	}

	function bbcs_ability_diag_geo( string $ip ): array {
		$lookup = class_exists( 'BotBlockerAsnDb' ) ? BotBlockerAsnDb::lookup( $ip ) : null;
		if ( ! is_array( $lookup ) ) {
			return array(
				'country'      => '',
				'country_name' => '',
				'continent'    => '',
				'asn'          => 0,
				'organization' => '',
				'cidr'         => '',
				'hosting'      => false,
				'vpn'          => false,
				'tor'          => false,
				'source'       => 'local_asn_db',
				'available'    => false,
			);
		}
		return array(
			'country'      => (string) ( $lookup['country'] ?? '' ),
			'country_name' => (string) ( $lookup['country_name'] ?? '' ),
			'continent'    => (string) ( $lookup['continent'] ?? '' ),
			'asn'          => (int) preg_replace( '/[^0-9]/', '', (string) ( $lookup['asn'] ?? '0' ) ),
			'organization' => (string) ( $lookup['as_name'] ?? '' ),
			'cidr'         => (string) ( $lookup['cidr'] ?? '' ),
			'hosting'      => false,
			'vpn'          => false,
			'tor'          => false,
			'source'       => 'local_asn_db',
			'available'    => true,
		);
	}

	function bbcs_ability_diag_terms( array $input ): array {
		$ua     = bbcs_ability_input_string( $input, 'user_agent' );
		$terms  = array();
		if ( '' !== $ua ) {
			$terms[] = 'useragent=' . $ua;
		}
		$country = strtoupper( bbcs_ability_input_string( $input, 'country' ) );
		if ( '' !== $country ) {
			$terms[] = 'country=' . $country;
		}
		$lang = bbcs_ability_input_string( $input, 'accept_language' );
		if ( '' !== $lang ) {
			$terms[] = 'lang=' . $lang;
		}
		$referer = bbcs_ability_input_string( $input, 'referer' );
		if ( '' !== $referer ) {
			$host = wp_parse_url( $referer, PHP_URL_HOST );
			if ( is_string( $host ) && '' !== $host ) {
				$terms[] = 'referer=' . $host;
			}
		}
		$path = bbcs_ability_input_string( $input, 'path' );
		if ( '' !== $path ) {
			$terms[] = 'page=' . $path;
			$terms[] = 'uri=' . $path;
		}
		$ip = bbcs_ability_input_string( $input, 'ip' );
		if ( '' !== $ip ) {
			$geo = bbcs_ability_diag_geo( $ip );
			if ( $geo['asn'] > 0 ) {
				$terms[] = 'asnum=' . $geo['asn'];
			}
		}
		return $terms;
	}

	function bbcs_ability_diag_custom_rules( array $terms ): array {
		global $wpdb;

		$table = $wpdb->bbcs_rules ?? '';
		if ( ! is_string( $table ) || '' === $table || empty( $terms ) ) {
			return array();
		}

		$clauses = array();
		foreach ( $terms as $term ) {
			$clauses[] = $wpdb->prepare( '`search` LIKE %s', '%' . $wpdb->esc_like( $term ) . '%' );
		}

		$sql = "SELECT `id`, `type`, `data`, `search`, `rule`, `priority`, `comment`, `expires` FROM `{$table}` WHERE `disable` = 0 AND ( `expires` = 0 OR `expires` > %d ) AND ( " . implode( ' OR ', $clauses ) . ' ) ORDER BY `priority` ASC, `id` ASC LIMIT 50';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, time() ), ARRAY_A );

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				'id'       => (int) $row['id'],
				'matcher'  => (string) $row['type'],
				'value'    => (string) $row['search'],
				'rule'     => (string) $row['rule'],
				'priority' => (int) $row['priority'],
				'comment'  => (string) $row['comment'],
				'expires'  => (int) $row['expires'],
			);
		}
		return $out;
	}

	function bbcs_ability_diag_pass( string $cookie_value, string $ip, string $uid ): array {
		$out = array(
			'valid'      => false,
			'expired'    => false,
			'expires_in' => 0,
			'expires'    => 0,
			'format'     => 'unknown',
			'bound'      => array( 'ip' => false, 'ua' => false, 'session' => false ),
			'reason'     => '',
		);

		if ( '' !== $cookie_value && preg_match( '/^(\d{1,12})\.([a-f0-9]{64})$/', $cookie_value, $m ) ) {
			$out['format']  = 'cache-pass';
			$out['expires'] = (int) $m[1];
			$out['expires_in'] = max( 0, (int) $m[1] - time() );
			$out['expired'] = (int) $m[1] <= time();
		} else {
			$out['reason'] = 'format';
			return $out;
		}

		$salt = bbcs_ability_diag_string_setting( 'salt' );
		if ( '' === $salt ) {
			$out['reason'] = 'salt_missing';
			return $out;
		}
		if ( '' === $ip || '' === $uid ) {
			$out['reason'] = 'bindings_missing';
			return $out;
		}

		$uid = (string) preg_replace( '/[^a-zA-Z0-9]/', '', $uid );
		if ( '' === $uid || strlen( $uid ) > 64 ) {
			$out['reason'] = 'uid_invalid';
			return $out;
		}

		$ttl_max = defined( 'BotBlockerBase::CACHE_PASS_TTL_MAX' ) ? constant( 'BotBlockerBase::CACHE_PASS_TTL_MAX' ) : 0;
		if ( $out['expired'] ) {
			$out['reason'] = 'expired';
			return $out;
		}
		if ( $ttl_max > 0 && (int) $m[1] > time() + (int) $ttl_max + 60 ) {
			$out['reason'] = 'ttl';
			return $out;
		}

		$prefix = defined( 'BotBlockerBase::CACHE_PASS_SIGN_PREFIX' ) ? (string) constant( 'BotBlockerBase::CACHE_PASS_SIGN_PREFIX' ) : 'cache-pass|';
		$expected = hash_hmac( 'sha256', $prefix . $uid . '|' . $ip . '|' . (int) $m[1], $salt );
		$out['valid'] = hash_equals( $expected, (string) $m[2] );
		$out['bound'] = array( 'ip' => true, 'ua' => false, 'session' => true );
		$out['reason'] = $out['valid'] ? 'ok' : 'signature';
		return $out;
	}

	function bbcs_ability_diag_layer_state(): array {
		$settings = bbcs_ability_diag_settings();
		$early    = $settings && ! empty( $settings->early_init_enable );
		$mu       = $settings && ! empty( $settings->mu_enable );

		$wp_config      = defined( 'ABSPATH' ) ? ABSPATH . 'wp-config.php' : '';
		$wp_config_hash = '';
		$router         = false;
		if ( '' !== $wp_config && is_readable( $wp_config ) ) {
			$contents       = (string) @file_get_contents( $wp_config );
			$router         = false !== strpos( $contents, 'bbcs-early-router.php' );
			$wp_config_hash = hash( 'sha256', $contents );
		}

		$mu_file = defined( 'WPMU_PLUGIN_DIR' ) ? trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php' : '';
		$mu_hash = '' !== $mu_file && is_readable( $mu_file ) ? (string) @hash_file( 'sha256', $mu_file ) : '';

		$router_file = '';
		$router_hash = '';
		if ( class_exists( 'BotBlockerMultisite' ) ) {
			$candidate = BotBlockerMultisite::getDataDir() . 'bbcs-early-router.php';
			if ( is_readable( $candidate ) ) {
				$router_file = $candidate;
				$router_hash = (string) @hash_file( 'sha256', $candidate );
			}
		}

		$conflicts = array();
		if ( $early && $mu ) {
			$conflicts[] = 'early_init_and_mu_both_enabled';
		}
		if ( $early && ! $router ) {
			$conflicts[] = 'early_init_wpconfig_router_missing';
		}
		if ( $mu && '' === $mu_hash ) {
			$conflicts[] = 'mu_plugin_file_missing';
		}

		$auto_enabled = $settings && ! empty( $settings->bbcs_edge_autotrust );
		$auto_entries = class_exists( 'BotBlockerEdgeStats' ) ? BotBlockerEdgeStats::autoTrustEntries() : array();
		$auto_count   = 0;
		$auto_expiry  = 0;
		if ( $auto_enabled ) {
			foreach ( $auto_entries as $entry ) {
				$expires = (int) ( is_array( $entry ) ? ( $entry['expires'] ?? 0 ) : 0 );
				if ( $expires > 0 && $expires < time() ) {
					continue;
				}
				++$auto_count;
				$auto_expiry = $auto_expiry === 0 ? $expires : min( $auto_expiry, $expires );
			}
		}

		return array(
			'early_init' => array(
				'active'      => (bool) $early,
				'file'        => $router_file,
				'hash'        => $router_hash,
				'wpconfig'    => array( 'path' => $wp_config, 'router_present' => $router, 'hash' => $wp_config_hash ),
			),
			'mu'         => array(
				'active' => (bool) $mu,
				'file'   => $mu_file,
				'hash'   => $mu_hash,
			),
			'main'       => array(
				'active'     => class_exists( 'BotBlocker' ),
				'version'    => defined( 'BOTBLOCKER_VERSION' ) ? (string) BOTBLOCKER_VERSION : '',
				'protection' => bbcs_ability_diag_int_setting( 'disable', 0 ) !== 1,
			),
			'gate'       => array(
				'cache_enabled' => defined( 'BBCS_EARLY_CACHE_GATE' ) && 'open' !== BBCS_EARLY_CACHE_GATE,
				'geo_enabled'   => bbcs_ability_diag_int_setting( 'early_geo_enable', 0 ) === 1 || bbcs_ability_diag_int_setting( 'mu_geo_enable', 0 ) === 1,
			),
			'autotrust'  => array(
				'enabled'     => (bool) $auto_enabled,
				'entries'     => $auto_count,
				'next_expiry' => bbcs_ability_iso_time( $auto_expiry ),
			),
			'conflicts'  => $conflicts,
			'versions'   => array(
				'php'  => PHP_VERSION,
				'wp'   => (string) get_bloginfo( 'version' ),
				'core' => defined( 'BOTBLOCKER_VERSION' ) ? (string) BOTBLOCKER_VERSION : '',
			),
		);
	}

	function bbcs_ability_diag_data_files(): array {
		$known = array(
			'settings.php',
			'search_engines.php',
			'rules.php',
			'paths.php',
			'proxy.php',
			'bot-networks.php',
			'auto-edges.php',
			'ip.php',
			'hot-bans.php',
			'asn_rules.php',
			'geo_countries.php',
			'tls_fingerprints.php',
			'llm_trusted.php',
			'bot-signatures-processed.php',
			'bbcs-early-router.php',
		);

		$settings = BotBlocker::getInstance()->settings;
		$early_on = is_object( $settings ) && ! empty( $settings->early_init_enable );
		$auto_on  = is_object( $settings ) && ! empty( $settings->bbcs_edge_autotrust );
		$optional = array( 'bbcs-early-router.php' => ! $early_on, 'auto-edges.php' => ! $auto_on );
		$dir = class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getDataDir() : '';
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return array( 'dir' => $dir, 'files' => array(), 'missing' => $known, 'extra' => array(), 'last_rebuild' => '' );
		}

		$files   = array();
		$missing = array();
		$last    = 0;
		foreach ( $known as $name ) {
			$path = $dir . $name;
			if ( ! is_file( $path ) || ! is_readable( $path ) ) {
				if ( empty( $optional[ $name ] ) ) {
					$missing[] = $name;
				}
				continue;
			}
			$mtime = (int) @filemtime( $path );
			$last  = max( $last, $mtime );
			$files[] = array(
				'name'  => $name,
				'size'  => (int) @filesize( $path ),
				'sha256'=> (string) @hash_file( 'sha256', $path ),
				'mtime' => bbcs_ability_iso_time( $mtime ),
				'ok'    => true,
			);
		}

		$extra = array();
		$scan  = @scandir( $dir );
		if ( is_array( $scan ) ) {
			foreach ( $scan as $entry ) {
				if ( ! is_string( $entry ) || '.' === $entry || '..' === $entry ) {
					continue;
				}
				$path = $dir . $entry;
				if ( is_file( $path ) && false === strpos( $entry, 'index.php' ) && ! in_array( $entry, $known, true ) ) {
					$extra[] = $entry;
				}
			}
		}
		sort( $extra );

		return array(
			'dir'          => $dir,
			'files'        => $files,
			'missing'      => $missing,
			'extra'        => $extra,
			'last_rebuild' => bbcs_ability_iso_time( $last ),
		);
	}

	function bbcs_ability_diag_rule_matches( array $input ): array {
		$ip      = bbcs_ability_input_string( $input, 'ip' );
		$matches = array();

		if ( '' !== $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			foreach ( bbcs_wp_ability_lookup_ip_rules( $ip ) as $rule ) {
				$matches[] = array(
					'id'       => (int) $rule['id'],
					'source'   => 'ip',
					'matcher'  => 'ip',
					'family'   => (string) $rule['family'],
					'rule'     => (string) $rule['rule'],
					'value'    => (string) $rule['value'],
					'priority' => (int) $rule['priority'],
					'action'   => (string) $rule['rule'],
					'expired'  => (bool) $rule['expired'],
				);
			}
		}

		foreach ( bbcs_ability_diag_custom_rules( bbcs_ability_diag_terms( $input ) ) as $rule ) {
			$matches[] = array(
				'id'       => (int) $rule['id'],
				'source'   => 'custom',
				'matcher'  => (string) $rule['matcher'],
				'family'   => '',
				'rule'     => (string) $rule['rule'],
				'value'    => (string) $rule['value'],
				'priority' => (int) $rule['priority'],
				'action'   => (string) $rule['rule'],
				'expired'  => false,
			);
		}

		usort(
			$matches,
			static function ( $a, $b ) {
				if ( $a['priority'] === $b['priority'] ) {
					return $a['id'] <=> $b['id'];
				}
				return $a['priority'] <=> $b['priority'];
			}
		);

		$winner = null;
		foreach ( $matches as $match ) {
			if ( empty( $match['expired'] ) ) {
				$winner = $match;
				break;
			}
		}

		return array( 'matches' => $matches, 'winner' => $winner );
	}

	function bbcs_ability_diag_decision( array $input ): array {
		$ua = bbcs_ability_input_string( $input, 'user_agent' );
		$ip = bbcs_ability_input_string( $input, 'ip' );

		$result = array(
			'decision'     => 'allow',
			'reason_code'  => 'no_match',
			'rule_id'      => 0,
			'rule_type'    => '',
			'priority'     => 0,
			'bot_signal'   => 'none',
			'tls_category' => 'unknown',
			'geo'          => '',
			'asn'          => 0,
			'proxy_type'   => 'unknown',
			'rate'         => array(),
			'pass'         => array(),
			'layer'        => 'none',
			'matched'      => array(),
			'notes'        => array(),
			'ptr_checked'  => false,
		);

		if ( '' === $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$result['notes'][] = 'ip_missing';
			return $result;
		}

		$geo             = bbcs_ability_diag_geo( $ip );
		$result['geo']   = $geo['country'];
		$result['asn']   = $geo['asn'];
		$result['rate']  = bbcs_ability_diag_rate_state( $ip );
		$result['pass']  = bbcs_ability_diag_pass(
			bbcs_ability_input_string( $input, 'pass_cookie' ),
			$ip,
			bbcs_ability_input_string( $input, 'uid' )
		);

		$hot_ban = bbcs_wp_ability_active_hot_ban( $ip );
		if ( ! empty( $hot_ban ) ) {
			$result['decision']    = BBCS_RULE_DARK === $hot_ban['rule'] ? 'dark' : 'block';
			$result['reason_code'] = 'hot_ban';
			$result['rule_type']   = 'none';
			$result['layer']       = 'hot-ban';
			return $result;
		}

		$rule_set = bbcs_ability_diag_rule_matches( $input );
		if ( ! empty( $rule_set['matches'] ) ) {
			$result['matched'] = $rule_set['matches'];
		}
		$winner = $rule_set['winner'];
		if ( ! empty( $winner ) ) {
			$map = array(
				BBCS_RULE_ALLOW => 'allow',
				BBCS_RULE_BLOCK => 'block',
				BBCS_RULE_DARK  => 'dark',
				BBCS_RULE_GRAY  => 'gray',
			);
			$action = (string) $winner['action'];
			$source = (string) ( $winner['source'] ?? '' );
			if ( isset( $map[ $action ] ) ) {
				$result['decision']    = $map[ $action ];
				$result['reason_code'] = 'ip' === $source ? 'ip_rule' : 'custom_rule';
				$result['rule_id']     = (int) $winner['id'];
				$result['rule_type']   = $source;
				$result['priority']    = (int) $winner['priority'];
				$result['layer']       = 'ip' === $source ? 'ip-rule' : 'custom-rule';
				return $result;
			}
		}

		$bot = bbcs_ability_diag_bot_match( $ua );
		$result['bot_signal'] = $bot['verdict'];
		if ( 'deny' === $bot['verdict'] || 'listed' === $bot['verdict'] ) {
			$result['decision']    = 'deny' === $bot['verdict'] ? 'block' : 'gray';
			$result['reason_code'] = 'bot_signature';
			$result['rule_type']   = (string) $bot['list_type'];
			$result['layer']       = 'bot-signature';
			return $result;
		}
		if ( 'allow' === $bot['verdict'] ) {
			$result['decision']    = 'allow';
			$result['reason_code'] = 'bot_allowlist';
			$result['rule_type']   = 'allowlist';
			$result['layer']       = 'bot-signature';

			$required = array();
			if ( ! empty( $bot['verified_by']['ptr'] ) ) {
				$required[] = __( 'reverse DNS', 'botblocker-security' );
			}
			if ( ! empty( $bot['verified_by']['asn'] ) ) {
				$required[] = __( 'ASN', 'botblocker-security' );
			}
			if ( ! empty( $bot['verified_by']['ip'] ) ) {
				$required[] = __( 'IP range', 'botblocker-security' );
			}
			$result['notes'][] = $required
				/* translators: %s: comma-separated verification methods the trusted-bot row requires. */
				? sprintf( __( 'Matched by user agent only: the row requires %s, none of it is checked here, so the live firewall may still deny this request.', 'botblocker-security' ), implode( ', ', $required ) )
				: __( 'Matched by user agent only: reverse DNS is not checked here, so the live firewall may still deny this request.', 'botblocker-security' );
			return $result;
		}

		$tls = bbcs_ability_diag_tls_match(
			bbcs_ability_input_string( $input, 'ja3' ),
			bbcs_ability_input_string( $input, 'ja4' ),
			$ua
		);
		$result['tls_category'] = $tls['category'];
		if ( 'malicious' === $tls['category'] ) {
			$result['decision']    = 'dark';
			$result['reason_code'] = 'tls_fingerprint';
			$result['rule_type']   = 'tls';
			$result['layer']       = 'tls';
			return $result;
		}

		if ( ! empty( $result['rate']['check_enabled'] ) ) {
			if ( $result['rate']['rpm'] >= $result['rate']['effective_block_rpm'] ) {
				$result['decision']    = 'block';
				$result['reason_code'] = 'rate_limit_block';
				$result['rule_type']   = 'rate';
				$result['layer']       = 'rate-limit';
				return $result;
			}
			if ( $result['rate']['rpm'] >= $result['rate']['effective_captcha_rpm'] ) {
				$result['decision']    = 'challenge';
				$result['reason_code'] = 'rate_limit_captcha';
				$result['rule_type']   = 'rate';
				$result['layer']       = 'rate-limit';
				return $result;
			}
		}

		if ( ! empty( $result['pass']['valid'] ) ) {
			$result['decision']    = 'allow';
			$result['reason_code'] = 'pass_valid';
			$result['rule_type']   = 'pass';
			$result['layer']       = 'cache-pass';
			return $result;
		}

		return $result;
	}

	function bbcs_wp_ability_execute_simulate_request( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/simulate-request', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! is_array( $input ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'An input object is required.', 'botblocker-security' ) );
		}
		return bbcs_ability_diag_decision( $input );
	}

	function bbcs_wp_ability_execute_get_rule_match( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-rule-match', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! is_array( $input ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'An input object is required.', 'botblocker-security' ) );
		}
		$has_term = '' !== bbcs_ability_input_string( $input, 'ip' )
			|| '' !== bbcs_ability_input_string( $input, 'path' )
			|| '' !== bbcs_ability_input_string( $input, 'user_agent' )
			|| '' !== bbcs_ability_input_string( $input, 'referer' )
			|| '' !== bbcs_ability_input_string( $input, 'accept_language' )
			|| '' !== bbcs_ability_input_string( $input, 'country' );
		if ( ! $has_term ) {
			return new WP_Error( 'ability_invalid_input', __( 'Provide at least one match term: ip, path, user_agent, referer, accept_language or country.', 'botblocker-security' ) );
		}
		$set = bbcs_ability_diag_rule_matches( $input );
		return array(
			'rules'  => $set['matches'],
			'winner' => $set['winner'],
		);
	}

	function bbcs_wp_ability_execute_get_rate_limit_state( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-rate-limit-state', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}
		return bbcs_ability_diag_rate_state( $ip );
	}

	function bbcs_wp_ability_execute_get_request_fingerprint( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-request-fingerprint', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$headers = isset( $input['headers'] ) && is_array( $input['headers'] ) ? $input['headers'] : array();

		$ip = bbcs_ability_input_string( $input, 'ip' );
		if ( '' === $ip ) {
			$ip = BotBlockerIp::getCurrentIp();
		}
		$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';

		$version = '' !== $ip ? BotBlockerIp::getVersion( $ip ) : 0;
		$normalized = $ip;
		if ( 6 === $version ) {
			$expanded = BotBlockerIp::expandIPv6( $ip );
			$normalized = is_string( $expanded ) && '' !== $expanded ? $expanded : $ip;
		}

		$ua = bbcs_ability_input_string( $input, 'user_agent' );
		if ( '' === $ua && isset( $headers['user-agent'] ) ) {
			$ua = sanitize_text_field( (string) $headers['user-agent'] );
		}

		$header = static function ( array $bag, string $key ): string {
			foreach ( $bag as $name => $value ) {
				if ( strtolower( (string) $name ) === $key && is_scalar( $value ) ) {
					return (string) $value;
				}
			}
			return '';
		};

		$ja3 = $header( $headers, 'ja3' );
		if ( '' === $ja3 ) {
			$ja3 = $header( $headers, 'cf-ja3-hash' );
		}
		$ja4 = $header( $headers, 'ja4' );
		if ( '' === $ja4 ) {
			$ja4 = $header( $headers, 'cf-ja4' );
		}

		$geo = '' !== $ip ? bbcs_ability_diag_geo( $ip ) : array( 'country' => '', 'asn' => 0 );

		$accept = bbcs_ability_input_string( $input, 'accept_language' );
		if ( '' === $accept ) {
			$accept = $header( $headers, 'accept-language' );
		}

		$proto = $header( $headers, 'x-forwarded-proto' );
		if ( '' === $proto ) {
			$proto = is_ssl() ? 'https' : 'http';
		}

		$identity = class_exists( 'BotBlockerIdentity' ) ? BotBlockerIdentity::current() : array(
			'peer'           => '',
			'role'           => '',
			'state'          => '',
			'subject_source' => '',
			'verified_by'    => '',
		);

		return array(
			'normalized_ip' => $normalized,
			'ip_version'    => (int) $version,
			'ua_family'     => bbcs_ability_diag_ua_family( $ua ),
			'ua_hash'       => '' !== $ua ? md5( $ua ) : '',
			'ja3'           => $ja3,
			'ja4'           => $ja4,
			'tls_trusted'   => false,
			'geo'           => (string) $geo['country'],
			'asn'           => (int) $geo['asn'],
			'hosting'       => false,
			'proxy'         => array( 'type' => 'unknown', 'trusted' => false ),
			'language'      => $accept,
			'protocol'      => strtolower( $proto ),
			'peer'          => (string) $identity['peer'],
			'edge_role'     => (string) $identity['role'],
			'edge_state'    => (string) $identity['state'],
			'subject_source'=> (string) $identity['subject_source'],
			'verified_by'   => (string) $identity['verified_by'],
		);
	}

	function bbcs_wp_ability_execute_get_data_integrity( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-data-integrity', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$hashes = bbcs_wp_ability_input_bool( $input, 'include_content_hashes', false );
		$state  = bbcs_ability_diag_data_files();

		$files = array();
		foreach ( $state['files'] as $file ) {
			$files[] = array(
				'name'   => $file['name'],
				'size'   => $file['size'],
				'sha256' => $hashes ? $file['sha256'] : '',
				'ok'     => $file['ok'],
				'mtime'  => $file['mtime'],
			);
		}

		$settings_path = '' !== $state['dir'] ? $state['dir'] . 'settings.php' : '';
		$settings_ok   = '' !== $settings_path && is_readable( $settings_path );
		$settings_hash = $settings_ok && $hashes ? (string) @hash_file( 'sha256', $settings_path ) : '';

		return array(
			'data_dir'         => $state['dir'],
			'files'            => $files,
			'runtime_settings' => array(
				'path' => $settings_path,
				'ok'   => $settings_ok,
				'hash' => $settings_hash,
			),
			'last_rebuild'     => $state['last_rebuild'],
			'missing'          => array_values( $state['missing'] ),
			'extra'            => array_values( $state['extra'] ),
			'includes_hashes'  => $hashes,
		);
	}

	function bbcs_wp_ability_execute_get_bot_signature_match( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-bot-signature-match', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ua = bbcs_ability_input_string( $input, 'user_agent' );
		if ( '' === $ua ) {
			return new WP_Error( 'ability_invalid_input', __( 'A user agent string is required.', 'botblocker-security' ) );
		}
		return bbcs_ability_diag_bot_match( $ua );
	}

	function bbcs_wp_ability_execute_get_tls_match( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-tls-match', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ja3 = bbcs_ability_input_string( $input, 'ja3' );
		$ja4 = bbcs_ability_input_string( $input, 'ja4' );
		if ( ( '' === $ja3 && '' === $ja4 ) || ( '' !== $ja3 && '' !== $ja4 ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Provide exactly one of ja3 or ja4.', 'botblocker-security' ) );
		}
		return bbcs_ability_diag_tls_match(
			$ja3,
			$ja4,
			bbcs_ability_input_string( $input, 'user_agent' )
		);
	}

	function bbcs_wp_ability_execute_get_geo( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-geo', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}
		return bbcs_ability_diag_geo( $ip );
	}

	function bbcs_wp_ability_execute_get_pass_state( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-pass-state', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$value = bbcs_ability_input_string( $input, 'cookie_value' );
		if ( '' === $value ) {
			return new WP_Error( 'ability_invalid_input', __( 'A cookie value is required.', 'botblocker-security' ) );
		}
		return bbcs_ability_diag_pass(
			$value,
			bbcs_ability_input_string( $input, 'ip' ),
			bbcs_ability_input_string( $input, 'uid' )
		);
	}

	function bbcs_wp_ability_execute_get_challenge_state( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-challenge-state', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$settings = bbcs_ability_diag_settings();
		$mode     = $settings && isset( $settings->bbcs_captcha_mode ) ? (int) $settings->bbcs_captcha_mode : 0;
		$hits     = bbcs_wp_ability_input_int( $input, 'hits', 0 );
		$limit    = bbcs_ability_diag_int_setting( 'hits_per_user', 0 );

		$provider        = '';
		$provider_ready  = true;
		if ( $mode >= 90 && class_exists( 'BotBlockerCaptchaRegistry' ) ) {
			$modes = BotBlockerCaptchaRegistry::allModes();
			if ( isset( $modes[ $mode ] ) ) {
				$provider = (string) $modes[ $mode ]['slug'];
			}
			$provider_ready = BotBlockerCaptchaRegistry::hasKeys( $mode );
		}

		$issues = array();
		foreach ( bbcs_wp_ability_captcha_modes() as $item ) {
			if ( (int) $item['mode'] === $mode && empty( $item['available'] ) ) {
				$issues[] = 'mode_unavailable:' . (string) $item['reason'];
			}
		}
		if ( $mode >= 90 && ! $provider_ready ) {
			$issues[] = 'provider_keys_missing';
		}

		return array(
			'mode'               => $mode,
			'mode_label'         => bbcs_wp_ability_mode_label( $mode ),
			'wait_seconds'       => max( 0, bbcs_ability_diag_int_setting( 'bbcs_captcha_wait', 30 ) ),
			'retry_limit'        => max( 0, $limit ),
			'retries_left'       => $limit > 0 ? max( 0, $limit - max( 0, $hits ) ) : null,
			'issues'             => $issues,
			'provider'           => $provider,
			'provider_available' => (bool) $provider_ready,
			'fallback'           => 'silent',
			'silent'             => defined( 'BOTBLOCKER_CAPTCHA_MODE_SILENT' ) && (int) BOTBLOCKER_CAPTCHA_MODE_SILENT === $mode,
		);
	}

	function bbcs_wp_ability_execute_get_layer_state( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-layer-state', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		return bbcs_ability_diag_layer_state();
	}

	function bbcs_wp_ability_execute_get_decision_trace( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/get-decision-trace', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$ip  = bbcs_ability_input_string( $input, 'ip' );
		$cid = bbcs_ability_input_string( $input, 'cid' );
		if ( '' !== $ip && ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address is required.', 'botblocker-security' ) );
		}
		if ( '' !== $ip && '' !== $cid ) {
			return new WP_Error( 'ability_invalid_input', __( 'Provide cid or ip, not both.', 'botblocker-security' ) );
		}
		if ( '' === $ip && '' === $cid ) {
			return new WP_Error( 'ability_invalid_input', __( 'A cid or an IP address is required.', 'botblocker-security' ) );
		}

		$row = null;
		$table = '';
		foreach ( array( $wpdb->bbcs_hits_suspicious, $wpdb->bbcs_hits ) as $candidate ) {
			if ( ! is_string( $candidate ) || '' === $candidate ) {
				continue;
			}
			$sql = '' !== $cid
				? "SELECT * FROM `{$candidate}` WHERE `cid` = %s ORDER BY `date` DESC LIMIT 1"
				: "SELECT * FROM `{$candidate}` WHERE `ip` = %s ORDER BY `date` DESC LIMIT 1";
			$arg = '' !== $cid ? $cid : $ip;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$found = $wpdb->get_row( $wpdb->prepare( $sql, $arg ), ARRAY_A );
			if ( is_array( $found ) ) {
				$row   = $found;
				$table = $candidate;
				break;
			}
		}

		if ( ! is_array( $row ) ) {
			return new WP_Error( 'ability_not_found', __( 'No event was stored for this address or cid.', 'botblocker-security' ) );
		}

		$text      = strtolower( (string) ( $row['result'] ?? '' ) );
		$decision  = 'unknown';
		if ( false !== strpos( $text, 'block' ) ) {
			$decision = 'block';
		} elseif ( false !== strpos( $text, 'dark' ) ) {
			$decision = 'dark';
		} elseif ( false !== strpos( $text, 'captcha' ) || false !== strpos( $text, 'gray' ) ) {
			$decision = 'challenge';
		} elseif ( false !== strpos( $text, 'allow' ) || false !== strpos( $text, 'legal' ) ) {
			$decision = 'allow';
		} elseif ( $wpdb->bbcs_hits === $table ) {
			$decision = 'allow';
		} elseif ( $wpdb->bbcs_hits_suspicious === $table ) {
			$decision = 'block';
		}

		$event_ip = (string) ( $row['ip'] ?? '' );
		$ip_rules = '' !== $event_ip && filter_var( $event_ip, FILTER_VALIDATE_IP ) ? bbcs_wp_ability_lookup_ip_rules( $event_ip ) : array();
		$rate     = '' !== $event_ip && filter_var( $event_ip, FILTER_VALIDATE_IP ) ? bbcs_ability_diag_rate_state( $event_ip ) : array();

		$edge = array(
			'peer'           => '',
			'client'         => '',
			'role'           => '',
			'state'          => '',
			'header'         => '',
			'subject_source' => '',
			'verified_by'    => '',
		);
		$access_raw = isset( $row['access'] ) && is_string( $row['access'] ) ? trim( (string) $row['access'] ) : '';
		if ( '' !== $access_raw && '{' === $access_raw[0] ) {
			$access = json_decode( $access_raw, true );
			if ( is_array( $access ) ) {
				$edge = array(
					'peer'           => bbcs_ability_mask_ip( (string) ( $access['peer'] ?? '' ) ),
					'client'         => bbcs_ability_mask_ip( (string) ( $access['client'] ?? '' ) ),
					'role'           => (string) ( $access['role'] ?? '' ),
					'state'          => (string) ( $access['edge'] ?? '' ),
					'header'         => (string) ( $access['hdr'] ?? '' ),
					'subject_source' => (string) ( $access['source'] ?? '' ),
					'verified_by'    => (string) ( $access['verified'] ?? '' ),
				);
			}
		}

		return array(
			'decision'      => $decision,
			'reason_code'   => (int) ( $row['passed'] ?? 0 ),
			'edge'          => $edge,
			'matched_rules' => array_values(
				array_map(
					static function ( $rule ) {
						return array(
							'id'       => (int) $rule['id'],
							'family'   => (string) ( $rule['family'] ?? '' ),
							'rule'     => (string) $rule['rule'],
							'value'    => (string) $rule['value'],
							'priority' => (int) $rule['priority'],
							'expired'  => (bool) $rule['expired'],
						);
					},
					$ip_rules
				)
			),
			'signals'       => array(
				'result' => bbcs_ability_mask_text( (string) ( $row['result'] ?? '' ), 80 ),
				'geo'    => (string) ( $row['country'] ?? '' ),
				'proxy'  => (string) ( $row['proxy'] ?? '' ),
				'asn'    => (int) preg_replace( '/[^0-9]/', '', (string) ( $row['asnum'] ?? '0' ) ),
			),
			'rate_summary'  => array(
				'individual_rpm'    => (float) ( $rate['rpm'] ?? 0 ),
				'effective_block'   => (float) ( $rate['effective_block_rpm'] ?? 0 ),
				'effective_captcha' => (float) ( $rate['effective_captcha_rpm'] ?? 0 ),
				'subnet_pressure'   => (float) ( $rate['subnet']['pressure'] ?? 0 ),
			),
			'layer'         => 'stored-event',
			'timings'       => array(
				'generation' => (float) ( $row['generation'] ?? 0 ),
				'event_time' => bbcs_ability_iso_time( (int) ( $row['date'] ?? 0 ) ),
			),
		);
	}

	function bbcs_wp_ability_execute_export_diagnostics( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/export-diagnostics', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$include_events = bbcs_wp_ability_input_bool( $input, 'include_events', false );
		$settings       = bbcs_ability_diag_settings();

		$values = array();
		if ( $settings ) {
			foreach ( get_object_vars( $settings ) as $key => $value ) {
				if ( bbcs_ability_setting_is_redacted( (string) $key ) ) {
					$values[ (string) $key ] = '[redacted]';
					continue;
				}
				if ( is_scalar( $value ) || null === $value ) {
					$values[ (string) $key ] = $value;
				}
			}
		}

		$last = null;
		if ( $include_events ) {
			$trace = bbcs_wp_ability_execute_get_decision_trace(
				array(
					'ip' => BotBlockerIp::getCurrentIp(),
				)
			);
			if ( ! is_wp_error( $trace ) ) {
				$last = array(
					'decision'    => (string) $trace['decision'],
					'reason_code' => (int) $trace['reason_code'],
					'event_time'  => (string) $trace['timings']['event_time'],
				);
			}
		}

		$automation = function_exists( 'bbcs_wp_ability_automation_state' )
			? bbcs_wp_ability_automation_state()
			: array( 'warning' => '' );
		return array(
			'generated_at' => gmdate( 'c' ),
			'plugin'       => array(
				'version' => defined( 'BOTBLOCKER_VERSION' ) ? (string) BOTBLOCKER_VERSION : '',
				'php'     => PHP_VERSION,
				'wp'      => (string) get_bloginfo( 'version' ),
			),
			'settings'     => $values,
			'integrity'    => bbcs_wp_ability_execute_get_data_integrity( array( 'include_content_hashes' => true ) ),
			'layers'       => bbcs_ability_diag_layer_state(),
			'last_event'   => $last,
			'automation'   => $automation,
			'warnings'     => '' === (string) ( $automation['warning'] ?? '' ) ? array() : array( (string) $automation['warning'] ),
		);
	}

	function bbcs_wp_ability_execute_reset_rate_limit( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/reset-rate-limit', $input, bbcs_ability_confirm_token( 'botblocker/reset-rate-limit' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$window = bbcs_ability_diag_window();
		$state  = bbcs_ability_diag_rate_state( $ip );
		$keys   = array( (string) $state['bucket'] );
		if ( ! empty( $state['subnet']['bucket'] ) ) {
			$keys[] = (string) $state['subnet']['bucket'];
		}
		$dry_run = bbcs_wp_ability_is_dry_run( $input );

		$removed = 0;
		if ( ! $dry_run ) {
			foreach ( $keys as $key ) {
				if ( '' === $key ) {
					continue;
				}
				if ( class_exists( 'BotBlockerCache' ) && BotBlockerCache::fileDelete( $key ) ) {
					++$removed;
				}
			}
			bbcs_wp_ability_audit( 'botblocker/reset-rate-limit', 'rate_limit_reset', array( 'ip' => $ip, 'keys' => count( $keys ) ) );
		}

		return array(
			'ip'                  => $ip,
			'subnet'              => (string) $state['subnet']['cidr'],
			'window_minutes'      => $window,
			'previous_rpm'        => (float) $state['rpm'],
			'previous_subnet_rpm' => (float) $state['subnet']['rpm'],
			'deleted'             => $removed,
			'changed'             => $removed > 0,
			'applied'             => ! $dry_run && $removed > 0,
			'dry_run'             => $dry_run,
		);
	}

	function bbcs_wp_ability_execute_reset_ptr_cache( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/reset-ptr-cache', $input, bbcs_ability_confirm_token( 'botblocker/reset-ptr-cache' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$previous = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_ptrcache}`" );
		$dry_run  = bbcs_wp_ability_is_dry_run( $input );
		$cleared  = false;

		if ( ! $dry_run ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$cleared = $wpdb->query( "TRUNCATE TABLE `{$wpdb->bbcs_ptrcache}`" ) !== false;
			if ( $cleared && class_exists( 'BotBlockerCache' ) ) {
				BotBlockerCache::flush();
			}
			bbcs_wp_ability_audit( 'botblocker/reset-ptr-cache', $cleared ? 'ptr_cache_reset' : 'ptr_cache_failed', array( 'rows' => $previous ) );
		}

		return array(
			'previous_rows' => $previous,
			'deleted'       => $cleared ? $previous : 0,
			'changed'       => $previous > 0,
			'applied'       => ! $dry_run && $cleared,
			'dry_run'       => $dry_run,
		);
	}

	function bbcs_wp_ability_execute_seed_test_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/seed-test-rule', $input, bbcs_ability_confirm_token( 'botblocker/seed-test-rule' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$type    = bbcs_ability_input_string( $input, 'type' );
		$data    = bbcs_ability_input_string( $input, 'data' );
		$rule    = bbcs_ability_input_enum( $input, 'rule', array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_GRAY, BBCS_RULE_DARK ), BBCS_RULE_BLOCK );
		$ttl     = bbcs_wp_ability_input_int( $input, 'expires_in', 0 );
		$prefix  = 'bbcs-harness:';
		$comment = bbcs_ability_input_string( $input, 'comment', $prefix . ' seeded' );

		if ( '' === $type || '' === $data || strlen( $type ) > 32 || strlen( $data ) > 512 ) {
			return new WP_Error( 'ability_invalid_input', __( 'type and data are required; type is limited to 32 characters and data to 512.', 'botblocker-security' ) );
		}
		if ( 0 !== strpos( $comment, $prefix ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The comment must start with the bbcs-harness: prefix.', 'botblocker-security' ) );
		}
		if ( $ttl < 60 || $ttl > 604800 ) {
			return new WP_Error( 'ability_invalid_input', __( 'expires_in is required and must be between 60 and 604800 seconds.', 'botblocker-security' ) );
		}

		$table = $wpdb->bbcs_rules ?? '';
		if ( ! is_string( $table ) || '' === $table ) {
			return new WP_Error( 'ability_unavailable', __( 'The rules table is not available.', 'botblocker-security' ) );
		}

		$data_row = array(
			'priority' => 1,
			'type'     => $type,
			'data'     => $data,
			'rule'     => $rule,
			'comment'  => $comment,
			'expires'  => time() + $ttl,
			'disable'  => 0,
		);
		$dry_run = bbcs_wp_ability_is_dry_run( $input );

		$id = 0;
		if ( ! $dry_run ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$saved = $wpdb->insert( $table, $data_row );
			if ( false === $saved ) {
				return new WP_Error( 'ability_execution_failed', __( 'The test rule could not be saved.', 'botblocker-security' ) );
			}
			$id = (int) $wpdb->insert_id;
			BotBlockerFileRenderer::renderRules();
			BotBlockerCache::clearFileCache();
			bbcs_wp_ability_audit( 'botblocker/seed-test-rule', 'test_rule_seeded', array( 'id' => $id, 'type' => $type ) );
		}

		return array(
			'id'      => $id,
			'type'    => $type,
			'data'    => $data,
			'rule'    => $rule,
			'comment' => $comment,
			'expires' => $data_row['expires'],
			'changed' => true,
			'applied' => ! $dry_run,
			'dry_run' => $dry_run,
		);
	}

	function bbcs_wp_ability_execute_clear_test_rules( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/clear-test-rules', $input, bbcs_ability_confirm_token( 'botblocker/clear-test-rules' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$table = $wpdb->bbcs_rules ?? '';
		if ( ! is_string( $table ) || '' === $table ) {
			return new WP_Error( 'ability_unavailable', __( 'The rules table is not available.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `comment` LIKE %s", $wpdb->esc_like( 'bbcs-harness:' ) . '%' ) );
		$count   = is_array( $ids ) ? count( $ids ) : 0;
		$dry_run = bbcs_wp_ability_is_dry_run( $input );

		$deleted = 0;
		if ( ! $dry_run ) {
			foreach ( (array) $ids as $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );
				if ( false !== $result && $result > 0 ) {
					++$deleted;
				}
			}
			BotBlockerFileRenderer::renderRules();
			BotBlockerCache::clearFileCache();
			bbcs_wp_ability_audit( 'botblocker/clear-test-rules', 'test_rules_cleared', array( 'deleted' => $deleted ) );
		}

		return array(
			'found'   => $count,
			'deleted' => $deleted,
			'changed' => $count > 0,
			'applied' => ! $dry_run,
			'dry_run' => $dry_run,
		);
	}

	function bbcs_ability_edge_maps(): array {
		$proxy  = array();
		$bots   = array();
		$shared = array();
		if ( class_exists( 'BotBlockerMultisite' ) && class_exists( 'BotBlockerDataFile' ) ) {
			$dir         = BotBlockerMultisite::getDataDir();
			$proxy_file  = $dir . 'proxy.php';
			$bots_file   = $dir . 'bot-networks.php';
			$shared_file = $dir . 'proxy-shared.php';
			if ( file_exists( $proxy_file ) ) {
				$data  = BotBlockerDataFile::safeLoad( $proxy_file );
				$proxy = isset( $data['bbcs_proxy'] ) && is_array( $data['bbcs_proxy'] ) ? array_keys( $data['bbcs_proxy'] ) : array();
			}
			if ( file_exists( $bots_file ) ) {
				$data = BotBlockerDataFile::safeLoad( $bots_file );
				$bots = isset( $data['bbcs_bot_networks'] ) && is_array( $data['bbcs_bot_networks'] ) ? array_keys( $data['bbcs_bot_networks'] ) : array();
			}
			if ( file_exists( $shared_file ) ) {
				$data   = BotBlockerDataFile::safeLoad( $shared_file );
				$shared = isset( $data['bbcs_proxy_shared'] ) && is_array( $data['bbcs_proxy_shared'] ) ? array_keys( $data['bbcs_proxy_shared'] ) : array();
			}
		}
		return array(
			'proxy'       => array_values( array_map( 'strval', $proxy ) ),
			'bot_network' => array_values( array_map( 'strval', $bots ) ),
			'shared'      => array_values( array_map( 'strval', $shared ) ),
		);
	}

	function bbcs_ability_mask_cidr( string $cidr ): string {
		if ( '' === $cidr ) {
			return '';
		}
		$parts = explode( '/', $cidr, 2 );
		$masked = bbcs_ability_mask_ip( $parts[0] );
		return isset( $parts[1] ) ? $masked . '/' . $parts[1] : $masked;
	}

	function bbcs_wp_ability_execute_get_edge_trust( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-edge-trust', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerIdentity' ) || ! class_exists( 'BotBlockerEdgeStats' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The identity component is not available.', 'botblocker-security' ) );
		}

		$include_map = bbcs_wp_ability_input_bool( $input, 'include_map', false );
		$identity    = BotBlockerIdentity::current();
		$settings    = BotBlocker::getInstance()->settings;
		$enabled     = is_object( $settings ) && ! empty( $settings->bbcs_edge_autotrust );

		$entries = array();
		if ( $enabled ) {
			foreach ( BotBlockerEdgeStats::autoTrustEntries() as $mask => $entry ) {
				$expires = (int) ( $entry['expires'] ?? 0 );
				if ( $expires > 0 && $expires < time() ) {
					continue;
				}
				$entries[] = array(
					'mask'     => bbcs_ability_mask_cidr( (string) $mask ),
					'header'   => (string) ( $entry['header'] ?? '' ),
					'expires'  => bbcs_ability_iso_time( $expires ),
					'evidence' => (int) ( $entry['evidence'] ?? 0 ),
				);
			}
		}

		$maps = array( 'proxy' => array(), 'bot_network' => array(), 'auto' => array(), 'shared' => array() );
		if ( $include_map ) {
			$loaded = bbcs_ability_edge_maps();
			$maps   = array(
				'proxy'       => array_map( 'bbcs_ability_mask_cidr', $loaded['proxy'] ),
				'bot_network' => array_map( 'bbcs_ability_mask_cidr', $loaded['bot_network'] ),
				'auto'        => array_map( 'bbcs_ability_mask_cidr', array_keys( BotBlockerEdgeStats::autoTrustMap() ) ),
				'shared'      => array_map( 'bbcs_ability_mask_cidr', $loaded['shared'] ),
			);
		}

		$suggestion = array(
			'found'    => false,
			'peer'     => '',
			'asn'      => '',
			'asname'   => '',
			'ptr'      => '',
			'header'   => '',
			'verified' => 0,
		);
		foreach ( BotBlockerEdgeStats::suggestions() as $row ) {
			if ( (string) ( $row['peer'] ?? '' ) !== (string) $identity['peer'] ) {
				continue;
			}
			$suggestion = array(
				'found'    => true,
				'peer'     => bbcs_ability_mask_ip( (string) ( $row['peer'] ?? '' ) ),
				'asn'      => (string) ( $row['asn'] ?? '' ),
				'asname'   => (string) ( $row['asname'] ?? '' ),
				'ptr'      => bbcs_ability_mask_short( (string) ( $row['ptr'] ?? '' ) ),
				'header'   => (string) ( $row['header'] ?? '' ),
				'verified' => (int) ( $row['verified'] ?? 0 ),
			);
			break;
		}

		return array(
			'current'       => array(
				'peer'           => bbcs_ability_mask_ip( (string) $identity['peer'] ),
				'client'         => bbcs_ability_mask_ip( (string) $identity['client'] ),
				'claimed'        => bbcs_ability_mask_ip( (string) $identity['claimed'] ),
				'role'           => (string) $identity['role'],
				'state'          => (string) $identity['state'],
				'header'         => (string) $identity['header'],
				'subject'        => bbcs_ability_mask_ip( (string) $identity['subject'] ),
				'subject_source' => (string) $identity['subject_source'],
				'verified_by'    => (string) $identity['verified_by'],
			),
			'autotrust'     => array(
				'enabled' => (bool) $enabled,
				'entries' => $entries,
			),
			'maps'          => $maps,
			'maps_included' => (bool) $include_map,
			'unknown_edge'  => in_array( (string) $identity['state'], array( 'unknown_edge', 'edge_unresolved' ), true ),
			'suggestion'    => $suggestion,
		);
	}

	function bbcs_wp_ability_execute_get_edge_evidence( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/get-edge-evidence', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerEdgeStats' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The edge evidence component is not available.', 'botblocker-security' ) );
		}
		$table = BotBlockerEdgeStats::table();
		if ( '' === $table ) {
			return new WP_Error( 'ability_unavailable', __( 'The edge evidence table is not available.', 'botblocker-security' ) );
		}

		$peer = bbcs_ability_input_string( $input, 'peer' );
		if ( '' !== $peer && ! filter_var( $peer, FILTER_VALIDATE_IP ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid peer address is required.', 'botblocker-security' ) );
		}
		$limit           = max( 1, min( 100, bbcs_wp_ability_input_int( $input, 'limit', 20 ) ) );
		$offset          = max( 0, bbcs_wp_ability_input_int( $input, 'offset', 0 ) );
		$include_clients = bbcs_wp_ability_input_bool( $input, 'include_clients', false );

		if ( '' !== $peer ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE peer = %s ORDER BY last_seen DESC LIMIT %d OFFSET %d", $peer, $limit, $offset ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY last_seen DESC LIMIT %d OFFSET %d", $limit, $offset ), ARRAY_A );
		}

		$since = time() - ( BotBlockerEdgeStats::WINDOW_DAYS * DAY_IN_SECONDS );
		$met   = false;
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$clients = json_decode( (string) ( $row['clients'] ?? '' ), true );
			$clients = is_array( $clients ) ? array_keys( $clients ) : array();
			if ( (int) ( $row['verified_hits'] ?? 0 ) >= BotBlockerEdgeStats::MIN_HITS
				&& (int) ( $row['last_seen'] ?? 0 ) >= $since
				&& count( $clients ) >= BotBlockerEdgeStats::MIN_CLIENTS ) {
				$met = true;
			}
			$item = array(
				'peer'             => bbcs_ability_mask_ip( (string) ( $row['peer'] ?? '' ) ),
				'ip_version'       => (int) ( $row['family'] ?? 0 ),
				'asn'              => (string) ( $row['asn'] ?? '' ),
				'asname'           => (string) ( $row['asname'] ?? '' ),
				'ptr'              => bbcs_ability_mask_short( (string) ( $row['ptr'] ?? '' ) ),
				'header'           => (string) ( $row['header'] ?? '' ),
				'hits'             => (int) ( $row['hits'] ?? 0 ),
				'bot_hits'         => (int) ( $row['bot_hits'] ?? 0 ),
				'verified_hits'    => (int) ( $row['verified_hits'] ?? 0 ),
				'distinct_clients' => count( $clients ),
				'first_seen'       => bbcs_ability_iso_time( (int) ( $row['first_seen'] ?? 0 ) ),
				'last_seen'        => bbcs_ability_iso_time( (int) ( $row['last_seen'] ?? 0 ) ),
				'state'            => (string) ( $row['state'] ?? '' ),
				'signature'        => (string) ( $row['signature'] ?? '' ),
			);
			if ( $include_clients ) {
				$item['clients'] = array_values( array_map( 'strval', $clients ) );
			}
			$out[] = $item;
		}

		$suggestions = array();
		foreach ( BotBlockerEdgeStats::suggestions() as $row ) {
			$suggestions[] = array(
				'peer'     => bbcs_ability_mask_ip( (string) ( $row['peer'] ?? '' ) ),
				'asn'      => (string) ( $row['asn'] ?? '' ),
				'asname'   => (string) ( $row['asname'] ?? '' ),
				'ptr'      => bbcs_ability_mask_short( (string) ( $row['ptr'] ?? '' ) ),
				'header'   => (string) ( $row['header'] ?? '' ),
				'verified' => (int) ( $row['verified'] ?? 0 ),
			);
		}

		return array(
			'rows'        => $out,
			'threshold'   => array(
				'clients'     => BotBlockerEdgeStats::MIN_CLIENTS,
				'hits'        => BotBlockerEdgeStats::MIN_HITS,
				'window_days' => BotBlockerEdgeStats::WINDOW_DAYS,
				'met'         => $met,
			),
			'suggestions' => $suggestions,
		);
	}

	function bbcs_wp_ability_execute_explain_ban_decision( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/explain-ban-decision', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerBanPolicy' ) || ! class_exists( 'BotBlockerIdentity' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The ban policy component is not available.', 'botblocker-security' ) );
		}

		$target = bbcs_ability_input_string( $input, 'target' );
		if ( '' === $target ) {
			return new WP_Error( 'ability_invalid_input', __( 'A target address is required.', 'botblocker-security' ) );
		}
		$identity = BotBlockerIdentity::current();
		$settings = BotBlocker::getInstance()->settings;

		$peer           = bbcs_ability_input_string( $input, 'peer', (string) $identity['peer'] );
		$role           = bbcs_ability_input_string( $input, 'role', (string) $identity['role'] );
		$edge_state     = bbcs_ability_input_string( $input, 'edge_state', (string) $identity['state'] );
		$header         = bbcs_ability_input_string( $input, 'header', (string) $identity['header'] );
		$proxy_detected = bbcs_wp_ability_input_bool( $input, 'proxy_detected', false );
		$block_proxy    = bbcs_wp_ability_input_bool( $input, 'block_proxy_users', is_object( $settings ) && ! empty( $settings->block_proxy_users ) );

		$maps         = bbcs_ability_edge_maps();
		$proxy_map    = array_fill_keys( $maps['proxy'], 'HTTP_X_FORWARDED_FOR' );
		if ( class_exists( 'BotBlockerEdgeStats' ) ) {
			foreach ( array_keys( BotBlockerEdgeStats::autoTrustMap() ) as $mask ) {
				$proxy_map[ $mask ] = 'HTTP_X_FORWARDED_FOR';
			}
		}
		$bot_networks = array_fill_keys( $maps['bot_network'], true );
		$edge_shared  = bbcs_wp_ability_input_bool( $input, 'edge_shared', false );
		if ( ! $edge_shared ) {
			foreach ( $maps['shared'] as $mask ) {
				if ( BotBlockerIp::netMatch( $mask, $peer ) ) {
					$edge_shared = true;
					break;
				}
			}
		}

		$map_match         = false;
		$bot_network_match = false;
		if ( filter_var( $target, FILTER_VALIDATE_IP ) ) {
			foreach ( $maps['proxy'] as $mask ) {
				if ( BotBlockerIp::netMatch( $mask, $target ) ) {
					$map_match = true;
					break;
				}
			}
			foreach ( $maps['bot_network'] as $mask ) {
				if ( BotBlockerIp::netMatch( $mask, $target ) ) {
					$bot_network_match = true;
					break;
				}
			}
		}

		$result = BotBlockerBanPolicy::check(
			$target,
			array(
				'role'              => $role,
				'edge_state'        => $edge_state,
				'peer'              => $peer,
				'header'            => $header,
				'edge_shared'       => $edge_shared,
				'isProxy'           => $proxy_detected ? 'DETECTED' : '',
				'block_proxy_users' => $block_proxy,
				'proxy_map'         => $proxy_map,
				'bot_networks'      => $bot_networks,
				'writer'            => 'ability',
			)
		);

		$reason = (string) ( $result['reason'] ?? '' );
		if ( 'proxy' === $reason ) {
			$reason = 'classic_proxy';
		}
		if ( ! in_array( $reason, array( 'ok', 'ok_clamped', 'target', 'mapped', 'classic_proxy', 'role', 'state', 'bot_ptr', 'auto_edge_target', 'clamp', 'unknown_target', 'shared_edge', 'identity', 'unknown_edge' ), true ) ) {
			$reason = 'identity';
		}

		$bbcS = BotBlocker::getInstance();
		$ptr  = is_object( $bbcS ) && (string) ( $bbcS->peer_ip ?? '' ) === $peer ? (string) ( $bbcS->ptr ?? '' ) : '';
		$asn  = is_object( $bbcS ) && (string) ( $bbcS->peer_ip ?? '' ) === $peer ? (string) ( $bbcS->asnum ?? '' ) : '';

		return array(
			'ban_allowed' => (bool) ( $result['allowed'] ?? false ),
			'reason'      => $reason,
			'target'      => $target,
			'clamp'       => (int) ( $result['clamp'] ?? 0 ),
			'evidence'    => array(
				'peer_ptr'          => bbcs_ability_mask_text( $ptr, 20 ),
				'peer_asn'          => $asn,
				'shared'            => $map_match || $bot_network_match,
				'map_match'         => $map_match,
				'bot_network_match' => $bot_network_match,
				'bot_ptr_match'     => 'bot_ptr' === (string) ( $result['reason'] ?? '' ),
			),
			'would_ban'   => ! empty( $result['allowed'] ) && '' !== (string) ( $result['target'] ?? '' ),
		);
	}

	function bbcs_ability_diag_integrity_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'data_dir'         => array( 'type' => 'string', 'description' => __( 'Absolute path of the generated runtime data directory.', 'botblocker-security' ) ),
				'files'            => array(
					'type'        => 'array',
					'description' => __( 'Generated runtime files present in the data directory.', 'botblocker-security' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'   => array( 'type' => 'string', 'description' => __( 'File name inside the data directory.', 'botblocker-security' ) ),
							'size'   => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'File size in bytes.', 'botblocker-security' ) ),
							'sha256' => array( 'type' => 'string', 'description' => __( 'SHA-256 of the file; an empty string unless include_content_hashes is true.', 'botblocker-security' ) ),
							'ok'     => array( 'type' => 'boolean', 'description' => __( 'True when the file exists and is readable; no hash comparison is performed.', 'botblocker-security' ) ),
							'mtime'  => array( 'type' => 'string', 'description' => __( 'File modification time as an ISO 8601 string.', 'botblocker-security' ) ),
						),
						'required'             => array( 'name', 'size', 'sha256', 'ok', 'mtime' ),
						'additionalProperties' => false,
					),
				),
				'runtime_settings' => array(
					'type'                 => 'object',
					'properties'           => array(
						'path' => array( 'type' => 'string', 'description' => __( 'Absolute path of the generated settings file.', 'botblocker-security' ) ),
						'ok'   => array( 'type' => 'boolean', 'description' => __( 'Whether the settings file exists and is readable.', 'botblocker-security' ) ),
						'hash' => array( 'type' => 'string', 'description' => __( 'SHA-256 of the settings file; an empty string unless include_content_hashes is true.', 'botblocker-security' ) ),
					),
					'required'             => array( 'path', 'ok', 'hash' ),
					'additionalProperties' => false,
					'description'          => __( 'Generated settings file state.', 'botblocker-security' ),
				),
				'last_rebuild'     => array( 'type' => 'string', 'description' => __( 'ISO 8601 time of the newest file write; an empty string when no file exists.', 'botblocker-security' ) ),
				'missing'          => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Expected file names that are absent; optional files are omitted while their feature is off.', 'botblocker-security' ) ),
				'extra'            => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'File names present in the data directory that this install does not generate.', 'botblocker-security' ) ),
				'includes_hashes'  => array( 'type' => 'boolean', 'description' => __( 'Whether SHA-256 hashes are filled in for files and runtime_settings.', 'botblocker-security' ) ),
			),
			'required'             => array( 'data_dir', 'files', 'runtime_settings', 'last_rebuild', 'missing', 'extra', 'includes_hashes' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_get_wp_ability_diagnostics_definitions(): array {
		$match_row  = bbcs_ability_schema_match_row();
		$winner_row = $match_row;
		$winner_row['type']        = array( 'object', 'null' );
		$winner_row['description'] = __( 'The first matching rule that is not expired, or null when no rule matches.', 'botblocker-security' );

		$verified_by = array(
			'type'                 => 'object',
			'properties'           => array(
				'ptr' => array( 'type' => 'boolean', 'description' => __( 'Whether the list entry carries a reverse-DNS token.', 'botblocker-security' ) ),
				'asn' => array( 'type' => 'boolean', 'description' => __( 'Whether the list entry carries an ASN token.', 'botblocker-security' ) ),
				'ip'  => array( 'type' => 'boolean', 'description' => __( 'Whether the list entry carries an IP-range token.', 'botblocker-security' ) ),
			),
			'required'             => array( 'ptr', 'asn', 'ip' ),
			'additionalProperties' => false,
			'description'          => __( 'Verification tokens the matched list entry carries.', 'botblocker-security' ),
		);

		$proxy_state = array(
			'type'                 => 'object',
			'properties'           => array(
				'type'    => array( 'type' => 'string', 'description' => __( 'Proxy type; always unknown because proxy detection is not evaluated by this ability.', 'botblocker-security' ) ),
				'trusted' => array( 'type' => 'boolean', 'description' => __( 'Whether the proxy is trusted; always false because proxy detection is not evaluated by this ability.', 'botblocker-security' ) ),
			),
			'required'             => array( 'type', 'trusted' ),
			'additionalProperties' => false,
			'description'          => __( 'Proxy verdict for the request: {type, trusted}. Always unknown/false: proxy detection is not evaluated by this ability.', 'botblocker-security' ),
		);

		$integrity                = bbcs_ability_diag_integrity_schema();
		$integrity['description'] = __( 'Generated runtime file inventory in the same shape as get-data-integrity.', 'botblocker-security' );

		$rate_state                = bbcs_ability_schema_rate_state();
		$rate_state['description'] = __( 'Individual and subnet rate-limit state in the same shape as get-rate-limit-state.', 'botblocker-security' );

		$pass_state                = bbcs_ability_schema_pass_state();
		$pass_state['description'] = __( 'Cache pass validation in the same shape as get-pass-state.', 'botblocker-security' );

		$trace_edge                = bbcs_ability_schema_trace_edge();
		$trace_edge['description'] = __( 'Edge identity of the stored event; peer and client are masked.', 'botblocker-security' );

		$trace_signals                = bbcs_ability_schema_trace_signals();
		$trace_signals['description'] = __( 'Signals stored with the event; result is truncated to 80 characters because it may contain a PTR.', 'botblocker-security' );

		$rate_summary                = bbcs_ability_schema_rate_summary();
		$rate_summary['description'] = __( 'Rate summary for the stored address.', 'botblocker-security' );

		$trace_timings                = bbcs_ability_schema_trace_timings();
		$trace_timings['description'] = __( 'Timings of the stored event.', 'botblocker-security' );

		$layer_state                = bbcs_ability_schema_layer_state();
		$layer_state['description'] = __( 'Execution layer state in the same shape as get-layer-state.', 'botblocker-security' );

		$automation_state                = bbcs_ability_schema_automation_state();
		$automation_state['description'] = __( 'Automation exposure in the same shape as get-capabilities.automation.', 'botblocker-security' );

		return array(
			array(
				'name'          => 'botblocker/simulate-request',
				'setting_key'   => 'bbcs_ability_simulate_request',
				'label'         => __( 'Simulate a request', 'botblocker-security' ),
				'description'   => __( 'Predicts the firewall decision for a fingerprint without sending a request: IP rules, custom rules, bot signatures, TLS category, geo, ASN and the current rate-limit state. Reverse DNS is never checked here, so a trusted-bot row reports allow even when the live check would deny the request; the answer always carries ptr_checked=false. Pure read: nothing is written, no counters change and no external host is contacted. Use get-rule-match for the rule list alone and check-ip for stored IP rules only.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip'              => bbcs_ability_schema_ip_string(),
						'method'          => array( 'type' => 'string', 'maxLength' => 16, 'description' => __( 'HTTP method of the simulated request.', 'botblocker-security' ) ),
						'path'            => array( 'type' => 'string', 'maxLength' => 400, 'description' => __( 'Request path used for custom-rule matching.', 'botblocker-security' ) ),
						'user_agent'      => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'User agent used for bot-signature and TLS-family matching.', 'botblocker-security' ) ),
						'referer'         => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'Referer; only its host is matched against custom rules.', 'botblocker-security' ) ),
						'accept_language' => array( 'type' => 'string', 'maxLength' => 256, 'description' => __( 'Accept-Language value used for custom-rule matching.', 'botblocker-security' ) ),
						'country'         => array( 'type' => 'string', 'maxLength' => 2, 'description' => __( 'Two-letter country code used for custom-rule matching.', 'botblocker-security' ) ),
						'ja3'             => array( 'type' => 'string', 'maxLength' => 128, 'description' => __( 'JA3 fingerprint used for TLS category matching.', 'botblocker-security' ) ),
						'ja4'             => array( 'type' => 'string', 'maxLength' => 128, 'description' => __( 'JA4 fingerprint; takes precedence over ja3 when both are sent.', 'botblocker-security' ) ),
						'pass_cookie'     => array( 'type' => 'string', 'maxLength' => 128, 'description' => __( 'Cache pass cookie value to validate in the prediction.', 'botblocker-security' ) ),
						'uid'             => array( 'type' => 'string', 'maxLength' => 64, 'description' => __( 'Visitor id bound to the cache pass cookie.', 'botblocker-security' ) ),
					),
					'required'             => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'decision'     => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'dark', 'gray', 'challenge' ), 'description' => __( 'Predicted decision.', 'botblocker-security' ) ),
						'reason_code'  => array( 'type' => 'string', 'enum' => array( 'no_match', 'hot_ban', 'ip_rule', 'custom_rule', 'bot_signature', 'bot_allowlist', 'tls_fingerprint', 'rate_limit_block', 'rate_limit_captcha', 'pass_valid' ), 'description' => __( 'Decision reason; one of the enum values.', 'botblocker-security' ) ),
						'rule_id'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Id of the deciding rule; 0 when no rule decided.', 'botblocker-security' ) ),
						'rule_type'    => array( 'type' => 'string', 'enum' => array( '', 'ip', 'custom', 'allowlist', 'blocklist', 'none', 'tls', 'rate', 'pass' ), 'description' => __( 'Source of the decision; one of the enum values.', 'botblocker-security' ) ),
						'priority'     => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Priority of the deciding rule; 0 when no rule decided.', 'botblocker-security' ) ),
						'bot_signal'   => array( 'type' => 'string', 'enum' => array( 'allow', 'deny', 'listed', 'none' ), 'description' => __( 'Bot-signature verdict for the supplied user agent.', 'botblocker-security' ) ),
						'tls_category' => array( 'type' => 'string', 'description' => __( 'TLS category from the fingerprint list; free text because the cloud list defines the set.', 'botblocker-security' ) ),
						'geo'          => array( 'type' => 'string', 'description' => __( 'Two-letter country code.', 'botblocker-security' ) ),
						'asn'          => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Autonomous system number; 0 when unknown.', 'botblocker-security' ) ),
						'proxy_type'   => array( 'type' => 'string', 'enum' => array( 'unknown' ), 'description' => __( 'Proxy type; always unknown because proxy detection is not evaluated by this ability.', 'botblocker-security' ) ),
						'rate'         => $rate_state,
						'pass'         => $pass_state,
						'layer'        => array( 'type' => 'string', 'enum' => array( 'none', 'hot-ban', 'ip-rule', 'custom-rule', 'bot-signature', 'tls', 'rate-limit', 'cache-pass' ), 'description' => __( 'Evaluation layer that produced the decision.', 'botblocker-security' ) ),
						'matched'      => array(
							'type'        => 'array',
							'description' => __( 'Rules that matched, ordered by priority, in the same shape as get-rule-match.', 'botblocker-security' ),
							'items'       => $match_row,
						),
						'notes'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Human-readable notes; empty when there is nothing to report.', 'botblocker-security' ) ),
						'ptr_checked'  => array( 'type' => 'boolean', 'description' => __( 'Always false: reverse DNS is never checked by this ability.', 'botblocker-security' ) ),
					),
					'required'             => array( 'decision', 'reason_code', 'rule_id', 'rule_type', 'priority', 'bot_signal', 'tls_category', 'geo', 'asn', 'proxy_type', 'rate', 'pass', 'layer', 'matched', 'notes', 'ptr_checked' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_simulate_request',
			),
			array(
				'name'          => 'botblocker/get-decision-trace',
				'setting_key'   => 'bbcs_ability_get_decision_trace',
				'label'         => __( 'Get decision trace', 'botblocker-security' ),
				'description'   => __( 'Explains the last stored decision for a cid or an IP address: the decision, the stored numeric reason, the IP rules that matched and the current rate summary. Provide cid or ip, not both. It reads the visitor log only: a request that left no log entry cannot be traced, and user agents and paths are not returned. The edge peer and client are masked. Returns error ability_not_found when no stored event matches. Use simulate-request for hypotheticals.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'description'          => __( 'Provide cid or ip, not both. With neither field the ability rejects the call.', 'botblocker-security' ),
					'properties'           => array(
						'ip'  => bbcs_ability_schema_ip_string(),
						'cid' => array( 'type' => 'string', 'maxLength' => 64, 'description' => __( 'Visitor event client id; the only event identifier.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'decision'      => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'dark', 'challenge', 'unknown' ), 'description' => __( 'Decision stored for the event; unknown when the stored text does not identify one.', 'botblocker-security' ) ),
						'reason_code'   => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Numeric reason code stored in the hits.passed column; 0 when not recorded. The human-readable reason is in signals.result.', 'botblocker-security' ) ),
						'edge'          => $trace_edge,
						'matched_rules' => array(
							'type'        => 'array',
							'description' => __( 'IP rules that matched the stored address, ordered by priority.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'       => array( 'type' => 'integer', 'description' => __( 'Rule row id.', 'botblocker-security' ) ),
									'family'   => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Address family of the rule.', 'botblocker-security' ) ),
									'rule'     => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark', 'noop' ), 'description' => __( 'Stored rule type.', 'botblocker-security' ) ),
									'value'    => array( 'type' => 'string', 'description' => __( 'Stored address or CIDR, unmasked.', 'botblocker-security' ) ),
									'priority' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Evaluation priority of the rule.', 'botblocker-security' ) ),
									'expired'  => array( 'type' => 'boolean', 'description' => __( 'Whether the rule has expired.', 'botblocker-security' ) ),
								),
								'required'             => array( 'id', 'family', 'rule', 'value', 'priority', 'expired' ),
								'additionalProperties' => false,
							),
						),
						'signals'       => $trace_signals,
						'rate_summary'  => $rate_summary,
						'layer'         => array( 'type' => 'string', 'enum' => array( 'stored-event' ), 'description' => __( 'Always stored-event: the answer comes from the visitor log.', 'botblocker-security' ) ),
						'timings'       => $trace_timings,
					),
					'required'             => array( 'decision', 'reason_code', 'edge', 'matched_rules', 'signals', 'rate_summary', 'layer', 'timings' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_decision_trace',
			),
			array(
				'name'          => 'botblocker/get-rate-limit-state',
				'setting_key'   => 'bbcs_ability_get_rate_limit_state',
				'label'         => __( 'Get rate-limit state', 'botblocker-security' ),
				'description'   => __( 'Returns the individual and subnet rate buckets for an IP address with the same keys as the rate limiter, without recording a hit: rpm, window, the effective block and CAPTCHA thresholds and an active rate-limit ban.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip' => bbcs_ability_schema_ip_string(),
					),
					'required'             => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => $rate_state,
				'execute'       => 'bbcs_wp_ability_execute_get_rate_limit_state',
			),
			array(
				'name'          => 'botblocker/get-request-fingerprint',
				'setting_key'   => 'bbcs_ability_get_request_fingerprint',
				'label'         => __( 'Get request fingerprint', 'botblocker-security' ),
				'description'   => __( 'Shows how BotBlocker reads a client: normalized IP, address family, user-agent family and hash, TLS headers, geo and ASN, language and protocol. It reports how the values were derived, so a test can spot a normalization mismatch. The peer address is the transport address of the current request, unmasked, and the user-agent hash is an MD5 digest; TLS trust, hosting and proxy detection are not evaluated here.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip'              => array( 'type' => 'string', 'description' => __( 'Address to read; the current request address when omitted.', 'botblocker-security' ) ),
						'user_agent'      => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'User agent to read; falls back to the user-agent header.', 'botblocker-security' ) ),
						'accept_language' => array( 'type' => 'string', 'maxLength' => 256, 'description' => __( 'Accept-Language value; falls back to the request header.', 'botblocker-security' ) ),
						'headers'         => array( 'type' => 'object', 'description' => __( 'Additional request headers as a name/value object. Scalar values only; nested values are ignored.', 'botblocker-security' ), 'additionalProperties' => array( 'type' => array( 'string', 'number', 'boolean' ) ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'normalized_ip' => array( 'type' => 'string', 'description' => __( 'Address after version normalization; an empty string when the input is not a valid address.', 'botblocker-security' ) ),
						'ip_version'    => array( 'type' => 'integer', 'enum' => array( 0, 4, 6 ), 'description' => __( 'IP version of the normalized address; 0 when the address is invalid.', 'botblocker-security' ) ),
						'ua_family'     => array( 'type' => 'string', 'enum' => array( 'unknown', 'automation', 'headless', 'edge', 'opera', 'chrome', 'firefox', 'safari', 'bot' ), 'description' => __( 'User-agent family derived by the plugin.', 'botblocker-security' ) ),
						'ua_hash'       => array( 'type' => 'string', 'description' => __( 'MD5 digest of the user agent; an empty string when no user agent was supplied.', 'botblocker-security' ) ),
						'ja3'           => array( 'type' => 'string', 'description' => __( 'JA3 fingerprint from the ja3 or cf-ja3-hash header.', 'botblocker-security' ) ),
						'ja4'           => array( 'type' => 'string', 'description' => __( 'JA4 fingerprint from the ja4 or cf-ja4 header.', 'botblocker-security' ) ),
						'tls_trusted'   => array( 'type' => 'boolean', 'description' => __( 'Always false: TLS trust is not evaluated by this ability.', 'botblocker-security' ) ),
						'geo'           => array( 'type' => 'string', 'description' => __( 'Two-letter country code; empty when the ASN database has no answer.', 'botblocker-security' ) ),
						'asn'           => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Autonomous system number; 0 when unknown.', 'botblocker-security' ) ),
						'hosting'       => array( 'type' => 'boolean', 'description' => __( 'Always false: hosting is not evaluated by this ability.', 'botblocker-security' ) ),
						'proxy'         => $proxy_state,
						'language'      => array( 'type' => 'string', 'description' => __( 'Accept-Language value sent to the matcher.', 'botblocker-security' ) ),
						'protocol'      => array( 'type' => 'string', 'description' => __( 'Request protocol from x-forwarded-proto, or https/http when the header is absent.', 'botblocker-security' ) ),
						'peer'          => array( 'type' => 'string', 'description' => __( 'Transport address of the current request, unmasked.', 'botblocker-security' ) ),
						'edge_role'     => array( 'type' => 'string', 'description' => __( 'Resolved edge role code.', 'botblocker-security' ) ),
						'edge_state'    => array( 'type' => 'string', 'description' => __( 'Resolved edge state code.', 'botblocker-security' ) ),
						'subject_source'=> array( 'type' => 'string', 'description' => __( 'Address subject source code.', 'botblocker-security' ) ),
						'verified_by'   => array( 'type' => 'string', 'description' => __( 'Edge verification method.', 'botblocker-security' ) ),
					),
					'required'             => array( 'normalized_ip', 'ip_version', 'ua_family', 'ua_hash', 'ja3', 'ja4', 'tls_trusted', 'geo', 'asn', 'hosting', 'proxy', 'language', 'protocol', 'peer', 'edge_role', 'edge_state', 'subject_source', 'verified_by' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_request_fingerprint',
			),
			array(
				'name'          => 'botblocker/get-data-integrity',
				'setting_key'   => 'bbcs_ability_get_data_integrity',
				'label'         => __( 'Get data integrity', 'botblocker-security' ),
				'description'   => __( 'Lists the generated runtime files in the data folder with size, modification time and, optionally, SHA-256 hashes, plus missing and unexpected files and the runtime settings file state. ok means the file exists and is readable; no hash comparison is performed. missing lists only files this install should have produced: the Early Init router and the auto-trust file are left out while the layer or the feature is off. extra lists other files present in the folder, which may belong to add-ons. File contents are never returned.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'include_content_hashes' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, files[].sha256 and runtime_settings.hash are filled in.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => $integrity,
				'execute'       => 'bbcs_wp_ability_execute_get_data_integrity',
			),
			array(
				'name'          => 'botblocker/get-bot-signature-match',
				'setting_key'   => 'bbcs_ability_get_bot_signature_match',
				'label'         => __( 'Get bot signature match', 'botblocker-security' ),
				'description'   => __( 'Matches a user agent against the generated bot signature and search-engine lists and returns the signature, the list, the action and which verification tokens the entry carries. It answers which signature should have matched and why.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'user_agent' => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'User agent to match against the generated lists.', 'botblocker-security' ) ),
					),
					'required'             => array( 'user_agent' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'matched_signature' => array( 'type' => 'string', 'description' => __( 'Signature token that matched; an empty string when nothing matched.', 'botblocker-security' ) ),
						'matched_pattern'   => array( 'type' => 'string', 'description' => __( 'Pattern that matched, identical to matched_signature; an empty string when nothing matched.', 'botblocker-security' ) ),
						'source'            => array( 'type' => 'string', 'enum' => array( 'search_engine', 'bot_signature', 'none' ), 'description' => __( 'List family that matched; none when nothing matched.', 'botblocker-security' ) ),
						'list_type'         => array( 'type' => 'string', 'enum' => array( 'allowlist', 'blocklist', 'none' ), 'description' => __( 'List type of the matched entry; none when nothing matched.', 'botblocker-security' ) ),
						'action'            => array( 'type' => 'string', 'enum' => array( '', 'allow', 'block', 'gray', 'dark' ), 'description' => __( 'Rule stored for the matched entry; an empty string when nothing matched.', 'botblocker-security' ) ),
						'verified_by'       => $verified_by,
						'verdict'           => array( 'type' => 'string', 'enum' => array( 'allow', 'deny', 'listed', 'none' ), 'description' => __( 'Verdict derived from the matched entry; none when nothing matched.', 'botblocker-security' ) ),
					),
					'required'             => array( 'matched_signature', 'matched_pattern', 'source', 'list_type', 'action', 'verified_by', 'verdict' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_bot_signature_match',
			),
			array(
				'name'          => 'botblocker/get-tls-match',
				'setting_key'   => 'bbcs_ability_get_tls_match',
				'label'         => __( 'Get TLS match', 'botblocker-security' ),
				'description'   => __( 'Looks a JA3 or JA4 fingerprint up in the TLS list and compares the list user-agent family with the family derived from a user agent. No TLS handshake is performed. Provide exactly one of ja3 or ja4.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'description'          => __( 'Provide exactly one of ja3 or ja4. user_agent is optional and is only used to compare families.', 'botblocker-security' ),
					'properties'           => array(
						'ja3'        => array( 'type' => 'string', 'maxLength' => 128, 'description' => __( 'JA3 fingerprint to look up.', 'botblocker-security' ) ),
						'ja4'        => array( 'type' => 'string', 'maxLength' => 128, 'description' => __( 'JA4 fingerprint to look up.', 'botblocker-security' ) ),
						'user_agent' => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'User agent whose family is compared with the listed family.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'fingerprint'     => array( 'type' => 'string', 'description' => __( 'Normalized fingerprint that was looked up.', 'botblocker-security' ) ),
						'category'        => array( 'type' => 'string', 'description' => __( 'TLS category from the fingerprint list; free text because the cloud list defines the set.', 'botblocker-security' ) ),
						'ua_family'       => array( 'type' => 'string', 'enum' => array( 'unknown', 'automation', 'headless', 'edge', 'opera', 'chrome', 'firefox', 'safari', 'bot' ), 'description' => __( 'User-agent family derived from the supplied user agent.', 'botblocker-security' ) ),
						'expected_family' => array( 'type' => 'string', 'description' => __( 'Family the list entry expects; an empty string when no entry matched.', 'botblocker-security' ) ),
						'match'           => array( 'type' => array( 'boolean', 'null' ), 'description' => __( 'Whether the families agree; null when there is no listed family to compare.', 'botblocker-security' ) ),
						'source'          => array( 'type' => 'string', 'enum' => array( 'tls_fingerprints', 'not_listed', 'unavailable' ), 'description' => __( 'Lookup result: tls_fingerprints when listed, not_listed when the list lacks the fingerprint, unavailable when the list file is missing.', 'botblocker-security' ) ),
					),
					'required'             => array( 'fingerprint', 'category', 'ua_family', 'expected_family', 'match', 'source' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_tls_match',
			),
			array(
				'name'          => 'botblocker/get-geo',
				'setting_key'   => 'bbcs_ability_get_geo',
				'label'         => __( 'Get geo and ASN', 'botblocker-security' ),
				'description'   => __( 'Returns country, continent, ASN, organization and CIDR for an IP address from the local ASN database. VPN, Tor and hosting are always false here: this ability reads country and ASN only.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip' => bbcs_ability_schema_ip_string(),
					),
					'required'             => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'country'      => array( 'type' => 'string', 'description' => __( 'Two-letter country code; empty when the database has no answer.', 'botblocker-security' ) ),
						'country_name' => array( 'type' => 'string', 'description' => __( 'Country name from the database; empty when unknown.', 'botblocker-security' ) ),
						'continent'    => array( 'type' => 'string', 'description' => __( 'Continent code from the database; empty when unknown.', 'botblocker-security' ) ),
						'asn'          => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Autonomous system number; 0 when unknown.', 'botblocker-security' ) ),
						'organization' => array( 'type' => 'string', 'description' => __( 'Autonomous system name; empty when unknown.', 'botblocker-security' ) ),
						'cidr'         => array( 'type' => 'string', 'description' => __( 'CIDR range of the matched database row; empty when unknown.', 'botblocker-security' ) ),
						'hosting'      => array( 'type' => 'boolean', 'description' => __( 'Always false: this ability reads country and ASN only.', 'botblocker-security' ) ),
						'vpn'          => array( 'type' => 'boolean', 'description' => __( 'Always false: this ability reads country and ASN only.', 'botblocker-security' ) ),
						'tor'          => array( 'type' => 'boolean', 'description' => __( 'Always false: this ability reads country and ASN only.', 'botblocker-security' ) ),
						'source'       => array( 'type' => 'string', 'enum' => array( 'local_asn_db' ), 'description' => __( 'Always local_asn_db: the lookup uses the local database only.', 'botblocker-security' ) ),
						'available'    => array( 'type' => 'boolean', 'description' => __( 'Whether the local ASN database answered the lookup.', 'botblocker-security' ) ),
					),
					'required'             => array( 'country', 'country_name', 'continent', 'asn', 'organization', 'cidr', 'hosting', 'vpn', 'tor', 'source', 'available' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_geo',
			),
			array(
				'name'          => 'botblocker/get-pass-state',
				'setting_key'   => 'bbcs_ability_get_pass_state',
				'label'         => __( 'Get cache pass state', 'botblocker-security' ),
				'description'   => __( 'Verifies a cache pass cookie value: format, expiry and the signature bound to the visitor id and IP. The salt and the signature are never returned. Without the visitor id and IP the answer reports bindings_missing instead of guessing.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'cookie_value' => array( 'type' => 'string', 'maxLength' => 128, 'description' => __( 'Cache pass cookie value to verify.', 'botblocker-security' ) ),
						'ip'           => array( 'type' => 'string', 'description' => __( 'Address the cookie is expected to be bound to.', 'botblocker-security' ) ),
						'uid'          => array( 'type' => 'string', 'maxLength' => 64, 'description' => __( 'Visitor id the cookie is expected to be bound to.', 'botblocker-security' ) ),
					),
					'required'             => array( 'cookie_value' ),
					'additionalProperties' => false,
				),
				'output_schema' => $pass_state,
				'execute'       => 'bbcs_wp_ability_execute_get_pass_state',
			),
			array(
				'name'          => 'botblocker/get-challenge-state',
				'setting_key'   => 'bbcs_ability_get_challenge_state',
				'label'         => __( 'Get challenge state', 'botblocker-security' ),
				'description'   => __( 'Returns the CAPTCHA challenge configuration: active mode, wait time, retry limit, remaining attempts for a supplied hit count, provider and availability, fallback and whether silent mode is active. Use get-captcha-state for the mode catalog and per-mode availability.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'hits' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 100000, 'description' => __( 'Recorded hits for the visitor; used only to compute retries_left.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'               => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Active CAPTCHA mode id.', 'botblocker-security' ) ),
						'mode_label'         => array( 'type' => 'string', 'description' => __( 'Human-readable mode label; do not parse.', 'botblocker-security' ) ),
						'wait_seconds'       => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Configured wait before the challenge is shown (bbcs_captcha_wait).', 'botblocker-security' ) ),
						'retry_limit'        => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Maximum attempts per visitor from hits_per_user; 0 means no limit.', 'botblocker-security' ) ),
						'retries_left'       => array( 'type' => array( 'integer', 'null' ), 'minimum' => 0, 'description' => __( 'Attempts left for the supplied hits; null when retry_limit is 0.', 'botblocker-security' ) ),
						'issues'             => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Issue codes: mode_unavailable:<reason> when the active mode cannot run, provider_keys_missing when provider keys are absent.', 'botblocker-security' ) ),
						'provider'           => array( 'type' => 'string', 'description' => __( 'Provider slug of the active mode; empty when the mode has no provider.', 'botblocker-security' ) ),
						'provider_available' => array( 'type' => 'boolean', 'description' => __( 'Whether the active provider has the keys it needs.', 'botblocker-security' ) ),
						'fallback'           => array( 'type' => 'string', 'enum' => array( 'silent' ), 'description' => __( 'Fallback used when the challenge cannot render; always silent.', 'botblocker-security' ) ),
						'silent'             => array( 'type' => 'boolean', 'description' => __( 'Whether the silent mode is the active mode.', 'botblocker-security' ) ),
					),
					'required'             => array( 'mode', 'mode_label', 'wait_seconds', 'retry_limit', 'retries_left', 'issues', 'provider', 'provider_available', 'fallback', 'silent' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_challenge_state',
			),
			array(
				'name'          => 'botblocker/get-rule-match',
				'setting_key'   => 'bbcs_ability_get_rule_match',
				'label'         => __( 'Get rule match', 'botblocker-security' ),
				'description'   => __( 'Returns every IP rule and custom rule that matches a fingerprint, ordered by priority, with the winning rule. Provide at least one match term; IP rules need ip, custom rules match the other terms. Use it to check priority and overlap after simulating a request.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'description'          => __( 'Provide at least one of ip, path, user_agent, referer, accept_language, country. Unknown or empty terms yield an empty result.', 'botblocker-security' ),
					'properties'           => array(
						'ip'              => array( 'type' => 'string', 'description' => __( 'Address matched against stored IP rules.', 'botblocker-security' ) ),
						'path'            => array( 'type' => 'string', 'maxLength' => 400, 'description' => __( 'Request path matched against custom rules.', 'botblocker-security' ) ),
						'user_agent'      => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'User agent matched against custom rules.', 'botblocker-security' ) ),
						'referer'         => array( 'type' => 'string', 'maxLength' => 1024, 'description' => __( 'Referer; only its host is matched against custom rules.', 'botblocker-security' ) ),
						'accept_language' => array( 'type' => 'string', 'maxLength' => 256, 'description' => __( 'Accept-Language value matched against custom rules.', 'botblocker-security' ) ),
						'country'         => array( 'type' => 'string', 'maxLength' => 2, 'description' => __( 'Two-letter country code matched against custom rules.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules'  => array(
							'type'        => 'array',
							'description' => __( 'Every matching rule, ordered by priority then id.', 'botblocker-security' ),
							'items'       => $match_row,
						),
						'winner' => $winner_row,
					),
					'required'             => array( 'rules', 'winner' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_rule_match',
			),
			array(
				'name'          => 'botblocker/get-layer-state',
				'setting_key'   => 'bbcs_ability_get_layer_state',
				'label'         => __( 'Get layer state', 'botblocker-security' ),
				'description'   => __( 'Reports which execution layer answers: Early Init (with the wp-config router flag and the router file hash), the MU-plugin file hash, the main shield, the cache and geo gates, detected conflicts and versions. early_init.file is the router path and mu.file is the MU-plugin path.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => false ),
				'output_schema' => $layer_state,
				'execute'       => 'bbcs_wp_ability_execute_get_layer_state',
			),
			array(
				'name'          => 'botblocker/export-diagnostics',
				'setting_key'   => 'bbcs_ability_export_diagnostics',
				'label'         => __( 'Export diagnostics', 'botblocker-security' ),
				'description'   => __( 'One call that bundles settings with credentials replaced by the literal [redacted], data-file integrity, layer state, automation state and optionally the last decision. Visitor data stays out. Integrity has the same shape as get-data-integrity and layers the same shape as get-layer-state.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'include_events' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, last_event carries the last stored decision for the current address.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'generated_at' => array( 'type' => 'string', 'description' => __( 'ISO 8601 time the export was generated.', 'botblocker-security' ) ),
						'plugin'       => array(
							'type'                 => 'object',
							'properties'           => array(
								'version' => array( 'type' => 'string', 'description' => __( 'BotBlocker version.', 'botblocker-security' ) ),
								'php'     => array( 'type' => 'string', 'description' => __( 'PHP version.', 'botblocker-security' ) ),
								'wp'      => array( 'type' => 'string', 'description' => __( 'WordPress version.', 'botblocker-security' ) ),
							),
							'required'             => array( 'version', 'php', 'wp' ),
							'additionalProperties' => false,
							'description'          => __( 'Versions of the plugin and its runtime.', 'botblocker-security' ),
						),
						'settings'     => array(
							'type'                 => 'object',
							'additionalProperties' => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'null' ) ),
							'description'          => __( 'Stored setting name to value; credential and PII keys carry the literal string [redacted].', 'botblocker-security' ),
						),
						'integrity'    => $integrity,
						'layers'       => $layer_state,
						'last_event'   => array(
							'type'                 => array( 'object', 'null' ),
							'properties'           => array(
								'decision'    => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'dark', 'challenge', 'unknown' ), 'description' => __( 'Decision stored for the event.', 'botblocker-security' ) ),
								'reason_code' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Numeric reason code stored in the hits.passed column; 0 when not recorded.', 'botblocker-security' ) ),
								'event_time'  => array( 'type' => 'string', 'description' => __( 'ISO 8601 timestamp of the stored event.', 'botblocker-security' ) ),
							),
							'required'             => array( 'decision', 'reason_code', 'event_time' ),
							'additionalProperties' => false,
							'description'          => __( 'The last stored decision, or null when no event was found.', 'botblocker-security' ),
						),
						'automation'   => $automation_state,
						'warnings'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Warning sentences; empty when there is nothing to report.', 'botblocker-security' ) ),
					),
					'required'             => array( 'generated_at', 'plugin', 'settings', 'integrity', 'layers', 'last_event', 'automation', 'warnings' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_export_diagnostics',
			),
			array(
				'name'          => 'botblocker/reset-rate-limit',
				'setting_key'   => 'bbcs_ability_reset_rate_limit',
				'label'         => __( 'Reset a rate-limit bucket', 'botblocker-security' ),
				'description'   => __( 'Deletes the individual and subnet rate-limit buckets for an IP address. It does not touch rules, settings or bans. Requires confirm "RESET" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip'      => bbcs_ability_schema_ip_string(),
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be RESET.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is deleted and the answer reports deleted=0, changed=false and applied=false.', 'botblocker-security' ) ),
					),
					'required'             => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip'                  => array( 'type' => 'string', 'description' => __( 'Address whose buckets were reset.', 'botblocker-security' ) ),
						'subnet'              => array( 'type' => 'string', 'description' => __( 'Subnet CIDR whose bucket was reset; empty when the subnet limit is off.', 'botblocker-security' ) ),
						'window_minutes'      => array( 'type' => 'integer', 'minimum' => 1, 'description' => __( 'Rate-limit window length in minutes.', 'botblocker-security' ) ),
						'previous_rpm'        => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Individual requests per minute before the reset; a projection on dry run.', 'botblocker-security' ) ),
						'previous_subnet_rpm' => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Subnet requests per minute before the reset; a projection on dry run.', 'botblocker-security' ) ),
						'deleted'             => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Bucket files actually deleted; 0 on dry run.', 'botblocker-security' ) ),
						'changed'             => array( 'type' => 'boolean', 'description' => __( 'True when at least one bucket file was deleted.', 'botblocker-security' ) ),
						'applied'             => array( 'type' => 'boolean', 'description' => __( 'True only when the call was not a dry run and at least one bucket was deleted.', 'botblocker-security' ) ),
						'dry_run'             => array( 'type' => 'boolean', 'description' => __( 'Whether the call was a dry run.', 'botblocker-security' ) ),
					),
					'required'             => array( 'ip', 'subnet', 'window_minutes', 'previous_rpm', 'previous_subnet_rpm', 'deleted', 'changed', 'applied', 'dry_run' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_reset_rate_limit',
			),
			array(
				'name'          => 'botblocker/reset-ptr-cache',
				'setting_key'   => 'bbcs_ability_reset_ptr_cache',
				'label'         => __( 'Reset the PTR cache', 'botblocker-security' ),
				'description'   => __( 'Deletes every cached reverse-DNS (PTR) result for the current site and rotates the plugin cache generation, so crawler verification runs a fresh lookup. It does not touch rules, settings or bans. Requires confirm "RESET" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be RESET.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is deleted and the answer reports deleted=0 and applied=false.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'previous_rows' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Cached PTR rows before the call; a projection on dry run.', 'botblocker-security' ) ),
						'deleted'       => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rows actually deleted; 0 on dry run.', 'botblocker-security' ) ),
						'changed'       => array( 'type' => 'boolean', 'description' => __( 'True when the cache held at least one row.', 'botblocker-security' ) ),
						'applied'       => array( 'type' => 'boolean', 'description' => __( 'True only when the call was not a dry run and the truncate succeeded.', 'botblocker-security' ) ),
						'dry_run'       => array( 'type' => 'boolean', 'description' => __( 'Whether the call was a dry run.', 'botblocker-security' ) ),
					),
					'required'             => array( 'previous_rows', 'deleted', 'changed', 'applied', 'dry_run' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_reset_ptr_cache',
			),
			array(
				'name'          => 'botblocker/seed-test-rule',
				'setting_key'   => 'bbcs_ability_seed_test_rule',
				'label'         => __( 'Seed a temporary test rule', 'botblocker-security' ),
				'description'   => __( 'Creates a short-lived custom rule whose comment must start with bbcs-harness: and whose expiry is mandatory. Refuses other comments and missing TTL. Requires confirm "SEED" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'type'       => array( 'type' => 'string', 'maxLength' => 32, 'description' => __( 'Custom rule matcher type; stored verbatim, and an unknown matcher type never matches.', 'botblocker-security' ) ),
						'data'       => array( 'type' => 'string', 'maxLength' => 512, 'description' => __( 'Matcher value; stored verbatim.', 'botblocker-security' ) ),
						'rule'       => array( 'type' => 'string', 'enum' => array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_GRAY, BBCS_RULE_DARK ), 'default' => BBCS_RULE_BLOCK, 'description' => __( 'Rule the seeded row applies when it matches; defaults to block.', 'botblocker-security' ) ),
						'comment'    => array( 'type' => 'string', 'maxLength' => 255, 'default' => 'bbcs-harness: seeded', 'description' => __( 'Must start with bbcs-harness:. Defaults to "bbcs-harness: seeded".', 'botblocker-security' ) ),
						'expires_in' => array( 'type' => 'integer', 'minimum' => 60, 'maximum' => 604800, 'description' => __( 'Lifetime in seconds, between 60 and 604800; values outside the range are rejected.', 'botblocker-security' ) ),
						'confirm'    => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be SEED.', 'botblocker-security' ) ),
						'dry_run'    => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is written and the answer reports id=0 and applied=false.', 'botblocker-security' ) ),
					),
					'required'             => array( 'type', 'data', 'expires_in' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Inserted rule id; 0 on dry run.', 'botblocker-security' ) ),
						'type'    => array( 'type' => 'string', 'description' => __( 'Custom rule matcher type that was stored.', 'botblocker-security' ) ),
						'data'    => array( 'type' => 'string', 'description' => __( 'Matcher value that was stored.', 'botblocker-security' ) ),
						'rule'    => array( 'type' => 'string', 'enum' => array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_GRAY, BBCS_RULE_DARK ), 'description' => __( 'Rule the seeded row applies.', 'botblocker-security' ) ),
						'comment' => array( 'type' => 'string', 'description' => __( 'Stored comment; always starts with bbcs-harness:.', 'botblocker-security' ) ),
						'expires' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix expiry timestamp of the seeded row.', 'botblocker-security' ) ),
						'changed' => array( 'type' => 'boolean', 'description' => __( 'Always true: the call adds a row, or would add one on dry run.', 'botblocker-security' ) ),
						'applied' => array( 'type' => 'boolean', 'description' => __( 'True only when the row was inserted.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'description' => __( 'Whether the call was a dry run.', 'botblocker-security' ) ),
					),
					'required'             => array( 'id', 'type', 'data', 'rule', 'comment', 'expires', 'changed', 'applied', 'dry_run' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_seed_test_rule',
			),
			array(
				'name'          => 'botblocker/clear-test-rules',
				'setting_key'   => 'bbcs_ability_clear_test_rules',
				'label'         => __( 'Clear seeded test rules', 'botblocker-security' ),
				'description'   => __( 'Deletes only custom rules whose comment starts with bbcs-harness: and leaves user rules untouched. Requires confirm "CLEAR" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be CLEAR.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is deleted and the answer reports deleted=0 and applied=false.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'found'   => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Seeded rows found; filled in on dry run too.', 'botblocker-security' ) ),
						'deleted' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rows actually deleted; 0 on dry run.', 'botblocker-security' ) ),
						'changed' => array( 'type' => 'boolean', 'description' => __( 'True when at least one seeded row was found.', 'botblocker-security' ) ),
						'applied' => array( 'type' => 'boolean', 'description' => __( 'True only when the call was not a dry run.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'description' => __( 'Whether the call was a dry run.', 'botblocker-security' ) ),
					),
					'required'             => array( 'found', 'deleted', 'changed', 'applied', 'dry_run' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_clear_test_rules',
			),
			array(
				'name'          => 'botblocker/get-edge-trust',
				'setting_key'   => 'bbcs_ability_get_edge_trust',
				'label'         => __( 'Get edge trust', 'botblocker-security' ),
				'description'   => __( 'Returns the identity of the current request, the auto-trusted edge entries, and optionally the proxy, crawler-network and auto-trust maps. current peer, client, claimed and subject, the map masks and the suggestion peer and PTR are masked; raw header values are never returned.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'risk'          => 'read',
				'pii'           => true,
				'public'        => false,
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'include_map' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, the proxy, bot-network, auto-trust and shared maps are loaded and masked.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'current'       => array(
							'type'                 => 'object',
							'properties'           => array(
								'peer'           => array( 'type' => 'string', 'description' => __( 'Transport address of the current request, masked.', 'botblocker-security' ) ),
								'client'         => array( 'type' => 'string', 'description' => __( 'Edge client address, masked.', 'botblocker-security' ) ),
								'claimed'        => array( 'type' => 'string', 'description' => __( 'Address claimed by the edge header, masked.', 'botblocker-security' ) ),
								'role'           => array( 'type' => 'string', 'description' => __( 'Resolved edge role code.', 'botblocker-security' ) ),
								'state'          => array( 'type' => 'string', 'description' => __( 'Resolved edge state code.', 'botblocker-security' ) ),
								'header'         => array( 'type' => 'string', 'description' => __( 'Edge header name; no header value is returned.', 'botblocker-security' ) ),
								'subject'        => array( 'type' => 'string', 'description' => __( 'Address the decision subject resolved to, masked.', 'botblocker-security' ) ),
								'subject_source' => array( 'type' => 'string', 'description' => __( 'Where the decision subject came from.', 'botblocker-security' ) ),
								'verified_by'    => array( 'type' => 'string', 'description' => __( 'Edge verification method.', 'botblocker-security' ) ),
							),
							'required'             => array( 'peer', 'client', 'claimed', 'role', 'state', 'header', 'subject', 'subject_source', 'verified_by' ),
							'additionalProperties' => false,
							'description'          => __( 'Resolved identity of the current request.', 'botblocker-security' ),
						),
						'autotrust'     => array(
							'type'                 => 'object',
							'properties'           => array(
								'enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether edge auto-trust is enabled.', 'botblocker-security' ) ),
								'entries' => array(
									'type'        => 'array',
									'description' => __( 'Active auto-trusted edge entries.', 'botblocker-security' ),
									'items'       => array(
										'type'                 => 'object',
										'properties'           => array(
											'mask'     => array( 'type' => 'string', 'description' => __( 'Auto-trusted CIDR mask, masked.', 'botblocker-security' ) ),
											'header'   => array( 'type' => 'string', 'description' => __( 'Edge header the entry was learned from.', 'botblocker-security' ) ),
											'expires'  => array( 'type' => 'string', 'description' => __( 'ISO 8601 expiry timestamp, or an empty string when the entry has no expiry.', 'botblocker-security' ) ),
											'evidence' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Evidence count recorded when the entry was trusted.', 'botblocker-security' ) ),
										),
										'required'             => array( 'mask', 'header', 'expires', 'evidence' ),
										'additionalProperties' => false,
									),
								),
							),
							'required'             => array( 'enabled', 'entries' ),
							'additionalProperties' => false,
							'description'          => __( 'Edge auto-trust state.', 'botblocker-security' ),
						),
						'maps'          => array(
							'type'                 => 'object',
							'properties'           => array(
								'proxy'       => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Configured proxy masks, masked; empty when include_map is false.', 'botblocker-security' ) ),
								'bot_network' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Configured crawler-network masks, masked; empty when include_map is false.', 'botblocker-security' ) ),
								'auto'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Auto-trusted masks, masked; empty when include_map is false.', 'botblocker-security' ) ),
								'shared'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Shared-infrastructure masks, masked; empty when include_map is false.', 'botblocker-security' ) ),
							),
							'required'             => array( 'proxy', 'bot_network', 'auto', 'shared' ),
							'additionalProperties' => false,
							'description'          => __( 'Trust maps; every list is empty when include_map is false.', 'botblocker-security' ),
						),
						'maps_included' => array( 'type' => 'boolean', 'description' => __( 'True when the trust maps were loaded; false when include_map is false and maps is empty.', 'botblocker-security' ) ),
						'unknown_edge'  => array( 'type' => 'boolean', 'description' => __( 'True when the current request resolves to an unknown or unresolved edge.', 'botblocker-security' ) ),
						'suggestion'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'found'    => array( 'type' => 'boolean', 'description' => __( 'Whether an auto-trust suggestion exists.', 'botblocker-security' ) ),
								'peer'     => array( 'type' => 'string', 'description' => __( 'Suggested edge address, masked; empty when no suggestion exists.', 'botblocker-security' ) ),
								'asn'      => array( 'type' => 'string', 'description' => __( 'Autonomous system number of the suggestion; empty when no suggestion exists.', 'botblocker-security' ) ),
								'asname'   => array( 'type' => 'string', 'description' => __( 'Autonomous system name of the suggestion; empty when no suggestion exists.', 'botblocker-security' ) ),
								'ptr'      => array( 'type' => 'string', 'description' => __( 'Reverse-DNS name of the suggestion, masked; empty when no suggestion exists.', 'botblocker-security' ) ),
								'header'   => array( 'type' => 'string', 'description' => __( 'Edge header of the suggestion; empty when no suggestion exists.', 'botblocker-security' ) ),
								'verified' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Verified hit count of the suggestion; 0 when no suggestion exists.', 'botblocker-security' ) ),
							),
							'required'             => array( 'found', 'peer', 'asn', 'asname', 'ptr', 'header', 'verified' ),
							'additionalProperties' => false,
							'description'          => __( 'Auto-trust suggestion for the current peer, if any.', 'botblocker-security' ) ),
					),
					'required'             => array( 'current', 'autotrust', 'maps', 'maps_included', 'unknown_edge', 'suggestion' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_edge_trust',
			),
			array(
				'name'          => 'botblocker/get-edge-evidence',
				'setting_key'   => 'bbcs_ability_get_edge_evidence',
				'label'         => __( 'Get edge evidence', 'botblocker-security' ),
				'description'   => __( 'Returns the stored evidence rows for unverified edges: peer, ASN, PTR, header, hit counters and the promotion thresholds. Row peer, suggestion peer and PTR names are masked; clients are pseudonymous ids and are not re-masked. Never prunes or writes. Returns error ability_unavailable when the evidence table is missing.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'risk'          => 'read',
				'pii'           => true,
				'public'        => false,
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'peer'            => array( 'type' => 'string', 'description' => __( 'Exact peer address to filter by; all rows when omitted.', 'botblocker-security' ) ),
						'limit'           => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20, 'description' => __( 'Maximum rows to return; values outside the range are clamped.', 'botblocker-security' ) ),
						'offset'          => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => __( 'Rows to skip, newest last_seen first.', 'botblocker-security' ) ),
						'include_clients' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, rows carry the pseudonymous client id prefixes.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rows'        => array(
							'type'        => 'array',
							'description' => __( 'Evidence rows, newest last_seen first.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'peer'             => array( 'type' => 'string', 'description' => __( 'Edge address, masked.', 'botblocker-security' ) ),
									'ip_version'       => array( 'type' => 'integer', 'enum' => array( 4, 6 ), 'description' => __( 'IP version: 4 or 6.', 'botblocker-security' ) ),
									'asn'              => array( 'type' => 'string', 'description' => __( 'Autonomous system number; empty when unknown.', 'botblocker-security' ) ),
									'asname'           => array( 'type' => 'string', 'description' => __( 'Autonomous system name; empty when unknown.', 'botblocker-security' ) ),
									'ptr'              => array( 'type' => 'string', 'description' => __( 'Reverse-DNS name, masked.', 'botblocker-security' ) ),
									'header'           => array( 'type' => 'string', 'description' => __( 'Edge header the peer was seen with.', 'botblocker-security' ) ),
									'hits'             => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Requests recorded for this peer.', 'botblocker-security' ) ),
									'bot_hits'         => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Requests recorded as bots for this peer.', 'botblocker-security' ) ),
									'verified_hits'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Requests with a verified reverse DNS for this peer.', 'botblocker-security' ) ),
									'distinct_clients' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of pseudonymous clients seen behind this peer.', 'botblocker-security' ) ),
									'first_seen'       => array( 'type' => 'string', 'description' => __( 'ISO 8601 time the peer was first recorded.', 'botblocker-security' ) ),
									'last_seen'        => array( 'type' => 'string', 'description' => __( 'ISO 8601 time the peer was last recorded.', 'botblocker-security' ) ),
									'state'            => array( 'type' => 'string', 'description' => __( 'Edge state code stored for this peer.', 'botblocker-security' ) ),
									'signature'        => array( 'type' => 'string', 'description' => __( 'Bot signature stored for this peer; empty when none.', 'botblocker-security' ) ),
									'clients'          => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Pseudonymous client ids; addresses are never stored. Present only when include_clients is true.', 'botblocker-security' ) ),
								),
								'required'             => array( 'peer', 'ip_version', 'asn', 'asname', 'ptr', 'header', 'hits', 'bot_hits', 'verified_hits', 'distinct_clients', 'first_seen', 'last_seen', 'state', 'signature' ),
								'additionalProperties' => false,
							),
						),
						'threshold'   => array(
							'type'                 => 'object',
							'properties'           => array(
								'clients'     => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Distinct clients a peer needs before it can be promoted.', 'botblocker-security' ) ),
								'hits'        => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Verified hits a peer needs before it can be promoted.', 'botblocker-security' ) ),
								'window_days' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Window length in days used by the promotion check.', 'botblocker-security' ) ),
								'met'         => array( 'type' => 'boolean', 'description' => __( 'Whether at least one returned row meets the thresholds.', 'botblocker-security' ) ),
							),
							'required'             => array( 'clients', 'hits', 'window_days', 'met' ),
							'additionalProperties' => false,
							'description'          => __( 'Promotion thresholds and whether a row met them.', 'botblocker-security' ) ),
						'suggestions' => array(
							'type'        => 'array',
							'description' => __( 'Peers proposed for auto-trust.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'peer'     => array( 'type' => 'string', 'description' => __( 'Suggested edge address, masked.', 'botblocker-security' ) ),
									'asn'      => array( 'type' => 'string', 'description' => __( 'Autonomous system number of the suggestion.', 'botblocker-security' ) ),
									'asname'   => array( 'type' => 'string', 'description' => __( 'Autonomous system name of the suggestion.', 'botblocker-security' ) ),
									'ptr'      => array( 'type' => 'string', 'description' => __( 'Reverse-DNS name of the suggestion, masked.', 'botblocker-security' ) ),
									'header'   => array( 'type' => 'string', 'description' => __( 'Edge header of the suggestion.', 'botblocker-security' ) ),
									'verified' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Verified hit count of the suggestion.', 'botblocker-security' ) ),
								),
								'required'             => array( 'peer', 'asn', 'asname', 'ptr', 'header', 'verified' ),
								'additionalProperties' => false,
							),
						),
					),
					'required'             => array( 'rows', 'threshold', 'suggestions' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_edge_evidence',
			),
			array(
				'name'          => 'botblocker/explain-ban-decision',
				'setting_key'   => 'bbcs_ability_explain_ban_decision',
				'label'         => __( 'Explain a ban decision', 'botblocker-security' ),
				'description'   => __( 'Runs the ban gate for a target without writing anything and reports whether a ban would be written and why. It uses the current request identity for any field the caller omits and never resolves a PTR for the target; target is returned unmasked.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'risk'          => 'read',
				'public'        => false,
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'target'            => array( 'type' => 'string', 'description' => __( 'Address the ban gate is asked about.', 'botblocker-security' ) ),
						'peer'              => array( 'type' => 'string', 'description' => __( 'Transport address of the request; the current peer when omitted.', 'botblocker-security' ) ),
						'role'              => array( 'type' => 'string', 'enum' => array( 'direct', 'edge', 'edge_auto', 'bot_network', 'unknown_edge', 'invalid', '' ), 'description' => __( 'Edge role code; the resolved current role when omitted, and an empty string means unresolved.', 'botblocker-security' ) ),
						'edge_state'        => array( 'type' => 'string', 'enum' => array( 'direct', 'edge', 'edge_unresolved', 'trusted_edge', 'unknown_edge', 'bot_network', 'invalid', '' ), 'description' => __( 'Edge state code; the resolved current state when omitted, and an empty string means unresolved.', 'botblocker-security' ) ),
						'header'            => array( 'type' => 'string', 'description' => __( 'Edge header name; the current header when omitted.', 'botblocker-security' ) ),
						'proxy_detected'    => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'True when the request is a detected classic proxy.', 'botblocker-security' ) ),
						'edge_shared'       => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'True when the edge is shared infrastructure; when false the shared map is still consulted.', 'botblocker-security' ) ),
						'block_proxy_users' => array( 'type' => 'boolean', 'description' => __( 'Whether proxy users may be banned; defaults to the stored setting.', 'botblocker-security' ) ),
					),
					'required'             => array( 'target' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'ban_allowed' => array( 'type' => 'boolean', 'description' => __( 'True when the ban gate would write a ban for the target.', 'botblocker-security' ) ),
						'reason'      => array( 'type' => 'string', 'enum' => array( 'ok', 'ok_clamped', 'target', 'mapped', 'classic_proxy', 'role', 'state', 'bot_ptr', 'auto_edge_target', 'clamp', 'unknown_target', 'shared_edge', 'identity', 'unknown_edge' ), 'description' => __( 'Ban gate reason code; one of the enum values.', 'botblocker-security' ) ),
						'target'      => array( 'type' => 'string', 'description' => __( 'Target address, unmasked.', 'botblocker-security' ) ),
						'clamp'       => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Maximum ban TTL in seconds applied by the gate; 0 when not clamped.', 'botblocker-security' ) ),
						'evidence'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'peer_ptr'          => array( 'type' => 'string', 'description' => __( 'Reverse-DNS name of the peer, masked and truncated; empty when the peer is not the current peer.', 'botblocker-security' ) ),
								'peer_asn'          => array( 'type' => 'string', 'description' => __( 'Autonomous system number of the peer; empty when the peer is not the current peer.', 'botblocker-security' ) ),
								'shared'            => array( 'type' => 'boolean', 'description' => __( 'Whether the target matched a proxy or crawler-network map.', 'botblocker-security' ) ),
								'map_match'         => array( 'type' => 'boolean', 'description' => __( 'Whether the target matched the proxy map.', 'botblocker-security' ) ),
								'bot_network_match' => array( 'type' => 'boolean', 'description' => __( 'Whether the target matched the crawler-network map.', 'botblocker-security' ) ),
								'bot_ptr_match'     => array( 'type' => 'boolean', 'description' => __( 'Whether the gate denied the target as a verified search-engine crawler.', 'botblocker-security' ) ),
							),
							'required'             => array( 'peer_ptr', 'peer_asn', 'shared', 'map_match', 'bot_network_match', 'bot_ptr_match' ),
							'additionalProperties' => false,
							'description'          => __( 'Evidence collected while the gate ran.', 'botblocker-security' ) ),
						'would_ban'   => array( 'type' => 'boolean', 'description' => __( 'True when a ban row would be written for the resolved target.', 'botblocker-security' ) ),
					),
					'required'             => array( 'ban_allowed', 'reason', 'target', 'clamp', 'evidence', 'would_ban' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_explain_ban_decision',
			),
		);
	}
}
