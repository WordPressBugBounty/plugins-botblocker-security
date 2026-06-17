<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $bbcs_google_auth;

$bbcs_roles = wp_roles()->roles;

$bbcs_user_id = get_current_user_id();
$bbcs_user    = wp_get_current_user();

$bbcs_secret = get_user_meta( $bbcs_user_id, '_2fa_secret_temp', true );
if ( empty( $bbcs_secret ) ) {
	$bbcs_new_secret = $bbcs_google_auth->createSecret();
	$bbcs_saved      = add_user_meta( $bbcs_user_id, '_2fa_secret_temp', $bbcs_new_secret, true );
	if ( $bbcs_saved ) {
		$bbcs_secret = $bbcs_new_secret;
	} else {
		$bbcs_secret = get_user_meta( $bbcs_user_id, '_2fa_secret_temp', true );
	}
}

$bbcs_backup_codes = get_user_meta( $bbcs_user_id, '_2fa_backup_codes_temp', true );
if ( empty( $bbcs_backup_codes ) ) {
	$bbcs_new_codes      = bbcs_generate_backup_codes();
	$bbcs_saved          = add_user_meta( $bbcs_user_id, '_2fa_backup_codes_temp', $bbcs_new_codes, true );
	if ( $bbcs_saved ) {
		$bbcs_backup_codes = $bbcs_new_codes;
	} else {
		$bbcs_backup_codes = get_user_meta( $bbcs_user_id, '_2fa_backup_codes_temp', true );
	}
}

$bbcs_qr_url = $bbcs_google_auth->getQRCodeUrl( $bbcs_user->user_email, $bbcs_secret );

$bbcs_is_verified = get_user_meta( $bbcs_user_id, '_2fa_verified', true );
$bbcs_has_secret  = get_user_meta( $bbcs_user_id, '_2fa_secret', true );

$bbcs_2fa_verified = ( ! empty( $bbcs_has_secret ) && ! empty( $bbcs_is_verified ) );

if ( ! $bbcs_2fa_verified ) {
	$bbcs_secret = get_user_meta( $bbcs_user_id, '_2fa_secret_temp', true );
	if ( empty( $bbcs_secret ) ) {
		$bbcs_new_secret = $bbcs_google_auth->createSecret();
		$bbcs_saved      = add_user_meta( $bbcs_user_id, '_2fa_secret_temp', $bbcs_new_secret, true );
		if ( $bbcs_saved ) {
			$bbcs_secret = $bbcs_new_secret;
		} else {
			$bbcs_secret = get_user_meta( $bbcs_user_id, '_2fa_secret_temp', true );
		}
	}

	$bbcs_backup_codes = get_user_meta( $bbcs_user_id, '_2fa_backup_codes_temp', true );
	if ( empty( $bbcs_backup_codes ) ) {
		$bbcs_new_codes      = bbcs_generate_backup_codes();
		$bbcs_saved          = add_user_meta( $bbcs_user_id, '_2fa_backup_codes_temp', $bbcs_new_codes, true );
		if ( $bbcs_saved ) {
			$bbcs_backup_codes = $bbcs_new_codes;
		} else {
			$bbcs_backup_codes = get_user_meta( $bbcs_user_id, '_2fa_backup_codes_temp', true );
		}
	}

	$bbcs_qr_url = $bbcs_google_auth->getQRCodeUrl( $bbcs_user->user_email, $bbcs_secret );
}

