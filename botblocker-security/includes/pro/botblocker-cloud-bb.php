<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_handleBotblockerPro(): void {
	add_action(
		'template_redirect',
		function () {
			if ( get_query_var( 'botblocker_cloud_api' ) == '1' ) {
				/* phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.PHP.DevelopmentFunctions.error_log_error_log */
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					error_log( '[BBCS DEBUG] [Cloud] BotBlocker cloud api activation requested.' );
					error_log( '[BBCS DEBUG] [Cloud] Email: ' . ( isset( $_GET['email'] ) ? sanitize_text_field( wp_unslash( $_GET['email'] ) ) : 'Not provided' ) );
					error_log( '[BBCS DEBUG] [Cloud] API Key: ' . ( isset( $_GET['cloud_api_key'] ) ? sanitize_text_field( wp_unslash( $_GET['cloud_api_key'] ) ) : 'Not provided' ) );
					error_log( '[BBCS DEBUG] [Cloud] API Secret: ' . ( isset( $_GET['cloud_api_secret'] ) ? sanitize_text_field( wp_unslash( $_GET['cloud_api_secret'] ) ) : 'Not provided' ) );
					error_log( '[BBCS DEBUG] [Cloud] Cloud API Expired: ' . ( isset( $_GET['cloud_api_expired'] ) ? absint( wp_unslash( $_GET['cloud_api_expired'] ) ) : 'Not provided' ) );
					error_log( '[BBCS DEBUG] [Cloud] Licence tier: ' . ( isset( $_GET['cloud_api_tier'] ) ? sanitize_text_field( wp_unslash( $_GET['cloud_api_tier'] ) ) : 'Not provided' ) );
				}
				/* phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_error_log */
				/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
				// External API endpoint from trusted server. Nonce not applicable.
				// Auth: X-BotBlocker-Secret = md5(email . api_key . api_secret . 'BB')
				$header_secret = isset( $_SERVER['HTTP_X_BOTBLOCKER_SECRET'] )
				? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_BOTBLOCKER_SECRET'] ) )
				: '';
				if ( $header_secret === '' ) {
					wp_send_json_error( 'Unauthorized: missing secret header.', 403 );
					return;
				}
				if ( isset( $_GET['cloud_api_expired'] ) && absint( wp_unslash( $_GET['cloud_api_expired'] ) ) === 1 ) {
					$stored        = BotBlockerPro::getSettings();
					$stored_email  = isset( $stored['cloud_api_email'] ) ? (string) $stored['cloud_api_email'] : '';
					$stored_key    = isset( $stored['cloud_api_key'] ) ? (string) $stored['cloud_api_key'] : '';
					$stored_secret = isset( $stored['cloud_api_secret'] ) ? (string) $stored['cloud_api_secret'] : '';
					if ( $stored_email === '' || $stored_key === '' || $stored_secret === '' ) {
						wp_send_json_error( 'Unauthorized: no stored credentials.', 403 );
						return;
					}
					$expected = md5( $stored_email . $stored_key . $stored_secret . 'BB' );
					if ( ! hash_equals( $expected, $header_secret ) ) {
						wp_send_json_error( 'Unauthorized: invalid secret.', 403 );
						return;
					}
					BotBlockerMultisite::forEachSite(
						function ( $site_id ) {
							BotBlockerCache::clearTransients();
							BotBlockerAlerts::setCloudApiExpired();
							BotBlockerPro::reset();
						}
					);
					wp_send_json_success( array( 'message' => 'Cloud API expired alert set.' ) );
					return;
				}
				if ( isset( $_GET['email'] ) && isset( $_GET['cloud_api_key'] ) && isset( $_GET['cloud_api_secret'] ) ) {
					$email               = sanitize_text_field( wp_unslash( $_GET['email'] ) );
					$api_key             = sanitize_text_field( wp_unslash( $_GET['cloud_api_key'] ) );
					$api_secret          = sanitize_text_field( wp_unslash( $_GET['cloud_api_secret'] ) );
					$expected_activation = md5( $email . $api_key . $api_secret . 'BB' );
					if ( ! hash_equals( $expected_activation, $header_secret ) ) {
						wp_send_json_error( 'Unauthorized: invalid secret.', 403 );
						return;
					}
					$cloud_api_tier = isset( $_GET['cloud_api_tier'] ) ? sanitize_text_field( wp_unslash( $_GET['cloud_api_tier'] ) ) : '';
					if ( ! BotBlockerPro::isValidTier( $cloud_api_tier ) ) {
						$cloud_api_tier = '';
					}
					$bbcs_propagate = array(
						'cloud_api_type'   => BBCS_CLOUD_TYPE_EXTENDED,
						'cloud_api_email'  => $email,
						'cloud_api_key'    => $api_key,
						'cloud_api_secret' => $api_secret,
						'cloud_api_tier'   => $cloud_api_tier,
						'check'            => 1,
					);
					if ( $cloud_api_tier !== BBCS_CLOUD_TIER_ULTIMATE ) {
						$bbcs_propagate['force_cloud_validation'] = 0;
					}
					BotBlockerMultisite::syncCloudSettingsNetwork( $bbcs_propagate );
					wp_send_json_success( array( 'message' => 'Cloud API activated successfully' ) );
				} else {
					wp_die( 'Invalid or missing parameters', 'Error', array( 'response' => 400 ) );
				}
				/* phpcs:enable WordPress.Security.NonceVerification.Recommended */
			}
		}
	);
}

// Cloud API functions have been moved to BotBlockerPro class.
// See: includes/class-botblocker-pro.php
