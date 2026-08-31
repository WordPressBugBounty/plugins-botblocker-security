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
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="browser-plugins"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/browser-plugins.svg', __( 'Browser Plugins', 'botblocker-security' ) )
			->withDescription( __( 'Browser detection identifies bots using spoofing, anti-detect tools, and privacy modes. Advanced options verify browser engine consistency.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-adblock-ublock-detection-why-botblocker-identifies-and-blocks-ad-blockers/', __( 'AdBlockers', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-are-antidetect-browsers-how-botblocker-identifies-stealth-browsers-and-fake-fingerprints/', __( 'Antidetect browsers', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/what-is-incognito-mode-why-botblocker-detects-it-and-why-detection-isnt-always-possible/', __( 'Incognito mode', 'botblocker-security' ) )
			->render();
		?>
		<?php
		FieldPair::make()
			->withItems( static function () use ( $data ): void {
				?>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'Browser Modes', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'block_incognito_users' )->withChecked( $data->is_checked( 'block_incognito_users' ) )->withLabel( __( 'Incognito / Private', 'botblocker-security' ) )->withTooltip( __( 'Block visitors using private/incognito browsing mode.', 'botblocker-security' ) )->render();
					} )
					->render();

				SettingsGroup::make()
					->withTitle( __( 'Browser Plugins', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'block_adblocker_users' )->withChecked( $data->is_checked( 'block_adblocker_users' ) )->withLabel( __( 'AdBlock / uBlock', 'botblocker-security' ) )->withTooltip( __( 'Detect active ad blockers. May affect legitimate users with ad-blocking extensions.', 'botblocker-security' ) )->render();
					} )
					->render();
				?>
			</div>
			<div>
				<?php
				SettingsGroup::make()
					->withTitle( __( 'Browser Options', 'botblocker-security' ) )
					->withItems( static function () use ( $data ): void {
						ToggleOption::make()->withName( 'block_simple_antidetect' )->withChecked( $data->is_checked( 'block_simple_antidetect' ) )->withLabel( __( 'Simple JS Consistency', 'botblocker-security' ) )->withTooltip( __( 'Verify browser API behavior (canvas, WebGL).', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_override' )->withChecked( $data->is_checked( 'block_override' ) )->withLabel( __( 'Override Detection', 'botblocker-security' ) )->withTooltip( __( 'Detect tampered browser properties (navigator, plugins).', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_web_engine_options' )->withChecked( $data->is_checked( 'block_web_engine_options' ) )->withLabel( __( 'Engine Parameter Checks', 'botblocker-security' ) )->withTooltip( __( 'Verify browser engine parameters against known browser signatures.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'block_device_options' )->withChecked( $data->is_checked( 'block_device_options' ) )->withLabel( __( 'Device API Verification', 'botblocker-security' ) )->withTooltip( __( 'Verify device APIs (touch, motion sensors) to detect headless browsers.', 'botblocker-security' ) )->render();
						ToggleOption::make()->withName( 'fingerprint_sticky_block' )->withChecked( $data->is_checked( 'fingerprint_sticky_block' ) )->withLabel( __( 'Fingerprint Sticky Block', 'botblocker-security' ) )->withTooltip( __( 'Block visitors by persistent fingerprint even if their IP changes. Fingerprint must be blocked 3+ times to activate.', 'botblocker-security' ) )->render();
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
