<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="7">
		<div class="bbcs-wizsuccess">
			<div class="bbcs-wizsuccess-ic"><svg class="bbcs-ico bbcs-ico--xl bbcs-ico--thick"><use href="#bbcs-i-check"></use></svg></div>
			<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Your site is protected!', 'botblocker-security' ); ?></h2>
			<p class="bbcs-wizsub"><?php esc_html_e( 'BotBlocker is monitoring and protecting your site.', 'botblocker-security' ); ?></p>
		</div>

		<div class="bbcs-wizsummary">
			<div class="bbcs-wizsummary-title"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-list"></use></svg> <?php esc_html_e( 'Configuration summary', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizsummary-grid">
				<div class="bbcs-wizsum-item">
					<div class="bbcs-wizsum-label"><?php esc_html_e( 'Protection level', 'botblocker-security' ); ?></div>
					<div class="bbcs-wizsum-val bbcs-tx-blue" id="bbcs-wiz-final-preset"><?php esc_html_e( 'Strong', 'botblocker-security' ); ?></div>
				</div>
				<div class="bbcs-wizsum-item">
					<div class="bbcs-wizsum-label"><?php esc_html_e( 'Captcha', 'botblocker-security' ); ?></div>
					<div class="bbcs-wizsum-val bbcs-tx-blue" id="bbcs-wiz-final-captcha"><?php esc_html_e( 'Silent Auto-Verify (No Captcha)', 'botblocker-security' ); ?></div>
				</div>
				<div class="bbcs-wizsum-item">
					<div class="bbcs-wizsum-label"><?php esc_html_e( 'Initialization', 'botblocker-security' ); ?></div>
					<div class="bbcs-wizsum-val bbcs-tx-blue" id="bbcs-wiz-final-init"><?php esc_html_e( 'Regular plugin', 'botblocker-security' ); ?></div>
				</div>
				<div class="bbcs-wizsum-item">
					<div class="bbcs-wizsum-label"><?php esc_html_e( 'Protection score', 'botblocker-security' ); ?></div>
					<div class="bbcs-wizsum-val bbcs-tx-green" id="bbcs-wiz-final-score"><?php esc_html_e( '75%', 'botblocker-security' ); ?></div>
				</div>
			</div>
		</div>

		<div class="bbcs-section">
			<div class="bbcs-section-head"><div class="bbcs-section-title"><?php esc_html_e( 'Quick start', 'botblocker-security' ); ?></div></div>
			<div class="bbcs-wiznext">
				<a class="bbcs-wiznext-card" href="<?php echo esc_url( $d->dashboard_url ); ?>">
					<div class="bbcs-wiznext-ic"><svg class="bbcs-ico"><use href="#bbcs-i-gauge"></use></svg></div>
					<div class="bbcs-wiznext-label"><?php esc_html_e( 'Dashboard', 'botblocker-security' ); ?></div>
					<div class="bbcs-wiznext-desc"><?php esc_html_e( 'Real-time threat monitoring', 'botblocker-security' ); ?></div>
				</a>
				<a class="bbcs-wiznext-card" href="<?php echo esc_url( $d->reports_url ); ?>">
					<div class="bbcs-wiznext-ic"><svg class="bbcs-ico"><use href="#bbcs-i-chart"></use></svg></div>
					<div class="bbcs-wiznext-label"><?php esc_html_e( 'Reports', 'botblocker-security' ); ?></div>
					<div class="bbcs-wiznext-desc"><?php esc_html_e( 'Detailed logs of all blocks', 'botblocker-security' ); ?></div>
				</a>
				<a class="bbcs-wiznext-card" href="<?php echo esc_url( $d->rules_url ); ?>">
					<div class="bbcs-wiznext-ic"><svg class="bbcs-ico"><use href="#bbcs-i-sliders"></use></svg></div>
					<div class="bbcs-wiznext-label"><?php esc_html_e( 'Rules', 'botblocker-security' ); ?></div>
					<div class="bbcs-wiznext-desc"><?php esc_html_e( 'Custom block/allow rules', 'botblocker-security' ); ?></div>
				</a>
				<a class="bbcs-wiznext-card" href="<?php echo esc_url( $d->settings_url ); ?>">
					<div class="bbcs-wiznext-ic"><svg class="bbcs-ico"><use href="#bbcs-i-gear"></use></svg></div>
					<div class="bbcs-wiznext-label"><?php esc_html_e( 'Settings', 'botblocker-security' ); ?></div>
					<div class="bbcs-wiznext-desc"><?php esc_html_e( 'Fine-tune algorithms', 'botblocker-security' ); ?></div>
				</a>
			</div>
		</div>

		<?php if ( ! $d->has_pro ) : ?>
		<div class="bbcs-wizpro">
			<div class="bbcs-wizpro-badge"><svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-crown"></use></svg> <?php esc_html_e( 'PRO', 'botblocker-security' ); ?></div>
			<div class="bbcs-wizpro-title"><?php esc_html_e( 'Unlock PRO protection', 'botblocker-security' ); ?></div>
			<p class="bbcs-dim bbcs-fs-xs bbcs-mb-3h"><?php esc_html_e( 'Advanced security features for production sites.', 'botblocker-security' ); ?></p>
			<div class="bbcs-wizpro-feats">
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'Early Init', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'Zero-day botnet updates', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'WordPress acceleration', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( '5M+ bot signatures', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'Hide wp-login + addons', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'All premium addons', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'VPN and Tor blocking', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'Priority support', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'AI behavior analysis', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizpro-feat"><?php esc_html_e( 'Emergency assistance (24h)', 'botblocker-security' ); ?></div>
			</div>
			<div class="bbcs-wizcta">
				<a class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" href="<?php echo esc_url( $d->pro_url ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-crown"></use></svg> <?php esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); ?></a>
			</div>
		</div>
		<?php endif; ?>

		<div class="bbcs-wizcta">
			<a class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" href="<?php echo esc_url( $d->dashboard_url ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-home"></use></svg> <?php esc_html_e( 'Go to Dashboard', 'botblocker-security' ); ?></a>
			<a class="bbcs-btn bbcs-btn--lg" href="<?php echo esc_url( $d->docs_url ); ?>" target="_blank"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg> <?php esc_html_e( 'Documentation', 'botblocker-security' ); ?></a>
		</div>

		<p class="bbcs-wizfoot">
			<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-support"></use></svg>
			<?php esc_html_e( 'Need help? Visit our', 'botblocker-security' ); ?>
			<a href="<?php echo esc_url( $d->docs_url ); ?>" target="_blank"><?php esc_html_e( 'support center', 'botblocker-security' ); ?></a>
			<?php esc_html_e( 'or contact us at', 'botblocker-security' ); ?>
			<a href="https://botblocker.top/contacts/" target="_blank">botblocker.top/contacts</a>
		</p>
	</div>
<?php };
