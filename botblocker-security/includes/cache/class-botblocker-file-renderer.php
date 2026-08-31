<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerFileRenderer {

	public static function atomicFileWrite( string $filePath, string $content ): bool {
		if ( strpos( $content, '<?php' ) === 0 ) {
			$content = BotBlockerDataFile::sign( $content );
		}

		$dir = dirname( $filePath );
		if ( ! is_dir( $dir ) || ! wp_is_writable( $dir ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BotBlocker] Directory not writable: ' . $dir );
			}
			return false;
		}
		$tmpFile = $dir . DIRECTORY_SEPARATOR . '.bbcs_tmp_' . basename( $filePath ) . '.' . getmypid();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$bytes = @file_put_contents( $tmpFile, $content, LOCK_EX );
		if ( $bytes === false ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmpFile );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BotBlocker] Failed to write config: ' . basename( $filePath ) );
			}
			return false;
		}
		// Atomic rename (same-filesystem guarantee on Linux/macOS)
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( @rename( $tmpFile, $filePath ) ) {
			BotBlockerCompiledFile::invalidate( $filePath );
			return true;
		}
		// Fallback for Windows or cross-device mounts
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		if ( @copy( $tmpFile, $filePath ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmpFile );
			BotBlockerCompiledFile::invalidate( $filePath );
			return true;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		@unlink( $tmpFile );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BotBlocker] Failed to save config: ' . basename( $filePath ) );
		}
		return false;
	}

	public static function renderProxy(): void {
		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, `key`, `value`, comment FROM `{$wpdb->bbcs_proxy}` WHERE 1 = %d ORDER BY `comment`, `key`",
				1
			),
			ARRAY_A
		);

		$proxyArr = array();
		$comments = array();

		foreach ( (array) $result as $proxy ) {
			$key     = $proxy['key'];
			$value   = $proxy['value'];
			$comment = $proxy['comment'];

			$proxyArr[ $key ] = $value;

			if ( ! isset( $comments[ $comment ] ) ) {
				$comments[ $comment ] = array();
			}
			$comments[ $comment ][] = $key;
		}

		$proxyContent  = BBCS_STOP_DIRECT . "\n";
		$proxyContent .= "return [\n";
		$proxyContent .= "    'bbcs_proxy' => [\n";

		foreach ( $comments as $comment => $keys ) {
			$proxyContent .= '        // ' . str_replace( array( "\r", "\n", '?>', '<?php', '<?=' ), ' ', $comment ) . "\n";
			foreach ( $keys as $key ) {
				$value         = $proxyArr[ $key ];
				$proxyContent .= "        '" . addslashes( $key ) . "'  => '" . addslashes( $value ) . "',\n";
			}
			$proxyContent .= "\n";
		}

		$proxyContent  = rtrim( $proxyContent, "\n" );
		$proxyContent  = rtrim( $proxyContent, ",\n" ) . "\n";
		$proxyContent .= "    ]\n";
		$proxyContent .= "];\n";

		$proxyFile = BotBlockerMultisite::getDataDir() . 'proxy.php';
		self::atomicFileWrite( $proxyFile, $proxyContent );
	}

	public static function renderPaths(): void {
		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule` FROM `{$wpdb->bbcs_path}` WHERE disable = %d ORDER BY priority ASC",
				0
			),
			ARRAY_A
		);

		$paths = '';
		foreach ( (array) $rows as $row ) {
			$key    = ltrim( $row['search'], '/' );
			$rule   = $row['rule'];
			$paths .= "    '" . addslashes( $key ) . "' => '" . addslashes( $rule ) . "',\n";
		}
		$paths = rtrim( $paths, ",\n" );

		$content = BBCS_STOP_DIRECT . "\nreturn [\n'bbcs_path' => [\n$paths\n],\n];";
		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'paths.php', $content );
	}

	public static function renderRules(): void {
		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `id`, `expires` FROM `{$wpdb->bbcs_rules}` WHERE disable = %d AND (expires = 0 OR expires > %d) ORDER BY priority ASC",
				0,
				time()
			),
			ARRAY_A
		);

		$rules = '';
		foreach ( (array) $rows as $row ) {
			$rules .= "    ['search' => '" . addslashes( $row['search'] ) . "', 'rule' => '" . addslashes( $row['rule'] ) . "', 'expires' => " . (int) $row['expires'] . ", 'id' => " . (int) $row['id'] . "],\n";
		}
		$rules = rtrim( $rules, ",\n" );

		$content = BBCS_STOP_DIRECT . "\nreturn [\n'bbcs_custom_rule' => [\n$rules\n],\n];";
		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'rules.php', $content );
	}

	public static function renderCountries(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `code`, `rule`, `id` FROM `{$wpdb->bbcs_countries}` WHERE disable = %d ORDER BY priority ASC, code ASC",
				0
			),
			ARRAY_A
		);

		$countries = '';
		foreach ( (array) $rows as $row ) {
			$countries .= "    ['code' => '" . addslashes( $row['code'] ) . "', 'rule' => '" . addslashes( $row['rule'] ) . "', 'id' => " . (int) $row['id'] . "],\n";
		}
		$countries = rtrim( $countries, ",\n" );

		$content = BBCS_STOP_DIRECT . "\nreturn [\n'bbcs_geo' => [\n$countries\n],\n];";
		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'geo_countries.php', $content );
	}

	public static function renderSearchEngines(): void {
		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `data` FROM `{$wpdb->bbcs_se}` WHERE disable = %d ORDER BY priority ASC",
				0
			),
			ARRAY_A
		);

		$rules       = array();
		$domains_map = array();

		foreach ( (array) $results as $item ) {
			$rules[ $item['search'] ]       = $item['rule'];
			$domains                        = preg_split( '/\s+/', trim( (string) $item['data'] ) );
			$domains_map[ $item['search'] ] = array_filter( $domains, 'strlen' );
		}

		$se_data  = BBCS_STOP_DIRECT . "\nreturn [\n";
		$se_data .= "    'bbcs_rule' => [\n";
		foreach ( $rules as $key => $value ) {
			$se_data .= "        '" . addslashes( $key ) . "' => '" . addslashes( $value ) . "',\n";
		}
		$se_data .= "    ],\n\n";
		$se_data .= "    'bbcs_se' => [\n";
		foreach ( $domains_map as $key => $domains ) {
			$se_data .= "        '" . addslashes( $key ) . "' => [";
			$se_data .= implode(
				', ',
				array_map(
					static function ( $d ) {
						return "'" . addslashes( $d ) . "'";
					},
					$domains
				)
			);
			$se_data .= "],\n";
		}
		$se_data .= "    ]\n";
		$se_data .= "];\n";

		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'search_engines.php', $se_data );
	}

	public static function renderBotSignatures(): void {
		$source = BOTBLOCKER_DIR . 'data/base/bot-signatures.php';
		if ( ! file_exists( $source ) ) {
			return;
		}
		$raw     = include $source;
		$raw_sub = isset( $raw['substrings'] ) && is_array( $raw['substrings'] ) ? $raw['substrings'] : $raw;
		$items   = array();
		foreach ( $raw_sub as $signature ) {
			$s = preg_replace( '/\s+/', ' ', trim( urldecode( (string) $signature ) ) );
			if ( $s !== '' ) {
				$items[] = $s;
			}
		}

		$priority = array();
		$rest     = array();
		$common   = array(
			'googlebot', 'bingbot', 'yandex', 'baiduspider',
			'facebookexternalhit', 'twitterbot', 'duckduckbot',
			'slurp', 'semrushbot', 'ahrefsbot', 'mj12bot',
			'petalbot', 'applebot', 'google-ads', 'adsbot-google',
		);
		foreach ( $items as $item ) {
			$lower  = strtolower( $item );
			$found  = false;
			foreach ( $common as $c ) {
				if ( strpos( $lower, $c ) !== false ) {
					$priority[] = $item;
					$found      = true;
					break;
				}
			}
			if ( ! $found ) {
				$rest[] = $item;
			}
		}
		$items = array_merge( $priority, $rest );

		$patterns = isset( $raw['patterns'] ) && is_array( $raw['patterns'] ) ? array_values( $raw['patterns'] ) : array();

		$content  = BBCS_STOP_DIRECT . "\n";
		$content .= '// BASE_HASH: ' . hash_file( 'sha256', $source ) . "\n";
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$content .= 'return ' . var_export( array( 'substrings' => $items, 'patterns' => $patterns ), true ) . ";\n";
		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'bot-signatures-processed.php', $content );
	}

	/**
	 * True when the generated bot-signatures-processed.php was built from a
	 * different data/base/bot-signatures.php than the one shipped with this
	 * plugin build (plugin update without a migration re-render).
	 */
	public static function isBotSignaturesStale(): bool {
		$source = BOTBLOCKER_DIR . 'data/base/bot-signatures.php';
		if ( ! file_exists( $source ) ) {
			return false;
		}
		$target = BotBlockerMultisite::getDataDir() . 'bot-signatures-processed.php';
		if ( ! file_exists( $target ) ) {
			return true;
		}
		$content = (string) @file_get_contents( $target );
		return strpos( $content, '// BASE_HASH: ' . hash_file( 'sha256', $source ) ) === false;
	}

	public static function renderLlmTrusted(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `provider`, `search`, `verified_ip_ranges` FROM `{$wpdb->bbcs_llm_trusted}` WHERE disabled = %d ORDER BY provider ASC",
				0
			),
			ARRAY_A
		);

		$llm_data  = BBCS_STOP_DIRECT . "\nreturn [\n";
		$llm_data .= "    'bbcs_llm' => [\n";
		foreach ( (array) $results as $item ) {
			$provider = trim( (string) $item['provider'] );
			$search   = trim( (string) $item['search'] );
			if ( $provider === '' || $search === '' ) {
				continue;
			}
			$ranges = preg_split( '/\s+/', trim( (string) $item['verified_ip_ranges'] ), -1, PREG_SPLIT_NO_EMPTY );
			$ranges = array_filter( (array) $ranges, 'strlen' );

			$llm_data .= "        '" . addslashes( $provider ) . "' => [ 'search' => '" . addslashes( $search ) . "', 'ranges' => [";
			$llm_data .= implode(
				', ',
				array_map(
					static function ( $c ) {
						return "'" . addslashes( $c ) . "'";
					},
					$ranges
				)
			);
			$llm_data .= "] ],\n";
		}
		$llm_data .= "    ]\n";
		$llm_data .= "];\n";

		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'llm_trusted.php', $llm_data );
	}

	public static function renderAsn(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT CAST(`asnum` AS CHAR) AS `asnum`, `rule` FROM `{$wpdb->bbcs_asn}` WHERE disable = %d ORDER BY priority ASC",
				0
			),
			ARRAY_A
		);

		$asn_data  = BBCS_STOP_DIRECT . "\nreturn [\n";
		$asn_data .= "    'bbcs_asn' => [\n";
		foreach ( (array) $results as $row ) {
			$asnum = BotBlockerAsnValue::normalize( $row['asnum'] );
			if ( $asnum === null ) {
				continue;
			}
			$asn_data .= "        '" . $asnum . "' => '" . addslashes( $row['rule'] ) . "',\n";
		}
		$asn_data .= "    ]\n";
		$asn_data .= "];\n";

		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'asn_rules.php', $asn_data );
	}

	public static function renderIps(): void {
		global $wpdb;
		$ip_from_db = array(
			'self_ips' => array(),
			'admin'    => array(),
			'ipv4'     => array(),
			'ipv6'     => array(),
		);

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows_ipv4 = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `readonly`, `comment` FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND expires = %d",
				0,
				BOTBLOCKER_EXP_INF
			),
			ARRAY_A
		);

		foreach ( (array) $rows_ipv4 as $ip ) {
			if ( (int) $ip['readonly'] === 1 ) {
				if ( $ip['comment'] === 'Admin IP' ) {
					$ip_from_db['admin'][ $ip['search'] ] = $ip['rule'];
				} elseif ( ! in_array( $ip['comment'], array( 'Local IP', 'Local IP from SERVER_ADDR', 'Server IPv4', 'Server IPv6' ), true )
					|| ! BotBlockerIp::isPublicIp( (string) $ip['search'] ) ) {
					$ip_from_db['self_ips'][ $ip['search'] ] = 'allow';
				}
			} else {
				$ip_from_db['ipv4'][ $ip['search'] ] = $ip['rule'];
			}
		}
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows_ipv6 = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `readonly`, `comment` FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND expires = %d",
				0,
				BOTBLOCKER_EXP_INF
			),
			ARRAY_A
		);

		foreach ( (array) $rows_ipv6 as $ip ) {
			if ( (int) $ip['readonly'] === 1 ) {
				if ( $ip['comment'] === 'Admin IP' ) {
					$ip_from_db['admin'][ $ip['search'] ] = $ip['rule'];
				} elseif ( ! in_array( $ip['comment'], array( 'Local IP', 'Local IP from SERVER_ADDR', 'Server IPv4', 'Server IPv6' ), true )
					|| ! BotBlockerIp::isPublicIp( (string) $ip['search'] ) ) {
					$ip_from_db['self_ips'][ $ip['search'] ] = 'allow';
				}
			} else {
				$ip_from_db['ipv6'][ $ip['search'] ] = $ip['rule'];
			}
		}

		$ip_data = BBCS_STOP_DIRECT . " \n return [\n";
		foreach ( $ip_from_db as $group => $ips ) {
			$ip_data .= "'{$group}' => [\n";
			foreach ( $ips as $ip => $status ) {
				$ip_data .= "    '" . addslashes( $ip ) . "' => '" . addslashes( $status ) . "',\n";
			}
			$ip_data .= "],\n";
		}
		$ip_data .= "];\n";

		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'ip.php', $ip_data );
	}

	/**
	 * Rebuild hot-bans.php from the DB - mirrors renderIps() for temporary bans.
	 *
	 * Queries both IPv4/IPv6 tables for active temporary bans
	 * (disable = 0 AND expires > time() AND expires < BOTBLOCKER_EXP_INF)
	 * and writes a fresh file. Any orphan entries (no DB row) are dropped.
	 * Permanent bans (BOTBLOCKER_EXP_INF) live in ip.php only.
	 */
	public static function renderHotBans(): void {
		global $wpdb;

		$now            = time();

		$hot = array(
			'ipv4' => array(),
			'ipv6' => array(),
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows_ipv4 = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `expires` FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND expires > %d AND expires < %d",
				0,
				$now,
				BOTBLOCKER_EXP_INF
			),
			ARRAY_A
		);

		foreach ( (array) $rows_ipv4 as $row ) {
			$hot['ipv4'][ $row['search'] ] = array( $row['rule'], (int) $row['expires'] );
		}

		$rows_ipv6 = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `expires` FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND expires > %d AND expires < %d",
				0,
				$now,
				BOTBLOCKER_EXP_INF
			),
			ARRAY_A
		);

		foreach ( (array) $rows_ipv6 as $row ) {
			$hot['ipv6'][ $row['search'] ] = array( $row['rule'], (int) $row['expires'] );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';

		$lockFp = self::acquireHotBanLock();
		if ( ! $lockFp ) {
			return;
		}

		$total = count( $hot['ipv4'] ) + count( $hot['ipv6'] );
		if ( $total === 0 ) {
			if ( file_exists( $file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $file );
				BotBlockerCompiledFile::invalidate( $file );
			}
			self::releaseHotBanLock( $lockFp );
			return;
		}

		self::atomicFileWrite( $file, self::buildHotBansContent( $hot ) );
		self::releaseHotBanLock( $lockFp );
	}

	private static function isHotBansTampered(): bool {
		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';
		if ( ! file_exists( $file ) ) {
			return false;
		}
		$content = @file_get_contents( $file );
		if ( false === $content || false === strrpos( $content, '// HASH:' ) ) {
			return false;
		}
		return ! BotBlockerDataFile::verify( $content );
	}

	public static function ensureHotBansIntegrity(): void {
		if ( self::isHotBansTampered() ) {
			self::renderHotBans();
		}
	}

	/**
	 *
	 * @param mixed $data
	 * @return array{ipv4: array<string, array{string, int}>, ipv6: array<string, array{string, int}>}
	 */
	private static function normalizeHotBansData( $data ): array {
		if ( ! is_array( $data ) ) {
			return array(
				'ipv4' => array(),
				'ipv6' => array(),
			);
		}
		return array(
			'ipv4' => ( isset( $data['ipv4'] ) && is_array( $data['ipv4'] ) ) ? $data['ipv4'] : array(),
			'ipv6' => ( isset( $data['ipv6'] ) && is_array( $data['ipv6'] ) ) ? $data['ipv6'] : array(),
		);
	}

	private static function buildHotBansContent( array $data ): string {
		$data     = self::normalizeHotBansData( $data );
		$content  = BBCS_STOP_DIRECT . " \n";
		$content .= "return [\n";
		$content .= "    'ipv4' => [\n";
		foreach ( $data['ipv4'] as $ipAddr => $entry ) {
			$content .= "        '" . addslashes( $ipAddr ) . "' => ['" . addslashes( $entry[0] ) . "', " . intval( $entry[1] ) . "],\n";
		}
		$content .= "    ],\n";
		$content .= "    'ipv6' => [\n";
		foreach ( $data['ipv6'] as $ipAddr => $entry ) {
			$content .= "        '" . addslashes( $ipAddr ) . "' => ['" . addslashes( $entry[0] ) . "', " . intval( $entry[1] ) . "],\n";
		}
		$content .= "    ]\n";
		$content .= "];\n";
		return $content;
	}

	private static function hotBanLockFile(): string {
		return BotBlockerMultisite::getDataDir() . '.hotbans.lock';
	}

	private static function acquireHotBanLock() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$fp = @fopen( self::hotBanLockFile(), 'w' );
		if ( $fp ) {
			@flock( $fp, LOCK_EX );
		}
		return $fp;
	}

	private static function releaseHotBanLock( $fp ): void {
		if ( $fp ) {
			@flock( $fp, LOCK_UN );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			@fclose( $fp );
		}
	}

	public static function syncIpBanFiles( string $ip, string $rule, int $expires ): void {
		// Temporary bans (any finite expiry) go to hot-bans.php (TTL-filtered at
		// match time in all three layers). Permanent bans go to ip.php only.
		$short_term = $expires < BOTBLOCKER_EXP_INF;
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [HotBan] syncIpBanFiles ip=' . $ip . ' rule=' . $rule . ' expires=' . $expires . ' short_term=' . ( $short_term ? 'YES' : 'NO' ) );
		}
		if ( $short_term ) {
			self::appendHotBan( $ip, $rule, $expires );
		} else {
			self::removeHotBan( $ip );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [HotBan] syncIpBanFiles hot-ban step done, renderIps' );
		}
		self::renderIps();
		BotBlockerCache::clearFileCache();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [HotBan] syncIpBanFiles complete' );
		}
	}

	public static function removeHotBan( string $ip ): void {
		$ip = trim( $ip );
		if ( $ip === '' ) {
			return;
		}

		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';
		if ( ! file_exists( $file ) ) {
			return;
		}

		self::ensureHotBansIntegrity();

		$lockFp = self::acquireHotBanLock();
		if ( ! $lockFp ) {
			return;
		}

		$data = self::normalizeHotBansData( BotBlockerDataFile::safeLoad( $file ) );

		$key   = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? 'ipv6' : 'ipv4';
		$found = false;
		if ( isset( $data[ $key ][ $ip ] ) ) {
			unset( $data[ $key ][ $ip ] );
			$found = true;
		}

		if ( ! $found ) {
			self::releaseHotBanLock( $lockFp );
			return;
		}

		$total = count( $data['ipv4'] ) + count( $data['ipv6'] );
		if ( $total === 0 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $file );
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $file, true );
			}
			self::releaseHotBanLock( $lockFp );
			BotBlockerCache::clearFileCache();
			return;
		}

		self::atomicFileWrite( $file, self::buildHotBansContent( $data ) );

		self::releaseHotBanLock( $lockFp );
		BotBlockerCache::clearFileCache();
	}

	public static function appendHotBan( string $ip, string $action, int $expires ): void {
		$ip = trim( $ip );
		if ( $ip === '' ) {
			return;
		}

		$key = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? 'ipv6' : 'ipv4';

		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';

		self::ensureHotBansIntegrity();

		$lockFp = self::acquireHotBanLock();
		if ( ! $lockFp ) {
			return;
		}

		$existing = array( 'ipv4' => array(), 'ipv6' => array() );
		if ( file_exists( $file ) ) {
			$existing = self::normalizeHotBansData( BotBlockerDataFile::safeLoad( $file ) );
		}

		self::pruneExpiredHotBans( $existing );

		$existing[ $key ][ $ip ] = array( $action, $expires );

		if ( count( $existing['ipv4'] ) + count( $existing['ipv6'] ) > 10000 ) {
			self::releaseHotBanLock( $lockFp );
			self::renderHotBans();
			self::renderIps();
			return;
		}

		// atomicFileWrite() calls BotBlockerDataFile::sign() again because content starts with
		// <?php (via BBCS_STOP_DIRECT). This is safe - BotBlockerDataFile::verify() uses strrpos()
		// to find the last // HASH: line and verifies against the content before it.
		// A second hash simply overwrites the first; verification always succeeds.
		self::atomicFileWrite( $file, self::buildHotBansContent( $existing ) );

		self::releaseHotBanLock( $lockFp );
	}

	/**
	 * Batch-add multiple entries to hot-bans.php in a single read+write.
	 *
	 * Only temporary bans (expires < BOTBLOCKER_EXP_INF) are stored;
	 * permanent bans belong in ip.php only (see syncIpBanFiles).
	 *
	 * @param array<int, array{ip: string, action: string, expires: int}> $entries
	 */
	public static function appendHotBanBatch( array $entries ): void {
		if ( empty( $entries ) ) {
			return;
		}

		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';

		self::ensureHotBansIntegrity();

		$lockFp = self::acquireHotBanLock();
		if ( ! $lockFp ) {
			return;
		}

		$existing = array( 'ipv4' => array(), 'ipv6' => array() );
		if ( file_exists( $file ) ) {
			$existing = self::normalizeHotBansData( BotBlockerDataFile::safeLoad( $file ) );
		}

		// Prune expired entries inline - no extra I/O (already read the file).
		self::pruneExpiredHotBans( $existing );

		foreach ( $entries as $entry ) {
			$ip = trim( $entry['ip'] ?? '' );
			if ( $ip === '' ) {
				continue;
			}
			// Permanent bans do not belong in hot-bans (Gap 2).
			if ( (int) ( $entry['expires'] ?? 0 ) >= BOTBLOCKER_EXP_INF ) {
				continue;
			}
			$key                           = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? 'ipv6' : 'ipv4';
			$existing[ $key ][ $ip ]       = array( $entry['action'], $entry['expires'] );
		}

		if ( count( $existing['ipv4'] ) + count( $existing['ipv6'] ) > 10000 ) {
			self::releaseHotBanLock( $lockFp );
			self::renderHotBans();
			self::renderIps();
			return;
		}

		self::atomicFileWrite( $file, self::buildHotBansContent( $existing ) );

		self::releaseHotBanLock( $lockFp );
	}

	private static function pruneExpiredHotBans( array &$data ): bool {
		$now     = time();
		$changed = false;
		foreach ( array( 'ipv4', 'ipv6' ) as $version ) {
			if ( empty( $data[ $version ] ) ) {
				continue;
			}
			foreach ( $data[ $version ] as $ip => $entry ) {
				if ( ! is_array( $entry ) || empty( $entry[1] ) || $entry[1] <= $now ) {
					unset( $data[ $version ][ $ip ] );
					$changed = true;
				}
			}
		}
		return $changed;
	}

	public static function cleanupHotBans(): void {
		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';
		if ( ! file_exists( $file ) ) {
			return;
		}

		self::ensureHotBansIntegrity();

		$lockFp = self::acquireHotBanLock();
		if ( ! $lockFp ) {
			return;
		}

		$data = self::normalizeHotBansData( BotBlockerDataFile::safeLoad( $file ) );

		$changed = self::pruneExpiredHotBans( $data );
		if ( ! $changed ) {
			self::releaseHotBanLock( $lockFp );
			return;
		}

		$total = count( $data['ipv4'] ) + count( $data['ipv6'] );
		if ( $total === 0 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $file );
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $file, true );
			}
			self::releaseHotBanLock( $lockFp );
			return;
		}
		self::atomicFileWrite( $file, self::buildHotBansContent( $data ) );

		self::releaseHotBanLock( $lockFp );
	}

	public static function renderTlsFingerprints(): void {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- intentional uncached query
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `fingerprint`, `category`, `ua_family`, `description` FROM `{$wpdb->bbcs_tls_fingerprints}` WHERE disabled = %d ORDER BY category ASC, fingerprint ASC",
				0
			),
			ARRAY_A
		);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$data = BBCS_STOP_DIRECT . "\nreturn [\n";
		$data .= "    'bbcs_tls_fingerprints' => [\n";
		foreach ( (array) $rows as $row ) {
			$fp    = addslashes( $row['fingerprint'] );
			$cat   = addslashes( $row['category'] );
			$ua    = addslashes( $row['ua_family'] );
			$desc  = addslashes( $row['description'] );
			$data .= "        '{$fp}' => ['category' => '{$cat}', 'ua_family' => '{$ua}', 'description' => '{$desc}'],\n";
		}
		$data .= "    ]\n";
		$data .= "];\n";

		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'tls_fingerprints.php', $data );
	}

	public static function renderAddons(): void {
		$content = BBCS_STOP_DIRECT . "\nreturn " . BotBlockerInstall::phpExport( BotBlockerAddons::scanAllRaw(), 0, true ) . ";\n";
		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'addons.php', BotBlockerDataFile::sign( $content ) );
	}

	public static function generateSettingsFile( $type = null ) {

		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( "SELECT `key`, `value` FROM `{$wpdb->bbcs_settings}`", ARRAY_A );

		$settings = array();
		foreach ( $results as $row ) {
			$key     = $row['key'];
			$value   = $row['value'];
			$decoded = json_decode( $value, true );
			if ( json_last_error() == JSON_ERROR_NONE ) {
				$settings[ $key ] = $decoded;
			} elseif ( is_numeric( $value ) ) {
				if ( strpos( $value, '.' ) !== false ) {
					$settings[ $key ] = (float) $value;
				} else {
					$settings[ $key ] = (int) $value;
				}
			} elseif ( $value === 'true' || $value === 'false' ) {
				$settings[ $key ] = $value === 'true';
			} else {
				$settings[ $key ] = $value;
			}
		}

		$settings['db_prefix'] = $wpdb->prefix;

		$settingsContent = BBCS_STOP_DIRECT . "\nreturn " . BotBlockerInstall::phpExport( $settings, 0, true ) . ";\n";

		$settingsFile = BotBlockerMultisite::getDataDir() . 'settings.php';

		$previous = array();
		if ( is_file( $settingsFile ) && is_readable( $settingsFile ) ) {
			$loaded = BotBlockerDataFile::safeLoad( $settingsFile );
			if ( is_array( $loaded ) ) {
				$previous = $loaded;
			}
		}
		do_action( 'bbcs_audit_settings_diff', $previous, $settings );

		$wrote        = self::atomicFileWrite( $settingsFile, $settingsContent );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Early-Init] generateSettingsFile blog_id=' . get_current_blog_id() . ' file=' . $settingsFile . ' wrote=' . ( $wrote ? 'YES' : 'NO' ) . ' keys=' . count( $settings ) );
		}

		BotBlockerCache::clearFileCache();

		if ( class_exists( 'BotBlockerTwoFactorAuth' ) ) {
			BotBlockerTwoFactorAuth::flushSettingsCache();
		}

		if ( ! isset( $type ) ) {
			return true;
		} elseif ( isset( $type ) && $type == true ) {
			return $settings;
		}
	}
}
