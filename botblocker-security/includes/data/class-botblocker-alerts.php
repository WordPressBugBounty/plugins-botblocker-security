<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// core-helpers.php is also loaded by the main plugin bootstrap (botblocker-security.php:68).
// This require_once is a safety net: when BotBlockerAlerts is used in isolation
// (e.g., by external tooling, CLI scripts, or addon bootstrap), the helpers are
// guaranteed to be available without depending on the main plugin load order.
require_once BOTBLOCKER_DIR . 'core-helpers.php';

class BotBlockerAlerts {

	/** Shared transient holding all background data-sync failure alerts. */
	private const SYNC_ALERTS_KEY = 'bbcs_sync_failed_alerts';

	/**
	 * Default destination per alert type: admin page slug + tab anchor + label.
	 * Used by resolveLink() when an alert does not carry its own link.
	 *
	 * @var array<string, array{0:string,1:string,2:string}>
	 */
	private const LINK_MAP = array(
		'no_connection_bbcloud'    => array( 'bbcs_cloud_api', '', 'Go to Cloud API' ),
		'missing_files'            => array( 'bbcs_tools', '#maintenance', 'Go to Tools' ),
		'cloud_api_expired'        => array( 'bbcs_cloud_api', '', 'Go to Cloud API' ),
		'cloud_api_hits_exhausted' => array( 'bbcs_cloud_api', '', 'Go to Cloud API' ),
		'asn_db_failed'            => array( 'bbcs_tools', '#maintenance&focus=bbcs-update-asn-database', 'Go to Tools' ),
		'addon_update_failed'      => array( 'bbcs_addons', '', 'Go to Add-ons' ),
		'addon_incompatible'       => array( 'bbcs_addons', '', 'Go to Add-ons' ),
		'early_init_broken'        => array( 'bbcs_addons', '', 'Go to Add-ons' ),
		'rugov_update_failed'      => array( 'bbcs_tools', '#maintenance', 'Go to Tools' ),
		'llm_sync_failed'          => array( 'bbcs_tools', '#maintenance', 'Go to Tools' ),
		'tls_sync_failed'          => array( 'bbcs_tools', '#maintenance', 'Go to Tools' ),
		'recaptcha_keys_missing'   => array( 'bbcs_integrations', '#recaptcha-v3', 'Go to Integrations' ),
		'telegram_not_configured'  => array( 'bbcs_addons', '#bbcs-telegram', 'Go to Add-ons' ),
		'2fa_not_setup'            => array( 'bbcs_integrations', '#bbcs-2fa', 'Go to Integrations' ),
		'cache_compatibility'      => array( 'bbcs_integrations', '#cache', 'Go to Integrations' ),
		'malware_findings'         => array( 'bbcs_addons', '#bbcs-malware', 'Go to Add-ons' ),
		'ts_findings'              => array( 'bbcs_addons', '#bbcs-truth-source', 'Go to Add-ons' ),
		'redis_available'          => array( 'bbcs_integrations', '#redis', 'Go to Integrations' ),
		'memcached_available'      => array( 'bbcs_integrations', '#memcached', 'Go to Integrations' ),
		'setup_wizard_not_completed' => array( 'bbcs_setup_wizard', '', 'Go to Setup Wizard' ),
		'recaptcha_v2_keys_missing'  => array( 'bbcs_integrations', '#recaptcha-v2', 'Go to Integrations' ),
		'2fa_not_verified'           => array( 'bbcs_integrations', '#bbcs-2fa', 'Go to Integrations' ),
		'dev_mode_active'            => array( 'bbcs_tools', '#maintenance', 'Go to Tools' ),
	);

