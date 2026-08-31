<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorAuthentication {

	public static function register(): void {
		add_action( 'wp_login', array( self::class, 'onLogin' ), 20, 2 );
		add_action( 'wp_login_failed', array( self::class, 'onLoginFailed' ), 10, 1 );
		add_action( 'wp_logout', array( self::class, 'onLogout' ), 10, 1 );
		add_action( 'password_reset', array( self::class, 'onPasswordReset' ), 10, 2 );
		add_action( 'bbcs_2fa_verified', array( self::class, 'on2faVerified' ), 10, 2 );
		add_action( 'bbcs_2fa_verification_failed', array( self::class, 'on2faFailed' ), 10, 1 );
		add_action( 'bbcs_2fa_trusted_device_login', array( self::class, 'onTrustedDeviceLogin' ), 10, 1 );
		add_action( 'bbcs_2fa_setup_completed', array( self::class, 'on2faSetupCompleted' ), 10, 1 );
		add_action( 'bbcs_2fa_reset', array( self::class, 'on2faReset' ), 10, 1 );
		add_action( 'bbcs_2fa_devices_revoked', array( self::class, 'on2faDevicesRevoked' ), 10, 1 );
	}

	public static function onLogin( $user_login, $user ): void {
		if ( ! $user instanceof WP_User ) {
			return;
		}

		if ( class_exists( 'BotBlockerTwoFactorAuth' ) && BotBlockerTwoFactorAuth::isRequiredForUser( $user->ID ) ) {
			return;
		}

		if ( ! BotBlockerAuditContext::isTargetRoleEnabled( $user->ID ) ) {
			return;
		}

		// wp_signon never calls wp_set_current_user, so the ambient user is still 0 here.
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_LOGIN_SUCCESS,
			array(
				'actor_user_id' => $user->ID,
				'object_id'     => $user->ID,
				'data'          => array(
					'login' => (string) $user_login,
				),
			)
		);
	}

	public static function onLoginFailed( $username ): void {
		$username = is_string( $username ) ? $username : '';
		if ( $username === '' ) {
			return;
		}

		$user = get_user_by( 'login', $username );
		if ( ! $user && strpos( $username, '@' ) !== false ) {
			$user = get_user_by( 'email', $username );
		}

		// A nonexistent username is the main brute-force signal, so it is recorded, not dropped.
		if ( $user instanceof WP_User && ! BotBlockerAuditContext::isTargetRoleEnabled( $user->ID ) ) {
			return;
		}

		$user_id = $user instanceof WP_User ? (int) $user->ID : BotBlockerAuditContext::ACTOR_UNIDENTIFIED;

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_LOGIN_FAILED,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
				'data'          => array(
					'login'          => $username,
					'user_exists'    => $user_id > 0,
				),
				'dedup'         => $username,
			)
		);
	}

	public static function onLogout( $user_id = 0 ): void {
		// Core calls wp_set_current_user( 0 ) before wp_logout, so only the passed id is reliable.
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return;
		}

		$user = get_userdata( $user_id );

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_LOGOUT,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
				'data'          => array(
					'login' => $user instanceof WP_User ? $user->user_login : '#' . $user_id,
				),
			)
		);
	}

	public static function onPasswordReset( $user, $new_pass ): void {
		unset( $new_pass );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_PASSWORD_RESET,
			array(
				'actor_user_id' => $user->ID,
				'object_id'     => $user->ID,
				'data'          => array(
					'login' => $user->user_login,
				),
			)
		);
	}

	public static function on2faVerified( $user_id, $via = BotBlockerTwoFactorAuth::VERIFIED_VIA_TOTP ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		$via = is_string( $via ) && $via !== '' ? $via : BotBlockerTwoFactorAuth::VERIFIED_VIA_TOTP;

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_2FA_VERIFIED,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
				'data'          => array(
					'via' => $via,
				),
			)
		);

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_LOGIN_SUCCESS,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
				'data'          => array(
					'via' => $via,
				),
				'dedup'         => '2fa_success',
			)
		);
	}

	public static function on2faFailed( $user_id ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_2FA_FAILED,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
			)
		);
	}

	public static function onTrustedDeviceLogin( $user_id ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_2FA_TRUSTED_DEVICE,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
			)
		);

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_LOGIN_SUCCESS,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
				'data'          => array(
					'via' => 'trusted_device',
				),
				'dedup'         => 'trusted_device',
			)
		);
	}

	public static function on2faSetupCompleted( $user_id ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_2FA_SETUP_COMPLETED,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
			)
		);
	}

	public static function on2faReset( $user_id ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_2FA_RESET,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
			)
		);
	}

	public static function on2faDevicesRevoked( $user_id ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::AUTH_2FA_DEVICES_REVOKED,
			array(
				'actor_user_id' => $user_id,
				'object_id'     => $user_id,
			)
		);
	}
}
