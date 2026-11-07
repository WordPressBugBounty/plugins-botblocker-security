<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_13_0() {
	global $wpdb;

	if ( empty( $wpdb->bbcs_ipv4rules ) ) {
		return false;
	}

	BotBlockerSeedData::insertDefaultIpRules();
	BotBlockerFileRenderer::renderIps();

	return true;
}
