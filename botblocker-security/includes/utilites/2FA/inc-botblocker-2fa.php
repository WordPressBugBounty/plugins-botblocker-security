<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Two-Factor Authentication (2FA) with Google Authenticator
 *
 * Conditionally loads Google2FA v8.0 (PHP < 8.1) or v9.0 (PHP >= 8.1)
 *
 * @package botblocker-security
 * @version 2.2
 */

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

global $wpdb;

class BBCS_GoogleAuthenticator {

	protected $_codeLength = 6;
	/** @var \BotBlocker\Vendor\PragmaRX\Google2FA\Google2FA|null */
	private $driver = null;

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

	public function getQRCodeUrl( $name, $secret, $issuer = 'BotBlocker' ): string {
		$otpauthUrl =
			'otpauth://totp/' . rawurlencode( $issuer . ':' . $name ) .
			'?secret=' . $secret .
			'&issuer=' . rawurlencode( $issuer ) .
			'&algorithm=SHA1' .
			'&digits=6' .
			'&period=30';

		if ( class_exists( '\\BotBlocker\\Vendor\\GlobusStudio\\QRCode\\QRCode' ) ) {
			$svg = \BotBlocker\Vendor\GlobusStudio\QRCode\QRCode::svg( $otpauthUrl, array( 'size' => 4, 'margin' => 2 ) );
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

	public function get_settings(): array {
		static $runtime_cache = null;

		if ( $runtime_cache !== null ) {
			return $runtime_cache;
		}

		$cache_key = 'bbcs_2fa_settings';
		$found     = false;
		$cached    = wp_cache_get( $cache_key, 'bbcs', false, $found );
		if ( $found ) {
			return $runtime_cache = $cached;
		}

		$file_settings = false;
		$settings_file = rtrim( BotBlockerMultisite::getDataDir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'settings.php';
		if ( is_file( $settings_file ) && is_readable( $settings_file ) ) {
			try {
				$loaded = bbcs_safe_load_data_file( $settings_file );
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
			$db_settings = $this->bbcs_load_integrations_settings();
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

		return $runtime_cache;
	}


	private function bbcs_load_integrations_settings() {
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
}

$bbcs_required_files = array(
	BOTBLOCKER_DIR . 'includes/utilites/2FA/bbcs-2fa-init.php',
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
	return; // don't include anything and don't create the instance
}

$bbcs_google_auth = new BBCS_GoogleAuthenticator();

	function bbcs_is_2fa_required_for_user( $user_id ): bool {
	global $bbcs_google_auth;
	$bbcs_saved_settings = $bbcs_google_auth->get_settings();

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

foreach ( $bbcs_required_files as $bbcs_file ) {
	require_once $bbcs_file;
}

// Delete temporary variables
unset( $bbcs_required_files );
unset( $bbcs_missing );
unset( $bbcs_file );
unset( $bbcs_f );

// Backup Code Regeneration: Generates plain codes, hashes them, and stores them
function bbcs_regenerate_backup_codes_for_user( $user_id, $count = 10 ) {
	if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
		return false;
	}

	$plain_codes = bbcs_generate_backup_codes( $count );
	$hashed      = array_map( 'bbcs_hash_backup_code', $plain_codes );

	update_user_meta( $user_id, '_2fa_backup_codes', $hashed );

	return $plain_codes;
}

// Reset 2FA for User
function bbcs_admin_reset_user_2fa( $user_id ) {
	if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
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
	delete_transient( 'bbcs_2fa_attempts_' . $user_id );

	return true;
}

add_action( 'wp_ajax_bbcs_2fa_setup', 'bbcs_handle_2fa_setup_ajax' );
function bbcs_handle_2fa_setup_ajax(): void {
	check_ajax_referer( 'botblocker_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'message' => __( 'You must be logged in.', 'botblocker-security' ),
			)
		);
	}

	global $bbcs_google_auth;

	if ( ! is_object( $bbcs_google_auth ) ) {
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

	$secret = get_user_meta( $user_id, '_2fa_secret_temp', true );
	if ( empty( $secret ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Setup session expired. Please refresh the page.', 'botblocker-security' ),
			)
		);
	}

	if ( ! $bbcs_google_auth->verifyCode( $secret, $code ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid verification code. Please try again.', 'botblocker-security' ),
			)
		);
	}

	// Successfully authentication
	$backup_codes        = get_user_meta( $user_id, '_2fa_backup_codes_temp', true );
	$hashed_backup_codes = array_map( 'bbcs_hash_backup_code', is_array( $backup_codes ) ? $backup_codes : array() );

	update_user_meta( $user_id, '_2fa_secret', $secret );
	update_user_meta( $user_id, '_2fa_backup_codes', $hashed_backup_codes );
	update_user_meta( $user_id, '_2fa_verified', 1 ); // Added: verification flag

	delete_user_meta( $user_id, '_2fa_secret_temp' );
	delete_user_meta( $user_id, '_2fa_backup_codes_temp' );
	delete_user_meta( $user_id, '_2fa_setup_pending' );
	delete_user_meta( $user_id, '_2fa_pending' );

	wp_send_json_success(
		array(
			'message' => __( 'Two-factor authentication enabled.', 'botblocker-security' ),
			'reload'  => true,
		)
	);
}

add_action( 'wp_ajax_bbcs_reset_2fa', 'bbcs_ajax_reset_2fa' );
function bbcs_ajax_reset_2fa(): void {

	check_ajax_referer( 'botblocker_nonce', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( __( 'User not logged in', 'botblocker-security' ) );
	}

	$user = wp_get_current_user();
	if ( ! $user || ! $user->exists() ) {
		wp_send_json_error( __( 'User not found', 'botblocker-security' ) );
	}

	global $bbcs_google_auth;
	if ( ! is_object( $bbcs_google_auth ) ) {
		wp_send_json_error( __( '2FA service unavailable', 'botblocker-security' ) );
	}

	bbcs_admin_reset_user_2fa( $user_id );

	delete_user_meta( $user_id, '_2fa_secret_temp' );
	delete_user_meta( $user_id, '_2fa_backup_codes_temp' );

	// New secret — atomic insert to prevent race on double-submit
	$bbcs_new_secret = $bbcs_google_auth->createSecret();
	$bbcs_saved      = add_user_meta( $user_id, '_2fa_secret_temp', $bbcs_new_secret, true );
	if ( $bbcs_saved ) {
		$secret = $bbcs_new_secret;
	} else {
		$secret = get_user_meta( $user_id, '_2fa_secret_temp', true );
	}

	// Backup codes — same atomic pattern
	$bbcs_new_codes      = bbcs_generate_backup_codes();
	$bbcs_saved          = add_user_meta( $user_id, '_2fa_backup_codes_temp', $bbcs_new_codes, true );
	if ( $bbcs_saved ) {
		$backup_codes = $bbcs_new_codes;
	} else {
		$backup_codes = get_user_meta( $user_id, '_2fa_backup_codes_temp', true );
	}

	// QR
	$email  = sanitize_email( $user->user_email );
	$qr_url = $bbcs_google_auth->getQRCodeUrl( $email, $secret );

	wp_send_json_success(
		array(
			'message'           => __( '2FA has been reset.', 'botblocker-security' ),
			'bbcs_qr_code'      => esc_attr( $qr_url ),
			'bbcs_backup_codes' => array_map( 'esc_html', $backup_codes ),
		)
	);
}
