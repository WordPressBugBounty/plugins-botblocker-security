<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void {
	$video_base = BOTBLOCKER_MATERIALS_URL . 'video/captcha/';
	$captcha_options = array(
		array(
			'id'          => 8,
			'title'       => __( 'Silent Auto-Verify (No Captcha)', 'botblocker-security' ),
			'description' => __( 'No visible checks. Decisions based on IP databases and threat intelligence.', 'botblocker-security' ),
			'image'       => $d->captcha_preview_img_url,
			'video'       => '',
			'recommended' => true,
		),
		array(
			'id'          => 1,
			'title'       => __( 'Color Buttons', 'botblocker-security' ),
			'description' => __( 'Pick the correct color. Simple logic, hard to automate.', 'botblocker-security' ),
			'image'       => '',
			'video'       => $video_base . 'color_circles.mp4',
			'recommended' => false,
		),
		array(
			'id'          => 2,
			'title'       => __( 'BotBlocker Image Captcha', 'botblocker-security' ),
			'description' => __( 'Select matching images. Best balance of protection and UX.', 'botblocker-security' ),
			'image'       => '',
			'video'       => $video_base . 'images.mp4',
			'recommended' => false,
		),
		array(
			'id'          => 5,
			'title'       => __( 'Dynamic Shape Captcha', 'botblocker-security' ),
			'description' => __( 'Match rotating shapes. BotBlocker exclusive.', 'botblocker-security' ),
			'image'       => '',
			'video'       => $video_base . 'shapes.mp4',
			'recommended' => false,
		),
		array(
			'id'          => 6,
			'title'       => __( 'Dynamic Digit Captcha', 'botblocker-security' ),
			'description' => __( 'Simple math with moving numbers. Easy and effective.', 'botblocker-security' ),
			'image'       => '',
			'video'       => $video_base . 'digits.mp4',
			'recommended' => false,
		),
		array(
			'id'          => 7,
			'title'       => __( 'Hold Button Captcha', 'botblocker-security' ),
			'description' => __( 'Press and hold to confirm. No images or calculations.', 'botblocker-security' ),
			'image'       => '',
			'video'       => $video_base . 'hold_button.mp4',
			'recommended' => false,
		),
		array(
			'id'          => 3,
			'title'       => __( 'Google reCAPTCHA v2 with Button', 'botblocker-security' ),
			'description' => __( 'Use Google reCAPTCHA v2 together with a visible verification button.', 'botblocker-security' ),
			'image'       => BOTBLOCKER_URL . 'public/icons/google.svg',
			'video'       => '',
			'recommended' => false,
		),
		array(
			'id'          => 4,
			'title'       => __( 'Google reCAPTCHA v2', 'botblocker-security' ),
			'description' => __( 'Use Google reCAPTCHA v2 as the verification challenge.', 'botblocker-security' ),
			'image'       => BOTBLOCKER_URL . 'public/icons/google.svg',
			'video'       => '',
			'recommended' => false,
		),
	);

	if ( class_exists( 'BotBlockerCaptchaRegistry' ) ) {
		foreach ( BotBlockerCaptchaRegistry::wizardCards() as $wizard_card ) {
			// Only show addon captchas whose provider keys are configured.
			$card_id = (int) $wizard_card['id'];
			if ( ! BotBlockerCaptchaRegistry::hasKeys( $card_id ) ) {
				continue;
			}
			$captcha_options[] = array(
				'id'          => $card_id,
				'title'       => (string) $wizard_card['title'],
				'description' => (string) $wizard_card['subtitle'],
				'image'       => (string) $wizard_card['icon_url'],
				'video'       => '',
				'recommended' => false,
			);
		}
	}

	$addon_modes = apply_filters( 'bbcs_setup_wizard_captcha_modes', array() );
	foreach ( $addon_modes as $addon_mode ) {
		if ( empty( $addon_mode['id'] ) || (int) $addon_mode['id'] < 90 ) {
			continue;
		}
		$addon_mode_id = (int) $addon_mode['id'];
		// Modes registered through the captcha registry are gated by their
		// keys probe; unregistered modes pass through (filter-only providers).
		if ( class_exists( 'BotBlockerCaptchaRegistry' ) && BotBlockerCaptchaRegistry::has( $addon_mode_id ) && ! BotBlockerCaptchaRegistry::hasKeys( $addon_mode_id ) ) {
			continue;
		}
		$captcha_options[] = array(
			'id'          => $addon_mode_id,
			'title'       => isset( $addon_mode['title'] ) ? (string) $addon_mode['title'] : '',
			'description' => isset( $addon_mode['subtitle'] ) ? (string) $addon_mode['subtitle'] : '',
			'image'       => isset( $addon_mode['icon_url'] ) ? (string) $addon_mode['icon_url'] : '',
			'video'       => '',
			'recommended' => false,
		);
	}
	?>
	<div class="bbcs-wizstep" data-step="5">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Choose primary BotBlocker verification', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Select the challenge visitors see when BotBlocker needs extra proof.', 'botblocker-security' ); ?></p>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-3h bbcs-blue-card">
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'Primary verification:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'Choose one BotBlocker challenge below. Google reCAPTCHA v3, when enabled above, runs invisibly alongside it.', 'botblocker-security' ); ?></div>
		</div>

		<div class="bbcs-wizcaptcha-layout">
			<div class="bbcs-wizcaptcha-options" role="radiogroup" aria-label="<?php esc_attr_e( 'CAPTCHA type', 'botblocker-security' ); ?>">
				<?php foreach ( $captcha_options as $option ) : ?>
					<label class="bbcs-wizcaptcha-option<?php echo 8 === (int) $option['id'] ? ' is-sel' : ''; ?>" data-captcha="<?php echo (int) $option['id']; ?>">
						<input class="bbcs-wizcaptcha-radio" type="radio" name="bbcs_wizard_captcha_mode" value="<?php echo (int) $option['id']; ?>" data-captcha="<?php echo (int) $option['id']; ?>"<?php checked( 8, (int) $option['id'] ); ?> />
						<span class="bbcs-wizcaptcha-option-copy">
							<span class="bbcs-wizcaptcha-option-title"><?php echo esc_html( $option['title'] ); ?></span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="bbcs-wizcaptcha-detail-panel">
				<?php foreach ( $captcha_options as $option ) : ?>
					<div class="bbcs-wizcaptcha-detail" data-captcha-detail="<?php echo (int) $option['id']; ?>"<?php echo 8 === (int) $option['id'] ? '' : ' hidden'; ?>>
						<div class="bbcs-wizcaptcha-media">
							<?php if ( '' !== $option['video'] ) : ?>
								<video class="bbcs-wizcaptcha-video" autoplay muted loop playsinline preload="metadata">
									<source src="<?php echo esc_url( $option['video'] ); ?>" type="video/mp4">
								</video>
							<?php elseif ( '' !== $option['image'] ) : ?>
								<img class="bbcs-wizcaptcha-image" src="<?php echo esc_url( $option['image'] ); ?>" alt="<?php echo esc_attr( $option['title'] ); ?>" />
							<?php else : ?>
								<svg class="bbcs-wizcaptcha-placeholder bbcs-ico"><use href="#bbcs-i-captcha"></use></svg>
							<?php endif; ?>
						</div>
						<div class="bbcs-wizcaptcha-detail-copy">
							<h3>
								<span><?php echo esc_html( $option['title'] ); ?></span>
							<?php if ( $option['recommended'] ) : ?>
								<span class="bbcs-wizcaptcha-detail-recommended"><?php esc_html_e( 'Recommended for most sites', 'botblocker-security' ); ?></span>
							<?php endif; ?>
							</h3>
							<p><?php echo esc_html( $option['description'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
				<p class="bbcs-wizcaptcha-detail-help"><?php esc_html_e( 'You can change this choice later in Settings.', 'botblocker-security' ); ?></p>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-3h bbcs-blue-card">
			<?php $integrations_url = $d->integrations_url; ?>
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'Tip:', 'botblocker-security' ); ?></b> <?php printf( /* translators: %s: URL to Integrations settings page */ esc_html__( 'Google reCaptcha keys can also be changed later in %s.', 'botblocker-security' ), '<a class="bbcs-link" href="' . esc_url( $integrations_url ) . '">' . esc_html__( 'Integrations', 'botblocker-security' ) . '</a>' ); ?></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back5"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next5"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
		</div>
	</div>
<?php };
