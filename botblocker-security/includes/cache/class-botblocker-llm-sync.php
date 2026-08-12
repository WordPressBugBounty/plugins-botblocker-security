<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerLlmSync {

	const LOCK_KEY      = 'bbcs_llm_sync_lock';
	const LOCK_TTL      = 30 * MINUTE_IN_SECONDS;
	const STATUS_OPTION = 'bbcs_llm_sync_status';
	const SELF_HEAL_KEY = 'bbcs_llm_sync_self_heal_throttle';

	public static function getStatus(): array {
		$defaults = array(
			'state'          => 'absent',
			'hash'           => '',
			'generated_at'   => 0,
			'provider_count' => 0,
			'range_count'    => 0,
			'last_sync'      => 0,
			'last_attempt'   => 0,
			'last_error'     => '',
			'failures'       => 0,
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
		if ( wp_next_scheduled( 'bbcs_llm_sync_event', array( $reason ) ) !== false ) {
			return false;
		}
		return (bool) wp_schedule_single_event( $when, 'bbcs_llm_sync_event', array( $reason ) );
	}

	public static function isEventQueued(): bool {
		$crons = _get_cron_array();
		if ( ! is_array( $crons ) ) {
			return false;
		}
		foreach ( $crons as $events ) {
			if ( is_array( $events ) && isset( $events['bbcs_llm_sync_event'] ) ) {
				return true;
			}
		}
		return false;
	}

	public static function selfHeal(): void {
		global $wpdb;

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = (string) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->bbcs_llm_trusted )
		) === $wpdb->bbcs_llm_trusted;
		if ( ! $table_exists ) {
			return;
		}

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_llm_trusted}`" );
		$file_missing = ! file_exists( BotBlockerMultisite::getDataDir() . 'llm_trusted.php' );
		if ( ( 0 === $count || $file_missing ) && ! self::isEventQueued() ) {
			self::scheduleSync( 'self-heal', 60 );
		}
	}

	public static function doSync( string $reason = '', bool $force = false ): bool {
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
			if ( class_exists( 'BotBlockerAlerts' ) ) {
				BotBlockerAlerts::clearSyncFailed( 'llm' );
			}
			if ( $locked ) {
				self::releaseLock();
			}
			return true;
		}

		if ( empty( $payload['providers'] ) || ! is_array( $payload['providers'] ) ) {
			self::recordFailure( 'empty_payload' );
			if ( $locked ) {
				self::releaseLock();
			}
			return false;
		}

		$providers        = $payload['providers'];
		$generated_at_raw = $payload['generated_at'] ?? 0;
		$generated_at     = is_string( $generated_at_raw ) ? (int) strtotime( $generated_at_raw ) : (int) $generated_at_raw;
		$new_hash         = (string) ( $payload['hash'] ?? '' );

		$rows = self::parseProviders( $providers );

		if ( empty( $rows ) ) {
			self::recordFailure( 'no_valid_rows' );
			if ( $locked ) {
				self::releaseLock();
			}
			return false;
		}

		global $wpdb;

	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$disabled_providers = $wpdb->get_col(
			"SELECT DISTINCT provider FROM `{$wpdb->bbcs_llm_trusted}` WHERE disabled = 1"
		);
		$disabled_providers = is_array( $disabled_providers ) ? array_flip( $disabled_providers ) : array();

		$wpdb->query( 'START TRANSACTION' );
		$ok = true;

		$wpdb->query( "DELETE FROM `{$wpdb->bbcs_llm_trusted}`" );
		if ( $wpdb->last_error ) {
			$ok = false;
		}

		if ( $ok ) {
			foreach ( $rows as $row ) {
				$row['disabled'] = isset( $disabled_providers[ $row['provider'] ] ) ? 1 : 0;
				$result          = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->bbcs_llm_trusted}`
                         (provider, provider_label, `search`, verified_ip_ranges, disabled)
                         VALUES (%s, %s, %s, %s, %d)",
						$row['provider'],
						$row['provider_label'],
						$row['search'],
						$row['verified_ip_ranges'],
						$row['disabled']
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

		$provider_count = count( array_unique( array_column( $rows, 'provider' ) ) );
		$range_count    = 0;
		foreach ( $rows as $row ) {
			$ranges       = preg_split( '/\s+/', trim( $row['verified_ip_ranges'] ), -1, PREG_SPLIT_NO_EMPTY );
			$range_count += count( $ranges );
		}

		self::setStatus(
			array(
				'state'          => 'ok',
				'hash'           => $new_hash,
				'generated_at'   => $generated_at,
				'provider_count' => $provider_count,
				'range_count'    => $range_count,
				'last_sync'      => $now,
				'last_error'     => '',
				'failures'       => 0,
			)
		);

		BotBlockerFileRenderer::renderLlmTrusted();
		BotBlockerCache::clearFileCache();

		if ( class_exists( 'BotBlockerAlerts' ) ) {
			BotBlockerAlerts::clearSyncFailed( 'llm' );
		}

		if ( $locked ) {
			self::releaseLock();
		}
		return true;
	}

	public static function fetchFromCloud( string $stored_hash = '' ) {
		$request = array();
		if ( '' !== $stored_hash ) {
			$request['hash'] = $stored_hash;
		}

		$result = BotBlockerWpRequest::send_to_cloud( $request, BOTBLOCKER_API_URL, 'llm_providers_sync' );
		if ( false === $result ) {
			$result = BotBlockerWpRequest::send_to_cloud( $request, BOTBLOCKER_API_GS_URL, 'llm_providers_sync' );
		}

		if ( ! is_array( $result ) ) {
			return false;
		}

		return $result;
	}

	public static function parseProviders( array $providers ): array {
		$rows = array();

		foreach ( $providers as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}

			$provider = sanitize_key( $p['provider'] );
			if ( '' === $provider ) {
				continue;
			}

			$provider_label = sanitize_text_field( $p['label'] ?? $provider );

			$raw_ranges   = preg_split( '/\s+/', trim( (string) ( $p['verified_ip_ranges'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY );
			$valid_ranges = array();
			foreach ( $raw_ranges as $cidr ) {
				$type = BotBlockerIp::detectType( $cidr );
				if ( 'ip' === $type || 'cidr' === $type ) {
					$valid_ranges[] = $cidr;
				}
			}
			$verified_ip_ranges = implode( ' ', $valid_ranges );

			$raw_tokens   = preg_split( '/\s+/', trim( (string) ( $p['ua_tokens'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY );
			$valid_tokens = array();
			foreach ( $raw_tokens as $token ) {
				$token = sanitize_text_field( $token );
				if ( '' !== $token ) {
					$valid_tokens[] = $token;
				}
			}
			if ( empty( $valid_tokens ) ) {
				continue;
			}

			$rows[] = array(
				'provider'           => $provider,
				'provider_label'     => $provider_label,
				'search'             => implode( ' ', $valid_tokens ),
				'verified_ip_ranges' => $verified_ip_ranges,
				'disabled'           => 0,
			);
		}

		return $rows;
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

		if ( class_exists( 'BotBlockerAlerts' ) ) {
			BotBlockerAlerts::setSyncFailed(
				'llm',
				'llm_sync_failed',
				__( 'LLM Provider Sync Failed', 'botblocker-security' ),
				__( 'Could not sync the LLM provider list. Trusted LLM bots may not be recognized.', 'botblocker-security' )
			);
		}

		if ( $failures < 7 ) {
			$backoff = min( 6, $failures );
			$delay   = max( HOUR_IN_SECONDS, $backoff * HOUR_IN_SECONDS );
			wp_schedule_single_event( time() + $delay, 'bbcs_llm_sync_event', array( 'retry' ) );
		}
	}
}

add_action( 'bbcs_llm_sync_event', array( 'BotBlockerLlmSync', 'doSync' ), 10, 1 );
