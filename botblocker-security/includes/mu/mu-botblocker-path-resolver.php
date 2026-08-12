<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes a filesystem path: backslashes to forward, single trailing slash, '' stays ''.
 */
function bbcs_path_normalize( string $path ): string {
	if ( $path === '' ) {
		return '';
	}
	return rtrim( str_replace( '\\', '/', $path ), '/' ) . '/';
}

/**
 * Ordered candidate list of plugin root dirs for the given context.
 * Context keys: script_dir, plugin_slug, abspath, wp_plugin_dir, wp_content_dir.
 * Pure function - no filesystem checks.
 *
 * @param array $ctx
 * @return string[]
 */
function bbcs_path_candidates( array $ctx ): array {
	$slug        = ( isset( $ctx['plugin_slug'] ) && is_string( $ctx['plugin_slug'] ) && $ctx['plugin_slug'] !== '' ) ? $ctx['plugin_slug'] : 'botblocker-security';
	$script_dir  = ( isset( $ctx['script_dir'] ) && is_string( $ctx['script_dir'] ) ) ? bbcs_path_normalize( $ctx['script_dir'] ) : '';
	$plugin_dir  = ( isset( $ctx['wp_plugin_dir'] ) && is_string( $ctx['wp_plugin_dir'] ) ) ? bbcs_path_normalize( $ctx['wp_plugin_dir'] ) : '';
	$content_dir = ( isset( $ctx['wp_content_dir'] ) && is_string( $ctx['wp_content_dir'] ) ) ? bbcs_path_normalize( $ctx['wp_content_dir'] ) : '';
	$abspath     = ( isset( $ctx['abspath'] ) && is_string( $ctx['abspath'] ) ) ? bbcs_path_normalize( $ctx['abspath'] ) : '';

	$candidates = array();
	if ( $script_dir !== '' ) {
		$candidates[] = bbcs_path_normalize( dirname( $script_dir, 2 ) );
		$candidates[] = bbcs_path_normalize( dirname( $script_dir, 3 ) );
	}
	if ( $plugin_dir !== '' ) {
		$candidates[] = $plugin_dir . $slug . '/';
	}
	if ( $content_dir !== '' ) {
		$candidates[] = $content_dir . 'plugins/' . $slug . '/';
	}
	if ( $script_dir !== '' ) {
		$candidates[] = bbcs_path_normalize( dirname( $script_dir, 5 ) . '/plugins/' . $slug );
	}
	if ( $abspath !== '' ) {
		$candidates[] = $abspath . 'wp-content/plugins/' . $slug . '/';
	}

	$result = array();
	foreach ( $candidates as $candidate ) {
		if ( $candidate === '' || in_array( $candidate, $result, true ) ) {
			continue;
		}
		$result[] = $candidate;
	}
	return $result;
}

/**
 * Scans plugin base dirs for a directory containing the plugin main file.
 * Used only when direct candidates all fail (renamed plugin folder).
 *
 * @param array $ctx
 * @return string|null
 */
function bbcs_path_scan( array $ctx ): ?string {
	$script_dir  = ( isset( $ctx['script_dir'] ) && is_string( $ctx['script_dir'] ) ) ? bbcs_path_normalize( $ctx['script_dir'] ) : '';
	$plugin_dir  = ( isset( $ctx['wp_plugin_dir'] ) && is_string( $ctx['wp_plugin_dir'] ) ) ? bbcs_path_normalize( $ctx['wp_plugin_dir'] ) : '';
	$content_dir = ( isset( $ctx['wp_content_dir'] ) && is_string( $ctx['wp_content_dir'] ) ) ? bbcs_path_normalize( $ctx['wp_content_dir'] ) : '';
	$abspath     = ( isset( $ctx['abspath'] ) && is_string( $ctx['abspath'] ) ) ? bbcs_path_normalize( $ctx['abspath'] ) : '';

	$bases = array();
	if ( $plugin_dir !== '' ) {
		$bases[] = $plugin_dir;
	}
	if ( $content_dir !== '' ) {
		$bases[] = $content_dir . 'plugins/';
	}
	if ( $script_dir !== '' ) {
		$bases[] = bbcs_path_normalize( dirname( $script_dir, 5 ) . '/plugins' );
	}
	if ( $abspath !== '' ) {
		$bases[] = $abspath . 'wp-content/plugins/';
	}

	$seen = array();
	foreach ( $bases as $base ) {
		if ( $base === '' || in_array( $base, $seen, true ) || ! is_dir( $base ) ) {
			continue;
		}
		$seen[]  = $base;
		$entries = @scandir( $base );
		if ( $entries === false ) {
			continue;
		}
		foreach ( $entries as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			$candidate = $base . $entry . '/';
			if ( is_dir( $candidate ) && is_file( $candidate . 'botblocker-security.php' ) ) {
				return $candidate;
			}
		}
	}
	return null;
}

/**
 * First live plugin root candidate, cached per context for the request.
 *
 * @param array $ctx
 * @return string|null
 */
function bbcs_resolve_plugin_path( array $ctx ): ?string {
	static $cache = array();
	$key = md5( serialize( $ctx ) );
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	$found = null;
	foreach ( bbcs_path_candidates( $ctx ) as $candidate ) {
		if ( is_dir( $candidate ) && is_file( $candidate . 'botblocker-security.php' ) ) {
			$found = $candidate;
			break;
		}
	}
	if ( $found === null ) {
		$found = bbcs_path_scan( $ctx );
	}

	$cache[ $key ] = $found;
	return $found;
}

/**
 * Autoloader path for a vendor inside the plugin root. Highest version dir wins.
 *
 * @param string $plugin_root
 * @param string $vendor
 * @return string|null
 */
function bbcs_vendor_autoloader( string $plugin_root, string $vendor = 'MaxMindDb' ): ?string {
	if ( $plugin_root === '' ) {
		return null;
	}
	$root      = bbcs_path_normalize( $plugin_root );
	$vendor_dir = $root . 'vendor/' . $vendor . '/';
	if ( ! is_dir( $vendor_dir ) ) {
		return null;
	}
	$dirs = glob( $vendor_dir . '*', GLOB_ONLYDIR );
	if ( ! $dirs ) {
		return null;
	}
	usort(
		$dirs,
		static function ( $a, $b ) {
			return version_compare( basename( $b ), basename( $a ) );
		}
	);
	foreach ( $dirs as $dir ) {
		$autoloader = rtrim( $dir, '/\\' ) . '/standalone/autoloader.php';
		if ( is_file( $autoloader ) && is_readable( $autoloader ) ) {
			return $autoloader;
		}
	}
	return null;
}

/**
 * One-call reader lookup: plugin root + highest vendor autoloader.
 *
 * @param array  $ctx
 * @param string $vendor
 * @return string|null
 */
function bbcs_resolve_reader_autoloader( array $ctx, string $vendor = 'MaxMindDb' ): ?string {
	$root = bbcs_resolve_plugin_path( $ctx );
	if ( $root === null ) {
		return null;
	}
	return bbcs_vendor_autoloader( $root, $vendor );
}

/**
 * Includes an autoloader with a class_exists guard. Never throws.
 *
 * @param string $autoloader
 * @param string $class
 * @return bool
 */
function bbcs_include_autoloader( string $autoloader, string $class ): bool {
	if ( $autoloader === '' || ! is_file( $autoloader ) || ! is_readable( $autoloader ) ) {
		return false;
	}
	if ( class_exists( $class ) ) {
		return true;
	}
	try {
		require_once $autoloader;
	} catch ( \Throwable $e ) {
		return false;
	}
	return class_exists( $class );
}
