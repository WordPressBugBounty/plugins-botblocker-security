<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerCloudBb {

	const DOORBELL_COOLDOWN    = 360;
	const PUSH_NONCE_TRANSIENT = 'bbcs_license_push_nonce';

	private static function consumePushNonce(): void {
		$posted  = isset( $_POST['push_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['push_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- External API push token verified below.
		$pending = get_transient( self::PUSH_NONCE_TRANSIENT );
		if ( '' === $posted || ! is_string( $pending ) || ! hash_equals( $pending, $posted ) ) {
			wp_send_json_error( 'Unauthorized: push nonce mismatch.', 403 );
			return;
		}
		delete_transient( self::PUSH_NONCE_TRANSIENT );
	}

	public static function registerRewriteRules(): void {
		add_rewrite_tag( '%botblocker_cloud_api%', '([01])' );
		add_rewrite_rule( '^botblocker_cloud_api/?$', 'index.php?botblocker_cloud_api=1', 'top' );
	}

	public static function addQueryVars( array $vars ): array {
		$vars[] = 'botblocker_cloud_api';
		return $vars;
	}

	public static function parseRequest( $wp ): void {
		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */
		$path      = wp_parse_url( $request_uri, PHP_URL_PATH );
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$rel       = '/' . ltrim( substr( (string) $path, strlen( (string) $home_path ) ), '/' );
		if ( rtrim( (string) $rel, '/' ) === '/botblocker_cloud_api' ) {
			$wp->query_vars['botblocker_cloud_api'] = '1';
		}
	}

	private static function isRejected( string $api_key ): bool {
		$cloud = BotBlockerBackend::sendToCloud( array( 'cloud_api_key' => $api_key ), 'validate_api_key' );
		return is_array( $cloud ) && ! empty( $cloud['rejected'] );
	}

	public static function register(): void {
		add_action( 'init', array( self::class, 'registerRewriteRules' ) );
		add_filter( 'query_vars', array( self::class, 'addQueryVars' ) );
		add_action( 'parse_request', array( self::class, 'parseRequest' ), 0 );
		add_action(
			'template_redirect',
			function () {
				if ( get_query_var( 'botblocker_cloud_api' ) == '1' ) {
					/* phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.PHP.DevelopmentFunctions.error_log_error_log */
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						error_log( '[BBCS DEBUG] [Cloud] BotBlocker cloud api activation requested.' );
						error_log( '[BBCS DEBUG] [Cloud] License key: ' . ( isset( $_POST['cloud_api_key'] ) ? 'provided' : 'not provided' ) );
					}
					/* phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_error_log */
					/* phpcs:disable WordPress.Security.NonceVerification.Missing */
					// External API endpoint from trusted server. Nonce not applicable.
					$bbcs_push = isset( $_POST['push_nonce'] );
					if ( $bbcs_push ) {
						self::consumePushNonce();
					}
					$stored     = BotBlockerPro::getSettings();
					$stored_key = isset( $stored['cloud_api_key'] ) ? (string) $stored['cloud_api_key'] : '';
					$api_key    = $stored_key;
					if ( isset( $_POST['cloud_api_key'] ) ) {
						$api_key = sanitize_text_field( wp_unslash( $_POST['cloud_api_key'] ) );
					}
					if ( $api_key === '' ) {
						wp_send_json_error( 'Unauthorized: missing license key.', 403 );
						return;
					}
					$is_stored_key = $stored_key !== '' && hash_equals( $stored_key, $api_key );
					if ( ! $bbcs_push && ( ! $is_stored_key || ! isset( $_POST['cloud_api_key'] ) ) ) {
						// Anyone can ring without the stored key, so those rings are throttled; the owner's push never is.
						if ( get_transient( 'bbcs_cloud_doorbell_cooldown' ) ) {
							wp_send_json_error( 'Too many requests.', 429 );
							return;
						}
						set_transient( 'bbcs_cloud_doorbell_cooldown', 1, self::DOORBELL_COOLDOWN );
					}
					$cloud = BotBlockerBackend::sendToCloud( array( 'cloud_api_key' => $api_key ), 'fetch_api_key' );
					if ( is_array( $cloud ) && isset( $cloud['expired'] ) && $cloud['expired'] === 1 ) {
						if ( ! $is_stored_key ) {
							wp_send_json_error( 'Unauthorized: expired key is not the active one.', 403 );
							return;
						}
						BotBlockerMultisite::forEachSite(
							function ( $site_id ) {
								BotBlockerCache::clearTransients();
								BotBlockerPro::clearCache();
								BotBlockerAlerts::setCloudApiExpired();
								BotBlockerPro::reset();
							}
						);
						wp_send_json_success( array( 'message' => 'Cloud API expired alert set.' ) );
						return;
					}
					if ( $is_stored_key && is_array( $cloud ) && isset( $cloud['error'] ) && self::isRejected( $api_key ) ) {
						require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-cloud-api-hooks.php';
						BotBlockerCloudApiHooks::disconnect();
						wp_send_json_success( array( 'message' => 'Cloud API disconnected.' ) );
						return;
					}
					if ( ! is_array( $cloud ) || isset( $cloud['error'] ) || empty( $cloud['api_key'] ) || empty( $cloud['api_secret'] ) ) {
						wp_send_json_error( 'Unauthorized: license not confirmed by cloud.', 403 );
						return;
					}
					if ( ! is_string( $cloud['api_key'] ) || ! hash_equals( $api_key, $cloud['api_key'] ) ) {
						wp_send_json_error( 'Unauthorized: cloud answered for a different key.', 403 );
						return;
					}
					$cloud_api_tier = isset( $cloud['tier'] ) ? sanitize_text_field( (string) $cloud['tier'] ) : '';
					if ( ! BotBlockerPro::isValidTier( $cloud_api_tier ) ) {
						$cloud_api_tier = '';
					}
					if ( isset( $cloud['hits'], $cloud['days'] ) ) {
						BotBlockerPro::setRemainingHits( $cloud['hits'] );
						BotBlockerPro::setRemainingDays( $cloud['days'] );
					}
					$bbcs_propagate = array(
						'cloud_api_type'   => BotBlockerPro::TYPE_EXTENDED,
						'cloud_api_email'  => sanitize_text_field( (string) ( $cloud['email'] ?? '' ) ),
						'cloud_api_key'    => sanitize_text_field( (string) $cloud['api_key'] ),
						'cloud_api_secret' => sanitize_text_field( (string) $cloud['api_secret'] ),
						'cloud_api_tier'   => $cloud_api_tier,
						'check'            => 1,
					);
					if ( $cloud_api_tier !== BotBlockerPro::TIER_ULTIMATE ) {
						$bbcs_propagate['force_cloud_validation'] = 0;
					}
					BotBlockerMultisite::syncCloudSettingsNetwork( $bbcs_propagate );
					wp_send_json_success( array( 'message' => 'Cloud API activated successfully' ) );
					/* phpcs:enable WordPress.Security.NonceVerification.Missing */
				}
			}
		);
	}
}
