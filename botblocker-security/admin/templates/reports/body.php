<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_ReportsViewModel $data): void {
	$table_header = require BOTBLOCKER_DIR . 'admin/templates/reports/table-header.php';
?>
	<div class="bbcs-tabs" role="tablist">
		<div role="tab" aria-selected="true" class="bbcs-tab is-active" data-tab="Reports Dashboard" tabindex="0"><?php esc_html_e('Reports Dashboard', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Site Visitors" tabindex="0"><?php esc_html_e('Site Visitors', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Admin Panel Log" tabindex="0"><?php esc_html_e('Admin Panel Log', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="WordPress Actions" tabindex="0"><?php esc_html_e('WordPress Actions', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Audit Log" tabindex="0"><?php esc_html_e('Audit Log', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Full Log" tabindex="0"><?php esc_html_e('Full Log', 'botblocker-security'); ?></div>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Reports Dashboard">
		<?php (require BOTBLOCKER_DIR . 'admin/templates/reports/dashboard.php')($data); ?>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Site Visitors" hidden>
		<div class="bbcs-card bbcs-card-pad">
			<table class="bbcs-table compact bbcs-mb-0" id="botblocker-hits"
				style="width:100%; font-size: 11px;">
				<?php $table_header(); ?>
			</table>
		</div>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Admin Panel Log" hidden>
		<div class="bbcs-card bbcs-card-pad">
			<table class="bbcs-table compact bbcs-mb-0" id="botblocker-hits-admin"
				style="width:100%; font-size: 11px;">
				<?php $table_header(); ?>
			</table>
		</div>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="WordPress Actions" hidden>
		<div class="bbcs-card bbcs-card-pad">
			<table class="bbcs-table compact bbcs-mb-0" id="botblocker-other-admin"
				style="width:100%; font-size: 11px;">
				<?php $table_header(); ?>
			</table>
		</div>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Audit Log" hidden>
		<?php (require BOTBLOCKER_DIR . 'admin/templates/reports/audit-log.php')($data); ?>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Full Log" hidden>
		<div class="bbcs-card bbcs-card-pad">
			<table class="bbcs-table compact bbcs-mb-0" id="botblocker-hits-full"
				style="width:100%; font-size: 11px;">
				<?php $table_header(); ?>
			</table>
		</div>
	</div>
<?php
};
