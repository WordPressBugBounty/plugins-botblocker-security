<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;

$bbcs_ddos_md = '';
$bbcs_ddos_md_path = BOTBLOCKER_DIR . 'docs/DDOS-COMPATIBILITY.md';
if ( file_exists( $bbcs_ddos_md_path ) ) {
	$bbcs_ddos_md = (string) file_get_contents( $bbcs_ddos_md_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

return static function (Botblocker_SettingsViewModel $data, bool $isActive) use ( $bbcs_ddos_md ): void {
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

					?>
					<div class="bbcs-option bbcs-hoverbg">
						<button type="button" class="bbcs-btn" id="bbcs-ddos-compat-guide-trigger">
							<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
							<?php esc_html_e( 'Open DDOS-COMPATIBILITY.md', 'botblocker-security' ); ?>
						</button>
						<span class="bbcs-option-label"><?php esc_html_e( 'Full guide: running behind DDoS-Guard, Stormwall, Cloudflare UAM, Qrator and similar services.', 'botblocker-security' ); ?></span>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/DDOS-COMPATIBILITY.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
					</div>
					<?php


				} )
				->render();
			?>
		</div>
	</div>

	<div class="bbcs-modal-overlay" id="bbcsDdosCompatModal" style="display:none;">
		<div class="bbcs-modal bbcs-modal--wide">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title">
					<svg class="bbcs-ico bbcs-ico--sm" style="margin-right:var(--bbcs-sp-1);"><use href="#bbcs-i-doc"></use></svg>
					<?php esc_html_e( 'DDOS-COMPATIBILITY.md', 'botblocker-security' ); ?>
				</div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<pre class="bbcs-md-view"><?php echo esc_html( $bbcs_ddos_md ); ?></pre>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>

	<script>
	(function() {
		'use strict';
		var trigger = document.getElementById('bbcs-ddos-compat-guide-trigger');
		var overlay = document.getElementById('bbcsDdosCompatModal');
		if (!trigger || !overlay) return;

		trigger.addEventListener('click', function(e) {
			e.preventDefault();
			overlay.style.display = 'flex';
		});

		overlay.addEventListener('click', function(e) {
			var btn = e.target.closest('[data-modal-close]');
			if (btn) {
				overlay.style.display = 'none';
				return;
			}
			if (e.target === overlay) {
				overlay.style.display = 'none';
			}
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && overlay.style.display === 'flex') {
				overlay.style.display = 'none';
			}
		});
	})();
	</script>
<?php
};
