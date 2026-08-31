<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BBCS_MemcachedStorage extends BBCS_ObjectCacheStorage {

	/** @var BBCS_MemcachedStorage|null */
	private static $instance = null;
	/** @var \Memcached|null */
	private $memcached;
	/** @var bool */
	private $selfJson = false;

	private function __construct(
		string $host = '127.0.0.1',
		int $port = 11211,
		string $prefix = 'bbcs_req_'
	) {
		if ( ! extension_loaded( 'memcached' ) ) {
			$this->lastError = 'Memcached PHP extension is not installed.';
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
			throw new \Exception( $this->lastError );
		}

		$this->host       = $host;
		$this->port       = $port;
		$this->prefix     = $prefix;
		$this->sitePrefix = self::buildSitePrefix();
		$this->memcached  = new \Memcached();
		$this->connect();
	}

	public static function getInstance(
		string $host = '127.0.0.1',
		int $port = 11211,
		string $prefix = 'bbcs_req_'
	): BBCS_MemcachedStorage {
		if ( self::$instance === null ) {
			try {
				self::$instance = new BBCS_MemcachedStorage( $host, $port, $prefix );
			} catch ( \Exception $e ) {
				if ( self::$instance ) {
					self::$instance->logDebug( 'BBCS_MemcachedStorage Error: ' . $e->getMessage() );
				} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && defined( 'BBCS_CACHE_DEBUG' ) && BBCS_CACHE_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[BBCS DEBUG] [Cache] BBCS_MemcachedStorage Error: ' . $e->getMessage() );

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
		if ( ! extension_loaded( 'memcached' ) ) {
			$this->lastError = 'Memcached PHP extension is not installed.';
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
			throw new \Exception( $this->lastError );
		}

		$servers = $this->memcached->getServerList();
		if ( empty( $servers ) ) {
			if ( ! $this->memcached->addServer( $this->host, $this->port ) ) {
				$this->lastError = 'Failed to connect to Memcached server: ' . $this->host . ':' . $this->port;
				$this->logDebug( $this->lastError );
				$this->logDebug( 'Memcached error code: ' . $this->memcached->getResultCode() );
				$this->logDebug( 'Memcached error message: ' . $this->memcached->getResultMessage() );
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
				throw new \Exception( $this->lastError );
			}
		}

		$this->memcached->setOption( \Memcached::OPT_COMPRESSION, true );
		$this->memcached->setOption( \Memcached::OPT_CONNECT_TIMEOUT, 1000 );
		$this->memcached->setOption( \Memcached::OPT_RETRY_TIMEOUT, 30 );
		// Non-PHP serializer: PHP-serialized object payloads (co-tenant on a
		// shared memcached) must never materialize into live objects. Ext
		// builds without JSON serializer support fall back to self-encoded
		// JSON strings (PHP 7.4 Windows builds, HG-2).
		$json_serializer = defined( 'Memcached::SERIALIZER_JSON' ) ? \Memcached::SERIALIZER_JSON : null;
		if ( $json_serializer !== null ) {
			try {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- unsupported serializer raises a warning on some ext builds; the return value is checked instead
				$applied = @$this->memcached->setOption( \Memcached::OPT_SERIALIZER, $json_serializer );
				if ( ! $applied || $this->memcached->getOption( \Memcached::OPT_SERIALIZER ) !== $json_serializer ) {
					$json_serializer = null;
				}
			} catch ( \Exception $e ) {
				$json_serializer = null;
			}
		}
		$this->selfJson = $json_serializer === null;
		if ( defined( 'Memcached::JSON_OBJECT_AS_ARRAY' ) && ! $this->selfJson ) {
			try {
				$this->memcached->setOption( \Memcached::OPT_JSON, \Memcached::JSON_OBJECT_AS_ARRAY );
			} catch ( \Exception $e ) {
				unset( $e );
			}
		}

		return true;
	}

	public function getClientOptions(): array {
		if ( ! $this->memcached instanceof \Memcached ) {
			return array();
		}
		return array(
			'compression'     => (int) $this->memcached->getOption( \Memcached::OPT_COMPRESSION ),
			'connect_timeout' => (int) $this->memcached->getOption( \Memcached::OPT_CONNECT_TIMEOUT ),
			'retry_timeout'   => (int) $this->memcached->getOption( \Memcached::OPT_RETRY_TIMEOUT ),
		);
	}

	public function isAvailable(): bool {
		if ( $this->isAvailableCache !== null ) {
			return $this->isAvailableCache;
		}
		try {
			$stats = $this->memcached->getStats();

			if ( empty( $stats ) || ! is_array( $stats ) ) {
				$this->isAvailableCache = false;
				return false;
			}

			foreach ( $stats as $server => $status ) {
				if ( $status === false || ! isset( $status['pid'] ) || $status['pid'] < 1 ) {
					$this->isAvailableCache = false;
					return false;
				}
			}

			$testKey   = 'bbcs_test_' . microtime( true );
			$testValue = array( 'test' => true );

			if ( ! $this->set( $testKey, $testValue, 5 ) ) {
				$this->isAvailableCache = false;
				$this->logDebug( 'Memcached set test failed. Error: ' . $this->memcached->getResultMessage() );
				return false;
			}

			$retrievedValue = $this->get( $testKey );
			if ( ! is_array( $retrievedValue ) || ! isset( $retrievedValue['test'] ) || $retrievedValue['test'] !== true ) {
				$this->isAvailableCache = false;
				$this->logDebug( 'Memcached get test failed. Retrieved value does not match set value.' );
				return false;
			}

			$this->delete( $testKey );
			$this->isAvailableCache = true;
			return true;

		} catch ( \Exception $e ) {
			$this->isAvailableCache = false;
			$this->lastError        = $e->getMessage();
			$this->logDebug( 'Memcached availability check failed: ' . $e->getMessage() );
			return false;
		}
	}

	public function set( string $key, array $data, int $ttl = 3600 ): bool {
		try {
			if ( $ttl <= 0 ) {
				$this->lastError = 'Memcached TTL must be greater than zero for key: ' . $key;
				$this->logDebug( $this->lastError );
				return false;
			}

			$prefixedKey = $this->getPayloadKey( $key );
			$payload     = $this->selfJson ? (string) wp_json_encode( $data ) : $data;
			$result      = $this->memcached->set( $prefixedKey, $payload, $ttl );

			if ( ! $result ) {
				$this->logDebug( 'Memcached set failed for key: ' . $prefixedKey );
				$this->logDebug( 'Memcached error: ' . $this->memcached->getResultMessage() );
			}

			return $result;
		} catch ( \Exception $e ) {
			$this->lastError = $e->getMessage();
			$this->logDebug( 'Memcached set exception for key: ' . $key . '. Error: ' . $e->getMessage() );
			return false;
		}
	}

	public function get( string $key ): ?array {
		try {
			$prefixedKey = $this->getPayloadKey( $key );
			$data        = $this->memcached->get( $prefixedKey );

			if ( $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND ) {
				return null;
			}

			if ( $this->memcached->getResultCode() !== \Memcached::RES_SUCCESS ) {
				$this->logDebug( 'Memcached get error for key: ' . $prefixedKey );
				$this->logDebug( 'Memcached error: ' . $this->memcached->getResultMessage() );
				return null;
			}

			if ( $this->selfJson && is_string( $data ) ) {
				$data = json_decode( $data, true );
			}

			// Older ext versions decode JSON to stdClass despite OPT_JSON.
			return is_array( $data ) ? $data : ( is_object( $data ) ? (array) $data : null );
		} catch ( \Exception $e ) {
			$this->lastError = $e->getMessage();
			$this->logDebug( 'Memcached get exception for key: ' . $key . '. Error: ' . $e->getMessage() );
			return null;
		}
	}

	public function delete( string $key ): bool {
		try {
			$prefixedKey = $this->getPayloadKey( $key );
			$result      = $this->memcached->delete( $prefixedKey );

			if ( ! $result && $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND ) {
				$this->logDebug( 'Memcached delete failed for key: ' . $prefixedKey );
				$this->logDebug( 'Memcached error: ' . $this->memcached->getResultMessage() );
			}

			return $result || $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND;
		} catch ( \Exception $e ) {
			$this->lastError = $e->getMessage();
			$this->logDebug( 'Memcached delete exception for key: ' . $key . '. Error: ' . $e->getMessage() );
			return false;
		}
	}

	public function disconnect(): void {
		try {
			if ( $this->memcached ) {
				$this->memcached->quit();
			}
		} catch ( \Exception $e ) {
			$this->lastError = $e->getMessage();
			$this->logDebug( 'Memcached disconnect exception: ' . $e->getMessage() );
		}
	}

	protected function rawGet( string $key ) {
		return $this->memcached->get( $key );
	}

	protected function rawSet( string $key, $value, int $ttl = 0 ): bool {
		if ( $ttl <= 0 ) {
			$this->lastError = 'Memcached raw set TTL must be greater than zero for key: ' . $key;
			$this->logDebug( $this->lastError );
			return false;
		}

		return $this->memcached->set( $key, $value, $ttl );
	}

	protected function rawSetGeneration( string $key, string $value ): bool {
		return $this->memcached->set( $key, $value, 0 );
	}

	protected function rawDelete( string $key ): int {
		return $this->memcached->delete( $key ) ? 1 : 0;
	}

	protected function getDriverName(): string {
		return 'Memcached';
	}
}
