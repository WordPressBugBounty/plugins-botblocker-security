<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\FieldPair;
use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\CustomSelect;
use BotBlocker\Component\TextInput;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
	$cm  = $data->get('bbcs_captcha_mode', (string) BOTBLOCKER_CAPTCHA_MODE_DEFAULT);
	$img = $data->get('bbcs_captcha_img_inline', '1');
	$pkg = $data->get('bbcs_captcha_img_pack', '1');
	$wait = (string) $data->get('bbcs_captcha_wait', '30');

	$mode_options = array(
		'8' => __('Silent Auto-Verify (No Captcha)', 'botblocker-security'),
		'0' => __('Button - "I am not a robot"', 'botblocker-security'),
		'1' => __('Color Buttons', 'botblocker-security'),
		'2' => __('BotBlocker Image Captcha', 'botblocker-security'),
		'3' => __('reCaptcha v2 - "I am not a robot"', 'botblocker-security'),
		'4' => __('reCaptcha v2', 'botblocker-security'),
		'5' => __('Dynamic Shape Captcha', 'botblocker-security'),
		'6' => __('Dynamic Digit Captcha', 'botblocker-security'),
		'7' => __('Hold Button Captcha', 'botblocker-security'),
	);

	$inline_options = array(
		'1' => __('Inline Base64 (Recommended)', 'botblocker-security'),
		'0' => __('Separate Requests (Legacy)', 'botblocker-security'),
	);

	$pack_options = array(
		'1' => __('Eagle', 'botblocker-security'),
		'2' => __('Horse', 'botblocker-security'),
		'3' => __('Raccoon', 'botblocker-security'),
		'4' => __('Dog', 'botblocker-security'),
		'5' => __('Cat', 'botblocker-security'),
	);
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="captcha"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		$info = InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/Captcha.svg', __( 'BotBlocker Captcha', 'botblocker-security' ) )
			->withDescription( __( 'Choose from button, color, image, shape, digit, or reCaptcha verification methods.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/all-Captcha-types-in-botblocker-maximum-flexibility-and-reliable-protection/', __( 'BotBlocker Captcha', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/reCaptcha-v2-in-botblocker-an-additional-user-verification-method-and-how-to-set-up-keys/', __( 'reCaptcha v2', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/reCaptcha-v3-in-botblocker-user-verification-and-key-setup-guide/', __( 'reCaptcha v3', 'botblocker-security' ) )
			->withDocLink( 'https://en.wikipedia.org/wiki/Captcha', __( 'Captcha', 'botblocker-security' ) );

		$gd_modes = array( BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS, BOTBLOCKER_CAPTCHA_MODE_IMAGE );
		if ( ! $data->has_gd && in_array( (int) $cm, $gd_modes, true ) ) {
			$info->withNote(
				esc_html__( 'GD library is not enabled on this server. Image Captcha (mode 2) and Color Buttons (mode 1) will not work and will fall back to another mode.', 'botblocker-security' )
			);
		}
		$recaptcha_modes = array( BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON, BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2 );
		if ( ! $data->has_recaptcha_v2 && in_array( (int) $cm, $recaptcha_modes, true ) ) {
			$info->withNote(
				esc_html__( 'reCaptcha v2 keys are not configured.', 'botblocker-security' )
				. ' <a href="' . esc_url( $data->integrations_url . '#bbcs_recaptchav2' ) . '">'
				. esc_html__( 'Configure now', 'botblocker-security' )
				. '</a>'
			);
		}

		$info->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'BotBlocker Captcha', 'botblocker-security' ) )
				->withItems( static function () use ( $data, $mode_options, $cm, $inline_options, $img, $pack_options, $pkg, $wait ): void {
					FieldPair::make()
						->withItems( static function () use ( $mode_options, $cm, $inline_options, $img ): void {
							CustomSelect::make()
								->withName( 'bbcs_captcha_mode' )
								->withValue( $cm )
								->withOptions( $mode_options )
								->withLabel( __( 'Captcha Mode:', 'botblocker-security' ) )
								->withTooltip( __( 'Select Captcha type for visitor verification.', 'botblocker-security' ) )
								->render();

							CustomSelect::make()
								->withName( 'bbcs_captcha_img_inline' )
								->withValue( $img )
								->withOptions( $inline_options )
								->withLabel( __( 'Image Delivery Mode:', 'botblocker-security' ) )
								->withTooltip( __( 'Inline: embed images in page (recommended). Separate: load via AJAX (legacy).', 'botblocker-security' ) )
								->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $pack_options, $pkg, $wait ): void {
							CustomSelect::make()
								->withName( 'bbcs_captcha_img_pack' )
								->withValue( $pkg )
								->withOptions( $pack_options )
								->withLabel( __( 'Image Captcha Pack:', 'botblocker-security' ) )
								->withTooltip( __( 'Select image theme for Captcha.', 'botblocker-security' ) )
								->render();

							TextInput::make()
								->withName( 'bbcs_captcha_wait' )
								->withValue( $wait )
								->withType( 'number' )
								->withLabel( __( 'Captcha Timeout (seconds):', 'botblocker-security' ) )
								->withTooltip( __( 'Time allowed to complete Captcha verification.', 'botblocker-security' ) )
								->withEditable()
								->render();
						} )
						->render();

					?>
					<div class="bbcs-setgroup">
						<div class="bbcs-setgroup-head"><?php esc_html_e( 'Extended Captcha Check', 'botblocker-security' ); ?></div>
						<div class="bbcs-infocol-desc"><?php
							printf(
								wp_kses_post(
									/* translators: %s is the URL to the reCaptcha v3 integration configuration page. */
									__( 'Combine any Captcha type with reCaptcha v3. <a href="%s">Configure keys</a> in Integrations.', 'botblocker-security' )
								),
								esc_url( $data->integrations_url ) . '#bbcs_recaptchav3'
							);
						?></div>
					</div>
					<?php
				} )
				->render();
			?>
		</div>
	</div>
<?php
};
