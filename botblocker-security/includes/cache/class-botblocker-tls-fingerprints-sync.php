<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerTlsFingerprintsSync {

	const LOCK_KEY      = 'bbcs_tls_fingerprints_sync_lock';
	const LOCK_TTL      = 30 * MINUTE_IN_SECONDS;
	const STATUS_OPTION = 'bbcs_tls_fingerprints_sync_status';
	const SELF_HEAL_KEY = 'bbcs_tls_fingerprints_sync_self_heal_throttle';

	public static function getStatus(): array {
		$defaults = array(
			'state'              => 'absent',
			'hash'               => '',
			'generated_at'       => 0,
			'fingerprint_count'  => 0,
			'last_sync'          => 0,
			'last_attempt'       => 0,
			'last_error'         => '',
			'failures'           => 0,
		);
		$stored   = get_option( self::STATUS_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( $defaults, $stored );
	}

	public static function setStatus( array $patch ): void {
		$current = self::getStatus();
		foreach ( $patch as $k => $v ) {
			$current[ $k ] = $v;
		}
		update_option( self::STATUS_OPTION, $current, false );
	}

	public static function acquireLock(): bool {
		if ( get_transient( self::LOCK_KEY ) ) {
			return false;
		}
		set_transient( self::LOCK_KEY, (string) time(), self::LOCK_TTL );
		return true;
	}

	public static function releaseLock(): void {
		delete_transient( self::LOCK_KEY );
	}

	public static function scheduleSync( string $reason = '', int $delay_seconds = 60 ): bool {
		$delay = max( 10, $delay_seconds );
		$when  = time() + $delay;
		if ( wp_next_scheduled( 'bbcs_tls_fingerprints_sync_event', array( $reason ) ) !== false ) {
			return false;
		}
		return (bool) wp_schedule_single_event( $when, 'bbcs_tls_fingerprints_sync_event', array( $reason ) );
	}

	public static function isEventQueued(): bool {
		$crons = _get_cron_array();
		if ( ! is_array( $crons ) ) {
			return false;
		}
		foreach ( $crons as $events ) {
			if ( is_array( $events ) && isset( $events['bbcs_tls_fingerprints_sync_event'] ) ) {
				return true;
			}
		}
		return false;
	}

	public static function selfHeal(): void {
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}
		if ( get_transient( self::SELF_HEAL_KEY ) ) {
			return;
		}
		set_transient( self::SELF_HEAL_KEY, '1', HOUR_IN_SECONDS );

		$settings = ( method_exists( 'BotBlocker', 'getInstance' ) ? (array) BotBlocker::getInstance()->settings : array() );
		if ( empty( $settings['tls_fingerprint_check'] ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = (string) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->bbcs_tls_fingerprints )
		) === $wpdb->bbcs_tls_fingerprints;
		if ( ! $table_exists ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_tls_fingerprints}`" );
		$file_missing = ! file_exists( BotBlockerMultisite::getDataDir() . 'tls_fingerprints.php' );
		if ( ( 0 === $count || $file_missing ) && ! self::isEventQueued() ) {
			self::scheduleSync( 'self-heal', 60 );
		}
	}

	public static function fetchFromCloud( string $stored_hash = '' ) {
		$request = array();
		if ( '' !== $stored_hash ) {
			$request['hash'] = $stored_hash;
		}
		$result = BotBlockerWpRequest::send_to_cloud( $request, BOTBLOCKER_API_URL, 'tls_fingerprints_sync' );

		if ( false === $result ) {
			$result = BotBlockerWpRequest::send_to_cloud( $request, BOTBLOCKER_API_GS_URL, 'tls_fingerprints_sync' );
		}

		if ( ! is_array( $result ) ) {
			return false;
		}

		return $result;
	}

	public static function doSync( string $reason = '', bool $force = false ): bool {
		$settings = ( method_exists( 'BotBlocker', 'getInstance' ) ? (array) BotBlocker::getInstance()->settings : array() );
		if ( empty( $settings['tls_fingerprint_check'] ) ) {
			return false;
		}

		$locked = false;
		if ( ! $force ) {
			if ( ! self::acquireLock() ) {
				return false;
			}
			$locked = true;
		}

		$now           = time();
		$stored_status = self::getStatus();
		$stored_hash   = $force ? '' : (string) ( $stored_status['hash'] ?? '' );
		self::setStatus( array( 'last_attempt' => $now ) );

		$payload = self::fetchFromCloud( $stored_hash );

		if ( false === $payload ) {
			self::recordFailure( 'transport_error' );
			if ( $locked ) {
				self::releaseLock();
			}
			return false;
		}

		if ( ! empty( $payload['up_to_date'] ) ) {
			self::setStatus(
				array(
					'state'      => 'ok',
					'last_sync'  => $now,
					'last_error' => '',
					'failures'   => 0,
				)
			);
			if ( $locked ) {
				self::releaseLock();
			}
			return true;
		}

		if ( empty( $payload['fingerprints'] ) || ! is_array( $payload['fingerprints'] ) ) {
			self::recordFailure( 'empty_payload' );
			if ( $locked ) {
				self::releaseLock();
			}
			return false;
		}

		$fingerprints    = $payload['fingerprints'];
		$generated_at_raw = $payload['generated_at'] ?? 0;
		$generated_at     = is_string( $generated_at_raw ) ? (int) strtotime( $generated_at_raw ) : (int) $generated_at_raw;
		$new_hash         = (string) ( $payload['hash'] ?? '' );

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$wpdb->query( 'START TRANSACTION' );
		$ok = true;

		$wpdb->query( "DELETE FROM `{$wpdb->bbcs_tls_fingerprints}`" );
		if ( $wpdb->last_error ) {
			$ok = false;
		}

		if ( $ok ) {
			foreach ( $fingerprints as $fp ) {
				$fingerprint = isset( $fp['fingerprint'] ) ? sanitize_text_field( $fp['fingerprint'] ) : '';
				if ( '' === $fingerprint ) {
					continue;
				}

				$result = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->bbcs_tls_fingerprints}`
						 (fingerprint, category, ua_family, description, disabled)
						 VALUES (%s, %s, %s, %s, %d)",
						$fingerprint,
						isset( $fp['category'] ) ? sanitize_text_field( $fp['category'] ) : 'unknown',
						isset( $fp['ua_family'] ) ? sanitize_text_field( $fp['ua_family'] ) : '',
						isset( $fp['description'] ) ? sanitize_text_field( $fp['description'] ) : '',
						isset( $fp['disabled'] ) ? absint( $fp['disabled'] ) : 0
					)
				);
				if ( false === $result ) {
					$ok = false;
					break;
				}
			}
		}

		if ( $ok ) {
			$wpdb->query( 'COMMIT' );
		} else {
			$wpdb->query( 'ROLLBACK' );
			self::recordFailure( 'db_insert_error' );
			if ( $locked ) {
				self::releaseLock();
			}
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return false;
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$fingerprint_count = count( $fingerprints );

		self::setStatus(
			array(
				'state'             => 'ok',
				'hash'              => $new_hash,
				'generated_at'      => $generated_at,
				'fingerprint_count' => $fingerprint_count,
				'last_sync'         => $now,
				'last_error'        => '',
				'failures'          => 0,
			)
		);

		BotBlockerFileRenderer::renderTlsFingerprints();
		BotBlockerCache::clearFileCache();

		if ( $locked ) {
			self::releaseLock();
		}
		return true;
	}

	public static function recordFailure( string $error ): void {
		$status   = self::getStatus();
		$failures = (int) ( $status['failures'] ?? 0 ) + 1;
		self::setStatus(
			array(
				'state'      => 'error',
				'last_error' => $error,
				'failures'   => $failures,
			)
		);

		if ( $failures < 7 && wp_next_scheduled( 'bbcs_tls_fingerprints_sync_event', array( 'retry' ) ) === false ) {
			$backoff = min( 6, $failures );
			$delay   = max( HOUR_IN_SECONDS, $backoff * HOUR_IN_SECONDS );
			wp_schedule_single_event( time() + $delay, 'bbcs_tls_fingerprints_sync_event', array( 'retry' ) );
		}
	}
}

add_action( 'init', array( 'BotBlockerTlsFingerprintsSync', 'selfHeal' ), 25 );
add_action( 'bbcs_tls_fingerprints_sync_event', array( 'BotBlockerTlsFingerprintsSync', 'doSync' ), 10, 1 );
