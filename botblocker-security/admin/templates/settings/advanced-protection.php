<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;
return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="advanced-protection"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/advanced-protection.svg', __( 'Advanced Protection', 'botblocker-security' ) )
			->withDescription( __( 'Advanced protection uses cloud-based real-time analysis and smart verification to detect sophisticated bots.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/how-botblocker-pros-cloud-verification-defeats-bots/', __( 'Cloud validation', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/cloud-verification-in-botblocker-database-types-used-for-advanced-threat-detection/', __( 'Cloud databases of threats', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/botblocker-free-vs-pro-which-version-to-choose/', __( 'PRO vs Free', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Advanced Protection', 'botblocker-security' ) )
				->withItems( static function () use ( $data ): void {
					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'check' )->withChecked( $data->is_checked( 'check' ) )->withLabel( __( 'Cloud Validation', 'botblocker-security' ) )->withTooltip( __( 'Send suspicious requests to BotBlocker Cloud for verification.', 'botblocker-security' ) )->withBadge( 'PRO', ToggleOption::BADGE_PRO )->withDisabled( ! $data->has_pro )->render();
							ToggleOption::make()->withName( 'unresponsive' )->withChecked( $data->is_checked( 'unresponsive' ) )->withLabel( __( 'Block Unresponsive Clients', 'botblocker-security' ) )->withTooltip( __( 'Block clients that don&#39;t respond to verification checks.', 'botblocker-security' ) )->withBadge( 'PRO', ToggleOption::BADGE_PRO )->withDisabled( ! $data->has_pro )->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'cloud_fallback_block' )->withChecked( $data->is_checked( 'cloud_fallback_block' ) )->withLabel( __( 'Block on Cloud API Errors', 'botblocker-security' ) )->withTooltip( __( 'When cloud API returns unexpected data - block the visitor and invalidate cache instead of silent pass.', 'botblocker-security' ) )->withBadge( 'PRO', ToggleOption::BADGE_PRO )->withDisabled( ! $data->has_pro )->render();
							ToggleOption::make()->withName( 'botblocker_force_check' )->withChecked( $data->is_checked( 'botblocker_force_check' ) )->withLabel( __( 'Force Verification for All', 'botblocker-security' ) )->withTooltip( __( 'Show Captcha to all visitors, bypassing other checks.', 'botblocker-security' ) )->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'bbcs_ddos_resilience' )->withChecked( $data->is_checked( 'bbcs_ddos_resilience' ) )->withLabel( __( 'Server DDoS Protection Support (Experimental)', 'botblocker-security' ) )->withTooltip( __( 'Hardens the verification cycle against server-side interference (DDoS protection, WAF, CDN, rate-limiters). Adds response signing, circuit breaker, and transport hardening. Disable if your hosting environment interferes with these protections.', 'botblocker-security' ) )->render();
							ToggleOption::make()->withName( 'force_cloud_validation' )->withChecked( $data->is_checked( 'force_cloud_validation' ) )->withLabel( __( 'Force Cloud Validation', 'botblocker-security' ) )->withTooltip( __( 'Verify every visitor via cloud database. Ultimate tier only.', 'botblocker-security' ) )->withBadge( 'Ultimate', ToggleOption::BADGE_ULTIMATE )->withDisabled( ! $data->has_ultimate )->render();
						} )
						->render();


				} )
				->render();
			?>
		</div>
	</div>
<?php
};
