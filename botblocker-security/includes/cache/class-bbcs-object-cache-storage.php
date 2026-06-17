<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class BBCS_ObjectCacheStorage {

	protected const GENERATION_KEY_SUFFIX = 'g';
	protected const MAX_KEY_LENGTH        = 250;
	protected const MAX_PREFIX_LENGTH     = 64;

	/** @var string */
	protected $host;
	/** @var int */
	protected $port;
	/** @var string */
	protected $prefix;
	/** @var string */
	protected $sitePrefix = '';
	/** @var bool|null */
	protected $isAvailableCache = null;
	/** @var string */
	protected $lastError = '';

	abstract protected function rawGet( string $key );
	abstract protected function rawSet( string $key, $value, int $ttl = 0 ): bool;
	abstract protected function rawSetGeneration( string $key, string $value ): bool;
	abstract protected function rawDelete( string $key ): int;
	abstract protected function getDriverName(): string;

	protected function getPayloadKey( string $key ): string {
		$payloadPrefix = $this->getPayloadPrefix( $this->getGeneration() );

		return $payloadPrefix . self::normalizePayloadKey( $key, self::MAX_KEY_LENGTH - strlen( $payloadPrefix ) );
	}

	protected function getGeneration(): string {
		$generation = $this->rawGet( $this->getGenerationKey() );
		if ( is_string( $generation ) && $generation !== '' ) {
			return $generation;
		}

		return $this->rotateGeneration();
	}

	protected function rotateGeneration(): string {
		$generation = md5( (string) wp_rand() . microtime( true ) );
		if ( ! $this->rawSetGeneration( $this->getGenerationKey(), $generation ) ) {
			$this->lastError = $this->getDriverName() . ' generation update failed for prefix: ' . $this->getGenerationPrefix();
			$this->logDebug( $this->lastError );
		}

		return $generation;
	}

	public function rotateCacheGeneration(): void {
		try {
			$this->clearCacheGeneration();
			$this->rotateGeneration();
			$this->logDebug( $this->getDriverName() . ' rotated cache generation for prefix: ' . $this->getGenerationPrefix() );
		} catch ( \Exception $e ) {
			$this->lastError = $this->getDriverName() . ' cache generation rotation exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
		}
	}

	public function clearCacheGeneration(): bool {
		try {
			$this->rawDelete( $this->getGenerationKey() );
			$this->logDebug( $this->getDriverName() . ' cleared cache generation for prefix: ' . $this->getGenerationPrefix() );
			return true;
		} catch ( \Exception $e ) {
			$this->lastError = $this->getDriverName() . ' cache generation clear exception: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
			return false;
		}
	}

	public function getLastError(): string {
		return $this->lastError;
	}

	protected function getBasePrefix(): string {
		$basePrefix = '';
		if ( trim( $this->prefix ) !== '' ) {
			$basePrefix = $this->prefix;
		} else {
			$basePrefix = defined( 'BOTBLOCKER_PREFIX' ) ? BOTBLOCKER_PREFIX : 'bbcs_';
		}

		return self::normalizePrefix( $basePrefix );
	}

	protected function getGenerationPrefix(): string {
		return self::normalizePrefix( $this->getBasePrefix() . $this->sitePrefix );
	}

	protected function getGenerationKey(): string {
		return $this->getGenerationPrefix() . self::GENERATION_KEY_SUFFIX;
	}

	protected function getPayloadPrefix( string $generation ): string {
		return $this->getBasePrefix() . 'c_' . md5( $this->sitePrefix . '|' . $generation ) . '_';
	}

	protected static function buildSitePrefix(): string {
		global $wpdb, $table_prefix;

		$basePrefix = '';
		if ( is_object( $wpdb ) && isset( $wpdb->base_prefix ) ) {
			$basePrefix = (string) $wpdb->base_prefix;
		} elseif ( isset( $table_prefix ) ) {
			$basePrefix = (string) $table_prefix;
		}

		$blogId   = function_exists( 'get_current_blog_id' ) ? (string) get_current_blog_id() : '0';
		$dbName   = defined( 'DB_NAME' ) ? DB_NAME : '';
		$dbHost   = defined( 'DB_HOST' ) ? DB_HOST : '';
		$identity = $dbHost . '|' . $dbName . '|' . $basePrefix . '|' . $blogId;

		return 's_' . md5( $identity ) . '_';
	}

	protected static function normalizePrefix( string $prefix ): string {
		$cleanPrefix = self::cleanKeySegment( $prefix );
		if ( strlen( $cleanPrefix ) <= self::MAX_PREFIX_LENGTH ) {
			return $cleanPrefix;
		}

		return substr( $cleanPrefix, 0, 28 ) . 'h_' . md5( $prefix ) . '_';
	}

	protected static function normalizePayloadKey( string $key, int $maxLength ): string {
		$cleanKey = self::cleanKeySegment( $key );
		if ( $cleanKey === $key && strlen( $cleanKey ) <= $maxLength ) {
			return $cleanKey;
		}

		$hashKey = 'k_' . md5( $key );
		$suffixLength = $maxLength - strlen( $hashKey ) - 1;
		if ( $suffixLength <= 0 ) {
			return substr( $hashKey, 0, $maxLength );
		}

		return $hashKey . '_' . substr( $cleanKey, 0, $suffixLength );
	}

	protected static function cleanKeySegment( string $value ): string {
		$cleanValue = preg_replace( '/[^A-Za-z0-9_.:-]/', '_', $value );

		return is_string( $cleanValue ) ? $cleanValue : '';
	}

	/**
	 * Log debug message if debug mode is enabled
	 *
	 * @param string $message Message to log
	 * @return void
	 */
	protected function logDebug( string $message ): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && defined( 'BBCS_CACHE_DEBUG' ) && BBCS_CACHE_DEBUG ) {
			// REVIEWER NOTE: Conditional debug logging; gated behind BBCS_DEBUG + BBCS_CACHE_DEBUG and disabled in production.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Cache] ' . $message );
		}
	}
}
