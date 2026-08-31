<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP-CLI commands for 2FA administration (spec-2FA item 3):
 *   wp bbcs 2fa status <user>
 *   wp bbcs 2fa reset <user>
 *   wp bbcs 2fa list [--role=<role>] [--format=table|csv|json|count]
 *   wp bbcs 2fa count
 *
 * The *Data() methods are WP-CLI-free so contract tests can exercise the
 * data layer; the CLI methods only format and emit.
 */
class BBCS_2FA_CLI {

	public static function statusData( int $user_id ): array {
		$backup_codes = get_user_meta( $user_id, '_2fa_backup_codes', true );
		return array(
			'enabled'            => (bool) BotBlockerTwoFactorAuth::isRequiredForUser( $user_id ),
			'verified'           => (bool) get_user_meta( $user_id, '_2fa_verified', true ),
			'secret_set'         => (bool) get_user_meta( $user_id, '_2fa_secret', true ),
			'backup_codes_count' => is_array( $backup_codes ) ? count( $backup_codes ) : 0,
			'pending'            => (bool) ( get_user_meta( $user_id, '_2fa_pending', true ) || get_user_meta( $user_id, '_2fa_setup_pending', true ) ),
		);
	}

	public static function resetUser( int $user_id ): bool {
		return (bool) BotBlockerTwoFactorAuth::adminResetUser2fa( $user_id );
	}

	public static function listUsers( string $role = '' ): array {
		$args = array();
		if ( $role !== '' ) {
			$args['role'] = $role;
		}
		$users = get_users( $args );

		$out = array();
		foreach ( $users as $user ) {
			$data = self::statusData( (int) $user->ID );
			if ( ! $data['enabled'] && ! $data['verified'] && ! $data['secret_set'] && ! $data['pending'] && $data['backup_codes_count'] === 0 ) {
				continue;
			}
			$out[ (int) $user->ID ] = array(
				'login'    => $user->user_login,
				'role'     => implode( ',', (array) $user->roles ),
				'enabled'  => $data['enabled'],
				'verified' => $data['verified'],
			);
		}
		return $out;
	}

	public static function countUsers(): array {
		$counts = array(
			'total'          => 0,
			'verified'       => 0,
			'pending_setup'  => 0,
			'pending_verify' => 0,
			'no_2fa'         => 0,
		);

		$users = get_users();
		foreach ( $users as $user ) {
			$counts['total']++;
			$data = self::statusData( (int) $user->ID );
			if ( $data['verified'] ) {
				$counts['verified']++;
			} elseif ( $data['secret_set'] && ! $data['verified'] ) {
				$counts['pending_verify']++;
			} elseif ( $data['enabled'] || $data['pending'] ) {
				$counts['pending_setup']++;
			} else {
				$counts['no_2fa']++;
			}
		}
		return $counts;
	}

	public static function status( array $args ): void {
		$user_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			WP_CLI::error( 'Usage: wp bbcs 2fa status <user_id>' );
		}
		foreach ( self::statusData( $user_id ) as $key => $value ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- WP-CLI status output uses PHP value syntax
			WP_CLI::log( $key . ': ' . var_export( $value, true ) );
		}
	}

	public static function reset( array $args ): void {
		$user_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			WP_CLI::error( 'Usage: wp bbcs 2fa reset <user_id>' );
		}
		if ( self::resetUser( $user_id ) ) {
			WP_CLI::success( "2FA reset for user {$user_id}" );
		} else {
			WP_CLI::error( "Reset failed — run with --user=<admin> so manage_options is available" );
		}
	}

	public static function list( array $args, array $assoc_args ): void {
		$role   = isset( $assoc_args['role'] ) ? (string) $assoc_args['role'] : '';
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';

		$users = self::listUsers( $role );
		if ( $format === 'count' ) {
			WP_CLI::log( (string) count( $users ) );
			return;
		}
		$rows = array();
		foreach ( $users as $id => $row ) {
			$rows[] = array_merge( array( 'ID' => $id ), $row );
		}
		WP_CLI\Utils\format_items( $format, $rows, array( 'ID', 'login', 'role', 'enabled', 'verified' ) );
	}

	public static function count( array $args, array $assoc_args ): void {
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		$counts = self::countUsers();
		if ( $format === 'json' ) {
			WP_CLI::log( (string) wp_json_encode( $counts ) );
			return;
		}
		foreach ( $counts as $key => $value ) {
			WP_CLI::log( $key . ': ' . $value );
		}
	}
}
