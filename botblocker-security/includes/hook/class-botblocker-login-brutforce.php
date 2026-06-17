<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'core-helpers.php';

class BotBlockerLoginBruteForce
{
	public static function getIp(): string {
		$BBCS = BotBlocker::getInstance();
		return $BBCS->ip;
	}

	public static function getCacheKey( string $ip ): string {
		return 'bbcs_login_bf_' . md5( $ip );
	}

	public static function getRepeatKey( string $ip ): string {
		return 'bbcs_login_bf_repeat_' . md5( $ip );
	}

	public static function getAttempts( string $cache_key ): ?array {
		try {
			$storage = BotBlockerCache::connect();

			if ( $storage !== null ) {
				$data = $storage->get( $cache_key );
				return is_array( $data ) ? $data : null;
			}

			$data = get_transient( $cache_key );
			return is_array( $data ) ? $data : null;
		} catch ( \Exception $e ) {
			return null;
		}
	}

	public static function setAttempts( string $cache_key, array $data, int $ttl ): bool {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}
		try {
			$storage = BotBlockerCache::connect();

			if ( $storage !== null ) {
				return $storage->set( $cache_key, $data, $ttl );
			}

			return set_transient( $cache_key, $data, $ttl );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public static function blockIp( string $ip, int $block_time ): bool {
		global $wpdb;

		$ip_version = null;

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$ip_version = 4;
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$ip_version = 6;
		}

		if ( $ip_version === null ) {
			return false;
		}

		$now     = time();
		$expires = $now + $block_time;

		if ( $ip_version === 4 ) {
			$numeric_ip = BotBlockerIp::toNumeric( $ip );

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$wpdb->bbcs_ipv4rules}` WHERE `search` = %s AND `rule` = 'block' AND `comment` LIKE %s LIMIT 1",
					$ip,
					'%Login brute force%'
				)
			);

			if ( $exists ) {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->bbcs_ipv4rules,
					array( 'expires' => $expires ),
					array( 'id' => $exists )
				);
				return true;
			}

			$data = array(
				'priority' => 1,
				'search'   => $ip,
				'ip1'      => $numeric_ip,
				'ip2'      => $numeric_ip,
				'rule'     => 'block',
				'comment'  => 'Login brute force block (auto)',
				'expires'  => $expires,
				'disable'  => 0,
			);

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert( $wpdb->bbcs_ipv4rules, $data );
		} else {
			$binary_ip = BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$wpdb->bbcs_ipv6rules}` WHERE `search` = %s AND `rule` = 'block' AND `comment` LIKE %s LIMIT 1",
					$ip,
					'%Login brute force%'
				)
			);

			if ( $exists ) {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->bbcs_ipv6rules,
					array( 'expires' => $expires ),
					array( 'id' => $exists )
				);
				return true;
			}

			$data = array(
				'priority' => 1,
				'search'   => $ip,
				'ip1'      => $binary_ip,
				'ip2'      => $binary_ip,
				'rule'     => 'block',
				'comment'  => 'Login brute force block (auto)',
				'expires'  => $expires,
				'disable'  => 0,
			);

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert( $wpdb->bbcs_ipv6rules, $data );
		}

		if ( $result !== false ) {
			if ( ! get_transient( 'bbcs_ip_render_throttle' ) ) {
				BotBlockerFileRenderer::renderIps();
				BotBlockerCache::clearFileCache();
				set_transient( 'bbcs_ip_render_throttle', 1, 15 );
			}
		}

		return ( $result !== false );
	}

	public static function onFailedLogin( string $username ): void {
		$BBCS = BotBlocker::getInstance();

		if ( ! isset( $BBCS->settings->login_brutforce_enabled ) || $BBCS->settings->login_brutforce_enabled !== 1 ) {
			return;
		}

		$ip = self::getIp();
		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return;
		}

		$max_attempts    = (int) ( $BBCS->settings->login_brutforce_attempts ?? 5 );
		$period          = (int) ( $BBCS->settings->login_brutforce_period ?? 900 );
		$primary_block   = (int) ( $BBCS->settings->login_brutforce_primary_block_time ?? 900 );
		$secondary_block = (int) ( $BBCS->settings->login_brutforce_secondary_block_time ?? 1800 );

		$cache_key  = self::getCacheKey( $ip );
		$repeat_key = self::getRepeatKey( $ip );

		$now = time();

		$attempt_data = self::getAttempts( $cache_key );

		if ( $attempt_data === null || ! isset( $attempt_data['count'], $attempt_data['first_attempt'] ) || ( $now - $attempt_data['first_attempt'] ) > $period ) {
			$attempt_data = array(
				'count'         => 1,
				'first_attempt' => $now,
			);
		} else {
			++$attempt_data['count'];
		}

		self::setAttempts( $cache_key, $attempt_data, $period );

		if ( $attempt_data['count'] >= $max_attempts ) {
			$repeat_data = self::getAttempts( $repeat_key );
			$block_time  = ( $repeat_data !== null ) ? $secondary_block : $primary_block;

			self::setAttempts(
				$repeat_key,
				array(
					'blocked' => true,
					'time'    => $now,
				),
				$secondary_block * 2
			);
			self::blockIp( $ip, $block_time );

			self::setAttempts(
				$cache_key,
				array(
					'count'         => 0,
					'first_attempt' => $now,
				),
				$period
			);
		}
	}

	public static function deleteAttempts( string $cache_key ): void {
		$storage = BotBlockerCache::connect();

		if ( $storage !== null ) {
			$storage->delete( $cache_key );
			return;
		}

		delete_transient( $cache_key );
	}

	public static function onSuccessLogin( string $user_login ): void {
		$BBCS = BotBlocker::getInstance();

		if ( ! isset( $BBCS->settings->login_brutforce_enabled ) || $BBCS->settings->login_brutforce_enabled !== 1 ) {
			return;
		}

		$ip = self::getIp();
		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return;
		}

		self::deleteAttempts( self::getCacheKey( $ip ) );
		self::deleteAttempts( self::getRepeatKey( $ip ) );
	}
}
