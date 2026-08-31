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
			->withIconImage( BOTBLOCKER_URL . 'public/icons/connections-types.svg', __( 'Connection Types', 'botblocker-security' ) )
			->withDescription( __( 'Connection filtering blocks suspicious connection methods used by bots and scrapers.', 'botblocker-security' ) )
			->withDescription( __( 'Restrict proxy servers, data centers, VPNs, and legacy protocols to reduce automated threats.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-an-ip-address-understanding-ipv4-and-ipv6/', __( 'IP protocols', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-asn-how-autonomous-systems-help-identify-threats-to-your-website/', __( 'ASN', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-http-understanding-protocol-versions-and-blocking-http-1-0-in-botblocker/', __( 'HTTP', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/hosting-detection-why-botblocker-identifies-hosting-providers-and-what-it-means-for-security/', __( 'Detect hosting', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-a-vpn-how-virtual-private-networks-work-and-why-they-matter/', __( 'VPN', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-tor-how-botblocker-detects-and-blocks-connections-from-the-tor-network/', __( 'Tor', 'botblocker-security' ) )
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
						ToggleOption::make()->withName( 'block_proxy_users' )->withChecked( $data->is_checked( 'block_proxy_users' ) )->withLabel( __( 'Classic Proxy', 'botblocker-security' ) )->withTooltip( __( 'Block HTTP proxy IP ranges.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_cf_users' )->withChecked( $data->is_checked( 'block_cf_users' ) )->withLabel( __( 'Cloudflare Origin IPs', 'botblocker-security' ) )->withTooltip( __( 'Block unauthenticated requests from Cloudflare IPs.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_ipv6_users' )->withChecked( $data->is_checked( 'block_ipv6_users' ) )->withLabel( __( 'IPv6 Connections', 'botblocker-security' ) )->withTooltip( __( 'Block access via IPv6 protocol.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_http10_users' )->withChecked( $data->is_checked( 'block_http10_users' ) )->withLabel( __( 'HTTP/1.0 Protocol', 'botblocker-security' ) )->withTooltip( __( 'Block legacy HTTP/1.0 protocol.', 'botblocker-security' ) )->render();
					} )
					->render();
				?>
			</div>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'Extra Connection Types', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'hosting_block' )->withChecked( $data->is_checked( 'hosting_block' ) )->withLabel( __( 'Hosting Provider IPs', 'botblocker-security' ) )->withTooltip( __( 'Block data center IPs (VPS, AWS, DigitalOcean, etc.). Search engines are always whitelisted.', 'botblocker-security' ) )->withBadge( 'PRO', ToggleOption::BADGE_PRO )->withDisabled( ! $data->has_pro )->render();
						ToggleOption::make()->withName( 'block_vpn_users' )->withChecked( $data->is_checked( 'block_vpn_users' ) )->withLabel( __( 'VPN Connections', 'botblocker-security' ) )->withTooltip( __( 'Block traffic from known VPN service IP addresses.', 'botblocker-security' ) )->withBadge( 'PRO', ToggleOption::BADGE_PRO )->withDisabled( ! $data->has_pro )->render();
						ToggleOption::make()->withName( 'block_tor_users' )->withChecked( $data->is_checked( 'block_tor_users' ) )->withLabel( __( 'Tor Exit Nodes', 'botblocker-security' ) )->withTooltip( __( 'Block traffic from known Tor exit nodes.', 'botblocker-security' ) )->withBadge( 'PRO', ToggleOption::BADGE_PRO )->withDisabled( ! $data->has_pro )->render();
					} )
					->render();

				SettingsGroup::make()
					->withTitle( __( 'Self Connections', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'allow_self_ip_req' )->withChecked( $data->is_checked( 'allow_self_ip_req' ) )->withLabel( __( 'Allow requests from your server IP', 'botblocker-security' ) )->withTooltip( __( 'Allow your server IP to bypass security checks for updates and automated tasks.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'allow_self_call_header' )->withChecked( $data->is_checked( 'allow_self_call_header' ) )->withLabel( __( 'Self-call secret header proof', 'botblocker-security' ) )->withTooltip( __( 'Attach a signed X-BotBlocker-Self header to outgoing WordPress HTTP API calls to your own domain and accept it as self-call proof. Disable only if your hosting/WAF interferes with unknown request headers.', 'botblocker-security' ) )->render();
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
