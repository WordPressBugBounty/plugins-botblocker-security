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
				'description'   => __( 'Returns the minimal protection state: protection switch, secure mode, CAPTCHA mode, plugin version and contract version. Use get-protection-snapshot for the matching preset and edge auto-trust, and get-layer-state for execution layers.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => false ),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array_merge(
						bbcs_ability_schema_status_core()['properties'],
						array(
							'version'          => array( 'type' => 'string', 'description' => __( 'Installed BotBlocker plugin version.', 'botblocker-security' ) ),
							'contract_version' => array( 'type' => 'string', 'description' => __( 'Ability schema revision; "2" for this contract.', 'botblocker-security' ) ),
						)
					),
					'required'             => array_merge( bbcs_ability_schema_status_core()['required'], array( 'version', 'contract_version' ) ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_firewall_status',
			),
			array(
				'name'          => 'botblocker/check-ip',
				'setting_key'   => 'bbcs_ability_check_ip',
				'label'         => __( 'Check an IP address', 'botblocker-security' ),
				'description'   => __( 'Checks whether an IP address is blocked by stored IP rules and hot-bans, and returns the deciding rule and the number of matching rules. Use get-ip-rules for the full list. It does not evaluate bot signatures, geo, ASN or rate limits; use simulate-request for a full prediction.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip' => bbcs_ability_schema_ip_string(),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'blocked'            => array( 'type' => 'boolean', 'description' => __( 'Whether an active block rule or hot-ban covers the address.', 'botblocker-security' ) ),
						'reason'             => array( 'type' => 'string', 'description' => __( 'Rule comment or hot-ban reason; empty when not blocked.', 'botblocker-security' ) ),
						'source'             => array( 'type' => 'string', 'enum' => array( 'db', 'hot-ban', 'expired', 'none' ), 'description' => __( 'Where the verdict came from: a database rule, a hot-ban, an expired rule or no match.', 'botblocker-security' ) ),
						'matched_rules'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of stored rules covering the address.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on.', 'botblocker-security' ) ),
						'deciding_rule'      => array_merge(
							bbcs_ability_schema_rule_row(),
							array(
								'type'        => array( 'object', 'null' ),
								'description' => __( 'The rule that decided the answer, or null when no rule matched.', 'botblocker-security' ),
							)
						),
					),
					'required'             => array( 'blocked', 'reason', 'source', 'matched_rules', 'protection_enabled', 'deciding_rule' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_check_ip',
			),
			array(
				'name'          => 'botblocker/check-ips',
				'setting_key'   => 'bbcs_ability_check_ips',
				'label'         => __( 'Check several IP addresses', 'botblocker-security' ),
				'description'   => __( 'Checks a batch of IP addresses against stored IP rules and hot-bans; it is not a full firewall simulation. Invalid addresses are reported per item and do not abort the batch.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ips' => array(
							'type'        => 'array',
							'minItems'    => 1,
							'maxItems'    => 20,
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'IPv4 or IPv6 addresses to check, at most 20.', 'botblocker-security' ),
						),
					),
					'required'   => array( 'ips' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'results' => array(
							'type'        => 'array',
							'description' => __( 'One result per requested address, in the same order.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'ip'            => array( 'type' => 'string', 'description' => __( 'Address as submitted.', 'botblocker-security' ) ),
									'valid'         => array( 'type' => 'boolean', 'description' => __( 'Whether the submitted value is a valid IP address.', 'botblocker-security' ) ),
									'blocked'       => array( 'type' => 'boolean', 'description' => __( 'Whether an active block rule or hot-ban covers the address.', 'botblocker-security' ) ),
									'reason'        => array( 'type' => 'string', 'description' => __( 'Rule comment or hot-ban reason; empty when not blocked.', 'botblocker-security' ) ),
									'source'        => array( 'type' => 'string', 'description' => __( 'Verdict source code; empty for an invalid address.', 'botblocker-security' ) ),
									'matched_rules' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of stored rules covering the address.', 'botblocker-security' ) ),
									'error'         => array( 'type' => 'string', 'description' => __( 'invalid_ip for a malformed address; empty otherwise.', 'botblocker-security' ) ),
								),
								'required'             => array( 'ip', 'valid', 'blocked', 'reason', 'source', 'matched_rules', 'error' ),
								'additionalProperties' => false,
							),
						),
						'total'   => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of items in results.', 'botblocker-security' ) ),
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
				'description'   => __( 'Returns the firewall rules matching a given IP address, ordered by priority. Addresses are returned unmasked because rule management is an administrative task. Use check-ip for the verdict; use get-rule-match to include custom rules.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip' => bbcs_ability_schema_ip_string(),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules' => array( 'type' => 'array', 'items' => bbcs_ability_schema_rule_row(), 'description' => __( 'Rules covering the address, ordered by priority.', 'botblocker-security' ) ),
						'total' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of returned rules.', 'botblocker-security' ) ),
					),
					'required'             => array( 'rules', 'total' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_ip_rules',
			),
			array(
				'name'          => 'botblocker/list-ip-rules',
				'setting_key'   => 'bbcs_ability_list_ip_rules',
				'label'         => __( 'List IP rules', 'botblocker-security' ),
				'description'   => __( 'Lists firewall rules with filters for family, rule type and search text, sorted by priority then id then family, with pagination. Addresses are returned unmasked because rule management is an administrative task.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'family'      => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Restrict to one address family; omit for both.', 'botblocker-security' ) ),
						'rule'        => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark' ), 'description' => __( 'Restrict to one stored rule type; omit for all types.', 'botblocker-security' ) ),
						'search'      => array( 'type' => 'string', 'description' => __( 'Case-insensitive text matched against the rule address and comment.', 'botblocker-security' ) ),
						'active_only' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, expired rules are omitted.', 'botblocker-security' ) ),
						'limit'       => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20, 'description' => __( 'Maximum rules to return.', 'botblocker-security' ) ),
						'offset'      => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => __( 'Rules to skip before returning results.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules' => array( 'type' => 'array', 'items' => bbcs_ability_schema_rule_row(), 'description' => __( 'Page of rules, ordered by priority, id and family.', 'botblocker-security' ) ),
						'total' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of rules matching the filters, ignoring pagination.', 'botblocker-security' ) ),
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
				'description'   => __( 'Returns the active block rules with the blocked address in the value field. Addresses are returned unmasked because rule management is an administrative task.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'family' => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Restrict to one address family; omit for both.', 'botblocker-security' ) ),
						'limit'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20, 'description' => __( 'Maximum rules to return.', 'botblocker-security' ) ),
						'offset' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => __( 'Rules to skip before returning results.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'rules' => array( 'type' => 'array', 'items' => bbcs_ability_schema_rule_row(), 'description' => __( 'Active block rules.', 'botblocker-security' ) ),
						'total' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of active block rules matching the filter.', 'botblocker-security' ) ),
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
				'description'   => __( 'Returns aggregated request, block, CAPTCHA and proxy counters for a period. hits counts every request, blocks every suspicious-table row, suspicious rows with passed=0, captcha rows with a non-empty recaptcha value, proxied rows with a non-empty proxy value.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'period' => array( 'type' => 'string', 'enum' => array( 'day', 'week', 'month' ), 'default' => 'day', 'description' => __( 'Aggregation window: the last day, week or month.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'period'     => array( 'type' => 'string', 'enum' => array( 'day', 'week', 'month' ), 'description' => __( 'Period the counters were computed for.', 'botblocker-security' ) ),
						'hits'       => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'All requests in both traffic tables.', 'botblocker-security' ) ),
						'blocks'     => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'All suspicious-table rows.', 'botblocker-security' ) ),
						'captcha'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rows with a non-empty recaptcha value.', 'botblocker-security' ) ),
						'suspicious' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Suspicious-table rows with passed=0.', 'botblocker-security' ) ),
						'proxy'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rows with a non-empty proxy value.', 'botblocker-security' ) ),
					),
					'required'             => array( 'period', 'hits', 'blocks', 'captcha', 'suspicious', 'proxy' ),
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
						'period' => array( 'type' => 'string', 'enum' => array( 'day', 'week', 'month' ), 'default' => 'day', 'description' => __( 'Aggregation window: the last day, week or month.', 'botblocker-security' ) ),
						'type'   => array( 'type' => 'string', 'enum' => array( 'block', 'allow', 'captcha', 'proxy' ), 'description' => __( 'Restrict to one event type; omit for all four.', 'botblocker-security' ) ),
						'limit'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 500, 'default' => 100, 'description' => __( 'Maximum buckets to return.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'events' => array(
							'type'        => 'array',
							'description' => __( 'Aggregated event buckets.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'bucket'         => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix timestamp of the bucket start.', 'botblocker-security' ) ),
									'bucket_seconds' => array( 'type' => 'integer', 'minimum' => 1, 'description' => __( 'Bucket width in seconds.', 'botblocker-security' ) ),
									'type'           => array( 'type' => 'string', 'enum' => array( 'block', 'allow', 'captcha', 'proxy' ), 'description' => __( 'Event type of the bucket.', 'botblocker-security' ) ),
									'count'          => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Events in the bucket.', 'botblocker-security' ) ),
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
				'description'   => __( 'Returns non-secret BotBlocker settings. Credentials and PII are reported only as configured true in redacted; group all includes every stored setting, including the bbcs_ability_* switches.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'group' => array( 'type' => 'string', 'enum' => array( 'protection', 'captcha', 'logging', 'integrations', 'all' ), 'default' => 'protection', 'description' => __( 'Settings group to return; all returns every stored setting.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'group'    => array( 'type' => 'string', 'enum' => array( 'protection', 'captcha', 'logging', 'integrations', 'all' ), 'description' => __( 'Requested settings group.', 'botblocker-security' ) ),
						'values'   => array( 'type' => 'object', 'additionalProperties' => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'null', 'array', 'object' ) ), 'description' => __( 'Non-empty setting name to stored value; redacted keys are excluded.', 'botblocker-security' ) ),
						'redacted' => array( 'type' => 'object', 'additionalProperties' => array( 'type' => 'boolean' ), 'description' => __( 'Credential and PII setting name to true when configured; the value is never returned.', 'botblocker-security' ) ),
						'writable' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'All keys botblocker/set-setting accepts; independent of the requested group.', 'botblocker-security' ) ),
					),
					'required'             => array( 'group', 'values', 'redacted', 'writable' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_get_settings',
			),
			array(
				'name'          => 'botblocker/get-captcha-state',
				'setting_key'   => 'bbcs_ability_get_captcha_state',
				'label'         => __( 'Get CAPTCHA state', 'botblocker-security' ),
				'description'   => __( 'Returns the active CAPTCHA mode, wait time, image packing and the availability of every mode on this site. Use get-challenge-state for the challenge runtime: retries left and fallback.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => false ),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'            => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Active CAPTCHA mode id.', 'botblocker-security' ) ),
						'mode_label'      => array( 'type' => 'string', 'description' => __( 'Human-readable active mode label; do not parse.', 'botblocker-security' ) ),
						'wait_seconds'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Configured wait before showing the challenge.', 'botblocker-security' ) ),
						'image_pack'      => array( 'type' => 'boolean', 'description' => __( 'Whether CAPTCHA images are packed.', 'botblocker-security' ) ),
						'inline_images'   => array( 'type' => 'boolean', 'description' => __( 'Whether CAPTCHA images are inlined.', 'botblocker-security' ) ),
						'available_modes' => array(
							'type'        => 'array',
							'description' => __( 'Every CAPTCHA mode known to this site and whether it can run.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'mode'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'CAPTCHA mode id.', 'botblocker-security' ) ),
									'slug'      => array( 'type' => 'string', 'description' => __( 'Stable mode name accepted by set-captcha-mode.', 'botblocker-security' ) ),
									'label'     => array( 'type' => 'string', 'description' => __( 'Human-readable mode label; do not parse.', 'botblocker-security' ) ),
									'available' => array( 'type' => 'boolean', 'description' => __( 'Whether the mode can run on this site.', 'botblocker-security' ) ),
									'reason'    => array( 'type' => 'string', 'enum' => array( '', 'gd_required', 'keys_required' ), 'description' => __( 'Why the mode is unavailable: gd_required, keys_required, or an empty string when available.', 'botblocker-security' ) ),
								),
								'required'             => array( 'mode', 'slug', 'label', 'available', 'reason' ),
								'additionalProperties' => false,
							),
						),
						'warnings'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Warning codes; empty when there is nothing to report.', 'botblocker-security' ) ),
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
				'description'   => __( 'Runs a set of read-only health checks against the BotBlocker data directory, settings file and database tables. Use validate-config for the wider configuration checks and get-data-integrity for the file inventory.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => false ),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'passed' => array( 'type' => 'boolean', 'description' => __( 'Whether every check passed.', 'botblocker-security' ) ),
						'items'  => array(
							'type'        => 'array',
							'description' => __( 'Individual diagnostic checks.', 'botblocker-security' ),
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'key'    => array( 'type' => 'string', 'enum' => array( 'data_dir', 'settings_file', 'tables' ), 'description' => __( 'Check identifier.', 'botblocker-security' ) ),
									'label'  => array( 'type' => 'string', 'description' => __( 'Human-readable check label; do not parse.', 'botblocker-security' ) ),
									'passed' => array( 'type' => 'boolean', 'description' => __( 'Whether this check passed.', 'botblocker-security' ) ),
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
				'input_schema'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => false ),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'active'   => array( 'type' => 'boolean', 'description' => __( 'Whether the self-test reports the shield as active.', 'botblocker-security' ) ),
						'verdict'  => array( 'type' => 'string', 'enum' => array( 'passed_silently', 'captcha_solved', 'captcha_shown', 'challenge_page', 'pending' ), 'description' => __( 'Self-test verdict code.', 'botblocker-security' ) ),
						'settings' => array(
							'type'                 => 'object',
							'properties'           => array(
								'disabled'          => array( 'type' => 'boolean', 'description' => __( 'Whether protection is disabled.', 'botblocker-security' ) ),
								'secure_mode'       => array( 'type' => 'string', 'enum' => array( 'frontend', 'full' ), 'description' => __( 'Stable secure mode code.', 'botblocker-security' ) ),
								'captcha_mode'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Active CAPTCHA mode id.', 'botblocker-security' ) ),
								'captcha_mode_label' => array( 'type' => 'string', 'description' => __( 'Translated CAPTCHA mode label; do not parse.', 'botblocker-security' ) ),
								'silent'            => array( 'type' => 'boolean', 'description' => __( 'Whether the silent mode is on.', 'botblocker-security' ) ),
								'force_check'       => array( 'type' => 'boolean', 'description' => __( 'Whether the forced check is on.', 'botblocker-security' ) ),
							),
							'required'             => array( 'disabled', 'secure_mode', 'captcha_mode', 'captcha_mode_label', 'silent', 'force_check' ),
							'additionalProperties' => false,
							'description'          => __( 'Stable self-test settings summary.', 'botblocker-security' ) ),
						'bypasses' => array( 'type' => 'array', 'items' => array( 'type' => 'string', 'enum' => array( 'admin_role', 'logged_in', 'pass_cookie', 'allowed_ip' ) ), 'description' => __( 'Active bypass codes.', 'botblocker-security' ) ),
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
				'description'   => __( 'Adds a firewall block rule for an IP address and regenerates the runtime ban files. Use upsert-ip-rule to create allow, gray or dark rules or to update an existing rule. Requires confirm "BLOCK". Refuses readonly rules and self-blocking without force. Supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'      => bbcs_ability_schema_ip_string(),
						'reason'  => array( 'type' => 'string', 'description' => __( 'Rule comment. Defaults to "Blocked via WP Abilities"; an existing comment is preserved when the field is omitted.', 'botblocker-security' ) ),
						'expires' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix expiry timestamp; 0 means never.', 'botblocker-security' ) ),
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be BLOCK.', 'botblocker-security' ) ),
						'force'   => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'Overrides the self-block refusal and the allow-rule downgrade refusal. Readonly rows are never overridden.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is written and the answer is a projection.', 'botblocker-security' ) ),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'created'            => array( 'type' => 'boolean', 'description' => __( 'True when a new rule row was created; false on dry run.', 'botblocker-security' ) ),
						'id'                 => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rule row id; 0 when a new row is only projected on dry run.', 'botblocker-security' ) ),
						'previous_rule'      => array( 'type' => 'string', 'description' => __( 'Rule type stored before the call; empty when no rule existed.', 'botblocker-security' ) ),
						'changed'            => array( 'type' => 'boolean', 'description' => __( 'Whether the stored rule, expiry or comment differs from the request.', 'botblocker-security' ) ),
						'applied'            => array( 'type' => 'boolean', 'description' => __( 'True only when the write path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'            => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'created', 'id', 'previous_rule', 'changed', 'applied', 'dry_run', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_block_ip',
			),
			array(
				'name'          => 'botblocker/unblock-ip',
				'setting_key'   => 'bbcs_ability_unblock_ip',
				'label'         => __( 'Unblock an IP address', 'botblocker-security' ),
				'description'   => __( 'Removes non-readonly firewall rules for an IP address and regenerates the runtime ban files. Use delete-ip-rules to remove every rule type for an address. Requires confirm "UNBLOCK" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'      => bbcs_ability_schema_ip_string(),
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be UNBLOCK.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is deleted and the answer is a projection.', 'botblocker-security' ) ),
					),
					'required'   => array( 'ip' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'deleted'            => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of removed rules; 0 on dry run.', 'botblocker-security' ) ),
						'previous_rules'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Rule types stored before the call.', 'botblocker-security' ) ),
						'changed'            => array( 'type' => 'boolean', 'description' => __( 'Whether any deletable rule existed.', 'botblocker-security' ) ),
						'applied'            => array( 'type' => 'boolean', 'description' => __( 'True only when the delete path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'            => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'deleted', 'previous_rules', 'changed', 'applied', 'dry_run', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_unblock_ip',
			),
			array(
				'name'          => 'botblocker/upsert-ip-rule',
				'setting_key'   => 'bbcs_ability_upsert_ip_rule',
				'label'         => __( 'Create or update an IP rule', 'botblocker-security' ),
				'description'   => __( 'Creates or updates a firewall rule for an IP address or CIDR range. Requires confirm "UPSERT". Refuses readonly rows and refuses to downgrade an allow rule or block trusted addresses without force. Supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'ip'         => array( 'type' => 'string', 'description' => __( 'IPv4 or IPv6 address or CIDR range.', 'botblocker-security' ) ),
						'rule'       => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark' ), 'description' => __( 'Rule type to store.', 'botblocker-security' ) ),
						'expires'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix expiry timestamp; 0 means never. Omitted fields keep the existing value on update; new rules are permanent.', 'botblocker-security' ) ),
						'expires_in' => array( 'type' => 'integer', 'minimum' => 60, 'maximum' => 31536000, 'description' => __( 'Relative expiry in seconds; an alternative to expires. Omitted fields keep the existing value on update; new rules are permanent.', 'botblocker-security' ) ),
						'comment'    => array( 'type' => 'string', 'description' => __( 'Rule comment. Omitted fields keep the existing value on update; new rules use an empty comment.', 'botblocker-security' ) ),
						'priority'   => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 100000, 'description' => __( 'Evaluation priority. Omitted fields keep the existing value on update; new rules use 100.', 'botblocker-security' ) ),
						'force'      => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'Overrides the self-block refusal and the allow-rule downgrade refusal. Readonly rows are never overridden.', 'botblocker-security' ) ),
						'dry_run'    => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is written and the answer is a projection.', 'botblocker-security' ) ),
						'confirm'    => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be UPSERT.', 'botblocker-security' ) ),
					),
					'required'   => array( 'ip', 'rule' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'created'            => array( 'type' => 'boolean', 'description' => __( 'True when a new rule row was created; false on dry run.', 'botblocker-security' ) ),
						'id'                 => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rule row id; 0 when a new row is only projected on dry run.', 'botblocker-security' ) ),
						'previous_rule'      => array( 'type' => 'string', 'description' => __( 'Rule type stored before the call; empty when no rule existed.', 'botblocker-security' ) ),
						'changed'            => array( 'type' => 'boolean', 'description' => __( 'Whether the stored rule, expiry, priority or comment differs from the request.', 'botblocker-security' ) ),
						'applied'            => array( 'type' => 'boolean', 'description' => __( 'True only when the write path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'            => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'family'             => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Address family of the stored rule.', 'botblocker-security' ) ),
						'expires'            => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Resulting expiry timestamp; 0 means never.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'created', 'id', 'previous_rule', 'changed', 'applied', 'dry_run', 'family', 'expires', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_upsert_ip_rule',
			),
			array(
				'name'          => 'botblocker/delete-ip-rule',
				'setting_key'   => 'bbcs_ability_delete_ip_rule',
				'label'         => __( 'Delete an IP rule by id', 'botblocker-security' ),
				'description'   => __( 'Deletes a single firewall rule by its id. Rule ids are unique only inside one address family, so pass family when the same id exists in both. Requires confirm "DELETE". Refuses readonly rows. Supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer', 'minimum' => 1, 'description' => __( 'Rule row id.', 'botblocker-security' ) ),
						'family'  => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Address family; required when the id exists in both families.', 'botblocker-security' ) ),
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be DELETE.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is deleted and the answer is a projection.', 'botblocker-security' ) ),
					),
					'required'   => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'deleted'            => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 1, 'description' => __( '1 when the row was deleted; 0 on dry run.', 'botblocker-security' ) ),
						'id'                 => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Id of the addressed rule.', 'botblocker-security' ) ),
						'rule'               => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark', 'noop' ), 'description' => __( 'Rule type of the addressed row.', 'botblocker-security' ) ),
						'family'             => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Address family of the addressed row.', 'botblocker-security' ) ),
						'changed'            => array( 'type' => 'boolean', 'description' => __( 'True when the addressed rule exists.', 'botblocker-security' ) ),
						'applied'            => array( 'type' => 'boolean', 'description' => __( 'True only when the delete path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'            => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'deleted', 'id', 'rule', 'family', 'changed', 'applied', 'dry_run', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_delete_ip_rule',
			),
			array(
				'name'          => 'botblocker/set-protection',
				'setting_key'   => 'bbcs_ability_set_protection',
				'label'         => __( 'Enable or disable protection', 'botblocker-security' ),
				'description'   => __( 'Turns BotBlocker protection on or off through the same path as the Dashboard toggle, including the runtime settings file. Requires confirm "ENABLE" when enabling and "DISABLE" when disabling. Supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'enabled' => array( 'type' => 'boolean', 'description' => __( 'Target protection state.', 'botblocker-security' ) ),
						'reason'  => array( 'type' => 'string', 'description' => __( 'Free-form audit note; not stored as a setting.', 'botblocker-security' ) ),
						'confirm' => array( 'type' => 'string', 'description' => __( 'ENABLE when enabled is true; DISABLE when enabled is false.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is written and the answer is a projection.', 'botblocker-security' ) ),
					),
					'required'   => array( 'enabled' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'enabled'            => array( 'type' => 'boolean', 'description' => __( 'Requested protection state.', 'botblocker-security' ) ),
						'previous'           => array( 'type' => 'boolean', 'description' => __( 'Protection state before the call.', 'botblocker-security' ) ),
						'changed'            => array( 'type' => 'boolean', 'description' => __( 'Whether the requested state differs from the stored state.', 'botblocker-security' ) ),
						'applied'            => array( 'type' => 'boolean', 'description' => __( 'True only when the write path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'            => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Current protection state; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'enabled', 'previous', 'changed', 'applied', 'dry_run', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_set_protection',
			),
			array(
				'name'          => 'botblocker/set-secure-mode',
				'setting_key'   => 'bbcs_ability_set_secure_mode',
				'label'         => __( 'Set the secure mode', 'botblocker-security' ),
				'description'   => __( 'Switches the secure mode between frontend and full. Full mode also challenges the WordPress admin area. Requires confirm "FRONTEND" when mode is frontend and "FULL" when mode is full. Supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'mode'    => array( 'type' => 'string', 'enum' => array( 'frontend', 'full' ), 'description' => __( 'Target secure mode.', 'botblocker-security' ) ),
						'confirm' => array( 'type' => 'string', 'description' => __( 'FRONTEND when mode is frontend; FULL when mode is full.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is written and the answer is a projection.', 'botblocker-security' ) ),
					),
					'required'   => array( 'mode' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'               => array( 'type' => 'string', 'enum' => array( 'frontend', 'full' ), 'description' => __( 'Requested secure mode.', 'botblocker-security' ) ),
						'previous'           => array( 'type' => 'string', 'enum' => array( 'frontend', 'full' ), 'description' => __( 'Secure mode before the call.', 'botblocker-security' ) ),
						'changed'            => array( 'type' => 'boolean', 'description' => __( 'Whether the requested mode differs from the stored mode.', 'botblocker-security' ) ),
						'applied'            => array( 'type' => 'boolean', 'description' => __( 'True only when the write path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'            => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'warnings'           => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Warning codes; empty when there are none.', 'botblocker-security' ) ),
						'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'mode', 'previous', 'changed', 'applied', 'dry_run', 'warnings', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_set_secure_mode',
			),
			array(
				'name'          => 'botblocker/set-captcha-mode',
				'setting_key'   => 'bbcs_ability_set_captcha_mode',
				'label'         => __( 'Set the CAPTCHA mode', 'botblocker-security' ),
				'description'   => __( 'Switches the CAPTCHA mode and optional wait time. Use get-captcha-state to discover the mode ids and availability on this site. A mode that is unavailable is rejected instead of silently falling back. Requires confirm "CAPTCHA" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'mode'         => array(
							'type'        => array( 'integer', 'string' ),
							'description' => __( 'Mode id or slug from get-captcha-state.available_modes. The alias color-buttons is accepted but not advertised.', 'botblocker-security' ),
							'oneOf'       => array(
								array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Numeric CAPTCHA mode id.', 'botblocker-security' ) ),
								array( 'type' => 'string', 'enum' => array( 'button', 'color_buttons', 'image', 'recaptcha_v2_button', 'recaptcha_v2', 'shape', 'digit', 'hold', 'silent' ), 'description' => __( 'Built-in mode name accepted by this ability.', 'botblocker-security' ) ),
							),
						),
						'wait_seconds' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 300, 'description' => __( 'Wait before showing the challenge; omitted keeps the current value.', 'botblocker-security' ) ),
						'dry_run'      => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is written and the answer is a projection.', 'botblocker-security' ) ),
						'confirm'      => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be CAPTCHA.', 'botblocker-security' ) ),
					),
					'required'   => array( 'mode' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'                 => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Resolved CAPTCHA mode id after the call.', 'botblocker-security' ) ),
						'mode_label'           => array( 'type' => 'string', 'description' => __( 'Human-readable mode label; do not parse.', 'botblocker-security' ) ),
						'previous'             => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'CAPTCHA mode id before the call.', 'botblocker-security' ) ),
						'wait_seconds'         => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 300, 'description' => __( 'Target wait time after clamping; the current value when the field was omitted.', 'botblocker-security' ) ),
						'previous_wait_seconds' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Stored wait time before the call.', 'botblocker-security' ) ),
						'changed'              => array( 'type' => 'boolean', 'description' => __( 'Whether the mode or the wait time changes.', 'botblocker-security' ) ),
						'applied'              => array( 'type' => 'boolean', 'description' => __( 'True only when the write path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'              => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
						'warnings'             => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Warning codes; empty when there are none.', 'botblocker-security' ) ),
						'protection_enabled'   => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on; always the actual state.', 'botblocker-security' ) ),
					),
					'required'             => array( 'mode', 'mode_label', 'previous', 'wait_seconds', 'previous_wait_seconds', 'changed', 'applied', 'dry_run', 'warnings', 'protection_enabled' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_set_captcha_mode',
			),
			array(
				'name'          => 'botblocker/clear-cache',
				'setting_key'   => 'bbcs_ability_clear_cache',
				'label'         => __( 'Clear the plugin file cache', 'botblocker-security' ),
				'description'   => __( 'Clears BotBlocker cache buckets, rotates the object-cache generation and invalidates OPcache for generated data files. It does not touch rules, settings or bans. Requires confirm "CLEAR" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
				'input_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be CLEAR.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is cleared and the answer is a projection.', 'botblocker-security' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'changed' => array( 'type' => 'boolean', 'description' => __( 'Whether cache bucket files existed before the call.', 'botblocker-security' ) ),
						'applied' => array( 'type' => 'boolean', 'description' => __( 'True only when the cache was cleared; false on dry run.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
					),
					'required'             => array( 'changed', 'applied', 'dry_run' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_clear_cache',
			),
			array(
				'name'          => 'botblocker/remove-edge-trust',
				'setting_key'   => 'bbcs_ability_remove_edge_trust',
				'label'         => __( 'Remove an auto-trusted edge', 'botblocker-security' ),
				'description'   => __( 'Removes one entry from the auto-trust file by its mask and records an audit entry. The file is re-signed after the edit. Requires confirm "REMOVE" and supports dry_run.', 'botblocker-security' ),
				'annotations'   => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ),
				'risk'          => 'danger',
				'input_schema'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'mask'    => array( 'type' => 'string', 'maxLength' => 64, 'description' => __( 'Auto-trust mask as returned by get-edge-trust.', 'botblocker-security' ) ),
						'confirm' => array( 'type' => 'string', 'description' => __( 'Confirmation token; must be REMOVE.', 'botblocker-security' ) ),
						'dry_run' => array( 'type' => 'boolean', 'default' => false, 'description' => __( 'When true, nothing is removed and the answer is a projection.', 'botblocker-security' ) ),
					),
					'required'             => array( 'mask' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'deleted'   => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 1, 'description' => __( '1 when the entry was removed; 0 on dry run.', 'botblocker-security' ) ),
						'mask'      => array( 'type' => 'string', 'description' => __( 'Mask that was addressed.', 'botblocker-security' ) ),
						'remaining' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Entries left after the call; a projection on dry run.', 'botblocker-security' ) ),
						'changed'   => array( 'type' => 'boolean', 'description' => __( 'True when an entry with this mask exists.', 'botblocker-security' ) ),
						'applied'   => array( 'type' => 'boolean', 'description' => __( 'True only when the write path ran successfully; false on dry run.', 'botblocker-security' ) ),
						'dry_run'   => array( 'type' => 'boolean', 'description' => __( 'Mirrors the dry_run input.', 'botblocker-security' ) ),
					),
					'required'             => array( 'deleted', 'mask', 'remaining', 'changed', 'applied', 'dry_run' ),
					'additionalProperties' => false,
				),
				'execute'       => 'bbcs_wp_ability_execute_remove_edge_trust',
			),
		);

		$extended    = function_exists( 'bbcs_get_wp_ability_extended_definitions' ) ? bbcs_get_wp_ability_extended_definitions() : array();
		$diagnostics = function_exists( 'bbcs_get_wp_ability_diagnostics_definitions' ) ? bbcs_get_wp_ability_diagnostics_definitions() : array();
		$all         = array_merge( $definitions, is_array( $extended ) ? $extended : array(), is_array( $diagnostics ) ? $diagnostics : array() );

		foreach ( $all as $bbcs_index => $bbcs_definition ) {
			if ( is_array( $bbcs_definition ) ) {
				$all[ $bbcs_index ] = bbcs_ability_enrich_definition( $bbcs_definition );
			}
		}

		return $all;
	}

	function bbcs_ability_schema_ip_string(): array {
		return array( 'type' => 'string', 'description' => __( 'IPv4 or IPv6 address.', 'botblocker-security' ) );
	}

	function bbcs_ability_schema_rule_row(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'       => array( 'type' => 'integer', 'minimum' => 1, 'description' => __( 'Rule row id, unique inside one address family.', 'botblocker-security' ) ),
				'family'   => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6' ), 'description' => __( 'Address family of the rule.', 'botblocker-security' ) ),
				'rule'     => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark', 'noop' ), 'description' => __( 'Stored rule type; noop is a non-matching row.', 'botblocker-security' ) ),
				'value'    => array( 'type' => 'string', 'description' => __( 'Stored address or CIDR, unmasked.', 'botblocker-security' ) ),
				'priority' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 100000, 'description' => __( 'Lower values are evaluated first.', 'botblocker-security' ) ),
				'expires'  => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix timestamp; 0 means never.', 'botblocker-security' ) ),
				'expired'  => array( 'type' => 'boolean', 'description' => __( 'Whether the rule has expired.', 'botblocker-security' ) ),
				'readonly' => array( 'type' => 'boolean', 'description' => __( 'Plugin-managed row; cannot be edited or deleted.', 'botblocker-security' ) ),
				'comment'  => array( 'type' => 'string', 'description' => __( 'Stored rule comment.', 'botblocker-security' ) ),
			),
			'required'             => array( 'id', 'family', 'rule', 'value', 'priority', 'expires', 'expired', 'readonly', 'comment' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_match_row(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'       => array( 'type' => 'integer', 'description' => __( 'Rule row id; 0 for a rule that is not stored by id.', 'botblocker-security' ) ),
				'source'   => array( 'type' => 'string', 'enum' => array( 'ip', 'custom' ), 'description' => __( 'ip for a stored IP rule; custom for a custom rule.', 'botblocker-security' ) ),
				'matcher'  => array( 'type' => 'string', 'description' => __( 'ip for an IP rule; otherwise the custom matcher type.', 'botblocker-security' ) ),
				'family'   => array( 'type' => 'string', 'enum' => array( 'ipv4', 'ipv6', '' ), 'description' => __( 'Address family; an empty string for a custom rule.', 'botblocker-security' ) ),
				'rule'     => array( 'type' => 'string', 'description' => __( 'Stored rule type.', 'botblocker-security' ) ),
				'value'    => array( 'type' => 'string', 'description' => __( 'Address, CIDR or custom value that matched.', 'botblocker-security' ) ),
				'priority' => array( 'type' => 'integer', 'description' => __( 'Evaluation priority of the matched rule.', 'botblocker-security' ) ),
				'action'   => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'gray', 'dark', 'noop' ), 'description' => __( 'Action the matched rule produces.', 'botblocker-security' ) ),
				'expired'  => array( 'type' => 'boolean', 'description' => __( 'Whether the rule has expired.', 'botblocker-security' ) ),
			),
			'required'             => array( 'id', 'source', 'matcher', 'family', 'rule', 'value', 'priority', 'action', 'expired' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_rate_state(): array {
		$counters = array(
			'type'  => 'array',
			'items' => array(
				'type'                 => 'object',
				'properties'           => array(
					'minute' => array( 'type' => 'integer', 'description' => __( 'Minute bucket, as a Unix timestamp truncated to the minute.', 'botblocker-security' ) ),
					'count'  => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Requests recorded in this minute.', 'botblocker-security' ) ),
				),
				'required'             => array( 'minute', 'count' ),
				'additionalProperties' => false,
			),
			'description' => __( 'Per-minute request counters inside the window.', 'botblocker-security' ),
		);
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'bucket'                => array( 'type' => 'string', 'description' => __( 'Rate-limit bucket key for the address.', 'botblocker-security' ) ),
				'rpm'                   => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Requests per minute in the current window.', 'botblocker-security' ) ),
				'total_hits'            => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Total requests recorded in the window.', 'botblocker-security' ) ),
				'window_minutes'        => array( 'type' => 'integer', 'minimum' => 1, 'description' => __( 'Length of the rate-limit window in minutes.', 'botblocker-security' ) ),
				'counters'              => $counters,
				'effective_block_rpm'   => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Block threshold applied to this request.', 'botblocker-security' ) ),
				'effective_captcha_rpm' => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'CAPTCHA threshold applied to this request.', 'botblocker-security' ) ),
				'check_enabled'         => array( 'type' => 'boolean', 'description' => __( 'Whether the rate-limit check is enabled.', 'botblocker-security' ) ),
				'subnet'                => array(
					'type'                 => 'object',
					'properties'           => array(
						'enabled'  => array( 'type' => 'boolean', 'description' => __( 'Whether the subnet rate limit is enabled.', 'botblocker-security' ) ),
						'cidr'     => array( 'type' => 'string', 'description' => __( 'Subnet CIDR the address belongs to.', 'botblocker-security' ) ),
						'bucket'   => array( 'type' => 'string', 'description' => __( 'Rate-limit bucket key for the subnet.', 'botblocker-security' ) ),
						'rpm'      => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Subnet requests per minute in the current window.', 'botblocker-security' ) ),
						'pressure' => array( 'type' => 'number', 'minimum' => 0, 'maximum' => 1, 'description' => __( 'Subnet load as a fraction of its threshold.', 'botblocker-security' ) ),
						'counters' => $counters,
					),
					'required'             => array( 'enabled', 'cidr', 'bucket', 'rpm', 'pressure', 'counters' ),
					'additionalProperties' => false,
					'description'          => __( 'Subnet rate-limit state for the address.', 'botblocker-security' ),
				),
				'ban'                   => array(
					'type'                 => 'object',
					'properties'           => array(
						'active'  => array( 'type' => 'boolean', 'description' => __( 'Whether a rate-limit ban is active.', 'botblocker-security' ) ),
						'expires' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix expiry timestamp; 0 means no active ban.', 'botblocker-security' ) ),
						'source'  => array( 'type' => 'string', 'enum' => array( '', 'rate-limit-block' ), 'description' => __( 'Ban source; an empty string when no ban is active.', 'botblocker-security' ) ),
					),
					'required'             => array( 'active', 'expires', 'source' ),
					'additionalProperties' => false,
					'description'          => __( 'Rate-limit ban attached to the bucket.', 'botblocker-security' ),
				),
			),
			'required'             => array( 'bucket', 'rpm', 'total_hits', 'window_minutes', 'counters', 'effective_block_rpm', 'effective_captcha_rpm', 'check_enabled', 'subnet', 'ban' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_pass_state(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'valid'      => array( 'type' => 'boolean', 'description' => __( 'True only when format, expiry and signature all pass.', 'botblocker-security' ) ),
				'expired'    => array( 'type' => 'boolean', 'description' => __( 'Whether the cookie expiry has passed.', 'botblocker-security' ) ),
				'expires_in' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Seconds until expiry; 0 when expired or unknown.', 'botblocker-security' ) ),
				'expires'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Unix expiry timestamp; 0 means unknown.', 'botblocker-security' ) ),
				'format'     => array( 'type' => 'string', 'enum' => array( 'unknown', 'cache-pass' ), 'description' => __( 'Detected cookie format.', 'botblocker-security' ) ),
				'bound'      => array(
					'type'                 => 'object',
					'properties'           => array(
						'ip'      => array( 'type' => 'boolean', 'description' => __( 'Whether the cookie is bound to the address.', 'botblocker-security' ) ),
						'ua'      => array( 'type' => 'boolean', 'description' => __( 'Whether the cookie is bound to the user agent.', 'botblocker-security' ) ),
						'session' => array( 'type' => 'boolean', 'description' => __( 'Whether the cookie is bound to the session token.', 'botblocker-security' ) ),
					),
					'required'             => array( 'ip', 'ua', 'session' ),
					'additionalProperties' => false,
					'description'          => __( 'Bindings stored in the cookie.', 'botblocker-security' ) ),
				'reason'     => array( 'type' => 'string', 'enum' => array( 'format', 'salt_missing', 'bindings_missing', 'uid_invalid', 'expired', 'ttl', 'signature', 'ok' ), 'description' => __( 'Validation result code.', 'botblocker-security' ) ),
			),
			'required'             => array( 'valid', 'expired', 'expires_in', 'expires', 'format', 'bound', 'reason' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_layer_state(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'early_init' => array(
					'type'                 => 'object',
					'properties'           => array(
						'active'   => array( 'type' => 'boolean', 'description' => __( 'Whether the early-init layer is enabled.', 'botblocker-security' ) ),
						'file'     => array( 'type' => 'string', 'description' => __( 'Path to the early-init router file; empty when missing.', 'botblocker-security' ) ),
						'hash'     => array( 'type' => 'string', 'description' => __( 'SHA-256 of the router file; empty when missing.', 'botblocker-security' ) ),
						'wpconfig' => array(
							'type'                 => 'object',
							'properties'           => array(
								'path'           => array( 'type' => 'string', 'description' => __( 'Path to wp-config.php.', 'botblocker-security' ) ),
								'router_present' => array( 'type' => 'boolean', 'description' => __( 'Whether wp-config.php includes the router.', 'botblocker-security' ) ),
								'hash'           => array( 'type' => 'string', 'description' => __( 'SHA-256 of wp-config.php; empty when unreadable.', 'botblocker-security' ) ),
							),
							'required'             => array( 'path', 'router_present', 'hash' ),
							'additionalProperties' => false,
							'description'          => __( 'wp-config.php state for the early-init layer.', 'botblocker-security' ) ),
					),
					'required'             => array( 'active', 'file', 'hash', 'wpconfig' ),
					'additionalProperties' => false,
					'description'          => __( 'Early-init execution layer.', 'botblocker-security' ) ),
				'mu'         => array(
					'type'                 => 'object',
					'properties'           => array(
						'active' => array( 'type' => 'boolean', 'description' => __( 'Whether the MU layer is enabled.', 'botblocker-security' ) ),
						'file'   => array( 'type' => 'string', 'description' => __( 'Path to the MU-plugin file.', 'botblocker-security' ) ),
						'hash'   => array( 'type' => 'string', 'description' => __( 'SHA-256 of the MU-plugin file; empty when missing.', 'botblocker-security' ) ),
					),
					'required'             => array( 'active', 'file', 'hash' ),
					'additionalProperties' => false,
					'description'          => __( 'MU-plugin execution layer.', 'botblocker-security' ) ),
				'main'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'active'     => array( 'type' => 'boolean', 'description' => __( 'Whether the main plugin layer is loaded.', 'botblocker-security' ) ),
						'version'    => array( 'type' => 'string', 'description' => __( 'Plugin version.', 'botblocker-security' ) ),
						'protection' => array( 'type' => 'boolean', 'description' => __( 'Whether protection is enabled.', 'botblocker-security' ) ),
					),
					'required'             => array( 'active', 'version', 'protection' ),
					'additionalProperties' => false,
					'description'          => __( 'Main shield execution layer.', 'botblocker-security' ) ),
				'gate'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'cache_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether the early cache gate is enabled.', 'botblocker-security' ) ),
						'geo_enabled'   => array( 'type' => 'boolean', 'description' => __( 'Whether geo checks run in an early layer.', 'botblocker-security' ) ),
					),
					'required'             => array( 'cache_enabled', 'geo_enabled' ),
					'additionalProperties' => false,
					'description'          => __( 'Early gate state.', 'botblocker-security' ) ),
				'autotrust'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'enabled'     => array( 'type' => 'boolean', 'description' => __( 'Whether edge auto-trust is enabled.', 'botblocker-security' ) ),
						'entries'     => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Number of active auto-trust entries.', 'botblocker-security' ) ),
						'next_expiry' => array( 'type' => 'string', 'description' => __( 'ISO 8601 timestamp of the earliest expiry; empty when none.', 'botblocker-security' ) ),
					),
					'required'             => array( 'enabled', 'entries', 'next_expiry' ),
					'additionalProperties' => false,
					'description'          => __( 'Edge auto-trust state.', 'botblocker-security' ) ),
				'conflicts'  => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Conflict codes; values are stable codes, not translated text.', 'botblocker-security' ) ),
				'versions'   => array(
					'type'                 => 'object',
					'properties'           => array(
						'php'  => array( 'type' => 'string', 'description' => __( 'PHP version.', 'botblocker-security' ) ),
						'wp'   => array( 'type' => 'string', 'description' => __( 'WordPress version.', 'botblocker-security' ) ),
						'core' => array( 'type' => 'string', 'description' => __( 'Plugin version.', 'botblocker-security' ) ),
					),
					'required'             => array( 'php', 'wp', 'core' ),
					'additionalProperties' => false,
					'description'          => __( 'Runtime versions.', 'botblocker-security' ) ),
			),
			'required'             => array( 'early_init', 'mu', 'main', 'gate', 'autotrust', 'conflicts', 'versions' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_license_state(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'active'         => array( 'type' => 'boolean', 'description' => __( 'Whether the license is active.', 'botblocker-security' ) ),
				'expired'        => array( 'type' => 'boolean', 'description' => __( 'Whether the license has expired.', 'botblocker-security' ) ),
				'type'           => array( 'type' => 'string', 'description' => __( 'License type reported by the license service; empty when unknown.', 'botblocker-security' ) ),
				'tier'           => array( 'type' => 'string', 'description' => __( 'License tier reported by the license service; empty when unknown.', 'botblocker-security' ) ),
				'key_configured' => array( 'type' => 'boolean', 'description' => __( 'Whether a license key is stored.', 'botblocker-security' ) ),
				'remaining_days' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Remaining license days; 0 when unknown or expired.', 'botblocker-security' ) ),
				'remaining_hits' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Remaining license hits; 0 when unknown or unlimited.', 'botblocker-security' ) ),
			),
			'required'             => array( 'active', 'expired', 'type', 'tier', 'key_configured', 'remaining_days', 'remaining_hits' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_automation_state(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'current_ip'        => array( 'type' => 'string', 'description' => __( 'Address of the current request, unmasked.', 'botblocker-security' ) ),
				'allowlisted'       => array( 'type' => 'boolean', 'description' => __( 'Whether the current address is covered by an active allow rule.', 'botblocker-security' ) ),
				'app_password_rest' => array( 'type' => 'boolean', 'description' => __( 'Whether application-password REST access is allowed.', 'botblocker-security' ) ),
				'abilities'         => array( 'type' => 'boolean', 'description' => __( 'Whether the WP Abilities master switch is on.', 'botblocker-security' ) ),
				'warning'           => array( 'type' => 'string', 'description' => __( 'Empty string, or one sentence describing the automation risk.', 'botblocker-security' ) ),
			),
			'required'             => array( 'current_ip', 'allowlisted', 'app_password_rest', 'abilities', 'warning' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_audit_row(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'             => array( 'type' => 'integer', 'description' => __( 'Audit row id.', 'botblocker-security' ) ),
				'created_at'     => array( 'type' => 'string', 'description' => __( 'ISO 8601 timestamp.', 'botblocker-security' ) ),
				'created_ts'     => array( 'type' => 'integer', 'description' => __( 'Unix timestamp of the event.', 'botblocker-security' ) ),
				'event_key'      => array( 'type' => 'string', 'description' => __( 'Stable event key, for example auth.login.failed.', 'botblocker-security' ) ),
				'severity'       => array( 'type' => 'string', 'description' => __( 'Severity level as a numeric string.', 'botblocker-security' ) ),
				'actor_user_id'  => array( 'type' => 'integer', 'description' => __( 'Actor user id; 0 when the actor is unknown.', 'botblocker-security' ) ),
				'actor_username' => array( 'type' => 'string', 'description' => __( 'Actor login, masked.', 'botblocker-security' ) ),
				'actor_role'     => array( 'type' => 'string', 'description' => __( 'Actor role slug.', 'botblocker-security' ) ),
				'object_type'    => array( 'type' => 'string', 'description' => __( 'Object type the event refers to.', 'botblocker-security' ) ),
				'object_id'      => array( 'type' => 'integer', 'description' => __( 'Object id; 0 when not applicable.', 'botblocker-security' ) ),
				'ip'             => array( 'type' => 'string', 'description' => __( 'Request address, masked.', 'botblocker-security' ) ),
				'context'        => array( 'type' => 'string', 'description' => __( 'Stored event context.', 'botblocker-security' ) ),
				'request_path'   => array( 'type' => 'string', 'description' => __( 'Request path, masked.', 'botblocker-security' ) ),
				'user_agent'     => array( 'type' => 'string', 'description' => __( 'User agent, masked.', 'botblocker-security' ) ),
				'data'           => array( 'type' => 'object', 'description' => __( 'Event payload; keys depend on event_key.', 'botblocker-security' ), 'additionalProperties' => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'null', 'array', 'object' ) ) ),
			),
			'required'             => array( 'id', 'created_at', 'created_ts', 'event_key', 'severity', 'actor_user_id', 'actor_username', 'actor_role', 'object_type', 'object_id', 'ip', 'context', 'request_path', 'user_agent', 'data' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_visitor_row( bool $event_extras = false ): array {
		$properties = array(
			'cid'       => array( 'type' => 'string', 'description' => __( 'Visitor event client id.', 'botblocker-security' ) ),
			'ip'        => array( 'type' => 'string', 'description' => __( 'Visitor address, masked.', 'botblocker-security' ) ),
			'ptr'       => array( 'type' => 'string', 'description' => __( 'Reverse-DNS name, masked.', 'botblocker-security' ) ),
			'date'      => array( 'type' => 'string', 'description' => __( 'ISO 8601 event timestamp.', 'botblocker-security' ) ),
			'country'   => array( 'type' => 'string', 'description' => __( 'Two-letter country code.', 'botblocker-security' ) ),
			'asnum'     => array( 'type' => 'string', 'description' => __( 'Autonomous system number.', 'botblocker-security' ) ),
			'asname'    => array( 'type' => 'string', 'description' => __( 'Autonomous system name.', 'botblocker-security' ) ),
			'browser'   => array( 'type' => 'string', 'description' => __( 'Detected browser family.', 'botblocker-security' ) ),
			'os'        => array( 'type' => 'string', 'description' => __( 'Detected operating system.', 'botblocker-security' ) ),
			'device'    => array( 'type' => 'string', 'description' => __( 'Detected device type.', 'botblocker-security' ) ),
			'method'    => array( 'type' => 'string', 'description' => __( 'HTTP method of the request.', 'botblocker-security' ) ),
			'page'      => array( 'type' => 'string', 'description' => __( 'Stored page URL without a query string.', 'botblocker-security' ) ),
			'referer'   => array( 'type' => 'string', 'description' => __( 'Referer, masked.', 'botblocker-security' ) ),
			'useragent' => array( 'type' => 'string', 'description' => __( 'User agent, masked.', 'botblocker-security' ) ),
			'result'    => array( 'type' => 'string', 'description' => __( 'Stored result text, truncated to 80 characters because it may contain a PTR.', 'botblocker-security' ) ),
			'passed'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Stored pass counter for the request.', 'botblocker-security' ) ),
			'proxy'     => array( 'type' => 'string', 'description' => __( 'Stored proxy flag.', 'botblocker-security' ) ),
			'tor'       => array( 'type' => 'string', 'description' => __( 'Stored Tor flag.', 'botblocker-security' ) ),
			'vpn'       => array( 'type' => 'string', 'description' => __( 'Stored VPN flag.', 'botblocker-security' ) ),
		);
		$required   = array_keys( $properties );
		if ( $event_extras ) {
			$properties['country_name'] = array( 'type' => 'string', 'description' => __( 'Country name as stored in the event row.', 'botblocker-security' ) );
			$properties['recaptcha']    = array( 'type' => 'string', 'description' => __( 'Stored CAPTCHA provider flag.', 'botblocker-security' ) );
			$properties['hosting']      = array( 'type' => 'boolean', 'description' => __( 'Whether the address belongs to a hosting provider.', 'botblocker-security' ) );
			$properties['hit']          = array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Stored hit counter for the event row.', 'botblocker-security' ) );
			$properties['peer_ip']      = array( 'type' => 'string', 'description' => __( 'Edge peer address, masked; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['edge_state']   = array( 'type' => 'string', 'description' => __( 'Edge state code; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['edge_header']  = array( 'type' => 'string', 'description' => __( 'Edge header name; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['edge_role']    = array( 'type' => 'string', 'description' => __( 'Edge role code; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['subject_source'] = array( 'type' => 'string', 'description' => __( 'Address subject source; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['verified_by']  = array( 'type' => 'string', 'description' => __( 'Edge verification method; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['edge_client']  = array( 'type' => 'string', 'description' => __( 'Edge client id, masked; present only when the access payload stores edge data.', 'botblocker-security' ) );
			$properties['xff_present']  = array( 'type' => 'boolean', 'description' => __( 'Whether an X-Forwarded-For header was present; present only when the access payload stores edge data.', 'botblocker-security' ) );
		}
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => $required,
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_cron_job(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'hook'       => array( 'type' => 'string', 'description' => __( 'Cron hook name.', 'botblocker-security' ) ),
				'timestamp'  => array( 'type' => 'integer', 'description' => __( 'Scheduled Unix timestamp.', 'botblocker-security' ) ),
				'next_run'   => array( 'type' => 'string', 'description' => __( 'ISO 8601 timestamp of the next run.', 'botblocker-security' ) ),
				'recurrence' => array( 'type' => 'string', 'description' => __( 'WordPress schedule name; empty when the event does not recur.', 'botblocker-security' ) ),
				'interval'   => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Schedule interval in seconds; 0 when the event does not recur.', 'botblocker-security' ) ),
				'args'       => array( 'type' => 'object', 'description' => __( 'Cron arguments; present only when args_exposed is true.', 'botblocker-security' ), 'additionalProperties' => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'null', 'array', 'object' ) ) ),
			),
			'required'             => array( 'hook', 'timestamp', 'next_run', 'recurrence', 'interval' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_db_table(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'name'       => array( 'type' => 'string', 'description' => __( 'Table name without the WordPress prefix, for example bbcs_hits.', 'botblocker-security' ) ),
				'exists'     => array( 'type' => 'boolean', 'description' => __( 'Whether the table exists.', 'botblocker-security' ) ),
				'rows'       => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Row count; 0 when the table is missing or unknown.', 'botblocker-security' ) ),
				'size_bytes' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Table size in bytes; 0 when unknown.', 'botblocker-security' ) ),
			),
			'required'             => array( 'name', 'exists', 'rows', 'size_bytes' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_addon_row(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'slug'          => array( 'type' => 'string', 'description' => __( 'Add-on directory slug.', 'botblocker-security' ) ),
				'name'          => array( 'type' => 'string', 'description' => __( 'Add-on display name from the manifest.', 'botblocker-security' ) ),
				'version'       => array( 'type' => 'string', 'description' => __( 'Add-on version from the manifest.', 'botblocker-security' ) ),
				'active'        => array( 'type' => 'boolean', 'description' => __( 'Whether the add-on is active.', 'botblocker-security' ) ),
				'compatible'    => array( 'type' => 'boolean', 'description' => __( 'Whether the add-on is compatible with the installed core and PHP.', 'botblocker-security' ) ),
				'requires_core' => array( 'type' => 'string', 'description' => __( 'Minimum core version required by the manifest.', 'botblocker-security' ) ),
				'features'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Feature slugs declared by the manifest.', 'botblocker-security' ) ),
				'abilities'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Ability names declared by the manifest.', 'botblocker-security' ) ),
			),
			'required'             => array( 'slug', 'name', 'version', 'active', 'compatible', 'requires_core', 'features', 'abilities' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_header_context(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'context' => array( 'type' => 'string', 'enum' => array( 'allow', 'block', 'challenge', 'not_found', 'rest', 'admin' ), 'description' => __( 'Request context the headers are computed for.', 'botblocker-security' ) ),
				'sent'    => array( 'type' => 'boolean', 'description' => __( 'Whether the add-on sends at least one header in this context.', 'botblocker-security' ) ),
				'note'    => array( 'type' => 'string', 'enum' => array( 'mu_layer_uniform', 'headers_disabled' ), 'description' => __( 'Why the context is uniform or disabled: mu_layer_uniform or headers_disabled.', 'botblocker-security' ) ),
			),
			'required'             => array( 'context', 'sent', 'note' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_trace_edge(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'peer'           => array( 'type' => 'string', 'description' => __( 'Peer address, masked.', 'botblocker-security' ) ),
				'client'         => array( 'type' => 'string', 'description' => __( 'Client address, masked.', 'botblocker-security' ) ),
				'role'           => array( 'type' => 'string', 'description' => __( 'Edge role code.', 'botblocker-security' ) ),
				'state'          => array( 'type' => 'string', 'description' => __( 'Edge state code.', 'botblocker-security' ) ),
				'header'         => array( 'type' => 'string', 'description' => __( 'Edge header name.', 'botblocker-security' ) ),
				'subject_source' => array( 'type' => 'string', 'description' => __( 'Address subject source.', 'botblocker-security' ) ),
				'verified_by'    => array( 'type' => 'string', 'description' => __( 'Edge verification method.', 'botblocker-security' ) ),
			),
			'required'             => array( 'peer', 'client', 'role', 'state', 'header', 'subject_source', 'verified_by' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_trace_signals(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'result' => array( 'type' => 'string', 'description' => __( 'Human-readable decision reason, truncated to 80 characters because it may contain a PTR.', 'botblocker-security' ) ),
				'geo'    => array( 'type' => 'string', 'description' => __( 'Geo signal text.', 'botblocker-security' ) ),
				'proxy'  => array( 'type' => 'string', 'description' => __( 'Proxy signal text.', 'botblocker-security' ) ),
				'asn'    => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Autonomous system number; 0 when unknown.', 'botblocker-security' ) ),
			),
			'required'             => array( 'result', 'geo', 'proxy', 'asn' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_rate_summary(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'individual_rpm'    => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Requests per minute for this address.', 'botblocker-security' ) ),
				'effective_block'   => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Block threshold applied to this request.', 'botblocker-security' ) ),
				'effective_captcha' => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'CAPTCHA threshold applied to this request.', 'botblocker-security' ) ),
				'subnet_pressure'   => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Subnet load as a fraction of its threshold.', 'botblocker-security' ) ),
			),
			'required'             => array( 'individual_rpm', 'effective_block', 'effective_captcha', 'subnet_pressure' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_trace_timings(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'generation' => array( 'type' => 'number', 'minimum' => 0, 'description' => __( 'Trace generation duration in seconds, as stored.', 'botblocker-security' ) ),
				'event_time' => array( 'type' => 'string', 'description' => __( 'ISO 8601 timestamp of the stored event.', 'botblocker-security' ) ),
			),
			'required'             => array( 'generation', 'event_time' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_ability_call_row(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'            => array( 'type' => 'integer', 'description' => __( 'Call log row id.', 'botblocker-security' ) ),
				'created_at'    => array( 'type' => 'string', 'description' => __( 'ISO 8601 timestamp.', 'botblocker-security' ) ),
				'ability'       => array( 'type' => 'string', 'description' => __( 'Ability name.', 'botblocker-security' ) ),
				'outcome'       => array( 'type' => 'string', 'enum' => array( 'success', 'error', 'denied' ), 'description' => __( 'Call outcome.', 'botblocker-security' ) ),
				'error'         => array( 'type' => 'string', 'description' => __( 'Error code; empty on success.', 'botblocker-security' ) ),
				'duration_ms'   => array( 'type' => 'integer', 'description' => __( 'Call duration in milliseconds.', 'botblocker-security' ) ),
				'actor_user_id' => array( 'type' => 'integer', 'description' => __( 'Actor user id; 0 when unknown.', 'botblocker-security' ) ),
				'ip'            => array( 'type' => 'string', 'description' => __( 'Request address, masked.', 'botblocker-security' ) ),
				'masked'        => array( 'type' => 'boolean', 'description' => __( 'Always true; masking is always applied and cannot be disabled.', 'botblocker-security' ) ),
			),
			'required'             => array( 'id', 'created_at', 'ability', 'outcome', 'error', 'duration_ms', 'actor_user_id', 'ip', 'masked' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_threat_status(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'state'           => array( 'type' => 'string', 'description' => __( 'Source state code.', 'botblocker-security' ) ),
				'version'         => array( 'type' => 'string', 'description' => __( 'Threat data version.', 'botblocker-security' ) ),
				'database_type'   => array( 'type' => 'string', 'description' => __( 'Database type reported by the source.', 'botblocker-security' ) ),
				'build_epoch'     => array( 'type' => 'integer', 'description' => __( 'Build time as a Unix timestamp.', 'botblocker-security' ) ),
				'node_count'      => array( 'type' => 'integer', 'description' => __( 'Number of nodes in the threat database.', 'botblocker-security' ) ),
				'sha256'          => array( 'type' => 'string', 'description' => __( 'SHA-256 of the downloaded data.', 'botblocker-security' ) ),
				'size'            => array( 'type' => 'integer', 'description' => __( 'Downloaded size in bytes.', 'botblocker-security' ) ),
				'downloaded_at'   => array( 'type' => 'integer', 'description' => __( 'Download time as a Unix timestamp; 0 when never downloaded.', 'botblocker-security' ) ),
				'last_attempt_at' => array( 'type' => 'integer', 'description' => __( 'Last update attempt as a Unix timestamp.', 'botblocker-security' ) ),
				'last_error'      => array( 'type' => 'string', 'description' => __( 'Last error text; empty when the last attempt succeeded.', 'botblocker-security' ) ),
				'source_url'      => array( 'type' => 'string', 'description' => __( 'Source URL used for the download.', 'botblocker-security' ) ),
				'failures'        => array( 'type' => 'integer', 'description' => __( 'Consecutive failure count.', 'botblocker-security' ) ),
				'last_reason'     => array( 'type' => 'string', 'description' => __( 'Last failure reason code.', 'botblocker-security' ) ),
				'hash'            => array( 'type' => 'string', 'description' => __( 'Stored data hash.', 'botblocker-security' ) ),
				'generated_at'    => array( 'type' => 'integer', 'description' => __( 'Generation time as a Unix timestamp.', 'botblocker-security' ) ),
				'provider_count'  => array( 'type' => 'integer', 'description' => __( 'Number of providers in the local database.', 'botblocker-security' ) ),
				'range_count'     => array( 'type' => 'integer', 'description' => __( 'Number of ranges in the local database.', 'botblocker-security' ) ),
				'fingerprint_count' => array( 'type' => 'integer', 'description' => __( 'Number of TLS fingerprints in the local database.', 'botblocker-security' ) ),
				'last_sync'       => array( 'type' => 'integer', 'description' => __( 'Last sync time as a Unix timestamp.', 'botblocker-security' ) ),
				'last_attempt'    => array( 'type' => 'integer', 'description' => __( 'Last attempt time as a Unix timestamp.', 'botblocker-security' ) ),
			),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_schema_status_core(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'protection_enabled' => array( 'type' => 'boolean', 'description' => __( 'Whether BotBlocker protection is on.', 'botblocker-security' ) ),
				'secure_mode'        => array( 'type' => 'integer', 'enum' => array( 0, 1, 2 ), 'description' => __( 'Secure mode: 0 when settings are unavailable, 1 frontend, 2 full.', 'botblocker-security' ) ),
				'secure_mode_label'  => array( 'type' => 'string', 'description' => __( 'Human-readable secure mode label; do not parse.', 'botblocker-security' ) ),
				'captcha_mode'       => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Active CAPTCHA mode id.', 'botblocker-security' ) ),
				'captcha_mode_label' => array( 'type' => 'string', 'description' => __( 'Human-readable CAPTCHA mode label; do not parse.', 'botblocker-security' ) ),
			),
			'required'             => array( 'protection_enabled', 'secure_mode', 'secure_mode_label', 'captcha_mode', 'captcha_mode_label' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_confirm_tokens(): array {
		return array(
			'botblocker/block-ip'                     => array( 'BLOCK' ),
			'botblocker/unblock-ip'                   => array( 'UNBLOCK' ),
			'botblocker/upsert-ip-rule'               => array( 'UPSERT' ),
			'botblocker/delete-ip-rule'               => array( 'DELETE' ),
			'botblocker/set-protection'               => array( 'ENABLE', 'DISABLE' ),
			'botblocker/set-secure-mode'              => array( 'FRONTEND', 'FULL' ),
			'botblocker/set-captcha-mode'             => array( 'CAPTCHA' ),
			'botblocker/clear-cache'                  => array( 'CLEAR' ),
			'botblocker/remove-edge-trust'            => array( 'REMOVE' ),
			'botblocker/load-license-from-cloud'      => array( 'LOAD' ),
			'botblocker/delete-ip-rules'              => array( 'DELETE' ),
			'botblocker/set-country-rule'             => array( 'COUNTRY' ),
			'botblocker/set-asn-rule'                 => array( 'ASN' ),
			'botblocker/update-threat-data'           => array( 'UPDATE' ),
			'botblocker/apply-preset'                 => array( 'PRESET' ),
			'botblocker/set-setting'                  => array( 'SET' ),
			'botblocker/reset-rate-limit'             => array( 'RESET' ),
			'botblocker/reset-ptr-cache'              => array( 'RESET' ),
			'botblocker/seed-test-rule'               => array( 'SEED' ),
			'botblocker/clear-test-rules'             => array( 'CLEAR' ),
			'botblocker/set-security-headers-domains' => array( 'DOMAINS' ),
			'botblocker/run-malware-scan'             => array( 'SCAN' ),
			'botblocker/purge-url-cache'              => array( 'PURGE' ),
		);
	}

	function bbcs_ability_confirm_token( string $ability ): string {
		$tokens = bbcs_ability_confirm_tokens();
		return isset( $tokens[ $ability ][0] ) ? (string) $tokens[ $ability ][0] : '';
	}

	function bbcs_ability_export_groups(): array {
		return array( 'protection', 'captcha', 'logging', 'integrations', 'all' );
	}

	function bbcs_ability_secret_setting_keys(): array {
		return array(
			'recaptcha_key2',
			'recaptcha_secret2',
			'recaptcha_key3',
			'recaptcha_secret3',
			'recaptcha_secret',
			'cloud_api_key',
			'cloud_api_pass',
			'cloud_api_secret',
			'bbcs_antispam_api_key',
			'secret_botblocker_get_param',
			'redis_password',
			'salt',
		);
	}

	function bbcs_ability_non_secret_setting_keys(): array {
		return array(
			'allow_app_password_rest',
			'session_token_enabled',
			'vary_cookie',
			'cookie',
			'cookie_lifetime',
			'early_cache_pass_ttl',
		);
	}

	function bbcs_ability_setting_is_secret( string $key ): bool {
		if ( 0 === strpos( $key, 'bbcs_ability_' ) ) {
			return false;
		}
		if ( in_array( $key, bbcs_ability_non_secret_setting_keys(), true ) ) {
			return false;
		}
		if ( in_array( $key, bbcs_ability_secret_setting_keys(), true ) ) {
			return true;
		}
		return class_exists( 'BotBlockerAuditLogger' ) && BotBlockerAuditLogger::isSensitiveKey( $key );
	}

	function bbcs_ability_audit_row_schema(): array {
		return bbcs_ability_schema_audit_row();
	}

	function bbcs_ability_visitor_row_schema(): array {
		return bbcs_ability_schema_visitor_row();
	}

	function bbcs_ability_user_risk_row_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'user_id'           => array( 'type' => 'integer', 'description' => __( 'WordPress user id.', 'botblocker-security' ) ),
				'login'             => array( 'type' => 'string', 'description' => __( 'User login, masked.', 'botblocker-security' ) ),
				'email'             => array( 'type' => 'string', 'description' => __( 'User email, masked.', 'botblocker-security' ) ),
				'roles'             => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => __( 'Role slugs assigned to the user.', 'botblocker-security' ) ),
				'registered'        => array( 'type' => 'string', 'description' => __( 'Registration date, ISO 8601.', 'botblocker-security' ) ),
				'2fa_enabled'       => array( 'type' => 'boolean', 'description' => __( 'True when _2fa_secret and _2fa_verified are both present.', 'botblocker-security' ) ),
				'2fa_pending'       => array( 'type' => 'boolean', 'description' => __( 'True when a secret exists without _2fa_verified, or when _2fa_secret_temp exists.', 'botblocker-security' ) ),
				'failed_logins_30d' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Failed logins recorded for the user in the last 30 days.', 'botblocker-security' ) ),
			),
			'required'             => array( 'user_id', 'login', 'email', 'roles', 'registered', '2fa_enabled', '2fa_pending', 'failed_logins_30d' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_top_item_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'key'   => array( 'type' => 'string', 'description' => __( 'Aggregated value: country code, ASN, user-agent family, status or path.', 'botblocker-security' ) ),
				'count' => array( 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Rows aggregated under this key.', 'botblocker-security' ) ),
			),
			'required'             => array( 'key', 'count' ),
			'additionalProperties' => false,
		);
	}

	function bbcs_ability_setting_is_redacted( string $key ): bool {
		return bbcs_ability_setting_is_secret( $key ) || 'cloud_api_email' === $key;
	}

	function bbcs_ability_enrich_definition( array $definition ): array {
		$name = isset( $definition['name'] ) ? (string) $definition['name'] : '';

		$input = isset( $definition['input_schema'] ) && is_array( $definition['input_schema'] ) ? $definition['input_schema'] : array();
		if ( isset( $input['default'] ) && is_array( $input['default'] ) && array() === $input['default'] ) {
			unset( $input['default'] );
		}
		if ( isset( $input['properties'] ) && is_array( $input['properties'] ) && array() === $input['properties'] ) {
			unset( $input['properties'] );
		}
		if ( isset( $input['properties'] ) && is_array( $input['properties'] ) ) {
			foreach ( $input['properties'] as $bbcs_param => $bbcs_prop ) {
				if ( ! is_array( $bbcs_prop ) ) {
					continue;
				}
				if ( 'botblocker/set-setting' === $name && 'key' === $bbcs_param && function_exists( 'bbcs_wp_ability_setting_switch_keys' ) ) {
					$bbcs_prop['enum'] = array_values( bbcs_wp_ability_setting_switch_keys() );
				}
				if ( 'botblocker/export-settings' === $name && 'group' === $bbcs_param ) {
					$bbcs_prop['enum'] = bbcs_ability_export_groups();
				}
				$input['properties'][ $bbcs_param ] = $bbcs_prop;
			}
		}
		$bbcs_confirm_tokens = bbcs_ability_confirm_tokens();
		if ( isset( $bbcs_confirm_tokens[ $name ] ) && isset( $input['properties']['confirm'] ) ) {
			$bbcs_tokens = array_values( array_map( 'strval', (array) $bbcs_confirm_tokens[ $name ] ) );
			$bbcs_confirm = array(
				'type' => 'string',
				'enum' => $bbcs_tokens,
			);
			if ( ! empty( $input['properties']['confirm']['description'] ) ) {
				$bbcs_confirm['description'] = $input['properties']['confirm']['description'];
			}
			$input['properties']['confirm'] = $bbcs_confirm;
			$bbcs_required = isset( $input['required'] ) && is_array( $input['required'] ) ? $input['required'] : array();
			if ( ! in_array( 'confirm', $bbcs_required, true ) ) {
				$bbcs_required[] = 'confirm';
			}
			$input['required'] = $bbcs_required;
		}
		$definition['input_schema'] = $input;

		return $definition;
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

	/**
	 * HTTP status for a BotBlocker ability error code. WP core falls back to
	 * 500 for every WP_Error without `data.status`, so map the contract here.
	 */
	function bbcs_ability_error_status( string $code ): int {
		$map = array(
			'ability_invalid_input'                 => 400,
			'ability_confirmation_required'         => 400,
			'ability_forbidden'                     => 403,
			'ability_not_found'                     => 404,
			'ability_readonly_rule'                 => 409,
			'ability_allow_downgrade_requires_force' => 409,
			'ability_self_block_requires_force'     => 409,
			'ability_ambiguous_rule_id'             => 409,
			'ability_mode_unavailable'              => 409,
			'ability_unavailable'                   => 409,
			'ability_rate_limited'                  => 429,
			'ability_execution_failed'              => 500,
			'ability_backend_unavailable'           => 503,
			'rest_forbidden'                        => 403,
			'rest_not_logged_in'                    => 401,
		);
		$status = isset( $map[ $code ] ) ? $map[ $code ] : 500;
		return (int) apply_filters( 'bbcs_ability_error_status', $status, $code );
	}

	function bbcs_ability_error_with_status( WP_Error $error ): WP_Error {
		$data = $error->get_error_data();
		if ( is_array( $data ) && isset( $data['status'] ) ) {
			return $error;
		}
		$code            = (string) $error->get_error_code();
		$status          = bbcs_ability_error_status( $code );
		$data            = is_array( $data ) ? $data : array();
		$data['status']  = $status;
		$message         = (string) $error->get_error_message();
		if ( '' !== $code && false === strpos( $message, '[' . $code . ']' ) ) {
			$message .= ' [' . $code . ']';
		}
		return new WP_Error( $code, $message, $data );
	}

	function bbcs_ability_public_flag( array $definition ): bool {		// PII_OFF lifts both the PII floor and the destructive default.
		if ( defined( 'PII_OFF' ) && PII_OFF ) {
			return true;
		}
		// An explicit `public` key is an intentional opt-in (e.g. a read-only
		// export that masks PII and redacts secrets); it outranks the PII floor.
		if ( array_key_exists( 'public', $definition ) ) {
			return (bool) $definition['public'];
		}
		if ( ! empty( $definition['pii'] ) ) {
			return false;
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

	/**
	 * No-argument ability input schema.
	 *
	 * A plain `{type:object}` schema rejects a missing GET input (null), which
	 * makes read-only abilities unreachable over the REST run endpoint. The
	 * nullable object accepts both a missing input and an explicit `{}` while
	 * still refusing every non-empty payload.
	 */
	function bbcs_normalize_ability_input_schema( array $schema ): array {
		if ( ! isset( $schema['type'] ) || 'object' !== $schema['type'] ) {
			return $schema;
		}
		if ( ! empty( $schema['properties'] ) || ! empty( $schema['required'] ) ) {
			return $schema;
		}
		if ( isset( $schema['additionalProperties'] ) && false !== $schema['additionalProperties'] ) {
			return $schema;
		}
		return array(
			'type'                 => array( 'object', 'null' ),
			'additionalProperties' => false,
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
				if ( is_wp_error( $bbcs_result ) ) {
					$bbcs_result = bbcs_ability_error_with_status( $bbcs_result );
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
					'input_schema'        => bbcs_normalize_ability_input_schema( isset( $definition['input_schema'] ) && is_array( $definition['input_schema'] ) ? $definition['input_schema'] : array() ),
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
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_BUTTON, 'slug' => 'button', 'label' => __( 'Button', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS, 'slug' => 'color_buttons', 'label' => __( 'Color Buttons', 'botblocker-security' ), 'available' => $gd, 'reason' => $gd ? '' : 'gd_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_IMAGE, 'slug' => 'image', 'label' => __( 'Image', 'botblocker-security' ), 'available' => $gd, 'reason' => $gd ? '' : 'gd_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON, 'slug' => 'recaptcha_v2_button', 'label' => __( 'reCAPTCHA v2 Button', 'botblocker-security' ), 'available' => $keys, 'reason' => $keys ? '' : 'keys_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2, 'slug' => 'recaptcha_v2', 'label' => __( 'reCAPTCHA v2', 'botblocker-security' ), 'available' => $keys, 'reason' => $keys ? '' : 'keys_required' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_SHAPE, 'slug' => 'shape', 'label' => __( 'Shape', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_DIGIT, 'slug' => 'digit', 'label' => __( 'Digit', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_HOLD, 'slug' => 'hold', 'label' => __( 'Hold Button', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
			array( 'mode' => BOTBLOCKER_CAPTCHA_MODE_SILENT, 'slug' => 'silent', 'label' => __( 'Silent', 'botblocker-security' ), 'available' => true, 'reason' => '' ),
		);

		if ( class_exists( 'BotBlockerCaptchaRegistry' ) ) {
			foreach ( BotBlockerCaptchaRegistry::allModes() as $id => $mode ) {
				$modes[] = array(
					'mode'      => (int) $id,
					'slug'      => (string) ( $mode['slug'] ?? '' ),
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

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = bbcs_wp_ability_rule_row_output( $row, $family );
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
		if ( ! is_array( $row ) ) {
			return array();
		}
		$row['family'] = 6 === $version ? 'ipv6' : 'ipv4';

		return $row;
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

	function bbcs_wp_ability_find_rule_by_id( int $id, string $family = '' ): array {
		global $wpdb;
		$tables = array( 'ipv4' => $wpdb->bbcs_ipv4rules, 'ipv6' => $wpdb->bbcs_ipv6rules );
		if ( 'ipv4' === $family ) {
			$tables = array( 'ipv4' => $wpdb->bbcs_ipv4rules );
		} elseif ( 'ipv6' === $family ) {
			$tables = array( 'ipv6' => $wpdb->bbcs_ipv6rules );
		}
		foreach ( $tables as $fam => $table ) {
			if ( empty( $table ) ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb; values prepared.
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `search`, `rule`, `priority`, `comment`, `expires`, `readonly` FROM `{$table}` WHERE `id` = %d LIMIT 1", $id ), ARRAY_A );
			if ( is_array( $row ) ) {
				$row['family'] = $fam;
				$row['table']  = $table;
				return $row;
			}
		}
		return array();
	}

	function bbcs_wp_ability_rule_id_families( int $id ): array {
		$families = array();
		foreach ( array( 'ipv4', 'ipv6' ) as $family ) {
			if ( ! empty( bbcs_wp_ability_find_rule_by_id( $id, $family ) ) ) {
				$families[] = $family;
			}
		}
		return $families;
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
		$expires = (int) $row['expires'];
		return array(
			'id'       => (int) $row['id'],
			'family'   => $family,
			'rule'     => (string) $row['rule'],
			'value'    => (string) $row['search'],
			'priority' => (int) $row['priority'],
			'expires'  => $expires,
			'expired'  => $expires > 0 && $expires <= time(),
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
			'protection_enabled' => $enabled,
			'secure_mode'        => $secure,
			'secure_mode_label'  => 2 === $secure ? __( 'Full', 'botblocker-security' ) : __( 'Frontend', 'botblocker-security' ),
			'captcha_mode'       => $mode,
			'captcha_mode_label' => bbcs_wp_ability_mode_label( $mode ),
			'version'            => defined( 'BOTBLOCKER_VERSION' ) ? (string) BOTBLOCKER_VERSION : '',
			'contract_version'   => defined( 'MCP_CONTRACT_VERSION' ) ? (string) MCP_CONTRACT_VERSION : '2',
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
			'deciding_rule' => empty( $deciding ) ? null : bbcs_wp_ability_rule_row_output( $deciding, (string) ( $deciding['family'] ?? '' ) ),
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
			$raw = is_scalar( $candidate ) ? trim( (string) $candidate ) : '';
			$ip  = bbcs_wp_ability_input_ip( array( 'ip' => $raw ) );
			if ( is_wp_error( $ip ) ) {
				$results[] = array(
					'ip'            => $raw,
					'valid'         => false,
					'blocked'       => false,
					'reason'        => '',
					'source'        => '',
					'matched_rules' => 0,
					'error'         => 'invalid_ip',
				);
				continue;
			}
			$result = bbcs_wp_ability_check_ip_result( $ip );
			unset( $result['deciding_rule'] );
			$result['valid'] = true;
			$result['error'] = '';
			$results[]       = $result;
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
		$rules = bbcs_wp_ability_lookup_ip_rules( $ip );
		return array( 'rules' => $rules, 'total' => count( $rules ) );
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
				$where[]  = '( `search` LIKE %s OR `comment` LIKE %s )';
				$params[] = '%' . $wpdb->esc_like( $search ) . '%';
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
					if ( (int) $a['id'] === (int) $b['id'] ) {
						return strcmp( (string) $a['family'], (string) $b['family'] );
					}
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

		$rules = $result['rules'];

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
			'period'     => $period,
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

		$values = array();
		$redacted = array();
		if ( 'all' === $group ) {
			$settings = BotBlocker::getInstance()->settings;
			$all      = is_object( $settings ) ? get_object_vars( $settings ) : array();
			foreach ( $all as $key => $value ) {
				if ( bbcs_ability_setting_is_redacted( (string) $key ) ) {
					$redacted[ (string) $key ] = is_string( $value ) ? '' !== $value : ! empty( $value );
					continue;
				}
				if ( null === $value || '' === $value ) {
					continue;
				}
				$values[ $key ] = $value;
			}
		} elseif ( isset( $map[ $group ] ) ) {
			foreach ( $map[ $group ] as $key ) {
				$value = bbcs_wp_ability_read_setting( $key, null );
				if ( bbcs_ability_setting_is_redacted( (string) $key ) ) {
					$redacted[ (string) $key ] = is_string( $value ) ? '' !== $value : ! empty( $value );
					continue;
				}
				if ( null === $value || '' === $value ) {
					continue;
				}
				$values[ $key ] = $value;
			}
		} else {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported settings group.', 'botblocker-security' ) );
		}

		foreach ( bbcs_ability_secret_setting_keys() as $key ) {
			$value                = bbcs_wp_ability_read_setting( $key, '' );
			$redacted[ $key ]       = is_string( $value ) ? '' !== $value : ! empty( $value );
		}
		$email = bbcs_wp_ability_read_setting( 'cloud_api_email', '' );
		$redacted['cloud_api_email'] = is_string( $email ) ? '' !== $email : ! empty( $email );

		return array(
			'group'    => $group,
			'values'   => $values,
			'redacted' => $redacted,
			'writable' => function_exists( 'bbcs_wp_ability_setting_switch_keys' ) ? array_values( bbcs_wp_ability_setting_switch_keys() ) : array(),
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

		$guard = bbcs_wp_ability_guard( 'botblocker/block-ip', $input, bbcs_ability_confirm_token( 'botblocker/block-ip' ), true );
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

		$expires = bbcs_wp_ability_resolve_expires( $input, BOTBLOCKER_EXP_INF );
		if ( is_wp_error( $expires ) ) {
			return $expires;
		}

		$force   = bbcs_wp_ability_input_bool( $input, 'force', false );
		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		$value   = bbcs_wp_ability_encode_value( $ip );

		$existing          = bbcs_wp_ability_find_rule( $table, $ip );
		$previous_rule     = is_array( $existing ) ? (string) $existing['rule'] : '';
		$existing_readonly = is_array( $existing ) && (int) $existing['readonly'] === 1;

		if ( $reason === '' ) {
			$reason = is_array( $existing ) ? (string) $existing['comment'] : '';
		}
		if ( $reason === '' ) {
			$reason = __( 'Blocked via WP Abilities', 'botblocker-security' );
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

		if ( $existing_readonly ) {
			return new WP_Error( 'ability_readonly_rule', __( 'The existing rule is readonly and cannot be changed.', 'botblocker-security' ) );
		}
		if ( BBCS_RULE_ALLOW === $previous_rule && ! $force ) {
			return new WP_Error( 'ability_allow_downgrade_requires_force', __( 'Blocking an allowed IP requires force.', 'botblocker-security' ) );
		}
		if ( ! $force && bbcs_wp_ability_self_block( $ip ) ) {
			return new WP_Error( 'ability_self_block_requires_force', __( 'Blocking the current or server IP requires force.', 'botblocker-security' ) );
		}

		$created = ! is_array( $existing );
		$id      = is_array( $existing ) ? (int) $existing['id'] : 0;
		$changed = $created
			|| $previous_rule !== BBCS_RULE_BLOCK
			|| (int) ( $existing['expires'] ?? 0 ) !== (int) $expires
			|| (string) ( $existing['comment'] ?? '' ) !== $reason;

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
			'created'            => $created && ! $dry_run,
			'id'                 => $dry_run && $created ? 0 : $id,
			'previous_rule'      => $previous_rule,
			'changed'            => $changed,
			'applied'            => ! $dry_run,
			'dry_run'            => $dry_run,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_unblock_ip( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/unblock-ip', $input, bbcs_ability_confirm_token( 'botblocker/unblock-ip' ), true );
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
				'deleted'            => 0,
				'previous_rules'     => $previous_rules,
				'changed'            => ! empty( $deletable_ids ),
				'applied'            => false,
				'dry_run'            => true,
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
			'deleted'            => $deleted,
			'previous_rules'     => $previous_rules,
			'changed'            => ! empty( $deletable_ids ),
			'applied'            => true,
			'dry_run'            => false,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_upsert_ip_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/upsert-ip-rule', $input, bbcs_ability_confirm_token( 'botblocker/upsert-ip-rule' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$target = bbcs_wp_ability_input_target( $input );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$rule = ( is_array( $input ) && isset( $input['rule'] ) ) ? (string) $input['rule'] : '';
		if ( ! in_array( $rule, array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_GRAY, BBCS_RULE_DARK ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported rule type.', 'botblocker-security' ) );
		}

		$table = bbcs_wp_ability_rule_table( (int) $target['version'] );
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}

		$force    = bbcs_wp_ability_input_bool( $input, 'force', false );
		$dry_run  = bbcs_wp_ability_is_dry_run( $input );
		$existing = bbcs_wp_ability_find_rule( $table, (string) $target['search'] );
		$previous = is_array( $existing ) ? (string) $existing['rule'] : '';
		$readonly = is_array( $existing ) && (int) $existing['readonly'] === 1;
		$created  = ! is_array( $existing );

		$has_expiry = is_array( $input ) && ( array_key_exists( 'expires', $input ) || array_key_exists( 'expires_in', $input ) );
		if ( $has_expiry ) {
			$expires = bbcs_wp_ability_resolve_expires( $input, BOTBLOCKER_EXP_INF );
		} else {
			$expires = is_array( $existing ) ? (int) $existing['expires'] : BOTBLOCKER_EXP_INF;
		}
		if ( is_wp_error( $expires ) ) {
			return $expires;
		}

		$priority = ( is_array( $input ) && array_key_exists( 'priority', $input ) )
			? bbcs_wp_ability_input_int( $input, 'priority', 100 )
			: ( is_array( $existing ) ? (int) $existing['priority'] : 100 );
		$priority = max( 0, min( 100000, $priority ) );
		$comment  = ( is_array( $input ) && array_key_exists( 'comment', $input ) && is_scalar( $input['comment'] ) )
			? sanitize_text_field( (string) $input['comment'] )
			: ( is_array( $existing ) ? (string) $existing['comment'] : '' );

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
			'priority' => $priority,
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
		$changed = $created
			|| $previous !== $rule
			|| (int) ( $existing['expires'] ?? 0 ) !== (int) $expires
			|| (int) ( $existing['priority'] ?? 0 ) !== $priority
			|| (string) ( $existing['comment'] ?? '' ) !== $comment;

		if ( $dry_run ) {
			return array(
				'created'            => false,
				'id'                 => $created ? 0 : $id,
				'previous_rule'      => $previous,
				'changed'            => $changed,
				'applied'            => false,
				'dry_run'            => true,
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
			'created'            => $created,
			'id'                 => $id,
			'previous_rule'      => $previous,
			'changed'            => $changed,
			'applied'            => true,
			'dry_run'            => false,
			'family'             => (string) $target['family'],
			'expires'            => (int) $expires,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_delete_ip_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/delete-ip-rule', $input, bbcs_ability_confirm_token( 'botblocker/delete-ip-rule' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$id = bbcs_wp_ability_input_int( $input, 'id', 0 );
		if ( $id <= 0 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A rule id is required.', 'botblocker-security' ) );
		}
		$family = '';
		if ( is_array( $input ) && isset( $input['family'] ) && is_scalar( $input['family'] ) ) {
			$family = sanitize_key( (string) $input['family'] );
		}
		if ( '' !== $family && ! in_array( $family, array( 'ipv4', 'ipv6' ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The family must be ipv4 or ipv6.', 'botblocker-security' ) );
		}
		$families = bbcs_wp_ability_rule_id_families( $id );
		if ( empty( $families ) ) {
			return new WP_Error( 'ability_not_found', __( 'The rule was not found.', 'botblocker-security' ) );
		}
		if ( '' === $family && count( $families ) > 1 ) {
			return new WP_Error( 'ability_ambiguous_rule_id', __( 'This rule id exists in both address families. Pass family ipv4 or ipv6.', 'botblocker-security' ) );
		}
		if ( '' === $family ) {
			$family = $families[0];
		}

		$row = bbcs_wp_ability_find_rule_by_id( $id, $family );
		if ( empty( $row ) ) {
			return new WP_Error( 'ability_not_found', __( 'The rule was not found.', 'botblocker-security' ) );
		}
		if ( (int) $row['readonly'] === 1 ) {
			return new WP_Error( 'ability_readonly_rule', __( 'Readonly rules cannot be deleted.', 'botblocker-security' ) );
		}

		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		if ( $dry_run ) {
			return array(
				'deleted'            => 0,
				'id'                 => $id,
				'rule'               => (string) $row['rule'],
				'family'             => (string) $row['family'],
				'changed'            => true,
				'applied'            => false,
				'dry_run'            => true,
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
			'deleted'            => 1,
			'id'                 => $id,
			'rule'               => (string) $row['rule'],
			'family'             => (string) $row['family'],
			'changed'            => true,
			'applied'            => true,
			'dry_run'            => false,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_set_protection( $input = null ) {
		if ( ! is_array( $input ) || ! array_key_exists( 'enabled', $input ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The enabled flag is required.', 'botblocker-security' ) );
		}

		$enabled = bbcs_wp_ability_input_bool( $input, 'enabled', false );
		$bbcs_confirm_tokens = bbcs_ability_confirm_tokens();
		$bbcs_tokens = isset( $bbcs_confirm_tokens['botblocker/set-protection'] ) ? $bbcs_confirm_tokens['botblocker/set-protection'] : array( 'ENABLE', 'DISABLE' );
		$confirm = $enabled ? (string) $bbcs_tokens[0] : (string) $bbcs_tokens[1];
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
			'dry_run'            => $dry_run,
			'protection_enabled' => $dry_run ? $previous : $enabled,
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
			'dry_run'            => $dry_run,
			'warnings'           => $warnings,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_set_captcha_mode( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/set-captcha-mode', $input, bbcs_ability_confirm_token( 'botblocker/set-captcha-mode' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
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
			'mode'                  => (int) $mode,
			'mode_label'            => bbcs_wp_ability_mode_label( (int) $mode ),
			'previous'              => $previous,
			'wait_seconds'          => $wait,
			'previous_wait_seconds' => $previous_wait,
			'changed'               => $changed,
			'applied'               => $changed && ! $dry_run,
			'dry_run'               => $dry_run,
			'warnings'              => array(),
			'protection_enabled'    => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_clear_cache( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/clear-cache', $input, bbcs_ability_confirm_token( 'botblocker/clear-cache' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		$changed = BotBlockerCache::fileCacheCount() > 0;
		if ( $dry_run ) {
			bbcs_wp_ability_audit( 'botblocker/clear-cache', 'cache_dry_run', array( 'changed' => $changed ) );
			return array(
				'changed' => $changed,
				'applied' => false,
				'dry_run' => true,
			);
		}

		$buckets = BotBlockerCache::clearFileCacheBuckets();
		$cleared = BotBlockerCache::clearFileCache();
		BotBlockerCache::flush();
		bbcs_wp_ability_audit( 'botblocker/clear-cache', 'cache_cleared', array( 'cleared' => (bool) $cleared ) );

		return array(
			'changed' => $changed,
			'applied' => (bool) $buckets && (bool) $cleared,
			'dry_run' => false,
		);
	}

	require_once __DIR__ . '/class-botblocker-ability-registry.php';
	require_once __DIR__ . '/inc-botblocker-wp-abilities-extended.php';

	add_action( 'wp_abilities_api_categories_init', 'bbcs_register_wp_ability_categories' );
	add_action( 'wp_abilities_api_init', 'bbcs_register_wp_abilities' );
	add_action( 'wp_ability_invoked', 'bbcs_wp_ability_audit_log', 10, 3 );
	add_filter( 'wp_get_abilities_item_include', 'bbcs_wp_ability_filter_visibility', 10, 2 );
}
