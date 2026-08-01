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
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="simple-detection"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/simple-bot-detection.svg', __( 'Simple Bot Detection', 'botblocker-security' ) )
			->withDescription( __( 'Simple bot detection analyzes basic browser characteristics to catch bots that fail to mimic real browsers.', 'botblocker-security' ) )
			->withDescription( __( 'Each method targets a specific bot weakness. Some privacy tools may trigger false positives.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/simple-bot-blocking-settings-you-should-enable/', __( 'Simple Bot Detection', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/understanding-user-agent-strings-methods-for-bot-detection/', __( 'User-Agent', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/accept-language-header-basic-bot-detection-in-botblocker/', __( 'Accept-Language', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/how-javascript-support-check-in-botblocker-pro-detects-bots/', __( 'Javascript Support', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/geoip-language-mismatch-advanced-bot-filtering-in-botblocker/', __( 'GeoIP Language mismatch', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/ptr-record-checks-detecting-fake-bots-with-reverse-dns-in-botblocker/', __( 'PTR', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/ptr-equals-ip-optional-blocking-for-generic-reverse-dns-in-botblocker/', __( 'PTR anomalies', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-the-referer-header-filtering-fake-dangerous-and-invalid-referer-traffic-in-botblocker/', __( 'Referer', 'botblocker-security' ) )
			->render();
		?>
		<?php
		FieldPair::make()
			->withItems( static function () use ( $data ): void {
				?>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'Simple Bot Detection', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'block_empty_ua' )->withChecked( $data->is_checked( 'block_empty_ua' ) )->withLabel( __( 'Empty User-Agent', 'botblocker-security' ) )->withTooltip( __( 'Block requests with missing User-Agent header.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_simplebot_ua' )->withChecked( $data->is_checked( 'block_simplebot_ua' ) )->withLabel( __( 'User-Agent Anomalies', 'botblocker-security' ) )->withTooltip( __( 'Block known anti-detect and malformed User-Agent strings.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_empty_lang' )->withChecked( $data->is_checked( 'block_empty_lang' ) )->withLabel( __( 'Empty Accept-Language', 'botblocker-security' ) )->withTooltip( __( 'Block requests with missing Accept-Language header.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'bbcs_allow_empty_accept_lang' )->withChecked( $data->is_checked( 'bbcs_allow_empty_accept_lang' ) )->withLabel( __( 'Allow Empty Accept-Language in Verification', 'botblocker-security' ) )->withTooltip( __( 'Permit CAPTCHA verification for visitors without Accept-Language header (privacy tools, Tor safest mode). Falls back to site default locale. Keep disabled unless you serve privacy-focused users.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_nojs_users' )->withChecked( $data->is_checked( 'block_nojs_users' ) )->withLabel( __( 'No JavaScript Support', 'botblocker-security' ) )->withTooltip( __( 'Block visitors without JavaScript support.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_fake_ref' )->withChecked( $data->is_checked( 'block_fake_ref' ) )->withLabel( __( 'Fake Referer', 'botblocker-security' ) )->withTooltip( __( 'Block requests with spoofed Referer headers.', 'botblocker-security' ) )->render();
					} )
					->render();
				?>
			</div>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'PTR Options', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'block_ip_ptr_match' )->withChecked( $data->is_checked( 'block_ip_ptr_match' ) )->withLabel( __( 'PTR / DNS Anomalies', 'botblocker-security' ) )->withTooltip( __( 'Block IPs where forward and reverse DNS records don&#39;t match.', 'botblocker-security' ) )->render();
					} )
					->render();

				SettingsGroup::make()
					->withTitle( __( 'Extra Options', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'options_preflight' )->withChecked( $data->is_checked( 'options_preflight' ) )->withLabel( __( 'Allow OPTIONS Preflight', 'botblocker-security' ) )->withTooltip( __( 'Allow HTTP OPTIONS requests for CORS preflight (REST API, WooCommerce, external apps).', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_incorrect_lang_users' )->withChecked( $data->is_checked( 'block_incorrect_lang_users' ) )->withLabel( __( 'Geo IP / Language Mismatch', 'botblocker-security' ) )->withTooltip( __( 'Flag visitors whose browser language doesn&#39;t match their GeoIP location. May affect travelers and VPN users.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'whitelist_whatsapp_preview' )->withChecked( $data->is_checked( 'whitelist_whatsapp_preview' ) )->withLabel( __( 'Whitelist WhatsApp Preview', 'botblocker-security' ) )->withTooltip( __( 'Allow WhatsApp to create link previews for shared pages. Warning: this reduces protection across the whole site for matching requests, not just previews. Enable only if really needed.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_rkn' )->withChecked( $data->is_checked( 'block_rkn' ) )->withLabel( __( 'Block RKN', 'botblocker-security' ) )->withTooltip( __( 'Block connections from known RKN-listed government IP addresses.', 'botblocker-security' ) )->render();
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
