<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Base;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="wp-abilities"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/wordpress.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Expose BotBlocker to the WordPress Abilities API. When disabled, nothing is registered — the abilities do not exist for REST, MCP or AI clients.', 'botblocker-security'); ?></div>
			</div>
			<div class="bbcs-ability-bulk">
				<div class="bbcs-ability-bulk-head">
					<span class="bbcs-ability-bulk-title"><?php esc_html_e('Group switches', 'botblocker-security'); ?></span>
					<?php echo Base::tooltip( __( 'Sets several switches at once. Nothing is saved until you press Save.', 'botblocker-security' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-ability-bulk-group" role="group" aria-label="<?php echo esc_attr__( 'Ability group switches', 'botblocker-security' ); ?>">
					<button type="button" class="bbcs-ability-bulk-btn" data-bbcs-abilities-bulk="enable-all" title="<?php echo esc_attr__( 'Turn the master switch on and every BotBlocker ability on.', 'botblocker-security' ); ?>">
						<svg class="bbcs-ico bbcs-ico--sm" aria-hidden="true"><use href="#bbcs-i-check"></use></svg>
						<span class="bbcs-ability-bulk-label"><?php esc_html_e( 'Enable all', 'botblocker-security' ); ?></span>
					</button>
					<button type="button" class="bbcs-ability-bulk-btn" data-bbcs-abilities-bulk="read-only" title="<?php echo esc_attr__( 'Keep the master switch on and enable read-only abilities only. Clients can ask questions and cannot change protection.', 'botblocker-security' ); ?>">
						<svg class="bbcs-ico bbcs-ico--sm" aria-hidden="true"><use href="#bbcs-i-eye"></use></svg>
						<span class="bbcs-ability-bulk-label"><?php esc_html_e( 'Read-only', 'botblocker-security' ); ?></span>
					</button>
					<button type="button" class="bbcs-ability-bulk-btn bbcs-ability-bulk-btn--off" data-bbcs-abilities-bulk="disable-all" title="<?php echo esc_attr__( 'Turn the master switch off and every BotBlocker ability off.', 'botblocker-security' ); ?>">
						<svg class="bbcs-ico bbcs-ico--sm" aria-hidden="true"><use href="#bbcs-i-x"></use></svg>
						<span class="bbcs-ability-bulk-label"><?php esc_html_e( 'Disable all', 'botblocker-security' ); ?></span>
					</button>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-field-pair">
				<div>
					<div class="bbcs-setgroup">
						<div class="bbcs-setgroup-head"><?php esc_html_e('WP Abilities API', 'botblocker-security'); ?></div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_wp_abilities_enabled', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_wp_abilities_enabled', '1') ? 'true' : 'false'; ?>" data-field="bbcs_wp_abilities_enabled"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_wp_abilities_enabled" value="<?php echo $data->is_checked('bbcs_wp_abilities_enabled', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable BotBlocker abilities', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'Master switch. When off, no BotBlocker ability is registered at all — it is invisible in the ability list, REST, MCP and run endpoints.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_wp_abilities_enabled' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
					</div>
					<div class="bbcs-setgroup" data-bbcs-ability-scope="read">
						<div class="bbcs-setgroup-head"><?php esc_html_e('Read-only', 'botblocker-security'); ?></div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_firewall_status', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_firewall_status', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_firewall_status"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_firewall_status" value="<?php echo $data->is_checked('bbcs_ability_get_firewall_status', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get firewall status', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-firewall-status — returns the protection mode, enabled state and plugin version.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_firewall_status' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_check_ip', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_check_ip', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_check_ip"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_check_ip" value="<?php echo $data->is_checked('bbcs_ability_check_ip', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Check an IP address', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/check-ip — checks an IP address against the firewall rules and returns the matched rules.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_check_ip' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_ip_rules', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_ip_rules', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_ip_rules"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_ip_rules" value="<?php echo $data->is_checked('bbcs_ability_get_ip_rules', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get IP rules', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-ip-rules — returns the firewall rules matching a given IP address.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_ip_rules' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_traffic_summary', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_traffic_summary', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_traffic_summary"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_traffic_summary" value="<?php echo $data->is_checked('bbcs_ability_get_traffic_summary', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get traffic summary', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-traffic-summary — aggregated request, block, captcha, suspicious and proxy counters.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_traffic_summary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_run_diagnostics', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_run_diagnostics', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_run_diagnostics"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_run_diagnostics" value="<?php echo $data->is_checked('bbcs_ability_run_diagnostics', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Run diagnostics', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/run-diagnostics — read-only health checks for the data directory and database tables.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_run_diagnostics' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_list_ip_rules', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_list_ip_rules', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_list_ip_rules"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_list_ip_rules" value="<?php echo $data->is_checked('bbcs_ability_list_ip_rules', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('List IP rules', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/list-ip-rules — lists firewall rules with family, type, search and pagination filters.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_list_ip_rules' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_blocked_ips', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_blocked_ips', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_blocked_ips"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_blocked_ips" value="<?php echo $data->is_checked('bbcs_ability_get_blocked_ips', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get blocked IP addresses', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-blocked-ips — returns the IP addresses currently blocked by firewall rules.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_blocked_ips' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_recent_events', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_recent_events', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_recent_events"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_recent_events" value="<?php echo $data->is_checked('bbcs_ability_get_recent_events', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get recent events', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-recent-events — aggregated blocks, allows, CAPTCHA and proxy events per time bucket, without IP addresses or user agents.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_recent_events' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_settings', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_settings', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_settings"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_settings" value="<?php echo $data->is_checked('bbcs_ability_get_settings', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get settings', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-settings — non-secret settings by group; secrets are reported only as configured or not.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_settings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_captcha_state', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_captcha_state', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_captcha_state"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_captcha_state" value="<?php echo $data->is_checked('bbcs_ability_get_captcha_state', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get CAPTCHA state', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/get-captcha-state — active CAPTCHA mode, wait time and the availability of every mode.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_captcha_state' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_run_self_test', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_run_self_test', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_run_self_test"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_run_self_test" value="<?php echo $data->is_checked('bbcs_ability_run_self_test', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Run self-test summary', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/run-self-test — self-test summary and bypass reasons without writing to the plugin log.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_run_self_test' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_check_ips', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_check_ips', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_check_ips"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_check_ips" value="<?php echo $data->is_checked('bbcs_ability_check_ips', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Check several IP addresses', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/check-ips — batch check of up to 20 addresses in one call.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_check_ips' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<?php
						$bbcs_extra_read = array(
							'bbcs_ability_get_health_score'        => array( __( 'Get health score', 'botblocker-security' ), __( 'botblocker/get-health-score — health score and the state of every scored protection item.', 'botblocker-security' ) ),
							'bbcs_ability_get_protection_snapshot' => array( __( 'Get protection snapshot', 'botblocker-security' ), __( 'botblocker/get-protection-snapshot — protection state, secure mode, CAPTCHA mode, preset and layers.', 'botblocker-security' ) ),
							'bbcs_ability_get_addons_status'       => array( __( 'Get add-ons status', 'botblocker-security' ), __( 'botblocker/get-addons-status — installed add-ons with activation and compatibility state.', 'botblocker-security' ) ),
							'bbcs_ability_get_license_status'      => array( __( 'Get license status', 'botblocker-security' ), __( 'botblocker/get-license-status — license state without the key or secret.', 'botblocker-security' ) ),
							'bbcs_ability_get_capabilities'        => array( __( 'Get capabilities', 'botblocker-security' ), __( 'botblocker/get-capabilities — feature map, add-on features and protection gates.', 'botblocker-security' ) ),
							'bbcs_ability_validate_config'         => array( __( 'Validate configuration', 'botblocker-security' ), __( 'botblocker/validate-config — read-only configuration checks.', 'botblocker-security' ) ),
							'bbcs_ability_get_cron_jobs'           => array( __( 'Get cron jobs', 'botblocker-security' ), __( 'botblocker/get-cron-jobs — BotBlocker cron events. Event arguments are hidden by default.', 'botblocker-security' ) ),
							'bbcs_ability_get_db_health'           => array( __( 'Get database health', 'botblocker-security' ), __( 'botblocker/get-db-health — table presence, size and approximate row counts.', 'botblocker-security' ) ),
							'bbcs_ability_get_top_entities'        => array( __( 'Get top entities', 'botblocker-security' ), __( 'botblocker/get-top-entities — aggregated country, ASN, user agent, status and path totals.', 'botblocker-security' ) ),
							'bbcs_ability_get_2fa_summary'         => array( __( 'Get 2FA summary', 'botblocker-security' ), __( 'botblocker/get-2fa-summary — administrator two-factor counters only.', 'botblocker-security' ) ),
							'bbcs_ability_get_audit_log'           => array( __( 'Get audit log (PII)', 'botblocker-security' ), __( 'botblocker/get-audit-log — filtered audit log. Masked by default, never public.', 'botblocker-security' ) ),
							'bbcs_ability_get_event_details'       => array( __( 'Get event details (PII)', 'botblocker-security' ), __( 'botblocker/get-event-details — one visitor event with IP, user agent and path. Masked by default.', 'botblocker-security' ) ),
							'bbcs_ability_get_top_ips'             => array( __( 'Get top IP addresses (PII)', 'botblocker-security' ), __( 'botblocker/get-top-ips — busiest IP addresses. Masked by default.', 'botblocker-security' ) ),
							'bbcs_ability_get_login_attempts'      => array( __( 'Get login attempts (PII)', 'botblocker-security' ), __( 'botblocker/get-login-attempts — successful and failed login audit events. Masked by default.', 'botblocker-security' ) ),
							'bbcs_ability_get_visitor_log'         => array( __( 'Get visitor log (PII)', 'botblocker-security' ), __( 'botblocker/get-visitor-log — recent visitor events. Masked by default.', 'botblocker-security' ) ),
							'bbcs_ability_get_user_risk'           => array( __( 'Get user risk (PII)', 'botblocker-security' ), __( 'botblocker/get-user-risk — user role, 2FA state and failed login counts. Masked by default.', 'botblocker-security' ) ),
							'bbcs_ability_get_ability_calls'       => array( __( 'Get ability calls (PII)', 'botblocker-security' ), __( 'botblocker/get-ability-calls — recorded ability calls with outcome and duration. Masked by default.', 'botblocker-security' ) ),
							'bbcs_ability_export_settings'         => array( __( 'Export settings', 'botblocker-security' ), __( 'botblocker/export-settings — settings export. Secrets are always redacted.', 'botblocker-security' ) ),
							'bbcs_ability_simulate_request'        => array( __( 'Simulate a request', 'botblocker-security' ), __( 'botblocker/simulate-request — read-only decision prediction for a fingerprint: rules, signatures, TLS, geo, ASN and rate state.', 'botblocker-security' ) ),
							'bbcs_ability_get_decision_trace'      => array( __( 'Get decision trace', 'botblocker-security' ), __( 'botblocker/get-decision-trace — explains the last stored decision for an IP address or a client id (cid).', 'botblocker-security' ) ),
							'bbcs_ability_get_rate_limit_state'    => array( __( 'Get rate-limit state', 'botblocker-security' ), __( 'botblocker/get-rate-limit-state — individual and subnet buckets, thresholds and an active rate-limit ban.', 'botblocker-security' ) ),
							'bbcs_ability_get_request_fingerprint' => array( __( 'Get request fingerprint', 'botblocker-security' ), __( 'botblocker/get-request-fingerprint — how BotBlocker normalizes IP, user agent, TLS headers, geo and protocol.', 'botblocker-security' ) ),
							'bbcs_ability_get_data_integrity'      => array( __( 'Get data integrity', 'botblocker-security' ), __( 'botblocker/get-data-integrity — runtime files with size, mtime, optional hashes, missing and extra files.', 'botblocker-security' ) ),
							'bbcs_ability_get_bot_signature_match' => array( __( 'Get bot signature match', 'botblocker-security' ), __( 'botblocker/get-bot-signature-match — which signature or search-engine entry matches a user agent.', 'botblocker-security' ) ),
							'bbcs_ability_get_tls_match'           => array( __( 'Get TLS match', 'botblocker-security' ), __( 'botblocker/get-tls-match — JA3/JA4 category and user-agent family comparison without a handshake.', 'botblocker-security' ) ),
							'bbcs_ability_get_geo'                 => array( __( 'Get geo and ASN', 'botblocker-security' ), __( 'botblocker/get-geo — country, continent, ASN, organization and CIDR from the local ASN database.', 'botblocker-security' ) ),
							'bbcs_ability_get_pass_state'          => array( __( 'Get cache pass state', 'botblocker-security' ), __( 'botblocker/get-pass-state — format, expiry and signature verdict for a cache pass cookie.', 'botblocker-security' ) ),
							'bbcs_ability_get_challenge_state'     => array( __( 'Get challenge state', 'botblocker-security' ), __( 'botblocker/get-challenge-state — CAPTCHA mode, wait time, remaining attempts, provider and fallback.', 'botblocker-security' ) ),
							'bbcs_ability_get_rule_match'          => array( __( 'Get rule match', 'botblocker-security' ), __( 'botblocker/get-rule-match — every matching IP and custom rule with the winner.', 'botblocker-security' ) ),
							'bbcs_ability_get_layer_state'         => array( __( 'Get layer state', 'botblocker-security' ), __( 'botblocker/get-layer-state — Early Init, MU-plugin, main shield, gates, conflicts and versions.', 'botblocker-security' ) ),
							'bbcs_ability_get_edge_trust'         => array( __( 'Get edge trust', 'botblocker-security' ), __( 'botblocker/get-edge-trust — current identity, auto-trusted edges and optional trust maps. Addresses are masked.', 'botblocker-security' ) ),
							'bbcs_ability_get_edge_evidence'      => array( __( 'Get edge evidence (PII)', 'botblocker-security' ), __( 'botblocker/get-edge-evidence — stored evidence rows and promotion thresholds. Addresses and PTR names are masked.', 'botblocker-security' ) ),
							'bbcs_ability_explain_ban_decision'   => array( __( 'Explain a ban decision', 'botblocker-security' ), __( 'botblocker/explain-ban-decision — runs the real ban gate for one target and writes nothing.', 'botblocker-security' ) ),
							'bbcs_ability_export_diagnostics'      => array( __( 'Export diagnostics', 'botblocker-security' ), __( 'botblocker/export-diagnostics — redacted settings, integrity, layers and optionally the last decision.', 'botblocker-security' ) ),
						);
						foreach ( $bbcs_extra_read as $bbcs_key => $bbcs_meta ) :
							?>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked($bbcs_key, '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked($bbcs_key, '1') ? 'true' : 'false'; ?>" data-field="<?php echo esc_attr($bbcs_key); ?>"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="<?php echo esc_attr($bbcs_key); ?>" value="<?php echo $data->is_checked($bbcs_key, '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php echo esc_html($bbcs_meta[0]); ?></span>
							<?php echo Base::help( $bbcs_meta[1], BotBlockerDocs::url( $bbcs_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div>
					<div class="bbcs-setgroup" data-bbcs-ability-scope="manage">
						<div class="bbcs-setgroup-head"><?php esc_html_e('Manage', 'botblocker-security'); ?></div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_block_ip', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_block_ip', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_block_ip"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_block_ip" value="<?php echo $data->is_checked('bbcs_ability_block_ip', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Block an IP address', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/block-ip — adds a firewall block rule and regenerates the runtime ban files. Destructive, requires manage_options.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_block_ip' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_unblock_ip', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_unblock_ip', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_unblock_ip"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_unblock_ip" value="<?php echo $data->is_checked('bbcs_ability_unblock_ip', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Unblock an IP address', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/unblock-ip — removes firewall rules for an IP address and regenerates the runtime ban files. Destructive, requires manage_options.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_unblock_ip' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_upsert_ip_rule', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_upsert_ip_rule', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_upsert_ip_rule"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_upsert_ip_rule" value="<?php echo $data->is_checked('bbcs_ability_upsert_ip_rule', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Create or update an IP rule', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/upsert-ip-rule — creates or updates a rule for an IP or CIDR range. Refuses readonly rows and self-blocking without force.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_upsert_ip_rule' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_delete_ip_rule', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_delete_ip_rule', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_delete_ip_rule"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_delete_ip_rule" value="<?php echo $data->is_checked('bbcs_ability_delete_ip_rule', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Delete an IP rule by id', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/delete-ip-rule — deletes a single rule by id. Requires confirm "DELETE" and refuses readonly rows.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_delete_ip_rule' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<?php
						$bbcs_extra_manage = array(
							'bbcs_ability_delete_ip_rules'       => array( __( 'Delete IP rules for an address', 'botblocker-security' ), __( 'botblocker/delete-ip-rules — removes non-readonly rules for an IP or CIDR, optionally one rule type. Requires confirm "DELETE".', 'botblocker-security' ) ),
							'bbcs_ability_set_country_rule'      => array( __( 'Set a country rule', 'botblocker-security' ), __( 'botblocker/set-country-rule — creates, updates or disables a country rule. Requires confirm "COUNTRY".', 'botblocker-security' ) ),
							'bbcs_ability_set_asn_rule'          => array( __( 'Set an ASN rule', 'botblocker-security' ), __( 'botblocker/set-asn-rule — creates, updates or disables an ASN rule. Requires confirm "ASN".', 'botblocker-security' ) ),
							'bbcs_ability_update_threat_data'    => array( __( 'Update threat data', 'botblocker-security' ), __( 'botblocker/update-threat-data — refreshes ASN, LLM or TLS data. Requires confirm "UPDATE" and dry-run first.', 'botblocker-security' ) ),
							'bbcs_ability_apply_preset'          => array( __( 'Apply a security preset', 'botblocker-security' ), __( 'botblocker/apply-preset — applies Light, Medium, Strong, Strong API, Full or Emergency and keeps the current ability toggles. Full and Emergency need PRO. Requires confirm "PRESET".', 'botblocker-security' ) ),
							'bbcs_ability_reset_rate_limit'      => array( __( 'Reset a rate-limit bucket', 'botblocker-security' ), __( 'botblocker/reset-rate-limit — clears the individual and subnet buckets for an IP. Requires confirm "RESET".', 'botblocker-security' ) ),
							'bbcs_ability_reset_ptr_cache'       => array( __( 'Reset the PTR cache', 'botblocker-security' ), __( 'botblocker/reset-ptr-cache — deletes cached reverse-DNS results and rotates the plugin cache generation. Requires confirm "RESET".', 'botblocker-security' ) ),
							'bbcs_ability_seed_test_rule'        => array( __( 'Seed a temporary test rule', 'botblocker-security' ), __( 'botblocker/seed-test-rule — creates a short-lived rule with a bbcs-harness: comment. Requires confirm "SEED".', 'botblocker-security' ) ),
							'bbcs_ability_clear_test_rules'      => array( __( 'Clear seeded test rules', 'botblocker-security' ), __( 'botblocker/clear-test-rules — deletes only bbcs-harness: rules. Requires confirm "CLEAR".', 'botblocker-security' ) ),
							'bbcs_ability_set_setting'           => array( __( 'Change a switch setting', 'botblocker-security' ), __( 'botblocker/set-setting — turns one boolean protection switch on or off through the settings save path. Requires confirm "SET".', 'botblocker-security' ) ),
							'bbcs_ability_load_license_from_cloud' => array( __( 'Load license from cloud', 'botblocker-security' ), __( 'botblocker/load-license-from-cloud — asks the license server to push the license to this site. Requires confirm "LOAD".', 'botblocker-security' ) ),
						);
						foreach ( $bbcs_extra_manage as $bbcs_key => $bbcs_meta ) :
							?>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked($bbcs_key, '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked($bbcs_key, '1') ? 'true' : 'false'; ?>" data-field="<?php echo esc_attr($bbcs_key); ?>"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="<?php echo esc_attr($bbcs_key); ?>" value="<?php echo $data->is_checked($bbcs_key, '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php echo esc_html($bbcs_meta[0]); ?></span>
							<?php echo Base::help( $bbcs_meta[1], BotBlockerDocs::url( $bbcs_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<?php endforeach; ?>
					</div>
					<div class="bbcs-setgroup" data-bbcs-ability-scope="danger">
						<div class="bbcs-setgroup-head"><?php esc_html_e('Protection control (destructive)', 'botblocker-security'); ?></div>
						<div class="bbcs-infocol-desc"><?php esc_html_e('These abilities change protection settings. A connected client acts with administrator rights — publish them only for a client you control.', 'botblocker-security'); ?></div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_set_protection', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_set_protection', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_set_protection"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_set_protection" value="<?php echo $data->is_checked('bbcs_ability_set_protection', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable or disable protection', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/set-protection — turns protection on or off through the Dashboard path. Requires confirm "ENABLE" or "DISABLE".', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_set_protection' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_set_secure_mode', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_set_secure_mode', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_set_secure_mode"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_set_secure_mode" value="<?php echo $data->is_checked('bbcs_ability_set_secure_mode', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Set the secure mode', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/set-secure-mode — switches between frontend and full secure mode. Full mode also challenges the admin area.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_set_secure_mode' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_set_captcha_mode', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_set_captcha_mode', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_set_captcha_mode"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_set_captcha_mode" value="<?php echo $data->is_checked('bbcs_ability_set_captcha_mode', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Set the CAPTCHA mode', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/set-captcha-mode — switches the CAPTCHA mode. Unavailable modes are rejected instead of silently falling back.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_set_captcha_mode' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_clear_cache', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_clear_cache', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_clear_cache"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_clear_cache" value="<?php echo $data->is_checked('bbcs_ability_clear_cache', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Clear the plugin file cache', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/clear-cache — clears the BotBlocker file cache after manual rule or data changes.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_clear_cache' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
						<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_remove_edge_trust', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_remove_edge_trust', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_remove_edge_trust"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_remove_edge_trust" value="<?php echo $data->is_checked('bbcs_ability_remove_edge_trust', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Remove an auto-trusted edge', 'botblocker-security'); ?></span>
							<?php echo Base::help( __( 'botblocker/remove-edge-trust — removes one auto-trust entry by mask and audits it. Requires confirm "REMOVE".', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_remove_edge_trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						</div>
					</div>
				<?php if (! empty($data->wp_abilities_addon_groups)) : ?>
					<div class="bbcs-setgroup" data-bbcs-addon-abilities="1">
						<div class="bbcs-setgroup-head"><?php esc_html_e('Add-on abilities', 'botblocker-security'); ?></div>
						<?php foreach ($data->wp_abilities_addon_groups as $bbcs_group) : ?>
							<?php $bbcs_master_on = $data->is_checked('bbcs_wp_abilities_enabled', '1'); ?>
							<div class="bbcs-option bbcs-hoverbg" data-bbcs-addon-ability-group="<?php echo esc_attr((string) $bbcs_group['slug']); ?>">
								<span class="bbcs-option-label"><?php echo esc_html((string) $bbcs_group['name']); ?></span>
								<?php if (! empty($bbcs_group['settings_url'])) : ?>
									<a href="<?php echo esc_url((string) $bbcs_group['settings_url']); ?>"><?php esc_html_e('Open add-on settings', 'botblocker-security'); ?></a>
								<?php endif; ?>
								<div class="bbcs-infocol-desc">
									<?php foreach ((array) $bbcs_group['abilities'] as $bbcs_ability) : ?>
										<div data-bbcs-addon-ability="<?php echo esc_attr((string) $bbcs_ability['name']); ?>">
											<?php echo esc_html((string) $bbcs_ability['label']); ?> — <code><?php echo esc_html((string) $bbcs_ability['name']); ?></code>
											<span class="bbcs-badge"><?php echo ! empty($bbcs_ability['enabled']) ? esc_html__('Enabled', 'botblocker-security') : esc_html__('Disabled', 'botblocker-security'); ?></span>
										</div>
									<?php endforeach; ?>
									<?php if (empty($bbcs_group['active'])) : ?>
										<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Abilities are not registered while the add-on is inactive.', 'botblocker-security'); ?></div>
									<?php elseif (! $bbcs_master_on) : ?>
										<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Disabled because the master switch is off.', 'botblocker-security'); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
<?php
};
