<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration 2.12.0 — seed the extended path templates as DISABLED rows.
 *
 * Finding FF (P9, 2026-09-04): the extended allow-template list (REST,
 * XML-RPC, admin pages, ...) was dead code — never seeded, so nothing was
 * exempt from the challenge wall and admins had no one-click templates in
 * the path-rules UI. Existing installs get the same DISABLED rows fresh
 * installs now seed via BotBlockerSeedData::insertExtendedPaths().
 * Idempotent: INSERT IGNORE on the unique search row.
 */
function bbcs_migration_2_12_0() {
	return bbcs_migration_2_12_0_seed_extended_paths();
}

function bbcs_migration_2_12_0_seed_extended_paths(): bool {
	global $wpdb;

	if ( empty( $wpdb->bbcs_path ) ) {
		return false;
	}

	$extended_paths = BotBlockerSeedData::extendedPathTemplates();

	foreach ( $extended_paths as $path ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `{$wpdb->bbcs_path}` (priority, search, rule, comment, disable) VALUES (%d, %s, %s, %s, %d)",
				$path['priority'],
				$path['search'],
				$path['rule'],
				$path['comment'],
				$path['disable']
			)
		);
	}

	return true;
}
