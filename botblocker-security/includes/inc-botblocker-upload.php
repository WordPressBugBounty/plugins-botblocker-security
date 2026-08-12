<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_get_protected_upload_slug(): string {
	static $slug = null;
	if ( $slug !== null ) {
		return $slug;
	}
	$raw  = defined( 'BOTBLOCKER_SHORT_NAME' ) ? BOTBLOCKER_SHORT_NAME : 'botblocker';
	$slug = sanitize_title( (string) $raw );
	return $slug;
}

function bbcs_get_protected_upload_dir( bool $return_url = false ) {

	static $cached_dir  = null;
	static $cached_url  = null;

	if ( $return_url && $cached_url !== null ) {
		return $cached_url;
	}
	if ( ! $return_url && $cached_dir !== null ) {
		return $cached_dir;
	}

	$uploads = wp_upload_dir();
	$slug    = bbcs_get_protected_upload_slug();
	$dir     = trailingslashit( $uploads['basedir'] ) . $slug . '/';

	$dir_url = isset( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) . $slug . '/' : null;

	if ( ! is_dir( $dir ) ) {
		$cached_dir = null;
		$cached_url = null;
		return null;
	}

	$marker = $dir . 'bbcs-owner.txt';
	if ( ! file_exists( $marker ) ) {
		$cached_dir = null;
		$cached_url = null;
		return null;
	}

	$cached_dir = $dir;
	$cached_url = $dir_url;
	return $return_url ? $dir_url : $dir;
}

function bbcs_create_protected_upload_dir( bool $return_url = false ) {
	if ( ! function_exists( 'wp_upload_dir' ) ) {
		return new WP_Error( 'no_wp_context', 'wp_upload_dir() unavailable' );
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return new WP_Error( 'uploads_error', $uploads['error'] );
	}

	$slug = bbcs_get_protected_upload_slug();
	$dir  = trailingslashit( $uploads['basedir'] ) . $slug . '/';

	$dir_url = isset( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) . $slug . '/' : null;

	if ( ! is_dir( $dir ) ) {
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'mkdir_failed', 'Failed to create plugin uploads directory' );
		}
	}

	$addons_dir = trailingslashit( $dir . 'addons' );
	if ( ! is_dir( $addons_dir ) ) {
		if ( ! wp_mkdir_p( $addons_dir ) ) {
			return new WP_Error( 'mkdir_failed', 'Failed to create addons directory' );
		}
	}

	$data_dir = trailingslashit( $dir . 'data' );
	if ( ! is_dir( $data_dir ) ) {
		if ( ! wp_mkdir_p( $data_dir ) ) {
			return new WP_Error( 'mkdir_failed', 'Failed to create data directory' );
		}
	}

	$marker_name    = 'bbcs-owner.txt';
	$marker_content = 'botblocker|' . ( defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : 'unknown' ) . '|' . "\n";

	foreach ( array( $dir, $addons_dir, $data_dir ) as $base ) {
		BotBlockerWpUtility::write_web_access_deny( $base );
		$marker = trailingslashit( $base ) . $marker_name;
		if ( ! file_exists( $marker ) ) {
			if ( false === file_put_contents( $marker, $marker_content, LOCK_EX ) ) {
				return new WP_Error( 'write_failed', 'Failed to write protection file: ' . $marker_name );
			}
		}
	}

	return $return_url ? $dir_url : $dir;
}
