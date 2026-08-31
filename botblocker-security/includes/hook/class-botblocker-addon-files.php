<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add-on package file operations: URL allowlist, ZIP validation,
 * extracted-package contract gates, runtime move and cleanup.
 * Migrated from hook-addon-validation.php (S-11) + hook-addon-install.php (S-12).
 */
class BotBlockerAddonFiles {

	public static function isAllowedAddonUrl( string $url ): bool {

		$allowed_domains = array( 'botblocker.top', 'globus.studio' );
		$parsed          = wp_parse_url( $url );
		$scheme          = isset( $parsed['scheme'] ) ? strtolower( (string) $parsed['scheme'] ) : '';
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}
		if ( empty( $parsed['host'] ) ) {
			return false;
		}
		$host = strtolower( $parsed['host'] );

		foreach ( $allowed_domains as $domain ) {
			if ( $host === $domain ) {
				return true;
			}
			$suffix = '.' . $domain;
			if ( strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}
		return false;
	}

	public static function addonZipEntryIsSafe( string $name ): bool {
		$name = str_replace( '\\', '/', $name );
		if ( $name === '' || strpos( $name, "\0" ) !== false || strpos( $name, ':' ) !== false ) {
			return false;
		}
		if ( $name[0] === '/' || preg_match( '#(^|/)\.\.(/|$)#', $name ) ) {
			return false;
		}
		return true;
	}

	public static function validateAddonZip( string $zip_file, string $filename = '' ) {
		if ( ! file_exists( $zip_file ) || ! is_readable( $zip_file ) ) {
			return new WP_Error( 'zip_missing', __( 'Add-on package is missing or unreadable.', 'botblocker-security' ) );
		}
		$extension_source = $filename !== '' ? $filename : $zip_file;
		if ( strtolower( pathinfo( $extension_source, PATHINFO_EXTENSION ) ) !== 'zip' ) {
			return new WP_Error( 'zip_extension', __( 'Add-on package must be a ZIP file.', 'botblocker-security' ) );
		}
		if ( filesize( $zip_file ) > 20 * 1024 * 1024 ) {
			return new WP_Error( 'zip_too_large', __( 'Add-on package is too large.', 'botblocker-security' ) );
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			return true;
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_file ) ) {
			return new WP_Error( 'zip_open', __( 'Add-on package cannot be opened.', 'botblocker-security' ) );
		}

