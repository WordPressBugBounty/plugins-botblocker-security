<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Base;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
	$malware_active = function_exists('bbcs_wp_ability_addon_active') && bbcs_wp_ability_addon_active('bbcs-malware');
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="wp-abilities"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/wordpress.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Expose BotBlocker to the WordPress Abilities API. When disabled, nothing is registered — the abilities do not exist for REST, MCP or AI clients.', 'botblocker-security'); ?></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('WP Abilities API', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_wp_abilities_enabled', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_wp_abilities_enabled', '1') ? 'true' : 'false'; ?>" data-field="bbcs_wp_abilities_enabled"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_wp_abilities_enabled" value="<?php echo $data->is_checked('bbcs_wp_abilities_enabled', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable BotBlocker abilities', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'Master switch. When off, no BotBlocker ability is registered at all — it is invisible in the ability list, REST, MCP and run endpoints.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_wp_abilities_enabled' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
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
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Manage', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_block_ip', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_block_ip', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_block_ip"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_block_ip" value="<?php echo $data->is_checked('bbcs_ability_block_ip', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Block an IP address', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'botblocker/block-ip — adds a firewall block rule and regenerates the runtime ban files. Destructive, requires manage_options.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_block_ip' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_unblock_ip', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_unblock_ip', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_unblock_ip"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_unblock_ip" value="<?php echo $data->is_checked('bbcs_ability_unblock_ip', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Unblock an IP address', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'botblocker/unblock-ip — removes firewall rules for an IP address and regenerates the runtime ban files. Destructive, requires manage_options.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_unblock_ip' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
			</div>
		</div>
		<?php if ($malware_active) : ?>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Malware Scanner (PRO)', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_run_malware_scan', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_run_malware_scan', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_run_malware_scan"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_run_malware_scan" value="<?php echo $data->is_checked('bbcs_ability_run_malware_scan', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Run a malware scan', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'botblocker/run-malware-scan — starts a background malware scan through the Malware Scanner add-on.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_run_malware_scan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_ability_get_scan_results', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_ability_get_scan_results', '1') ? 'true' : 'false'; ?>" data-field="bbcs_ability_get_scan_results"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_ability_get_scan_results" value="<?php echo $data->is_checked('bbcs_ability_get_scan_results', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Get malware scan results', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'botblocker/get-scan-results — returns the status and findings of the latest malware scan.', 'botblocker-security' ), BotBlockerDocs::url( 'bbcs_ability_get_scan_results' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
<?php
};
