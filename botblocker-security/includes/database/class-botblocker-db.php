<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerDb {

	public static function storePTRrule(): bool {
		$BBCS = BotBlocker::getInstance();
		global $wpdb;

		if ( $BBCS->ip_version != 4 && $BBCS->ip_version != 6 ) {
			return false;
		}

		$subnet_setting = isset( $BBCS->settings->ptrcache_subnet ) ? $BBCS->settings->ptrcache_subnet : '24-64';
		$parts          = explode( '-', $subnet_setting );
		$mask_v4        = isset( $parts[0] ) ? (int) $parts[0] : 24;
		$mask_v6        = isset( $parts[1] ) ? (int) $parts[1] : 64;

		$search_cidr = BotBlockerIp::computePtrSubnet( $BBCS->ip, $BBCS->ip_version, $mask_v4, $mask_v6 );

		if ( $BBCS->ip_version == 4 ) {
			// REVIEWER NOTE: custom table operations require direct database queries.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_entry = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
					$search_cidr
				)
			);
		} else {
			// REVIEWER NOTE: custom table operations require direct database queries.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_entry = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s",
					$search_cidr
				)
			);
		}

		if ( $existing_entry > 0 ) {
			return true;
		}

		$ips = BotBlockerIp::toRange( $search_cidr );

		if ( isset( $ips[2] ) && $ips[2] === 0 ) {
			return false;
		}

		$ttl_days = isset( $BBCS->settings->ptrcache_rule_ttl ) ? (int) $BBCS->settings->ptrcache_rule_ttl : 90;
		if ( $ttl_days < 1 ) {
			$ttl_days = 90;
		}

		$data = array(
			'priority' => '10',
			'search'   => sanitize_text_field( $search_cidr ),
			'rule'     => BBCS_RULE_ALLOW,
			'comment'  => sanitize_text_field( $BBCS->useragent . ' (ip: ' . $BBCS->ip . ')' ),
			'expires'  => ( $BBCS->time + $ttl_days * DAY_IN_SECONDS ),
		);

		if ( $BBCS->ip_version == 4 ) {
			$data['ip1'] = BotBlockerIp::toNumeric( $ips[0] );
			$data['ip2'] = BotBlockerIp::toNumeric( $ips[1] );
		} else {
			$data['ip1'] = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ips[0] ) );
			$data['ip2'] = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ips[1] ) );
		}

		/**
		 * REVIEWER NOTE:
		 * - custom table operations require direct database queries.
		 * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
		 * - no user input is interpolated into this query.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $BBCS->ip_version == 4 ) {
			$result = $wpdb->insert( $wpdb->bbcs_ipv4rules, $data );
		} else {
			$result = $wpdb->insert( $wpdb->bbcs_ipv6rules, $data );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return ( $result !== false );
	}



	public static function togglePower( int $state ): void {
		global $wpdb;

		/**
		 * REVIEWER NOTE:
		 * - custom table operations require direct database queries.
		 * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
		 * - no user input is interpolated into this query.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", 'disable' ) );
		if ( $exists ) {
			$updated = $wpdb->update(
				$wpdb->bbcs_settings,
				array( 'value' => $state ),
				array( 'key' => 'disable' ),
				array( '%d' ),
				array( '%s' )
			);
		} else {
			$updated = $wpdb->insert(
				$wpdb->bbcs_settings,
				array( 'key' => 'disable', 'value' => $state ),
				array( '%s', '%d' )
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $updated !== false ) {
			BotBlockerFileRenderer::generateSettingsFile();
		} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [DB] Error change security state of BotBlocker' );
		}
	}

	public static function getIPNotLikeSQL(): array {
		$BBCS = BotBlocker::getInstance();
		if ( empty( $BBCS->self_ips ) || ! is_array( $BBCS->self_ips ) ) {
			return array( '', array() );
		}
		$ips = array();
		foreach ( $BBCS->self_ips as $ip => $status ) {
			if ( $status === BBCS_RULE_ALLOW ) {
				$ips[] = $ip;
			}
		}
		if ( empty( $ips ) ) {
			return array( '', array() );
		}
		$placeholders = implode( ', ', array_fill( 0, count( $ips ), '%s' ) );
		$fragment     = " AND ip NOT IN ( $placeholders )";
		return array( $fragment, $ips );
	}

	public static function generateAllFiles(): bool {
		BotBlockerFileRenderer::renderRules();
		BotBlockerFileRenderer::renderPaths();
		BotBlockerFileRenderer::renderSearchEngines();
		BotBlockerFileRenderer::renderLlmTrusted();
		BotBlockerFileRenderer::renderAsn();
		BotBlockerFileRenderer::renderIps();
		BotBlockerFileRenderer::renderProxy();

		BotBlockerCache::clearFileCache();
		return true;
	}
}
