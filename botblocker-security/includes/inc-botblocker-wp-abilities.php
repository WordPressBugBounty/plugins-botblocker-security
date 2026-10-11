<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_get_wp_ability_definitions' ) ) {

	function bbcs_get_wp_ability_definitions(): array {
		return array(
			array(
				'name'          => 'botblocker/get-firewall-status',
				'setting_key'   => 'bbcs_ability_get_firewall_status',
				'label'         => __( 'Get firewall status', 'botblocker-security' ),
				'description'   => __( 'Returns the current BotBlocker protection mode, enabled state and plugin version.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'secure_mode' => array( 'type' => 'integer' ),
						'enabled'     => array( 'type' => 'boolean' ),
						'version'     => array( 'type' => 'string' ),
					),
					'required'   => array( 'secure_mode', 'enabled', 'version' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_get_firewall_status',
			),
			array(
				'name'          => 'botblocker/check-ip',
				'setting_key'   => 'bbcs_ability_check_ip',
				'label'         => __( 'Check an IP address', 'botblocker-security' ),
				'description'   => __( 'Checks whether an IP address matches any configured firewall rule and returns the matched rules.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip' => array( 'type' => 'string' ),
					),
					'required'   => array( 'ip' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'blocked' => array( 'type' => 'boolean' ),
						'reason'  => array( 'type' => 'string' ),
						'score'   => array( 'type' => 'integer' ),
						'rules'   => array( 'type' => 'array' ),
					),
					'required'   => array( 'blocked', 'reason', 'score', 'rules' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_check_ip',
			),
			array(
				'name'          => 'botblocker/get-ip-rules',
				'setting_key'   => 'bbcs_ability_get_ip_rules',
				'label'         => __( 'Get IP rules', 'botblocker-security' ),
				'description'   => __( 'Returns the firewall rules matching a given IP address.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip' => array( 'type' => 'string' ),
					),
					'required'   => array( 'ip' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'rules' => array( 'type' => 'array' ),
					),
					'required'   => array( 'rules' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_get_ip_rules',
			),
			array(
				'name'          => 'botblocker/get-traffic-summary',
				'setting_key'   => 'bbcs_ability_get_traffic_summary',
				'label'         => __( 'Get traffic summary', 'botblocker-security' ),
				'description'   => __( 'Returns aggregated request, block, captcha and proxy counters for a period.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'default'    => array(),
					'properties' => array(
						'period' => array(
							'type'    => 'string',
							'enum'    => array( 'day', 'week', 'month' ),
							'default' => 'day',
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'hits'       => array( 'type' => 'integer' ),
						'blocks'     => array( 'type' => 'integer' ),
						'captcha'    => array( 'type' => 'integer' ),
						'suspicious' => array( 'type' => 'integer' ),
						'proxy'      => array( 'type' => 'integer' ),
					),
					'required'   => array( 'hits', 'blocks', 'captcha', 'suspicious', 'proxy' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_get_traffic_summary',
			),
			array(
				'name'          => 'botblocker/run-diagnostics',
				'setting_key'   => 'bbcs_ability_run_diagnostics',
				'label'         => __( 'Run diagnostics', 'botblocker-security' ),
				'description'   => __( 'Runs a set of read-only health checks against the BotBlocker data directory and database tables.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'passed' => array( 'type' => 'boolean' ),
						'items'  => array( 'type' => 'array' ),
					),
					'required'   => array( 'passed', 'items' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_run_diagnostics',
			),
			array(
				'name'          => 'botblocker/block-ip',
				'setting_key'   => 'bbcs_ability_block_ip',
				'label'         => __( 'Block an IP address', 'botblocker-security' ),
				'description'   => __( 'Adds a firewall block rule for an IP address and regenerates the runtime ban files.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'      => array( 'type' => 'string' ),
						'reason'  => array( 'type' => 'string' ),
						'expires' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'ip' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'added' => array( 'type' => 'boolean' ),
						'id'    => array( 'type' => 'integer' ),
					),
					'required'   => array( 'added', 'id' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_block_ip',
			),
			array(
				'name'          => 'botblocker/unblock-ip',
				'setting_key'   => 'bbcs_ability_unblock_ip',
				'label'         => __( 'Unblock an IP address', 'botblocker-security' ),
				'description'   => __( 'Removes firewall rules for an IP address and regenerates the runtime ban files.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip' => array( 'type' => 'string' ),
					),
					'required'   => array( 'ip' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'removed' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'removed' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_unblock_ip',
			),
			array(
				'name'          => 'botblocker/run-malware-scan',
				'setting_key'   => 'bbcs_ability_run_malware_scan',
				'label'         => __( 'Run a malware scan', 'botblocker-security' ),
				'description'   => __( 'Starts a background malware scan through the BotBlocker Malware Scanner add-on.', 'botblocker-security' ),
				'addon'         => 'bbcs-malware',
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'scan_id' => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string' ),
					),
					'required'   => array( 'scan_id', 'status' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_run_malware_scan',
			),
			array(
				'name'          => 'botblocker/get-scan-results',
				'setting_key'   => 'bbcs_ability_get_scan_results',
				'label'         => __( 'Get malware scan results', 'botblocker-security' ),
				'description'   => __( 'Returns the status and findings of the latest malware scan from the BotBlocker Malware Scanner add-on.', 'botblocker-security' ),
				'addon'         => 'bbcs-malware',
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'scan_id' => array( 'type' => 'string' ),
					),
					'required'   => array( 'scan_id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'status'   => array( 'type' => 'string' ),
						'findings' => array( 'type' => 'array' ),
					),
					'required'   => array( 'status', 'findings' ),
				),
				'execute'       => 'bbcs_wp_ability_execute_get_scan_results',
			),
		);
	}

	function bbcs_wp_ability_allowed( string $key ): bool {
		$BBCS = BotBlocker::getInstance();
		if ( ! is_object( $BBCS->settings ) ) {
			return false;
		}
		return isset( $BBCS->settings->$key ) && (int) $BBCS->settings->$key === 1;
	}

	function bbcs_wp_ability_addon_active( string $slug ): bool {
		if ( ! class_exists( 'BotBlockerAddons' ) ) {
			return false;
		}
		return BotBlockerAddons::isActive( $slug );
	}

	function bbcs_ability_permission_admin() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return new WP_Error(
			'rest_forbidden',
			__( 'You are not allowed to execute BotBlocker abilities.', 'botblocker-security' )
		);
	}

	function bbcs_register_wp_ability_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		if ( ! bbcs_wp_ability_allowed( 'bbcs_wp_abilities_enabled' ) ) {
			return;
		}
		wp_register_ability_category(
			'botblocker',
			array(
				'label'       => __( 'BotBlocker Security', 'botblocker-security' ),
				'description' => __( 'Administrative abilities exposed by the BotBlocker Security plugin.', 'botblocker-security' ),
			)
		);
	}

	function bbcs_register_wp_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		if ( ! bbcs_wp_ability_allowed( 'bbcs_wp_abilities_enabled' ) ) {
			return;
		}
		foreach ( bbcs_get_wp_ability_definitions() as $definition ) {
			if ( ! bbcs_wp_ability_allowed( $definition['setting_key'] ) ) {
				continue;
			}
			if ( isset( $definition['addon'] ) && ! bbcs_wp_ability_addon_active( $definition['addon'] ) ) {
				continue;
			}
			wp_register_ability(
				$definition['name'],
				array(
					'label'               => $definition['label'],
					'description'         => $definition['description'],
					'category'            => 'botblocker',
					'execute_callback'    => $definition['execute'],
					'permission_callback' => 'bbcs_ability_permission_admin',
					'input_schema'        => $definition['input_schema'],
					'output_schema'       => $definition['output_schema'],
					'meta'                => array(
						'public'       => true,
						'show_in_rest' => true,
						'annotations'  => $definition['annotations'],
					),
				)
			);
		}
	}

	function bbcs_wp_ability_audit_log( $ability_name, $input, $ability ): void {
		if ( ! defined( 'BBCS_DEBUG' ) || ! BBCS_DEBUG ) {
			return;
		}
		if ( ! is_string( $ability_name ) || $ability_name === '' ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
		error_log( '[BBCS DEBUG] [Abilities] invoked ' . $ability_name );
	}

	function bbcs_wp_ability_input_ip( $input ) {
		if ( ! is_array( $input ) || ! isset( $input['ip'] ) || ! is_scalar( $input['ip'] ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address is required.', 'botblocker-security' ) );
		}
		$ip = sanitize_text_field( (string) $input['ip'] );
		if ( $ip === '' || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address is required.', 'botblocker-security' ) );
		}
		return $ip;
	}

	function bbcs_wp_ability_lookup_ip_rules( string $ip ): array {
		global $wpdb;

		$version = BotBlockerIp::getVersion( $ip );
		if ( 4 === $version ) {
			$table  = $wpdb->bbcs_ipv4rules;
			$value  = (string) BotBlockerIp::toNumeric( $ip );
			$format = '%d';
		} elseif ( 6 === $version ) {
			$table  = $wpdb->bbcs_ipv6rules;
			$binary = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );
			if ( false === $binary ) {
				return array();
			}
			$value  = $binary;
			$format = '%s';
		} else {
			return array();
		}

		if ( empty( $table ) ) {
			return array();
		}

		$sql = "SELECT `search`, `rule`, `comment`, `expires` FROM `{$table}`
			WHERE `disable` = 0 AND ( `search` = %s OR ( `ip1` <= {$format} AND `ip2` >= {$format} ) )
			ORDER BY `priority` ASC, `id` ASC";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $ip, $value, $value ), ARRAY_A );

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				'type'    => (string) $row['rule'],
				'value'   => (string) $row['search'],
				'expires' => (int) $row['expires'],
				'comment' => (string) $row['comment'],
			);
		}
		return $out;
	}

	function bbcs_wp_ability_period_days( string $period ): int {
		if ( 'week' === $period ) {
			return 7;
		}
		if ( 'month' === $period ) {
			return 30;
		}
		return 1;
	}

	function bbcs_wp_ability_execute_get_firewall_status( $input = null ) {
		$BBCS     = BotBlocker::getInstance();
		$settings = is_object( $BBCS->settings ) ? $BBCS->settings : null;

		return array(
			'secure_mode' => $settings && isset( $settings->secure_mode ) ? (int) $settings->secure_mode : 0,
			'enabled'     => $settings && isset( $settings->disable ) ? (int) $settings->disable !== 1 : false,
			'version'     => defined( 'BOTBLOCKER_VERSION' ) ? (string) BOTBLOCKER_VERSION : '',
		);
	}

	function bbcs_wp_ability_execute_check_ip( $input = null ) {
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$rules    = bbcs_wp_ability_lookup_ip_rules( $ip );
		$blocked  = false;
		$reason   = '';
		foreach ( $rules as $rule ) {
			if ( $rule['type'] !== BBCS_RULE_ALLOW ) {
				$blocked = true;
				if ( $reason === '' ) {
					$reason = $rule['comment'] !== '' ? $rule['comment'] : $rule['type'];
				}
			}
		}

		return array(
			'blocked' => $blocked,
			'reason'  => $reason,
			'score'   => count( $rules ),
			'rules'   => $rules,
		);
	}

	function bbcs_wp_ability_execute_get_ip_rules( $input = null ) {
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}
		return array( 'rules' => bbcs_wp_ability_lookup_ip_rules( $ip ) );
	}

	function bbcs_wp_ability_execute_get_traffic_summary( $input = null ) {
		global $wpdb;

		$period = ( is_array( $input ) && isset( $input['period'] ) ) ? (string) $input['period'] : 'day';
		if ( ! in_array( $period, array( 'day', 'week', 'month' ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported period.', 'botblocker-security' ) );
		}

		$hits_table = isset( $wpdb->bbcs_hits ) ? $wpdb->bbcs_hits : '';
		$sus_table  = isset( $wpdb->bbcs_hits_suspicious ) ? $wpdb->bbcs_hits_suspicious : '';
		if ( $hits_table === '' || $sus_table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Traffic tables are not available.', 'botblocker-security' ) );
		}

		$start = time() - ( bbcs_wp_ability_period_days( $period ) * DAY_IN_SECONDS );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$hits = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$hits_table}` WHERE `date` >= %d", $start ) );
		$blocks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$sus_table}` WHERE `date` >= %d", $start ) );
		$captcha = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$hits_table}` WHERE `date` >= %d AND `recaptcha` NOT IN ('', '0')", $start ) )
			+ (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$sus_table}` WHERE `date` >= %d AND `recaptcha` NOT IN ('', '0')", $start ) );
		$proxy = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$hits_table}` WHERE `date` >= %d AND `proxy` NOT IN ('', '0')", $start ) )
			+ (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$sus_table}` WHERE `date` >= %d AND `proxy` NOT IN ('', '0')", $start ) );
		$suspicious = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$sus_table}` WHERE `date` >= %d AND `passed` = 0", $start ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'hits'       => $hits + $blocks,
			'blocks'     => $blocks,
			'captcha'    => $captcha,
			'suspicious' => $suspicious,
			'proxy'      => $proxy,
		);
	}

	function bbcs_wp_ability_execute_run_diagnostics( $input = null ) {
		$data_dir = class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getDataDir() : '';

		$items = array(
			array(
				'key'    => 'data_dir',
				'label'  => __( 'Data directory writable', 'botblocker-security' ),
				'passed' => $data_dir !== '' && is_dir( $data_dir ) && wp_is_writable( $data_dir ),
			),
			array(
				'key'    => 'settings_file',
				'label'  => __( 'Runtime settings file present', 'botblocker-security' ),
				'passed' => $data_dir !== '' && file_exists( $data_dir . 'settings.php' ),
			),
			array(
				'key'    => 'tables',
				'label'  => __( 'Database tables present', 'botblocker-security' ),
				'passed' => class_exists( 'BotBlockerInstall' ) && BotBlockerInstall::tablesExist( true ),
			),
		);

		$passed = true;
		foreach ( $items as $item ) {
			if ( ! $item['passed'] ) {
				$passed = false;
			}
		}

		return array(
			'passed' => $passed,
			'items'  => $items,
		);
	}

	function bbcs_wp_ability_execute_block_ip( $input = null ) {
		global $wpdb;

		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$version = BotBlockerIp::getVersion( $ip );
		if ( 4 === $version ) {
			$table = $wpdb->bbcs_ipv4rules;
			$value = (string) BotBlockerIp::toNumeric( $ip );
		} elseif ( 6 === $version ) {
			$table  = $wpdb->bbcs_ipv6rules;
			$binary = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );
			if ( false === $binary ) {
				return new WP_Error( 'ability_invalid_input', __( 'A valid IP address is required.', 'botblocker-security' ) );
			}
			$value = $binary;
		} else {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address is required.', 'botblocker-security' ) );
		}

		if ( empty( $table ) ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}

		$reason = ( is_array( $input ) && isset( $input['reason'] ) && is_scalar( $input['reason'] ) )
			? sanitize_text_field( (string) $input['reason'] )
			: '';
		if ( $reason === '' ) {
			$reason = __( 'Blocked via WP Abilities', 'botblocker-security' );
		}

		$expires = ( is_array( $input ) && isset( $input['expires'] ) ) ? (int) $input['expires'] : 0;
		if ( $expires <= 0 ) {
			$expires = BOTBLOCKER_EXP_INF;
		}

		$data = array(
			'priority' => 100,
			'search'   => $ip,
			'ip1'      => $value,
			'ip2'      => $value,
			'rule'     => BBCS_RULE_BLOCK,
			'comment'  => $reason,
			'expires'  => $expires,
			'disable'  => 0,
			'readonly' => 0,
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `{$table}` WHERE `search` = %s", $ip ) );
		if ( $existing_id > 0 ) {
			$saved = $wpdb->update( $table, $data, array( 'id' => $existing_id ) );
			$id    = $existing_id;
		} else {
			$saved = $wpdb->insert( $table, $data );
			$id    = (int) $wpdb->insert_id;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( false === $saved ) {
			return new WP_Error( 'ability_execution_failed', __( 'The IP rule could not be saved.', 'botblocker-security' ) );
		}

		BotBlockerFileRenderer::syncIpBanFiles( $ip, BBCS_RULE_BLOCK, $expires );

		return array(
			'added' => true,
			'id'    => $id,
		);
	}

	function bbcs_wp_ability_execute_unblock_ip( $input = null ) {
		global $wpdb;

		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$version = BotBlockerIp::getVersion( $ip );
		if ( 4 === $version ) {
			$table = $wpdb->bbcs_ipv4rules;
		} elseif ( 6 === $version ) {
			$table = $wpdb->bbcs_ipv6rules;
		} else {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address is required.', 'botblocker-security' ) );
		}

		if ( empty( $table ) ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->delete( $table, array( 'search' => $ip ), array( '%s' ) );

		BotBlockerFileRenderer::removeHotBan( $ip );
		BotBlockerFileRenderer::renderIps();
		BotBlockerCache::clearFileCache();

		return array( 'removed' => false !== $deleted && $deleted > 0 );
	}

	function bbcs_wp_ability_execute_run_malware_scan( $input = null ) {
		if ( ! bbcs_wp_ability_addon_active( 'bbcs-malware' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The malware scanner add-on is not active.', 'botblocker-security' ) );
		}
		if ( ! class_exists( 'BBCS_Malware_Plugin' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The malware scanner is not available.', 'botblocker-security' ) );
		}

		$plugin = BBCS_Malware_Plugin::instance();
		if ( ! method_exists( $plugin, 'getBackground' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The malware scanner is not available.', 'botblocker-security' ) );
		}

		$state = $plugin->getBackground()->startBackgroundScan( array(), 'ability' );

		return array(
			'scan_id' => ( is_array( $state ) && isset( $state['id'] ) ) ? (string) $state['id'] : '',
			'status'  => ( is_array( $state ) && isset( $state['status'] ) ) ? (string) $state['status'] : '',
		);
	}

	function bbcs_wp_ability_execute_get_scan_results( $input = null ) {
		if ( ! bbcs_wp_ability_addon_active( 'bbcs-malware' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The malware scanner add-on is not active.', 'botblocker-security' ) );
		}
		if ( ! class_exists( 'BBCS_Malware_Plugin' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The malware scanner is not available.', 'botblocker-security' ) );
		}

		$plugin = BBCS_Malware_Plugin::instance();
		if ( ! method_exists( $plugin, 'getSettings' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The malware scanner is not available.', 'botblocker-security' ) );
		}

		$result   = $plugin->getSettings()->getLastResult();
		$findings = array();
		if ( is_array( $result ) && isset( $result['findings'] ) && is_array( $result['findings'] ) ) {
			foreach ( $result['findings'] as $finding ) {
				if ( ! is_array( $finding ) ) {
					continue;
				}
				$findings[] = array(
					'file'           => isset( $finding['relative_path'] ) ? (string) $finding['relative_path'] : ( isset( $finding['file'] ) ? (string) $finding['file'] : '' ),
					'severity'       => isset( $finding['severity'] ) ? (string) $finding['severity'] : '',
					'classification' => isset( $finding['classification'] ) ? (string) $finding['classification'] : '',
				);
			}
		}

		return array(
			'status'   => ( is_array( $result ) && isset( $result['status'] ) ) ? (string) $result['status'] : '',
			'findings' => $findings,
		);
	}

	add_action( 'wp_abilities_api_categories_init', 'bbcs_register_wp_ability_categories' );
	add_action( 'wp_abilities_api_init', 'bbcs_register_wp_abilities' );
	add_action( 'wp_ability_invoked', 'bbcs_wp_ability_audit_log', 10, 3 );
}
