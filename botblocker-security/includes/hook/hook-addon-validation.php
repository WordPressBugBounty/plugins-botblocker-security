<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_is_allowed_addon_url( string $url ): bool {

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

function bbcs_addon_zip_entry_is_safe( string $name ): bool {
	$name = str_replace( '\\', '/', $name );
	if ( $name === '' || strpos( $name, "\0" ) !== false || strpos( $name, ':' ) !== false ) {
		return false;
	}
	if ( $name[0] === '/' || preg_match( '#(^|/)\.\.(/|$)#', $name ) ) {
		return false;
	}
	return true;
}

function bbcs_validate_addon_zip( string $zip_file, string $filename = '' ) {
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
		if ( ! bbcs_addon_zip_entry_is_safe( $name ) ) {
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

function bbcs_find_extracted_addon_root( string $tmp_dir ) {
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

function bbcs_validate_addon_extracted( string $tmp_dir, array $args = array() ) {
	$source_dir = bbcs_find_extracted_addon_root( $tmp_dir );
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
