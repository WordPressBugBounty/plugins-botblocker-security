<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_RulesViewModel $data): void {
	$bbcs_geo_countries       = $data->geo_countries;
	$bbcs_geo_countries_count = $data->geo_countries_count;
?>
	<div class="bbcs-tabs" role="tablist">
		<div role="tab" aria-selected="true" class="bbcs-tab is-active" data-tab="Rules" tabindex="0"><?php esc_html_e('Rules', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Paths" tabindex="0"><?php esc_html_e('Paths', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Trusted Bots" tabindex="0"><?php esc_html_e('Trusted Bots', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="IPv4 List" tabindex="0"><?php esc_html_e('IPv4 List', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="IPv6 List" tabindex="0"><?php esc_html_e('IPv6 List', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="Proxy" tabindex="0"><?php esc_html_e('Proxy', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="ASN" tabindex="0"><?php esc_html_e('ASN', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="LLM" tabindex="0"><?php esc_html_e('LLM', 'botblocker-security'); ?></div>
		<div role="tab" class="bbcs-tab" aria-selected="false" data-tab="GEO" tabindex="0"><?php esc_html_e('GEO', 'botblocker-security'); ?></div>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Rules" id="bbcs_rules">
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-rules"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Type', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Expires', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Paths" id="bbcs_path" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-paths"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Trusted Bots" id="bbcs_white_bots" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-white"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Search', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="IPv4 List" id="bbcs_IPv4_list" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-ipv4-rules"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 50px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Expires', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="IPv6 List" id="bbcs_IPv6_list" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-ipv6-rules"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 50px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Expires', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="Proxy" id="bbcs_proxy_list" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-proxy-rules"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 150px;"><?php esc_html_e('Network Mask', 'botblocker-security'); ?></th>
					<th style="min-width: 150px;"><?php esc_html_e('HTTP Header', 'botblocker-security'); ?></th>
					<th style="min-width: 150px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>
	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="ASN" id="bbcs_asn_list" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-asn-rules"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('ASN', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Name', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="LLM" id="bbcs_llm_list" hidden>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-llm" style="width:100%; font-size:11px;">
			<thead>
				<tr>
					<th><?php esc_html_e('Provider', 'botblocker-security'); ?></th>
					<th><?php esc_html_e('IP Ranges', 'botblocker-security'); ?></th>
					<th><?php esc_html_e('UA Tokens', 'botblocker-security'); ?></th>
					<th><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>

	<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="GEO" id="bbcs_geo_list" hidden>
		<div class="bbcs-col bbcs-g-3h">
			<div class="bbcs-row bbcs-g-2">
				<span class="bbcs-fw-semibold bbcs-fs-sm"><?php esc_html_e('Blocked countries:', 'botblocker-security'); ?></span>
				<strong id="bbcs_geo_count"><?php echo (int) $bbcs_geo_countries_count; ?></strong>
			</div>
			<div class="bbcs-row bbcs-g-2">
				<button type="button" id="bbcs_geo_refresh" class="bbcs-btn bbcs-btn--sm"><svg class="bbcs-ico bbcs-ico--xs">
						<use href="#bbcs-i-refresh"></use>
					</svg><?php esc_html_e('Reload', 'botblocker-security'); ?></button>
				<button type="button" id="bbcs_geo_save" class="bbcs-btn bbcs-btn--sm bbcs-btn--pri"><svg class="bbcs-ico bbcs-ico--xs">
						<use href="#bbcs-i-check"></use>
					</svg><?php esc_html_e('Save list', 'botblocker-security'); ?></button>
				<button type="button" class="bbcs-btn bbcs-btn--sm" id="bbcs_geo_add_country"><svg class="bbcs-ico bbcs-ico--xs">
						<use href="#bbcs-i-plus"></use>
					</svg><?php esc_html_e('Add country', 'botblocker-security'); ?></button>
				<button type="button" class="bbcs-btn bbcs-btn--sm bbcs-btn--danger" id="bbcs_geo_clear_all"><svg class="bbcs-ico bbcs-ico--xs">
						<use href="#bbcs-i-trash"></use>
					</svg><?php esc_html_e('Clear all', 'botblocker-security'); ?></button>
			</div>
			<div id="bbcs_geo_alert" class="bbcs-card bbcs-amber-card bbcs-mb-2" style="display:none;"><?php esc_html_e('Country list saved.', 'botblocker-security'); ?></div>
			<div class="bbcs-field">
				<div class="bbcs-select">
					<div class="bbcs-select-trigger">
						<span class="bbcs-select-value"><?php esc_html_e('Select a country', 'botblocker-security'); ?></span>
						<span class="bbcs-select-caret"><svg class="bbcs-ico bbcs-ico--sm">
								<use href="#bbcs-i-chevron"></use>
							</svg></span>
					</div>
					<div class="bbcs-select-menu">
						<?php foreach (BBCS_COUNTRIES as $bbcs_key => $bbcs_country_name) : ?>
							<div class="bbcs-select-opt" data-value="<?php echo esc_attr(strtoupper($bbcs_key)); ?>"><?php echo esc_html($bbcs_country_name . ' - ' . strtoupper($bbcs_key)); ?></div>
						<?php endforeach; ?>
					</div>
				</div>
				<input type="hidden" id="geoCountrySelect" value="">
			</div>
			<div>
				<div class="bbcs-fs-xs bbcs-dim bbcs-mb-1h"><?php esc_html_e('Selected countries:', 'botblocker-security'); ?></div>
				<div id="geoTags" class="bbcs-row bbcs-g-1 bbcs-row--wrap">
				</div>
			</div>
			<div>
				<div class="bbcs-fs-xs bbcs-dim bbcs-mb-1h"><?php esc_html_e('Codes for config:', 'botblocker-security'); ?></div>
				<textarea id="geoCountryCodes" class="bbcs-input bbcs-input--mono" rows="3" readonly><?php echo esc_textarea(implode(',', $bbcs_geo_countries)); ?></textarea>
			</div>
			<div class="bbcs-fs-xs bbcs-dim"><?php esc_html_e('Visitors from selected countries will be unable to access the site.', 'botblocker-security'); ?></div>
		</div>
	</div>


<?php
};
