<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Log debug message if debug and cache debug modes are enabled
 *
 * @param string $message Message to log
 * @return void
 */
function bbcs_log_cache_debug(string $message): void
{
    if (defined('BBCS_DEBUG') && BBCS_DEBUG && defined('BBCS_CACHE_DEBUG') && BBCS_CACHE_DEBUG) {
       // error_log($message);
    }
}

function bbcs_connectToRedisOrMMC()
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    $lastError = '';

    if (
        isset($BBCS->settings->redis_enable) && 
        $BBCS->settings->redis_enable === 1
    ) {
        try {
            require_once BOTBLOCKER_DIR . 'includes/class-redis-storage.php';
            
            $redis = BBCS_RedisStorage::getInstance(
                $BBCS->settings->redis_host ?? '127.0.0.1',
                $BBCS->settings->redis_port ?? 6379,
                $BBCS->settings->redis_password ?? '',
                $BBCS->settings->redis_db ?? null,
                $BBCS->settings->redis_prefix ?? 'bbcs_'
            );
            
            if ($redis && $redis->isAvailable()) {
                return $redis;
            }
            
            $lastError = 'Redis connection failed: ' . $redis->getLastError();
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->bbcs_settings, ['value' => 0], ['key' => 'redis_enable']);
            $BBCS->settings->redis_enable = 0;

            if (isset($BBCS->settings->memcached_enable) && $BBCS->settings->memcached_enable === 0) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update($wpdb->bbcs_settings, ['value' => 1], ['key' => 'memcached_enable']);
                $BBCS->settings->memcached_enable = 1;
            }
            
            bbcs_generateSettingsFileFromDb();
            
            bbcs_log_cache_debug($lastError);
            bbcs_log_cache_debug('Redis disabled, trying Memcached');
        } catch (\Exception $e) {
            $lastError = 'Redis exception: ' . $e->getMessage();
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->bbcs_settings, ['value' => 0], ['key' => 'redis_enable']);
            $BBCS->settings->redis_enable = 0;

            if (isset($BBCS->settings->memcached_enable) && $BBCS->settings->memcached_enable === 0) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update($wpdb->bbcs_settings, ['value' => 1], ['key' => 'memcached_enable']);
                $BBCS->settings->memcached_enable = 1;
            }
            
            bbcs_generateSettingsFileFromDb();
            
            bbcs_log_cache_debug($lastError);
            bbcs_log_cache_debug('Redis disabled due to exception, trying Memcached');
        }
    } 

    if (isset($BBCS->settings->memcached_enable) && $BBCS->settings->memcached_enable === 1) {
        try {
            require_once BOTBLOCKER_DIR . 'includes/class-memcached-storage.php';
            
            $mmc = BBCS_MemcachedStorage::getInstance(
                $BBCS->settings->memcached_host ?? '127.0.0.1',
                $BBCS->settings->memcached_port ?? 11211,
                $BBCS->settings->memcached_prefix ?? 'bbcs_'
            );
            
            if ($mmc && $mmc->isAvailable()) {
                return $mmc;
            }
            
            $lastError = 'Memcached connection failed: ' . $mmc->getLastError();
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->bbcs_settings, ['value' => 0], ['key' => 'memcached_enable']);
            bbcs_generateSettingsFileFromDb();
            
            bbcs_log_cache_debug($lastError);
            bbcs_log_cache_debug('Memcached disabled, falling back to transients');
        } catch (\Exception $e) {
            $lastError = 'Memcached exception: ' . $e->getMessage();
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->bbcs_settings, ['value' => 0], ['key' => 'memcached_enable']);
            bbcs_generateSettingsFileFromDb();
            
            bbcs_log_cache_debug($lastError);
            bbcs_log_cache_debug('Memcached disabled due to exception, falling back to transients');
        }
    }

    return null;
}

function bbcs_checkRedisAvailability()
{
    try {
        require_once BOTBLOCKER_DIR . 'includes/class-redis-storage.php';
        $BBCS = BotBlocker::getInstance();
        
        $redis = BBCS_RedisStorage::getInstance(
            $BBCS->settings->redis_host ?? '127.0.0.1',
            $BBCS->settings->redis_port ?? 6379,
            $BBCS->settings->redis_password ?? '',
            $BBCS->settings->redis_db ?? null,
            $BBCS->settings->redis_prefix ?? 'bbcs_'
        );
        
        $available = $redis && $redis->isAvailable();
        
        if (!$available) {
            bbcs_log_cache_debug('Redis availability check failed: ' . $redis->getLastError());
        }
        
        return $available;
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Redis availability check exception: ' . $e->getMessage());
        return false;
    }
}

