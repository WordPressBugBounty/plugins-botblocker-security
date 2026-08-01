<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerRugov {

	const LOCK_KEY       = 'bbcs_rugov_sync_lock';
	const LOCK_TTL       = 30 * MINUTE_IN_SECONDS;
	const STATUS_OPTION  = 'bbcs_rugov_sync_status';
	const SELF_HEAL_KEY  = 'bbcs_rugov_self_heal_throttle';
	const MIN_RANGES     = 500;
	const FRESHNESS_DAYS = 7;

	public static function getStatus(): array {
		$defaults = array(
			'state'        => 'absent',
			'hash'         => '',
			'range_count'  => 0,
			'last_sync'    => 0,
			'last_attempt' => 0,
			'last_error'   => '',
			'failures'     => 0,
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

	public static function scheduleUpdate( string $reason = '', int $delay_seconds = 60 ): bool {
		$delay = max( 10, $delay_seconds );
		$when  = time() + $delay;
		if ( wp_next_scheduled( 'bbcs_rugov_update_event', array( $reason ) ) !== false ) {
			return false;
		}
		return (bool) wp_schedule_single_event( $when, 'bbcs_rugov_update_event', array( $reason ) );
	}

	public static function isEventQueued(): bool {
		$crons = _get_cron_array();
		if ( ! is_array( $crons ) ) {
			return false;
		}
		foreach ( $crons as $events ) {
			if ( is_array( $events ) && isset( $events['bbcs_rugov_update_event'] ) ) {
				return true;
			}
		}
		return false;
	}

	public static function uploadsFile(): string {
		$dir = BotBlockerMultisite::getDataDir();
		if ( $dir === '' ) {
			return '';
		}
		return $dir . 'bbcs-rugov-data.php';
	}

	public static function isFilePresent(): bool {
		$path = self::uploadsFile();
		return $path !== '' && file_exists( $path );
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
		if ( $failures < 7 ) {
			$backoff = min( 6, $failures );
			$delay   = max( HOUR_IN_SECONDS, $backoff * HOUR_IN_SECONDS );
			wp_schedule_single_event( time() + $delay, 'bbcs_rugov_update_event', array( 'retry' ) );
		}
	}

	public static function fetchFromCloud( string $stored_hash = '' ) {
		$request = array();
		if ( '' !== $stored_hash ) {
			$request['hash'] = $stored_hash;
		}

		$result = BotBlockerWpRequest::send_to_cloud( $request, BOTBLOCKER_API_URL, 'rugov_sync' );
		if ( false === $result ) {
			$result = BotBlockerWpRequest::send_to_cloud( $request, BOTBLOCKER_API_GS_URL, 'rugov_sync' );
		}

		if ( ! is_array( $result ) ) {
			return false;
		}

		return $result;
	}

	public static function parseRanges( array $raw_ranges ): array {
		$ranges = array();
		foreach ( $raw_ranges as $entry ) {
			$entry = trim( (string) $entry );
			if ( $entry === '' ) {
				continue;
			}
			$type = BotBlockerIp::detectType( $entry );
			if ( 'ip' === $type || 'cidr' === $type ) {
				$ranges[] = $entry;
			}
		}
		return $ranges;
	}

	public static function doSync( string $reason = '' ): bool {
		$settings = ( method_exists( 'BotBlocker', 'getInstance' ) ? (array) BotBlocker::getInstance()->settings : array() );
		if ( empty( $settings['block_rkn'] ) ) {
			return false;
		}

		$is_manual = ( $reason === 'manual' );

		if ( $is_manual ) {
			self::releaseLock();
		}

		if ( ! self::acquireLock() ) {
			return false;
		}

		$now           = time();
		$stored_status = self::getStatus();
		$stored_hash   = $is_manual ? '' : (string) ( $stored_status['hash'] ?? '' );
		self::setStatus( array( 'last_attempt' => $now ) );

		$payload = self::fetchFromCloud( $stored_hash );

		if ( false === $payload ) {
			self::recordFailure( 'transport_error' );
			self::releaseLock();
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
			self::releaseLock();
			return true;
		}

		$new_hash   = (string) ( $payload['hash'] ?? '' );
		$raw_ranges = isset( $payload['ranges'] ) && is_array( $payload['ranges'] ) ? $payload['ranges'] : array();

		if ( empty( $raw_ranges ) ) {
			self::recordFailure( 'empty_payload' );
			self::releaseLock();
			return false;
		}

		$result = self::parseRanges( $raw_ranges );

		if ( count( $result ) < self::MIN_RANGES ) {
			self::recordFailure( 'sanity_floor:count=' . count( $result ) );
			self::releaseLock();
			return false;
		}

		$out_path = self::uploadsFile();
		if ( $out_path === '' ) {
			self::recordFailure( 'uploads_dir_unavailable' );
			self::releaseLock();
			return false;
		}

		$php  = "<?php\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\n";
		$php .= "// bbcs-rugov-data.php\n";
		$php .= "// Auto-generated CIDR list (VK networks excluded).\n";
		$php .= "// Source: BotBlocker Cloud API - rugov_sync\n\n";
		$php .= "return array(\n\t'bbcs_rugov' => array(\n";
		foreach ( $result as $cidr ) {
			$php .= "\t\t'" . addslashes( $cidr ) . "',\n";
		}
		$php .= "\t),\n);\n";

		$php = bbcs_data_file_sign( $php );

		$tmp = $out_path . '.tmp-' . wp_generate_password( 8, false, false );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( file_put_contents( $tmp, $php ) === false ) {
			self::recordFailure( 'write_failed' );
			self::releaseLock();
			return false;
		}

		if ( file_exists( $out_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $out_path );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( ! @rename( $tmp, $out_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
			if ( ! @copy( $tmp, $out_path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $tmp );
				self::recordFailure( 'swap_failed' );
				self::releaseLock();
				return false;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmp );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		@chmod( $out_path, 0644 );

		self::setStatus(
			array(
				'state'       => 'ok',
				'hash'        => $new_hash,
				'range_count' => count( $result ),
				'last_sync'   => $now,
				'last_error'  => '',
				'failures'    => 0,
			)
		);

		BotBlockerCache::clearFileCache();
		self::releaseLock();
		return true;
	}

	public static function freshnessHandler(): void {
		$settings = (array) BotBlocker::getInstance()->settings;
		if ( empty( $settings['block_rkn'] ) ) {
			return;
		}
		if ( ! self::isFilePresent() ) {
			self::scheduleUpdate( 'cron-missing' );
			return;
		}
		$status = self::getStatus();
		$age    = time() - (int) ( $status['last_sync'] ?? 0 );
		if ( $age > self::FRESHNESS_DAYS * DAY_IN_SECONDS ) {
			self::scheduleUpdate( 'cron-stale' );
		}
	}

	public static function selfHeal(): void {
		if ( wp_doing_cron() ) {
			return;
		}
		if ( get_transient( self::SELF_HEAL_KEY ) ) {
			return;
		}
		set_transient( self::SELF_HEAL_KEY, '1', HOUR_IN_SECONDS );

		$settings = (array) BotBlocker::getInstance()->settings;
		if ( empty( $settings['block_rkn'] ) ) {
			return;
		}
		if ( self::isFilePresent() ) {
			return;
		}
		if ( self::isEventQueued() ) {
			return;
		}
		self::scheduleUpdate( 'self-heal', 60 );
	}
}

add_action( 'bbcs_rugov_update_event', array( 'BotBlockerRugov', 'doSync' ), 10, 1 );
add_action( 'bbcs_rugov_freshness_task', array( 'BotBlockerRugov', 'freshnessHandler' ) );
add_action( 'init', array( 'BotBlockerRugov', 'selfHeal' ), 26 );
