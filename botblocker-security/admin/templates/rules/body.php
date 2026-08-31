<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_RulesViewModel $data): void {
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
		<div class="bbcs-asn-db-note">
			<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-database"></use></svg>
			<div>
				<?php if ( $data->asn_db_present ) : ?>
					<div>
						<?php esc_html_e( 'ASN database', 'botblocker-security' ); ?>:
						<span class="bbcs-tx-violet"><?php echo esc_html( number_format_i18n( $data->asn_db_count ) ); ?></span>
						<?php if ( $data->asn_db_updated_at > 0 ) : ?>
							<?php
							$bbcs_asn_age = sprintf(
								/* translators: %1$s: time since last update */
								__( 'updated %1$s ago', 'botblocker-security' ),
								human_time_diff( $data->asn_db_updated_at, time() )
							);
							?>
							<span class="bbcs-dim">· <?php echo esc_html( $bbcs_asn_age ); ?></span>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="bbcs-dim"><?php esc_html_e( 'ASN database: not yet downloaded.', 'botblocker-security' ); ?></div>
				<?php endif; ?>
				<div class="bbcs-dim"><?php esc_html_e( 'Maintained automatically by BotBlocker cloud - read-only. Manage your own ASN rules in the table above.', 'botblocker-security' ); ?></div>
			</div>
		</div>
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
		<div class="bbcs-row bbcs-g-2 bbcs-mb-2 bbcs-geo-toolbar">
			<div class="bbcs-field">
				<div class="bbcs-select">
					<div class="bbcs-select-trigger">
						<span class="bbcs-select-value"><?php esc_html_e('Select a country', 'botblocker-security'); ?></span>
						<span class="bbcs-select-caret"><svg class="bbcs-ico bbcs-ico--sm">
								<use href="#bbcs-i-chevron"></use>
							</svg></span>
					</div>
					<div class="bbcs-select-menu">
						<?php foreach (BotBlockerGeo::getCountries() as $bbcs_key => $bbcs_country_name) : ?>
							<div class="bbcs-select-opt" data-value="<?php echo esc_attr(strtoupper($bbcs_key)); ?>"><?php echo esc_html($bbcs_country_name . ' - ' . strtoupper($bbcs_key)); ?></div>
						<?php endforeach; ?>
					</div>
				</div>
				<input type="hidden" id="geoCountrySelect" value="">
			</div>
			<button type="button" class="bbcs-btn bbcs-btn--sm bbcs-btn--pri" id="bbcs_geo_add_country"><svg class="bbcs-ico bbcs-ico--xs">
					<use href="#bbcs-i-plus"></use>
				</svg><?php esc_html_e('Add country', 'botblocker-security'); ?></button>
			<button type="button" class="bbcs-btn bbcs-btn--sm bbcs-btn--danger" id="bbcs_geo_clear_all"><svg class="bbcs-ico bbcs-ico--xs">
					<use href="#bbcs-i-trash"></use>
				</svg><?php esc_html_e('Clear all', 'botblocker-security'); ?></button>
			<div class="bbcs-row bbcs-g-2" style="margin-left:auto;">
				<a href="<?php echo esc_url( $data->mu_geo_url ); ?>" class="bbcs-btn bbcs-btn--sm">
					<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-shield"></use></svg>
					<?php esc_html_e( 'MU layer', 'botblocker-security' ); ?>
					<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Enable GEO blocking in the MU layer to filter blocked countries before WordPress loads plugins.', 'botblocker-security' ); ?></span></span>
				</a>
				<?php if ( $data->early_geo_available ) : ?>
					<a href="<?php echo esc_url( $data->early_geo_url ); ?>" class="bbcs-btn bbcs-btn--sm">
						<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-bolt"></use></svg>
						<?php esc_html_e( 'Early Init', 'botblocker-security' ); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_html_e( 'Enable GEO blocking in Early Init to reject blocked countries before WordPress even starts.', 'botblocker-security' ); ?></span></span>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $data->early_geo_url ); ?>" class="bbcs-btn bbcs-btn--sm" title="<?php esc_attr_e( 'Early Init requires an active PRO license', 'botblocker-security' ); ?>">
						<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-bolt"></use></svg>
						<?php esc_html_e( 'Early Init', 'botblocker-security' ); ?>
						<span class="bbcs-pill bbcs-pill--violet bbcs-pill--pro"><?php esc_html_e( 'PRO', 'botblocker-security' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<table class="bbcs-table compact bbcs-mb-0" id="botblocker-geo"
			style="width:100%; font-size: 11px;">
			<thead>
				<tr>
					<th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Code', 'botblocker-security'); ?></th>
					<th style="min-width: 150px;"><?php esc_html_e('Country', 'botblocker-security'); ?></th>
					<th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
					<th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
				</tr>
			</thead>
		</table>
	</div>

<?php
};
