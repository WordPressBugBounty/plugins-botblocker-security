<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BBCS_Scan_Lock_Trait {

	abstract protected function scanLockPrefix(): string;

	abstract protected function scanStaleFilterName(): string;

	public function scanStaleThreshold(): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- concrete implementations return prefixed hooks (bbcs_malware_scan_stale_threshold, bbcs_ts_scan_stale_threshold); PHPCS cannot trace abstract method return values
		$threshold = (int) apply_filters( $this->scanStaleFilterName(), 15 * MINUTE_IN_SECONDS );
		return $threshold > 0 ? $threshold : 15 * MINUTE_IN_SECONDS;
	}

	public function scanStateIsStale( array $state ): bool {
		if ( ( $state['status'] ?? '' ) !== 'running' ) {
			return false;
		}
		$updated_at = isset( $state['updated_at'] ) ? strtotime( (string) $state['updated_at'] ) : 0;
		if ( ! $updated_at ) {
			return true;
		}
		return ( time() - $updated_at ) > $this->scanStaleThreshold();
	}

	public function scanLockName(): string {
		global $wpdb;
		return $this->scanLockPrefix() . md5( (string) $wpdb->prefix );
	}

	public function scanLockTimeout(): int {
		$timeout = (int) apply_filters( 'bbcs_scan_lock_timeout', 5 * MINUTE_IN_SECONDS );
		return $timeout > 0 ? $timeout : 5 * MINUTE_IN_SECONDS;
	}

	public function scanLockAcquire(): bool {
		$lock_name = $this->scanLockName();

		// Check whether another process already holds a fresh lock.
		if ( get_transient( $lock_name ) !== false ) {
			return false;
		}

		$acquired = set_transient( $lock_name, time(), $this->scanLockTimeout() );
		return (bool) $acquired;
	}

	public function scanLockRelease(): void {
		delete_transient( $this->scanLockName() );
	}
}
