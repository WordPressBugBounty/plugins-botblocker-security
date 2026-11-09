<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_get_wp_ability_definitions' ) ) {

	function bbcs_get_wp_ability_definitions(): array {
		$definitions = array(
			array(
				'name'          => 'botblocker/get-firewall-status',
				'setting_key'   => 'bbcs_ability_get_firewall_status',
				'label'         => __( 'Get firewall status', 'botblocker-security' ),
				'description'   => __( 'Returns the current protection state, secure mode, CAPTCHA mode and plugin version.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'secure_mode'         => array( 'type' => 'integer' ),
						'secure_mode_label'   => array( 'type' => 'string' ),
						'enabled'             => array( 'type' => 'boolean' ),
						'protection_enabled'  => array( 'type' => 'boolean' ),
						'captcha_mode'        => array( 'type' => 'integer' ),
						'captcha_mode_label'  => array( 'type' => 'string' ),
						'version'             => array( 'type' => 'string' ),
					),
					'required'             => array( 'secure_mode', 'secure_mode_label', 'enabled', 'protection_enabled', 'captcha_mode', 'captcha_mode_label', 'version' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_firewall_status',
			),
			array(
				'name'          => 'botblocker/check-ip',
				'setting_key'   => 'bbcs_ability_check_ip',
				'label'         => __( 'Check an IP address', 'botblocker-security' ),
				'description'   => __( 'Checks whether an IP address is blocked by the firewall engine, including the deciding rule, expired rules and active hot-bans.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip' => array( 'type' => 'string' ),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'blocked'            => array( 'type' => 'boolean' ),
						'reason'             => array( 'type' => 'string' ),
						'source'             => array( 'type' => 'string' ),
						'matched_rules'      => array( 'type' => 'integer' ),
						'protection_enabled' => array( 'type' => 'boolean' ),
						'rules'              => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'       => array( 'type' => 'integer' ),
									'type'     => array( 'type' => 'string' ),
									'value'    => array( 'type' => 'string' ),
									'family'   => array( 'type' => 'string' ),
									'priority' => array( 'type' => 'integer' ),
									'expires'  => array( 'type' => 'integer' ),
									'expired'  => array( 'type' => 'boolean' ),
									'readonly' => array( 'type' => 'boolean' ),
									'comment'  => array( 'type' => 'string' ),
								),
								'required'             => array( 'id', 'type', 'value', 'family', 'priority', 'expires', 'expired', 'readonly', 'comment' ),
								'additionalProperties' => false,
							),
						),
					),
					'required'             => array( 'blocked', 'reason', 'source', 'matched_rules', 'protection_enabled', 'rules' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_check_ip',
			),
			array(
				'name'          => 'botblocker/check-ips',
				'setting_key'   => 'bbcs_ability_check_ips',
				'label'         => __( 'Check several IP addresses', 'botblocker-security' ),
				'description'   => __( 'Checks a batch of IP addresses against the firewall engine and returns one result per address.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ips' => array(
							'type'     => 'array',
							'minItems' => 1,
							'maxItems' => 20,
							'items'    => array( 'type' => 'string' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'results' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'ip'            => array( 'type' => 'string' ),
									'blocked'       => array( 'type' => 'boolean' ),
									'reason'        => array( 'type' => 'string' ),
									'source'        => array( 'type' => 'string' ),
									'matched_rules' => array( 'type' => 'integer' ),
								),
								'required'             => array( 'ip', 'blocked', 'reason', 'source', 'matched_rules' ),
								'additionalProperties' => false,
							),
						),
						'total'   => array( 'type' => 'integer' ),
					),
					'required'             => array( 'results', 'total' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_check_ips',
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
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'       => array( 'type' => 'integer' ),
									'type'     => array( 'type' => 'string' ),
									'value'    => array( 'type' => 'string' ),
									'family'   => array( 'type' => 'string' ),
									'priority' => array( 'type' => 'integer' ),
									'expires'  => array( 'type' => 'integer' ),
									'expired'  => array( 'type' => 'boolean' ),
									'readonly' => array( 'type' => 'boolean' ),
									'comment'  => array( 'type' => 'string' ),
								),
								'required'             => array( 'id', 'type', 'value', 'family', 'priority', 'expires', 'expired', 'readonly', 'comment' ),
								'additionalProperties' => false,
							),
						),
					),
					'required'             => array( 'rules' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_ip_rules',
			),
			array(
				'name'          => 'botblocker/list-ip-rules',
				'setting_key'   => 'bbcs_ability_list_ip_rules',
				'label'         => __( 'List IP rules', 'botblocker-security' ),
				'description'   => __( 'Lists firewall rules with filters for family, rule type and search text, with pagination.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'family'      => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ) ),
						'rule'        => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark' ) ),
						'search'      => array( 'type' => 'string' ),
						'active_only' => array( 'type' => 'boolean', 'default' => false ),
						'limit'       => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20 ),
						'offset'      => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'       => array( 'type' => 'integer' ),
									'search'   => array( 'type' => 'string' ),
									'rule'     => array( 'type' => 'string' ),
									'family'   => array( 'type' => 'string' ),
									'priority' => array( 'type' => 'integer' ),
									'expires'  => array( 'type' => 'integer' ),
									'readonly' => array( 'type' => 'boolean' ),
									'comment'  => array( 'type' => 'string' ),
								),
								'required'             => array( 'id', 'search', 'rule', 'family', 'priority', 'expires', 'readonly', 'comment' ),
								'additionalProperties' => false,
							),
						),
						'total' => array( 'type' => 'integer' ),
					),
					'required'             => array( 'rules', 'total' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_list_ip_rules',
			),
			array(
				'name'          => 'botblocker/get-blocked-ips',
				'setting_key'   => 'bbcs_ability_get_blocked_ips',
				'label'         => __( 'Get blocked IP addresses', 'botblocker-security' ),
				'description'   => __( 'Returns the IP addresses currently blocked by firewall rules.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'family' => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ) ),
						'limit'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20 ),
						'offset' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'      => array( 'type' => 'integer' ),
									'search'  => array( 'type' => 'string' ),
									'family'  => array( 'type' => 'string' ),
									'expires' => array( 'type' => 'integer' ),
									'comment' => array( 'type' => 'string' ),
								),
								'required'             => array( 'id', 'search', 'family', 'expires', 'comment' ),
								'additionalProperties' => false,
							),
						),
						'total' => array( 'type' => 'integer' ),
					),
					'required'             => array( 'rules', 'total' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_blocked_ips',
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
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'hits'       => array( 'type' => 'integer' ),
						'blocks'     => array( 'type' => 'integer' ),
						'captcha'    => array( 'type' => 'integer' ),
						'suspicious' => array( 'type' => 'integer' ),
						'proxy'      => array( 'type' => 'integer' ),
					),
					'required'             => array( 'hits', 'blocks', 'captcha', 'suspicious', 'proxy' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_traffic_summary',
			),
			array(
				'name'          => 'botblocker/get-recent-events',
				'setting_key'   => 'bbcs_ability_get_recent_events',
				'label'         => __( 'Get recent events', 'botblocker-security' ),
				'description'   => __( 'Returns aggregated firewall events per time bucket: blocks, allows, CAPTCHA and proxy hits. No individual IP addresses or user agents are exposed.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'period' => array( 'type' => 'string', 'enum' => array( 'day', 'week', 'month' ), 'default' => 'day' ),
						'type'   => array( 'type' => 'string', 'enum' => array( 'block', 'allow', 'captcha', 'proxy' ) ),
						'limit'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 500, 'default' => 100 ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'events' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'bucket'         => array( 'type' => 'integer' ),
									'bucket_seconds' => array( 'type' => 'integer' ),
									'type'           => array( 'type' => 'string' ),
									'count'          => array( 'type' => 'integer' ),
								),
								'required'             => array( 'bucket', 'bucket_seconds', 'type', 'count' ),
								'additionalProperties' => false,
							),
						),
					),
					'required'             => array( 'events' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_recent_events',
			),
			array(
				'name'          => 'botblocker/get-settings',
				'setting_key'   => 'bbcs_ability_get_settings',
				'label'         => __( 'Get settings', 'botblocker-security' ),
				'description'   => __( 'Returns non-secret BotBlocker settings for a group. Secrets are reported only as configured true/false and never expose their value.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'group' => array( 'type' => 'string', 'enum' => array( 'protection', 'captcha', 'logging', 'integrations' ), 'default' => 'protection' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'group'   => array( 'type' => 'string' ),
						'values'  => array( 'type' => 'object' ),
						'secrets' => array( 'type' => 'object' ),
					),
					'required'             => array( 'group', 'values', 'secrets' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_settings',
			),
			array(
				'name'          => 'botblocker/get-captcha-state',
				'setting_key'   => 'bbcs_ability_get_captcha_state',
				'label'         => __( 'Get CAPTCHA state', 'botblocker-security' ),
				'description'   => __( 'Returns the active CAPTCHA mode, wait time, image packing and the availability of every mode on this site.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'            => array( 'type' => 'integer' ),
						'mode_label'      => array( 'type' => 'string' ),
						'wait_seconds'    => array( 'type' => 'integer' ),
						'image_pack'      => array( 'type' => 'boolean' ),
						'inline_images'   => array( 'type' => 'boolean' ),
						'available_modes' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'mode'      => array( 'type' => 'integer' ),
									'label'     => array( 'type' => 'string' ),
									'available' => array( 'type' => 'boolean' ),
									'reason'    => array( 'type' => 'string' ),
								),
								'required'             => array( 'mode', 'label', 'available', 'reason' ),
								'additionalProperties' => false,
							),
						),
						'warnings'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
					'required'             => array( 'mode', 'mode_label', 'wait_seconds', 'image_pack', 'inline_images', 'available_modes', 'warnings' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_captcha_state',
			),
			array(
				'name'          => 'botblocker/run-diagnostics',
				'setting_key'   => 'bbcs_ability_run_diagnostics',
				'label'         => __( 'Run diagnostics', 'botblocker-security' ),
				'description'   => __( 'Runs a set of read-only health checks against the BotBlocker data directory and database tables.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'passed' => array( 'type' => 'boolean' ),
						'items'  => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'key'    => array( 'type' => 'string' ),
									'label'  => array( 'type' => 'string' ),
									'passed' => array( 'type' => 'boolean' ),
								),
								'required'             => array( 'key', 'label', 'passed' ),
								'additionalProperties' => false,
							),
						),
					),
					'required'             => array( 'passed', 'items' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_run_diagnostics',
			),
			array(
				'name'          => 'botblocker/run-self-test',
				'setting_key'   => 'bbcs_ability_run_self_test',
				'label'         => __( 'Run self-test summary', 'botblocker-security' ),
				'description'   => __( 'Returns the BotBlocker self-test summary, bypass reasons and the active flag without writing to the plugin log.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'active'   => array( 'type' => 'boolean' ),
						'verdict'  => array( 'type' => 'string' ),
						'settings' => array( 'type' => 'object' ),
						'bypasses' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
					'required'             => array( 'active', 'verdict', 'settings', 'bypasses' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_run_self_test',
			),
			array(
				'name'          => 'botblocker/block-ip',
				'setting_key'   => 'bbcs_ability_block_ip',
				'label'         => __( 'Block an IP address', 'botblocker-security' ),
				'description'   => __( 'Adds a firewall block rule for an IP address and regenerates the runtime ban files. Requires confirm "BLOCK". Refuses readonly rules and self-blocking without force.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'      => array( 'type' => 'string' ),
						'reason'  => array( 'type' => 'string' ),
						'expires' => array( 'type' => 'integer' ),
						'confirm' => array( 'type' => 'string' ),
						'force'   => array( 'type' => 'boolean', 'default' => false ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'added'              => array( 'type' => 'boolean' ),
						'id'                 => array( 'type' => 'integer' ),
						'changed'            => array( 'type' => 'boolean' ),
						'previous_rule'      => array( 'type' => 'string' ),
						'applied'            => array( 'type' => 'boolean' ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'added', 'id', 'changed', 'previous_rule', 'applied', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_block_ip',
			),
			array(
				'name'          => 'botblocker/unblock-ip',
				'setting_key'   => 'bbcs_ability_unblock_ip',
				'label'         => __( 'Unblock an IP address', 'botblocker-security' ),
				'description'   => __( 'Removes non-readonly firewall rules for an IP address and regenerates the runtime ban files. Requires confirm "UNBLOCK".', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'      => array( 'type' => 'string' ),
						'confirm' => array( 'type' => 'string' ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'removed'            => array( 'type' => 'boolean' ),
						'deleted'            => array( 'type' => 'integer' ),
						'previous_rules'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'applied'            => array( 'type' => 'boolean' ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'removed', 'deleted', 'previous_rules', 'applied', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_unblock_ip',
			),
			array(
				'name'          => 'botblocker/upsert-ip-rule',
				'setting_key'   => 'bbcs_ability_upsert_ip_rule',
				'label'         => __( 'Create or update an IP rule', 'botblocker-security' ),
				'description'   => __( 'Creates or updates a firewall rule for an IP address or CIDR range. Refuses readonly rows and refuses to downgrade an allow rule or block trusted addresses without force.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'         => array( 'type' => 'string' ),
						'rule'       => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark' ) ),
						'expires'    => array( 'type' => 'integer' ),						'expires_in' => array( 'type' => 'integer', 'minimum' => 60, 'maximum' => 31536000 ),
						'comment'    => array( 'type' => 'string' ),
						'priority'   => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 100000 ),
						'force'      => array( 'type' => 'boolean', 'default' => false ),
						'dry_run'    => array( 'type' => 'boolean', 'default' => false ),
						'confirm'    => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'                 => array( 'type' => 'integer' ),
						'created'            => array( 'type' => 'boolean' ),
						'previous_rule'      => array( 'type' => 'string' ),
						'changed'            => array( 'type' => 'boolean' ),
						'applied'            => array( 'type' => 'boolean' ),
						'family'             => array( 'type' => 'string' ),
						'expires'            => array( 'type' => 'integer' ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'id', 'created', 'previous_rule', 'changed', 'applied', 'family', 'expires', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_upsert_ip_rule',
			),
			array(
				'name'          => 'botblocker/delete-ip-rule',
				'setting_key'   => 'bbcs_ability_delete_ip_rule',
				'label'         => __( 'Delete an IP rule by id', 'botblocker-security' ),
				'description'   => __( 'Deletes a single firewall rule by its id. Requires confirm "DELETE". Refuses readonly rows.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer', 'minimum' => 1 ),
						'confirm' => array( 'type' => 'string' ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'deleted'            => array( 'type' => 'boolean' ),
						'id'                 => array( 'type' => 'integer' ),
						'rule'               => array( 'type' => 'string' ),
						'family'             => array( 'type' => 'string' ),
						'applied'            => array( 'type' => 'boolean' ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'deleted', 'id', 'rule', 'family', 'applied', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_delete_ip_rule',
			),
			array(
				'name'          => 'botblocker/set-protection',
				'setting_key'   => 'bbcs_ability_set_protection',
				'label'         => __( 'Enable or disable protection', 'botblocker-security' ),
				'description'   => __( 'Turns BotBlocker protection on or off through the same path as the Dashboard toggle, including the runtime settings file. Requires confirm "ENABLE" or "DISABLE".', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'enabled' => array( 'type' => 'boolean' ),
						'reason'  => array( 'type' => 'string' ),
						'confirm' => array( 'type' => 'string' ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'enabled'            => array( 'type' => 'boolean' ),
						'previous'           => array( 'type' => 'boolean' ),
						'changed'            => array( 'type' => 'boolean' ),
						'applied'            => array( 'type' => 'boolean' ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'enabled', 'previous', 'changed', 'applied', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_set_protection',
			),
			array(
				'name'          => 'botblocker/set-secure-mode',
				'setting_key'   => 'bbcs_ability_set_secure_mode',
				'label'         => __( 'Set the secure mode', 'botblocker-security' ),
				'description'   => __( 'Switches the secure mode between frontend and full. Full mode also challenges the WordPress admin area. Requires confirm "FRONTEND" or "FULL".', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'mode'    => array( 'type' => 'string', 'enum' => array( 'frontend', 'full' ) ),
						'confirm' => array( 'type' => 'string' ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'               => array( 'type' => 'string' ),
						'previous'           => array( 'type' => 'string' ),
						'changed'            => array( 'type' => 'boolean' ),
						'applied'            => array( 'type' => 'boolean' ),
						'warnings'           => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'mode', 'previous', 'changed', 'applied', 'warnings', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_set_secure_mode',
			),
			array(
				'name'          => 'botblocker/set-captcha-mode',
				'setting_key'   => 'bbcs_ability_set_captcha_mode',
				'label'         => __( 'Set the CAPTCHA mode', 'botblocker-security' ),
				'description'   => __( 'Switches the CAPTCHA mode and optional wait time. A mode that is unavailable on this site is rejected instead of silently falling back.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'mode'         => array( 'type' => array( 'integer', 'string' ) ),
						'wait_seconds' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 300 ),
						'dry_run'      => array( 'type' => 'boolean', 'default' => false ),
						'confirm'      => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'               => array( 'type' => 'integer' ),
						'mode_label'         => array( 'type' => 'string' ),
						'previous'           => array( 'type' => 'integer' ),
						'changed'            => array( 'type' => 'boolean' ),
						'applied'            => array( 'type' => 'boolean' ),
						'warnings'           => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'protection_enabled' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'mode', 'mode_label', 'previous', 'changed', 'applied', 'warnings', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_set_captcha_mode',
			),
			array(
				'name'          => 'botblocker/clear-cache',
				'setting_key'   => 'bbcs_ability_clear_cache',
				'label'         => __( 'Clear the plugin file cache', 'botblocker-security' ),
				'description'   => __( 'Clears the BotBlocker file cache after manual rule or data changes.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'default'    => array(),
					'properties' => array(
						'confirm' => array( 'type' => 'string' ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'cleared' => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'cleared' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_clear_cache',
			),
		);

		$extended = function_exists( 'bbcs_get_wp_ability_extended_definitions' ) ? bbcs_get_wp_ability_extended_definitions() : array();
		return array_merge( $definitions, is_array( $extended ) ? $extended : array() );
	}

	function bbcs_wp_ability_allowed( string $key ): bool {
		$BBCS = BotBlocker::getInstance();
		if ( ! is_object( $BBCS->settings ) ) {
			return false;
		}
		return isset( $BBCS->settings->$key ) && (int) $BBCS->settings->$key === 1;
	}

	function bbcs_ability_risk( string $ability_name ): string {
		if ( class_exists( 'BotBlockerAbilityRegistry' ) ) {
			$definition = BotBlockerAbilityRegistry::get( $ability_name );
			if ( ! empty( $definition['risk'] ) && is_string( $definition['risk'] ) ) {
				return $definition['risk'];
			}
		}
		return 'manage';
	}

	function bbcs_ability_required_capability( string $ability_name, string $risk = '' ): string {
		$base = 'manage_options';
		if ( class_exists( 'BotBlockerAbilityRegistry' ) ) {
			$definition = BotBlockerAbilityRegistry::get( $ability_name );
			if ( ! empty( $definition['capability'] ) && is_string( $definition['capability'] ) ) {
				$base = $definition['capability'];
			}
		}
		$capability = apply_filters( 'bbcs_ability_required_capability', $base, $ability_name );
		if ( ! is_string( $capability ) || $capability === '' ) {
			$capability = 'manage_options';
		}
		if ( $risk === '' ) {
			$risk = bbcs_ability_risk( $ability_name );
		}
		$capability = apply_filters( 'bbcs_ability_capability_by_risk', $capability, $risk, $ability_name );
		return is_string( $capability ) && $capability !== '' ? $capability : 'manage_options';
	}

	function bbcs_ability_public_flag( array $definition ): bool {
		if ( ! empty( $definition['pii'] ) ) {
			return false;
		}
		if ( array_key_exists( 'public', $definition ) ) {
			return (bool) $definition['public'];
		}
		return empty( $definition['annotations']['destructive'] );
	}

	function bbcs_wp_ability_user_allowed( string $ability_name, string $risk = '' ): bool {
		if ( ! current_user_can( bbcs_ability_required_capability( $ability_name, $risk ) ) ) {
			return false;
		}
		if ( is_multisite() && BotBlockerMultisite::isNetworkActive() ) {
			return current_user_can( BotBlockerMultisite::canManage() );
		}
		return true;
	}

	function bbcs_wp_ability_audit( string $ability_name, string $outcome, array $data = array() ): void {
		if ( ! class_exists( 'BotBlockerAuditLogger' ) || ! class_exists( 'BotBlockerAuditEvents' ) ) {
			return;
		}
		$payload = array(
			'severity'   => BotBlockerAuditLogger::SEVERITY_MEDIUM,
			'object_type'=> 'ability',
			'data'       => array_merge(
				array(
					'ability' => $ability_name,
					'outcome' => $outcome,
				),
				$data
			),
			'dedup'      => $ability_name . '|' . $outcome . '|' . microtime( true ),
		);
		BotBlockerAuditLogger::record( BotBlockerAuditEvents::BOTBLOCKER_ABILITY_CALL, $payload );
	}

	function bbcs_ability_permission_admin( $ability_name = '', string $risk = '' ) {
		$name = is_string( $ability_name ) ? $ability_name : '';

		if ( bbcs_wp_ability_user_allowed( $name, $risk ) ) {
			return true;
		}

		if ( $name !== '' ) {
			bbcs_wp_ability_audit( $name, 'denied', array( 'reason' => 'capability' ) );
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You are not allowed to execute BotBlocker abilities.', 'botblocker-security' )
		);
	}

	function bbcs_wp_ability_guard( string $ability_name, $input, string $confirm = '', bool $rate_limited = false ) {
		if ( ! bbcs_wp_ability_user_allowed( $ability_name ) ) {
			bbcs_wp_ability_audit( $ability_name, 'denied', array( 'reason' => 'capability' ) );
			return new WP_Error( 'rest_forbidden', __( 'You are not allowed to execute BotBlocker abilities.', 'botblocker-security' ) );
		}

		if ( $confirm !== '' ) {
			$confirmed = bbcs_wp_ability_confirm( $input, $confirm );
			if ( is_wp_error( $confirmed ) ) {
				bbcs_wp_ability_audit( $ability_name, 'denied', array( 'reason' => 'confirmation' ) );
				return $confirmed;
			}
		}

		if ( $rate_limited && bbcs_wp_ability_rate_limited( $ability_name ) ) {
			bbcs_wp_ability_audit( $ability_name, 'denied', array( 'reason' => 'rate_limit' ) );
			return new WP_Error( 'ability_rate_limited', __( 'Too many destructive ability calls. Try again later.', 'botblocker-security' ) );
		}

		return true;
	}

	function bbcs_wp_ability_filter_visibility( $include, $ability, $args = array() ) {
		if ( ! $include || ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
			return $include;
		}
		$name = (string) $ability->get_name();
		if ( strpos( $name, 'botblocker/' ) !== 0 ) {
			return $include;
		}
		if ( bbcs_wp_ability_user_allowed( $name ) ) {
			return $include;
		}
		return false;
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

		$registry    = class_exists( 'BotBlockerAbilityRegistry' ) ? BotBlockerAbilityRegistry::all() : array();
		$definitions = $registry;
		if ( empty( $definitions ) ) {
			foreach ( bbcs_get_wp_ability_definitions() as $definition ) {
				$definitions[ $definition['name'] ] = $definition;
			}
		}

		foreach ( $definitions as $definition ) {
			if ( ! is_array( $definition ) || empty( $definition['name'] ) ) {
				continue;
			}
			if ( ! bbcs_wp_ability_definition_enabled( $definition ) ) {
				continue;
			}

			$bbcs_executor = $definition['execute'];
			$bbcs_execute  = static function ( $input = null ) use ( $bbcs_executor, $definition ) {
				$bbcs_start     = microtime( true );
				$bbcs_exception = '';
				try {
					$bbcs_result = call_user_func( $bbcs_executor, $input );
				} catch ( Throwable $bbcs_error ) {
					$bbcs_exception = get_class( $bbcs_error );
					$bbcs_result    = new WP_Error( 'ability_execution_failed', __( 'The ability could not be completed.', 'botblocker-security' ) );
				}
				$bbcs_audit = array(
					'input'       => is_array( $input ) ? $input : array(),
					'error'       => is_wp_error( $bbcs_result ) ? $bbcs_result->get_error_code() : '',
					'duration_ms' => (int) round( ( microtime( true ) - $bbcs_start ) * 1000 ),
				);
				if ( '' !== $bbcs_exception ) {
					$bbcs_audit['exception'] = $bbcs_exception;
				}
				bbcs_wp_ability_audit( $definition['name'], is_wp_error( $bbcs_result ) ? 'error' : 'success', $bbcs_audit );
				return $bbcs_result;
			};

			$bbcs_is_public = bbcs_ability_public_flag( $definition );
			$bbcs_risk      = isset( $definition['risk'] ) ? (string) $definition['risk'] : 'manage';
			$bbcs_permission = static function ( $permission_input = null ) use ( $definition, $bbcs_risk ) {
				unset( $permission_input );
				if ( empty( $definition['permission_callback'] ) || ! is_callable( $definition['permission_callback'] ) ) {
					return bbcs_ability_permission_admin( $definition['name'], $bbcs_risk );
				}
				$result = bbcs_ability_permission_admin( $definition['name'], $bbcs_risk );
				if ( $result !== true ) {
					return $result;
				}
				$custom = call_user_func( $definition['permission_callback'], $definition['name'] );
				if ( $custom === true ) {
					return true;
				}
				bbcs_wp_ability_audit( $definition['name'], 'denied', array( 'reason' => 'custom_permission' ) );
				if ( is_wp_error( $custom ) ) {
					return $custom;
				}
				return new WP_Error( 'rest_forbidden', __( 'You are not allowed to execute BotBlocker abilities.', 'botblocker-security' ) );
			};

			wp_register_ability(
				$definition['name'],
				array(
					'label'               => $definition['label'],
					'description'         => $definition['description'],
					'category'            => 'botblocker',
					'execute_callback'    => $bbcs_execute,
					'permission_callback' => $bbcs_permission,
					'input_schema'        => $definition['input_schema'],
					'output_schema'       => $definition['output_schema'],
					'meta'                => array(
						'public'       => $bbcs_is_public,
						'show_in_rest' => $definition['show_in_rest'] ?? true,
						'annotations'  => $definition['annotations'],
						'mcp'          => array( 'public' => $bbcs_is_public ),
					),
				)
			);
		}
	}

	function bbcs_wp_ability_definition_enabled( array $definition ): bool {
		if ( class_exists( 'BotBlockerAbilityRegistry' ) ) {
			return BotBlockerAbilityRegistry::isEnabled( (string) $definition['name'] );
		}
		return bbcs_wp_ability_allowed( (string) $definition['setting_key'] );
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

	function bbcs_wp_ability_input_target( $input ) {
		if ( ! is_array( $input ) || ! isset( $input['ip'] ) || ! is_scalar( $input['ip'] ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address or CIDR range is required.', 'botblocker-security' ) );
		}
		$target = sanitize_text_field( (string) $input['ip'] );
		$type   = BotBlockerIp::detectType( $target );
		if ( BBCS_IP_TYPE_INVALID === $type ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address or CIDR range is required.', 'botblocker-security' ) );
		}
		$base = ( strpos( $target, '/' ) !== false ) ? explode( '/', $target )[0] : $target;
		$version = BotBlockerIp::getVersion( $base );
		if ( 4 !== $version && 6 !== $version ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid IP address or CIDR range is required.', 'botblocker-security' ) );
		}
		return array(
			'search'  => $target,
			'type'    => $type,
			'version' => $version,
			'family'  => 6 === $version ? 'ipv6' : 'ipv4',
			'ip1'     => bbcs_wp_ability_encode_value( 6 === $version ? ( ( strpos( $target, '/' ) !== false ) ? BotBlockerIp::toRange( $target )[0] : BotBlockerIp::expandIPv6( $base ) ) : $base ),
			'ip2'     => bbcs_wp_ability_encode_value( 6 === $version ? ( ( strpos( $target, '/' ) !== false ) ? BotBlockerIp::toRange( $target )[1] : BotBlockerIp::expandIPv6( $base ) ) : $base ),
		);
	}

	function bbcs_wp_ability_encode_value( string $ip ) {
		$version = BotBlockerIp::getVersion( $ip );
		if ( 6 === $version ) {
			return BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );
		}
		return (string) BotBlockerIp::toNumeric( $ip );
	}

	function bbcs_wp_ability_input_bool( $input, string $key, bool $default = false ): bool {
		if ( ! is_array( $input ) || ! array_key_exists( $key, $input ) ) {
			return $default;
		}
		return (bool) filter_var( $input[ $key ], FILTER_VALIDATE_BOOLEAN );
	}

	function bbcs_wp_ability_input_int( $input, string $key, int $default = 0 ): int {
		if ( ! is_array( $input ) || ! isset( $input[ $key ] ) || ! is_numeric( $input[ $key ] ) ) {
			return $default;
		}
		return (int) $input[ $key ];
	}

	function bbcs_wp_ability_confirm( $input, string $expected ) {
		$value = ( is_array( $input ) && isset( $input['confirm'] ) && is_scalar( $input['confirm'] ) )
			? strtoupper( trim( sanitize_text_field( (string) $input['confirm'] ) ) )
			: '';
		if ( $expected !== '' && $value === $expected ) {
			return true;
		}
		return new WP_Error(
			'ability_confirmation_required',
			__( 'This ability requires a matching confirmation value.', 'botblocker-security' )
		);
	}

	function bbcs_wp_ability_is_dry_run( $input ): bool {
		return bbcs_wp_ability_input_bool( $input, 'dry_run', false );
	}

	function bbcs_ability_strict_confirm( $input, string $token ) {
		if ( ! apply_filters( 'bbcs_ability_strict_confirm', false, $token ) ) {
			return true;
		}
		return bbcs_wp_ability_confirm( $input, $token );
	}

	function bbcs_wp_ability_rate_limited( string $ability_name ): bool {
		$BBCS   = BotBlocker::getInstance();
		$limit  = ( is_object( $BBCS->settings ) && isset( $BBCS->settings->bbcs_rate_block_rpm ) ) ? (int) $BBCS->settings->bbcs_rate_block_rpm : 50;
		$window = ( is_object( $BBCS->settings ) && isset( $BBCS->settings->bbcs_rate_window_minutes ) ) ? (int) $BBCS->settings->bbcs_rate_window_minutes : 5;
		if ( $limit < 1 ) {
			$limit = 50;
		}
		if ( $window < 1 ) {
			$window = 5;
		}
		$window *= MINUTE_IN_SECONDS;

		$key  = 'bbcs_ability_rl_' . get_current_user_id() . '_' . md5( $ability_name . '|' . BotBlockerIp::getCurrentIp() );
		$data = get_transient( $key );

		if ( ! is_array( $data ) || ( time() - (int) $data['since'] ) >= $window ) {
			set_transient( $key, array( 'count' => 1, 'since' => time() ), $window );
			return false;
		}

		if ( (int) $data['count'] >= $limit ) {
			return true;
		}

		set_transient(
			$key,
			array(
				'count' => (int) $data['count'] + 1,
				'since' => (int) $data['since'],
			),
			$window
		);
		return false;
	}

	function bbcs_wp_ability_read_setting( string $key, $default = null ) {
		$BBCS = BotBlocker::getInstance();
		if ( ! is_object( $BBCS->settings ) || ! isset( $BBCS->settings->$key ) ) {
			return $default;
		}
		return $BBCS->settings->$key;
	}

	function bbcs_wp_ability_write_setting( string $key, $value ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => $key,
				'value' => (string) $value,
			),
			array( '%s', '%s' )
		);
		if ( false === $result ) {
			return false;
		}

		$BBCS = BotBlocker::getInstance();
		if ( is_object( $BBCS->settings ) ) {
			$BBCS->settings->$key = (int) $value;
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::clearFileCache();
		return true;
	}

	function bbcs_wp_ability_captcha_modes(): array {
		$BBCS   = BotBlocker::getInstance();
		$gd     = isset( $BBCS->prefly['gd'] ) && (int) $BBCS->prefly['gd'] === 1;
		$keys   = ! empty( $BBCS->settings->recaptcha_key2 ) && ! empty( $BBCS->settings->recaptcha_secret2 );

		$modes = array(
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_BUTTON, 'label' => __( 'Button', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS, 'label' => __( 'Color Buttons', 'botblocker-security' ), 'available' => $gd, 'reason' => $gd ? '' : 'gd_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_IMAGE, 'label' => __( 'Image', 'botblocker-security' ), 'available' => $gd, 'reason' => $gd ? '' : 'gd_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON, 'label' => __( 'reCAPTCHA v2 Button', 'botblocker-security' ), 'available' => $keys, 'reason' => $keys ? '' : 'keys_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2, 'label' => __( 'reCAPTCHA v2', 'botblocker-security' ), 'available' => $keys, 'reason' => $keys ? '' : 'keys_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_SHAPE, 'label' => __( 'Shape', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_DIGIT, 'label' => __( 'Digit', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_HOLD, 'label' => __( 'Hold Button', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_SILENT, 'label' => __( 'Silent', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
		);

		if ( class_exists( 'BotBlockerCaptchaRegistry' ) ) {
			foreach ( BotBlockerCaptchaRegistry::allModes() as $id => $mode ) {
				$modes[] = array(
					'mode'      => (int) $id,
					'label'     => (string) $mode['name'],
					'available' => BotBlockerCaptchaRegistry::hasKeys( (int) $id ),
					'reason'    => BotBlockerCaptchaRegistry::hasKeys( (int) $id ) ? '' : 'keys_required',
				);
			}
		}

		return $modes;
	}

	function bbcs_wp_ability_mode_label( int $mode ): string {
		foreach ( bbcs_wp_ability_captcha_modes() as $item ) {
			if ( (int) $item['mode'] === $mode ) {
				return (string) $item['label'];
			}
		}
		return __( 'Unknown', 'botblocker-security' );
	}

	function bbcs_wp_ability_resolve_captcha_mode( $mode ) {
		$aliases = array(
			'button'              => BOTBLOCKER_CAPTCHA_MODE_BUTTON,
			'color_buttons'       => BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS,
			'color-buttons'       => BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS,
			'image'               => BOTBLOCKER_CAPTCHA_MODE_IMAGE,
			'recaptcha_v2_button' => BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON,
			'recaptcha_v2'        => BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2,
			'shape'               => BOTBLOCKER_CAPTCHA_MODE_SHAPE,
			'digit'               => BOTBLOCKER_CAPTCHA_MODE_DIGIT,
			'hold'                => BOTBLOCKER_CAPTCHA_MODE_HOLD,
			'silent'              => BOTBLOCKER_CAPTCHA_MODE_SILENT,
		);

		$resolved = -1;
		if ( is_int( $mode ) || ( is_string( $mode ) && ctype_digit( $mode ) ) ) {
			$resolved = (int) $mode;
		} elseif ( is_string( $mode ) ) {
			$key = strtolower( trim( $mode ) );
			if ( isset( $aliases[ $key ] ) ) {
				$resolved = (int) $aliases[ $key ];
			} elseif ( class_exists( 'BotBlockerCaptchaRegistry' ) ) {
				foreach ( BotBlockerCaptchaRegistry::allModes() as $id => $item ) {
					if ( strtolower( (string) $item['slug'] ) === $key ) {
						$resolved = (int) $id;
						break;
					}
				}
			}
		}

		if ( $resolved < 0 ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported CAPTCHA mode.', 'botblocker-security' ) );
		}

		foreach ( bbcs_wp_ability_captcha_modes() as $item ) {
			if ( (int) $item['mode'] === $resolved ) {
				if ( empty( $item['available'] ) ) {
					return new WP_Error(
						'ability_mode_unavailable',
						__( 'The selected CAPTCHA mode is not available on this site.', 'botblocker-security' )
					);
				}
				return $resolved;
			}
		}

		return new WP_Error( 'ability_invalid_input', __( 'Unsupported CAPTCHA mode.', 'botblocker-security' ) );
	}

	function bbcs_wp_ability_lookup_ip_rules( string $ip ): array {
		global $wpdb;

		$version = BotBlockerIp::getVersion( $ip );
		if ( 4 === $version ) {
			$table  = $wpdb->bbcs_ipv4rules;
			$value  = (string) BotBlockerIp::toNumeric( $ip );
			$format = '%d';
			$family = 'ipv4';
		} elseif ( 6 === $version ) {
			$table  = $wpdb->bbcs_ipv6rules;
			$binary = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );
			if ( false === $binary ) {
				return array();
			}
			$value  = $binary;
			$format = '%s';
			$family = 'ipv6';
		} else {
			return array();
		}

		if ( empty( $table ) ) {
			return array();
		}

		$sql = "SELECT `id`, `search`, `rule`, `priority`, `comment`, `expires`, `readonly` FROM `{$table}`
			WHERE `disable` = 0 AND ( `search` = %s OR ( `ip1` <= {$format} AND `ip2` >= {$format} ) )
			ORDER BY `priority` ASC, `id` ASC";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $ip, $value, $value ), ARRAY_A );

		$now = time();
		$out = array();
		foreach ( (array) $rows as $row ) {
			$expires = (int) $row['expires'];
			$out[]   = array(
				'id'       => (int) $row['id'],
				'type'     => (string) $row['rule'],
				'value'    => (string) $row['search'],
				'family'   => $family,
				'priority' => (int) $row['priority'],
				'expires'  => $expires,
				'expired'  => $expires > 0 && $expires <= $now,
				'readonly' => (int) $row['readonly'] === 1,
				'comment'  => (string) $row['comment'],
			);
		}
		return $out;
	}

	function bbcs_wp_ability_deciding_rule( string $ip ): array {
		global $wpdb;

		$version = BotBlockerIp::getVersion( $ip );
		if ( 4 === $version ) {
			$table  = $wpdb->bbcs_ipv4rules;
			$value  = (string) BotBlockerIp::toNumeric( $ip );
			$format = '%d';
		} elseif ( 6 === $version ) {
			$binary = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );
			if ( false === $binary ) {
				return array();
			}
			$table  = $wpdb->bbcs_ipv6rules;
			$value  = $binary;
			$format = '%s';
		} else {
			return array();
		}

		if ( empty( $table ) ) {
			return array();
		}

		$sql = "SELECT `id`, `search`, `rule`, `priority`, `comment`, `expires`, `readonly` FROM `{$table}`
			WHERE `disable` = 0 AND ( `ip1` <= {$format} AND `ip2` >= {$format} )
			AND NOT ( `readonly` = 1 AND `comment` IN ( %s, %s, %s, %s ) )
			ORDER BY `priority` ASC, `id` ASC LIMIT 1";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $value, $value, 'Local IP', 'Local IP from SERVER_ADDR', 'Server IPv4', 'Server IPv6' ), ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	function bbcs_wp_ability_active_hot_ban( string $ip ): array {
		if ( ! class_exists( 'BotBlockerFileRenderer' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return array();
		}
		$file = BotBlockerMultisite::getDataDir() . 'hot-bans.php';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$data = BotBlockerDataFile::safeLoad( $file );
		if ( ! is_array( $data ) ) {
			return array();
		}
		$version = BotBlockerIp::getVersion( $ip );
		$family  = 'ipv' . $version;
		$entries = isset( $data[ $family ] ) && is_array( $data[ $family ] ) ? $data[ $family ] : array();
		if ( ! isset( $entries[ $ip ] ) || ! is_array( $entries[ $ip ] ) ) {
			return array();
		}
		$entry = $entries[ $ip ];
		if ( empty( $entry[1] ) || (int) $entry[1] <= time() ) {
			return array();
		}
		$rule = isset( $entry[0] ) ? (string) $entry[0] : '';
		if ( $rule !== BBCS_RULE_BLOCK && $rule !== BBCS_RULE_DARK ) {
			return array();
		}
		return array(
			'rule'    => $rule,
			'expires' => (int) $entry[1],
		);
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

	function bbcs_wp_ability_rule_table( int $version ): string {
		global $wpdb;
		if ( 6 === $version ) {
			return isset( $wpdb->bbcs_ipv6rules ) ? (string) $wpdb->bbcs_ipv6rules : '';
		}
		return isset( $wpdb->bbcs_ipv4rules ) ? (string) $wpdb->bbcs_ipv4rules : '';
	}

	function bbcs_wp_ability_find_rule( string $table, string $search ) {
		global $wpdb;
		if ( $table === '' ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb; values prepared.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `search`, `rule`, `priority`, `comment`, `expires`, `readonly` FROM `{$table}` WHERE `search` = %s ORDER BY `priority` ASC, `id` ASC LIMIT 1", $search ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	function bbcs_wp_ability_find_rule_by_id( int $id ): array {
		global $wpdb;
		foreach ( array( 'ipv4' => $wpdb->bbcs_ipv4rules, 'ipv6' => $wpdb->bbcs_ipv6rules ) as $family => $table ) {
			if ( empty( $table ) ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb; values prepared.
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `search`, `rule`, `priority`, `comment`, `expires`, `readonly` FROM `{$table}` WHERE `id` = %d LIMIT 1", $id ), ARRAY_A );
			if ( is_array( $row ) ) {
				$row['family'] = $family;
				$row['table']  = $table;
				return $row;
			}
		}
		return array();
	}

	function bbcs_wp_ability_ip_covers( string $search, string $ip ): bool {
		if ( $search === '' || $ip === '' ) {
			return false;
		}
		if ( strpos( $search, '/' ) !== false ) {
			return BotBlockerIp::matches( $search, $ip );
		}
		return $search === $ip;
	}

	function bbcs_wp_ability_self_block( string $search ): bool {
		$targets = array( BotBlockerIp::getCurrentIp() );
		if ( ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$targets[] = sanitize_text_field( wp_unslash( (string) $_SERVER['SERVER_ADDR'] ) );
		}
		foreach ( $targets as $target ) {
			if ( is_string( $target ) && $target !== '' && bbcs_wp_ability_ip_covers( $search, $target ) ) {
				return true;
			}
		}
		return false;
	}

	function bbcs_wp_ability_resolve_expires( $input, int $default = BOTBLOCKER_EXP_INF ) {
		$expires    = bbcs_wp_ability_input_int( $input, 'expires', 0 );
		$expires_in = bbcs_wp_ability_input_int( $input, 'expires_in', 0 );

		if ( $expires > 0 && $expires_in > 0 ) {
			return new WP_Error( 'ability_invalid_input', __( 'Use either expires or expires_in, not both.', 'botblocker-security' ) );
		}
		if ( $expires_in > 0 ) {
			$expires = time() + $expires_in;
		}
		if ( $expires <= 0 ) {
			return $default;
		}
		if ( $expires !== BOTBLOCKER_EXP_INF && $expires > time() + ( 10 * YEAR_IN_SECONDS ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The expiry timestamp is too far in the future.', 'botblocker-security' ) );
		}
		return $expires;
	}

	function bbcs_wp_ability_sync_rule_files( string $ip, string $rule, int $expires ): void {
		if ( BBCS_RULE_BLOCK === $rule ) {
			BotBlockerFileRenderer::syncIpBanFiles( $ip, $rule, $expires );
			return;
		}
		BotBlockerFileRenderer::removeHotBan( $ip );
		BotBlockerFileRenderer::renderIps();
		BotBlockerCache::clearFileCache();
	}

	function bbcs_wp_ability_rule_row_output( array $row, string $family ): array {
		return array(
			'id'       => (int) $row['id'],
			'search'   => (string) $row['search'],
			'rule'     => (string) $row['rule'],
			'family'   => $family,
			'priority' => (int) $row['priority'],
			'expires'  => (int) $row['expires'],
			'readonly' => (int) $row['readonly'] === 1,
			'comment'  => (string) $row['comment'],
		);
	}

	function bbcs_wp_ability_execute_get_firewall_status( $input = null ) {
		$BBCS     = BotBlocker::getInstance();
		$settings = is_object( $BBCS->settings ) ? $BBCS->settings : null;
		$mode     = $settings && isset( $settings->bbcs_captcha_mode ) ? (int) $settings->bbcs_captcha_mode : 0;
		$secure   = $settings && isset( $settings->secure_mode ) ? (int) $settings->secure_mode : 0;
		$enabled  = $settings && isset( $settings->disable ) ? (int) $settings->disable !== 1 : false;

		return array(
			'secure_mode'        => $secure,
			'secure_mode_label'  => 2 === $secure ? __( 'Full', 'botblocker-security' ) : __( 'Frontend', 'botblocker-security' ),
			'enabled'            => $enabled,
			'protection_enabled' => $enabled,
			'captcha_mode'       => $mode,
			'captcha_mode_label' => bbcs_wp_ability_mode_label( $mode ),
			'version'            => defined( 'BOTBLOCKER_VERSION' ) ? (string) BOTBLOCKER_VERSION : '',
		);
	}

	function bbcs_wp_ability_check_ip_result( string $ip ): array {
		$rules    = bbcs_wp_ability_lookup_ip_rules( $ip );
		$deciding = bbcs_wp_ability_deciding_rule( $ip );
		$hot_ban  = bbcs_wp_ability_active_hot_ban( $ip );
		$enabled  = (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1;

		$blocked = false;
		$reason  = '';
		$source  = 'none';

		if ( ! empty( $hot_ban ) ) {
			$blocked = true;
			$reason  = BBCS_RULE_BLOCK === $hot_ban['rule'] ? 'hot-ban: block' : 'hot-ban: dark';
			$source  = 'hot-ban';
		} elseif ( ! empty( $deciding ) ) {
			$expires = (int) $deciding['expires'];
			if ( $expires > 0 && $expires <= time() ) {
				$blocked = false;
				$reason  = '';
				$source  = 'expired';
			} else {
				$rule    = (string) $deciding['rule'];
				$blocked = BBCS_RULE_ALLOW !== $rule && BBCS_RULE_NOOP !== $rule;
				$reason  = $blocked ? ( (string) $deciding['comment'] !== '' ? (string) $deciding['comment'] : $rule ) : '';
				$source  = 'db';
			}
		}

		return array(
			'ip'            => $ip,
			'blocked'       => $blocked,
			'reason'        => $reason,
			'source'        => $source,
			'matched_rules' => count( $rules ),
		);
	}

	function bbcs_wp_ability_execute_check_ip( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/check-ip', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$result = bbcs_wp_ability_check_ip_result( $ip );
		unset( $result['ip'] );
		$result['rules']              = bbcs_wp_ability_lookup_ip_rules( $ip );
		$result['protection_enabled'] = (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1;

		return $result;
	}

	function bbcs_wp_ability_execute_check_ips( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/check-ips', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! is_array( $input ) || ! isset( $input['ips'] ) || ! is_array( $input['ips'] ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A list of IP addresses is required.', 'botblocker-security' ) );
		}
		if ( count( $input['ips'] ) < 1 || count( $input['ips'] ) > 20 ) {
			return new WP_Error( 'ability_invalid_input', __( 'Between 1 and 20 IP addresses are allowed per call.', 'botblocker-security' ) );
		}

		$results = array();
		foreach ( $input['ips'] as $candidate ) {
			$ip = bbcs_wp_ability_input_ip( array( 'ip' => $candidate ) );
			if ( is_wp_error( $ip ) ) {
				return $ip;
			}
			$results[] = bbcs_wp_ability_check_ip_result( $ip );
		}

		return array(
			'results' => $results,
			'total'   => count( $results ),
		);
	}

	function bbcs_wp_ability_execute_get_ip_rules( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-ip-rules', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}
		return array( 'rules' => bbcs_wp_ability_lookup_ip_rules( $ip ) );
	}

	function bbcs_wp_ability_execute_list_ip_rules( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/list-ip-rules', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$family      = ( is_array( $input ) && isset( $input['family'] ) ) ? (string) $input['family'] : '';
		$rule        = ( is_array( $input ) && isset( $input['rule'] ) ) ? (string) $input['rule'] : '';
		$search      = ( is_array( $input ) && isset( $input['search'] ) ) ? sanitize_text_field( (string) $input['search'] ) : '';
		$active_only = bbcs_wp_ability_input_bool( $input, 'active_only', false );
		$limit       = bbcs_wp_ability_input_int( $input, 'limit', 20 );
		$offset      = bbcs_wp_ability_input_int( $input, 'offset', 0 );
		$limit       = max( 1, min( 100, $limit ) );
		$offset      = max( 0, $offset );

		$tables = array();
		if ( 'ipv6' === $family ) {
			$tables['ipv6'] = $wpdb->bbcs_ipv6rules;
		} elseif ( 'ipv4' === $family ) {
			$tables['ipv4'] = $wpdb->bbcs_ipv4rules;
		} else {
			$tables['ipv4'] = $wpdb->bbcs_ipv4rules;
			$tables['ipv6'] = $wpdb->bbcs_ipv6rules;
		}

		$all = array();
		foreach ( $tables as $fam => $table ) {
			if ( empty( $table ) ) {
				continue;
			}
			$where  = array( '1=1' );
			$params = array();
			if ( $rule !== '' ) {
				$where[]  = '`rule` = %s';
				$params[] = $rule;
			}
			if ( $search !== '' ) {
				$where[]  = '`search` LIKE %s';
				$params[] = '%' . $wpdb->esc_like( $search ) . '%';
			}
			if ( $active_only ) {
				$where[]  = '( `expires` = 0 OR `expires` > %d )';
				$params[] = time();
			}
			$sql = "SELECT `id`, `search`, `rule`, `priority`, `comment`, `expires`, `readonly` FROM `{$table}` WHERE " . implode( ' AND ', $where );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results( $params ? $wpdb->prepare( $sql, $params ) : $sql, ARRAY_A );
			foreach ( (array) $rows as $row ) {
				$row['family'] = $fam;
				$all[]         = $row;
			}
		}

		usort(
			$all,
			static function ( $a, $b ) {
				if ( (int) $a['priority'] === (int) $b['priority'] ) {
					return (int) $a['id'] <=> (int) $b['id'];
				}
				return (int) $a['priority'] <=> (int) $b['priority'];
			}
		);

		$total = count( $all );
		$page  = array_slice( $all, $offset, $limit );
		$rules = array();
		foreach ( $page as $row ) {
			$rules[] = bbcs_wp_ability_rule_row_output( $row, (string) $row['family'] );
		}

		return array(
			'rules' => $rules,
			'total' => $total,
		);
	}

	function bbcs_wp_ability_execute_get_blocked_ips( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-blocked-ips', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$params = is_array( $input ) ? $input : array();
		$params['rule']        = BBCS_RULE_BLOCK;
		$params['active_only'] = true;
		$result                = bbcs_wp_ability_execute_list_ip_rules( $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$rules = array();
		foreach ( $result['rules'] as $row ) {
			$rules[] = array(
				'id'      => (int) $row['id'],
				'search'  => (string) $row['search'],
				'family'  => (string) $row['family'],
				'expires' => (int) $row['expires'],
				'comment' => (string) $row['comment'],
			);
		}

		return array(
			'rules' => $rules,
			'total' => (int) $result['total'],
		);
	}

	function bbcs_wp_ability_execute_get_traffic_summary( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/get-traffic-summary', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

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

	function bbcs_wp_ability_execute_get_recent_events( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/get-recent-events', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$period = ( is_array( $input ) && isset( $input['period'] ) ) ? (string) $input['period'] : 'day';
		if ( ! in_array( $period, array( 'day', 'week', 'month' ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported period.', 'botblocker-security' ) );
		}
		$type  = ( is_array( $input ) && isset( $input['type'] ) ) ? (string) $input['type'] : '';
		$limit = max( 1, min( 500, bbcs_wp_ability_input_int( $input, 'limit', 100 ) ) );

		$hits_table = isset( $wpdb->bbcs_hits ) ? $wpdb->bbcs_hits : '';
		$sus_table  = isset( $wpdb->bbcs_hits_suspicious ) ? $wpdb->bbcs_hits_suspicious : '';
		if ( $hits_table === '' || $sus_table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Traffic tables are not available.', 'botblocker-security' ) );
		}

		$bucket_seconds = 'day' === $period ? HOUR_IN_SECONDS : DAY_IN_SECONDS;
		$start          = time() - ( bbcs_wp_ability_period_days( $period ) * DAY_IN_SECONDS );

		$queries = array(
			'allow'   => array( $hits_table, '`passed` = 1' ),
			'block'   => array( $sus_table, '`passed` = 0' ),
			'captcha' => array( $hits_table . '|' . $sus_table, '`recaptcha` NOT IN (\'\', \'0\')' ),
			'proxy'   => array( $hits_table . '|' . $sus_table, '`proxy` NOT IN (\'\', \'0\')' ),
		);

		$events = array();
		foreach ( $queries as $event_type => $query ) {
			if ( $type !== '' && $type !== $event_type ) {
				continue;
			}
			$tables = explode( '|', $query[0] );
			foreach ( $tables as $table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ( FLOOR( `date` / %d ) * %d ) AS `bucket`, COUNT(*) AS `count` FROM `{$table}` WHERE `date` >= %d AND {$query[1]} GROUP BY `bucket` ORDER BY `bucket` ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tables from $wpdb; predicates fixed above.
						$bucket_seconds,
						$bucket_seconds,
						$start
					),
					ARRAY_A
				);
				foreach ( (array) $rows as $row ) {
					$bucket = (int) $row['bucket'];
					if ( ! isset( $events[ $bucket ][ $event_type ] ) ) {
						$events[ $bucket ][ $event_type ] = 0;
					}
					$events[ $bucket ][ $event_type ] += (int) $row['count'];
				}
			}
		}

		$out = array();
		foreach ( $events as $bucket => $types ) {
			foreach ( $types as $event_type => $count ) {
				$out[] = array(
					'bucket'         => (int) $bucket,
					'bucket_seconds' => (int) $bucket_seconds,
					'type'           => (string) $event_type,
					'count'          => (int) $count,
				);
			}
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return (int) $a['bucket'] <=> (int) $b['bucket'];
			}
		);
		$out = array_slice( $out, 0, $limit );

		return array( 'events' => $out );
	}

	function bbcs_wp_ability_execute_get_settings( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-settings', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$group = ( is_array( $input ) && isset( $input['group'] ) ) ? (string) $input['group'] : 'protection';
		$map   = array(
			'protection'   => array( 'disable', 'secure_mode', 'bbcs_honeypot_enabled', 'block_proxy_users', 'block_tor_users', 'block_vpn_users', 'block_empty_ua', 'block_fake_ref', 'skip_logged_in_users' ),
			'captcha'      => array( 'bbcs_captcha_mode', 'bbcs_captcha_wait', 'bbcs_captcha_img_pack', 'bbcs_captcha_img_inline' ),
			'logging'      => array( 'botblocker_log_error', 'botblocker_log_admin', 'botblocker_log_allow', 'botblocker_log_block', 'audit_log_enable', 'audit_log_retention_days' ),
			'integrations' => array( 'bbcs_wp_abilities_enabled', 'bbcs_wp_connectors_enabled', 'redis_enable', 'memcached_enable', 'apcu_enable', 'transients_enable' ),
		);
		if ( ! isset( $map[ $group ] ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported settings group.', 'botblocker-security' ) );
		}

		$secrets = array(
			'recaptcha_key2',
			'recaptcha_secret2',
			'recaptcha_secret',
			'cloud_api_key',
			'cloud_api_pass',
			'cloud_api_secret',
			'bbcs_antispam_api_key',
		);

		$values = array();
		foreach ( $map[ $group ] as $key ) {
			$values[ $key ] = bbcs_wp_ability_read_setting( $key, null );
		}

		$secret_state = array();
		foreach ( $secrets as $key ) {
			$value                = bbcs_wp_ability_read_setting( $key, '' );
			$secret_state[ $key ] = is_string( $value ) ? $value !== '' : ! empty( $value );
		}

		return array(
			'group'   => $group,
			'values'  => $values,
			'secrets' => $secret_state,
		);
	}

	function bbcs_wp_ability_execute_get_captcha_state( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/get-captcha-state', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$mode     = (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_mode', 0 );
		$modes    = bbcs_wp_ability_captcha_modes();
		$warnings = array();
		foreach ( $modes as $item ) {
			if ( (int) $item['mode'] === $mode && empty( $item['available'] ) ) {
				$warnings[] = __( 'The selected CAPTCHA mode is not available on this site.', 'botblocker-security' );
			}
		}

		return array(
			'mode'            => $mode,
			'mode_label'      => bbcs_wp_ability_mode_label( $mode ),
			'wait_seconds'    => (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_wait', 0 ),
			'image_pack'      => (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_img_pack', 0 ) === 1,
			'inline_images'   => (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_img_inline', 0 ) === 1,
			'available_modes' => $modes,
			'warnings'        => $warnings,
		);
	}

	function bbcs_wp_ability_execute_run_diagnostics( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/run-diagnostics', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

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

	function bbcs_wp_ability_execute_run_self_test( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/run-self-test', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		if ( ! class_exists( 'BotBlockerSelfTest' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The self-test component is not available.', 'botblocker-security' ) );
		}

		return array(
			'active'   => BotBlockerSelfTest::isActive(),
			'verdict'  => BotBlockerSelfTest::verdict( array() ),
			'settings' => BotBlockerSelfTest::settingsSummary(),
			'bypasses' => BotBlockerSelfTest::normalBypasses(),
		);
	}

	function bbcs_wp_ability_execute_block_ip( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/block-ip', $input, 'BLOCK', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$version = BotBlockerIp::getVersion( $ip );
		$table   = bbcs_wp_ability_rule_table( (int) $version );
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}

		$reason = ( is_array( $input ) && isset( $input['reason'] ) && is_scalar( $input['reason'] ) )
			? sanitize_text_field( (string) $input['reason'] )
			: '';
		if ( $reason === '' ) {
			$reason = __( 'Blocked via WP Abilities', 'botblocker-security' );
		}

		$expires = bbcs_wp_ability_resolve_expires( $input, BOTBLOCKER_EXP_INF );
		if ( is_wp_error( $expires ) ) {
			return $expires;
		}

		$force   = bbcs_wp_ability_input_bool( $input, 'force', false );
		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		$value   = bbcs_wp_ability_encode_value( $ip );
		$data    = array(
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

		$existing          = bbcs_wp_ability_find_rule( $table, $ip );
		$previous_rule     = is_array( $existing ) ? (string) $existing['rule'] : '';
		$existing_readonly = is_array( $existing ) && (int) $existing['readonly'] === 1;

		if ( $existing_readonly ) {
			return new WP_Error( 'ability_readonly_rule', __( 'The existing rule is readonly and cannot be changed.', 'botblocker-security' ) );
		}
		if ( BBCS_RULE_ALLOW === $previous_rule && ! $force ) {
			return new WP_Error( 'ability_allow_downgrade_requires_force', __( 'Blocking an allowed IP requires force.', 'botblocker-security' ) );
		}
		if ( ! $force && bbcs_wp_ability_self_block( $ip ) ) {
			return new WP_Error( 'ability_self_block_requires_force', __( 'Blocking the current or server IP requires force.', 'botblocker-security' ) );
		}

		$id      = is_array( $existing ) ? (int) $existing['id'] : 0;
		$changed = $previous_rule !== BBCS_RULE_BLOCK;

		if ( ! $dry_run ) {
			if ( $id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$saved = $wpdb->update( $table, $data, array( 'id' => $id ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$saved = $wpdb->insert( $table, $data );
				$id    = (int) $wpdb->insert_id;
			}
			if ( false === $saved ) {
				return new WP_Error( 'ability_execution_failed', __( 'The IP rule could not be saved.', 'botblocker-security' ) );
			}
			bbcs_wp_ability_sync_rule_files( $ip, BBCS_RULE_BLOCK, (int) $expires );
			bbcs_wp_ability_audit( 'botblocker/block-ip', 'rule_written', array( 'ip' => $ip, 'rule' => BBCS_RULE_BLOCK ) );
		}

		return array(
			'added'              => true,
			'id'                 => $id,
			'changed'            => $changed,
			'previous_rule'      => $previous_rule,
			'applied'            => ! $dry_run,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_unblock_ip( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/unblock-ip', $input, 'UNBLOCK', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$ip = bbcs_wp_ability_input_ip( $input );
		if ( is_wp_error( $ip ) ) {
			return $ip;
		}

		$version = BotBlockerIp::getVersion( $ip );
		$table   = bbcs_wp_ability_rule_table( (int) $version );
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}

		$dry_run = bbcs_wp_ability_is_dry_run( $input );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb; values prepared.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `rule`, `readonly` FROM `{$table}` WHERE `search` = %s", $ip ), ARRAY_A );
		$previous_rules = array();
		$deletable_ids  = array();
		foreach ( (array) $rows as $row ) {
			if ( (int) $row['readonly'] === 1 ) {
				continue;
			}
			$previous_rules[] = (string) $row['rule'];
			$deletable_ids[]  = (int) $row['id'];
		}

		if ( empty( $deletable_ids ) && ! empty( $rows ) ) {
			return new WP_Error( 'ability_readonly_rule', __( 'Only readonly rules match this IP and cannot be removed.', 'botblocker-security' ) );
		}
		if ( $dry_run ) {
			return array(
				'removed'            => ! empty( $deletable_ids ),
				'deleted'            => count( $deletable_ids ),
				'previous_rules'     => $previous_rules,
				'applied'            => false,
				'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
			);
		}

		$deleted = 0;
		foreach ( $deletable_ids as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
			if ( false !== $result && $result > 0 ) {
				$deleted += (int) $result;
			}
		}

		BotBlockerFileRenderer::removeHotBan( $ip );
		BotBlockerFileRenderer::renderIps();
		BotBlockerCache::clearFileCache();
		bbcs_wp_ability_audit( 'botblocker/unblock-ip', 'rule_deleted', array( 'ip' => $ip, 'deleted' => $deleted ) );

		return array(
			'removed'            => $deleted > 0,
			'deleted'            => $deleted,
			'previous_rules'     => $previous_rules,
			'applied'            => true,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_upsert_ip_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/upsert-ip-rule', $input, '', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$strict = bbcs_ability_strict_confirm( $input, 'UPSERT' );
		if ( is_wp_error( $strict ) ) {
			bbcs_wp_ability_audit( 'botblocker/upsert-ip-rule', 'denied', array( 'reason' => 'confirmation' ) );
			return $strict;
		}

		$target = bbcs_wp_ability_input_target( $input );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$rule = ( is_array( $input ) && isset( $input['rule'] ) ) ? (string) $input['rule'] : '';
		if ( ! in_array( $rule, array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_GRAY, BBCS_RULE_DARK ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported rule type.', 'botblocker-security' ) );
		}

		$expires = bbcs_wp_ability_resolve_expires( $input, BOTBLOCKER_EXP_INF );
		if ( is_wp_error( $expires ) ) {
			return $expires;
		}

		$table = bbcs_wp_ability_rule_table( (int) $target['version'] );
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}

		$force     = bbcs_wp_ability_input_bool( $input, 'force', false );
		$dry_run   = bbcs_wp_ability_is_dry_run( $input );
		$priority  = bbcs_wp_ability_input_int( $input, 'priority', 100 );
		$comment   = ( is_array( $input ) && isset( $input['comment'] ) && is_scalar( $input['comment'] ) )
			? sanitize_text_field( (string) $input['comment'] )
			: '';
		$existing  = bbcs_wp_ability_find_rule( $table, (string) $target['search'] );
		$previous  = is_array( $existing ) ? (string) $existing['rule'] : '';
		$readonly  = is_array( $existing ) && (int) $existing['readonly'] === 1;
		$created   = ! is_array( $existing );

		if ( $readonly && $rule !== $previous ) {
			return new WP_Error( 'ability_readonly_rule', __( 'The existing rule is readonly and cannot be changed.', 'botblocker-security' ) );
		}
		if ( BBCS_RULE_ALLOW === $previous && $rule !== BBCS_RULE_ALLOW && ! $force ) {
			return new WP_Error( 'ability_allow_downgrade_requires_force', __( 'Changing an allow rule requires force.', 'botblocker-security' ) );
		}
		if ( $rule !== BBCS_RULE_ALLOW && ! $force && bbcs_wp_ability_self_block( (string) $target['search'] ) ) {
			return new WP_Error( 'ability_self_block_requires_force', __( 'Blocking the current or server IP requires force.', 'botblocker-security' ) );
		}

		$data = array(
			'priority' => max( 0, min( 100000, $priority ) ),
			'search'   => (string) $target['search'],
			'ip1'      => $target['ip1'],
			'ip2'      => $target['ip2'],
			'rule'     => $rule,
			'comment'  => $comment,
			'expires'  => $expires,
			'disable'  => 0,
			'readonly' => is_array( $existing ) ? (int) $existing['readonly'] : 0,
		);

		$id      = is_array( $existing ) ? (int) $existing['id'] : 0;
		$changed = $created || $previous !== $rule || (int) $existing['expires'] !== (int) $expires;

		if ( $dry_run ) {
			return array(
				'id'                 => $id,
				'created'            => $created,
				'previous_rule'      => $previous,
				'changed'            => $changed,
				'applied'            => false,
				'family'             => (string) $target['family'],
				'expires'            => (int) $expires,
				'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
			);
		}

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$saved = $wpdb->update( $table, $data, array( 'id' => $id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$saved = $wpdb->insert( $table, $data );
			$id    = (int) $wpdb->insert_id;
		}

		if ( false === $saved ) {
			return new WP_Error( 'ability_execution_failed', __( 'The IP rule could not be saved.', 'botblocker-security' ) );
		}

		bbcs_wp_ability_sync_rule_files( (string) $target['search'], $rule, (int) $expires );
		bbcs_wp_ability_audit( 'botblocker/upsert-ip-rule', 'rule_written', array( 'ip' => (string) $target['search'], 'rule' => $rule ) );

		return array(
			'id'                 => $id,
			'created'            => $created,
			'previous_rule'      => $previous,
			'changed'            => $changed,
			'applied'            => true,
			'family'             => (string) $target['family'],
			'expires'            => (int) $expires,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_delete_ip_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/delete-ip-rule', $input, 'DELETE', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$id = bbcs_wp_ability_input_int( $input, 'id', 0 );
		if ( $id <= 0 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A rule id is required.', 'botblocker-security' ) );
		}

		$row = bbcs_wp_ability_find_rule_by_id( $id );
		if ( empty( $row ) ) {
			return new WP_Error( 'ability_not_found', __( 'The rule was not found.', 'botblocker-security' ) );
		}
		if ( (int) $row['readonly'] === 1 ) {
			return new WP_Error( 'ability_readonly_rule', __( 'Readonly rules cannot be deleted.', 'botblocker-security' ) );
		}

		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		if ( $dry_run ) {
			return array(
				'deleted'            => false,
				'id'                 => $id,
				'rule'               => (string) $row['rule'],
				'family'             => (string) $row['family'],
				'applied'            => false,
				'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( (string) $row['table'], array( 'id' => $id ), array( '%d' ) );
		if ( false === $deleted || $deleted < 1 ) {
			return new WP_Error( 'ability_execution_failed', __( 'The rule could not be deleted.', 'botblocker-security' ) );
		}

		BotBlockerFileRenderer::removeHotBan( (string) $row['search'] );
		BotBlockerFileRenderer::renderIps();
		BotBlockerCache::clearFileCache();
		bbcs_wp_ability_audit( 'botblocker/delete-ip-rule', 'rule_deleted', array( 'id' => $id, 'rule' => (string) $row['rule'] ) );

		return array(
			'deleted'            => true,
			'id'                 => $id,
			'rule'               => (string) $row['rule'],
			'family'             => (string) $row['family'],
			'applied'            => true,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_set_protection( $input = null ) {
		if ( ! is_array( $input ) || ! array_key_exists( 'enabled', $input ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The enabled flag is required.', 'botblocker-security' ) );
		}

		$enabled = bbcs_wp_ability_input_bool( $input, 'enabled', false );
		$confirm = $enabled ? 'ENABLE' : 'DISABLE';
		$guard   = bbcs_wp_ability_guard( 'botblocker/set-protection', $input, $confirm, true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$previous = (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1;
		$changed  = $previous !== $enabled;
		$dry_run  = bbcs_wp_ability_is_dry_run( $input );
		$value    = $enabled ? 0 : 1;

		if ( $changed && ! $dry_run ) {
			if ( ! bbcs_wp_ability_write_setting( 'disable', $value ) ) {
				return new WP_Error( 'ability_execution_failed', __( 'The protection state could not be saved.', 'botblocker-security' ) );
			}
			BotBlockerEarlyPhaseDedup::dedup( 'disable', $value );
			bbcs_wp_ability_audit(
				'botblocker/set-protection',
				'protection_changed',
				array(
					'from'   => $previous ? 'on' : 'off',
					'to'     => $enabled ? 'on' : 'off',
					'reason' => ( is_array( $input ) && isset( $input['reason'] ) ) ? sanitize_text_field( (string) $input['reason'] ) : '',
				)
			);
		}

		return array(
			'enabled'            => $enabled,
			'previous'           => $previous,
			'changed'            => $changed,
			'applied'            => $changed && ! $dry_run,
			'protection_enabled' => $enabled,
		);
	}

	function bbcs_wp_ability_execute_set_secure_mode( $input = null ) {
		$mode = ( is_array( $input ) && isset( $input['mode'] ) ) ? strtolower( (string) $input['mode'] ) : '';
		if ( ! in_array( $mode, array( 'frontend', 'full' ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported secure mode.', 'botblocker-security' ) );
		}

		$guard = bbcs_wp_ability_guard( 'botblocker/set-secure-mode', $input, strtoupper( $mode ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$previous_mode = (int) bbcs_wp_ability_read_setting( 'secure_mode', 1 );
		$previous      = 2 === $previous_mode ? 'full' : 'frontend';
		$changed       = $previous !== $mode;
		$dry_run       = bbcs_wp_ability_is_dry_run( $input );
		$value         = 'full' === $mode ? 2 : 1;
		$warnings      = 'full' === $mode ? array( __( 'Full secure mode challenges the WordPress admin area as well.', 'botblocker-security' ) ) : array();

		if ( $changed && ! $dry_run ) {
			if ( ! bbcs_wp_ability_write_setting( 'secure_mode', $value ) ) {
				return new WP_Error( 'ability_execution_failed', __( 'The secure mode could not be saved.', 'botblocker-security' ) );
			}
			bbcs_wp_ability_audit( 'botblocker/set-secure-mode', 'mode_changed', array( 'from' => $previous, 'to' => $mode ) );
		}

		return array(
			'mode'               => $mode,
			'previous'           => $previous,
			'changed'            => $changed,
			'applied'            => $changed && ! $dry_run,
			'warnings'           => $warnings,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_set_captcha_mode( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/set-captcha-mode', $input, '', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$strict = bbcs_ability_strict_confirm( $input, 'CAPTCHA' );
		if ( is_wp_error( $strict ) ) {
			bbcs_wp_ability_audit( 'botblocker/set-captcha-mode', 'denied', array( 'reason' => 'confirmation' ) );
			return $strict;
		}

		if ( ! is_array( $input ) || ! array_key_exists( 'mode', $input ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A CAPTCHA mode is required.', 'botblocker-security' ) );
		}

		$mode = bbcs_wp_ability_resolve_captcha_mode( $input['mode'] );
		if ( is_wp_error( $mode ) ) {
			return $mode;
		}

		$previous    = (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_mode', 0 );
		$wait        = bbcs_wp_ability_input_int( $input, 'wait_seconds', (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_wait', 30 ) );
		$wait        = max( 0, min( 300, $wait ) );
		$previous_wait = (int) bbcs_wp_ability_read_setting( 'bbcs_captcha_wait', 30 );
		$changed     = $previous !== (int) $mode || $previous_wait !== $wait;
		$dry_run     = bbcs_wp_ability_is_dry_run( $input );

		if ( $changed && ! $dry_run ) {
			if ( ! bbcs_wp_ability_write_setting( 'bbcs_captcha_mode', (int) $mode ) ) {
				return new WP_Error( 'ability_execution_failed', __( 'The CAPTCHA mode could not be saved.', 'botblocker-security' ) );
			}
			if ( $previous_wait !== $wait ) {
				bbcs_wp_ability_write_setting( 'bbcs_captcha_wait', $wait );
			}
			bbcs_wp_ability_audit( 'botblocker/set-captcha-mode', 'mode_changed', array( 'from' => $previous, 'to' => (int) $mode ) );
		}

		return array(
			'mode'               => (int) $mode,
			'mode_label'         => bbcs_wp_ability_mode_label( (int) $mode ),
			'previous'           => $previous,
			'changed'            => $changed,
			'applied'            => $changed && ! $dry_run,
			'warnings'           => array(),
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_clear_cache( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/clear-cache', $input, '', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$strict = bbcs_ability_strict_confirm( $input, 'CLEAR' );
		if ( is_wp_error( $strict ) ) {
			bbcs_wp_ability_audit( 'botblocker/clear-cache', 'denied', array( 'reason' => 'confirmation' ) );
			return $strict;
		}
		if ( bbcs_wp_ability_is_dry_run( $input ) ) {
			bbcs_wp_ability_audit( 'botblocker/clear-cache', 'cache_dry_run', array( 'cleared' => false ) );
			return array( 'cleared' => false );
		}

		$cleared = BotBlockerCache::clearFileCache();
		bbcs_wp_ability_audit( 'botblocker/clear-cache', 'cache_cleared', array( 'cleared' => (bool) $cleared ) );

		return array( 'cleared' => (bool) $cleared );
	}

	require_once __DIR__ . '/class-botblocker-ability-registry.php';
	require_once __DIR__ . '/inc-botblocker-wp-abilities-extended.php';

	add_action( 'wp_abilities_api_categories_init', 'bbcs_register_wp_ability_categories' );
	add_action( 'wp_abilities_api_init', 'bbcs_register_wp_abilities' );
	add_action( 'wp_ability_invoked', 'bbcs_wp_ability_audit_log', 10, 3 );
	add_filter( 'wp_get_abilities_item_include', 'bbcs_wp_ability_filter_visibility', 10, 2 );
}