		if ( $zip->numFiles < 1 || $zip->numFiles > 500 ) {
			$zip->close();
			return new WP_Error( 'zip_file_count', __( 'Add-on package has an invalid file count.', 'botblocker-security' ) );
		}

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex( $i );
			$name = isset( $stat['name'] ) ? (string) $stat['name'] : '';
			if ( ! self::addonZipEntryIsSafe( $name ) ) {
				$zip->close();
				return new WP_Error( 'zip_unsafe_path', __( 'Add-on package contains an unsafe path.', 'botblocker-security' ) );
			}
			if ( isset( $stat['size'] ) && (int) $stat['size'] > 5 * 1024 * 1024 ) {
				$zip->close();
				return new WP_Error( 'zip_entry_too_large', __( 'Add-on package contains an oversized file.', 'botblocker-security' ) );
			}
		}

		$zip->close();
		return true;
	}

	public static function findExtractedAddonRoot( string $tmp_dir ) {
		if ( ! is_dir( $tmp_dir ) ) {
			return new WP_Error( 'extract_missing', __( 'Temporary extraction directory is missing.', 'botblocker-security' ) );
		}
		$scanned = scandir( $tmp_dir );
		if ( $scanned === false ) {
			return new WP_Error( 'scan_failed', __( 'Failed to scan extraction directory.', 'botblocker-security' ) );
		}
		$entries = array_values(
			array_filter(
				$scanned,
				function ( $entry ) {
					if ( $entry === '.' || $entry === '..' || $entry === '__MACOSX' ) {
						return false;
					}
					return true;
				}
			)
		);

		$root_dirs = array();
		foreach ( $entries as $entry ) {
			if ( ! is_dir( trailingslashit( $tmp_dir ) . $entry ) ) {
				return new WP_Error( 'package_root', __( 'Add-on package must contain exactly one root folder.', 'botblocker-security' ) );
			}
			$root_dirs[] = $entry;
		}

		if ( count( $root_dirs ) !== 1 ) {
			return new WP_Error( 'package_root', __( 'Add-on package must contain exactly one root folder.', 'botblocker-security' ) );
		}
		$root = sanitize_key( $root_dirs[0] );
		if ( $root === '' || $root !== $root_dirs[0] ) {
			return new WP_Error( 'package_slug', __( 'Add-on root folder must be a valid slug.', 'botblocker-security' ) );
		}
		return trailingslashit( $tmp_dir ) . $root;
	}

	public static function validateAddonExtracted( string $tmp_dir, array $args = array() ) {
		$source_dir = self::findExtractedAddonRoot( $tmp_dir );
		if ( is_wp_error( $source_dir ) ) {
			return $source_dir;
		}

		$slug  = basename( $source_dir );
		$addon = BotBlockerAddons::parseManifest( $source_dir, $slug );
		if ( empty( $addon ) ) {
			$addon = BotBlockerAddons::scanLegacy( $source_dir, $slug );
		}

		$expected_slug = isset( $args['slug'] ) ? sanitize_key( (string) $args['slug'] ) : '';
		if ( $expected_slug !== '' && $expected_slug !== $addon['slug'] ) {
			return new WP_Error( 'slug_mismatch', __( 'Add-on package slug does not match the requested add-on.', 'botblocker-security' ) );
		}
		if ( empty( $addon['valid'] ) ) {
			return new WP_Error( 'package_invalid', __( 'Add-on package does not match the BotBlocker add-on contract.', 'botblocker-security' ) );
		}
		if ( empty( $addon['requires_core'] ) ) {
			return new WP_Error( 'requires_core_missing', __( 'Add-on package must declare Requires-Core.', 'botblocker-security' ) );
		}

		$core_version = isset( $args['core_version'] ) && is_string( $args['core_version'] ) && $args['core_version'] !== ''
			? $args['core_version']
			: ( defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '0.0.0' );
		if ( version_compare( $core_version, $addon['requires_core'], '<' ) ) {
			return new WP_Error( 'requires_core', $addon['requires_core'] );
		}
		if ( ! empty( $addon['requires_php'] ) && version_compare( PHP_VERSION, $addon['requires_php'], '<' ) ) {
			return new WP_Error( 'requires_php', $addon['requires_php'] );
		}

		return array(
			'source_dir' => $source_dir,
			'slug'       => $addon['slug'],
			'addon'      => $addon,
		);
	}

	/**
	 * BBCS_DEBUG-gated logger for the add-on install/copy chain. Traces WHY runtime
	 * files fail to land in uploads (rename across filesystems, copy_dir errors,
	 * partial copies on restrictive shared hosts).
	 */
	public static function addonInstallDebugLog( string $msg ): void {
		$debug = defined( 'BBCS_DEBUG' ) ? BBCS_DEBUG : ( defined( 'WP_DEBUG' ) && WP_DEBUG );
		if ( $debug ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated by BBCS_DEBUG/WP_DEBUG
			error_log( '[BBCS DEBUG] [Addon-Install] ' . $msg );
		}
	}

	public static function moveAddonIntoRuntime( string $source_dir, string $slug, array $args = array() ) {
		$slug = sanitize_key( $slug );
		if ( $slug === '' || ! is_dir( $source_dir ) ) {
			return new WP_Error( 'move_source', __( 'Validated add-on source is missing.', 'botblocker-security' ) );
		}

		$dest = trailingslashit( BotBlockerMultisite::getAddonsDir() );
		if ( ! is_dir( $dest ) ) {
			wp_mkdir_p( $dest );
		}

		$folder    = $dest . $slug;
		$backup    = $dest . $slug . '_bbcs_bak';
		$backed_up = false;

		if ( is_dir( $backup ) ) {
			self::rrmdir( $backup );
		}
		if ( is_dir( $folder ) ) {
			$GLOBALS['bbcs_upgrade_swap_in_progress'] = true;
	        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
			$backed_up = @rename( $folder, $backup );
			if ( ! $backed_up ) {
				return new WP_Error( 'backup_failed', __( 'Failed to backup existing add-on.', 'botblocker-security' ) );
			}
		}

    // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
		$moved = @rename( $source_dir, $folder );
		if ( ! $moved && function_exists( 'copy_dir' ) ) {
			self::addonInstallDebugLog( 'move_addon[' . $slug . ']: rename ' . $source_dir . ' -> ' . $folder . ' failed; falling back to copy_dir' );
			$copied = copy_dir( $source_dir, $folder );
			$moved  = ! is_wp_error( $copied );
			if ( $moved ) {
				self::rrmdir( $source_dir );
			} else {
				self::addonInstallDebugLog( 'move_addon[' . $slug . ']: copy_dir FAILED to ' . $folder . ' | error=' . ( is_wp_error( $copied ) ? $copied->get_error_message() : 'unknown' ) );
			}
		}

		if ( ! $moved ) {
			self::addonInstallDebugLog( 'move_addon[' . $slug . ']: install FAILED, no files placed at ' . $folder . ' (dest=' . $dest . ')' );
			if ( is_dir( $folder ) ) {
				self::rrmdir( $folder );
			}
			if ( $backed_up ) {
	            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
				@rename( $backup, $folder );
			}
			return new WP_Error( 'move_failed', __( 'Failed to install add-on package.', 'botblocker-security' ) );
		}

		self::addonInstallDebugLog( 'move_addon[' . $slug . ']: OK placed at ' . $folder );

		// Post-move probe: verify any gateway-declared key file exists (catches partial copies on restrictive hosts).
		$addon = BotBlockerAddons::parseManifest( $folder, $slug );
		$data_probe = $addon['gateway']['early_init']['data_file_probe'] ?? '';
		if ( $data_probe !== '' ) {
			$probe_path = $folder . '/' . ltrim( $data_probe, '/' );
			if ( ! file_exists( $probe_path ) ) {
				self::addonInstallDebugLog( 'move_addon[' . $slug . ']: WARNING - ' . $data_probe . ' missing after move at ' . $probe_path );
			}
		}

		if ( $backed_up && is_dir( $backup ) ) {
			self::rrmdir( $backup );
		}

		return array(
			'slug'     => $slug,
			'folder'   => $folder,
			'replaced' => $backed_up,
		);
	}

	public static function cleanupAddonTmp( string $path ): void {
		if ( $path !== '' && is_dir( $path ) ) {
			self::rrmdir( $path );
		} elseif ( $path !== '' && file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}

	public static function installAddonPackage( string $zip_file, array $args = array() ) {
		if ( BotBlockerAddons::isLocalMode() ) {
			return new WP_Error( 'local_mode', __( 'Add-on package install is disabled in local add-on mode.', 'botblocker-security' ) );
		}
		if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
			return new WP_Error( 'file_mods_disabled', __( 'File modifications are disabled.', 'botblocker-security' ) );
		}

		$filename       = isset( $args['filename'] ) && is_string( $args['filename'] ) ? $args['filename'] : '';
		$zip_validation = self::validateAddonZip( $zip_file, $filename );
		if ( is_wp_error( $zip_validation ) ) {
			return $zip_validation;
		}

		if ( ! function_exists( 'unzip_file' ) || ! function_exists( 'WP_Filesystem' ) || ! function_exists( 'copy_dir' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! WP_Filesystem() ) {
			return new WP_Error( 'fs_unavailable', __( 'WordPress filesystem is unavailable.', 'botblocker-security' ) );
		}

		$tmp_dir = trailingslashit( get_temp_dir() ) . 'bbcs-addon-' . wp_generate_uuid4();
		if ( ! wp_mkdir_p( $tmp_dir ) ) {
			return new WP_Error( 'tmp_failed', __( 'Failed to create temporary add-on directory.', 'botblocker-security' ) );
		}

		$unzipped = unzip_file( $zip_file, $tmp_dir );
		if ( is_wp_error( $unzipped ) ) {
			self::cleanupAddonTmp( $tmp_dir );
			return $unzipped;
		}

		$validated = self::validateAddonExtracted( $tmp_dir, $args );
		if ( is_wp_error( $validated ) ) {
			self::cleanupAddonTmp( $tmp_dir );
			return $validated;
		}

		$moved = self::moveAddonIntoRuntime( $validated['source_dir'], $validated['slug'], $args );
		if ( is_wp_error( $moved ) ) {
			self::cleanupAddonTmp( $tmp_dir );
			self::addonInstallDebugLog( 'install[' . $validated['slug'] . ']: move failed: ' . $moved->get_error_message() );
			return $moved;
		}
		self::addonInstallDebugLog( 'install[' . $validated['slug'] . ']: moved, replaced=' . ( $moved['replaced'] ? 'yes' : 'no' ) );

		self::cleanupAddonTmp( $tmp_dir );

		if ( ! class_exists( 'BotBlockerFileRenderer' ) && defined( 'BOTBLOCKER_DIR' ) ) {
			$renderer_file = BOTBLOCKER_DIR . 'includes/cache/class-botblocker-file-renderer.php';
			if ( file_exists( $renderer_file ) ) {
				require_once $renderer_file;
			}
		}
		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::renderAddons();
			self::addonInstallDebugLog( 'install[' . $validated['slug'] . ']: snapshot regenerated' );
		} else {
			self::addonInstallDebugLog( 'install[' . $validated['slug'] . ']: WARNING - file renderer unavailable, add-on snapshot NOT regenerated' );
		}

		$installed = BotBlockerAddons::parseManifest( $moved['folder'], $validated['slug'] );
		if ( empty( $installed ) ) {
			$installed = $validated['addon'];
		}

		$event = isset( $args['lifecycle_event'] ) ? sanitize_key( (string) $args['lifecycle_event'] ) : 'install';

		if ( $event !== '' && empty( $moved['replaced'] ) ) {
			try {
				BotBlockerAddons::dispatchLifecycle( $validated['slug'], $event, $installed, $args );
			} catch ( \Throwable $e ) {
				BotBlockerAddons::panic(
					array( $validated['slug'] ),
					array(
						array(
							'name'  => ( isset( $installed['name'] ) && $installed['name'] !== '' ) ? $installed['name'] : $validated['slug'],
							'error' => BotBlockerAddons::FAIL_LIFECYCLE_THROW,
						),
					)
				);
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] install: add-on "' . $validated['slug'] . '" lifecycle threw and was switched off: ' . $e->getMessage() );
				return new WP_Error(
					'addon_lifecycle_failed',
					__( 'The add-on could not be activated after installation.', 'botblocker-security' )
				);
			}
		} elseif ( ! empty( $moved['replaced'] ) ) {
			self::addonInstallDebugLog( 'install[' . $validated['slug'] . ']: cold swap, lifecycle "' . $event . '" not dispatched in this request' );
		}

		return array_merge( $moved, array( 'addon' => $installed ) );
	}

	public static function rrmdir( string $dir ): void {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		if ( $wp_filesystem && $wp_filesystem->exists( $dir ) ) {
			$wp_filesystem->delete( $dir, true );
			return;
		}
		if ( is_file( $dir ) || is_link( $dir ) ) {
			if ( file_exists( $dir ) ) {
				wp_delete_file( $dir ); }
			return;
		}
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				self::rrmdir( $path );
			} elseif ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		if ( $wp_filesystem && $wp_filesystem->exists( $dir ) ) {
			$wp_filesystem->delete( $dir );
		}
	}
}
