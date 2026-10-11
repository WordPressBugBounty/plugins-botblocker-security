<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void {
	$provider = 'none';
	if ( $d->recaptcha_key3 !== '' || $d->recaptcha_secret3 !== '' || $d->recaptcha_check ) {
		$provider = 'v3';
	} elseif ( $d->recaptcha_key2 !== '' || $d->recaptcha_secret2 !== '' ) {
		$provider = 'v2';
	}
?>
	<div class="bbcs-wizstep" data-step="4">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Connect Google reCAPTCHA (optional additional layer)', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Set up Google verification separately from your primary BotBlocker challenge.', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizcards">
			<div class="bbcs-wizcard<?php echo $provider === 'none' ? ' is-sel' : ''; ?>" data-recaptcha-provider="none">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-ban"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'No Google reCAPTCHA', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Use BotBlocker verification only. You can add Google later.', 'botblocker-security' ); ?></div>
			</div>
			<div class="bbcs-wizcard<?php echo $provider === 'v2' ? ' is-sel' : ''; ?>" data-recaptcha-provider="v2">
				<div class="bbcs-wizcard-ic"><img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/google.svg' ); ?>" alt="" class="bbcs-info-img" /></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Connect Google reCAPTCHA v2', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Visible checkbox or challenge. Choose its BotBlocker mode on next step.', 'botblocker-security' ); ?></div>
			</div>
			<div class="bbcs-wizcard<?php echo $provider === 'v3' ? ' is-sel' : ''; ?>" data-recaptcha-provider="v3">
				<div class="bbcs-wizcard-ic"><img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/google.svg' ); ?>" alt="" class="bbcs-info-img" /></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Connect Google reCAPTCHA v3', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Invisible risk score. Runs alongside any primary CAPTCHA selected next.', 'botblocker-security' ); ?></div>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-3h" data-recaptcha-fields="v2"<?php echo $provider === 'v2' ? '' : ' hidden'; ?>>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Google reCAPTCHA v2 keys', 'botblocker-security' ); ?></div>
				<div class="bbcs-field-pair">
					<div class="bbcs-field">
						<div class="bbcs-field-label"><?php esc_html_e( 'Site key', 'botblocker-security' ); ?></div>
						<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" id="bbcs-wiz-recaptcha-key2" value="<?php echo esc_attr( $d->recaptcha_key2 ); ?>" autocomplete="off"></div>
					</div>
					<div class="bbcs-field">
						<div class="bbcs-field-label"><?php esc_html_e( 'Secret key', 'botblocker-security' ); ?></div>
						<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" id="bbcs-wiz-recaptcha-secret2" value="<?php echo esc_attr( $d->recaptcha_secret2 ); ?>" autocomplete="off"></div>
					</div>
				</div>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-3h" data-recaptcha-fields="v3"<?php echo $provider === 'v3' ? '' : ' hidden'; ?>>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Google reCAPTCHA v3 keys', 'botblocker-security' ); ?></div>
				<div class="bbcs-field-pair">
					<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $d->recaptcha_check ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $d->recaptcha_check ? 'true' : 'false'; ?>" data-recaptcha-toggle="enable"><span class="bbcs-toggle-knob"></span></button><span class="bbcs-option-label"><?php esc_html_e( 'Enable reCAPTCHA v3 protection', 'botblocker-security' ); ?></span></div>
					<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $d->recaptcha_v3_ipv6_block ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $d->recaptcha_v3_ipv6_block ? 'true' : 'false'; ?>" data-recaptcha-toggle="ipv6"><span class="bbcs-toggle-knob"></span></button><span class="bbcs-option-label"><?php esc_html_e( 'Disable reCAPTCHA for IPv6 visitors', 'botblocker-security' ); ?></span></div>
				</div>
				<div class="bbcs-field-pair">
					<div class="bbcs-field">
						<div class="bbcs-field-label"><?php esc_html_e( 'Site key', 'botblocker-security' ); ?></div>
						<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" id="bbcs-wiz-recaptcha-key3" value="<?php echo esc_attr( $d->recaptcha_key3 ); ?>" autocomplete="off"></div>
					</div>
					<div class="bbcs-field">
						<div class="bbcs-field-label"><?php esc_html_e( 'Secret key', 'botblocker-security' ); ?></div>
						<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" id="bbcs-wiz-recaptcha-secret3" value="<?php echo esc_attr( $d->recaptcha_secret3 ); ?>" autocomplete="off"></div>
					</div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e( 'Score threshold', 'botblocker-security' ); ?> - <span id="bbcs-wiz-recaptcha-threshold-value"><?php echo esc_html( $d->recaptcha_tresshold ); ?></span></div>
					<div class="bbcs-field-desc"><?php esc_html_e( 'Lower scores block more traffic; higher scores are more permissive.', 'botblocker-security' ); ?></div>
					<div class="bbcs-field-box"><input type="range" class="bbcs-range bbcs-fill" id="bbcs-wiz-recaptcha-threshold" min="0.1" max="1" step="0.1" value="<?php echo esc_attr( $d->recaptcha_tresshold ); ?>"></div>
				</div>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-4h bbcs-blue-card">
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'How this works:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'Google reCAPTCHA v3 runs alongside any primary CAPTCHA selected below. Create keys in Google reCAPTCHA, paste them above, then choose the matching Google mode on the next step.', 'botblocker-security' ); ?></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back4"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next4"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
		</div>
	</div>
<?php };
