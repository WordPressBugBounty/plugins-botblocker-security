<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerGateway {

	private static array $registry = array();

	private static array $active = array();

	public static function reset(): void {
		self::$registry = array();
		self::$active   = array();
	}

	public static function register( string $slug, string $type, array $config ): void {
		if ( ! isset( self::$registry[ $type ] ) ) {
			self::$registry[ $type ] = array();
		}
		self::$registry[ $type ][ $slug ] = $config;
	}

	public static function unregister( string $slug, string $type ): void {
		unset( self::$registry[ $type ][ $slug ] );
		if ( empty( self::$registry[ $type ] ) ) {
			unset( self::$registry[ $type ] );
		}
		if ( isset( self::$active[ $type ] ) && self::$active[ $type ] === $slug ) {
			unset( self::$active[ $type ] );
		}
	}

	private static function enable( string $slug, string $type ): void {
		if ( ! isset( self::$registry[ $type ][ $slug ] ) ) {
			return;
		}
		self::$active[ $type ] = $slug;
	}

	public static function disable( string $slug, string $type ): void {
		if ( isset( self::$active[ $type ] ) && self::$active[ $type ] === $slug ) {
			unset( self::$active[ $type ] );
		}
	}

	public static function toggle( string $slug, string $type ): void {
		if ( self::isActive( $slug, $type ) ) {
			self::disable( $slug, $type );
		} else {
			self::enable( $slug, $type );
		}
	}

	public static function isActive( string $slug, string $type ): bool {
		return isset( self::$active[ $type ] ) && self::$active[ $type ] === $slug;
	}

	public static function isEnabled( string $type ): ?string {
		return self::$active[ $type ] ?? null;
	}

	/** True when at least one addon registered this gateway type (loaded), regardless of feature toggle. */
	public static function isRegistered( string $type ): bool {
		return ! empty( self::$registry[ $type ] );
	}

	public static function listByType( string $type ): array {
		return self::$registry[ $type ] ?? array();
	}

	public static function getConfig( string $slug, string $type ): array {
		return self::$registry[ $type ][ $slug ] ?? array();
	}

	public static function getAllConfigs( string $type ): array {
		return self::$registry[ $type ] ?? array();
	}

	public static function getRegisteredTypes(): array {
		return array_keys( self::$registry );
	}

	public static function firstSlug( string $type, string $default = '' ): string {
		$list = self::$registry[ $type ] ?? array();
		if ( empty( $list ) ) {
			return $default;
		}
		return array_key_first( $list );
	}

	public static function fireHook( string $type, string $event ): void {
		$slug = self::isEnabled( $type );
		if ( null === $slug ) {
			return;
		}
		$config = self::getConfig( $slug, $type );
		do_action( "bbcs_gateway_{$type}_{$event}", $slug, $config );
	}

	public static function enableGateway( string $type, string $slug ): void {
		self::enable( $slug, $type );

		$config = self::getConfig( $slug, $type );
		$mutual = $config['mutual_exclusion'] ?? array();
		if ( ! is_array( $mutual ) ) {
			$mutual = array();
		}

		foreach ( $mutual as $excluded_type ) {
			if ( self::isEnabled( $excluded_type ) !== null ) {
				self::disableGateway( $excluded_type );
			}
		}

		do_action( "bbcs_gateway_{$type}_toggled", true, $slug );
	}

	public static function disableGateway( string $type ): void {
		$slug = self::isEnabled( $type );
		if ( null === $slug ) {
			return;
		}
		self::disable( $slug, $type );
		do_action( "bbcs_gateway_{$type}_toggled", false, $slug );
	}

	public static function restoreState( string $type, string $slug ): void {
		if ( ! isset( self::$registry[ $type ][ $slug ] ) ) {
			return;
		}
		self::enable( $slug, $type );
	}
}
