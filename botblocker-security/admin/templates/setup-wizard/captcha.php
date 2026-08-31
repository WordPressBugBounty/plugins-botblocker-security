<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="4">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'How to verify suspicious visitors?', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Choose a verification method for questionable traffic', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizcards" style="grid-template-columns:repeat(auto-fit, minmax(300px, 1fr))">
			<div class="bbcs-wizcard is-sel" data-captcha="8">
				<div class="bbcs-wizcaptcha-preview"><img src="<?php echo esc_url( $d->captcha_preview_img_url ); ?>" alt="<?php esc_attr_e( 'Silent Auto-Verify', 'botblocker-security' ); ?>" style="width:100%;height:100%;object-fit:cover" /></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Silent Auto-Verify (No Captcha)', 'botblocker-security' ); ?> <span class="bbcs-pill bbcs-pill--green bbcs-pill--pro"><?php esc_html_e( 'Recommended', 'botblocker-security' ); ?></span></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'No visible checks. Decisions based on IP databases and threat intelligence.', 'botblocker-security' ); ?></div>
			</div>

			<div class="bbcs-wizcard" data-captcha="1">
				<div class="bbcs-wizcaptcha-preview">
					<video class="bbcs-wizcaptcha-video" autoplay muted loop playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover">
						<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/color_circles.mp4' ); ?>" type="video/mp4">
					</video>
				</div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Color Buttons', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Pick the correct color. Simple logic, hard to automate.', 'botblocker-security' ); ?></div>
			</div>

			<div class="bbcs-wizcard" data-captcha="2">
				<div class="bbcs-wizcaptcha-preview">
					<video class="bbcs-wizcaptcha-video" autoplay muted loop playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover">
						<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/images.mp4' ); ?>" type="video/mp4">
					</video>
				</div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'BotBlocker Image Captcha', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Select matching images. Best balance of protection and UX.', 'botblocker-security' ); ?></div>
			</div>

			<div class="bbcs-wizcard" data-captcha="5">
				<div class="bbcs-wizcaptcha-preview">
					<video class="bbcs-wizcaptcha-video" autoplay muted loop playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover">
						<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/shapes.mp4' ); ?>" type="video/mp4">
					</video>
				</div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Dynamic Shape Captcha', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Match rotating shapes. BotBlocker exclusive.', 'botblocker-security' ); ?></div>
			</div>

			<div class="bbcs-wizcard" data-captcha="6">
				<div class="bbcs-wizcaptcha-preview">
					<video class="bbcs-wizcaptcha-video" autoplay muted loop playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover">
						<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/digits.mp4' ); ?>" type="video/mp4">
					</video>
				</div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Dynamic Digit Captcha', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Simple math with moving numbers. Easy and effective.', 'botblocker-security' ); ?></div>
			</div>

			<div class="bbcs-wizcard" data-captcha="7">
				<div class="bbcs-wizcaptcha-preview">
					<video class="bbcs-wizcaptcha-video" autoplay muted loop playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover">
						<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/hold_button.mp4' ); ?>" type="video/mp4">
					</video>
				</div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'Hold Button Captcha', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Press and hold to confirm. No images or calculations.', 'botblocker-security' ); ?></div>
			</div>

			<?php
			// Standardized cards for ACTIVE captcha addons: title/subtitle/icon
			// come from the addon manifest (captcha.modes[].wizard) + package.
			if ( class_exists( 'BotBlockerCaptchaRegistry' ) ) {
				foreach ( BotBlockerCaptchaRegistry::wizardCards() as $wizard_card ) {
					?>
					<div class="bbcs-wizcard" data-captcha="<?php echo (int) $wizard_card['id']; ?>">
						<div class="bbcs-wizcaptcha-preview">
							<?php if ( '' !== $wizard_card['icon_url'] ) : ?>
								<img src="<?php echo esc_url( $wizard_card['icon_url'] ); ?>" alt="<?php echo esc_attr( $wizard_card['title'] ); ?>" style="width:100%;height:100%;object-fit:cover" />
							<?php endif; ?>
						</div>
						<div class="bbcs-wizcard-title"><?php echo esc_html( $wizard_card['title'] ); ?></div>
						<div class="bbcs-wizcard-sub"><?php echo esc_html( $wizard_card['subtitle'] ); ?></div>
					</div>
					<?php
				}
			}

			$addon_modes = apply_filters( 'bbcs_setup_wizard_captcha_modes', array() );
			foreach ( $addon_modes as $addon_mode ) {
				if ( empty( $addon_mode['id'] ) || (int) $addon_mode['id'] < 90 ) {
					continue;
				}
				?>
				<div class="bbcs-wizcard" data-captcha="<?php echo (int) $addon_mode['id']; ?>">
					<div class="bbcs-wizcaptcha-preview">
						<?php if ( ! empty( $addon_mode['icon_url'] ) ) : ?>
							<img src="<?php echo esc_url( $addon_mode['icon_url'] ); ?>" alt="<?php echo esc_attr( $addon_mode['title'] ); ?>" style="width:100%;height:100%;object-fit:cover" />
						<?php endif; ?>
					</div>
					<div class="bbcs-wizcard-title"><?php echo esc_html( $addon_mode['title'] ); ?></div>
					<div class="bbcs-wizcard-sub"><?php echo esc_html( $addon_mode['subtitle'] ); ?></div>
				</div>
				<?php
			}
			?>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-3h bbcs-blue-card">
			<?php
			$integrations_url = $d->integrations_url;
			?>
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'Tip:', 'botblocker-security' ); ?></b> <?php printf( /* translators: %s: URL to Integrations settings page */ esc_html__( 'Google reCaptcha v2/v3 is also supported. Key configuration is in %s.', 'botblocker-security' ), '<a class="bbcs-link" href="' . esc_url( $integrations_url ) . '">' . esc_html__( 'Integrations', 'botblocker-security' ) . '</a>' ); ?></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back4"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next4"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
		</div>
	</div>
<?php };
