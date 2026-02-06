<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BBCS_MemcachedStorage
{
    private static $instance = null; 
    private $memcached;
    private $host;
    private $port;
    private $prefix;
    private $isAvailableCache = null;
    private $lastError = '';

    private function __construct(
        string $host = '127.0.0.1',
        int $port = 11211,
        string $prefix = 'bbcs_req_'
    ) {
        if (!extension_loaded('memcached')) {
            $this->lastError = 'Memcached PHP extension is not installed.';
            throw new \Exception(esc_html($this->lastError));
        }

        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->memcached = new \Memcached();
        $this->connect(); 
    }

    public static function getInstance(
        string $host = '127.0.0.1', 
        int $port = 11211, 
        string $prefix = 'bbcs_req_'
        ): BBCS_MemcachedStorage
    {
        if (self::$instance === null) {
            try {
                self::$instance = new BBCS_MemcachedStorage($host, $port, $prefix);
            } catch (\Exception $e) {
                if (self::$instance) {
                    self::$instance->logDebug('BBCS_MemcachedStorage Error: ' . $e->getMessage());
                } else {
                    if (defined('BBCS_DEBUG') && BBCS_DEBUG && defined('BBCS_CACHE_DEBUG') && BBCS_CACHE_DEBUG) {
                       // error_log('BBCS_MemcachedStorage Error: ' . $e->getMessage());
                    }
                }
                throw $e;
            }
        }
        return self::$instance;
    }

    public function connect(): bool
    {
        if (!extension_loaded('memcached')) {
            $this->lastError = 'Memcached PHP extension is not installed.';
            throw new \Exception(esc_html($this->lastError));
        }

        $servers = $this->memcached->getServerList();
        if (empty($servers)) {
            if (!$this->memcached->addServer($this->host, $this->port)) {
                $this->lastError = 'Failed to connect to Memcached server: ' . $this->host . ':' . $this->port;
                $this->logDebug($this->lastError);
                $this->logDebug('Memcached error code: ' . $this->memcached->getResultCode());
                $this->logDebug('Memcached error message: ' . $this->memcached->getResultMessage());
                throw new \Exception(esc_html($this->lastError));
            }
        }
        
        $this->memcached->setOption(\Memcached::OPT_COMPRESSION, true);
        $this->memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000);
        $this->memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT, 30);
        
        return true;
    }

    public function isAvailable(): bool
    {
        if ($this->isAvailableCache !== null) {
            return $this->isAvailableCache;
        }
        
        try {
            $stats = $this->memcached->getStats();
            
            if (empty($stats) || !is_array($stats)) {
                $this->isAvailableCache = false;
                return false;
            }
            
            foreach ($stats as $server => $status) {
                if ($status === false || !isset($status['pid']) || $status['pid'] < 1) {
                    $this->isAvailableCache = false;
                    return false;
                }
            }
            
            $testKey = 'bbcs_test_' . microtime(true);
            $testValue = ['test' => true];
            
            if (!$this->memcached->set($testKey, $testValue, 5)) {
                $this->isAvailableCache = false;
                $this->logDebug('Memcached set test failed. Error: ' . $this->memcached->getResultMessage());
                return false;
            }
            
            $retrievedValue = $this->memcached->get($testKey);
            if (!is_array($retrievedValue) || !isset($retrievedValue['test']) || $retrievedValue['test'] !== true) {
                $this->isAvailableCache = false;
                $this->logDebug('Memcached get test failed. Retrieved value does not match set value.');
                return false;
            }
            
            $this->memcached->delete($testKey);
            $this->isAvailableCache = true;
            return true;
            
        } catch (\Exception $e) {
            $this->isAvailableCache = false;
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached availability check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function set(string $key, array $data, int $ttl = 3600): bool
    {
        try {
            $prefixedKey = $this->prefix . $key;
            $result = $this->memcached->set($prefixedKey, $data, $ttl);
            
            if (!$result) {
                $this->logDebug('Memcached set failed for key: ' . $prefixedKey);
                $this->logDebug('Memcached error: ' . $this->memcached->getResultMessage());
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached set exception for key: ' . $key . '. Error: ' . $e->getMessage());
            return false;
        }
    }

    public function get(string $key): ?array
    {
        try {
            $prefixedKey = $this->prefix . $key;
            $data = $this->memcached->get($prefixedKey);
            
            if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
                return null;
            }
            
            if ($this->memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
                $this->logDebug('Memcached get error for key: ' . $prefixedKey);
                $this->logDebug('Memcached error: ' . $this->memcached->getResultMessage());
                return null;
            }
            
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached get exception for key: ' . $key . '. Error: ' . $e->getMessage());
            return null;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $prefixedKey = $this->prefix . $key;
            $result = $this->memcached->delete($prefixedKey);
            
            if (!$result && $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND) {
                $this->logDebug('Memcached delete failed for key: ' . $prefixedKey);
                $this->logDebug('Memcached error: ' . $this->memcached->getResultMessage());
            }
            
            return $result || $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached delete exception for key: ' . $key . '. Error: ' . $e->getMessage());
            return false;
        }
    }

    public function flushByPrefix(): void
    {
        try {
            $keys = $this->getKeysByPrefix();
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $this->memcached->delete($key);
                }
                
                $this->logDebug('Memcached flushed ' . count($keys) . ' keys by prefix: ' . $this->prefix);
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached flushByPrefix exception: ' . $e->getMessage());
        }
    }

    private function getKeysByPrefix(): array
    {
        $keys = [];
        try {
            $allKeys = $this->memcached->getAllKeys();
            if ($allKeys) {
                $prefixLength = strlen($this->prefix);
                foreach ($allKeys as $key) {
                    if (substr($key, 0, $prefixLength) === $this->prefix) {
                        $keys[] = $key;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached getKeysByPrefix exception: ' . $e->getMessage());
        }
        return $keys;
    }

    public function disconnect(): void
    {
        try {
            if ($this->memcached) {
                $this->memcached->quit();
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached disconnect exception: ' . $e->getMessage());
        }
    }

    public function getList(): array
    {
        $result = [];
        try {
            $keys = $this->getKeysByPrefix();
            foreach ($keys as $key) {
                $value = $this->memcached->get($key);
                if ($value !== false) {
                    $result[$key] = $value;
                }
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logDebug('Memcached getList exception: ' . $e->getMessage());
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
