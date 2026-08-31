<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action(
	'template_redirect',
	function (): void {
		// CRITICAL: We check the endpoint instead of the query_var.
		// We use an underscored query_var, but we also support the hyphenated form for compatibility.
		if ( ! get_query_var( 'bbcs_2fa' ) ) {
			return;
		}

		$recovery_raw = isset( $_GET['recovery'] ) ? sanitize_text_field( wp_unslash( $_GET['recovery'] ) ) : '';

		if ( ! is_user_logged_in() ) {
			if ( $recovery_raw !== '' ) {
				wp_safe_redirect( wp_login_url( home_url( '/?bbcs_2fa=1&recovery=' . rawurlencode( $recovery_raw ) ) ) );
			} else {
				wp_safe_redirect( wp_login_url() );
			}
			exit;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}

		if ( ! BotBlockerTwoFactorAuth::isRequiredForUser( $user_id ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		$auth          = BotBlockerTwoFactorAuth::instance();
		$user          = wp_get_current_user();
		$error         = '';
		$recovery_sent = false;

		$secret       = get_user_meta( $user_id, '_2fa_secret', true );
		$backup_codes = get_user_meta( $user_id, '_2fa_backup_codes', true );

		// Rate limiting check.
		$rate_limited = false;
		$_2fa_data    = get_transient( 'bbcs_2fa_attempts_' . $user_id );
		if ( $_2fa_data === false ) {
			$attempts_left = 5;
		} elseif ( is_array( $_2fa_data ) ) {
			$attempts_left = max( 0, 5 - (int) $_2fa_data['count'] );
		} else {
			$attempts_left = max( 0, 5 - (int) $_2fa_data );
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		// One-time recovery email link (?recovery=TOKEN).
		if ( $recovery_raw !== '' ) {
			if ( BotBlockerTwoFactorAuth::handleRecoveryForUser( $user_id, $recovery_raw ) ) {
				wp_safe_redirect( home_url( '/?bbcs_2fa_setup=1' ) );
				exit;
			}
			$error = __( 'Invalid or expired recovery link.', 'botblocker-security' );
		}

		// "Lost access?" form: rate-limited recovery email.
		if ( $request_method === 'POST' && isset( $_POST['bbcs_2fa_recovery'] ) ) {
			$nonce_raw = isset( $_POST['bbcs_2fa_recovery_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_2fa_recovery_nonce'] ) ) : '';
			if ( empty( $nonce_raw ) || ! wp_verify_nonce( $nonce_raw, 'bbcs_2fa_recovery' ) ) {
				$error = __( 'Security error. Refresh the page and try again.', 'botblocker-security' );
			} else {
				$recovery_sent = BotBlockerTwoFactorAuth::sendRecoveryEmail( $user_id );
			}
		}

		$code_raw = filter_input( INPUT_POST, 'code', FILTER_UNSAFE_RAW );
		if ( $code_raw !== null ) {
			$code_raw = wp_unslash( $code_raw );
		}

		if ( $request_method === 'POST' && $code_raw !== null ) {

			$nonce_raw = isset( $_POST['bbcs_2fa_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_2fa_nonce'] ) ) : '';
			if ( empty( $nonce_raw ) || ! wp_verify_nonce( $nonce_raw, 'bbcs_2fa_verify' ) ) {
				$error = __( 'Security error. Refresh the page and try again.', 'botblocker-security' );
			} elseif ( ! BotBlockerTwoFactorAuth::checkRateLimit( $user_id ) ) {
				$rate_limited = true;
				$error        = __( 'Too many attempts. Try again in 5 minutes.', 'botblocker-security' );
			} else {
				$code          = sanitize_text_field( $code_raw );
				$code_verified = false;
				$verified_via  = BotBlockerTwoFactorAuth::VERIFIED_VIA_TOTP;

				// Verify the TOTP code (6 digits)
				if ( strlen( $code ) === 6 && ctype_digit( $code ) ) {
					if ( $auth->verifyCode( $secret, $code ) ) {
						$code_verified = true;
					}
				}

				// Verify the backup codes (16-character hex)
				if ( ! $code_verified && ! empty( $backup_codes ) && strlen( $code ) > 6 ) {
					$code_trim = strtoupper( $code );
					$found     = BotBlockerTwoFactorAuth::verifyBackupCode( $code_trim, $backup_codes );
					if ( $found !== false ) {
						$code_verified = true;
						$verified_via  = BotBlockerTwoFactorAuth::VERIFIED_VIA_BACKUP_CODE;
						// Delete the used backup code (supporting both hashes and plaintext)
						unset( $backup_codes[ $found ] );
						update_user_meta( $user_id, '_2fa_backup_codes', array_values( $backup_codes ) );
					}
				}

				if ( $code_verified ) {
					delete_user_meta( $user_id, '_2fa_pending' );
					BotBlockerTwoFactorAuth::resetRateLimit( $user_id );

					do_action( 'bbcs_2fa_verified', $user_id, $verified_via );

					if ( isset( $_POST['bbcs_2fa_remember'] ) ) {
						BotBlockerTwoFactorAuth::setTrustedDevice( $user_id );
					}

					// Session cookie regeneration
					wp_set_auth_cookie( $user_id, true );

					// Redirect to the saved URL
					$redirect_to = get_user_meta( $user_id, '_2fa_redirect_to', true );
					delete_user_meta( $user_id, '_2fa_redirect_to' );

					wp_safe_redirect( $redirect_to ?: admin_url() );
					exit;
				} else {
					$error = __( 'Invalid verification code.', 'botblocker-security' );
					do_action( 'bbcs_2fa_verification_failed', $user_id );
					$attempts_left--;
				}
			}
		}

		// Handle user-initiated 2FA reset (fully authenticated sessions only).
		if ( isset( $_GET['bbcs_2fa_reset'] ) && 'none' === BotBlockerTwoFactorAuth::getPendingState( $user_id ) ) {

			if (
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bbcs_2fa_reset' )
			) {
				wp_die( esc_html__( 'Security check failed.', 'botblocker-security' ) );
			}

			// Full reset of user's 2FA data
			delete_user_meta( $user_id, '_2fa_secret' );
			delete_user_meta( $user_id, '_2fa_backup_codes' );
			delete_user_meta( $user_id, '_2fa_verified' );
			delete_user_meta( $user_id, '_2fa_pending' );
			delete_user_meta( $user_id, '_2fa_redirect_to' );

			delete_user_meta( $user_id, '_2fa_secret_temp' );
			delete_user_meta( $user_id, '_2fa_backup_codes_temp' );

			delete_transient( 'bbcs_2fa_attempts_' . $user_id );

			BotBlockerTwoFactorAuth::revokeAllTrustedDevices( $user_id );

			do_action( 'bbcs_2fa_reset', $user_id );

			// Generate new secret and backup codes
			$new_secret = $auth->createSecret();
			update_user_meta( $user_id, '_2fa_secret_temp', $new_secret );
			update_user_meta( $user_id, '_2fa_backup_codes_temp', BotBlockerTwoFactorAuth::generateBackupCodes() );
			update_user_meta( $user_id, '_2fa_setup_pending', 1 );

			wp_safe_redirect( home_url( '/?bbcs_2fa_setup=1' ) );
			exit;
		}

		// Counting remaining backup codes
		$backup_codes_count = is_array( $backup_codes ) ? count( $backup_codes ) : 0;

		// enqueue stylesheet for this setup page
		$css_path = BOTBLOCKER_URL . 'public/css/bbcs-2fa.css';
		$css_ver  = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : filemtime( WP_PLUGIN_DIR . '/botblocker-security/public/css/bbcs-2fa.css' );
		// wp_enqueue_style('bbcs-2fa', $css_path, array(), $css_ver);

		?>
	<!DOCTYPE html>
	<html>

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php esc_html_e( 'Two-Factor Authentication', 'botblocker-security' ); ?></title>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
		<link rel="stylesheet" href="<?php echo esc_url( $css_path ); ?>?ver=<?php echo esc_attr( $css_ver ); ?>">
		<?php //wp_head(); ?>
	</head>

	<body class="bbcs-2fa">
		<div class="bbcs-2fa-container">
			<div class="bbcs-2fa-header">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage 
				?>
				<?php echo '<img src="' . esc_url( BOTBLOCKER_URL . 'admin/img/logo-small-transparent.webp' ) . '" height="50" alt="' . esc_attr( BOTBLOCKER_SHORT_NAME ) . '">'; ?>
				<h1><?php esc_html_e( 'BotBlocker Security', 'botblocker-security' ); ?></h1>
				<h3>&#x1F512; <?php esc_html_e( 'Two-Factor Authentication', 'botblocker-security' ); ?></h3>
				<p><?php esc_html_e( 'Enter the code to confirm login', 'botblocker-security' ); ?></p>
			</div>

			<div class="bbcs-2fa-content">
				<?php if ( $rate_limited ) : ?>
					<div class="bbcs-2fa-error"><?php echo esc_html( $error ); ?></div>
				<?php else : ?>
					<?php if ( $error ) : ?>
						<div class="bbcs-2fa-error"><?php echo esc_html( $error ); ?></div>
					<?php endif; ?>

					<?php if ( $attempts_left < 5 && $attempts_left > 0 ) : ?>
						<div class="bbcs-2fa-rate-limit-warning">
							<?php /* translators: %s: number of remaining verification attempts */ ?>
							<?php printf( esc_html__( 'Attempts left: %s', 'botblocker-security' ), esc_html( (string) $attempts_left ) ); ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<div class="bbcs-2fa-info-box">
					<p>
						<?php esc_html_e( 'Open Google Authenticator or another TOTP app and enter the 6-digit code.', 'botblocker-security' ); ?>
					</p>
					<?php if ( $backup_codes_count > 0 ) : ?>
						<p class="bbcs-2fa-backup-info">
							<?php /* translators: %s: number of remaining backup codes */ ?>
							<?php printf( esc_html__( 'Or use a backup code (remaining: %s)', 'botblocker-security' ), esc_html( (string) $backup_codes_count ) ); ?>
						</p>
					<?php else : ?>
						<p style="color: #dc2626; font-weight: 600;">
							<?php esc_html_e( 'No backup codes remaining.', 'botblocker-security' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<form method="POST">
					<?php wp_nonce_field( 'bbcs_2fa_verify', 'bbcs_2fa_nonce' ); ?>
					<div class="bbcs-2fa-form-group">
						<label class="bbcs-2fa-label"><?php esc_html_e( 'Verification Code', 'botblocker-security' ); ?></label>
						<input
							type="text"
							name="code"
							class="bbcs-2fa-code"
							maxlength="16"
							required
							autofocus
							placeholder="000000"
							<?php echo $rate_limited ? 'disabled' : ''; ?>>
					</div>
					<label class="bbcs-2fa-remember">
						<input type="checkbox" name="bbcs_2fa_remember" value="1" <?php echo $rate_limited ? 'disabled' : ''; ?>>
						<span><?php esc_html_e( 'Remember this device for 30 days', 'botblocker-security' ); ?></span>
					</label>
					<button type="submit" class="bbcs-2fa-btn-submit" <?php echo $rate_limited ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'Verify', 'botblocker-security' ); ?>
					</button>
				</form>

				<?php if ( ! empty( $user->user_email ) ) : ?>
					<div class="bbcs-2fa-recovery-link">
						<?php if ( $recovery_sent ) : ?>
							<span class="bbcs-2fa-recovery-sent"><?php esc_html_e( 'Recovery email sent. Check your inbox.', 'botblocker-security' ); ?></span>
						<?php else : ?>
							<form method="POST" class="bbcs-2fa-recovery-form">
								<?php wp_nonce_field( 'bbcs_2fa_recovery', 'bbcs_2fa_recovery_nonce' ); ?>
								<button type="submit" name="bbcs_2fa_recovery" value="1" class="bbcs-2fa-recovery-btn"><?php esc_html_e( 'Lost access? Send recovery email', 'botblocker-security' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="bbcs-2fa-help-text">
					<?php esc_html_e( 'Enter a 6-digit TOTP code', 'botblocker-security' ); ?><br>
					<?php esc_html_e( 'or a 16-character backup code', 'botblocker-security' ); ?>
				</div>

				<div class="bbcs-2fa-logout-link">
					<a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php esc_html_e( 'Log out', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>
	</body>
	</html>
		<?php
		exit;
	}
);

BotBlockerTwoFactorAuth::markPagesLoaded();
