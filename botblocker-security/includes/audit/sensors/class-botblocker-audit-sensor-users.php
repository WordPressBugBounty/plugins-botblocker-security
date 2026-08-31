<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorUsers {

	/** @var array<int, array<string, mixed>> */
	private static $delete_cache = array();

	/** @var array<int, string[]> Roles a user held before this request touched them. */
	private static $role_before = array();

	/** @var array<int, true> Users whose before-state came from set_user_role. */
	private static $role_before_final = array();

	public static function register(): void {
		add_action( 'user_register', array( self::class, 'onUserRegister' ), 10, 1 );
		add_action( 'shutdown', array( self::class, 'flushRoleChanges' ), 15 );
		add_action( 'profile_update', array( self::class, 'onProfileUpdate' ), 10, 2 );
		add_action( 'delete_user', array( self::class, 'onDeleteUser' ), 10, 1 );
		add_action( 'deleted_user', array( self::class, 'onDeletedUser' ), 10, 1 );
		add_action( 'set_user_role', array( self::class, 'onSetUserRole' ), 10, 3 );
		add_action( 'add_user_role', array( self::class, 'onAddUserRole' ), 10, 2 );
		add_action( 'remove_user_role', array( self::class, 'onRemoveUserRole' ), 10, 2 );
		add_action( 'grant_super_admin', array( self::class, 'onGrantSuperAdmin' ), 10, 1 );
		add_action( 'revoke_super_admin', array( self::class, 'onRevokeSuperAdmin' ), 10, 1 );
	}

	public static function onUserRegister( $user_id ): void {
		$user_id = (int) $user_id;
		unset( self::$role_before[ $user_id ], self::$role_before_final[ $user_id ] );

		$user = get_userdata( $user_id );
		$data = array();
		if ( $user instanceof WP_User ) {
			$data = array(
				'login' => $user->user_login,
				'email' => $user->user_email,
				'roles' => array_values( (array) $user->roles ),
			);
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::USER_CREATED,
			array(
				'object_id' => $user_id,
				'data'      => $data,
			)
		);
	}

	public static function onProfileUpdate( $user_id, $old_user_data ): void {
		unset( $old_user_data );
		$user_id = (int) $user_id;
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::USER_UPDATED,
			array(
				'object_id' => $user_id,
				'data'      => array(
					'login' => self::targetLogin( $user_id ),
				),
			)
		);
	}

	public static function onDeleteUser( $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}
		self::$delete_cache[ (int) $user_id ] = array(
			'login' => $user->user_login,
			'email' => $user->user_email,
		);
	}

	public static function onDeletedUser( $user_id ): void {
		$user_id = (int) $user_id;
		$data    = isset( self::$delete_cache[ $user_id ] ) ? self::$delete_cache[ $user_id ] : array();
		unset( self::$delete_cache[ $user_id ] );

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::USER_DELETED,
			array(
				'object_id' => $user_id,
				'data'      => $data,
			)
		);
	}

	private static function rememberRolesBefore( int $user_id, array $before, bool $authoritative = false ): void {
		if ( isset( self::$role_before_final[ $user_id ] ) ) {
			return;
		}
		if ( $authoritative ) {
			self::$role_before_final[ $user_id ] = true;
		} elseif ( isset( self::$role_before[ $user_id ] ) ) {
			return;
		}

		self::$role_before[ $user_id ] = array_values( $before );
	}

	/** @return string[] */
	private static function currentRoles( int $user_id ): array {
		$user = get_userdata( $user_id );

		return $user instanceof WP_User ? array_values( (array) $user->roles ) : array();
	}

	public static function onSetUserRole( $user_id, $role, $old_roles ): void {
		unset( $role );
		self::rememberRolesBefore( (int) $user_id, is_array( $old_roles ) ? $old_roles : array(), true );
	}

	public static function onAddUserRole( $user_id, $role ): void {
		$user_id = (int) $user_id;
		$before  = array_diff( self::currentRoles( $user_id ), array( (string) $role ) );
		self::rememberRolesBefore( $user_id, $before );
	}

	public static function onRemoveUserRole( $user_id, $role ): void {
		$user_id = (int) $user_id;
		$before  = array_merge( self::currentRoles( $user_id ), array( (string) $role ) );
		self::rememberRolesBefore( $user_id, array_unique( $before ) );
	}

	public static function flushRoleChanges(): void {
		$queued                  = self::$role_before;
		self::$role_before       = array();
		self::$role_before_final = array();

		if ( ! $queued ) {
			return;
		}

		foreach ( $queued as $user_id => $before ) {
			clean_user_cache( $user_id );
			$user = get_userdata( $user_id );
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			$after = array_values( (array) $user->roles );
			sort( $before );
			sort( $after );
			if ( $before === $after ) {
				continue;
			}

			BotBlockerAuditLogger::record(
				BotBlockerAuditEvents::USER_ROLE_CHANGED,
				array(
					'object_id' => $user_id,
					'data'      => array(
						'login' => $user->user_login,
						'from'  => $before,
						'to'    => $after,
					),
					'dedup'     => implode( ',', $before ) . '>' . implode( ',', $after ),
				)
			);
		}
	}

	/** Stored now, not resolved at read time: the account may be gone by then. */
	private static function targetLogin( int $user_id ): string {
		$user = get_userdata( $user_id );

		return $user instanceof WP_User ? (string) $user->user_login : '#' . $user_id;
	}

	public static function onGrantSuperAdmin( $user_id ): void {
		$user_id = (int) $user_id;
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::USER_SUPER_ADMIN_GRANTED,
			array(
				'object_id' => $user_id,
				'data'      => array(
					'login' => self::targetLogin( $user_id ),
				),
				'dedup'     => 'grant_super_admin',
			)
		);
	}

	public static function onRevokeSuperAdmin( $user_id ): void {
		$user_id = (int) $user_id;
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::USER_SUPER_ADMIN_REVOKED,
			array(
				'object_id' => $user_id,
				'data'      => array(
					'login' => self::targetLogin( $user_id ),
				),
				'dedup'     => 'revoke_super_admin',
			)
		);
	}
}
