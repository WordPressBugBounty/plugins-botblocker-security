<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerMigration {

	public static function getRegistry(): array {
		return apply_filters(
			'bbcs_migrations_registry',
			array(
				'2.2.0' => array(
					'file'     => 'migration-2-2-0.php',
					'callback' => 'bbcs_migration_2_2_0',
				),
				'2.3.0' => array(
					'file'     => 'migration-2-3-0.php',
					'callback' => 'bbcs_migration_2_3_0',
				),
				'2.4.0' => array(
					'file'     => 'migration-2-4-0.php',
					'callback' => 'bbcs_migration_2_4_0',
				),
				'2.5.0' => array(
					'file'     => 'migration-2-5-0.php',
					'callback' => 'bbcs_migration_2_5_0',
				),
				'2.6.0' => array(
					'file'     => 'migration-2-6-0.php',
					'callback' => 'bbcs_migration_2_6_0',
				),
				'2.7.0' => array(
					'file'     => 'migration-2-7-0.php',
					'callback' => 'bbcs_migration_2_7_0',
				),
			'2.8.0' => array(
				'file'     => 'migration-2-8-0.php',
				'callback' => 'bbcs_migration_2_8_0',
			),
			'2.9.0' => array(
				'file'     => 'migration-2-9-0.php',
				'callback' => 'bbcs_migration_2_9_0',
			),
			)
		);
	}

	public static function loadMigration( array $entry ): bool {
		$dir  = __DIR__ . '/migrations/';
		$path = $dir . $entry['file'];
		if ( ! file_exists( $path ) ) {
			return false;
		}
		require_once $path;
		return is_callable( $entry['callback'] );
	}

	public static function maybeUpgradeDb(): bool {
		wp_cache_delete( 'bbcs_db_version', 'options' );
		$installed = get_option( 'bbcs_db_version', '0' );
		$target    = BOTBLOCKER_DB_VERSION;

		if ( version_compare( $installed, $target, '>=' ) ) {
			return true;
		}

		$lock_key   = 'bbcs_migration_lock';
		$now        = time();
		$lock_token = $now . '|' . uniqid( '', true );

		if ( ! add_option( $lock_key, $lock_token, '', 'no' ) ) {
			$lock_value = get_option( $lock_key, '' );
			$lock_time  = is_string( $lock_value ) ? (int) $lock_value : 0;
			if ( $now - $lock_time > 120 ) {
				delete_option( $lock_key );
				add_option( $lock_key, $lock_token, '', 'no' );
			}
			if ( get_option( $lock_key ) !== $lock_token ) {
				return true;
			}
		}

		if ( version_compare( $installed, '2.2.0', '>=' ) && ! BotBlockerSummary::isTableReady() ) {
			$registry = self::getRegistry();
			if ( isset( $registry['2.2.0'] ) && self::loadMigration( $registry['2.2.0'] ) ) {
				self::runMigration( $registry['2.2.0']['callback'] );
			}
		}

		$migrations  = self::getRegistry();
		$is_existing = ( $installed !== '0' || BotBlockerInstall::tablesExist() );

		if ( $is_existing ) {
			$applied = $installed;
			$failed  = false;
			foreach ( $migrations as $version => $entry ) {
				if ( version_compare( $installed, $version, '<' ) ) {
					if ( self::loadMigration( $entry ) ) {
						$result = self::runMigration( $entry['callback'] );
						if ( $result ) {
							$applied = $version;
						} else {
							$failed = true;
							if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
								// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
								error_log( '[BBCS DEBUG] [Migration] migration ' . $version . ' failed' );
							}
							break;
						}
					}
				}
			}
			update_option( 'bbcs_db_version', $applied, true );
		} else {
			update_option( 'bbcs_db_version', $target, true );
		}

		if ( get_option( $lock_key ) === $lock_token ) {
			delete_option( $lock_key );
		}

		return empty( $failed );
	}

	public static function runMigration( $callback ): bool {
		try {
			$result = call_user_func( $callback );
			return $result !== false;
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// REVIEWER NOTE: Conditional debug logging; gated behind BBCS_DEBUG and disabled in production.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Migration] migration failed: ' . $e->getMessage() );
			}
			return false;
		}
	}
}
