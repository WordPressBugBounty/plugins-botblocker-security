<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBCS_ApcuStorage extends BBCS_ObjectCacheStorage {

	/** @var BBCS_ApcuStorage|null */
	private static $instance = null;
	/** @var bool */
	private $connected = false;

	private function __construct( string $prefix ) {
		if ( ! extension_loaded( 'apcu' ) ) {
			$this->lastError = 'APCu PHP extension is not installed.';
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
			throw new \Exception( $this->lastError );
		}

		$this->prefix     = $prefix;
		$this->sitePrefix = self::buildSitePrefix();
		$this->connect();
	}

	public static function getInstance( string $prefix = '' ): BBCS_ApcuStorage {
		if ( self::$instance === null ) {
			if ( empty( $prefix ) ) {
				$prefix = defined( 'BOTBLOCKER_PREFIX' ) ? BOTBLOCKER_PREFIX : 'bbcs_';
			}
			try {
				self::$instance = new BBCS_ApcuStorage( $prefix );
			} catch ( \Exception $e ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && defined( 'BBCS_CACHE_DEBUG' ) && BBCS_CACHE_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Cache] BBCS_ApcuStorage Error: ' . $e->getMessage() );
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
		if ( ! extension_loaded( 'apcu' ) ) {
			$this->lastError = 'APCu PHP extension is not installed.';
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $this->lastError is an internal error string, not user-controlled output.
			throw new \Exception( $this->lastError );
		}

		if ( ! apcu_enabled() ) {
			$this->lastError = 'APCu disabled';
			$this->connected = false;
			return false;
		}

		$this->connected = true;
		return true;
	}

	public function isAvailable(): bool {
		if ( $this->isAvailableCache !== null ) {
			return $this->isAvailableCache;
		}
		try {
			if ( ! $this->connected && ! $this->connect() ) {
				$this->isAvailableCache = false;
				return false;
			}

			$testKey   = 'bbcs_test_' . microtime( true );
			$testValue = array( 'test' => true );

			if ( ! $this->set( $testKey, $testValue, 5 ) ) {
				$this->isAvailableCache = false;
				$this->logDebug( 'APCu set test failed: ' . $this->lastError );
				return false;
			}

			$retrieved = $this->get( $testKey );
			if ( ! is_array( $retrieved ) || ! isset( $retrieved['test'] ) || $retrieved['test'] !== true ) {
				$this->isAvailableCache = false;
				$this->logDebug( 'APCu get test failed. Retrieved value does not match set value.' );
				return false;
			}

			$this->delete( $testKey );
			$this->isAvailableCache = true;
			return true;

		} catch ( \Exception $e ) {
			$this->isAvailableCache = false;
			$this->lastError        = $e->getMessage();
			$this->logDebug( 'APCu availability check failed: ' . $e->getMessage() );
			return false;
		}
	}

	public function set( string $key, array $data, int $ttl = 3600 ): bool {
		try {
			if ( $ttl <= 0 ) {
				$this->lastError = 'APCu TTL must be greater than zero for key: ' . $key;
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

			$result = apcu_store( $prefixedKey, $jsonData, $ttl );

			if ( $result === false ) {
				$this->lastError = 'APCu set operation failed for key: ' . $prefixedKey;
				$this->logDebug( $this->lastError );
				return false;
			}

			return true;
		} catch ( \Exception $e ) {
			$this->lastError = 'APCu set exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
			return false;
		}
	}

	public function get( string $key ): ?array {
		try {
			$prefixedKey = $this->getPayloadKey( $key );
			$success     = false;
			$jsonData    = apcu_fetch( $prefixedKey, $success );

			if ( ! $success ) {
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
			$this->lastError = 'APCu get exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError . ' for key: ' . $key );
			return null;
		}
	}

	public function delete( string $key ): bool {
		try {
			$prefixedKey = $this->getPayloadKey( $key );
			return (bool) apcu_delete( $prefixedKey );
		} catch ( \Exception $e ) {
			$this->lastError = 'APCu delete exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError . ' for key: ' . $key );
			return false;
		}
	}

	public function flush(): bool {
		if ( ! $this->connected && ! $this->connect() ) {
			return false;
		}
		$this->rotateCacheGeneration();
		return true;
	}

	protected function rawGet( string $key ) {
		$success = false;
		$value   = apcu_fetch( $key, $success );
		return $success ? $value : null;
	}

	protected function rawSet( string $key, $value, int $ttl = 0 ): bool {
		if ( $ttl <= 0 ) {
			$this->lastError = 'APCu raw set TTL must be greater than zero for key: ' . $key;
			$this->logDebug( $this->lastError );
			return false;
		}

		return apcu_store( $key, $value, $ttl ) !== false;
	}

	protected function rawSetGeneration( string $key, string $value ): bool {
		return apcu_store( $key, $value ) !== false;
	}

	protected function rawDelete( string $key ): int {
		return apcu_delete( $key ) ? 1 : 0;
	}

	protected function getDriverName(): string {
		return 'APCu';
	}
}