	/**
	 * Sets a background data-sync failure alert (RUGOV / LLM / TLS). All kinds
	 * share one transient container so the header pays a single read.
	 *
	 * @param string $kind    Alert kind (rugov | llm | tls).
	 * @param string $type    Alert type (must exist in LINK_MAP).
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 * @return bool
	 */
	public static function setSyncFailed( string $kind, string $type, string $title, string $message ): bool {
		$alerts = get_transient( self::SYNC_ALERTS_KEY );
		if ( ! is_array( $alerts ) ) {
			$alerts = array();
		}
		$alerts[ $kind ] = array(
			'type'    => $type,
			'icon'    => 'fas fa-database bg-warning text-light',
			'title'   => $title,
			'message' => $message,
		);
		return set_transient( self::SYNC_ALERTS_KEY, $alerts, DAY_IN_SECONDS );
	}

	/**
	 * @param string $kind Alert kind (rugov | llm | tls).
	 */
	public static function clearSyncFailed( string $kind ): void {
		$alerts = get_transient( self::SYNC_ALERTS_KEY );
		if ( ! is_array( $alerts ) ) {
			return;
		}
		unset( $alerts[ $kind ] );
		if ( empty( $alerts ) ) {
			delete_transient( self::SYNC_ALERTS_KEY );
			return;
		}
		set_transient( self::SYNC_ALERTS_KEY, $alerts, DAY_IN_SECONDS );
	}

	/**
	 * Runs every render-time config detector once and caches the combined
	 * result per user for an hour. One transient read instead of several
	 * option / user-meta reads on every admin page.
	 *
	 * @return array<int, array<string,string>>
	 */
	public static function detectConfigAlerts(): array {
		$user_id  = (int) get_current_user_id();
		$cache_key = self::configCacheKey( $user_id );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$alerts = array();
		foreach ( array(
			array( __CLASS__, 'detectRecaptchaConfig' ),
			array( __CLASS__, 'detectRecaptchaV2Config' ),
			array( __CLASS__, 'detectTelegramConfig' ),
			array( __CLASS__, 'detectSetupWizardConfig' ),
			array( __CLASS__, 'detectTwoFactorConfig' ),
			array( __CLASS__, 'detectDevMode' ),
		) as $detector ) {
			$result = call_user_func( $detector );
			if ( is_array( $result ) ) {
				$alerts[] = $result;
			}
		}
		$alerts = array_merge( $alerts, self::detectRedisMemcachedConfig() );

		set_transient( $cache_key, $alerts, HOUR_IN_SECONDS );
		return $alerts;
	}

	/**
	 * @param int|null $user_id Defaults to the current user.
	 */
	public static function invalidateConfigCache( ?int $user_id = null ): void {
		if ( $user_id === null ) {
			$user_id = (int) get_current_user_id();
		}
		delete_transient( self::configCacheKey( $user_id ) );
	}

	/**
	 * @param int $user_id
	 * @return string
	 */
	private static function configCacheKey( int $user_id ): string {
		return 'bbcs_alert_config_' . $user_id;
	}

