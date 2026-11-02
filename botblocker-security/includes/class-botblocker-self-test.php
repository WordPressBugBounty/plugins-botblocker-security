<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerSelfTest {

	const PARAM          = 'bbcs_selftest';
	const TTL            = 300;
	const NONCE_ACTION   = 'bbcs_selftest_admin';
	const TRANSIENT_BASE = 'bbcs_selftest_';

	private static $active = null;
	private static $token  = '';

	public static function register(): void {
		add_action( 'wp_ajax_bbcs_selftest_start', array( self::class, 'ajaxStart' ) );
		add_action( 'wp_ajax_bbcs_selftest_status', array( self::class, 'ajaxStatus' ) );
		add_filter( 'show_admin_bar', array( self::class, 'hideAdminBar' ) );
	}

	public static function hideAdminBar( $show ) {
		if ( self::isActive() ) {
			return false;
		}

		return $show;
	}

	public static function issue( int $user_id ): string {
		$token = wp_generate_password( 32, false, false );

		set_transient(
			self::stateKey( $token ),
			array(
				'user'    => $user_id,
				'created' => time(),
				'events'  => array(),
			),
			self::TTL
		);

		return $token;
	}

	// Security-critical: active only for the admin session that issued the token.
	public static function isActive(): bool {
		if ( self::$active !== null ) {
			return self::$active;
		}

		self::$active = false;

		$token = self::requestToken();
		if ( $token === '' || ! function_exists( 'get_current_user_id' ) ) {
			return false;
		}

		$state = self::readState( $token );
		if ( $state === null ) {
			return false;
		}

		if ( (int) $state['user'] !== get_current_user_id() ) {
			return false;
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return false;
		}

		self::$token  = $token;
		self::$active = true;

		return true;
	}

	public static function token(): string {
		if ( self::isActive() ) {
			return self::$token;
		}

		return '';
	}

	public static function record( string $code, string $detail = '' ): void {
		if ( ! self::isActive() ) {
			return;
		}

		$key   = self::stateKey( self::$token );
		$state = get_transient( $key );
		if ( ! is_array( $state ) ) {
			return;
		}

		$events   = isset( $state['events'] ) && is_array( $state['events'] ) ? $state['events'] : array();
		$events[] = array(
			'time'   => time(),
			'code'   => $code,
			'detail' => $detail,
		);

		$state['events'] = $events;
		set_transient( $key, $state, self::TTL );
	}

	public static function markPassed( string $code, string $detail = '' ): void {
		if ( ! self::isActive() ) {
			return;
		}

		self::record( $code, $detail );

		$key   = self::stateKey( self::$token );
		$state = get_transient( $key );
		if ( ! is_array( $state ) ) {
			return;
		}

		$state['passed'] = true;
		set_transient( $key, $state, self::TTL );
	}

	public static function hasPassed(): bool {
		if ( ! self::isActive() ) {
			return false;
		}

		$state = get_transient( self::stateKey( self::$token ) );

		return is_array( $state ) && ! empty( $state['passed'] );
	}

	public static function resetForTests(): void {
		self::$active = null;
		self::$token  = '';
	}

	private static function stateKey( string $token ): string {
		return self::TRANSIENT_BASE . $token;
	}

	private static function readState( string $token ): ?array {
		$state = get_transient( self::stateKey( $token ) );

		if ( ! is_array( $state ) || empty( $state['user'] ) ) {
			return null;
		}

		return $state;
	}

	private static function requestToken(): string {
		$raw = '';

		// phpcs:disable WordPress.Security.NonceVerification -- token is bound to the issuing admin session.
		if ( isset( $_POST[ self::PARAM ] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST[ self::PARAM ] ) );
		} elseif ( isset( $_GET[ self::PARAM ] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_GET[ self::PARAM ] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return self::sanitizeToken( $raw );
	}

	private static function sanitizeToken( string $raw ): string {
		$token = preg_replace( '/[^a-zA-Z0-9]/', '', $raw );

		if ( ! is_string( $token ) || strlen( $token ) !== 32 ) {
			return '';
		}

		return $token;
	}

	public static function ajaxStart(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$token = self::issue( get_current_user_id() );

		wp_send_json_success(
			array(
				'token' => $token,
				'url'   => add_query_arg( self::PARAM, $token, home_url( '/' ) ),
			)
		);
	}

	public static function ajaxStatus(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$token  = isset( $_POST['token'] ) ? self::sanitizeToken( sanitize_text_field( wp_unslash( $_POST['token'] ) ) ) : '';
		$state  = $token !== '' ? get_transient( self::stateKey( $token ) ) : false;
		$events = array();

		if (
			is_array( $state )
			&& isset( $state['user'], $state['events'] )
			&& is_array( $state['events'] )
			&& (int) $state['user'] === get_current_user_id()
		) {
			$events = $state['events'];
			set_transient( self::stateKey( $token ), $state, self::TTL );
		}

		wp_send_json_success(
			array(
				'events'   => $events,
				'verdict'  => self::verdict( $events ),
				'settings' => self::settingsSummary(),
				'bypasses' => self::normalBypasses(),
			)
		);
	}

	public static function verdict( array $events ): string {
		$codes = array();

		foreach ( $events as $event ) {
			if ( isset( $event['code'] ) ) {
				$codes[] = (string) $event['code'];
			}
		}

		$silent = self::settingsSummary()['silent'];

		if ( $silent && ( in_array( 'captcha_solved', $codes, true ) || in_array( 'passed_silently', $codes, true ) ) ) {
			return 'passed_silently';
		}

		if ( in_array( 'captcha_solved', $codes, true ) ) {
			return 'captcha_solved';
		}

		if ( $silent && in_array( 'captcha_required', $codes, true ) ) {
			return 'challenge_page';
		}

		if ( in_array( 'captcha_required', $codes, true ) ) {
			return 'captcha_shown';
		}

		if ( in_array( 'passed_silently', $codes, true ) ) {
			return 'passed_silently';
		}

		if ( in_array( 'challenge_page', $codes, true ) ) {
			return 'challenge_page';
		}

		return 'pending';
	}

	public static function settingsSummary(): array {
		$settings = self::loadSettings();
		$mode     = (int) $settings->bbcs_captcha_mode;
		$modes    = array(
			BOTBLOCKER_CAPTCHA_MODE_BUTTON              => __( 'Button', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS       => __( 'Color Buttons', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_IMAGE               => __( 'Image', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON => __( 'reCAPTCHA v2 Button', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2        => __( 'reCAPTCHA v2', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_SHAPE               => __( 'Shape', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_DIGIT               => __( 'Digit', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_HOLD                => __( 'Hold Button', 'botblocker-security' ),
			BOTBLOCKER_CAPTCHA_MODE_SILENT              => __( 'Silent', 'botblocker-security' ),
		);

		return array(
			'disabled'     => (int) $settings->disable === 1,
			'secure_mode'  => (int) $settings->secure_mode === 2 ? __( 'Full', 'botblocker-security' ) : __( 'Frontend', 'botblocker-security' ),
			'captcha_mode' => isset( $modes[ $mode ] ) ? $modes[ $mode ] : __( 'Unknown', 'botblocker-security' ),
			'silent'       => $mode === BOTBLOCKER_CAPTCHA_MODE_SILENT,
			'force_check'  => (int) $settings->botblocker_force_check === 1,
		);
	}

	public static function normalBypasses(): array {
		$settings = self::loadSettings();
		$reasons  = array();
		$user     = wp_get_current_user();
		$roles    = array( 'administrator', 'editor', 'moderator', 'shop_manager' );

		if ( ! empty( array_intersect( $roles, (array) $user->roles ) ) ) {
			$reasons[] = 'admin_role';
		} elseif ( (int) $settings->skip_logged_in_users === 1 ) {
			$reasons[] = 'logged_in';
		}

		$uid = isset( $_COOKIE[ $settings->cookie ] )
			? preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ $settings->cookie ] ) ) )
			: '';

		if ( $uid !== '' && isset( $_COOKIE[ $uid ] ) ) {
			$reasons[] = 'pass_cookie';
		}

		if ( self::remoteIpAllowed() ) {
			$reasons[] = 'allowed_ip';
		}

		return $reasons;
	}

	private static function remoteIpAllowed(): bool {
		global $wpdb;

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$table = $wpdb->prefix . 'bbcs_ipv4rules';
			$num   = BotBlockerIp::toNumeric( $ip );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is $wpdb->prefix + constant name
			$rule = $wpdb->get_var( $wpdb->prepare( "SELECT rule FROM `{$table}` WHERE disable = %d AND CAST(ip1 AS UNSIGNED) <= %d AND CAST(ip2 AS UNSIGNED) >= %d ORDER BY priority ASC LIMIT 1", 0, $num, $num ) );
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$table = $wpdb->prefix . 'bbcs_ipv6rules';
			$num   = BotBlockerIp::toBinary( $ip );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is $wpdb->prefix + constant name
			$rule = $wpdb->get_var( $wpdb->prepare( "SELECT rule FROM `{$table}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC LIMIT 1", 0, $num, $num ) );
		} else {
			return false;
		}

		return $rule === 'allow';
	}

	private static function loadSettings(): BotBlockerSettings {
		if ( ! class_exists( 'BotBlockerSettings' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-settings.php';
		}

		$settings = new BotBlockerSettings();
		$settings->load( BotBlockerMultisite::getDataDir() . 'settings.php' );

		return $settings;
	}
}
