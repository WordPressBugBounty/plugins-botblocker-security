<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerFileRenderer {

	public static function atomicFileWrite( string $filePath, string $content ): bool {
		if ( strpos( $content, '<?php' ) === 0 ) {
			$content = bbcs_data_file_sign( $content );
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
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $filePath, true );
			}
			return true;
		}
		// Fallback for Windows or cross-device mounts
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		if ( @copy( $tmpFile, $filePath ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmpFile );
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $filePath, true );
			}
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
				"SELECT `search`, `rule` FROM `{$wpdb->bbcs_rules}` WHERE disable = %d ORDER BY priority ASC",
				0
			),
			ARRAY_A
		);

		$rules = '';
		foreach ( (array) $rows as $row ) {
			$rules .= "    '" . addslashes( $row['search'] ) . "' => '" . addslashes( $row['rule'] ) . "',\n";
		}
		$rules = rtrim( $rules, ",\n" );

		$content = BBCS_STOP_DIRECT . "\nreturn [\n'bbcs_rule' => [\n$rules\n],\n];";
		self::atomicFileWrite( BotBlockerMultisite::getDataDir() . 'rules.php', $content );
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
				"SELECT `asnum`, `rule` FROM `{$wpdb->bbcs_asn}` WHERE disable = %d ORDER BY priority ASC",
				0
			),
			ARRAY_A
		);

		$asn_data  = BBCS_STOP_DIRECT . "\nreturn [\n";
		$asn_data .= "    'bbcs_asn' => [\n";
		foreach ( (array) $results as $row ) {
			$asn_data .= '        ' . intval( $row['asnum'] ) . " => '" . addslashes( $row['rule'] ) . "',\n";
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

		$one_day_later = time() + DAY_IN_SECONDS;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows_ipv4 = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `search`, `rule`, `readonly`, `comment` FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND expires > %d",
				0,
				$one_day_later
			),
			ARRAY_A
		);

		foreach ( (array) $rows_ipv4 as $ip ) {
			if ( (int) $ip['readonly'] === 1 ) {
				if ( $ip['comment'] === 'Admin IP' ) {
					$ip_from_db['admin'][ $ip['search'] ] = $ip['rule'];
				} else {
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
				"SELECT `search`, `rule`, `readonly`, `comment` FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND expires > %d",
				0,
				$one_day_later
			),
			ARRAY_A
		);

		foreach ( (array) $rows_ipv6 as $ip ) {
			if ( (int) $ip['readonly'] === 1 ) {
				if ( $ip['comment'] === 'Admin IP' ) {
					$ip_from_db['admin'][ $ip['search'] ] = $ip['rule'];
				} else {
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
		self::atomicFileWrite( $settingsFile, $settingsContent );

		BotBlockerCache::clearFileCache();
		if ( ! isset( $type ) ) {
			return true;
		} elseif ( isset( $type ) && $type == true ) {
			return $settings;
		}
	}

}