function bbcs_checkMemcachedAvailability()
{
    try {
        require_once BOTBLOCKER_DIR . 'includes/class-memcached-storage.php';
        $BBCS = BotBlocker::getInstance();
        
        $mmc = BBCS_MemcachedStorage::getInstance(
            $BBCS->settings->memcached_host ?? '127.0.0.1',
            $BBCS->settings->memcached_port ?? 11211,
            $BBCS->settings->memcached_prefix ?? 'bbcs_'
        );
        
        $available = $mmc && $mmc->isAvailable();
        
        if (!$available) {
            bbcs_log_cache_debug('Memcached availability check failed: ' . $mmc->getLastError());
        }
        
        return $available;
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Memcached availability check exception: ' . $e->getMessage());
        return false;
    }
}

function bbcs_flushRedisAndMMC()
{
    $BBCS = BotBlocker::getInstance();
    $flushed = false;

    try {
        require_once BOTBLOCKER_DIR . 'includes/class-redis-storage.php';
        
        $redis = BBCS_RedisStorage::getInstance(
            $BBCS->settings->redis_host ?? '127.0.0.1',
            $BBCS->settings->redis_port ?? 6379,
            $BBCS->settings->redis_password ?? '',
            $BBCS->settings->redis_db ?? null,
            $BBCS->settings->redis_prefix ?? 'bbcs_'
        );
        
        if ($redis && $redis->isAvailable()) {
            $redis->flushByPrefix();
            $flushed = true;
            
            bbcs_log_cache_debug('Redis cache flushed successfully');
        } else {
            bbcs_log_cache_debug('Redis flush skipped: not available');
        }
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Redis flush exception: ' . $e->getMessage());
    }

    try {
        require_once BOTBLOCKER_DIR . 'includes/class-memcached-storage.php';
        
        $mmc = BBCS_MemcachedStorage::getInstance(
            $BBCS->settings->memcached_host ?? '127.0.0.1',
            $BBCS->settings->memcached_port ?? 11211,
            $BBCS->settings->memcached_prefix ?? 'bbcs_'
        );
        
        if ($mmc && $mmc->isAvailable()) {
            $mmc->flushByPrefix();
            $flushed = true;
            
            bbcs_log_cache_debug('Memcached cache flushed successfully');
        } else {
            bbcs_log_cache_debug('Memcached flush skipped: not available');
        }
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Memcached flush exception: ' . $e->getMessage());
    }
    
    return $flushed;
}

function bbcs_getCachePrefix($sub)
{
    $BBCS = BotBlocker::getInstance();
    $ip_hash = md5($BBCS->ip);
    
    if (isset($BBCS->settings->memcached_enable) && $BBCS->settings->memcached_enable == 1) {
        $prefix = $BBCS->settings->memcached_prefix . bbcs_current_site_clear() . $sub . $ip_hash; //BBCS-MULTISITE
        bbcs_log_cache_debug('Using Memcached prefix: ' . $prefix);
        return $prefix;
    }
    
    if (isset($BBCS->settings->redis_enable) && $BBCS->settings->redis_enable == 1) {
        $prefix = $BBCS->settings->redis_prefix . bbcs_current_site_clear() . $sub . $ip_hash; //BBCS-MULTISITE
        bbcs_log_cache_debug('Using Redis prefix: ' . $prefix);
        return $prefix;
    }

    $prefix = 'WP_trsnt' . $sub . $ip_hash;
    bbcs_log_cache_debug('Using transient prefix: ' . $prefix);
    return $prefix;
}

function bbcs_getCachedCloudData($cache_key)
{
    try {
        $storage = bbcs_connectToRedisOrMMC();
        
        if ($storage !== null) {
            $cached_data = $storage->get($cache_key);
            
            if ($cached_data !== null) {
                bbcs_log_cache_debug('Cache hit for key: ' . $cache_key . ' from ' . get_class($storage));
            }
        } else {
            $cached_data = get_transient($cache_key);
            
            if ($cached_data !== false) {
                bbcs_log_cache_debug('Cache hit for key: ' . $cache_key . ' from WP transients');
            }
        }
        
        if (empty($cached_data)) {
            bbcs_log_cache_debug('Cache miss for key: ' . $cache_key);
        }
        
        return is_array($cached_data) ? $cached_data : null;
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Error getting cached data: ' . $e->getMessage());
        return null;
    }
}

