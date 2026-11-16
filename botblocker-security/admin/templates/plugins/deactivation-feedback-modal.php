<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	$reason_labels      = BotBlockerDeactivationFeedback::getReasonLabels();
	$what_broke_options = BotBlockerDeactivationFeedback::getWhatBrokeLabels();
	$what_broke_reasons = BotBlockerDeactivationFeedback::getWhatBrokeReasons();
	$integrations_url   = BotBlockerMultisite::getAdminPageUrl( 'bbcs_integrations' );
	$addons_url         = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
	$wizard_url         = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' );
	$help_link          = static function (): void {
		?>
		<a href="<?php echo esc_url( BotBlockerDeactivationFeedback::SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get help', 'botblocker-security' ); ?> &rarr;</a>
		<?php
	};
	?>
	<div class="bbcs-modal-overlay" id="bbcsDeactivationFeedbackModal" style="display:none;">
		<div class="bbcs-modal bbcs-deactivation-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Before you go', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<div id="bbcs-deactivation-feedback-content">
					<p><?php esc_html_e( 'Help us improve BotBlocker. Why are you deactivating the plugin?', 'botblocker-security' ); ?></p>
					<div class="bbcs-col bbcs-g-2" id="bbcs-deactivation-reasons">
						<?php foreach ( BotBlockerDeactivationFeedback::getShuffledReasonKeys() as $key ) : ?>
							<label class="bbcs-row bbcs-g-2 bbcs-align-center">
								<input type="radio" name="bbcs_deactivation_reason" value="<?php echo esc_attr( $key ); ?>" />
								<span><?php echo esc_html( $reason_labels[ $key ] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>

					<div id="bbcs-deactivation-followup" class="bbcs-deactivation-followup bbcs-mt-3" style="display:none;">
						<div class="bbcs-deactivation-followup__title" id="bbcs-deactivation-followup-title"></div>
						<div class="bbcs-deactivation-followup__grid">
							<?php foreach ( $what_broke_options as $wb_key => $wb_label ) : ?>
								<label class="bbcs-row bbcs-g-1 bbcs-align-center" data-only-reasons="<?php echo esc_attr( implode( ',', $what_broke_reasons[ $wb_key ] ) ); ?>">
									<input type="radio" name="bbcs_deactivation_what_broke" value="<?php echo esc_attr( $wb_key ); ?>" />
									<span><?php echo esc_html( $wb_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div id="bbcs-deactivation-rescue" class="bbcs-deactivation-rescue bbcs-mt-3" style="display:none;">
						<div data-rescue-panel="pause">
							<div class="bbcs-deactivation-rescue__text" id="bbcs-deactivation-rescue-pause-text">
								<?php esc_html_e( 'Troubleshooting? You can pause protection without deactivating the plugin.', 'botblocker-security' ); ?>
							</div>
							<div class="bbcs-deactivation-rescue__text" id="bbcs-deactivation-rescue-temp-text" style="display:none;">
								<strong><?php esc_html_e( 'No need to deactivate.', 'botblocker-security' ); ?></strong>
								<?php esc_html_e( 'Pause protection now and turn it back on with one click when you are ready.', 'botblocker-security' ); ?>
							</div>
							<div class="bbcs-row bbcs-g-2 bbcs-align-center bbcs-deactivation-rescue__actions">
								<button type="button" class="bbcs-btn bbcs-btn--pri bbcs-btn--sm" id="bbcs-deactivation-pause">
									<?php esc_html_e( 'Pause protection instead', 'botblocker-security' ); ?>
								</button>
								<?php $help_link(); ?>
							</div>
							<div class="bbcs-deactivation-rescue__error" id="bbcs-deactivation-pause-error" style="display:none;"></div>
						</div>
						<div data-rescue-panel="captcha" style="display:none;">
							<div class="bbcs-deactivation-rescue__text">
								<strong><?php esc_html_e( 'The check page is shown once to each new visitor.', 'botblocker-security' ); ?></strong>
								<?php esc_html_e( 'Prefer a check your visitors already know?', 'botblocker-security' ); ?>
								<ul class="bbcs-deactivation-rescue__links">
									<li>
										<span><?php esc_html_e( 'Google reCAPTCHA:', 'botblocker-security' ); ?></span>
										<a href="<?php echo esc_url( $integrations_url . '#recaptcha-v2' ); ?>">v2</a>
										<span aria-hidden="true">&middot;</span>
										<a href="<?php echo esc_url( $integrations_url . '#recaptcha-v3' ); ?>">v3</a>
									</li>
									<li>
										<span><?php esc_html_e( 'Other providers:', 'botblocker-security' ); ?></span>
										<a href="<?php echo esc_url( $addons_url . '&highlight=bbcs-hcaptcha' ); ?>">hCaptcha</a>
										<span aria-hidden="true">&middot;</span>
										<a href="<?php echo esc_url( $addons_url . '&highlight=bbcs-turnstile' ); ?>">Cloudflare Turnstile</a>
									</li>
									<li><a href="<?php echo esc_url( BotBlockerSnav::getSettingLink( 'bbcs_captcha_mode', true ) ); ?>"><?php esc_html_e( 'Then switch the check to it in CAPTCHA settings', 'botblocker-security' ); ?> &rarr;</a></li>
								</ul>
							</div>
						</div>
						<div data-rescue-panel="wizard" style="display:none;">
							<div class="bbcs-deactivation-rescue__text">
								<strong><?php esc_html_e( 'Let the setup wizard do it in about 1 minute.', 'botblocker-security' ); ?></strong>
								<?php esc_html_e( 'Answer a few questions about your site and it picks the right settings for you.', 'botblocker-security' ); ?>
							</div>
							<div class="bbcs-row bbcs-g-2 bbcs-align-center bbcs-deactivation-rescue__actions">
								<a href="<?php echo esc_url( $wizard_url ); ?>" class="bbcs-btn bbcs-btn--pri bbcs-btn--sm"><?php esc_html_e( 'Run setup wizard', 'botblocker-security' ); ?></a>
								<?php $help_link(); ?>
							</div>
						</div>
						<div data-rescue-panel="addons" style="display:none;">
							<div class="bbcs-deactivation-rescue__text">
								<?php esc_html_e( 'Extra protection comes as add-ons. Tell us below what is missing: we read every answer.', 'botblocker-security' ); ?>
							</div>
							<div class="bbcs-row bbcs-g-2 bbcs-align-center bbcs-deactivation-rescue__actions">
								<a href="<?php echo esc_url( $addons_url ); ?>"><?php esc_html_e( 'Browse add-ons', 'botblocker-security' ); ?> &rarr;</a>
							</div>
						</div>
					</div>

					<div class="bbcs-col bbcs-g-1 bbcs-mt-3">
						<label for="bbcs-deactivation-details" id="bbcs-deactivation-details-label"><?php esc_html_e( 'Additional details (optional)', 'botblocker-security' ); ?></label>
						<textarea id="bbcs-deactivation-details" class="bbcs-input" rows="3" maxlength="2000"></textarea>
					</div>
				</div>

				<div id="bbcs-deactivation-paused-msg" class="bbcs-deactivation-paused" style="display:none;">
					<div class="bbcs-deactivation-paused__icon">&#10003;</div>
					<h3><?php esc_html_e( 'Protection Paused', 'botblocker-security' ); ?></h3>
					<p><?php esc_html_e( 'BotBlocker protection has been temporarily paused. Traffic will pass through freely so you can troubleshoot safely without deactivating the plugin.', 'botblocker-security' ); ?></p>
					<button type="button" class="bbcs-btn bbcs-btn--pri" id="bbcs-deactivation-stay">
						<?php esc_html_e( 'Keep plugin active', 'botblocker-security' ); ?>
					</button>
				</div>
			</div>
			<div class="bbcs-modal-footer" id="bbcs-deactivation-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" id="bbcs-deactivation-skip"><?php esc_html_e( 'Skip and deactivate', 'botblocker-security' ); ?></button>
				<button type="button" class="bbcs-btn bbcs-btn--danger" id="bbcs-deactivation-submit"><?php esc_html_e( 'Send and deactivate', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
