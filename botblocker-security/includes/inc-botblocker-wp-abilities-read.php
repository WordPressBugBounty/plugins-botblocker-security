<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_ability_input_string' ) ) {

	function bbcs_ability_input_string( $input, string $key, string $default = '' ): string {
		if ( ! is_array( $input ) || ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
			return $default;
		}
		return sanitize_text_field( (string) $input[ $key ] );
	}

	function bbcs_ability_input_enum( $input, string $key, array $allowed, string $default ): string {
		$value = bbcs_ability_input_string( $input, $key, $default );
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	function bbcs_ability_mask_enabled( $input, bool $allow_disable = false ): bool {
		if ( ! $allow_disable ) {
			return true;
		}
		if ( ! is_array( $input ) || ! array_key_exists( 'mask_pii', $input ) ) {
			return true;
		}
		return (bool) filter_var( $input['mask_pii'], FILTER_VALIDATE_BOOLEAN );
	}

	function bbcs_ability_mask_ip( string $ip ): string {
		if ( $ip === '' ) {
			return '';
		}
		if ( strpos( $ip, ':' ) !== false ) {
			$parts = explode( ':', $ip );
			return implode( ':', array_slice( $parts, 0, 4 ) ) . '::0';
		}
		$parts = explode( '.', $ip );
		if ( count( $parts ) === 4 ) {
			return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
		}
		return '***';
	}

	function bbcs_ability_mask_identity( string $value ): string {
		if ( $value === '' ) {
			return '';
		}
		return substr( $value, 0, 2 ) . '***';
	}

	function bbcs_ability_mask_email( string $value ): string {
		$at = strpos( $value, '@' );
		if ( $at === false ) {
			return bbcs_ability_mask_identity( $value );
		}
		$domain = substr( $value, $at + 1 );
		return substr( $value, 0, 1 ) . '***@' . $domain;
	}

	function bbcs_ability_mask_text( string $value, int $keep = 24 ): string {
		if ( $value === '' ) {
			return '';
		}
		$value = (string) preg_replace( '/\?.*$/', '', $value );
		return strlen( $value ) > $keep ? substr( $value, 0, $keep ) . '...' : $value;
	}

	function bbcs_ability_mask_rows( array $rows, bool $mask, bool $nested = false ): array {
		if ( ! $mask ) {
			return $rows;
		}
		$out = array();
		foreach ( $rows as $key => $value ) {
			if ( is_array( $value ) ) {
				$child_nested = is_int( $key ) ? $nested : true;
				$out[ $key ]  = bbcs_ability_mask_rows( $value, true, $child_nested );
				continue;
			}
			if ( $nested && class_exists( 'BotBlockerAuditLogger' ) && is_string( $key ) && BotBlockerAuditLogger::isSensitiveKey( $key ) ) {
				$out[ $key ] = '[redacted]';
				continue;
			}
			if ( ! is_string( $key ) || ! is_scalar( $value ) ) {
				$out[ $key ] = $value;
				continue;
			}
			$lower = strtolower( $key );
			$text  = (string) $value;
			if ( in_array( $lower, array( 'ip', 'ip1', 'ip2', 'remote_addr', 'client_ip', 'server_ip', 'ip_short', 'target_ip' ), true ) ) {
				$out[ $key ] = bbcs_ability_mask_ip( $text );
			} elseif ( in_array( $lower, array( 'login', 'username', 'user_login', 'actor_username' ), true ) ) {
				$out[ $key ] = bbcs_ability_mask_identity( $text );
			} elseif ( in_array( $lower, array( 'email', 'user_email', 'actor_email' ), true ) ) {
				$out[ $key ] = bbcs_ability_mask_email( $text );
			} elseif ( in_array( $lower, array( 'user_agent', 'ua', 'useragent' ), true ) ) {
				$out[ $key ] = bbcs_ability_mask_text( $text, 20 );
			} elseif ( in_array( $lower, array( 'path', 'request_path', 'page', 'referer', 'url' ), true ) ) {
				$out[ $key ] = bbcs_ability_mask_text( $text, 80 );
			} else {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	function bbcs_ability_iso_time( $timestamp ): string {
		$timestamp = (int) $timestamp;
		return $timestamp > 0 ? (string) gmdate( 'c', $timestamp ) : '';
	}

	function bbcs_ability_audit_rows( array $args, bool $mask ): array {
		if ( ! class_exists( 'BotBlockerAuditRepository' ) ) {
			return array( 'rows' => array(), 'total' => 0, 'masked' => $mask );
		}
		$result = BotBlockerAuditRepository::query( $args );
		$rows   = array();
		foreach ( (array) $result['rows'] as $row ) {
			$data = array();
			if ( isset( $row['data'] ) && is_string( $row['data'] ) && $row['data'] !== '' ) {
				$decoded = json_decode( $row['data'], true );
				$data    = is_array( $decoded ) ? $decoded : array();
			}
			$rows[] = array(
				'id'             => (int) $row['id'],
				'created_at'     => bbcs_ability_iso_time( $row['created_at'] ?? 0 ),
				'created_ts'     => (int) ( $row['created_at'] ?? 0 ),
				'event_key'      => (string) ( $row['event_key'] ?? '' ),
				'severity'       => (string) ( $row['severity'] ?? '' ),
				'actor_user_id'  => (int) ( $row['actor_user_id'] ?? 0 ),
				'actor_username' => (string) ( $row['actor_username'] ?? '' ),
				'actor_role'     => (string) ( $row['actor_role'] ?? '' ),
				'object_type'    => (string) ( $row['object_type'] ?? '' ),
				'object_id'      => (int) ( $row['object_id'] ?? 0 ),
				'ip'             => (string) ( $row['ip'] ?? '' ),
				'context'        => (string) ( $row['context'] ?? '' ),
				'request_path'   => (string) ( $row['request_path'] ?? '' ),
				'user_agent'     => (string) ( $row['user_agent'] ?? '' ),
				'data'           => $data,
			);
		}
		$masked = array();
		foreach ( $rows as $row ) {
			$masked[] = bbcs_ability_mask_rows( array( $row ), $mask )[0];
		}
		return array(
			'rows'   => $masked,
			'total'  => (int) $result['total'],
			'masked' => $mask,
		);
	}

	function bbcs_wp_ability_execute_get_health_score( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-health-score', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerHealthService' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The health service is not available.', 'botblocker-security' ) );
		}
		$bbcS          = BotBlocker::getInstance();
		$settings      = is_object( $bbcS->settings ) ? $bbcS->settings : null;
		$recaptcha     = $settings && ! empty( $settings->recaptcha_key2 ) && ! empty( $settings->recaptcha_secret2 );
		$items         = array();
		foreach ( BotBlockerHealthService::getDefinitions() as $definition ) {
			$items[] = array(
				'key'     => (string) $definition['key'],
				'label'   => (string) $definition['label'],
				'type'    => (string) $definition['type'],
				'enabled' => BotBlockerHealthService::isEnabled( (string) $definition['key'], $settings, $recaptcha ),
			);
		}
		return array(
			'score' => BotBlockerHealthService::calculateHealthScore( $settings, $recaptcha ),
			'items' => $items,
		);
	}

	function bbcs_wp_ability_execute_get_protection_snapshot( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-protection-snapshot', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$settings = BotBlocker::getInstance()->settings;
		$settings = is_object( $settings ) ? $settings : null;
		if ( ! $settings ) {
			return new WP_Error( 'ability_unavailable', __( 'Settings are not available.', 'botblocker-security' ) );
		}

		$preset = 'custom';
		if ( class_exists( 'BotBlockerSettingsPresets' ) ) {
			$candidates = array(
				'light'  => BotBlockerSettingsPresets::loadLightSecurity(),
				'strong' => BotBlockerSettingsPresets::loadStrongSecurity(),
				'full'   => BotBlockerSettingsPresets::loadFullSecurity(),
			);
			foreach ( $candidates as $name => $values ) {
				$match = true;
				foreach ( $values as $key => $expected ) {
					$actual = isset( $settings->$key ) ? (string) $settings->$key : null;
					if ( $actual === null ) {
						continue;
					}
					if ( $actual !== (string) $expected ) {
						$match = false;
						break;
					}
				}
				if ( $match ) {
					$preset = $name;
					break;
				}
			}
		}

		$mode    = isset( $settings->bbcs_captcha_mode ) ? (int) $settings->bbcs_captcha_mode : 0;
		$secure  = isset( $settings->secure_mode ) ? (int) $settings->secure_mode : 0;
		$enabled = isset( $settings->disable ) ? (int) $settings->disable !== 1 : false;
		$warnings = array();
		if ( 2 === $secure ) {
			$warnings[] = __( 'Full secure mode challenges the WordPress admin area as well.', 'botblocker-security' );
		}
		if ( ! $enabled ) {
			$warnings[] = __( 'Protection is disabled.', 'botblocker-security' );
		}

		return array(
			'enabled'            => $enabled,
			'secure_mode'        => $secure,
			'secure_mode_label'  => 2 === $secure ? __( 'Full', 'botblocker-security' ) : __( 'Frontend', 'botblocker-security' ),
			'captcha_mode'       => $mode,
			'captcha_mode_label' => bbcs_wp_ability_mode_label( $mode ),
			'preset'             => $preset,
			'layers'             => array(
				'early_init'   => ! empty( $settings->early_init_enable ),
				'early_geo'    => ! empty( $settings->early_geo_enable ),
				'mu_plugin'    => ! empty( $settings->mu_enable ),
				'mu_geo'       => ! empty( $settings->mu_geo_enable ),
				'wp_abilities' => ! empty( $settings->bbcs_wp_abilities_enabled ),
				'connectors'   => ! empty( $settings->bbcs_wp_connectors_enabled ),
			),
			'warnings'           => $warnings,
		);
	}

	function bbcs_wp_ability_execute_get_addons_status( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-addons-status', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerAddons' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The add-on manager is not available.', 'botblocker-security' ) );
		}
		$addons = BotBlockerAddons::scanAll();
		$rows   = array();
		foreach ( (array) $addons as $slug => $addon ) {
			if ( empty( $addon['valid'] ) ) {
				continue;
			}
			$abilities = array();
			foreach ( (array) ( $addon['abilities'] ?? array() ) as $entry ) {
				if ( is_array( $entry ) && ! empty( $entry['name'] ) ) {
					$abilities[] = (string) $entry['name'];
				}
			}
			$rows[] = array(
				'slug'          => (string) $slug,
				'name'          => (string) ( $addon['name'] ?? $slug ),
				'version'       => (string) ( $addon['version'] ?? '' ),
				'active'        => BotBlockerAddons::isActive( (string) $slug ),
				'compatible'    => class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::isCompatible( $addon ) : true,
				'requires_core' => (string) ( $addon['requires_core'] ?? '' ),
				'features'      => array_values( (array) ( $addon['features'] ?? array() ) ),
				'abilities'     => $abilities,
			);
		}
		return array( 'addons' => $rows );
	}

	function bbcs_wp_ability_execute_get_license_status( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-license-status', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerPro' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The license component is not available.', 'botblocker-security' ) );
		}
		$days = BotBlockerPro::getRemainingDays();
		$hits = BotBlockerPro::getRemainingHits();
		return array(
			'active'         => BotBlockerPro::isActive(),
			'expired'        => BotBlockerPro::isExpired(),
			'type'           => BotBlockerPro::getType(),
			'tier'           => BotBlockerPro::getTier(),
			'key_configured' => BotBlockerPro::getKey() !== '',
			'remaining_days' => is_numeric( $days ) ? (int) $days : 0,
			'remaining_hits' => is_numeric( $hits ) ? (int) $hits : 0,
		);
	}

	function bbcs_wp_ability_execute_get_capabilities( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-capabilities', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$pro = array( 'active' => false, 'tier' => '', 'features' => array() );
		if ( class_exists( 'BotBlockerPro' ) ) {
			$features = array();
			foreach ( array_keys( BotBlockerPro::PRO_FEATURE_TIERS ) as $feature ) {
				$features[ $feature ] = BotBlockerPro::allowsFeature( (string) $feature );
			}
			$pro = array(
				'active'   => BotBlockerPro::isActive(),
				'tier'     => BotBlockerPro::getTier(),
				'features' => $features,
			);
		}

		$addons = array();
		if ( class_exists( 'BotBlockerAddons' ) ) {
			foreach ( (array) BotBlockerAddons::scanAll() as $slug => $addon ) {
				if ( empty( $addon['valid'] ) ) {
					continue;
				}
				$addons[ (string) $slug ] = array(
					'active'   => BotBlockerAddons::isActive( (string) $slug ),
					'features' => array_values( (array) ( $addon['features'] ?? array() ) ),
				);
			}
		}

		$settings = BotBlocker::getInstance()->settings;
		return array(
			'pro'    => $pro,
			'addons' => $addons,
			'gates'  => array(
				'wp_abilities' => is_object( $settings ) && ! empty( $settings->bbcs_wp_abilities_enabled ),
				'connectors'   => is_object( $settings ) && ! empty( $settings->bbcs_wp_connectors_enabled ),
				'early_init'   => is_object( $settings ) && ! empty( $settings->early_init_enable ),
				'mu_plugin'    => is_object( $settings ) && ! empty( $settings->mu_enable ),
				'protection'   => is_object( $settings ) && (int) ( $settings->disable ?? 0 ) !== 1,
			),
		);
	}

	function bbcs_wp_ability_execute_validate_config( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/validate-config', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$settings = BotBlocker::getInstance()->settings;
		$settings = is_object( $settings ) ? $settings : null;
		$data_dir = class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getDataDir() : '';
		$checks   = array(
			array( 'key' => 'settings', 'passed' => $settings !== null ),
			array( 'key' => 'data_dir_writable', 'passed' => $data_dir !== '' && is_dir( $data_dir ) && wp_is_writable( $data_dir ) ),
			array( 'key' => 'settings_file', 'passed' => $data_dir !== '' && file_exists( $data_dir . 'settings.php' ) ),
			array( 'key' => 'tables', 'passed' => class_exists( 'BotBlockerInstall' ) && BotBlockerInstall::tablesExist( true ) ),
			array( 'key' => 'protection_enabled', 'passed' => $settings !== null && (int) ( $settings->disable ?? 0 ) !== 1 ),
		);
		$issues = array();
		foreach ( $checks as $check ) {
			if ( empty( $check['passed'] ) ) {
				$issues[] = (string) $check['key'];
			}
		}
		$score = 0;
		if ( class_exists( 'BotBlockerHealthService' ) && $settings ) {
			$recaptcha = ! empty( $settings->recaptcha_key2 ) && ! empty( $settings->recaptcha_secret2 );
			$score     = BotBlockerHealthService::calculateHealthScore( $settings, $recaptcha );
		}
		return array(
			'valid'  => empty( $issues ),
			'score'  => $score,
			'checks' => $checks,
			'issues' => $issues,
		);
	}

	function bbcs_wp_ability_execute_get_cron_jobs( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-cron-jobs', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$include_other = bbcs_wp_ability_input_bool( $input, 'include_other', false );
		$expose_args   = (bool) apply_filters( 'bbcs_ability_expose_cron_args', false );
		$crons         = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
		$jobs          = array();
		foreach ( (array) $crons as $timestamp => $hooks ) {
			foreach ( (array) $hooks as $hook => $events ) {
				$hook = (string) $hook;
				if ( ! $include_other && strpos( $hook, 'bbcs' ) === false && strpos( $hook, 'botblocker' ) === false ) {
					continue;
				}
				foreach ( (array) $events as $event ) {
					$job = array(
						'hook'       => $hook,
						'timestamp'  => (int) $timestamp,
						'next_run'   => bbcs_ability_iso_time( $timestamp ),
						'recurrence' => isset( $event['schedule'] ) && is_string( $event['schedule'] ) ? $event['schedule'] : '',
						'interval'   => isset( $event['interval'] ) ? (int) $event['interval'] : 0,
					);
					if ( $expose_args ) {
						$job['args'] = isset( $event['args'] ) && is_array( $event['args'] ) ? $event['args'] : array();
					}
					$jobs[] = $job;
				}
			}
		}
		usort(
			$jobs,
			static function ( $a, $b ) {
				return (int) $a['timestamp'] <=> (int) $b['timestamp'];
			}
		);
		return array(
			'jobs'        => $jobs,
			'args_exposed' => $expose_args,
		);
	}

	function bbcs_wp_ability_execute_get_db_health( $input = null ) {
		unset( $input );
		global $wpdb;
		$guard = bbcs_wp_ability_guard( 'botblocker/get-db-health', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$names = array(
			'bbcs_hits', 'bbcs_hits_suspicious', 'bbcs_hits_cloud', 'bbcs_se', 'bbcs_path',
			'bbcs_rules', 'bbcs_settings', 'bbcs_ptrcache', 'bbcs_proxy', 'bbcs_counters',
			'bbcs_ipv4rules', 'bbcs_ipv6rules', 'bbcs_page_filters', 'bbcs_daily_summary',
			'bbcs_asn', 'bbcs_llm_trusted', 'bbcs_tls_fingerprints', 'bbcs_countries',
			'bbcs_fingerprint', 'bbcs_sessions', 'bbcs_audit_log',
		);
		$tables = array();
		foreach ( $names as $name ) {
			$tables[ $name ] = array(
				'name'       => $name,
				'exists'     => false,
				'rows'       => 0,
				'size_bytes' => 0,
			);
		}

		$existing = $wpdb->get_col( 'SHOW TABLES' );
		$existing = is_array( $existing ) ? array_map( 'strval', $existing ) : null;

		$suppress = $wpdb->suppress_errors( true );
		$schema = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT table_name AS name, table_rows AS rows_count, ( data_length + index_length ) AS size_bytes
				 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ( ' . implode( ',', array_fill( 0, count( $names ), '%s' ) ) . ' )',
				$names
			),
			ARRAY_A
		);
		$wpdb->suppress_errors( $suppress );

		$available = is_array( $schema ) && ! empty( $schema );
		$reason    = $available ? '' : 'information_schema_unavailable';
		if ( is_array( $schema ) ) {
			foreach ( $schema as $row ) {
				$name = (string) $row['name'];
				if ( ! isset( $tables[ $name ] ) ) {
					continue;
				}
				$tables[ $name ]['exists']     = true;
				$tables[ $name ]['rows']       = (int) $row['rows_count'];
				$tables[ $name ]['size_bytes'] = (int) $row['size_bytes'];
			}
		}
		if ( is_array( $existing ) ) {
			foreach ( $names as $name ) {
				$tables[ $name ]['exists'] = in_array( $wpdb->prefix . 'bbcs_' . substr( $name, 7 ), $existing, true ) || in_array( $name, $existing, true );
			}
			if ( ! $available && ! empty( $tables ) ) {
				$reason = 'information_schema_unavailable';
			}
		}

		return array(
			'available' => $available,
			'reason'    => $reason,
			'tables'    => array_values( $tables ),
		);
	}

	function bbcs_wp_ability_execute_get_top_entities( $input = null ) {
		global $wpdb;
		$guard = bbcs_wp_ability_guard( 'botblocker/get-top-entities', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$dimension = bbcs_ability_input_enum( $input, 'dimension', array( 'country', 'asn', 'ua', 'status', 'path' ), 'country' );
		$period    = bbcs_ability_input_enum( $input, 'period', array( 'day', 'week', 'month' ), 'week' );
		$limit     = max( 1, min( 50, bbcs_wp_ability_input_int( $input, 'limit', 10 ) ) );
		$days      = bbcs_wp_ability_period_days( $period );
		$start     = gmdate( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) );
		$end       = gmdate( 'Y-m-d', time() );

		$summary_map = array( 'country' => 'top_country_h', 'ua' => 'grp_wbot' );
		if ( isset( $summary_map[ $dimension ] ) && class_exists( 'BotBlockerSummary' ) && BotBlockerSummary::isTableReady() ) {
			$values = BotBlockerSummary::getDimensions( $summary_map[ $dimension ], $start, $end, $limit );
			if ( empty( $values ) && $dimension === 'ua' && class_exists( 'BotBlockerSummary' ) ) {
				$values = BotBlockerSummary::getDimensions( 'grp_browser', $start, $end, $limit );
			}
			if ( ! empty( $values ) ) {
				$items = array();
				foreach ( $values as $key => $count ) {
					$items[] = array( 'key' => (string) $key, 'count' => (int) $count );
				}
				return array( 'dimension' => $dimension, 'period' => $period, 'source' => 'daily_summary', 'items' => $items );
			}
		}

		$column_map = array( 'asn' => 'asnum', 'status' => 'result', 'path' => 'page' );
		$column     = $column_map[ $dimension ] ?? '';
		if ( $column === '' || empty( $wpdb->bbcs_hits ) || empty( $wpdb->bbcs_hits_suspicious ) ) {
			return array( 'dimension' => $dimension, 'period' => $period, 'source' => 'none', 'items' => array() );
		}
		$since = time() - ( $days * DAY_IN_SECONDS );
		$sql   = "SELECT `{$column}` AS k, COUNT(*) AS c FROM (
			SELECT `{$column}` FROM `{$wpdb->bbcs_hits}` WHERE `date` >= %d
			UNION ALL
			SELECT `{$column}` FROM `{$wpdb->bbcs_hits_suspicious}` WHERE `date` >= %d
		) AS combined WHERE `{$column}` <> '' GROUP BY `{$column}` ORDER BY c DESC LIMIT %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- column from whitelist; tables from $wpdb.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $since, $since, $limit ), ARRAY_A );
		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = array( 'key' => (string) $row['k'], 'count' => (int) $row['c'] );
		}
		return array( 'dimension' => $dimension, 'period' => $period, 'source' => 'raw', 'items' => $items );
	}

	function bbcs_wp_ability_execute_get_2fa_summary( $input = null ) {
		unset( $input );
		$guard = bbcs_wp_ability_guard( 'botblocker/get-2fa-summary', null );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$users   = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID' ) ) );
		$total   = count( $users );
		$with    = 0;
		$pending = 0;
		foreach ( $users as $user ) {
			$id      = (int) $user->ID;
			$secret  = get_user_meta( $id, '_2fa_secret', true );
			$verified = get_user_meta( $id, '_2fa_verified', true );
			if ( ! empty( $secret ) && ! empty( $verified ) ) {
				++$with;
			} elseif ( ! empty( $secret ) || ! empty( get_user_meta( $id, '_2fa_secret_temp', true ) ) ) {
				++$pending;
			}
		}
		$settings = BotBlocker::getInstance()->settings;
		return array(
			'enabled'            => is_object( $settings ) && ! empty( $settings->bbcs_2fa_enable ),
			'xmlrpc_block'       => is_object( $settings ) && ! empty( $settings->bbcs_2fa_xmlrpc_block ),
			'admins_total'       => $total,
			'admins_with_2fa'    => $with,
			'admins_without_2fa' => max( 0, $total - $with ),
			'admins_pending'     => $pending,
		);
	}

	function bbcs_wp_ability_execute_get_audit_log( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-audit-log', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$mask = bbcs_ability_mask_enabled( $input );
		$args = array(
			'event_key'  => bbcs_ability_input_string( $input, 'event_key' ),
			'category'   => bbcs_ability_input_string( $input, 'category' ),
			'severity'   => bbcs_ability_input_string( $input, 'severity' ),
			'actor_id'   => bbcs_wp_ability_input_int( $input, 'actor_id', 0 ),
			'search'     => bbcs_ability_input_string( $input, 'search' ),
			'date_from'  => bbcs_wp_ability_input_int( $input, 'date_from', 0 ),
			'date_to'    => bbcs_wp_ability_input_int( $input, 'date_to', 0 ),
			'start'      => bbcs_wp_ability_input_int( $input, 'offset', 0 ),
			'length'     => max( 1, min( 200, bbcs_wp_ability_input_int( $input, 'limit', 50 ) ) ),
		);
		return bbcs_ability_audit_rows( $args, $mask );
	}

	function bbcs_wp_ability_execute_get_event_details( $input = null ) {
		global $wpdb;
		$guard = bbcs_wp_ability_guard( 'botblocker/get-event-details', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$cid = bbcs_ability_input_string( $input, 'cid' );
		if ( $cid === '' ) {
			return new WP_Error( 'ability_invalid_input', __( 'A client id is required.', 'botblocker-security' ) );
		}
		$table = bbcs_ability_input_enum( $input, 'table', array( 'hits', 'suspicious' ), 'suspicious' );
		$name  = 'hits' === $table ? $wpdb->bbcs_hits : $wpdb->bbcs_hits_suspicious;
		if ( empty( $name ) ) {
			return new WP_Error( 'ability_unavailable', __( 'Traffic tables are not available.', 'botblocker-security' ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$name}` WHERE `cid` = %s LIMIT 1", $cid ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return new WP_Error( 'ability_not_found', __( 'The event was not found.', 'botblocker-security' ) );
		}
		$keep = array( 'cid', 'ip', 'date', 'country', 'country_name', 'asnum', 'asname', 'browser', 'os', 'device', 'method', 'page', 'referer', 'useragent', 'result', 'access', 'passed', 'proxy', 'tor', 'vpn', 'recaptcha', 'hosting', 'hit' );
		$event = array();
		foreach ( $keep as $key ) {
			if ( array_key_exists( $key, $row ) ) {
				$event[ $key ] = $row[ $key ];
			}
		}
		$event['date'] = bbcs_ability_iso_time( $event['date'] ?? 0 );
		$mask  = bbcs_ability_mask_enabled( $input );
		return array(
			'event'  => bbcs_ability_mask_rows( array( $event ), $mask )[0],
			'masked' => $mask,
		);
	}

	function bbcs_wp_ability_execute_get_top_ips( $input = null ) {
		global $wpdb;
		$guard = bbcs_wp_ability_guard( 'botblocker/get-top-ips', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$period = bbcs_ability_input_enum( $input, 'period', array( 'day', 'week', 'month' ), 'day' );
		$limit  = max( 1, min( 100, bbcs_wp_ability_input_int( $input, 'limit', 20 ) ) );
		$mask   = bbcs_ability_mask_enabled( $input );
		$days   = bbcs_wp_ability_period_days( $period );
		$start  = gmdate( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) );
		$end    = gmdate( 'Y-m-d', time() );
		$source = 'daily_summary';
		$values = array();

		if ( class_exists( 'BotBlockerSummary' ) && BotBlockerSummary::isTableReady() ) {
			$values = BotBlockerSummary::getDimensions( 'top_ip', $start, $end, $limit );
		}
		if ( empty( $values ) && ! empty( $wpdb->bbcs_hits ) ) {
			$source = 'raw';
			$since  = time() - ( $days * DAY_IN_SECONDS );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT `ip` AS k, COUNT(*) AS c FROM `{$wpdb->bbcs_hits}` WHERE `date` >= %d AND `ip` <> '' GROUP BY `ip` ORDER BY c DESC LIMIT %d", $since, $limit ), ARRAY_A );
			foreach ( (array) $rows as $row ) {
				$values[ (string) $row['k'] ] = (int) $row['c'];
			}
		}
		$items = array();
		foreach ( $values as $ip => $count ) {
			$items[] = array( 'ip' => (string) $ip, 'count' => (int) $count );
		}
		if ( $mask ) {
			foreach ( $items as $index => $item ) {
				$items[ $index ]['ip'] = bbcs_ability_mask_ip( (string) $item['ip'] );
			}
		}
		return array( 'period' => $period, 'source' => $source, 'items' => $items, 'masked' => $mask );
	}

	function bbcs_wp_ability_execute_get_login_attempts( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-login-attempts', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$period = bbcs_ability_input_enum( $input, 'period', array( 'day', 'week', 'month' ), 'week' );
		$status = bbcs_ability_input_enum( $input, 'status', array( 'success', 'failed', 'all' ), 'failed' );
		$mask   = bbcs_ability_mask_enabled( $input );
		$args   = array(
			'start'     => bbcs_wp_ability_input_int( $input, 'offset', 0 ),
			'length'    => max( 1, min( 200, bbcs_wp_ability_input_int( $input, 'limit', 50 ) ) ),
			'date_from' => time() - ( bbcs_wp_ability_period_days( $period ) * DAY_IN_SECONDS ),
		);
		if ( 'all' === $status ) {
			$args['category'] = 'auth.login';
		} else {
			$args['event_key'] = 'success' === $status ? 'auth.login.success' : 'auth.login.failed';
		}
		return bbcs_ability_audit_rows( $args, $mask );
	}

	function bbcs_wp_ability_execute_get_visitor_log( $input = null ) {
		global $wpdb;
		$guard = bbcs_wp_ability_guard( 'botblocker/get-visitor-log', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$period = bbcs_ability_input_enum( $input, 'period', array( 'day', 'week', 'month' ), 'day' );
		$table  = bbcs_ability_input_enum( $input, 'table', array( 'hits', 'suspicious', 'both' ), 'suspicious' );
		$limit  = max( 1, min( 200, bbcs_wp_ability_input_int( $input, 'limit', 50 ) ) );
		$offset = max( 0, bbcs_wp_ability_input_int( $input, 'offset', 0 ) );
		$mask   = bbcs_ability_mask_enabled( $input );
		$since  = time() - ( bbcs_wp_ability_period_days( $period ) * DAY_IN_SECONDS );

		$tables = array();
		if ( 'hits' === $table ) {
			$tables[] = $wpdb->bbcs_hits;
		} elseif ( 'suspicious' === $table ) {
			$tables[] = $wpdb->bbcs_hits_suspicious;
		} else {
			$tables[] = $wpdb->bbcs_hits;
			$tables[] = $wpdb->bbcs_hits_suspicious;
		}

		$rows = array();
		foreach ( $tables as $name ) {
			if ( empty( $name ) ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb.
			$found = $wpdb->get_results( $wpdb->prepare( "SELECT `cid`, `ip`, `date`, `country`, `asnum`, `asname`, `browser`, `os`, `device`, `method`, `page`, `referer`, `useragent`, `result`, `access`, `passed`, `proxy`, `tor`, `vpn` FROM `{$name}` WHERE `date` >= %d ORDER BY `date` DESC LIMIT %d OFFSET %d", $since, $limit, $offset ), ARRAY_A );
			foreach ( (array) $found as $row ) {
				$rows[] = $row;
			}
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				return (int) $b['date'] <=> (int) $a['date'];
			}
		);
		$rows = array_slice( $rows, 0, $limit );
		foreach ( $rows as $index => $row ) {
			$rows[ $index ]['date'] = bbcs_ability_iso_time( $row['date'] ?? 0 );
		}
		return array(
			'rows'   => bbcs_ability_mask_rows( $rows, $mask ),
			'masked' => $mask,
		);
	}

	function bbcs_wp_ability_execute_get_user_risk( $input = null ) {
		global $wpdb;
		$guard = bbcs_wp_ability_guard( 'botblocker/get-user-risk', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$mask   = bbcs_ability_mask_enabled( $input );
		$limit  = max( 1, min( 100, bbcs_wp_ability_input_int( $input, 'limit', 25 ) ) );
		$offset = max( 0, bbcs_wp_ability_input_int( $input, 'offset', 0 ) );
		$role   = bbcs_ability_input_string( $input, 'role' );
		$args   = array( 'number' => $limit, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC' );
		if ( $role !== '' ) {
			$args['role'] = $role;
		}
		$users = get_users( $args );
		$since = time() - ( 30 * DAY_IN_SECONDS );
		$rows  = array();
		foreach ( $users as $user ) {
			$id      = (int) $user->ID;
			$login   = (string) $user->user_login;
			$failed  = 0;
			if ( ! empty( $wpdb->bbcs_audit_log ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$failed = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `{$wpdb->bbcs_audit_log}` WHERE `event_key` = %s AND `created_at` >= %d AND ( `actor_user_id` = %d OR `data` LIKE %s )",
						'auth.login.failed',
						$since,
						$id,
						'%' . $wpdb->esc_like( '"login":"' . $login . '"' ) . '%'
					)
				);
			}
			$secret   = get_user_meta( $id, '_2fa_secret', true );
			$verified = get_user_meta( $id, '_2fa_verified', true );
			$rows[]   = array(
				'user_id'          => $id,
				'login'            => $login,
				'email'            => (string) $user->user_email,
				'roles'            => array_values( (array) $user->roles ),
				'registered'       => bbcs_ability_iso_time( strtotime( (string) $user->user_registered ) ),
				'2fa_enabled'      => ! empty( $secret ) && ! empty( $verified ),
				'2fa_pending'      => ! empty( get_user_meta( $id, '_2fa_secret_temp', true ) ),
				'failed_logins_30d' => $failed,
			);
		}
		return array(
			'rows'   => bbcs_ability_mask_rows( $rows, $mask ),
			'masked' => $mask,
		);
	}

	function bbcs_wp_ability_execute_get_ability_calls( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-ability-calls', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$period  = bbcs_ability_input_enum( $input, 'period', array( 'day', 'week', 'month' ), 'week' );
		$ability = bbcs_ability_input_string( $input, 'ability' );
		$outcome = bbcs_ability_input_string( $input, 'outcome' );
		$mask    = bbcs_ability_mask_enabled( $input );
		$args    = array(
			'event_key'  => 'botblocker.ability.call',
			'start'      => bbcs_wp_ability_input_int( $input, 'offset', 0 ),
			'length'     => max( 1, min( 200, bbcs_wp_ability_input_int( $input, 'limit', 50 ) ) ),
			'date_from'  => time() - ( bbcs_wp_ability_period_days( $period ) * DAY_IN_SECONDS ),
		);
		$result = bbcs_ability_audit_rows( $args, $mask );
		if ( $ability === '' && $outcome === '' ) {
			return $result;
		}
		$rows  = array();
		$total = 0;
		foreach ( $result['rows'] as $row ) {
			$data = is_array( $row['data'] ) ? $row['data'] : array();
			if ( $ability !== '' && (string) ( $data['ability'] ?? '' ) !== $ability ) {
				continue;
			}
			if ( $outcome !== '' && (string) ( $data['outcome'] ?? '' ) !== $outcome ) {
				continue;
			}
			++$total;
			$rows[] = array(
				'id'          => (int) $row['id'],
				'created_at'  => (string) $row['created_at'],
				'ability'     => (string) ( $data['ability'] ?? '' ),
				'outcome'     => (string) ( $data['outcome'] ?? '' ),
				'error'       => (string) ( $data['error'] ?? '' ),
				'duration_ms' => (int) ( $data['duration_ms'] ?? 0 ),
				'actor_user_id' => (int) $row['actor_user_id'],
				'ip'          => (string) $row['ip'],
				'masked'      => $mask,
			);
		}
		return array( 'rows' => $rows, 'total' => $total, 'masked' => $mask );
	}
}
