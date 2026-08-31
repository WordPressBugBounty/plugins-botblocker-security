<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deduplicate Early Phase toggles (Early Init / MU-plugin).
 *
 * Ensures mutual exclusion: at most one of early_init_enable / mu_enable can be 1 at a time.
 * When a key is enabled, the other is forced to 0. When a key is disabled, the other is
 * left untouched (both can be off).
 *
 * Call this after writing the primary key to the DB. It will update the DB for the other key
 * and return the final state for both keys so callers can proceed with filesystem operations.
 *
 * @param string $changed_key  The key that was just written ('early_init_enable' or 'mu_enable').
 * @param int    $changed_val  The value that was just written (0 or 1).
 * @return array{early_init_enable: int, mu_enable: int} Final DB state for both keys.
 */
class BotBlockerEarlyPhaseDedup {

	public static function dedup( string $changed_key, int $changed_val ): array {
		global $wpdb;

		$early_key = 'early_init_enable';
		$mu_key    = 'mu_enable';

		if ( $changed_val === 1 && $changed_key !== 'disable' && ( $changed_key === $early_key || $changed_key === $mu_key ) ) {
			// Enabling one → force the other off.
			$other_key = ( $changed_key === $mu_key ) ? $early_key : $mu_key;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => 0 ), array( 'key' => $other_key ) );
		}

		// Read-back final state.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$early_val = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT value FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
			$early_key
		) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$mu_val = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT value FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
			$mu_key
		) );

		return array(
			$early_key => $early_val,
			$mu_key    => $mu_val,
		);
	}
}