	/**
	 * reCAPTCHA v3 is enabled but its keys are not configured - users believe
	 * captcha works while it silently falls back.
	 *
	 * @return array|null
	 */
	public static function detectRecaptchaConfig(): ?array {
		$BBCS = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		if ( $BBCS === null || ! isset( $BBCS->settings ) || ! isset( $BBCS->settings->recaptcha_check ) ) {
			return null;
		}
		if ( (int) $BBCS->settings->recaptcha_check !== 1 ) {
			return null;
		}
		$ready = class_exists( 'BotBlockerUI' ) ? BotBlockerUI::recaptcha_v3_keys_ready() : false;
		if ( $ready ) {
			return null;
		}
		return array(
			'type'    => 'recaptcha_keys_missing',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'reCaptcha v3 Keys Missing', 'botblocker-security' ),
			'message' => __( 'reCaptcha v3 protection is enabled but the site/secret keys are empty. Verification silently falls back to the simple check.', 'botblocker-security' ),
		);
	}

	/**
	 * Telegram notifications are enabled but the bot token / chat id are empty.
	 *
	 * @return array|null
	 */
	public static function detectTelegramConfig(): ?array {
		$settings = get_option( 'bbcs_telegram_settings', array() );
		if ( ! is_array( $settings ) || empty( $settings['enabled'] ) ) {
			return null;
		}
		if ( ! empty( $settings['bot_token'] ) && ! empty( $settings['chat_id'] ) ) {
			return null;
		}
		return array(
			'type'    => 'telegram_not_configured',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'Telegram Notifications Not Configured', 'botblocker-security' ),
			'message' => __( 'Telegram notifications are enabled but the bot token or chat id is missing. Alerts will not be delivered.', 'botblocker-security' ),
		);
	}

	/**
	 * Two-factor authentication is enabled but the current admin has no secret.
	 *
	 * @return array|null
	 */
	public static function detectTwoFactorConfig(): ?array {
		$BBCS = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		if ( $BBCS === null || ! isset( $BBCS->settings ) || ! isset( $BBCS->settings->bbcs_2fa_enable ) ) {
			return null;
		}
		if ( (int) $BBCS->settings->bbcs_2fa_enable !== 1 ) {
			return null;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}
		$secret = get_user_meta( $user_id, '_2fa_secret', true );
		if ( empty( $secret ) ) {
			return array(
				'type'    => '2fa_not_setup',
				'icon'    => 'fas fa-shield-halved bg-warning text-light',
				'title'   => __( 'Two-Factor Authentication Not Set Up', 'botblocker-security' ),
				'message' => __( 'Two-factor authentication is enabled but your account has no authenticator secret yet. Set it up to protect this account.', 'botblocker-security' ),
			);
		}
		if ( empty( get_user_meta( $user_id, '_2fa_verified', true ) ) ) {
			return array(
				'type'    => '2fa_not_verified',
				'icon'    => 'fas fa-shield-halved bg-warning text-light',
				'title'   => __( 'Two-Factor Authentication Not Verified', 'botblocker-security' ),
				'message' => __( 'Your authenticator secret is saved but the QR code was not verified yet. Complete the verification to actually protect this account.', 'botblocker-security' ),
			);
		}
		return null;
	}

	/**
	 * The setup wizard was never completed after activation.
	 *
	 * @return array|null
	 */
	public static function detectSetupWizardConfig(): ?array {
		$completed = method_exists( 'BotBlockerMultisite', 'getOption' )
			? (bool) BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false )
			: (bool) get_option( 'bbcs_setup_wizard_completed', false );
		if ( $completed ) {
			return null;
		}
		return array(
			'type'    => 'setup_wizard_not_completed',
			'icon'    => 'fas fa-wand-magic-sparkles bg-info text-light',
			'title'   => __( 'Setup Wizard Not Completed', 'botblocker-security' ),
			'message' => __( 'Finish the setup wizard to configure BotBlocker correctly for this site.', 'botblocker-security' ),
		);
	}

	/**
	 * reCAPTCHA v2 is selected as the captcha mode but its keys are empty.
	 *
	 * @return array|null
	 */
	public static function detectRecaptchaV2Config(): ?array {
		$BBCS = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		if ( $BBCS === null || ! isset( $BBCS->settings ) || ! isset( $BBCS->settings->bbcs_captcha_mode ) ) {
			return null;
		}
		$mode = (int) $BBCS->settings->bbcs_captcha_mode;
		$is_v2 = defined( 'BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON' ) && $mode === BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON
			|| defined( 'BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2' ) && $mode === BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2;
		if ( ! $is_v2 ) {
			return null;
		}
		$key  = isset( $BBCS->settings->recaptcha_key2 ) ? (string) $BBCS->settings->recaptcha_key2 : '';
		$sec  = isset( $BBCS->settings->recaptcha_secret2 ) ? (string) $BBCS->settings->recaptcha_secret2 : '';
		if ( ! empty( $key ) && ! empty( $sec ) ) {
			return null;
		}
		return array(
			'type'    => 'recaptcha_v2_keys_missing',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'reCaptcha v2 Keys Missing', 'botblocker-security' ),
			'message' => __( 'reCaptcha v2 is selected as the captcha mode but the site/secret keys are empty. Verification silently falls back to the simple check.', 'botblocker-security' ),
		);
	}

	/**
	 * BotBlocker runs in development mode (BOTBLOCKER_MODE=dev via .bbcdev).
	 *
	 * @return array|null
	 */
	public static function detectDevMode(): ?array {
		if ( ! defined( 'BOTBLOCKER_MODE' ) || BOTBLOCKER_MODE !== 'dev' ) {
			return null;
		}
		return array(
			'type'    => 'dev_mode_active',
			'icon'    => 'fas fa-flask bg-warning text-light',
			'title'   => __( 'Development Mode Active', 'botblocker-security' ),
			'message' => __( 'BotBlocker runs in development mode (.bbcdev marker). Remove the marker on production sites.', 'botblocker-security' ),
		);
	}

	/**
	 * Redis / Memcached extensions are loaded on the server but the matching
	 * counters are disabled. Offer to enable them (honest wording: the server
	 * itself is not pinged here - no TCP cost on every admin render).
	 *
	 * @return array<int, array<string,string>>
	 */
	public static function detectRedisMemcachedConfig(): array {
		$alerts = array();

		$BBCS = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		$settings = ( $BBCS !== null && isset( $BBCS->settings ) ) ? $BBCS->settings : null;

		if ( extension_loaded( 'redis' ) ) {
			$enabled = ( $settings !== null && isset( $settings->redis_enable ) ) ? (int) $settings->redis_enable : 0;
			if ( $enabled !== 1 ) {
				$alerts[] = array(
					'type'    => 'redis_available',
					'icon'    => 'fas fa-server bg-success text-light',
					'title'   => __( 'Redis Detected', 'botblocker-security' ),
					'message' => __( 'The Redis PHP extension is available on this server but Redis counters are disabled. Enable them if a Redis server is running.', 'botblocker-security' ),
				);
			}
		}

		if ( extension_loaded( 'memcached' ) ) {
			$enabled = ( $settings !== null && isset( $settings->memcached_enable ) ) ? (int) $settings->memcached_enable : 0;
			if ( $enabled !== 1 ) {
				$alerts[] = array(
					'type'    => 'memcached_available',
					'icon'    => 'fas fa-memory bg-success text-light',
					'title'   => __( 'Memcached Detected', 'botblocker-security' ),
					'message' => __( 'The Memcached PHP extension is available on this server but Memcached counters are disabled. Enable them if a Memcached server is running.', 'botblocker-security' ),
				);
			}
		}

		return $alerts;
	}

	/**
	 * @param array $alert
	 * @return array
	 */
	public static function resolveLink( array $alert ): array {
		if ( ! empty( $alert['link'] ) || empty( $alert['type'] ) ) {
			return $alert;
		}
		if ( isset( self::LINK_MAP[ $alert['type'] ] ) ) {
			$page               = self::LINK_MAP[ $alert['type'] ][0];
			$anchor             = self::LINK_MAP[ $alert['type'] ][1];
			$alert['link']      = method_exists( 'BotBlockerMultisite', 'getAdminPageUrl' ) ? BotBlockerMultisite::getAdminPageUrl( $page ) . $anchor : '';
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- finite internal link-label map
			$alert['link_text'] = __( self::LINK_MAP[ $alert['type'] ][2], 'botblocker-security' );
		}
		return $alert;
	}

	public static function getAll(): array {
		$alerts = array();

		$failed_alert_err = get_transient( 'bbcs_cloud_connection_failed_alert' );
		if ( ! empty( $failed_alert_err ) ) {
			$alerts[] = $failed_alert_err;
		}

		$missing_files_alert = get_transient( 'bbcs_missing_files_alert' );
		if ( ! empty( $missing_files_alert ) ) {
			$alerts[] = $missing_files_alert;
		}

		$cloud_api_expired_alert = get_transient( 'bbcs_cloud_api_expired_alert' );
		if ( ! empty( $cloud_api_expired_alert ) ) {
			$alerts[] = $cloud_api_expired_alert;
		}

		$cloud_api_hits_exhausted_alert = get_transient( 'bbcs_cloud_api_hits_exhausted_alert' );
		if ( ! empty( $cloud_api_hits_exhausted_alert ) ) {
			$alerts[] = $cloud_api_hits_exhausted_alert;
		}

		$addon_update_failed_alert = get_transient( 'bbcs_addon_update_failed_alert' );
		if ( ! empty( $addon_update_failed_alert ) ) {
			$alerts[] = $addon_update_failed_alert;
		}

		$addon_incompatible_alert = get_transient( 'bbcs_addon_incompatible_alert' );
		if ( ! empty( $addon_incompatible_alert ) ) {
			$alerts[] = $addon_incompatible_alert;
		}

		$asn_db_failed_alert = get_transient( 'bbcs_asn_db_failed_alert' );
		if ( ! empty( $asn_db_failed_alert ) ) {
			$alerts[] = $asn_db_failed_alert;
		}

		$sync_failed_alerts = get_transient( self::SYNC_ALERTS_KEY );
		if ( is_array( $sync_failed_alerts ) ) {
			foreach ( $sync_failed_alerts as $sync_alert ) {
				if ( is_array( $sync_alert ) ) {
					$alerts[] = $sync_alert;
				}
			}
		}

		// Cache plugin compatibility warnings
		$cache_alert = self::detectCacheIncompatibility();
		if ( ! empty( $cache_alert ) ) {
			$alerts[] = $cache_alert;
		}

		// Configuration gaps detected at render time (cached per user)
		foreach ( self::detectConfigAlerts() as $config_alert ) {
			$alerts[] = $config_alert;
		}

		$alerts = apply_filters( 'bbcs_alerts_collect', $alerts );

		if ( ! is_array( $alerts ) ) {
			return array();
		}

		return array_map(
			static function ( $alert ): array {
				return is_array( $alert ) ? self::resolveLink( $alert ) : array();
			},
			$alerts
		);
	}

	public static function setCustom( string $key, array $alert, int $ttl = DAY_IN_SECONDS ): bool {
		if ( $key === '' || empty( $alert['title'] ) || empty( $alert['message'] ) ) {
			return false;
		}

		$alert = wp_parse_args(
			$alert,
			array(
				'type' => 'custom',
				'icon' => 'fas fa-info-circle bg-info text-light',
			)
		);

		return set_transient( $key, $alert, max( 0, $ttl ) );
	}

	public static function setCloudConnectionFailed(): void {
		$alert = array(
			'type'    => 'no_connection_bbcloud',
			'icon'    => 'fas fa-signal bg-success text-light',
			'title'   => __( 'No connection to BotBlocker Cloud', 'botblocker-security' ),
			'message' => gmdate( 'd/m/Y' ),
		);

		set_transient( 'bbcs_cloud_connection_failed_alert', $alert, DAY_IN_SECONDS );
	}


	public static function setMissingFiles(): void {
		$alert = array(
			'type'    => 'missing_files',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'Missing Files', 'botblocker-security' ),
			'message' => __( 'Required files missing. Regenerated.', 'botblocker-security' ),
		);

		set_transient( 'bbcs_missing_files_alert', $alert, HOUR_IN_SECONDS );
	}

	public static function setCloudApiExpired( ?int $days_left = null ): void {
		/* translators: %d: number of days left before the cloud API expires. */
		$about_to_expire_message = __( 'Your cloud API will expire in %d days.', 'botblocker-security' );
		$message                 = $days_left !== null ? sprintf( $about_to_expire_message, intval( $days_left ) ) : __( 'Your cloud API has expired. Please renew it.', 'botblocker-security' );
		$alert                   = array(
			'type'    => 'cloud_api_expired',
			'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
			'title'   => __( 'Cloud API Expired', 'botblocker-security' ),
			'message' => $message,
		);

		set_transient( 'bbcs_cloud_api_expired_alert', $alert, DAY_IN_SECONDS );
	}

	public static function setCloudApiHitsExhausted( ?int $hits_left = null ): void {
		/* translators: %d: number of hits remaining before the cloud API is exhausted. */
		$low_hits_message = __( 'Your cloud API has fewer than %d hits remaining.', 'botblocker-security' );
		$message          = $hits_left !== null ? sprintf( $low_hits_message, intval( $hits_left ) ) : __( 'Your cloud API has no hits remaining. Please renew.', 'botblocker-security' );
		$alert            = array(
			'type'    => 'cloud_api_hits_exhausted',
			'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
			'title'   => __( 'Cloud API Hits Exhausted', 'botblocker-security' ),
			'message' => $message,
		);

		set_transient( 'bbcs_cloud_api_hits_exhausted_alert', $alert, DAY_IN_SECONDS );
	}

	public static function setAsnDbFailed( string $error_code = '' ): void {
		$alert = array(
			'type'    => 'asn_db_failed',
			'icon'    => 'fas fa-database bg-warning text-light',
			'title'   => __( 'ASN Database Update Failed', 'botblocker-security' ),
			'message' => $error_code !== ''
				? sprintf(
					/* translators: %s: short error code returned by the downloader. */
					__( 'Could not download the ASN database (%s). Will retry automatically.', 'botblocker-security' ),
					$error_code
				)
				: __( 'Could not download the ASN database. Will retry automatically.', 'botblocker-security' ),
		);

		set_transient( 'bbcs_asn_db_failed_alert', $alert, DAY_IN_SECONDS );
	}

	/**
	 * Detects cache plugins / server-level caches in front of the site.
	 * Returns one entry per known system:
	 *   state   - 'not_detected' | 'installed' | 'needs_config'
	 *   warning - recommendation text ('' when the system is safe/absent);
	 *             non-empty warnings feed the header alert
	 *   note    - informational text shown on the Cache tab only (never alerts)
	 *   label   - human-readable system name
	 *
	 * @return array<string, array{state:string, warning:string, note:string, label:string}>
	 */
	public static function detectCacheSystems(): array {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$systems = array(
			'wp_super_cache' => array(
				'label'   => 'WP Super Cache',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
			'w3tc'           => array(
				'label'   => 'W3 Total Cache',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
			'nginx'          => array(
				'label'   => 'Nginx',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
			'litespeed'      => array(
				'label'   => 'LiteSpeed',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
			'wp_rocket'      => array(
				'label'   => 'WP Rocket',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
			'wp_optimize'    => array(
				'label'   => 'WP Optimize',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
			'kinsta'         => array(
				'label'   => 'Kinsta',
				'state'   => 'not_detected',
				'warning' => '',
				'note'    => '',
			),
		);

		// WP Super Cache - Expert (mod_rewrite) mode bypasses PHP entirely.
		if ( defined( 'WPCACHEHOME' ) || is_plugin_active( 'wp-super-cache/wp-cache.php' ) || ( is_multisite() && is_plugin_active_for_network( 'wp-super-cache/wp-cache.php' ) ) ) {
			$wpsc_config    = defined( 'WPCACHEHOME' ) ? rtrim( WPCACHEHOME, '/' ) . '/wp-cache-config.php' : '';
			$is_mod_rewrite = false;
			if ( $wpsc_config && file_exists( $wpsc_config ) ) {
				$config_content = file_get_contents( $wpsc_config ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( $config_content !== false && preg_match( '/\$wp_cache_mod_rewrite\s*=\s*1/', $config_content ) ) {
					$is_mod_rewrite = true;
				}
			}
			$systems['wp_super_cache']['state'] = $is_mod_rewrite ? 'needs_config' : 'installed';
			if ( $is_mod_rewrite ) {
				$systems['wp_super_cache']['warning'] = __( 'WP Super Cache (Expert/mod_rewrite mode) serves cached pages via .htaccess, bypassing PHP. Add a cookie-based exception. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
			} else {
				$systems['wp_super_cache']['note'] = __( 'PHP caching mode - respects DONOTCACHEPAGE automatically.', 'botblocker-security' );
			}
		}

		// W3 Total Cache - Disk Enhanced with rewrite rules.
		if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) || ( is_multisite() && is_plugin_active_for_network( 'w3-total-cache/w3-total-cache.php' ) ) ) {
			$systems['w3tc']['state'] = defined( 'W3TC_DIR' ) ? 'needs_config' : 'installed';
			if ( defined( 'W3TC_DIR' ) ) {
				$systems['w3tc']['warning'] = __( 'W3 Total Cache detected. If using Disk Enhanced mode with rewrite rules, add a cookie-based server exception. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
			} else {
				$systems['w3tc']['note'] = __( 'PHP caching mode - respects DONOTCACHEPAGE automatically.', 'botblocker-security' );
			}
		}

		// Nginx FastCGI / Redis page cache (server-level).
		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'nginx' ) !== false ) {
			$systems['nginx']['state'] = ( defined( 'WP_CACHE' ) && WP_CACHE ) ? 'needs_config' : 'installed';
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				$systems['nginx']['warning'] = __( 'Nginx detected with WP_CACHE enabled. If using FastCGI Cache, ensure the BotBlocker cookie bypasses it. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
			} else {
				$systems['nginx']['note'] = __( 'Nginx detected without WP_CACHE - no PHP-level page cache in front of the site.', 'botblocker-security' );
			}
		}

		// LiteSpeed server with LSCache: LSCWP respects X-LiteSpeed-Cache-Control: no-cache - auto-compatible.
		if ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) || ( is_multisite() && is_plugin_active_for_network( 'litespeed-cache/litespeed-cache.php' ) ) ) {
			$systems['litespeed']['state'] = 'installed';
			$systems['litespeed']['note']  = __( 'Respects X-LiteSpeed-Cache-Control: no-cache - auto-compatible.', 'botblocker-security' );
		}

		// WP Rocket.
		if ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) || ( is_multisite() && is_plugin_active_for_network( 'wp-rocket/wp-rocket.php' ) ) ) {
			$systems['wp_rocket']['state']   = 'needs_config';
			$systems['wp_rocket']['warning'] = __( 'WP Rocket detected. Add the BotBlocker cookie to the "Never Cache Cookies" list in WP Rocket settings. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}

		// WP Optimize - page cache respects DONOTCACHEPAGE, no cookie exception needed.
		if ( is_plugin_active( 'wp-optimize/wp-optimize.php' ) || ( is_multisite() && is_plugin_active_for_network( 'wp-optimize/wp-optimize.php' ) ) ) {
			$systems['wp_optimize']['state'] = 'installed';
			$systems['wp_optimize']['note']  = __( 'Respects DONOTCACHEPAGE automatically - no cookie exception needed.', 'botblocker-security' );
		}

		// Kinsta server-level cache.
		if ( defined( 'KINSTAMU_VERSION' ) ) {
			$systems['kinsta']['state']   = 'needs_config';
			$systems['kinsta']['warning'] = __( 'Kinsta hosting detected. Add the BotBlocker cookie as a cache bypass rule in the Kinsta dashboard. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}

		return $systems;
	}

	/**
	 * Detects active cache plugins that may require manual server-level configuration.
	 * Returns an alert array if an incompatible mode is detected, or null if OK.
	 * Projection of detectCacheSystems() - keep both in sync.
	 */
	public static function detectCacheIncompatibility(): ?array {
		$warnings = array();
		foreach ( self::detectCacheSystems() as $system ) {
			if ( ! empty( $system['warning'] ) ) {
				$warnings[] = $system['warning'];
			}
		}

		if ( empty( $warnings ) ) {
			return null;
		}

		return array(
			'type'    => 'cache_compatibility',
			'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'   => __( 'Cache Plugin Compatibility', 'botblocker-security' ),
			'message' => implode( ' | ', $warnings ),
		);
	}

	/**
	 * @param array $failed_addons Array of ['slug'=>..., 'name'=>..., 'error'=>...].
	 */
	public static function setAddonUpdateFailed( array $failed_addons ): void {
		$names   = array_map(
			function ( $f ) {
				return $f['name'];
			},
			$failed_addons
		);
		$message = sprintf(
			/* translators: %s: comma-separated list of add-on names that failed to update. */
			__( 'Failed to auto-update add-on(s): %s. Please retry from the Add-ons page.', 'botblocker-security' ),
			implode( ', ', $names )
		);
		$alert = array(
			'type'      => 'addon_update_failed',
			'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'     => __( 'Add-on Update Failed', 'botblocker-security' ),
			'message'   => $message,
			'link'      => method_exists( 'BotBlockerMultisite', 'getAdminPageUrl' ) ? BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) : '',
			'link_text' => __( 'Go to Add-ons', 'botblocker-security' ),
		);

		set_transient( 'bbcs_addon_update_failed_alert', $alert, DAY_IN_SECONDS );
	}

	/**
	 * @param array $deactivated Array of ['name'=>..., 'requires_core'=>...].
	 */
	public static function setAddonIncompatible( array $deactivated ): void {
		$lines = array();
		foreach ( $deactivated as $item ) {
			if ( ! empty( $item['requires_core'] ) ) {
				/* translators: 1: add-on name, 2: required BotBlocker version. */
				$lines[] = sprintf( __( '%1$s (requires >= %2$s)', 'botblocker-security' ), $item['name'], $item['requires_core'] );
			} else {
				$lines[] = $item['name'];
			}
		}
		$alert = array(
			'type'      => 'addon_incompatible',
			'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'     => __( 'Add-ons Deactivated', 'botblocker-security' ),
			'message'   => sprintf(
				/* translators: %s: comma-separated list of deactivated add-on names with version requirements. */
				__( 'Incompatible add-ons were deactivated: %s. Please update BotBlocker.', 'botblocker-security' ),
				implode( ', ', $lines )
			),
			'link'      => method_exists( 'BotBlockerMultisite', 'getAdminPageUrl' ) ? BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) : '',
			'link_text' => __( 'View Add-ons', 'botblocker-security' ),
		);

		delete_transient( 'bbcs_addon_incompatible_alert' );
		delete_option( '_transient_bbcs_addon_incompatible_alert' );
		delete_option( '_transient_timeout_bbcs_addon_incompatible_alert' );
		wp_cache_delete( '_transient_bbcs_addon_incompatible_alert', 'options' );
		wp_cache_delete( '_transient_timeout_bbcs_addon_incompatible_alert', 'options' );
		set_transient( 'bbcs_addon_incompatible_alert', $alert, DAY_IN_SECONDS );
	}
}

// Invalidate the per-user config cache when relevant settings change.
add_action( 'update_option_bbcs_telegram_settings', array( 'BotBlockerAlerts', 'invalidateConfigCache' ), 10, 0 );
add_action( 'add_option_bbcs_telegram_settings', array( 'BotBlockerAlerts', 'invalidateConfigCache' ), 10, 0 );
add_action( 'update_option_bbcs_setup_wizard_completed', array( 'BotBlockerAlerts', 'invalidateConfigCache' ), 10, 0 );
add_action( 'add_option_bbcs_setup_wizard_completed', array( 'BotBlockerAlerts', 'invalidateConfigCache' ), 10, 0 );
add_action( 'updated_user_meta', function ( $meta_id, $user_id, $meta_key ) {
	if ( $meta_key === '_2fa_secret' || $meta_key === '_2fa_verified' ) {
		BotBlockerAlerts::invalidateConfigCache( (int) $user_id );
	}
}, 10, 3 );
add_action( 'deleted_user_meta', function ( $meta_ids, $user_id, $meta_key ) {
	if ( $meta_key === '_2fa_secret' || $meta_key === '_2fa_verified' ) {
		BotBlockerAlerts::invalidateConfigCache( (int) $user_id );
	}
}, 10, 3 );
