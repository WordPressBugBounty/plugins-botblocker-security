<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Two-Factor Authentication (2FA) with Google Authenticator.
 *
 * Migrated from bbcs-2fa-init.php (S-09) + inc-botblocker-2fa.php (S-10):
 * TOTP engine (was BBCS_GoogleAuthenticator), rewrite rules, backup codes,
 * rate limiting, admin reset and the AJAX handlers.
 *
 * Conditionally loads Google2FA v8.0 (PHP < 8.1) or v9.0 (PHP >= 8.1).
 */
class BotBlockerTwoFactorAuth {

	const RULES_VERSION = '1.2';

	const VERIFIED_VIA_TOTP        = 'totp';
	const VERIFIED_VIA_BACKUP_CODE = 'backup_code';

	/** @var BotBlockerTwoFactorAuth|null */
	private static $instance = null;

	/** @var array<string, mixed>|null */
	private static $settings_runtime_cache = null;

	/** @var bool */
	private static $pages_loaded = false;

	protected $_codeLength = 6;
	/** @var \BotBlocker\Vendor\PragmaRX\Google2FA\Google2FA|null */
	private $driver = null;

	public static function instance(): BotBlockerTwoFactorAuth {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load the vendor autoloaders and wire every 2FA hook.
	 */
	public static function bootstrap(): void {
		// Determine which version to load based on PHP version
		$bbcs_php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		if ( version_compare( $bbcs_php_version, '8.1', '>=' ) ) {
			// PHP 8.1+: Load Google2FA v9.0
			$bbcs_2fa_autoload = BOTBLOCKER_DIR . 'vendor/2FA/v9/autoload.php';
		} else {
			// PHP < 8.1: Load Google2FA v8.0
			$bbcs_2fa_autoload = BOTBLOCKER_DIR . 'vendor/2FA/v8/autoload.php';
		}

		// Load the appropriate version
		if ( file_exists( $bbcs_2fa_autoload ) ) {
			require_once $bbcs_2fa_autoload;
		} else {
			// Log error for debugging
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [2FA] Google2FA autoload not found at ' . $bbcs_2fa_autoload );
			}
			// Continue running without 2FA fallback
		}

		$bbcs_qrcode_autoload = BOTBLOCKER_DIR . 'vendor/PHPQRCode/standalone/autoloader.php';
		if ( file_exists( $bbcs_qrcode_autoload ) ) {
			require_once $bbcs_qrcode_autoload;
		}

		// Required files must exist before the instance is created.
		$bbcs_required_files = array(
			BOTBLOCKER_DIR . 'public/2FA/bbcs-2fa.php',
			BOTBLOCKER_DIR . 'public/2FA/bbcs-2fa-setup.php',
		);

		$bbcs_missing = array();
		foreach ( $bbcs_required_files as $bbcs_f ) {
			if ( ! file_exists( $bbcs_f ) ) {
				$bbcs_missing[] = $bbcs_f;
			}
		}

		if ( ! empty( $bbcs_missing ) ) {
			return; // don't create the instance
		}

		foreach ( $bbcs_required_files as $bbcs_file ) {
			require_once $bbcs_file;
		}
		unset( $bbcs_required_files, $bbcs_missing );

		self::register();
	}

