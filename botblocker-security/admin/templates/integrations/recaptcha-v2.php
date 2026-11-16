<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Base;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="recaptcha-v2"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/google.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Google reCaptcha v2 displays interactive puzzles to verify human users before form submission or page access.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-desc"><?php esc_html_e('Enter your Google reCaptcha v2 site and secret keys below.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-doc"><a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( BotBlockerDocs::page( 'recaptcha' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('reCaptcha v2', 'botblocker-security'); ?></div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('reCaptcha v2 Site Key:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Enter your Google reCaptcha v2 site key obtained from Google reCaptcha console. This key is used to display the Captcha challenge on your website forms.', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_key2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="recaptcha_key2" value="<?php echo esc_attr($data->get('recaptcha_key2')); ?>"></div>
					<?php if ( empty( $data->get('recaptcha_key2') ) ) : ?>
						<div class="bbcs-field-desc"><?php esc_html_e( 'Site key not set.', 'botblocker-security' ); ?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a></div>
					<?php endif; ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('reCaptcha v2 Secret Key:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Enter your Google reCaptcha v2 secret key. This private key is used to verify user responses on the server side and should be kept confidential.', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_secret2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="recaptcha_secret2" value="<?php echo esc_attr($data->get('recaptcha_secret2')); ?>"></div>
					<?php if ( empty( $data->get('recaptcha_secret2') ) ) : ?>
						<div class="bbcs-field-desc"><?php esc_html_e( 'Secret key not set.', 'botblocker-security' ); ?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a></div>
					<?php endif; ?>
				</div>
			</div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Check page', 'botblocker-security' ); ?></div>
				<div class="bbcs-recaptcha-use">
					<div class="bbcs-recaptcha-use__now">
						<span><?php esc_html_e( 'Visitors see now:', 'botblocker-security' ); ?></span>
						<span class="bbcs-pill <?php echo $data->recaptcha_v2_active ? 'bbcs-pill--green' : 'bbcs-recaptcha-use__mode'; ?>"><?php echo esc_html( $data->captcha_mode_label ); ?></span>
					</div>
					<?php if ( ! $data->recaptcha_v2_active ) : ?>
						<div class="bbcs-card bbcs-card-pad bbcs-amber-card bbcs-recaptcha-use__hint">
							<div class="bbcs-row bbcs-g-2h">
								<svg class="bbcs-ico bbcs-ico--md bbcs-tx-amber"><use href="#bbcs-i-warning"></use></svg>
								<span class="bbcs-fs-xs bbcs-tx-amber"><?php esc_html_e( 'The Save button only stores your keys. Click the button below to also show reCAPTCHA v2 to visitors on the check page.', 'botblocker-security' ); ?></span>
							</div>
						</div>
						<div class="bbcs-recaptcha-use__action">
							<button type="button" class="bbcs-btn bbcs-btn--pri bbcs-btn--sm" data-bbcs-use-recaptcha data-field="bbcs_captcha_mode" data-value="<?php echo esc_attr( (string) BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2 ); ?>" data-need="bbcs-recaptcha-v2-need" data-keys="recaptcha_key2,recaptcha_secret2"><?php esc_html_e( 'Use reCAPTCHA v2', 'botblocker-security' ); ?></button>
							<span class="bbcs-field-desc" id="bbcs-recaptcha-v2-need" hidden><?php esc_html_e( 'Enter both keys above first.', 'botblocker-security' ); ?></span>
						</div>
					<?php endif; ?>
					<a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( $data->captcha_settings_url ); ?>"><?php esc_html_e( 'Change in CAPTCHA settings', 'botblocker-security' ); ?> &rarr;</a>
				</div>
			</div>
		</div>
	</div>
<?php
};
