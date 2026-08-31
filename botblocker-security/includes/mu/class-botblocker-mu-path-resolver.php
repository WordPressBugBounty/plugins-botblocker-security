<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BotBlockerMuPathResolver' ) ) {

	/**
	 * Layer-2 plugin path resolution: candidate roots, vendor autoloader
	 * discovery, guarded autoloader include. Pure static, zero WP dependency.
	 */
	class BotBlockerMuPathResolver {

		private static $cache = array();

		/**
		 * Normalizes a filesystem path: backslashes to forward, single trailing slash, '' stays ''.
		 */
		public static function normalize( string $path ): string {
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
		public static function candidates( array $ctx ): array {
			$slug        = ( isset( $ctx['plugin_slug'] ) && is_string( $ctx['plugin_slug'] ) && $ctx['plugin_slug'] !== '' ) ? $ctx['plugin_slug'] : 'botblocker-security';
			$script_dir  = ( isset( $ctx['script_dir'] ) && is_string( $ctx['script_dir'] ) ) ? self::normalize( $ctx['script_dir'] ) : '';
			$plugin_dir  = ( isset( $ctx['wp_plugin_dir'] ) && is_string( $ctx['wp_plugin_dir'] ) ) ? self::normalize( $ctx['wp_plugin_dir'] ) : '';
			$content_dir = ( isset( $ctx['wp_content_dir'] ) && is_string( $ctx['wp_content_dir'] ) ) ? self::normalize( $ctx['wp_content_dir'] ) : '';
			$abspath     = ( isset( $ctx['abspath'] ) && is_string( $ctx['abspath'] ) ) ? self::normalize( $ctx['abspath'] ) : '';

			$candidates = array();
			if ( $script_dir !== '' ) {
				$candidates[] = self::normalize( dirname( $script_dir, 2 ) );
				$candidates[] = self::normalize( dirname( $script_dir, 3 ) );
			}
			if ( $plugin_dir !== '' ) {
				$candidates[] = $plugin_dir . $slug . '/';
			}
			if ( $content_dir !== '' ) {
				$candidates[] = $content_dir . 'plugins/' . $slug . '/';
			}
			if ( $script_dir !== '' ) {
				$candidates[] = self::normalize( dirname( $script_dir, 5 ) . '/plugins/' . $slug );
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
		public static function scan( array $ctx ): ?string {
			$script_dir  = ( isset( $ctx['script_dir'] ) && is_string( $ctx['script_dir'] ) ) ? self::normalize( $ctx['script_dir'] ) : '';
			$plugin_dir  = ( isset( $ctx['wp_plugin_dir'] ) && is_string( $ctx['wp_plugin_dir'] ) ) ? self::normalize( $ctx['wp_plugin_dir'] ) : '';
			$content_dir = ( isset( $ctx['wp_content_dir'] ) && is_string( $ctx['wp_content_dir'] ) ) ? self::normalize( $ctx['wp_content_dir'] ) : '';
			$abspath     = ( isset( $ctx['abspath'] ) && is_string( $ctx['abspath'] ) ) ? self::normalize( $ctx['abspath'] ) : '';

			$bases = array();
			if ( $plugin_dir !== '' ) {
				$bases[] = $plugin_dir;
			}
			if ( $content_dir !== '' ) {
				$bases[] = $content_dir . 'plugins/';
			}
			if ( $script_dir !== '' ) {
				$bases[] = self::normalize( dirname( $script_dir, 5 ) . '/plugins' );
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
		public static function resolvePluginPath( array $ctx ): ?string {
			$key = md5( serialize( $ctx ) );
			if ( array_key_exists( $key, self::$cache ) ) {
				return self::$cache[ $key ];
			}

			$found = null;
			foreach ( self::candidates( $ctx ) as $candidate ) {
				if ( is_dir( $candidate ) && is_file( $candidate . 'botblocker-security.php' ) ) {
					$found = $candidate;
					break;
				}
			}
			if ( $found === null ) {
				$found = self::scan( $ctx );
			}

			self::$cache[ $key ] = $found;
			return $found;
		}

		/**
		 * Autoloader path for a vendor inside the plugin root. Highest version dir wins.
		 *
		 * @param string $plugin_root
		 * @param string $vendor
		 * @return string|null
		 */
		public static function vendorAutoloader( string $plugin_root, string $vendor = 'MaxMindDb' ): ?string {
			if ( $plugin_root === '' ) {
				return null;
			}
			$root      = self::normalize( $plugin_root );
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
		public static function resolveReaderAutoloader( array $ctx, string $vendor = 'MaxMindDb' ): ?string {
			$root = self::resolvePluginPath( $ctx );
			if ( $root === null ) {
				return null;
			}
			return self::vendorAutoloader( $root, $vendor );
		}

		/**
		 * Includes an autoloader with a class_exists guard. Never throws.
		 *
		 * @param string $autoloader
		 * @param string $class
		 * @return bool
		 */
		public static function includeAutoloader( string $autoloader, string $class ): bool {
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
	}
}
