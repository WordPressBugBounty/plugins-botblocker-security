<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerInstall {

	public static function checkInstall(): void {
		if ( get_option( 'bbcs_db_version' ) === BOTBLOCKER_DB_VERSION ) {
			if ( get_transient( 'bbcs_tables_verified' ) ) {
				return;
			}
			if ( self::tablesExist() ) {
				set_transient( 'bbcs_tables_verified', true, HOUR_IN_SECONDS );
				return;
			}
		}

		if ( ! self::tablesExist() ) {
			self::createTables();
			self::tablesExist( true );

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$has_settings = (bool) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$wpdb->bbcs_settings}`"
			);

			if ( $has_settings ) {
				BotBlockerMigration::maybeUpgradeDb();
				BotBlockerDb::generateAllFiles();
				BotBlockerFileRenderer::generateSettingsFile();
			} else {
				self::initDbAndFiles();
			}
		} else {
			BotBlockerCounters::ensureRow();
			BotBlockerMigration::maybeUpgradeDb();
		}
	}

	public static function initDbAndFiles(): void {
		BotBlockerInstallIp::addServerIPs();
		BotBlockerInstallIp::addAdminIPs();
		BotBlockerInstallIp::fetchAndStoreParentIPs();
		BotBlockerSeedData::insertInitialData( self::createSaltFile( true ) );
		BotBlockerDb::generateAllFiles();
		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerLlmSync::scheduleSync( 'install', 60 );
	}

	public static function getCloudAPIEmail(): string {
		$default = '{email}';
		$email   = $default;

		if ( function_exists( 'is_user_logged_in' ) && function_exists( 'wp_get_current_user' ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && $user->ID && ! empty( $user->user_email ) ) {
				$email = $user->user_email;
			}
		}

		if ( ( $email === $default || $email === '' ) ) {
			$opt = get_option( 'admin_email' );
			if ( is_string( $opt ) && $opt !== '' ) {
				$email = $opt;
			}
		}

		if ( ( $email === $default || $email === '' ) && function_exists( 'is_multisite' ) && is_multisite() ) {
			$opt = get_site_option( 'admin_email' );
			if ( is_string( $opt ) && $opt !== '' ) {
				$email = $opt;
			}
		}

		if ( ( $email === $default || $email === '' ) && function_exists( 'get_users' ) && ! wp_installing() ) {
			$admins = get_users(
				array(
					'role__in' => array( 'administrator' ),
					'number'   => 1,
					'orderby'  => 'ID',
					'order'    => 'ASC',
					'fields'   => array( 'user_email' ),
				)
			);
			if ( ! empty( $admins ) && ! empty( $admins[0]->user_email ) ) {
				$email = $admins[0]->user_email;
			}
		}

		$email = sanitize_email( (string) $email );
		return is_email( $email ) ? $email : $default;
	}

	public static function sendActivationToCloud( string $support_data = '' ): void {
		$data = array(
			'data'     => $support_data ?: self::getCloudAPIEmail(),
			'site_url' => BotBlockerMultisite::getCurrentSiteUrl(),
		);

		$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_URL, 'activation' );
		if ( $cloud === false ) {
			BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_GS_URL, 'activation' );
		}
	}

	public static function saveSettingsToFile( array $settings ): bool {
		$settingsFile    = BotBlockerMultisite::getDataDir() . 'settings.php';
		$settingsContent = BBCS_STOP_DIRECT . "\nreturn " . self::phpExport( $settings, 0, true ) . ";\n";

		$written = BotBlockerFileRenderer::atomicFileWrite( $settingsFile, $settingsContent );
		if ( ! $written ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Install] Failed to write settings file: ' . $settingsFile );
			}
			return false;
		}
		BotBlockerCache::clearFileCache();
		return true;
	}

	public static function saveSettingsToDatabase( array $settings ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing      = $wpdb->get_col( "SELECT `key` FROM `{$wpdb->bbcs_settings}`" );
		$existing_keys = array_flip( $existing );

		foreach ( $settings as $setting_key => $setting_value ) {
			$val = is_array( $setting_value ) ? wp_json_encode( $setting_value ) : $setting_value;
			if ( isset( $existing_keys[ $setting_key ] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->bbcs_settings,
					array( 'value' => $val ),
					array( 'key' => $setting_key ),
					array( '%s' ),
					array( '%s' )
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$wpdb->bbcs_settings,
					array(
						'key'   => $setting_key,
						'value' => $val,
					),
					array( '%s', '%s' )
				);
			}
		}
	}

	public static function phpExport( $value, int $indent = 0, bool $top_array_keyword = false ): string {
		$pad  = str_repeat( '    ', $indent );
		$pad2 = str_repeat( '    ', $indent + 1 );

		$export_string = static function ( string $s ): string {
			$s = str_replace(
				array( '\\', "'", "\r", "\n", "\t", "\v", "\e", "\f", "\0", '$' ),
				array( '\\\\', "\\'", '\r', '\n', '\t', '\v', '\e', '\f', '\0', '\$' ),
				$s
			);
			return "'" . $s . "'";
		};

		if ( is_null( $value ) ) {
			return 'null';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_int( $value ) ) {
			return (string) $value;
		}

		if ( is_float( $value ) ) {
			$repr = rtrim( rtrim( number_format( $value, 12, '.', '' ), '0' ), '.' );
			return $repr === '' ? '0' : $repr;
		}

		if ( is_string( $value ) ) {
			return $export_string( $value );
		}

		if ( is_array( $value ) ) {
			if ( empty( $value ) ) {
				return $top_array_keyword ? 'array ()' : 'array ()';
			}

			$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );

			$out = $top_array_keyword ? "array (\n" : "[\n";
			foreach ( $value as $k => $v ) {
				if ( $is_list ) {
					$out .= $pad2 . self::phpExport( $v, $indent + 1, false ) . ",\n";
				} else {
					$key  = is_int( $k ) ? (string) $k : $export_string( (string) $k );
					$out .= $pad2 . $key . ' => ' . self::phpExport( $v, $indent + 1, false ) . ",\n";
				}
			}
			$out .= $pad . ( $top_array_keyword ? ')' : ']' );
			return $out;
		}

		return $export_string( (string) $value );
	}

	public static function createTables(): void {
		self::createHitsTable();
		self::createHitsSuspiciousTable();
		self::createHitsCloudTable();
		self::createSeTable();
		self::createAsnTable();
		self::createIpv4RulesTable();
		self::createIpv6RulesTable();
		self::createPathTable();
		self::createRulesTable();
		self::createSettingsTable();
		self::createProxyTable();
		self::createPtrcacheTable();
		self::createLlmTrustedTable();
		self::createTlsFingerprintsTable();
		self::createCountersTable();
		self::createDailySummaryTable();
		self::createPageFiltersTable();
		self::createFingerprintTable();
	}

	public static function createHitsTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$structure = "
	        `cid` TEXT NOT NULL,
	        `uid` TEXT NOT NULL,
	        `ip` TEXT NOT NULL,
	        `date` INTEGER NOT NULL DEFAULT 0,
	        `ptr` TEXT NOT NULL,
	        `lang` TEXT NOT NULL,
	        `accept_lang` TEXT NOT NULL,
	        `name_lang` TEXT NOT NULL,
	        `country` TEXT NOT NULL,
	        `browser` TEXT NOT NULL,
	        `os` TEXT NOT NULL,
	        `device` TEXT NOT NULL,
	        `referer` TEXT NOT NULL,
	        `page` TEXT NOT NULL,
	        `passed` INTEGER NOT NULL DEFAULT 0,
	        `js_w` INTEGER NOT NULL,
	        `js_h` INTEGER NOT NULL,
	        `js_cw` INTEGER NOT NULL,
	        `js_ch` INTEGER NOT NULL,
	        `js_co` INTEGER NOT NULL,
	        `js_pi` INTEGER NOT NULL,
	        `refhost` TEXT NOT NULL,
	        `asnum` TEXT NOT NULL,
	        `asname` TEXT NOT NULL,
	        `result` TEXT NOT NULL,
	        `access` TEXT NOT NULL,
	        `http_accept` TEXT NOT NULL,
	        `method` TEXT NOT NULL,
	        `ym_uid` TEXT NOT NULL,
	        `ga_uid` TEXT NOT NULL,
	        `ip_short` TEXT NOT NULL,
	        `hosting` INTEGER NOT NULL DEFAULT 0,
	        `hit` INTEGER NOT NULL DEFAULT 0,
	        `timezone` TEXT NOT NULL,
	        `cookie` TEXT NOT NULL,
	        `region` TEXT NOT NULL,
	        `region_name` TEXT NOT NULL,
	        `country_name` TEXT NOT NULL,
	        `proxy` TEXT NOT NULL,
	        `tor` TEXT NOT NULL,
	        `vpn` TEXT NOT NULL,
	        `carrier` TEXT NOT NULL,
	        `useragent` TEXT NOT NULL,
	        `adblock` INTEGER NOT NULL,
	        `lat` TEXT NOT NULL,
	        `lon` TEXT NOT NULL,
	        `city` TEXT NOT NULL,
	        `generation` TEXT NOT NULL,
	        `generation2` TEXT NOT NULL,
	        `ipv4` TEXT NOT NULL,
	        `cloud_data` TEXT NOT NULL,
	        `recaptcha` TEXT NOT NULL,
	        `wbot` TEXT NOT NULL,
	        `fp` TEXT NOT NULL,
	        UNIQUE KEY cid (cid(191)),
	        KEY i_ip (ip(191)),
	        KEY i_passed (passed),
	        KEY i_date (`date`)
	    ";

		dbDelta( "CREATE TABLE `{$wpdb->bbcs_hits}` ($structure) $charset_collate;" );
	}

	public static function createHitsSuspiciousTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$structure = "
	        `cid` TEXT NOT NULL,
	        `uid` TEXT NOT NULL,
	        `ip` TEXT NOT NULL,
	        `date` INTEGER NOT NULL DEFAULT 0,
	        `ptr` TEXT NOT NULL,
	        `lang` TEXT NOT NULL,
	        `accept_lang` TEXT NOT NULL,
	        `name_lang` TEXT NOT NULL,
	        `country` TEXT NOT NULL,
	        `browser` TEXT NOT NULL,
	        `os` TEXT NOT NULL,
	        `device` TEXT NOT NULL,
	        `referer` TEXT NOT NULL,
	        `page` TEXT NOT NULL,
	        `passed` INTEGER NOT NULL DEFAULT 0,
	        `js_w` INTEGER NOT NULL,
	        `js_h` INTEGER NOT NULL,
	        `js_cw` INTEGER NOT NULL,
	        `js_ch` INTEGER NOT NULL,
	        `js_co` INTEGER NOT NULL,
	        `js_pi` INTEGER NOT NULL,
	        `refhost` TEXT NOT NULL,
	        `asnum` TEXT NOT NULL,
	        `asname` TEXT NOT NULL,
	        `result` TEXT NOT NULL,
	        `access` TEXT NOT NULL,
	        `http_accept` TEXT NOT NULL,
	        `method` TEXT NOT NULL,
	        `ym_uid` TEXT NOT NULL,
	        `ga_uid` TEXT NOT NULL,
	        `ip_short` TEXT NOT NULL,
	        `hosting` INTEGER NOT NULL DEFAULT 0,
	        `hit` INTEGER NOT NULL DEFAULT 0,
	        `timezone` TEXT NOT NULL,
	        `cookie` TEXT NOT NULL,
	        `region` TEXT NOT NULL,
	        `region_name` TEXT NOT NULL,
	        `country_name` TEXT NOT NULL,
	        `proxy` TEXT NOT NULL,
	        `tor` TEXT NOT NULL,
	        `vpn` TEXT NOT NULL,
	        `carrier` TEXT NOT NULL,
	        `useragent` TEXT NOT NULL,
	        `adblock` INTEGER NOT NULL,
	        `lat` TEXT NOT NULL,
	        `lon` TEXT NOT NULL,
	        `city` TEXT NOT NULL,
	        `generation` TEXT NOT NULL,
	        `generation2` TEXT NOT NULL,
	        `ipv4` TEXT NOT NULL,
	        `cloud_data` TEXT NOT NULL,
	        `recaptcha` TEXT NOT NULL,
	        `wbot` TEXT NOT NULL,
	        `fp` TEXT NOT NULL,
	        UNIQUE KEY cid (cid(191)),
	        KEY i_ip (ip(191)),
	        KEY i_passed (passed),
	        KEY i_date (`date`)
	    ";

		dbDelta( "CREATE TABLE `{$wpdb->bbcs_hits_suspicious}` ($structure) $charset_collate;" );
	}

	public static function createHitsCloudTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$structure = "
	        `cid` TEXT NOT NULL,
	        `uid` TEXT NOT NULL,
	        `ip` TEXT NOT NULL,
	        `date` INTEGER NOT NULL DEFAULT 0,
	        `ptr` TEXT NOT NULL,
	        `lang` TEXT NOT NULL,
	        `accept_lang` TEXT NOT NULL,
	        `name_lang` TEXT NOT NULL,
	        `country` TEXT NOT NULL,
	        `browser` TEXT NOT NULL,
	        `os` TEXT NOT NULL,
	        `device` TEXT NOT NULL,
	        `referer` TEXT NOT NULL,
	        `page` TEXT NOT NULL,
	        `passed` INTEGER NOT NULL DEFAULT 0,
	        `js_w` INTEGER NOT NULL,
	        `js_h` INTEGER NOT NULL,
	        `js_cw` INTEGER NOT NULL,
	        `js_ch` INTEGER NOT NULL,
	        `js_co` INTEGER NOT NULL,
	        `js_pi` INTEGER NOT NULL,
	        `refhost` TEXT NOT NULL,
	        `asnum` TEXT NOT NULL,
	        `asname` TEXT NOT NULL,
	        `result` TEXT NOT NULL,
	        `access` TEXT NOT NULL,
	        `http_accept` TEXT NOT NULL,
	        `method` TEXT NOT NULL,
	        `ym_uid` TEXT NOT NULL,
	        `ga_uid` TEXT NOT NULL,
	        `ip_short` TEXT NOT NULL,
	        `hosting` INTEGER NOT NULL DEFAULT 0,
	        `hit` INTEGER NOT NULL DEFAULT 0,
	        `timezone` TEXT NOT NULL,
	        `cookie` TEXT NOT NULL,
	        `region` TEXT NOT NULL,
	        `region_name` TEXT NOT NULL,
	        `country_name` TEXT NOT NULL,
	        `proxy` TEXT NOT NULL,
	        `tor` TEXT NOT NULL,
	        `vpn` TEXT NOT NULL,
	        `carrier` TEXT NOT NULL,
	        `useragent` TEXT NOT NULL,
	        `adblock` INTEGER NOT NULL,
	        `lat` TEXT NOT NULL,
	        `lon` TEXT NOT NULL,
	        `city` TEXT NOT NULL,
	        `generation` TEXT NOT NULL,
	        `generation2` TEXT NOT NULL,
	        `ipv4` TEXT NOT NULL,
	        `cloud_data` TEXT NOT NULL,
	        `recaptcha` TEXT NOT NULL,
	        `wbot` TEXT NOT NULL,
	        `fp` TEXT NOT NULL,
	        UNIQUE KEY cid (cid(191)),
	        KEY i_ip (ip(191)),
	        KEY i_passed (passed),
	        KEY i_date (`date`)
	    ";

		dbDelta( "CREATE TABLE `{$wpdb->bbcs_hits_cloud}` ($structure) $charset_collate;" );
	}

	public static function createSeTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_se}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        `priority` INTEGER NOT NULL DEFAULT 100,
	        `search` TEXT NOT NULL,
	        `data` TEXT NOT NULL,
	        `rule` TEXT NOT NULL,
	        `comment` TEXT NOT NULL,
	        `disable` INTEGER NOT NULL,
	        PRIMARY KEY  (`id`),
	        UNIQUE KEY search (search(191))
	    		) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createIpv4RulesTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_ipv4rules}` (
	        `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `priority` INTEGER NOT NULL DEFAULT 100,
	        `search` TEXT NOT NULL,
	        `ip1` VARCHAR(11) NOT NULL DEFAULT '',
	        `ip2` VARCHAR(11) NOT NULL DEFAULT '',
	        `rule` TEXT NOT NULL,
	        `comment` TEXT NOT NULL,
	        `expires` BIGINT(20) NOT NULL DEFAULT " . BOTBLOCKER_EXP_INF . ",
	        `disable` INTEGER NOT NULL DEFAULT 0,
	        `readonly` INTEGER NOT NULL DEFAULT 0,
	        UNIQUE KEY search (search(191)),
	        KEY ipv4range_disabled_index (disable, ip1, ip2)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createIpv6RulesTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_ipv6rules}` (
	        `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `priority` INTEGER NOT NULL DEFAULT 100,
	        `search` TEXT NOT NULL,
	        `ip1` BINARY(16) NOT NULL,
	        `ip2` BINARY(16) NOT NULL,
	        `rule` TEXT NOT NULL,
	        `comment` TEXT NOT NULL,
	        `expires` BIGINT(20) NOT NULL DEFAULT " . BOTBLOCKER_EXP_INF . ",
	        `disable` INTEGER NOT NULL DEFAULT 0,
	        `readonly` INTEGER NOT NULL DEFAULT 0,
	        UNIQUE KEY search (search(191)),
	        KEY ipv6range_disabled_index (disable, ip1, ip2)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createPathTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_path}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `priority` INT(11) NOT NULL DEFAULT 100,
	        `search` TEXT NOT NULL,
	        `rule` TEXT NOT NULL,
	        `comment` TEXT NOT NULL,
	        `disable` INT(1) NOT NULL DEFAULT 0,
	        UNIQUE KEY search (search(191))
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createRulesTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_rules}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `search` VARCHAR(255) AS (CONCAT(`type`, '=', `data`)) STORED UNIQUE,
	        `priority` INTEGER NOT NULL DEFAULT 1,
	        `type` TEXT NOT NULL,
	        `data` TEXT NOT NULL,
	        `expires` BIGINT(20) NOT NULL DEFAULT " . BOTBLOCKER_EXP_INF . ",
	        `disable` INTEGER NOT NULL DEFAULT 0,
	        `rule` TEXT NOT NULL,
	        `comment` TEXT NOT NULL,
	        KEY i_priority (priority)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createSettingsTable(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->bbcs_settings ) ) === $wpdb->bbcs_settings ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_settings}` (
	        `key` VARCHAR(191) NOT NULL UNIQUE,
	        `value` TEXT NOT NULL
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createProxyTable(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->bbcs_proxy ) ) === $wpdb->bbcs_proxy ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_proxy}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
 			`key` TEXT NOT NULL,
        	`value` TEXT NOT NULL,
        	`comment` TEXT NOT NULL,
	        UNIQUE KEY uniq_key (`key`(191))
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createPtrcacheTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_ptrcache}` (
	        `ip` VARCHAR(45) NOT NULL DEFAULT '',
	        `ptr` VARCHAR(255) NOT NULL DEFAULT '',
	        `date` INTEGER NOT NULL DEFAULT 0,
	        `etime` TEXT,
	        PRIMARY KEY (ip)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createLlmTrustedTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_llm_trusted}` (
			`id` INTEGER NOT NULL AUTO_INCREMENT,
			PRIMARY KEY  (`id`),
			`provider` VARCHAR(64) NOT NULL DEFAULT '',
			`provider_label` VARCHAR(128) NOT NULL DEFAULT '',
			`search` TEXT NOT NULL,
			`verified_ip_ranges` TEXT NOT NULL,
			`disabled` TINYINT NOT NULL DEFAULT 0,
			UNIQUE KEY provider (provider)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}

	public static function createTlsFingerprintsTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_tls_fingerprints}` (
			`id` INTEGER NOT NULL AUTO_INCREMENT,
			PRIMARY KEY  (`id`),
			`fingerprint` VARCHAR(255) NOT NULL DEFAULT '',
			`category` VARCHAR(32) NOT NULL DEFAULT 'unknown',
			`ua_family` VARCHAR(64) NOT NULL DEFAULT '',
			`description` VARCHAR(255) NOT NULL DEFAULT '',
			`disabled` TINYINT NOT NULL DEFAULT 0,
			UNIQUE KEY fingerprint (fingerprint(191))
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}

	public static function createCountersTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_counters}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `today_hits` INT(11) NOT NULL DEFAULT 0,
	        `today_blocked` INT(11) NOT NULL DEFAULT 0,
	        `total_hits` INT(11) NOT NULL DEFAULT 0,
	        `total_blocked` INT(11) NOT NULL DEFAULT 0,
	        `search_engine_visits` INT(11) NOT NULL DEFAULT 0,
	        `percent_requests_blocked` DECIMAL(5,2) AS (IF((total_hits + total_blocked) > 0, (total_blocked / (total_hits + total_blocked)) * 100, 0)) VIRTUAL,
	        `last_update` DATETIME NULL
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createDailySummaryTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_daily_summary}` (
	        `date_key` date NOT NULL,
	        `metric` varchar(32) NOT NULL,
	        `dim_key` varchar(128) NOT NULL DEFAULT '',
	        `val` bigint NOT NULL DEFAULT 0,
	        PRIMARY KEY  (date_key,metric,dim_key),
	        KEY idx_metric_date (metric,date_key)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function tablesExist( bool $reset = false ): bool {
		global $wpdb;

		static $memo = array();
		$blog_id     = get_current_blog_id();
		if ( $reset ) {
			unset( $memo[ $blog_id ] );
		}
		if ( isset( $memo[ $blog_id ] ) ) {
			return $memo[ $blog_id ];
		}

		$requiredTables = array(
			$wpdb->bbcs_hits,
			$wpdb->bbcs_hits_suspicious,
			$wpdb->bbcs_hits_cloud,
			$wpdb->bbcs_se,
			$wpdb->bbcs_ipv4rules,
			$wpdb->bbcs_ipv6rules,
			$wpdb->bbcs_path,
			$wpdb->bbcs_rules,
			$wpdb->bbcs_settings,
			$wpdb->bbcs_ptrcache,
			$wpdb->bbcs_proxy,
			$wpdb->bbcs_counters,
			$wpdb->bbcs_page_filters,
			$wpdb->bbcs_daily_summary,
			$wpdb->bbcs_asn,
			$wpdb->bbcs_llm_trusted,
			$wpdb->bbcs_tls_fingerprints,
		);

		$requiredTables = array_values( array_unique( array_filter( $requiredTables, 'is_string' ) ) );
		if ( ! $requiredTables ) {
			$memo[ $blog_id ] = true;
			return true;
		}

		$ok = null;

		$placeholders = implode( ',', array_fill( 0, count( $requiredTables ), '%s' ) );

		// REVIEWER NOTE: Single-query check of BotBlocker-Security custom tables via information_schema with prepared IN list.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT COUNT(*)
	         FROM information_schema.tables
	         WHERE table_schema = DATABASE()
	           AND table_name IN ($placeholders)",
			$requiredTables
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $sql );

		if ( $count !== null && $wpdb->last_error === '' ) {
			$ok = ( (int) $count === count( $requiredTables ) );
		}

		if ( $ok === null ) {
			// REVIEWER NOTE: Fallback single-query table list check using SHOW TABLES (some hosts restrict information_schema).
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existingTables = $wpdb->get_col( 'SHOW TABLES' );

			if ( ! is_array( $existingTables ) || ! $existingTables ) {
				$ok = false;
			} else {
				$map = array_fill_keys( $existingTables, true );

				$ok = true;
				foreach ( $requiredTables as $t ) {
					if ( ! isset( $map[ $t ] ) ) {
						$ok = false;
						break;
					}
				}
			}
		}

		$memo[ $blog_id ] = (bool) $ok;

		return $memo[ $blog_id ];
	}

	public static function createPageFiltersTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_page_filters}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `pattern` VARCHAR(191) NOT NULL,
	        `category` VARCHAR(32) NOT NULL,
	        UNIQUE KEY `pattern` (`pattern`)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createAsnTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$wpdb->bbcs_asn}` (
	        `id` INTEGER NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY  (`id`),
	        `priority` INTEGER NOT NULL DEFAULT 50,
	        `asnum` INTEGER NOT NULL,
	        `asname` VARCHAR(255) NOT NULL DEFAULT '',
	        `rule` VARCHAR(10) NOT NULL DEFAULT 'block',
	        `comment` VARCHAR(255) NOT NULL DEFAULT '',
	        `disable` TINYINT(1) NOT NULL DEFAULT 0,
	        UNIQUE KEY `asnum` (`asnum`)
	    ) $charset_collate;";

		dbDelta( $sql );
	}

	public static function createFingerprintTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$structure = "
	        `fingerprint` VARCHAR(64) NOT NULL,
	        `ip` VARCHAR(45) NOT NULL,
	        `first_seen` INTEGER NOT NULL DEFAULT 0,
	        `last_seen` INTEGER NOT NULL DEFAULT 0,
	        `block_count` INTEGER NOT NULL DEFAULT 0,
	        `allow_count` INTEGER NOT NULL DEFAULT 0,
	        `last_block_reason` TEXT NOT NULL,
	        `last_country` VARCHAR(2) NOT NULL DEFAULT '',
	        `status` VARCHAR(16) NOT NULL DEFAULT 'watch',
	        UNIQUE KEY fingerprint (fingerprint),
	        KEY i_ip (ip),
	        KEY i_block_count (block_count),
	        KEY i_status (status)
	    ";

		dbDelta( "CREATE TABLE `{$wpdb->bbcs_fingerprint}` ($structure) $charset_collate;" );
	}

	public static function createSaltFile( $return_salt_bb = false ) {
		$saltFilePath = BotBlockerMultisite::getNetworkDataDir() . 'salt.php';

		if ( is_multisite() && file_exists( $saltFilePath ) ) {
			if ( $return_salt_bb ) {
				$existing = bbcs_safe_load_data_file( $saltFilePath );
				if ( is_array( $existing ) && isset( $existing['salt_bb'] ) ) {
					return (string) $existing['salt_bb'];
				}
			} else {
				return false;
			}
		}

		if ( ! file_exists( $saltFilePath ) || $return_salt_bb === true ) {
			$host_key = md5( get_option( 'siteurl' ) );
			$salt_bb  = bin2hex( random_bytes( 12 ) );
			$salt_ps  = bin2hex( random_bytes( 12 ) );
			$salt_pz  = time();

			$salt_data = array(
				'host_key' => $host_key,
				'salt_bb'  => $salt_bb,
				'salt_ps'  => $salt_ps,
				'salt_pz'  => $salt_pz,
			);

			$dir = dirname( $saltFilePath );
			if ( ! is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					update_option( 'bbcs_salt_fallback', $salt_data );
					set_transient( 'bbcs_salt_write_error', true, DAY_IN_SECONDS );
					return $salt_bb;
				}
			}

			if ( ! is_writable( $dir ) ) {   // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				update_option( 'bbcs_salt_fallback', $salt_data );
				set_transient( 'bbcs_salt_write_error', true, DAY_IN_SECONDS );
				return $salt_bb;
			}

			/**
			 * REVIEWER NOTE: This operation is not intended for debugging purposes. The following code generates a salt.php file
			 * to cache plugin salt data, thereby reducing the frequency of database queries and enhancing overall performance.
			 * No sensitive or user data is exposed by this process.
			 */
	         /* phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export */
			$fileContent = "<?php\nreturn " . var_export( $salt_data, true ) . ";\n";
			$fileContent = bbcs_data_file_sign( $fileContent );

			$result = file_put_contents( $saltFilePath, $fileContent );

			if ( $result === false ) {
				update_option( 'bbcs_salt_fallback', $salt_data );
				set_transient( 'bbcs_salt_write_error', true, DAY_IN_SECONDS );
				return $salt_bb;
			}

			BotBlockerCache::clearFileCache();

			return $salt_bb;
		}

		return false;
	}

	public static function installMuPlugin(): void {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$plugin_relative_file = plugin_basename( BOTBLOCKER_DIR . 'botblocker-mu.php' );

		$mu_plugin_content  = "<?php\n";
		$mu_plugin_content .= "/*\n";
		$mu_plugin_content .= "Plugin Name: BotBlocker (MU Loader)\n";
		$mu_plugin_content .= "Description: Loads BotBlocker early as an MU plugin.\n";
		$mu_plugin_content .= "Author: GLOBUS.studio\n";
		$mu_plugin_content .= 'Version: ' . BOTBLOCKER_VERSION . "\n";
		$mu_plugin_content .= "*/\n\n";
		$mu_plugin_content .= "if ( defined('WP_PLUGIN_DIR') && file_exists( WP_PLUGIN_DIR . '/" . $plugin_relative_file . "' ) ) {\n";
		$mu_plugin_content .= "    require_once WP_PLUGIN_DIR . '/" . $plugin_relative_file . "';\n";
		$mu_plugin_content .= "    if ( class_exists( 'BotBlockerMu' ) ) {\n";
		$mu_plugin_content .= "        \$botBlocker = new BotBlockerMu();\n";
		$mu_plugin_content .= "        \$botBlocker->run();\n";
		$mu_plugin_content .= "    }\n";
		$mu_plugin_content .= "}\n";

		$mu_plugins_dir = WPMU_PLUGIN_DIR;
		$mu_plugin_file = trailingslashit( $mu_plugins_dir ) . 'botblocker-mu-plugin.php';

		if ( ! $wp_filesystem->is_dir( $mu_plugins_dir ) ) {
			$wp_filesystem->mkdir( $mu_plugins_dir, defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755 );
		}

		$mode = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		$wp_filesystem->put_contents( $mu_plugin_file, $mu_plugin_content, $mode );
		BotBlockerCache::clearFileCache();
	}

	public static function uninstallMuPlugin(): void {
		$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php';

		if ( file_exists( $mu_plugin_file ) ) {
			wp_delete_file( $mu_plugin_file );
			clearstatcache( true );
		}
	}

	public static function deleteRuleFiles(): void {
		$files = array(
			BotBlockerMultisite::getDataDir() . 'search_engines.php',
			BotBlockerMultisite::getDataDir() . 'asn_rules.php',
			BotBlockerMultisite::getDataDir() . 'paths.php',
			BotBlockerMultisite::getDataDir() . 'rules.php',
			BotBlockerMultisite::getDataDir() . 'ip.php',
		);
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
				clearstatcache( true );
			}
		}
	}

	public static function setEarlyInitEnabled( bool $enabled ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'early_init_enable',
				'value' => $enabled ? '1' : '0',
			),
			array( '%s', '%s' )
		);

		// Mutual exclusivity: enabling early-init disables MU mode.
		if ( $enabled ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'mu_enable',
					'value' => '0',
				),
				array( '%s', '%s' )
			);
			self::uninstallMuPlugin();
		}

		do_action( 'bbcs_early_init_toggle', $enabled );

		$files_ok = apply_filters( 'bbcs_early_init_files_ok', true, $enabled );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		return $files_ok;
	}
}
