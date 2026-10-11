<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Base;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
	$keys_ready = ! empty( $data->get( 'recaptcha_key3' ) ) && ! empty( $data->get( 'recaptcha_secret3' ) );
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="recaptcha-v3"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/google.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Google reCaptcha v3 analyzes user behavior to generate risk scores and block bots invisibly, without interrupting users.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-desc"><?php esc_html_e('Set the score threshold: lower = stricter blocking, higher = more permissive.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-doc"><a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( BotBlockerDocs::page( 'recaptcha' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('reCaptcha v3', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('recaptcha_check', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('recaptcha_check', '1') ? 'true' : 'false'; ?>" data-field="recaptcha_check"<?php echo $keys_ready ? '' : ' disabled'; ?>><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="recaptcha_check" value="<?php echo $data->is_checked('recaptcha_check', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable reCaptcha v3 protection', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'Analyze user behavior and block bots without user interaction.', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('recaptcha_v3_ipv6_block', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('recaptcha_v3_ipv6_block', '1') ? 'true' : 'false'; ?>" data-field="recaptcha_v3_ipv6_block"<?php echo $keys_ready ? '' : ' disabled'; ?>><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="recaptcha_v3_ipv6_block" value="<?php echo $data->is_checked('recaptcha_v3_ipv6_block', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Disable reCaptcha for IPv6 visitors', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'Disable reCaptcha v3 for IPv6 visitors. Google reCaptcha v3 does not support IPv6 scoring, so visitors from IPv6 addresses will bypass reCaptcha checks.', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_v3_ipv6_block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('reCaptcha v3 Site Key:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Your Google reCaptcha v3 site key for invisible bot detection.', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_key3' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="recaptcha_key3" value="<?php echo esc_attr($data->get('recaptcha_key3')); ?>"></div>
					<?php if ( empty( $data->get('recaptcha_key3') ) ) : ?>
						<div class="bbcs-field-desc"><?php esc_html_e( 'Site key not set.', 'botblocker-security' ); ?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a></div>
					<?php endif; ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('reCaptcha v3 Secret Key:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Enter your Google reCaptcha v3 secret key. This private key verifies user authenticity scores and determines bot likelihood for enhanced security.', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_secret3' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="recaptcha_secret3" value="<?php echo esc_attr($data->get('recaptcha_secret3')); ?>"></div>
					<?php if ( empty( $data->get('recaptcha_secret3') ) ) : ?>
						<div class="bbcs-field-desc"><?php esc_html_e( 'Secret key not set.', 'botblocker-security' ); ?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'Create now', 'botblocker-security' ); ?></a></div>
					<?php endif; ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label">
						<?php esc_html_e( 'reCaptcha Threshold Level', 'botblocker-security' ); ?>
						- <span id="bbcs_recaptcha_tresshold_value"><?php echo esc_html($data->get('recaptcha_tresshold', '0.5')); ?></span>
						
						<?php echo Base::help( __( 'Set the reCaptcha threshold level (0.1–1.0)', 'botblocker-security' ), BotBlockerDocs::url( 'recaptcha_tresshold' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-desc"><?php esc_html_e( 'Move the slider to adjust the threshold level', 'botblocker-security' ); ?></div>
					<div class="bbcs-field-box">
						<input type="range" class="bbcs-range bbcs-fill" id="bbcs_recaptcha_tresshold" name="recaptcha_tresshold" min="0.1" max="1" step="0.1" value="<?php echo esc_attr($data->get('recaptcha_tresshold', '0.5')); ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
};
