<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_IntegrationsViewModel $data, bool $isActive ): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="bbcs-2fa"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/qrcode.svg' ); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e( 'Two-Factor Authentication (2FA) requires a time-based code from your authenticator app in addition to your password. This prevents unauthorized access even if your password is compromised. You can set which user roles require 2FA and generate backup codes for emergency access.', 'botblocker-security' ); ?></div>
				<div class="bbcs-doclist">
					<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span><?php esc_html_e( 'Documentation', 'botblocker-security' ); ?></div>
					<a href="https://developer.wordpress.org/advanced-administration/security/mfa/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'WordPress 2FA Documentation', 'botblocker-security' ); ?></a>
					<a href="https://www.authy.com/what-is-2fa/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'Understanding 2FA Security', 'botblocker-security' ); ?></a>
					<a href="https://support.google.com/accounts/answer/1066447" target="_blank" rel="noopener noreferrer" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'Google Authenticator Setup Guide', 'botblocker-security' ); ?></a>
					<a href="https://apps.apple.com/ru/app/google-authenticator/id388497605" target="_blank" rel="noopener noreferrer" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'Google Authenticator (App Store)', 'botblocker-security' ); ?></a>
					<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" rel="noopener noreferrer" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'Google Authenticator (Google Play)', 'botblocker-security' ); ?></a>
					<a href="https://authy.com/guides/" target="_blank" rel="noopener noreferrer" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'Authy Setup Guide', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Two-Factor Authentication', 'botblocker-security' ); ?></div>
				<div class="bbcs-option bbcs-hoverbg">
					<button class="bbcs-toggle<?php echo $data->is_checked( 'bbcs_2fa_enable', '1' ) ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked( 'bbcs_2fa_enable', '1' ) ? 'true' : 'false'; ?>" data-field="bbcs_2fa_enable"><span class="bbcs-toggle-knob"></span></button>
					<input type="hidden" name="bbcs_2fa_enable" value="<?php echo $data->is_checked( 'bbcs_2fa_enable', '1' ) ? '1' : '0'; ?>">
					<span class="bbcs-option-label"><?php esc_html_e( 'Enable Two-Factor Authentication', 'botblocker-security' ); ?></span>
					<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Enable BotBlocker Two-Factor Authentication for enhanced security.', 'botblocker-security' ); ?></span></span>
				</div>
			</div>

			<?php if ( ! $data->is_2fa_verified ) : ?>
				<div id="bbcs-2fa-message"></div>
				<div class="bbcs-grid bbcs-grid--2" style="gap: var(--bbcs-sp-4); align-items: stretch;">
					<div class="bbcs-setgroup" style="display:flex; flex-direction:column;">
						<div class="bbcs-setgroup-head"><?php esc_html_e( 'Scan QR Code', 'botblocker-security' ); ?></div>
						<div style="display:flex; flex-direction:column; gap: var(--bbcs-sp-2); flex:1;">
							<div class="bbcs-field">
								<div class="bbcs-field-label"><?php esc_html_e( 'Scan with authenticator app', 'botblocker-security' ); ?></div>
								<div class="bbcs-field-box" style="justify-content: center; padding: var(--bbcs-sp-4);">
									<img src="<?php echo esc_attr( $data->qr_url ); ?>" alt="QR Code" id="bbcs-2fa-qr-code" style="max-width:180px; display:block;">
								</div>
							</div>
							<div class="bbcs-field" style="margin-top:auto; margin-bottom:0;">
								<div class="bbcs-field-label">
									<span><?php esc_html_e( 'Enter 6-digit code from app', 'botblocker-security' ); ?></span>
								</div>
								<div style="display:flex; gap: var(--bbcs-sp-2); align-items: stretch;">
									<div class="bbcs-field-box bbcs-h-40" style="flex:1;">
										<input type="text"
											class="bbcs-input bbcs-h-full"
											id="bbcs-2fa-code-input"
											name="bbcs_2fa_code"
											maxlength="6"
											pattern="[0-9]{6}"
											placeholder="000000"
											style="letter-spacing: 0.5em; text-align:center; font-family: var(--bbcs-mono);"
											autofocus
											autocomplete="off">
									</div>
									<button type="button" class="bbcs-btn bbcs-btn--pri bbcs-h-40" id="bbcs-2fa-submit-btn" style="flex: 0 0 auto; white-space: nowrap;">
										<?php esc_html_e( 'Activate', 'botblocker-security' ); ?>
									</button>
								</div>
								<div id="bbcs-2fa-code-feedback" class="bbcs-field-desc" style="display:none; color: var(--bbcs-red);"><?php esc_html_e( 'Invalid 6-digit code. Please try again.', 'botblocker-security' ); ?></div>
							</div>
						</div>
					</div>
					<div class="bbcs-setgroup" style="display:flex; flex-direction:column;">
						<div class="bbcs-setgroup-head"><?php esc_html_e( 'Backup Codes', 'botblocker-security' ); ?></div>
						<div style="display:flex; flex-direction:column; gap: var(--bbcs-sp-2); flex:1; margin-top: var(--bbcs-sp-2);">
							<?php if ( ! empty( $data->backup_codes ) ) : ?>
								<div class="bbcs-grid bbcs-grid--2" style="gap: var(--bbcs-sp-3) var(--bbcs-sp-2);">
									<?php foreach ( $data->backup_codes as $code ) : ?>
										<div class="bbcs-field-box">
											<input type="text" class="bbcs-input bbcs-input--mono" value="<?php echo esc_attr( $code ); ?>" readonly style="background: transparent; border: none; text-align:center;">
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<div class="bbcs-field" style="margin-top:auto; margin-bottom:0;">
								<div class="bbcs-field-label">
									<span><?php esc_html_e( 'Store these backup codes in a safe place. Each code can be used only once.', 'botblocker-security' ); ?></span>
								</div>
								<div style="display:flex; gap: var(--bbcs-sp-2); align-items: stretch;">
									<button type="button" id="bbcs_download_backup_codes" class="bbcs-btn bbcs-btn--pri bbcs-btn--block bbcs-h-40" style="white-space: nowrap;">
										<i class="fa-solid fa-download"></i> <?php esc_html_e( 'Download backup codes', 'botblocker-security' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $data->is_2fa_verified ) : ?>
				<div class="bbcs-setgroup">
					<div class="bbcs-setgroup-head"><?php esc_html_e( 'Reset 2FA', 'botblocker-security' ); ?></div>
					<div class="bbcs-option bbcs-hoverbg">
						<span class="bbcs-option-label">
							<?php
							echo wp_kses(
								__( '<strong>Note:</strong> After clicking "Reset 2FA", the user will need to set up 2FA again <strong>only if "Enable Two-Factor Authentication" is checked</strong>. They can do this immediately (QR code will appear) or on their next login.', 'botblocker-security' ),
								array( 'strong' => array() )
							);
							?>
						</span>
					</div>
					<div class="bbcs-field">
						<button type="button" class="bbcs-btn bbcs-btn--danger bbcs-btn--block" data-bbcs-action="reset">
							<i class="fa-solid fa-rotate-left"></i> <?php esc_html_e( 'Reset 2FA', 'botblocker-security' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>

			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Roles with 2FA', 'botblocker-security' ); ?></div>
				<?php
				if ( isset( $data->wp_roles ) && is_array( $data->wp_roles ) && count( $data->wp_roles ) > 0 ) {
					$bbcs_2fa_roles = $data->get( 'bbcs_2fa_roles', array() );
					if ( ! is_array( $bbcs_2fa_roles ) ) {
						$bbcs_2fa_roles = array();
					}
					?>
					<div class="bbcs-field-pair">
					<?php foreach ( $data->wp_roles as $role_key => $role_value ) {
						?>
						<div class="bbcs-option bbcs-hoverbg">
							<button class="bbcs-toggle<?php echo isset( $bbcs_2fa_roles[ $role_key ] ) && (string) $bbcs_2fa_roles[ $role_key ] === '1' ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo isset( $bbcs_2fa_roles[ $role_key ] ) && (string) $bbcs_2fa_roles[ $role_key ] === '1' ? 'true' : 'false'; ?>" data-field="bbcs_2fa_roles_<?php echo esc_attr( $role_key ); ?>"><span class="bbcs-toggle-knob"></span></button>
							<input type="hidden" name="bbcs_2fa_roles[<?php echo esc_attr( $role_key ); ?>]" value="<?php echo isset( $bbcs_2fa_roles[ $role_key ] ) && (string) $bbcs_2fa_roles[ $role_key ] === '1' ? '1' : '0'; ?>">
							<span class="bbcs-option-label"><?php echo esc_html( $role_value['name'] ); ?></span>
							<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php echo esc_attr( sprintf( /* translators: %s: WordPress role name (e.g., Administrator, Editor) */ __( 'Enable BotBlocker Two-Factor Authentication for %s', 'botblocker-security' ), $role_value['name'] ) ); ?></span></span>
						</div>
						<?php
					}
					?>
					</div>
				<?php }
				?>
			</div>

			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Recommended 2FA Apps', 'botblocker-security' ); ?></div>
				<div class="bbcs-field" style="display:flex; gap: var(--bbcs-sp-2); flex-wrap: wrap;">
					<a href="<?php echo esc_url( 'https://apps.apple.com/ru/app/google-authenticator/id388497605' ); ?>" class="bbcs-btn" target="_blank" rel="noopener noreferrer">
						<i class="fa-solid fa-shield-halved"></i>&nbsp;<?php esc_html_e( 'Google Authenticator (App Store)', 'botblocker-security' ); ?>
					</a>
					<a href="<?php echo esc_url( 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2' ); ?>" class="bbcs-btn" target="_blank" rel="noopener noreferrer">
						<i class="fa-solid fa-shield-halved"></i>&nbsp;<?php esc_html_e( 'Google Authenticator (Google Play)', 'botblocker-security' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
<?php
};
