<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
   
<div class="tab-pane container fade" id="tls_fingerprint"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
			<div class="bbcs-info-inner">
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/security.svg' ); ?>" 
					alt="<?php esc_attr_e( 'TLS Fingerprinting', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'TLS fingerprinting detects bots by analyzing the TLS handshake signature (JA3/JA4). Real browsers have distinct TLS fingerprints vs headless/automation tools.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Requires a web server module (nginx, HAProxy, LiteSpeed) or Cloudflare (Business+) to pass the fingerprint via HTTP header.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="https://github.com/FoxIO-LLC/ja4" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'JA4 Spec', 'botblocker-security' ); ?></a>
					<a href="https://ja3er.com/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'JA3 DB', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'TLS Fingerprint Settings', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="tls_fingerprint_check" value="1" <?php checked( 1, isset( $bbcs_settings['tls_fingerprint_check'] ) ? (int) $bbcs_settings['tls_fingerprint_check'] : 0 ); ?>>
					<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable TLS Fingerprint Check', 'botblocker-security' ); ?></span>
				</div>				
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Enable JA3/JA4 TLS fingerprint analysis for bot detection. Requires fingerprint headers from web server.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Header Configuration', 'botblocker-security' ); ?></h3>

			<div class="bbcs_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs_label_input_input"><?php esc_html_e( 'JA3 Header Name', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'HTTP header name for JA3 fingerprint. Default: X-TLS-JA3. Cloudflare sends Cf-JA3-Fingerprint automatically.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="tls_fingerprint_header_ja3" 
						value="<?php echo isset( $bbcs_settings['tls_fingerprint_header_ja3'] ) ? esc_attr( $bbcs_settings['tls_fingerprint_header_ja3'] ) : 'X-TLS-JA3'; ?>">
				</div>
			</div>

			<div class="bbcs_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs_label_input_input"><?php esc_html_e( 'JA4 Header Name', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'HTTP header name for JA4 fingerprint. Default: X-TLS-JA4.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="tls_fingerprint_header_ja4" 
						value="<?php echo isset( $bbcs_settings['tls_fingerprint_header_ja4'] ) ? esc_attr( $bbcs_settings['tls_fingerprint_header_ja4'] ) : 'X-TLS-JA4'; ?>">
				</div>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Fingerprint Database', 'botblocker-security' ); ?></h3>		
			<div class="bbcs-button-container">
				<div class="bbcs_settings_button">
					<button type="button" id="bbcs_tls_import" class="mb-1 btn btn-xs btn-default">
						<i class="fa-solid fa-cloud-arrow-down"></i> <?php esc_html_e( 'Import JSON', 'botblocker-security' ); ?>
					</button>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Import a JSON file containing known JA3/JA4 TLS fingerprints. The file must be an array of objects with fields: fingerprint, category, ua_family, description.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_settings_button">
					<button type="button" id="bbcs_tls_clear" class="mb-1 btn btn-xs btn-default">
						<i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Clear All', 'botblocker-security' ); ?>
					</button>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Clear all known JA3/JA4 TLS fingerprints.', 'botblocker-security' ); ?>"></i>
				</div>
				<div class="bbcs_settings_button">
					<button type="button" id="bbcs_tls_sync" class="mb-1 btn btn-xs btn-default">
						<i class="fa-solid fa-sync"></i> <?php esc_html_e( 'Sync Now', 'botblocker-security' ); ?>
					</button>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Sync known JA3/JA4 TLS fingerprints from the BotBlocker server.', 'botblocker-security' ); ?>"></i>
				</div>
			</div>
		</div>
		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Trusted Proxy', 'botblocker-security' ); ?></h3>

			<div class="bbcs_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs_label_input_input"><?php esc_html_e( 'Trusted Proxy IP/CIDR', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Only accept TLS fingerprint headers from this trusted proxy IP or CIDR range. Cloudflare IPs are auto-detected.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" name="tls_fingerprint_trusted_proxy" 
						value="<?php echo isset( $bbcs_settings['tls_fingerprint_trusted_proxy'] ) ? esc_attr( $bbcs_settings['tls_fingerprint_trusted_proxy'] ) : ''; ?>"
						placeholder="<?php esc_attr_e( 'e.g. 173.245.48.0/20', 'botblocker-security' ); ?>">
				</div>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Diagnostics', 'botblocker-security' ); ?></h3>

			<div class="bbcs_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs_label_input_input"><?php esc_html_e( 'Current JA3', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Current JA3 fingerprint received from your web server or Cloudflare.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" readonly
						value="<?php echo isset( $_SERVER['HTTP_X_TLS_JA3'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TLS_JA3'] ) ) ) : ( isset( $_SERVER['HTTP_CF_JA3_FINGERPRINT'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_JA3_FINGERPRINT'] ) ) ) : esc_attr__( '(not detected)', 'botblocker-security' ) ); ?>">
				</div>
			</div>

			<div class="bbcs_input mb-2">
				<div class="bbcs_label_input_box">
					<span class="bbcs_label_input_input"><?php esc_html_e( 'Current JA4', 'botblocker-security' ); ?></span>
					<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Current JA4 fingerprint received from your web server.', 'botblocker-security' ); ?>">
					</i>
				</div>
				<div class="bbcs_text_input_inner">
					<input type="text" class="bbcs_text_input_input" readonly
						value="<?php echo isset( $_SERVER['HTTP_X_TLS_JA4'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TLS_JA4'] ) ) ) : esc_attr__( '(not detected)', 'botblocker-security' ); ?>">
				</div>
			</div>
		</div>
	</div>
</div>