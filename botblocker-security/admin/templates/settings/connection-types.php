<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\FieldPair;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="connection-types"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withDocPage( BotBlockerDocs::page( 'connection-types' ) )
			->withIconImage( BOTBLOCKER_URL . 'public/icons/connections-types.svg', __( 'Connection Types', 'botblocker-security' ) )
			->withDescription( __( 'Connection filtering blocks suspicious connection methods used by bots and scrapers.', 'botblocker-security' ) )
			->withDescription( __( 'Restrict proxy servers, data centers, VPNs, and legacy protocols to reduce automated threats.', 'botblocker-security' ) )
			->render();
		?>
		<?php
		FieldPair::make()
			->withItems( static function () use ( $data ): void {
				?>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'Connection Types', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'block_proxy_users' )->withChecked( $data->is_checked( 'block_proxy_users' ) )->withLabel( __( 'Classic Proxy', 'botblocker-security' ) )->withTooltip( __( 'Block HTTP proxy IP ranges.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'block_proxy_users' ) )->render();
						ToggleOption::make()->withName( 'block_cf_users' )->withChecked( $data->is_checked( 'block_cf_users' ) )->withLabel( __( 'Cloudflare Origin IPs', 'botblocker-security' ) )->withTooltip( __( 'Block unauthenticated requests from Cloudflare IPs.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'block_cf_users' ) )->render();
						ToggleOption::make()->withName( 'block_ipv6_users' )->withChecked( $data->is_checked( 'block_ipv6_users' ) )->withLabel( __( 'IPv6 Connections', 'botblocker-security' ) )->withTooltip( __( 'Block access via IPv6 protocol.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'block_ipv6_users' ) )->render();
						ToggleOption::make()->withName( 'block_http10_users' )->withChecked( $data->is_checked( 'block_http10_users' ) )->withLabel( __( 'HTTP/1.0 Protocol', 'botblocker-security' ) )->withTooltip( __( 'Block legacy HTTP/1.0 protocol.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'block_http10_users' ) )->render();
					} )
					->render();
				?>
			</div>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'Extra Connection Types', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'hosting_block' )->withChecked( $data->is_checked( 'hosting_block' ) )->withLabel( __( 'Hosting Provider IPs', 'botblocker-security' ) )->withTooltip( __( 'Block data center IPs (VPS, AWS, DigitalOcean, etc.). Search engines are always whitelisted.', 'botblocker-security' ) )->withProGate()->withDocLink( BotBlockerDocs::url( 'hosting_block' ) )->render();
						ToggleOption::make()->withName( 'block_vpn_users' )->withChecked( $data->is_checked( 'block_vpn_users' ) )->withLabel( __( 'VPN Connections', 'botblocker-security' ) )->withTooltip( __( 'Block traffic from known VPN service IP addresses.', 'botblocker-security' ) )->withProGate()->withDocLink( BotBlockerDocs::url( 'block_vpn_users' ) )->render();
						ToggleOption::make()->withName( 'block_tor_users' )->withChecked( $data->is_checked( 'block_tor_users' ) )->withLabel( __( 'Tor Exit Nodes', 'botblocker-security' ) )->withTooltip( __( 'Block traffic from known Tor exit nodes.', 'botblocker-security' ) )->withProGate()->withDocLink( BotBlockerDocs::url( 'block_tor_users' ) )->render();
					} )
					->render();

				SettingsGroup::make()
					->withTitle( __( 'Self Connections', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'allow_self_ip_req' )->withChecked( $data->is_checked( 'allow_self_ip_req' ) )->withLabel( __( 'Allow requests from your server IP', 'botblocker-security' ) )->withTooltip( __( 'Allow your server IP to bypass security checks for updates and automated tasks.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'allow_self_ip_req' ) )->render();
						ToggleOption::make()->withName( 'allow_self_call_header' )->withChecked( $data->is_checked( 'allow_self_call_header' ) )->withLabel( __( 'Self-call secret header proof', 'botblocker-security' ) )->withTooltip( __( 'Attach a signed X-BotBlocker-Self header to outgoing WordPress HTTP API calls to your own domain and accept it as self-call proof. Disable only if your hosting/WAF interferes with unknown request headers.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'allow_self_call_header' ) )->render();
					} )
					->render();
				?>
			</div>
				<?php
			} )
			->render();
		?>
	</div>
<?php
};
