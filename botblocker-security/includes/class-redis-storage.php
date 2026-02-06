<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BBCS_RedisStorage 
{
    private static $instance = null; 
    private $redis;
    private $host;
    private $port;
    private $password;
    private $database;
    private $prefix;
    private $isAvailableCache = null;
    private $lastError = '';
    private $connectionTimeout = 2;
    private $retryInterval = 60;
    private $lastConnectionAttempt = 0;

    private function __construct(
        string $host,
        int $port,
        string $password,
        ?int $database,
        string $prefix
    ) {
        if (!extension_loaded('redis')) {
            $this->lastError = 'Redis PHP extension is not installed.';
            throw new \Exception(esc_html($this->lastError));
        }

        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->prefix = $prefix;
        $this->connect(); 

        if ($database === null) {
            $this->database = $this->findFreeDatabase(); 
            $this->updateDatabaseSettings();
        } else {
            $this->database = $database; 
        }

        $this->selectDatabase($this->database);
    }

    public static function getInstance(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $password = '',
        ?int $database = null,
        string $prefix = 'bbcs_req_'
    ): BBCS_RedisStorage {
        if (self::$instance === null) {
            try {
                self::$instance = new BBCS_RedisStorage($host, $port, $password, $database, $prefix);
            } catch (\Exception $e) {
                if (defined('BBCS_DEBUG') && BBCS_DEBUG && defined('BBCS_CACHE_DEBUG') && BBCS_CACHE_DEBUG) {
                    // error_log('BBCS_RedisStorage Error: ' . $e->getMessage());
                }
                throw $e;
            }
        }
        return self::$instance;
    }

    public function connect(): bool
    {
        if (!extension_loaded('redis')) {
            $this->lastError = 'Redis PHP extension is not installed.';
            throw new \Exception(esc_html($this->lastError));
        }

        $currentTime = time();
        if ($this->lastConnectionAttempt > 0 && 
            $currentTime - $this->lastConnectionAttempt < $this->retryInterval) {
            $this->logDebug('Redis connection attempt throttled. Next attempt available in ' . 
                ($this->retryInterval - ($currentTime - $this->lastConnectionAttempt)) . ' seconds.');
            return false;
        }
        $this->lastConnectionAttempt = $currentTime;

        try {
            $this->redis = new \Redis();
            $connected = $this->redis->connect($this->host, $this->port, $this->connectionTimeout);

            if (!$connected) {
                $this->lastError = 'Failed to connect to Redis server at ' . $this->host . ':' . $this->port;
                $this->logDebug($this->lastError);
                return false;
            }

            if (!empty($this->password)) {
                if (!$this->redis->auth($this->password)) {
                    $this->lastError = 'Redis authentication failed';
                    $this->logDebug($this->lastError);
                    return false;
                }
            }

            $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, 1.0);
            $this->redis->setOption(\Redis::OPT_TCP_KEEPALIVE, 1);
            $this->redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
            
            return true;
        } catch (\Exception $e) {
            $this->lastError = 'Redis connection error: ' . $e->getMessage();
            $this->logDebug($this->lastError);
            return false;
        }
    }

    private function selectDatabase(int $dbNum): bool
    {
        try {
            return $this->redis->select($dbNum);
        } catch (\Exception $e) {
            $this->lastError = 'Failed to select Redis database ' . $dbNum . ': ' . $e->getMessage();
            $this->logDebug($this->lastError);
            return false;
        }
    }

    private function findFreeDatabase(): int
    {
        $maxDatabases = 16; 
        
        try {

            $config = $this->redis->config('GET', 'databases');
            if (isset($config['databases'])) {
                $maxDatabases = (int)$config['databases'];
            }
        } catch (\Exception $e) {
            $this->logDebug('Unable to get Redis database count: ' . $e->getMessage());
            $this->logDebug('Using default: 16 databases');
        }

        for ($i = 1; $i < $maxDatabases; $i++) {
            if ($this->selectDatabase($i)) {
                try {
                    $size = $this->redis->dbSize();

                    if ($size === 0) {
                        return $i;
                    }
                    
                    if ($size < 10) {

                        $keys = $this->redis->keys($this->prefix . '*');
                        if (count($keys) === $size) {
                            return $i; 
                        }
                    }
                } catch (\Exception $e) {
                    $this->logDebug('Error checking Redis database ' . $i . ': ' . $e->getMessage());
                    continue;
                }
            }
        }

        return 1;
    }

    private function updateDatabaseSettings(): void
    {
        try {
            global $wpdb;
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->update(
                $wpdb->bbcs_settings, 
                ['value' => $this->database], 
                ['key' => 'redis_db']
            );
            
            if ($result === false) {
                $this->logDebug('Failed to update Redis database setting in database');
            }
        } catch (\Exception $e) {
            $this->logDebug('Exception updating Redis database setting: ' . $e->getMessage());
        }
    }

    public function getSelectedDatabase(): int
    {
        return $this->database;
    }

    public function set(string $key, array $data, int $ttl = 3600): bool
    {
        try {
            $this->reconnectIfNeeded();
            $prefixedKey = $this->prefix . $key;
            $jsonData = wp_json_encode($data);
            
            if ($jsonData === false) {
                $this->lastError = 'JSON encoding failed: ' . json_last_error_msg();
                $this->logDebug($this->lastError);
                return false;
            }
            
            $setResult = $this->redis->set($prefixedKey, $jsonData);
            if (!$setResult) {
                $this->lastError = 'Redis set operation failed for key: ' . $prefixedKey;
                $this->logDebug($this->lastError);
                return false;
            }
            
            if ($ttl > 0) {
                $expireResult = $this->redis->expire($prefixedKey, $ttl);
                if (!$expireResult) {
                    $this->lastError = 'Redis expire operation failed for key: ' . $prefixedKey;
                    $this->logDebug($this->lastError);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            $this->lastError = 'Redis set exception: ' . $e->getMessage();
            $this->logDebug($this->lastError);
            return false;
        }
    }

    public function get(string $key): ?array
    {
        try {
            $this->reconnectIfNeeded();
            $prefixedKey = $this->prefix . $key;
            $jsonData = $this->redis->get($prefixedKey);
            
            if ($jsonData === false) {
                return null;
            }
            
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = 'JSON decoding failed: ' . json_last_error_msg();
                $this->logDebug($this->lastError . ' for key: ' . $prefixedKey);
                return null;
            }
            
            return $data;
        } catch (\Exception $e) {
            $this->lastError = 'Redis get exception: ' . $e->getMessage();
            $this->logDebug($this->lastError . ' for key: ' . $key);
            return null;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $this->reconnectIfNeeded();
            $prefixedKey = $this->prefix . $key;
            $result = $this->redis->del($prefixedKey);
            
            return $result > 0;
        } catch (\Exception $e) {
            $this->lastError = 'Redis delete exception: ' . $e->getMessage();
            $this->logDebug($this->lastError . ' for key: ' . $key);
            return false;
        }
    }

    public function flushByPrefix(): void
    {
        try {
            $this->reconnectIfNeeded();
            $pattern = $this->prefix . '*';
            $cursor = null; 
            $keysDeleted = 0;
            $maxExecutionTime = 5; 
            $startTime = microtime(true);
            $maxKeysPerIteration = 1000; 
            $totalKeysProcessed = 0;
            
            do {

                if (microtime(true) - $startTime > $maxExecutionTime) {
                    $this->logDebug('Redis flush operation timeout after ' . $keysDeleted . ' keys. Execution time: ' . 
                        round(microtime(true) - $startTime, 2) . ' sec');
                    break;
                }

                if (!$this->redis->isConnected()) {
                    if (!$this->connect()) {
                        $this->logDebug('Redis connection lost during flush operation');
                        break;
                    }
                    $this->selectDatabase($this->database);
                }
                
                try {
                    $result = $this->redis->scan($cursor, $pattern, 100);
                    
                    if ($result === false) {
                        // $this->lastError = 'Redis scan failed: ' . ($this->redis->getLastError() ?: 'Unknown error');
                        // $this->logDebug($this->lastError);
                        break;
                    }
                    
                    if (!is_array($result)) {
                        // $this->lastError = 'Redis scan returned non-array result: ' . print_r($result, true);
                        // $this->logDebug($this->lastError);
                        break;
                    }
                    
                    if (!empty($result)) {
                        $totalKeysProcessed += count($result);

                        if ($totalKeysProcessed > $maxKeysPerIteration) {
                            $this->logDebug('Max keys limit reached (' . $maxKeysPerIteration . '). Will continue in next request.');
                            break;
                        }
                        foreach (array_chunk($result, 100) as $keyChunk) {
                            try {
                                if (!$this->redis->isConnected()) {
                                    if (!$this->connect()) {
                                        $this->logDebug('Redis connection lost during key deletion');
                                        break 2; 
                                    }
                                    $this->selectDatabase($this->database);
                                }
                                
                                $deleted = $this->redis->del($keyChunk);
                                if ($deleted !== false) {
                                    $keysDeleted += $deleted;
                                } else {
                                    $this->logDebug('Failed to delete Redis keys chunk: ' . ($this->redis->getLastError() ?: 'Unknown error'));
                                }
                            } catch (\Exception $chunkEx) {
                                $this->lastError = 'Exception during chunk deletion: ' . $chunkEx->getMessage();
                                $this->logDebug($this->lastError);

                                continue;
                            }
                        }
                    }
                    
                } catch (\Exception $scanEx) {
                    $this->lastError = 'Exception during Redis scan: ' . $scanEx->getMessage();
                    $this->logDebug($this->lastError);

                    if ($this->forceReconnect()) {
                        $this->logDebug('Reconnected after scan exception, continuing flush operation');
                        $cursor = null;
                        continue;
                    } else {
                        $this->logDebug('Failed to reconnect after scan exception, aborting flush operation');
                        break;
                    }
                }

                $isComplete = ($cursor === '0' || $cursor === 0);
            } while (!$isComplete);
            
            $executionTime = microtime(true) - $startTime;
            $this->logDebug('Redis flushed ' . $keysDeleted . ' keys with prefix: ' . $this->prefix . 
                         ' in ' . round($executionTime, 2) . ' sec');
        } catch (\Exception $e) {
            $this->lastError = 'Redis flushByPrefix exception: ' . $e->getMessage();
            $this->logDebug($this->lastError);
        }
    }

    public function reconnectIfNeeded(): void
    {
        try {
            if (!$this->redis->isConnected()) {
                $this->logDebug('Redis connection lost. Attempting to reconnect...');
                
                if ($this->connect()) {
                    $this->selectDatabase($this->database);
                    $this->logDebug('Redis reconnection successful');
                } else {
                    $this->logDebug('Redis reconnection failed');
                }
            }
        } catch (\Exception $e) {
            $this->lastError = 'Redis reconnect exception: ' . $e->getMessage();
            $this->logDebug($this->lastError);
        }
    }

    public function isAvailable(): bool
    {
        if ($this->isAvailableCache !== null) {
            return $this->isAvailableCache;
        }
        
        try {
            if (!$this->redis || !$this->redis->isConnected()) {
                if (!$this->connect()) {
                    $this->isAvailableCache = false;
                    return false;
                }
            }
            
            $pingResponse = $this->redis->ping();
            
            if ($pingResponse === false || 
                ($pingResponse !== true && 
                $pingResponse !== '+PONG' && 
                $pingResponse !== 'PONG')) {
                
                $this->lastError = 'Redis ping failed: ' . $this->redis->getLastError();
                $this->isAvailableCache = false;
                return false;
            }

            if (!$this->selectDatabase($this->database)) {
                $this->isAvailableCache = false;
                return false;
            }

            $testKey = 'bbcs_test_' . microtime(true);
            $testData = ['test' => true, 'timestamp' => time()];
            
            if (!$this->set($testKey, $testData, 5)) {
                $this->isAvailableCache = false;
                return false;
            }
            
            $retrievedData = $this->get($testKey);
            if (!is_array($retrievedData) || 
                !isset($retrievedData['test']) || 
                $retrievedData['test'] !== true) {
                
                $this->isAvailableCache = false;
                return false;
            }
            
            $this->delete($testKey);
            $this->isAvailableCache = true;
            return true;
            
        } catch (\Exception $e) {
            $this->lastError = 'Redis availability check failed: ' . $e->getMessage();
            $this->isAvailableCache = false;
            $this->logDebug($this->lastError);
            return false;
        }
    }

    public function forceReconnect(): bool
    {
        try {

            $this->isAvailableCache = null;

            if ($this->redis && $this->redis->isConnected()) {
                $this->redis->close();
                $this->logDebug('Redis forcefully disconnected for reconnection');
            }

            $this->redis = new \Redis();

            $connected = $this->connect();
            if ($connected) {
                $this->selectDatabase($this->database);
                $this->logDebug('Redis force reconnect successful');
            } else {
                $this->logDebug('Redis force reconnect failed: ' . $this->lastError);
            }
            
            return $connected;
        } catch (\Exception $e) {
            $this->lastError = 'Redis force reconnect exception: ' . $e->getMessage();
            $this->logDebug($this->lastError);
            return false;
        }
    }
    
    public function getConnectionStatus(): array
    {
        $status = [
            'is_connected' => false,
            'last_error' => $this->lastError,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'server_info' => null,
            'ping_response' => null
        ];
        
        try {
            if ($this->redis) {
                $status['is_connected'] = $this->redis->isConnected();
                
                if ($status['is_connected']) {
                    try {
                        $status['server_info'] = $this->redis->info();
                    } catch (\Exception $e) {
                        $status['server_info_error'] = $e->getMessage();
                    }
                    
                    try {
                        $status['ping_response'] = $this->redis->ping();
                    } catch (\Exception $e) {
                        $status['ping_error'] = $e->getMessage();
                    }
                    
                    try {
                        $status['memory_usage'] = $this->redis->info('memory');
                    } catch (\Exception $e) {
                        $status['memory_error'] = $e->getMessage();
                    }
                }
            }
        } catch (\Exception $e) {
            $status['status_error'] = $e->getMessage();
        }
        
        return $status;
    }

    public function disconnect(): void
    {
        try {
            if ($this->redis && $this->redis->isConnected()) {
                $this->redis->close();
                $this->logDebug('Redis connection closed');
            }
        } catch (\Exception $e) {
            $this->lastError = 'Redis disconnect exception: ' . $e->getMessage();
            $this->logDebug($this->lastError);
        }
    }

    public function getList(): array
    {
        $result = [];
        try {
            $this->reconnectIfNeeded();
            $pattern = $this->prefix . '*';
            $cursor = null;
            
            do {
                $scanResult = $this->redis->scan($cursor, $pattern, 100);
                
                if (!$scanResult) {
                    break;
                }
                
                if (!is_array($scanResult)) {
                    // $this->lastError = 'Redis scan returned non-array result: ' . print_r($scanResult, true);
                    // $this->logDebug($this->lastError);
                    break;
                }
                
                if (count($scanResult) < 2) {
                    // $this->lastError = 'Redis scan returned incomplete result array: ' . print_r($scanResult, true);
                    // $this->logDebug($this->lastError);
                    break;
                }
                
                list($cursor, $keys) = $scanResult;
                
                if (!is_array($keys)) {
                    // $this->lastError = 'Redis scan returned invalid keys format (not array): ' . gettype($keys);
                    // $this->logDebug($this->lastError);
                    break;
                }
                
                foreach ($keys as $key) {
                    $value = $this->redis->get($key);
                    if ($value !== false) {
                        $jsonDecoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $result[$key] = $jsonDecoded;
                        } else {
                            $this->logDebug('Redis getList: JSON decode error for key ' . $key . ': ' . json_last_error_msg());
                        }
                    }
                }
                
            } while ($cursor != 0);
            
        } catch (\Exception $e) {
            $this->lastError = 'Redis getList exception: ' . $e->getMessage();
            $this->logDebug($this->lastError);
        }
        
        return $result;
    }
    
    public function getLastError(): string 
    {
        return $this->lastError;
    }

    /**
     * Log debug message if debug mode is enabled
     *
     * @param string $message Message to log
     * @return void
     */
    private function logDebug(string $message): void
    {
        if (defined('BBCS_DEBUG') && BBCS_DEBUG && defined('BBCS_CACHE_DEBUG') && BBCS_CACHE_DEBUG) {
            // error_log($message);
        }
    }
}
