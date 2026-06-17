<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BBCS_DEBUG-gated logger for the add-on install/copy chain. Traces WHY runtime
 * files fail to land in uploads (rename across filesystems, copy_dir errors,
 * partial copies on restrictive shared hosts).
 */
function bbcs_addon_install_debug_log( string $msg ): void {
	$debug = defined( 'BBCS_DEBUG' ) ? BBCS_DEBUG : ( defined( 'WP_DEBUG' ) && WP_DEBUG );
	if ( $debug ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated by BBCS_DEBUG/WP_DEBUG
		error_log( '[BBCS DEBUG] [Addon-Install] ' . $msg );
	}
}

function bbcs_move_addon_into_runtime( string $source_dir, string $slug, array $args = array() ) {
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
		bbcs_rrmdir( $backup );
	}
	if ( is_dir( $folder ) ) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
		$backed_up = @rename( $folder, $backup );
		if ( ! $backed_up ) {
			return new WP_Error( 'backup_failed', __( 'Failed to backup existing add-on.', 'botblocker-security' ) );
		}
	}

    // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
	$moved = @rename( $source_dir, $folder );
	if ( ! $moved && function_exists( 'copy_dir' ) ) {
		bbcs_addon_install_debug_log( 'move_addon[' . $slug . ']: rename ' . $source_dir . ' -> ' . $folder . ' failed; falling back to copy_dir' );
		$copied = copy_dir( $source_dir, $folder );
		$moved  = ! is_wp_error( $copied );
		if ( $moved ) {
			bbcs_rrmdir( $source_dir );
		} else {
			bbcs_addon_install_debug_log( 'move_addon[' . $slug . ']: copy_dir FAILED to ' . $folder . ' | error=' . ( is_wp_error( $copied ) ? $copied->get_error_message() : 'unknown' ) );
		}
	}

	if ( ! $moved ) {
		bbcs_addon_install_debug_log( 'move_addon[' . $slug . ']: install FAILED, no files placed at ' . $folder . ' (dest=' . $dest . ')' );
		if ( is_dir( $folder ) ) {
			bbcs_rrmdir( $folder );
		}
		if ( $backed_up ) {
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
			@rename( $backup, $folder );
		}
		return new WP_Error( 'move_failed', __( 'Failed to install add-on package.', 'botblocker-security' ) );
	}

	$data_probe = $folder . '/inc/bbcs-data-file.php';
	if ( $slug === 'bbcs-early-init' && ! file_exists( $data_probe ) ) {
		bbcs_addon_install_debug_log( 'move_addon[' . $slug . ']: WARNING move reported success but expected file missing at ' . $data_probe . ' (partial copy?)' );
	} else {
		bbcs_addon_install_debug_log( 'move_addon[' . $slug . ']: OK placed at ' . $folder );
	}

	if ( $backed_up && is_dir( $backup ) ) {
		bbcs_rrmdir( $backup );
	}

	return array(
		'slug'     => $slug,
		'folder'   => $folder,
		'replaced' => $backed_up,
	);
}

function bbcs_cleanup_addon_tmp( string $path ): void {
	if ( $path !== '' && is_dir( $path ) ) {
		bbcs_rrmdir( $path );
	} elseif ( $path !== '' && file_exists( $path ) ) {
		wp_delete_file( $path );
	}
}

function bbcs_install_addon_package( string $zip_file, array $args = array() ) {
	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		return new WP_Error( 'file_mods_disabled', __( 'File modifications are disabled.', 'botblocker-security' ) );
	}

	$filename       = isset( $args['filename'] ) && is_string( $args['filename'] ) ? $args['filename'] : '';
	$zip_validation = bbcs_validate_addon_zip( $zip_file, $filename );
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
		bbcs_cleanup_addon_tmp( $tmp_dir );
		return $unzipped;
	}

	$validated = bbcs_validate_addon_extracted( $tmp_dir, $args );
	if ( is_wp_error( $validated ) ) {
		bbcs_cleanup_addon_tmp( $tmp_dir );
		return $validated;
	}

	$moved = bbcs_move_addon_into_runtime( $validated['source_dir'], $validated['slug'], $args );
	if ( is_wp_error( $moved ) ) {
		bbcs_cleanup_addon_tmp( $tmp_dir );
		return $moved;
	}

	bbcs_cleanup_addon_tmp( $tmp_dir );

	$addons    = BotBlockerAddons::scanAll();
	$installed = $addons[ $validated['slug'] ] ?? $validated['addon'];
	$event     = isset( $args['lifecycle_event'] ) ? sanitize_key( (string) $args['lifecycle_event'] ) : 'install';
	if ( $event !== '' ) {
		BotBlockerAddons::dispatchLifecycle( $validated['slug'], $event, $installed, $args );
	}

	return array_merge( $moved, array( 'addon' => $installed ) );
}

function bbcs_addon_install_error_args( $error ): array {
	if ( ! is_wp_error( $error ) ) {
		return array();
	}
	$code    = $error->get_error_code();
	$message = $error->get_error_message();
	$args    = array( 'bbcs_error' => $code !== '' ? $code : 'addon_package' );
	if ( $message !== '' ) {
		$args['bbcs_error_msg'] = $message;
	}
	return $args;
}

function bbcs_rrmdir( string $dir ): void {
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
			bbcs_rrmdir( $path );
		} elseif ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}
	if ( $wp_filesystem && $wp_filesystem->exists( $dir ) ) {
		$wp_filesystem->delete( $dir );
	}
}