?><div class="tab-pane container fade" id="bbcs_2fa">
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage 
				?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/qrcode.svg' ); ?>"
					alt="<?php esc_attr_e( 'Two-Factor Authentication', 'botblocker-security' ); ?>"
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Two-Factor Authentication (2FA) requires a time-based code from your authenticator app in addition to your password. This prevents unauthorized access even if your password is compromised. You can set which user roles require 2FA and generate backup codes for emergency access.', 'botblocker-security' ); ?>
				</p>

				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( 'https://developer.wordpress.org/advanced-administration/security/mfa/' ); ?>" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'WordPress 2FA Documentation', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( 'https://authy.com/what-is-2fa/' ); ?>" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Understanding 2FA Security', 'botblocker-security' ); ?></a>
				</div>

			</div>
		</div>

		<!-- Show the QR code and the verification form -->
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Enable Two-Factor Authentication', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mt-3 mb-3">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox"
						role="switch"
						name="bbcs_2fa_enable"
						value="1"
						<?php
						checked( 1, isset( $bbcs_settings['bbcs_2fa_enable'] ) ? $bbcs_settings['bbcs_2fa_enable'] : 1 );
						?>
						>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable Two-Factor Authentication', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip"
					data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php echo esc_attr( 'Enable BotBlocker Two-Factor Authentication for enhanced security.' ); ?>">
				</i>
			</div>

			<div class="bbcs-2fa-verified" <?php echo ( $bbcs_2fa_verified ) ? ' style="display: none;"' : ''; ?>>
				<!-- QR Code Section -->
				<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Scan QR Code', 'botblocker-security' ); ?></h3>
				<div id="bbcs-2fa-message"></div>
				<div class="bbcs_checkbox_input mb-2">
					<div class="d-flex justify-content-center w-100 mb-3">
						<img src="<?php echo esc_attr( $bbcs_qr_url ); ?>"
							alt="QR Code"
							id="bbcs_2fa_qr-code"
							class="img-fluid border"
							style="max-width:180px;">
					</div>
				</div>
				<div class="row align-items-end g-2">
					<div class="col-md-12">
						<div class="bbcs_label_input_box">
							<span class="bbcs-label-input"><?php esc_html_e( 'Enter 6-digit code from app:', 'botblocker-security' ); ?></span>
							<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_html_e( 'Enter 6-digit code from app', 'botblocker-security' ); ?>"></i>
						</div>

						<div class="bbcs_text_input_inner">
							<input type="text"
								class="bbcs_text_input_input text-center"
								id="bbcs_2fa_code_input"
								name="bbcs_2fa_code"
								maxlength="6"
								pattern="[0-9]{6}"
								placeholder="000000"
								style="letter-spacing: 0.5em;"
								autofocus
								autocomplete="off">
							<div class="invalid-feedback text-center mt-1">
								<?php esc_html_e( 'Invalid 6-digit code. Please try again.', 'botblocker-security' ); ?>
							</div>
						</div>
					</div>

					<div class="col-md-12">
						<button type="button"
							class="btn btn-success w-100"
							id="bbcs_2fa_submit_btn"
							name="bbcs_2fa_submit_btn"
							value="Verify 2FA">
							<i class="fas fa-check me-1"></i>
							<?php esc_html_e( 'Activate', 'botblocker-security' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Reset 2FA -->
			<div class="bbcs-2fa-reset" <?php echo ( ! $bbcs_2fa_verified ) ? ' style="display: none;"' : ''; ?>>
				<h3 class="bbcs_settings_h3 mt-3">
					<?php esc_html_e( 'Reset 2FA for user', 'botblocker-security' ); ?>
				</h3>

				<p class="bbcs-info-text">
				<?php
				echo wp_kses(
					__( '<strong>Note:</strong> After clicking "Reset 2FA", the user will need to set up 2FA again <strong>only if "Enable Two-Factor Authentication" is checked</strong>. They can do this immediately (QR code will appear) or on their next login.', 'botblocker-security' ),
					array( 'strong' => array() )
				);
				?>
											</p>

				<div class="col-md-12">
					<button type="button" class="btn btn-danger w-100" data-bbcs-action="reset">
						<i class="fas fa-redo me-2"></i>
						<?php esc_html_e( 'Reset 2FA', 'botblocker-security' ); ?>
					</button>
				</div>
			</div>

			<div class="bbcs-2fa-reset" <?php echo ( ! $bbcs_2fa_verified ) ? ' style="display: none;"' : ''; ?>>
				<h3 class="bbcs_settings_h3 mt-3">
					<?php esc_html_e( 'Recommended 2FA Apps', 'botblocker-security' ); ?>
				</h3>
				<a href="<?php echo esc_url( 'https://apps.apple.com/ru/app/google-authenticator/id388497605' ); ?>"
					class="btn btn-xs"
					target="_blank"
					rel="noopener noreferrer">
					<i class="fa-solid fa-shield-halved"></i>&nbsp;
					<?php esc_html_e( 'Google Authenticator (App Store)', 'botblocker-security' ); ?>
				</a>

				<a href="<?php echo esc_url( 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2' ); ?>"
					class="btn btn-xs"
					target="_blank"
					rel="noopener noreferrer">
					<i class="fa-solid fa-shield-halved"></i>&nbsp;
					<?php esc_html_e( 'Google Authenticator (Google Play)', 'botblocker-security' ); ?>
				</a>
			</div>
		</div>

		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Roles with 2FA', 'botblocker-security' ); ?></h3>
			<?php
			if ( isset( $bbcs_roles ) && is_array( $bbcs_roles ) && count( $bbcs_roles ) > 0 ) {
				foreach ( $bbcs_roles as $bbcs_role_key => $bbcs_role_value ) {
					?>
					<div class="bbcs_checkbox_input mt-3 mb-3">
						<div class="bbcs_label_checkbox_box">
							<input type="checkbox"
								role="switch"
								name="bbcs_2fa_roles[<?php echo esc_attr( $bbcs_role_key ); ?>]"
								value="1"
								<?php
								checked( 1, isset( $bbcs_settings['bbcs_2fa_roles'][ $bbcs_role_key ] ) ? $bbcs_settings['bbcs_2fa_roles'][ $bbcs_role_key ] : 0 );
								?>
								>
							<span class="bbcs_label_input_checkbox"><?php echo esc_html( $bbcs_role_value['name'] ); ?></span>
						</div>
						<i class="fa-regular fa-circle-question"
							data-bs-toggle="tooltip"
							data-bs-html="true"
							data-bs-placement="top"
							<?php /* translators: %s: role display name, e.g. 'Administrator' */ ?>
							data-bs-original-title="<?php echo esc_attr( sprintf( __( 'Enable BotBlocker Two-Factor Authentication for %s', 'botblocker-security' ), $bbcs_role_value['name'] ) ); ?>">
						</i>
					</div>
					<?php
				}
			}
			?>
		
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-2fa-verified" <?php echo ( $bbcs_2fa_verified ) ? ' style="display: none;"' : ''; ?>>
			<!-- Backup Codes Section -->
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Save Backup Codes', 'botblocker-security' ); ?></h3>
			<p class="bbcs-info-text mb-3">
				<?php esc_html_e( 'Please scan the QR code using your authentication app (e.g., Google Authenticator, Authy, etc.) and store the backup codes in a safe place. Each backup code can be used only once.', 'botblocker-security' ); ?>
			</p>
			<div id="bbcs-backup-codes" class="row g-2 mb-2">
				<?php foreach ( $bbcs_backup_codes as $bbcs_code ) : ?>
					<div class="col-6">
						<div class="bbcs_text_input_inner">
							<input type="text" class="bbcs_text_input_input" name="bbcs_2fa_backup_code" value="<?php echo esc_attr( $bbcs_code ); ?>" readonly="">
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="btn-group btn-group-sm w-100" role="group">
				<button type="button"
					id="bbcs_download_backup_codes"
					class="btn btn-outline-secondary">
					<i class="fas fa-download"></i>
				</button>
			</div>

			<div class="bbcs-2fa-verified" <?php echo ( $bbcs_2fa_verified ) ? ' style="display: none;"' : ''; ?>>
				<h3 class="bbcs_settings_h3 mt-3">
					<?php esc_html_e( 'Recommended 2FA Apps', 'botblocker-security' ); ?>
				</h3>
				<a href="<?php echo esc_url( 'https://apps.apple.com/ru/app/google-authenticator/id388497605' ); ?>"
					class="btn btn-xs"
					target="_blank"
					rel="noopener noreferrer">
					<i class="fa-solid fa-shield-halved"></i>&nbsp;
					<?php esc_html_e( 'Google Authenticator (App Store)', 'botblocker-security' ); ?>
				</a>

				<a href="<?php echo esc_url( 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2' ); ?>"
					class="btn btn-xs"
					target="_blank"
					rel="noopener noreferrer">
					<i class="fa-solid fa-shield-halved"></i>&nbsp;
					<?php esc_html_e( 'Google Authenticator (Google Play)', 'botblocker-security' ); ?>
				</a>
			</div>
		</div>

	</div>
</div>
