<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditContext {

	/** No user id resolved: identification failed, or the request came from WP-CLI. */
	public const ACTOR_UNIDENTIFIED = 0;

	public const CHANNEL_CLI      = 'cli';
	public const CHANNEL_CRON     = 'cron';
	public const CHANNEL_XMLRPC   = 'xmlrpc';
	public const CHANNEL_REST     = 'rest';
	public const CHANNEL_AJAX     = 'ajax';
	public const CHANNEL_ADMIN    = 'admin';
	public const CHANNEL_FRONTEND = 'frontend';

	private static $writing = false;

	/** @var array<string, string>|null */
	private static $role_map_cache = null;

	/**
	 * Recursion guard for the audit write itself.
	 *
	 * Inserting a row fires WordPress hooks, and sensors listen on those hooks. Without
	 * this flag a sensor would call record() from inside the insert, forever. record()
	 * checks isWriting() and drops any event raised while a write is in flight.
	 */
	public static function beginWrite(): void {
		self::$writing = true;
	}

	/** Pair of beginWrite(); must run even if the insert throws. */
	public static function endWrite(): void {
		self::$writing = false;
	}

	public static function isWriting(): bool {
		return self::$writing;
	}

	public static function isActorRoleEnabled( int $user_id ): bool {
		return self::isRoleEnabled( self::getRoleForUser( $user_id ) );
	}

	public static function isTargetRoleEnabled( int $user_id ): bool {
		return self::isActorRoleEnabled( $user_id );
	}

	public static function getRoleForUser( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}
		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->roles ) ) {
			return '';
		}
		return (string) $user->roles[0];
	}

	public static function getUsernameForUser( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}
		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return '';
		}
		return (string) $user->user_login;
	}

	public static function getUserAgent(): string {
		if ( class_exists( 'BotBlocker' ) ) {
			$bbcs = BotBlocker::getInstance();
			if ( isset( $bbcs->useragent ) && is_string( $bbcs->useragent ) && $bbcs->useragent !== '' ) {
				return $bbcs->useragent;
			}
		}

		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$ua = trim( wp_strip_all_tags( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) );
			return $ua;
		}

		return '';
	}

	public static function getStoredRoleMap(): array {
		if ( self::$role_map_cache !== null ) {
			return self::$role_map_cache;
		}

		global $wpdb;

		$bbcs  = BotBlocker::getInstance();
		$value = isset( $bbcs->settings->audit_log_roles ) ? $bbcs->settings->audit_log_roles : null;

		if ( is_array( $value ) ) {
			self::$role_map_cache = self::normalizeRoleMap( $value );

			return self::$role_map_cache;
		}

		$raw = is_string( $value ) ? $value : '';

		if ( $raw === '' && ! empty( $wpdb->bbcs_settings ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$db_val = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s LIMIT 1",
					'audit_log_roles'
				)
			);
			if ( is_string( $db_val ) && $db_val !== '' ) {
				$raw = $db_val;
			}
		}

		$decoded = array();
		if ( $raw !== '' ) {
			$parsed = json_decode( $raw, true );
			if ( is_array( $parsed ) ) {
				$decoded = self::normalizeRoleMap( $parsed );
			}
		}

		self::$role_map_cache = $decoded;

		return $decoded;
	}

	/**
	 * @param array<mixed, mixed> $parsed
	 * @return array<string, string>
	 */
	private static function normalizeRoleMap( array $parsed ): array {
		$map = array();
		foreach ( $parsed as $role_key => $flag ) {
			if ( is_string( $role_key ) && is_scalar( $flag ) ) {
				$map[ $role_key ] = (string) $flag;
			}
		}

		return $map;
	}

	/** Settings save rewrites the map mid-request, so drop the cached copy. */
	public static function flushRoleMapCache(): void {
		self::$role_map_cache = null;
	}

	public static function isRoleEnabled( string $role ): bool {
		if ( $role === '' ) {
			return true;
		}
		$map = self::getStoredRoleMap();
		if ( ! isset( $map[ $role ] ) ) {
			return true;
		}
		return $map[ $role ] === '1' || $map[ $role ] === 1 || $map[ $role ] === true;
	}

	public static function getActorUserId(): int {
		return (int) get_current_user_id();
	}

	public static function getActorRole(): string {
		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return '';
		}
		return (string) $user->roles[0];
	}

	public static function getIp(): string {
		if ( class_exists( 'BotBlockerIp' ) && method_exists( 'BotBlockerIp', 'getCurrentIp' ) ) {
			$ip = BotBlockerIp::getCurrentIp();
			if ( is_string( $ip ) && $ip !== '' ) {
				return $ip;
			}
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}

	public static function getRequestChannel(): string {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return self::CHANNEL_CLI;
		}
		if ( wp_doing_cron() ) {
			return self::CHANNEL_CRON;
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return self::CHANNEL_XMLRPC;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return self::CHANNEL_REST;
		}
		if ( wp_doing_ajax() ) {
			return self::CHANNEL_AJAX;
		}
		if ( is_admin() ) {
			return self::CHANNEL_ADMIN;
		}
		return self::CHANNEL_FRONTEND;
	}

	public static function getRequestPath(): string {
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path is parsed before prepared storage and escaped audit output
			$path = wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
			if ( is_string( $path ) ) {
				return $path;
			}
		}
		return '';
	}

}
