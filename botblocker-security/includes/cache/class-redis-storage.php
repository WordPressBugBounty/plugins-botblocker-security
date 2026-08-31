<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BBCS_RedisStorage extends BBCS_ObjectCacheStorage {

	/** @var BBCS_RedisStorage|null */
	private static $instance = null;
	/** @var \Redis|null */
	private $redis;
	/** @var string */
	private $password;
	/** @var int */
	private $database = 0;
	/** @var int */
	private $connectionTimeout = 1;
	/** @var int */
	private $retryInterval = 60;
	/** @var int */
	private $lastConnectionAttempt = 0;

	private function __construct(
		string $host,
		int $port,
		string $password,
		string $prefix,
		int $database = 0
	) {
		if ( ! extension_loaded( 'redis' ) ) {
			$this->lastError = 'Redis PHP extension is not installed.';
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
			throw new \Exception( $this->lastError );
		}

		$this->host       = $host;
		$this->port       = $port;
		$this->password   = $password;
		$this->prefix     = $prefix;
		$this->database   = $database;
		$this->sitePrefix = self::buildSitePrefix();
		$this->connect();
	}

	public static function getInstance(
		string $host = '127.0.0.1',
		int $port = 6379,
		string $password = '',
		string $prefix = 'bbcs_req_',
		int $database = 0
	): BBCS_RedisStorage {
		if ( self::$instance === null ) {
			try {
				self::$instance = new BBCS_RedisStorage( $host, $port, $password, $prefix, $database );
			} catch ( \Exception $e ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && defined( 'BBCS_CACHE_DEBUG' ) && BBCS_CACHE_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Cache] BBCS_RedisStorage Error: ' . $e->getMessage() );
				}
				throw $e;
			}
		}
		return self::$instance;
	}

	public static function resetInstance(): void {
		self::$instance = null;
	}

	public function connect(): bool {
		if ( ! extension_loaded( 'redis' ) ) {
			$this->lastError = 'Redis PHP extension is not installed.';
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
			throw new \Exception( $this->lastError );
		}

		$currentTime = time();
		if ( $this->lastConnectionAttempt > 0 &&
			$currentTime - $this->lastConnectionAttempt < $this->retryInterval ) {
			$this->logDebug(
				'Redis connection attempt throttled. Next attempt available in ' .
				( $this->retryInterval - ( $currentTime - $this->lastConnectionAttempt ) ) . ' seconds.'
			);
			return false;
		}
		$this->lastConnectionAttempt = $currentTime;

		try {
			if ( $this->redis === null ) {
				$this->redis = new \Redis();
			}
			$connected   = $this->redis->connect( $this->host, $this->port, $this->connectionTimeout );

			if ( ! $connected ) {
				$this->lastError = 'Failed to connect to Redis server at ' . $this->host . ':' . $this->port;
				$this->logDebug( $this->lastError );
				return false;
			}

			if ( ! empty( $this->password ) ) {
				if ( ! $this->redis->auth( $this->password ) ) {
					$this->lastError = 'Redis authentication failed';
					$this->logDebug( $this->lastError );
					return false;
				}
			}

			if ( $this->database > 0 ) {
				if ( ! $this->redis->select( $this->database ) ) {
					$this->lastError = 'Redis database selection failed for index: ' . $this->database;
					$this->logDebug( $this->lastError );
					return false;
				}
			}

			$this->redis->setOption( \Redis::OPT_READ_TIMEOUT, 1.0 );
			$this->redis->setOption( \Redis::OPT_TCP_KEEPALIVE, 1 );
			$this->redis->setOption( \Redis::OPT_SCAN, \Redis::SCAN_RETRY );

			return true;
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis connection error: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
			return false;
		}
	}

	public function set( string $key, array $data, int $ttl = 3600 ): bool {
		try {
			$this->reconnectIfNeeded();

			if ( $ttl <= 0 ) {
				$this->lastError = 'Redis TTL must be greater than zero for key: ' . $key;
				$this->logDebug( $this->lastError );
				return false;
			}

			$prefixedKey = $this->getPayloadKey( $key );
			$jsonData    = wp_json_encode( $data );

			if ( $jsonData === false ) {
				$this->lastError = 'JSON encoding failed: ' . json_last_error_msg();
				$this->logDebug( $this->lastError );
				return false;
			}

			$setResult = $this->redis->setex( $prefixedKey, $ttl, $jsonData );

			if ( ! $setResult ) {
				$this->lastError = 'Redis set operation failed for key: ' . $prefixedKey;
				$this->logDebug( $this->lastError );
				return false;
			}

			return true;
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis set exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
			return false;
		}
	}

	public function get( string $key ): ?array {
		try {
			$this->reconnectIfNeeded();
			$prefixedKey = $this->getPayloadKey( $key );
			$jsonData    = $this->redis->get( $prefixedKey );

			if ( $jsonData === false ) {
				return null;
			}

			$data = json_decode( $jsonData, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$this->lastError = 'JSON decoding failed: ' . json_last_error_msg();
				$this->logDebug( $this->lastError . ' for key: ' . $prefixedKey );
				return null;
			}

			return is_array( $data ) ? $data : null;
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis get exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError . ' for key: ' . $key );
			return null;
		}
	}

	public function delete( string $key ): bool {
		try {
			$this->reconnectIfNeeded();
			$prefixedKey = $this->getPayloadKey( $key );
			$result      = $this->redis->del( $prefixedKey );

			return $result > 0;
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis delete exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError . ' for key: ' . $key );
			return false;
		}
	}

	public function reconnectIfNeeded(): void {
		try {
			if ( ! $this->redis->isConnected() ) {
				$this->logDebug( 'Redis connection lost. Attempting to reconnect...' );

				if ( $this->connect() ) {
					$this->logDebug( 'Redis reconnection successful' );
				} else {
					$this->logDebug( 'Redis reconnection failed' );
				}
			}
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis reconnect exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
		}
	}

	public function isAvailable(): bool {
		if ( $this->isAvailableCache !== null ) {
			return $this->isAvailableCache;
		}

		try {
			if ( ! $this->redis || ! $this->redis->isConnected() ) {
				if ( ! $this->connect() ) {
					$this->isAvailableCache = false;
					return false;
				}
			}

			$pingResponse = $this->redis->ping();

			if ( $pingResponse === false ||
				( $pingResponse !== true &&
				$pingResponse !== '+PONG' &&
				$pingResponse !== 'PONG' ) ) {

				$this->lastError        = 'Redis ping failed: ' . $this->redis->getLastError();
				$this->isAvailableCache = false;
				return false;
			}

			$testKey  = 'bbcs_test_' . microtime( true );
			$testData = array(
				'test'      => true,
				'timestamp' => time(),
			);

			if ( ! $this->set( $testKey, $testData, 5 ) ) {
				$this->isAvailableCache = false;
				return false;
			}

			$retrievedData = $this->get( $testKey );
			if ( ! is_array( $retrievedData ) ||
				! isset( $retrievedData['test'] ) ||
				$retrievedData['test'] !== true ) {

				$this->isAvailableCache = false;
				return false;
			}

			$this->delete( $testKey );
			$this->isAvailableCache = true;
			return true;

		} catch ( \Exception $e ) {
			$this->lastError        = 'Redis availability check failed: ' . $e->getMessage();
			$this->isAvailableCache = false;
			$this->logDebug( $this->lastError );
			return false;
		}
	}

	public function forceReconnect(): bool {
		try {

			$this->isAvailableCache = null;
			// Bypass the retry throttle: a health-check recovery must be able
			// to reconnect even right after a failed attempt.
			$this->lastConnectionAttempt = 0;

			if ( $this->redis && $this->redis->isConnected() ) {
				$this->redis->close();
				$this->logDebug( 'Redis forcefully disconnected for reconnection' );
			}

			$this->redis = new \Redis();

			$connected = $this->connect();
			if ( $connected ) {
				$this->logDebug( 'Redis force reconnect successful' );
			} else {
				$this->logDebug( 'Redis force reconnect failed: ' . $this->lastError );
			}

			return $connected;
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis force reconnect exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
			return false;
		}
	}

	public function getConnectionStatus(): array {
		$status = array(
			'is_connected'  => false,
			'last_error'    => $this->lastError,
			'host'          => $this->host,
			'port'          => $this->port,
			'server_info'   => null,
			'ping_response' => null,
		);

		try {
			if ( $this->redis ) {
				$status['is_connected'] = $this->redis->isConnected();

				if ( $status['is_connected'] ) {
					try {
						$status['server_info'] = $this->redis->info();
					} catch ( \Exception $e ) {
						$status['server_info_error'] = $e->getMessage();
					}

					try {
						$status['ping_response'] = $this->redis->ping();
					} catch ( \Exception $e ) {
						$status['ping_error'] = $e->getMessage();
					}

					try {
						$status['memory_usage'] = $this->redis->info( 'memory' );
					} catch ( \Exception $e ) {
						$status['memory_error'] = $e->getMessage();
					}
				}
			}
		} catch ( \Exception $e ) {
			$status['status_error'] = $e->getMessage();
		}

		return $status;
	}

	public function getSelectedDatabase(): int {
		return $this->database;
	}

	public function disconnect(): void {
		try {
			if ( $this->redis && $this->redis->isConnected() ) {
				$this->redis->close();
				$this->logDebug( 'Redis connection closed' );
			}
		} catch ( \Exception $e ) {
			$this->lastError = 'Redis disconnect exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
		}
	}

	public function rotateCacheGeneration(): void {
		try {
			$this->reconnectIfNeeded();
			$this->clearCacheGeneration();
			$this->rotateGeneration();
			$this->logDebug( $this->getDriverName() . ' rotated cache generation for prefix: ' . $this->getGenerationPrefix() );
		} catch ( \Exception $e ) {
			$this->lastError = $this->getDriverName() . ' cache generation rotation exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
		}
	}

	protected function rawGet( string $key ) {
		return $this->redis->get( $key );
	}

	protected function rawSet( string $key, $value, int $ttl = 0 ): bool {
		if ( $ttl <= 0 ) {
			$this->lastError = 'Redis raw set TTL must be greater than zero for key: ' . $key;
			$this->logDebug( $this->lastError );
			return false;
		}

		return $this->redis->setex( $key, $ttl, $value );
	}

	protected function rawSetGeneration( string $key, string $value ): bool {
		return $this->redis->set( $key, $value );
	}

	protected function rawDelete( string $key ): int {
		return $this->redis->del( $key );
	}

	protected function getDriverName(): string {
		return 'Redis';
	}
}