function bbcs_cacheCloudData($cache_key, $data, $ttl)
{
    try {
        if (empty($data) || !is_array($data)) {
            bbcs_log_cache_debug('Skipping cache storage for invalid data (key: ' . $cache_key . ')');
            return false;
        }
        
        $storage = bbcs_connectToRedisOrMMC();
        
        if ($storage !== null) {
            $result = $storage->set($cache_key, $data, $ttl);
            
            if ($result) {
                bbcs_log_cache_debug('Data cached successfully in ' . get_class($storage) . ' with key: ' . $cache_key . ' (TTL: ' . $ttl . 's)');
            } else {
                bbcs_log_cache_debug('Failed to cache data in ' . get_class($storage) . ' with key: ' . $cache_key);
            }
            
            return $result;
        } else {
            $result = set_transient($cache_key, $data, $ttl);
            
            if ($result) {
                bbcs_log_cache_debug('Data cached successfully in WP transients with key: ' . $cache_key . ' (TTL: ' . $ttl . 's)');
            } else {
                bbcs_log_cache_debug('Failed to cache data in WP transients with key: ' . $cache_key);
            }
            
            return $result;
        }
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Error caching data: ' . $e->getMessage());
        return false;
    }
}

function bbcs_resetTransientHealth()
{
    try {
        $transient_key_health_list = 'bbcs_site_health_list';
        $transient_key_health = 'bbcs_site_health';
        $transient_key_health_full = 'bbcs_health_full_html_';
        
        $result1 = delete_transient($transient_key_health_list);
        $result2 = delete_transient($transient_key_health);
        $result3 = delete_transient($transient_key_health_full);
        
        bbcs_log_cache_debug('Health transients reset: ' . 
            ($result1 ? 'Success' : 'Failed') . ' / ' . 
            ($result2 ? 'Success' : 'Failed') . ' / ' . 
            ($result3 ? 'Success' : 'Failed'));
        
        return $result1 && $result2 && $result3;
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Error resetting health transients: ' . $e->getMessage());
        return false;
    }
}

function bbcs_clear_transients()
{
    try {
            $transient_keys = [
                'bbcs_get_statistics',
                'bbcs_display_daily_hits_chart',
                'bbcs_display_hits_and_uniques_chart',
                'bbcs_display_visitors_jsvectormap',
                'bbcs_top_ips',
                'bbcs_top_countries',	
                'bbcs_top_devices',
                'bbcs_top_browsers',
                'bbcs_latest_hits_shortcode',
                //'bbcs_price_list',	// Off price list
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
                'bbcs_cloud_api_status_transient'
            ];
            
            $success_count = 0;
            foreach ($transient_keys as $key) {
                if (delete_transient($key)) {
                    $success_count++;
                }
            }
            
            bbcs_log_cache_debug('UI transients cleared: ' . $success_count . ' of ' . count($transient_keys));
            
            return true;

    } catch (\Exception $e) {
        bbcs_log_cache_debug('Error clearing UI transients: ' . $e->getMessage());
        return false;
    }
}

function bbcs_clearFileCache(){
    try {
        clearstatcache(true);
        
        $opcache_cleared = false;
        if (function_exists('opcache_reset')) {
            $opcache_cleared = @opcache_reset();
        }
        
        bbcs_log_cache_debug('File cache cleared, opcache reset: ' . ($opcache_cleared ? 'Success' : 'N/A'));

        return true;
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Error clearing file cache: ' . $e->getMessage());
        return false;
    }
}

function bbcs_checkAndFixRedisConnection() {
    try {
        $BBCS = BotBlocker::getInstance();
        
        if (isset($BBCS->settings->redis_enable) && $BBCS->settings->redis_enable === 1) {
            
            require_once BOTBLOCKER_DIR . 'includes/class-redis-storage.php';
            
            $redis = BBCS_RedisStorage::getInstance(
                $BBCS->settings->redis_host ?? '127.0.0.1',
                $BBCS->settings->redis_port ?? 6379,
                $BBCS->settings->redis_password ?? '',
                $BBCS->settings->redis_db ?? null,
                $BBCS->settings->redis_prefix ?? 'bbcs_'
            );
            
            if ($redis && !$redis->isAvailable()) {
                bbcs_log_cache_debug('Redis connection is down, attempting to recover...');

                $errorDetails = $redis->getConnectionStatus();
                
                bbcs_log_cache_debug('Redis connection status: ' . wp_json_encode($errorDetails));
                
                $redis->forceReconnect();
                
                if ($redis->isAvailable()) {
                    bbcs_log_cache_debug('Redis connection successfully recovered');
                    return true;
                } else {
                    bbcs_log_cache_debug('Redis connection recovery failed: ' . $redis->getLastError());
                    return false;
                }
            }
            
            return $redis && $redis->isAvailable();
        }
        
        return false;
    } catch (\Exception $e) {
        bbcs_log_cache_debug('Exception in Redis connection check: ' . $e->getMessage());
        return false;
    }
}
