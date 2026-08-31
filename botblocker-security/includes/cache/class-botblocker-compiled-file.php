<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability bridge for the Layer-1 shared class BotBlockerDataFile: stale deployed copies (runtime early-init) shadow the core copy via the class_exists guard, so members added after a copy was deployed must never be called directly - call them through invalidate().
 */
class BotBlockerCompiledFile {

	public const EXPECTED_CLASS_REV = 2;

	public static function invalidate( string $file ): void {
		if ( method_exists( 'BotBlockerDataFile', 'invalidateCompiled' ) ) {
			BotBlockerDataFile::invalidateCompiled( $file );
			return;
		}
		// Stale Layer-1 copy (pre-1.7.4.118): inline invalidation the callers used before the method existed.
		if ( function_exists( 'opcache_invalidate' ) ) {
			@opcache_invalidate( $file, true );
		}
	}

	public static function isCurrent(): bool {
		return defined( 'BotBlockerDataFile::CLASS_REV' )
			&& BotBlockerDataFile::CLASS_REV >= self::EXPECTED_CLASS_REV;
	}
}
