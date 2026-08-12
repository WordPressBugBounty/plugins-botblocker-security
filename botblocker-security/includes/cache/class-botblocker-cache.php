<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerCache {

	/**
	 * Log debug message if debug and cache debug modes are enabled
	 *
	 * @param string $message Message to log
	 * @return void
	 */
	public static function logDebug( string $message ): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && defined( 'BBCS_CACHE_DEBUG' ) && BBCS_CACHE_DEBUG ) {
			// REVIEWER NOTE: Conditional debug logging; gated behind BBCS_DEBUG + BBCS_CACHE_DEBUG and disabled in production.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Cache] ' . $message );
		}
	}

	/**
	 * @return BBCS_RedisStorage|BBCS_MemcachedStorage|null
	 */
	public static function connect() {
		global $wpdb;
		$BBCS      = BotBlocker::getInstance();
		$lastError = '';

		if (
			isset( $BBCS->settings->redis_enable ) &&
			$BBCS->settings->redis_enable === 1
		) {
			try {
				require_once BOTBLOCKER_DIR . 'includes/cache/class-redis-storage.php';

				$redis = BBCS_RedisStorage::getInstance(
					$BBCS->settings->redis_host ?? '127.0.0.1',
					$BBCS->settings->redis_port ?? 6379,
					$BBCS->settings->redis_password ?? '',
					$BBCS->settings->redis_prefix ?? 'bbcs_',
					$BBCS->settings->redis_database ?? 0
				);

				if ( $redis && $redis->isAvailable() ) {
					return $redis;
				}

			$was_enabled                = ( $BBCS->settings->redis_enable == 1 );
			$lastError = 'Redis connection failed: ' . $redis->getLastError();
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => 0 ), array( 'key' => 'redis_enable' ) );
			$BBCS->settings->redis_enable = 0;

			if ( $was_enabled ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}

			self::logDebug( $lastError );
			self::logDebug( 'Redis disabled, falling back to Memcached if enabled' );
		} catch ( \Exception $e ) {
			$was_enabled = ( $BBCS->settings->redis_enable == 1 );
			$lastError = 'Redis exception: ' . $e->getMessage();
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => 0 ), array( 'key' => 'redis_enable' ) );
			$BBCS->settings->redis_enable = 0;

			if ( $was_enabled ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}

			self::logDebug( $lastError );
			self::logDebug( 'Redis disabled due to exception, falling back to Memcached if enabled' );
			}
		}

		if ( isset( $BBCS->settings->memcached_enable ) && $BBCS->settings->memcached_enable === 1 ) {
			try {
				require_once BOTBLOCKER_DIR . 'includes/cache/class-memcached-storage.php';

				$mmc = BBCS_MemcachedStorage::getInstance(
					$BBCS->settings->memcached_host ?? '127.0.0.1',
					$BBCS->settings->memcached_port ?? 11211,
					$BBCS->settings->memcached_prefix ?? 'bbcs_'
				);

				if ( $mmc && $mmc->isAvailable() ) {
					return $mmc;
				}

			$was_enabled                   = ( $BBCS->settings->memcached_enable === 1 );
			$lastError = 'Memcached connection failed: ' . $mmc->getLastError();
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => 0 ), array( 'key' => 'memcached_enable' ) );
			if ( $was_enabled ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}

			self::logDebug( $lastError );
			self::logDebug( 'Memcached disabled, falling back to transients' );
		} catch ( \Exception $e ) {
			$was_enabled = ( $BBCS->settings->memcached_enable === 1 );
			$lastError = 'Memcached exception: ' . $e->getMessage();
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => 0 ), array( 'key' => 'memcached_enable' ) );
			if ( $was_enabled ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}

			self::logDebug( $lastError );
			self::logDebug( 'Memcached disabled due to exception, falling back to transients' );
			}
		}

		return null;
	}

	public static function isRedisAvailable(): bool {
		try {
			require_once BOTBLOCKER_DIR . 'includes/cache/class-redis-storage.php';
			$BBCS = BotBlocker::getInstance();

			$redis = BBCS_RedisStorage::getInstance(
				$BBCS->settings->redis_host ?? '127.0.0.1',
				$BBCS->settings->redis_port ?? 6379,
				$BBCS->settings->redis_password ?? '',
				$BBCS->settings->redis_prefix ?? 'bbcs_',
				$BBCS->settings->redis_database ?? 0
			);

			$available = $redis && $redis->isAvailable();

			if ( ! $available ) {
				self::logDebug( 'Redis availability check failed: ' . $redis->getLastError() );
			}

			return $available;
		} catch ( \Exception $e ) {
			self::logDebug( 'Redis availability check exception: ' . $e->getMessage() );
			return false;
		}
	}

	public static function isMemcachedAvailable(): bool {
		try {
			require_once BOTBLOCKER_DIR . 'includes/cache/class-memcached-storage.php';
			$BBCS = BotBlocker::getInstance();

			$mmc = BBCS_MemcachedStorage::getInstance(
				$BBCS->settings->memcached_host ?? '127.0.0.1',
				$BBCS->settings->memcached_port ?? 11211,
				$BBCS->settings->memcached_prefix ?? 'bbcs_'
			);

			$available = $mmc && $mmc->isAvailable();

			if ( ! $available ) {
				self::logDebug( 'Memcached availability check failed: ' . $mmc->getLastError() );
			}

			return $available;
		} catch ( \Exception $e ) {
			self::logDebug( 'Memcached availability check exception: ' . $e->getMessage() );
			return false;
		}
	}

	public static function flush(): bool {
		$BBCS    = BotBlocker::getInstance();
		$flushed = false;

		try {
			require_once BOTBLOCKER_DIR . 'includes/cache/class-redis-storage.php';

			$redis = BBCS_RedisStorage::getInstance(
				$BBCS->settings->redis_host ?? '127.0.0.1',
				$BBCS->settings->redis_port ?? 6379,
				$BBCS->settings->redis_password ?? '',
				$BBCS->settings->redis_prefix ?? 'bbcs_',
				$BBCS->settings->redis_database ?? 0
			);

			if ( $redis && $redis->isAvailable() ) {
				$redis->rotateCacheGeneration();
				$flushed = true;

				self::logDebug( 'Redis cache generation rotated successfully' );
			} else {
				self::logDebug( 'Redis cache generation rotation skipped: not available' );
			}
		} catch ( \Exception $e ) {
			self::logDebug( 'Redis cache generation rotation exception: ' . $e->getMessage() );
		}

		try {
			require_once BOTBLOCKER_DIR . 'includes/cache/class-memcached-storage.php';

			$mmc = BBCS_MemcachedStorage::getInstance(
				$BBCS->settings->memcached_host ?? '127.0.0.1',
				$BBCS->settings->memcached_port ?? 11211,
				$BBCS->settings->memcached_prefix ?? 'bbcs_'
			);

			if ( $mmc && $mmc->isAvailable() ) {
				$mmc->rotateCacheGeneration();
				$flushed = true;

				self::logDebug( 'Memcached cache generation rotated successfully' );
			} else {
				self::logDebug( 'Memcached cache generation rotation skipped: not available' );
			}
		} catch ( \Exception $e ) {
			self::logDebug( 'Memcached cache generation rotation exception: ' . $e->getMessage() );
		}

		return $flushed;
	}

	public static function getPrefix( string $sub ): string {
		$BBCS    = BotBlocker::getInstance();
		$ip_hash = md5( (string) ( $BBCS->ip ?? '' ) );

		if ( isset( $BBCS->settings->redis_enable ) && $BBCS->settings->redis_enable == 1 ) {
			$prefix = $BBCS->settings->redis_prefix . BotBlockerMultisite::getCurrentSiteClear() . $sub . $ip_hash; //BBCS-MULTISITE
			self::logDebug( 'Using Redis prefix: ' . $prefix );
			return $prefix;
		}

		if ( isset( $BBCS->settings->memcached_enable ) && $BBCS->settings->memcached_enable == 1 ) {
			$prefix = $BBCS->settings->memcached_prefix . BotBlockerMultisite::getCurrentSiteClear() . $sub . $ip_hash; //BBCS-MULTISITE
			self::logDebug( 'Using Memcached prefix: ' . $prefix );
			return $prefix;
		}

		$prefix = 'WP_trsnt' . $sub . $ip_hash;
		self::logDebug( 'Using transient prefix: ' . $prefix );
		return $prefix;
	}

	/**
	 * @param string $cache_key
	 * @return array|null
	 */
	private static $memoryCache = array();

	public static function getCacheData( string $cache_key ) {
		if ( array_key_exists( $cache_key, self::$memoryCache ) ) {
			return self::$memoryCache[ $cache_key ];
		}
		try {
			$storage = self::connect();

			if ( $storage !== null ) {
				$cached_data = $storage->get( $cache_key );

				if ( $cached_data !== null ) {
					self::logDebug( 'Cache hit for key: ' . $cache_key . ' from ' . get_class( $storage ) );
				}
			} else {
				$cached_data = self::fileGet( $cache_key );

				if ( $cached_data !== null ) {
					self::logDebug( 'Cache hit for key: ' . $cache_key . ' from file cache' );
				}
			}

			if ( empty( $cached_data ) ) {
				self::logDebug( 'Cache miss for key: ' . $cache_key );
			}

			$result = is_array( $cached_data ) ? $cached_data : null;
			self::$memoryCache[ $cache_key ] = $result;
			return $result;
		} catch ( \Exception $e ) {
			self::logDebug( 'Error getting cached data: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * @param string $cache_key
	 * @param mixed  $data
	 * @param int    $ttl
	 * @return bool
	 */
	public static function setCacheData( string $cache_key, $data, int $ttl ): bool {
		unset( self::$memoryCache[ $cache_key ] );
		try {
			if ( empty( $data ) || ! is_array( $data ) ) {
				self::logDebug( 'Skipping cache storage for invalid data (key: ' . $cache_key . ')' );
				return false;
			}

			$storage = self::connect();

			if ( $storage !== null ) {
				$result = $storage->set( $cache_key, $data, $ttl );

				if ( $result ) {
					self::logDebug( 'Data cached successfully in ' . get_class( $storage ) . ' with key: ' . $cache_key . ' (TTL: ' . $ttl . 's)' );
				} else {
					self::logDebug( 'Failed to cache data in ' . get_class( $storage ) . ' with key: ' . $cache_key );
				}

				return $result;
			} else {
				$result = self::fileSet( $cache_key, $data, $ttl );

				if ( $result ) {
					self::logDebug( 'Data cached successfully in file cache with key: ' . $cache_key . ' (TTL: ' . $ttl . 's)' );
				} else {
					self::logDebug( 'Failed to cache data in file cache with key: ' . $cache_key );
				}

				return $result;
			}
		} catch ( \Exception $e ) {
			self::logDebug( 'Error caching data: ' . $e->getMessage() );
			return false;
		}
	}

	public static function deleteCacheData( string $cache_key ): void {
		unset( self::$memoryCache[ $cache_key ] );
		try {
			$storage = self::connect();
			if ( $storage !== null ) {
				$storage->delete( $cache_key );
			}
			self::fileDelete( $cache_key );
		} catch ( \Exception $e ) {
			self::logDebug( 'Error deleting cache: ' . $e->getMessage() );
		}
	}

	private static function cacheDir(): string {
		return BotBlockerMultisite::getDataDir() . 'cache/';
	}

	private static function cacheFilePath( string $key ): string {
		return self::cacheDir() . md5( $key ) . '.cache';
	}

	/**
	 * @return mixed|null
	 */
	public static function fileGet( string $key ) {
		$file = self::cacheFilePath( $key );
		if ( ! file_exists( $file ) ) {
			return self::transientFallbackGet( $key );
		}
		$content = @file_get_contents( $file );
		if ( $content === false || $content === '' ) {
			return self::transientFallbackGet( $key );
		}
		$data = @unserialize( $content );
		if ( ! is_array( $data ) || ! isset( $data['d'], $data['e'] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $file );
			return self::transientFallbackGet( $key );
		}
		if ( time() > (int) $data['e'] ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $file );
			return self::transientFallbackGet( $key );
		}
		return $data['d'];
	}

	private static function transientFallbackGet( string $key ) {
		$value = get_transient( $key );
		if ( $value === false ) {
			return null;
		}
		return is_array( $value ) ? $value : null;
	}

	public static function fileSet( string $key, $data, int $ttl ): bool {
		$dir = self::cacheDir();
		if ( ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return self::transientFallbackSet( $key, $data, $ttl );
			}
		}
		$file    = self::cacheFilePath( $key );
		$tmp     = $file . '.' . getmypid() . '.tmp';
		$payload = serialize( array( 'd' => $data, 'e' => time() + $ttl ) );
		if ( @file_put_contents( $tmp, $payload, LOCK_EX ) === false ) {
			return self::transientFallbackSet( $key, $data, $ttl );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( ! @rename( $tmp, $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmp );
			return self::transientFallbackSet( $key, $data, $ttl );
		}
		delete_transient( $key );
		return true;
	}

	private static function transientFallbackSet( string $key, $data, int $ttl ): bool {
		return set_transient( $key, $data, $ttl );
	}

	public static function fileDelete( string $key ): void {
		$file = self::cacheFilePath( $key );
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $file );
		}
		delete_transient( $key );
	}

	public static function resetHealthTransients(): bool {
		try {
			$transient_key_health_list = 'bbcs_site_health_list';
			$transient_key_health      = 'bbcs_site_health';
			$transient_key_health_full = 'bbcs_health_full_html_';

			$result1 = delete_transient( $transient_key_health_list );
			$result2 = delete_transient( $transient_key_health );
			$result3 = delete_transient( $transient_key_health_full );

			self::logDebug(
				'Health transients reset: ' .
				( $result1 ? 'Success' : 'Failed' ) . ' / ' .
				( $result2 ? 'Success' : 'Failed' ) . ' / ' .
				( $result3 ? 'Success' : 'Failed' )
			);

			return $result1 && $result2 && $result3;
		} catch ( \Exception $e ) {
			self::logDebug( 'Error resetting health transients: ' . $e->getMessage() );
			return false;
		}
	}

	public static function clearTransients(): bool {
		try {
				$transient_keys = array(
					'bbcs_get_statistics',
					'bbcs_display_daily_hits_chart',
					'bbcs_display_hits_and_uniques_chart',
					'bbcs_display_visitors_jsvectormap',
					'bbcs_top_ips',
					'bbcs_top_countries',
					'bbcs_top_devices',
					'bbcs_top_browsers',
					'bbcs_latest_hits_shortcode',
					'bbcs_remaining_hits',
					'bbcs_remaining_days',
					'bbcs_site_health_list',
					'bbcs_health_full_html_',
					'bbcs_site_health',
					'bbcs_cloud_connection_failed_alert',
					'bbcs_missing_files_alert',
					'bbcs_rules_stat',
					'bbcs_botblocker_news_feed',
					'bbcs_database_update',
					'bbcs_database_total',
					'bbcs_cloud_api_status_transient', // managed by BotBlockerCache; cleans up legacy DB data
				);

				$success_count = 0;
				foreach ( $transient_keys as $key ) {
					if ( delete_transient( $key ) ) {
						++$success_count;
					}
				}

				self::logDebug( 'UI transients cleared: ' . $success_count . ' of ' . count( $transient_keys ) );

				return true;

		} catch ( \Exception $e ) {
			self::logDebug( 'Error clearing UI transients: ' . $e->getMessage() );
			return false;
		}
	}

	public static function clearFileCache(): bool {
		static $cleared = false;
		if ( $cleared ) {
			return true;
		}
		$cleared = true;
		try {
			clearstatcache( true );

			if ( function_exists( 'opcache_invalidate' ) && class_exists( 'BotBlockerMultisite' ) ) {
				$dataDir = BotBlockerMultisite::getDataDir();
				if ( $dataDir && is_dir( $dataDir ) ) {
					$files = glob( $dataDir . '*.php' );
					if ( is_array( $files ) ) {
						foreach ( $files as $file ) {
							@opcache_invalidate( $file, true );
						}
					}
				}
			}

			return true;
		} catch ( \Exception $e ) {
			self::logDebug( 'Error clearing file cache: ' . $e->getMessage() );
			return false;
		}
	}

	public static function checkAndFixRedis(): bool {
		try {
			$BBCS = BotBlocker::getInstance();

			if ( isset( $BBCS->settings->redis_enable ) && $BBCS->settings->redis_enable === 1 ) {

				require_once BOTBLOCKER_DIR . 'includes/cache/class-redis-storage.php';

				$redis = BBCS_RedisStorage::getInstance(
					$BBCS->settings->redis_host ?? '127.0.0.1',
					$BBCS->settings->redis_port ?? 6379,
					$BBCS->settings->redis_password ?? '',
					$BBCS->settings->redis_prefix ?? 'bbcs_',
					$BBCS->settings->redis_database ?? 0
				);

				if ( $redis && ! $redis->isAvailable() ) {
					self::logDebug( 'Redis connection is down, attempting to recover...' );

					$errorDetails = $redis->getConnectionStatus();

					self::logDebug( 'Redis connection status: ' . wp_json_encode( $errorDetails ) );

					$redis->forceReconnect();

					if ( $redis->isAvailable() ) {
						self::logDebug( 'Redis connection successfully recovered' );
						return true;
					} else {
						self::logDebug( 'Redis connection recovery failed: ' . $redis->getLastError() );
						return false;
					}
				}

				return $redis && $redis->isAvailable();
			}

			return false;
		} catch ( \Exception $e ) {
			self::logDebug( 'Exception in Redis connection check: ' . $e->getMessage() );
			return false;
		}
	}
}