	public static function register(): void {
		add_action( 'init', array( self::class, 'registerRewriteRules' ), 10 );
		add_action( 'parse_request', array( self::class, 'parseRequest' ), 5 );

		add_action(
			'init',
			function () {
				if ( ! is_admin() && ! is_user_logged_in() ) {
					return;
				}

				$rules_version   = get_option( 'bbcs_2fa_rules_version', '0' );
				$current_version = self::RULES_VERSION;

				if ( $rules_version !== $current_version ) {
					self::registerRewriteRules();
					flush_rewrite_rules( false );
					update_option( 'bbcs_2fa_rules_version', $current_version );
				}
			},
			999
		);

		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'bbcs_2fa';
				$vars[] = 'bbcs_2fa_setup';
				return $vars;
			},
			10,
			1
		);

		add_filter(
			'login_redirect',
			function ( $redirect_to, $request, $user ) {
				if ( is_wp_error( $user ) || ! self::isRequiredForUser( $user->ID ) ) {
					return $redirect_to;
				}

				$user_id = (int) $user->ID;

				$bbcs_recovery_token = self::extractRecoveryToken( $redirect_to );
				if ( '' !== $bbcs_recovery_token && self::handleRecoveryForUser( $user_id, $bbcs_recovery_token ) ) {
					return site_url( '/?bbcs_2fa_setup=1' );
				}

				if ( self::isDeviceTrusted( $user_id ) ) {
					do_action( 'bbcs_2fa_trusted_device_login', $user_id );
					return $redirect_to;
				}

				$secret = get_user_meta( $user_id, '_2fa_secret', true );

				if ( empty( $secret ) ) {
					update_user_meta( $user_id, '_2fa_setup_pending', 1 );
					return site_url( '/?bbcs_2fa_setup=1' );
				}

				update_user_meta( $user_id, '_2fa_pending', 1 );
				$saved_redirect = ! empty( $redirect_to ) ? $redirect_to : admin_url();
				update_user_meta( $user_id, '_2fa_redirect_to', $saved_redirect );
				return site_url( '/?bbcs_2fa=1' );
			},
			10,
			3
		);

		add_action(
			'admin_init',
			function () {
				if ( wp_doing_cron() ) {
					return;
				}

				if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
					return;
				}

				if ( ! is_user_logged_in() ) {
					return;
				}

				$user_id = get_current_user_id();
				if ( ! $user_id ) {
					return;
				}

				$state = self::getPendingState( $user_id );
				if ( 'none' === $state ) {
					return;
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read action name only to skip logout; no state change here
				$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
				if ( 'logout' === $action ) {
					return;
				}

			if ( get_query_var( 'bbcs_2fa' ) ) {
				return;
			}

			if ( get_query_var( 'bbcs_2fa_setup' ) && 'setup' === $state ) {
				return;
			}

				$bbcs_2fa_url = home_url( 'setup' === $state ? '/?bbcs_2fa_setup=1' : '/?bbcs_2fa=1' );

				if ( wp_doing_ajax() ) {
					if ( self::isAllowed2faAjaxAction( $action ) ) {
						return;
					}

					wp_send_json_error(
						array(
							'message'      => __( 'Two-factor authentication is required.', 'botblocker-security' ),
							'bbcs_2fa_url' => $bbcs_2fa_url,
						),
						403
					);
				}

				wp_safe_redirect( $bbcs_2fa_url );
				BotBlocker::getInstance()->process_die();
			},
			5
		);

		add_filter( 'rest_authentication_errors', array( self::class, 'restEnforcePending' ), 99, 1 );

		add_filter( 'authenticate', array( self::class, 'blockXmlrpcFor2faUsers' ), 40, 3 );

		add_action( 'wp_ajax_bbcs_2fa_setup', array( self::class, 'handleSetupAjax' ) );
		add_action( 'wp_ajax_bbcs_reset_2fa', array( self::class, 'handleResetAjax' ) );
		add_action( 'wp_ajax_bbcs_revoke_2fa_devices', array( self::class, 'handleRevokeDevicesAjax' ) );
	}

	/**
	 * Register 2FA rewrite rules. Use a named method so the activation hook
	 * can call it before flushing rules.
	 */
	public static function registerRewriteRules(): void {
		add_rewrite_rule(
			'^bbcs-2fa/?$',
			'index.php?bbcs_2fa=1',
			'top'
		);
		add_rewrite_rule(
			'^bbcs-2fa-setup/?$',
			'index.php?bbcs_2fa_setup=1',
			'top'
		);
	}

	/**
	 * Parse incoming requests and set query vars for 2FA endpoints
	 * as a fallback if rewrite rules are not yet present or flushed.
	 */
	public static function parseRequest( $wp ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		$bbcs_is_2fa_path   = (bool) ( $path && preg_match( '#/bbcs-2fa/?$#', $path ) );
		$bbcs_is_setup_path = (bool) ( $path && preg_match( '#/bbcs-2fa-setup/?$#', $path ) );

		if ( ! $bbcs_is_2fa_path && ! $bbcs_is_setup_path ) {
			return;
		}

		$bbcs_saved_settings = self::instance()->getSettings();

		if ( isset( $bbcs_saved_settings['bbcs_2fa_enable'] ) && ! $bbcs_saved_settings['bbcs_2fa_enable'] ) {
			return;
		}

		if ( $bbcs_is_2fa_path ) {
			$wp->query_vars['bbcs_2fa'] = '1';
		}
		if ( $bbcs_is_setup_path ) {
			$wp->query_vars['bbcs_2fa_setup'] = '1';
		}
	}

	public function __construct() {
		if ( class_exists( '\BotBlocker\Vendor\PragmaRX\Google2FA\Google2FA' ) ) {
			try {
				$this->driver = new \BotBlocker\Vendor\PragmaRX\Google2FA\Google2FA();
			} catch ( \Throwable $e ) {
				$this->driver = null;
			}
		}
	}

	public function createSecret( $secretLength = 16 ): string {
		if ( $this->driver ) {
			return $this->driver->generateSecretKey( $secretLength );
		}

		$validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$secret     = '';
		for ( $i = 0; $i < $secretLength; $i++ ) {
			$secret .= $validChars[ random_int( 0, 31 ) ];
		}
		return $secret;
	}

	public function getCode( $secret, $timeSlice = null ) {
		// Keep original implementation for deterministic code generation.
		if ( $timeSlice === null ) {
			$timeSlice = floor( time() / 30 );
		}

		$secretkey = $this->_base32Decode( $secret );
		if ( $secretkey === false || $secretkey === '' ) {
			return false;
		}
		$time     = pack( 'N', 0 ) . pack( 'N', $timeSlice );
		$hm       = hash_hmac( 'SHA1', $time, $secretkey, true );
		$offset   = ord( substr( $hm, -1 ) ) & 0x0F;
		$hashpart = substr( $hm, $offset, 4 );
		$value    = unpack( 'N', $hashpart );
		$value    = $value[1];
		$value    = $value & 0x7FFFFFFF;
		$modulo   = pow( 10, $this->_codeLength );
		return str_pad( (string) ( $value % $modulo ), $this->_codeLength, '0', STR_PAD_LEFT );
	}

	public function verifyCode( $secret, $code, $discrepancy = 1 ): bool {
		if ( $this->driver ) {
			$window = max( 1, (int) $discrepancy );
			return (bool) $this->driver->verifyKey( $secret, $code, $window );
		}

		$currentTimeSlice = floor( time() / 30 );
		for ( $i = -$discrepancy; $i <= $discrepancy; $i++ ) {
			$calculatedCode = $this->getCode( $secret, $currentTimeSlice + $i );
			if ( is_string( $code ) && is_string( $calculatedCode ) && hash_equals( $calculatedCode, $code ) ) {
				return true;
			}
		}
		return false;
	}

	public function getQRCodeUrl( $name, $secret, $issuer = '' ): string {
		if ( $issuer === '' ) {
			$issuer = BotBlockerMultisite::getCurrentSiteName();
			if ( $issuer === '' ) {
				$issuer = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'BotBlocker';
			}
		}
		$otpauthUrl =
			'otpauth://totp/' . rawurlencode( $issuer . ':' . $name ) .
			'?secret=' . $secret .
			'&issuer=' . rawurlencode( $issuer ) .
			'&algorithm=SHA1' .
			'&digits=6' .
			'&period=30';

		if ( class_exists( '\\BotBlocker\\Vendor\\GlobusStudio\\QRCode\\QRCode' ) ) {
			$svg = \BotBlocker\Vendor\GlobusStudio\QRCode\QRCode::svg(
				$otpauthUrl,
				array(
					'size'   => 4,
					'margin' => 2,
				)
			);
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return 'data:image/svg+xml;base64,';
	}

	protected function _base32Decode( $secret ) {
		if ( empty( $secret ) ) {
			return '';
		}

		$base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$secret      = strtoupper( $secret );
		$secret      = str_replace( '=', '', $secret );

		$binaryString = '';
		$buffer       = 0;
		$bitsLeft     = 0;

		$len = strlen( $secret );
		for ( $i = 0; $i < $len; $i++ ) {
			$ch  = $secret[ $i ];
			$val = strpos( $base32chars, $ch );
			if ( $val === false ) {
				return false; // invalid character
			}

			$buffer    = ( $buffer << 5 ) | $val;
			$bitsLeft += 5;

			if ( $bitsLeft >= 8 ) {
				$bitsLeft     -= 8;
				$byte          = ( $buffer >> $bitsLeft ) & 0xFF;
				$binaryString .= chr( $byte );
			}
		}

		return $binaryString;
	}

	public function getSettings(): array {
		$runtime_cache = self::$settings_runtime_cache;

		if ( $runtime_cache !== null ) {
			return $runtime_cache;
		}

		$cache_key = 'bbcs_2fa_settings';
		$found     = false;
		$cached    = wp_cache_get( $cache_key, 'bbcs', false, $found );
		if ( $found ) {
			self::$settings_runtime_cache = $cached;
			return $cached;
		}

		$file_settings = false;
		$settings_file = rtrim( BotBlockerMultisite::getDataDir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'settings.php';
		if ( is_file( $settings_file ) && is_readable( $settings_file ) ) {
			try {
				$loaded = BotBlockerDataFile::safeLoad( $settings_file );
				if ( is_array( $loaded ) ) {
					$file_settings = $loaded;
				}
			} catch ( \Throwable $e ) {
			}
		}

		// Use file as authoritative source. Only use DB when file is missing or invalid.
		if ( $file_settings !== false && is_array( $file_settings ) ) {
			$runtime_cache = $file_settings;
		} else {
			// Load from DB as fallback
			$db_settings = $this->loadIntegrationsSettings();
			if ( is_array( $db_settings ) ) {
				$runtime_cache = $db_settings;
			} else {
				$runtime_cache = array();
			}
		}

		// Ensure we always return an array to avoid warnings when callers expect array access
		if ( ! is_array( $runtime_cache ) ) {
			$runtime_cache = array();
		}

		wp_cache_set( $cache_key, $runtime_cache, 'bbcs', 300 );
		self::$settings_runtime_cache = $runtime_cache;

		return $runtime_cache;
	}

	public static function flushSettingsCache(): void {
		self::$settings_runtime_cache = null;
		wp_cache_delete( 'bbcs_2fa_settings', 'bbcs' );
	}

	private function loadIntegrationsSettings() {
		global $wpdb;

		$results = false;

		if ( isset( $wpdb->bbcs_settings ) ) {
			// REVIEWER NOTE:
			// Direct database access is required because this plugin uses a custom table.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results( "SELECT `key`, `value` FROM `{$wpdb->bbcs_settings}`", ARRAY_A );
		}

		if ( ! $results ) {
			return false;
		}

		$bbcs_settings = array();
		foreach ( (array) $results as $row ) {
			$decoded                      = json_decode( $row['value'], true );
			$bbcs_settings[ $row['key'] ] = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $row['value'];
		}

		return $bbcs_settings;
	}

	public static function isRequiredForUser( $user_id ): bool {
		$bbcs_saved_settings = self::instance()->getSettings();

		if ( empty( $bbcs_saved_settings['bbcs_2fa_enable'] ) ) {
			return false;
		}

		if ( empty( $bbcs_saved_settings['bbcs_2fa_roles'] ) || ! is_array( $bbcs_saved_settings['bbcs_2fa_roles'] ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}

		foreach ( $user->roles as $role ) {
			if ( ! empty( $bbcs_saved_settings['bbcs_2fa_roles'][ $role ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Pending challenge state of a logged-in session: 'setup' (no secret
	 * yet), 'verify' (TOTP not entered) or 'none'. Non-2FA roles are never
	 * pending.
	 */
	public static function getPendingState( int $user_id ): string {
		if ( ! self::isRequiredForUser( $user_id ) ) {
			return 'none';
		}

		$secret = get_user_meta( $user_id, '_2fa_secret', true );

		if ( empty( $secret ) && get_user_meta( $user_id, '_2fa_setup_pending', true ) ) {
			return 'setup';
		}

		if ( get_user_meta( $user_id, '_2fa_pending', true ) ) {
			return 'verify';
		}

		return 'none';
	}

	/**
	 * Admin-ajax actions that must stay reachable while the session is
	 * pending: the 2FA setup verifier and the reset/revoke handlers.
	 */
	private static function isAllowed2faAjaxAction( string $action ): bool {
		return in_array( $action, array( 'bbcs_2fa_setup', 'bbcs_revoke_2fa_devices' ), true );
	}

	/**
	 * REST gate: cookie sessions with a pending 2FA challenge get a 403.
	 * Application-password auth (core signals success with true) and
	 * anonymous requests pass. Runs at priority 99 — after core's
	 * application-password (90) and before the cookie nonce check (100).
	 *
	 * @param mixed $result Previous authentication result.
	 * @return mixed
	 */
	public static function restEnforcePending( $result ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( true === $result ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return $result;
		}

		if ( 'none' !== self::getPendingState( $user_id ) ) {
			return new WP_Error(
				'bbcs_2fa_pending',
				__( 'Two-factor authentication is required.', 'botblocker-security' ),
				array( 'status' => 403 )
			);
		}

		return $result;
	}

	public static function generateBackupCodes( int $count = 10 ): array {
		$codes = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$codes[] = strtoupper( bin2hex( random_bytes( 8 ) ) );
		}
		return $codes;
	}

	public static function hashBackupCode( string $code ): string {
		return password_hash( $code, PASSWORD_BCRYPT );
	}

	public static function verifyBackupCode( string $code, $stored_codes ) {
		if ( ! is_array( $stored_codes ) ) {
			return false;
		}

		foreach ( $stored_codes as $idx => $stored ) {
			if ( strpos( $stored, '$' ) === 0 ) {
				if ( password_verify( $code, $stored ) ) {
					return $idx;
				}
			}
		}

		$code_upper = strtoupper( $code );
		foreach ( $stored_codes as $idx => $stored ) {
			if ( is_string( $stored ) && strtoupper( $stored ) === $code_upper ) {
				return $idx;
			}
		}

		return false;
	}

	public static function checkRateLimit( $user_id ): bool {
		$data = get_transient( 'bbcs_2fa_attempts_' . $user_id );

		// No record - first attempt in this window.
		if ( $data === false ) {
			set_transient(
				'bbcs_2fa_attempts_' . $user_id,
				array(
					'count' => 1,
					'since' => time(),
				),
				300
			); // 5 minutes
			return true;
		}

		// Backwards compat: old format stored a plain integer.
		if ( ! is_array( $data ) ) {
			$data = array(
				'count' => (int) $data,
				'since' => time() - 300,
			);
		}

		// Fallback TTL check: persistent object cache may ignore TTL.
		// If 5+ minutes have passed since the window started, reset the counter.
		if ( time() - $data['since'] >= 300 ) {
			set_transient(
				'bbcs_2fa_attempts_' . $user_id,
				array(
					'count' => 1,
					'since' => time(),
				),
				300
			);
			return true;
		}

		if ( $data['count'] >= 5 ) {
			return false;
		}

		set_transient(
			'bbcs_2fa_attempts_' . $user_id,
			array(
				'count' => $data['count'] + 1,
				'since' => $data['since'],
			),
			300
		);
		return true;
	}

	public static function resetRateLimit( $user_id ): void {
		delete_transient( 'bbcs_2fa_attempts_' . $user_id );
	}

	// Backup Code Regeneration: Generates plain codes, hashes them, and stores them
	public static function regenerateBackupCodesForUser( $user_id, $count = 10 ) {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return false;
		}

		$plain_codes = self::generateBackupCodes( $count );
		$hashed      = array_map( array( self::class, 'hashBackupCode' ), $plain_codes );

		update_user_meta( $user_id, '_2fa_backup_codes', $hashed );

		return $plain_codes;
	}

	// Reset 2FA for User
	public static function adminResetUser2fa( $user_id ) {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return false;
		}

		return self::resetUser2faData( (int) $user_id );
	}

	public static function resetUser2faData( $user_id ): bool {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		delete_user_meta( $user_id, '_2fa_secret' );
		delete_user_meta( $user_id, '_2fa_backup_codes' );
		delete_user_meta( $user_id, '_2fa_secret_temp' );
		delete_user_meta( $user_id, '_2fa_backup_codes_temp' );
		delete_user_meta( $user_id, '_2fa_pending' );
		delete_user_meta( $user_id, '_2fa_setup_pending' );
		delete_user_meta( $user_id, '_2fa_redirect_to' );
		delete_user_meta( $user_id, '_2fa_verified' );
		delete_user_meta( $user_id, '_2fa_trusted_devices' );
		delete_user_meta( $user_id, '_2fa_recovery_token' );
		delete_transient( 'bbcs_2fa_attempts_' . $user_id );

		return true;
	}

	public static function markPagesLoaded(): void {
		self::$pages_loaded = true;
	}

	public static function arePagesLoaded(): bool {
		return self::$pages_loaded;
	}

	public static function extractRecoveryToken( $url ): string {
		$query = wp_parse_url( (string) $url, PHP_URL_QUERY );
		if ( ! is_string( $query ) || '' === $query ) {
			return '';
		}

		wp_parse_str( $query, $bbcs_args );
		if ( empty( $bbcs_args['recovery'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $bbcs_args['recovery'] ) );
	}

	public static function sendRecoveryEmail( $user_id ): bool {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return false;
		}

		if ( ! self::isRequiredForUser( $user_id ) ) {
			return false;
		}

		if ( ! get_user_meta( $user_id, '_2fa_pending', true ) ) {
			return false;
		}

		if ( ! get_user_meta( $user_id, '_2fa_secret', true ) ) {
			return false;
		}

		if ( get_transient( 'bbcs_2fa_recovery_sent_' . $user_id ) ) {
			return false;
		}

		$bbcs_daily  = get_transient( 'bbcs_2fa_recovery_daily_' . $user_id );
		$bbcs_daily  = is_array( $bbcs_daily ) ? $bbcs_daily : array();
		$bbcs_recent = array();
		$bbcs_day_ago = time() - DAY_IN_SECONDS;
		foreach ( $bbcs_daily as $bbcs_ts ) {
			if ( (int) $bbcs_ts >= $bbcs_day_ago ) {
				$bbcs_recent[] = (int) $bbcs_ts;
			}
		}
		if ( count( $bbcs_recent ) >= 6 ) {
			return false;
		}
		$bbcs_recent[] = time();
		set_transient( 'bbcs_2fa_recovery_daily_' . $user_id, $bbcs_recent, DAY_IN_SECONDS );
		set_transient( 'bbcs_2fa_recovery_sent_' . $user_id, 1, 15 * MINUTE_IN_SECONDS );

		$bbcs_token = wp_generate_password( 32, false );
		update_user_meta(
			$user_id,
			'_2fa_recovery_token',
			array(
				'hash'    => password_hash( $bbcs_token, PASSWORD_BCRYPT ),
				'created' => time(),
			)
		);

		$bbcs_link    = home_url( '/?bbcs_2fa=1&recovery=' . rawurlencode( $bbcs_token ) );
		$bbcs_subject = sprintf(
			/* translators: %s: site name */
			__( '%s: Two-Factor Authentication recovery', 'botblocker-security' ),
			BotBlockerMultisite::getCurrentSiteName()
		);
		$bbcs_body = sprintf(
			"Hello,\n\nTo reset two-factor authentication for your account, open this link within 1 hour:\n%s\n\nIf you did not request this, you can safely ignore this email.\n",
			$bbcs_link
		);

		wp_mail( $user->user_email, $bbcs_subject, $bbcs_body );

		return true;
	}

	public static function verifyRecoveryToken( $user_id, $token ): bool {
		$user_id = (int) $user_id;
		$stored  = get_user_meta( $user_id, '_2fa_recovery_token', true );
		delete_user_meta( $user_id, '_2fa_recovery_token' );

		if ( ! is_array( $stored ) || empty( $stored['hash'] ) || empty( $stored['created'] ) ) {
			return false;
		}

		if ( time() - (int) $stored['created'] > HOUR_IN_SECONDS ) {
			return false;
		}

		return password_verify( (string) $token, (string) $stored['hash'] );
	}

	public static function handleRecoveryForUser( $user_id, $token ): bool {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( ! self::isRequiredForUser( $user_id ) ) {
			return false;
		}

		if ( ! get_user_meta( $user_id, '_2fa_pending', true ) ) {
			return false;
		}

		if ( ! get_user_meta( $user_id, '_2fa_secret', true ) ) {
			return false;
		}

		if ( ! self::verifyRecoveryToken( $user_id, (string) $token ) ) {
			return false;
		}

		self::resetUser2faData( $user_id );
		update_user_meta( $user_id, '_2fa_setup_pending', 1 );

		return true;
	}

	public static function setTrustedDevice( $user_id ): bool {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		$bbcs_token = bin2hex( random_bytes( 32 ) );
		$bbcs_hash  = hash( 'sha256', $bbcs_token );

		$bbcs_devices = get_user_meta( $user_id, '_2fa_trusted_devices', true );
		$bbcs_devices = is_array( $bbcs_devices ) ? $bbcs_devices : array();

		$bbcs_now = time();
		foreach ( $bbcs_devices as $bbcs_stored_hash => $bbcs_expiry ) {
			if ( (int) $bbcs_expiry < $bbcs_now ) {
				unset( $bbcs_devices[ $bbcs_stored_hash ] );
			}
		}

		while ( count( $bbcs_devices ) >= 5 ) {
			asort( $bbcs_devices, SORT_NUMERIC );
			array_shift( $bbcs_devices );
		}

		$bbcs_devices[ $bbcs_hash ] = $bbcs_now + 30 * DAY_IN_SECONDS;
		update_user_meta( $user_id, '_2fa_trusted_devices', $bbcs_devices );

		$_COOKIE['bbcs_trusted_device'] = $bbcs_token;

		if ( headers_sent() ) {
			return true;
		}

		$bbcs_secure = 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
		setcookie(
			'bbcs_trusted_device',
			$bbcs_token,
			array(
				'expires'  => $bbcs_now + 30 * DAY_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => $bbcs_secure,
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		return true;
	}

	public static function isDeviceTrusted( $user_id ): bool {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( empty( $_COOKIE['bbcs_trusted_device'] ) ) {
			return false;
		}

		$bbcs_token = sanitize_text_field( wp_unslash( $_COOKIE['bbcs_trusted_device'] ) );
		if ( strlen( $bbcs_token ) !== 64 || ! ctype_xdigit( $bbcs_token ) ) {
			return false;
		}

		$bbcs_hash = hash( 'sha256', $bbcs_token );

		$bbcs_devices = get_user_meta( $user_id, '_2fa_trusted_devices', true );
		if ( ! is_array( $bbcs_devices ) ) {
			return false;
		}

		$bbcs_match = null;
		foreach ( $bbcs_devices as $bbcs_stored_hash => $bbcs_expiry ) {
			if ( hash_equals( (string) $bbcs_stored_hash, $bbcs_hash ) ) {
				$bbcs_match = (int) $bbcs_expiry;
				break;
			}
		}

		if ( $bbcs_match === null ) {
			return false;
		}

		$bbcs_now     = time();
		$bbcs_changed = false;
		foreach ( $bbcs_devices as $bbcs_stored_hash => $bbcs_expiry ) {
			if ( (int) $bbcs_expiry < $bbcs_now ) {
				unset( $bbcs_devices[ $bbcs_stored_hash ] );
				$bbcs_changed = true;
			}
		}

		if ( $bbcs_changed ) {
			update_user_meta( $user_id, '_2fa_trusted_devices', $bbcs_devices );
		}

		return $bbcs_match >= $bbcs_now;
	}

	public static function revokeTrustedDevice( $user_id, $token ): bool {
		$user_id = (int) $user_id;
		$bbcs_token = sanitize_text_field( wp_unslash( (string) $token ) );
		$bbcs_hash  = hash( 'sha256', $bbcs_token );

		$bbcs_devices = get_user_meta( $user_id, '_2fa_trusted_devices', true );
		if ( ! is_array( $bbcs_devices ) ) {
			return false;
		}

		foreach ( $bbcs_devices as $bbcs_stored_hash => $bbcs_expiry ) {
			if ( hash_equals( (string) $bbcs_stored_hash, $bbcs_hash ) ) {
				unset( $bbcs_devices[ $bbcs_stored_hash ] );
				update_user_meta( $user_id, '_2fa_trusted_devices', $bbcs_devices );
				return true;
			}
		}

		return false;
	}

	public static function revokeAllTrustedDevices( $user_id ): bool {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		delete_user_meta( $user_id, '_2fa_trusted_devices' );
		unset( $_COOKIE['bbcs_trusted_device'] );

		if ( headers_sent() ) {
			return true;
		}

		setcookie(
			'bbcs_trusted_device',
			'',
			array(
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		return true;
	}

	/**
	 * XML-RPC gate: XML-RPC has no 2FA challenge step, so a 2FA-required
	 * account must never authenticate through it when the admin opted in
	 * via bbcs_2fa_xmlrpc_block (default off). wp-login.php passes through
	 * (login_redirect forces the challenge), REST application passwords
	 * are handled by restEnforcePending().
	 *
	 * @param mixed  $user     Authenticated user or error.
	 * @param string $username Login name.
	 * @param string $password Password.
	 * @param bool|null $xmlrpc Explicit override for tests; null reads XMLRPC_REQUEST.
	 * @return mixed
	 */
	public static function blockXmlrpcFor2faUsers( $user, $username, $password, $xmlrpc = null ) {
		if ( $xmlrpc === null ) {
			$xmlrpc = defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
		}

		if ( ! $xmlrpc || ! $user instanceof WP_User ) {
			return $user;
		}

		$bbcs_saved_settings = self::instance()->getSettings();
		if ( empty( $bbcs_saved_settings['bbcs_2fa_xmlrpc_block'] ) ) {
			return $user;
		}

		if ( ! self::isRequiredForUser( $user->ID ) ) {
			return $user;
		}

		return new WP_Error(
			'bbcs_2fa_required',
			__( 'Two-factor authentication is required. XML-RPC login is blocked for this account.', 'botblocker-security' )
		);
	}

	public static function handleSetupAjax(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => __( 'You must be logged in.', 'botblocker-security' ),
				)
			);
		}

		$auth = self::instance();

		if ( ! is_object( $auth ) ) {
			wp_send_json_error(
				array(
					'message' => __( '2FA service unavailable.', 'botblocker-security' ),
				)
			);
		}

		$user_id = get_current_user_id();

		if ( ! isset( $_POST['bbcs_2fa_code'] ) || empty( $_POST['bbcs_2fa_code'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Verification code is required.', 'botblocker-security' ),
				)
			);
		}
		$code = sanitize_text_field( wp_unslash( $_POST['bbcs_2fa_code'] ) );

		if ( ! self::checkRateLimit( $user_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many attempts. Try again in 5 minutes.', 'botblocker-security' ),
				)
			);
		}

		$secret = get_user_meta( $user_id, '_2fa_secret_temp', true );
		if ( empty( $secret ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Setup session expired. Please refresh the page.', 'botblocker-security' ),
				)
			);
		}

		if ( 'setup' !== self::getPendingState( $user_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Two-factor authentication is required.', 'botblocker-security' ),
				)
			);
		}

		if ( ! $auth->verifyCode( $secret, $code ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid verification code. Please try again.', 'botblocker-security' ),
				)
			);
		}

		$bbcs_active_secret = get_user_meta( $user_id, '_2fa_secret', true );
		if ( ! empty( $bbcs_active_secret ) && ! $auth->verifyCode( $bbcs_active_secret, $code ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid verification code. Please try again.', 'botblocker-security' ),
				)
			);
		}

		// Successfully authentication
		$backup_codes        = get_user_meta( $user_id, '_2fa_backup_codes_temp', true );
		$hashed_backup_codes = array_map( array( self::class, 'hashBackupCode' ), is_array( $backup_codes ) ? $backup_codes : array() );

		update_user_meta( $user_id, '_2fa_secret', $secret );
		update_user_meta( $user_id, '_2fa_backup_codes', $hashed_backup_codes );
		update_user_meta( $user_id, '_2fa_verified', 1 ); // Added: verification flag

		delete_user_meta( $user_id, '_2fa_secret_temp' );
		delete_user_meta( $user_id, '_2fa_backup_codes_temp' );
		delete_user_meta( $user_id, '_2fa_setup_pending' );
		delete_user_meta( $user_id, '_2fa_pending' );
		delete_user_meta( $user_id, '_2fa_redirect_to' );
		self::resetRateLimit( $user_id );

		wp_send_json_success(
			array(
				'message' => __( 'Two-factor authentication enabled.', 'botblocker-security' ),
				'reload'  => true,
			)
		);
	}

	public static function handleResetAjax(): void {

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'User not logged in', 'botblocker-security' ) );
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			wp_send_json_error( __( 'User not found', 'botblocker-security' ) );
		}

		$auth = self::instance();
		if ( ! is_object( $auth ) ) {
			wp_send_json_error( __( '2FA service unavailable', 'botblocker-security' ) );
		}

		if ( 'none' !== self::getPendingState( $user_id ) || ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Not allowed.', 'botblocker-security' ),
				)
			);
		}

		$bbcs_reset_result = self::adminResetUser2fa( $user_id );
		if ( ! $bbcs_reset_result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to reset 2FA.', 'botblocker-security' ),
				)
			);
		}

		update_user_meta( $user_id, '_2fa_setup_pending', 1 );

		delete_user_meta( $user_id, '_2fa_secret_temp' );
		delete_user_meta( $user_id, '_2fa_backup_codes_temp' );

		// New secret - atomic insert to prevent race on double-submit
		$bbcs_new_secret = $auth->createSecret();
		$bbcs_saved      = add_user_meta( $user_id, '_2fa_secret_temp', $bbcs_new_secret, true );
		if ( $bbcs_saved ) {
			$secret = $bbcs_new_secret;
		} else {
			$secret = get_user_meta( $user_id, '_2fa_secret_temp', true );
		}

		// Backup codes - same atomic pattern
		$bbcs_new_codes = self::generateBackupCodes();
		$bbcs_saved     = add_user_meta( $user_id, '_2fa_backup_codes_temp', $bbcs_new_codes, true );
		if ( $bbcs_saved ) {
			$backup_codes = $bbcs_new_codes;
		} else {
			$backup_codes = get_user_meta( $user_id, '_2fa_backup_codes_temp', true );
		}

		// QR
		$email  = sanitize_email( $user->user_email );
		$qr_url = $auth->getQRCodeUrl( $email, $secret );

		do_action( 'bbcs_2fa_reset', $user_id );

		wp_send_json_success(
			array(
				'message'           => __( '2FA has been reset.', 'botblocker-security' ),
				'bbcs_qr_code'      => esc_attr( $qr_url ),
				'bbcs_backup_codes' => array_map( 'esc_html', $backup_codes ),
			)
		);
	}

	public static function handleRevokeDevicesAjax(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'User not logged in', 'botblocker-security' ) );
		}

		self::revokeAllTrustedDevices( $user_id );

		do_action( 'bbcs_2fa_devices_revoked', $user_id );

		wp_send_json_success(
			array(
				'message' => __( 'All trusted devices have been revoked.', 'botblocker-security' ),
			)
		);
	}
}
