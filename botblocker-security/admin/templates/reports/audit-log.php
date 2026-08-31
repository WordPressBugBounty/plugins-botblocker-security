<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_ReportsViewModel $data): void {
?>
	<div class="bbcs-card bbcs-card-pad">
		<div class="bbcs-audit-toolbar" id="bbcs-audit-filters">
			<select id="bbcs-audit-category" class="bbcs-input">
				<option value=""><?php esc_html_e( 'All categories', 'botblocker-security' ); ?></option>
				<option value="auth">auth</option>
				<option value="content">content</option>
				<option value="user">user</option>
				<option value="media">media</option>
				<option value="comment">comment</option>
				<option value="taxonomy">taxonomy</option>
				<option value="plugin">plugin</option>
				<option value="theme">theme</option>
				<option value="core">core</option>
				<option value="settings">settings</option>
				<option value="botblocker">botblocker</option>
			</select>
			<select id="bbcs-audit-severity" class="bbcs-input">
				<option value="0"><?php esc_html_e( 'All severities', 'botblocker-security' ); ?></option>
				<option value="100"><?php esc_html_e( 'Info and above', 'botblocker-security' ); ?></option>
				<option value="300"><?php esc_html_e( 'Medium and above', 'botblocker-security' ); ?></option>
				<option value="500"><?php esc_html_e( 'Critical only', 'botblocker-security' ); ?></option>
			</select>
			<select id="bbcs-audit-context" class="bbcs-input">
				<option value=""><?php esc_html_e( 'All contexts', 'botblocker-security' ); ?></option>
				<option value="admin">admin</option>
				<option value="ajax">ajax</option>
				<option value="rest">rest</option>
				<option value="xmlrpc">xmlrpc</option>
				<option value="frontend">frontend</option>
				<option value="cron">cron</option>
				<option value="cli">cli</option>
			</select>
		</div>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-audit-log" style="width:100%;">
			<thead>
				<tr>
					<th class="bbcs-audit-col-time"><?php esc_html_e( 'Time', 'botblocker-security' ); ?></th>
					<th class="bbcs-audit-col-sev"><?php esc_html_e( 'Severity', 'botblocker-security' ); ?></th>
					<th class="bbcs-audit-col-event"><?php esc_html_e( 'Event', 'botblocker-security' ); ?></th>
					<th class="bbcs-audit-col-actor"><?php esc_html_e( 'Actor', 'botblocker-security' ); ?></th>
					<th class="bbcs-audit-col-ip"><?php esc_html_e( 'IP', 'botblocker-security' ); ?></th>
					<th class="bbcs-audit-col-actions"><?php esc_html_e( 'Actions', 'botblocker-security' ); ?></th>
				</tr>
			</thead>
		</table>
	</div>
<?php
};
