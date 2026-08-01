<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="recaptcha-v2"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/google.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Google reCaptcha v2 displays interactive puzzles to verify human users before form submission or page access.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-desc"><?php esc_html_e('Enter your Google reCaptcha v2 site and secret keys below.', 'botblocker-security'); ?></div>
				<div class="bbcs-doclist">
					<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span><?php esc_html_e('Documentation', 'botblocker-security'); ?></div><a href="<?php echo esc_url($data->docs_url); ?>/recaptcha-v2-in-botblocker-an-additional-user-verification-method-and-how-to-set-up-keys/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e('About reCaptcha v2', 'botblocker-security'); ?></a><a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e('Create reCaptcha keys', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('reCaptcha v2', 'botblocker-security'); ?></div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('reCaptcha v2 Site Key:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Enter your Google reCaptcha v2 site key obtained from Google reCaptcha console. This key is used to display the Captcha challenge on your website forms.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="recaptcha_key2" value="<?php echo esc_attr($data->get('recaptcha_key2')); ?>"></div>
					<?php if ( empty( $data->get('recaptcha_key2') ) ) : ?>
						<div class="bbcs-field-desc"><?php esc_html_e( 'Site key not set.', 'botblocker-security' ); ?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a></div>
					<?php endif; ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('reCaptcha v2 Secret Key:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Enter your Google reCaptcha v2 secret key. This private key is used to verify user responses on the server side and should be kept confidential.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="recaptcha_secret2" value="<?php echo esc_attr($data->get('recaptcha_secret2')); ?>"></div>
					<?php if ( empty( $data->get('recaptcha_secret2') ) ) : ?>
						<div class="bbcs-field-desc"><?php esc_html_e( 'Secret key not set.', 'botblocker-security' ); ?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
<?php
};
