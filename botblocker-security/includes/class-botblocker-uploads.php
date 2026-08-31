<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerUploads {

	public static function getProtectedUploadSlug(): string {
		static $slug = null;
		if ( $slug !== null ) {
			return $slug;
		}
		$raw  = defined( 'BOTBLOCKER_SHORT_NAME' ) ? BOTBLOCKER_SHORT_NAME : 'botblocker';
		$slug = sanitize_title( (string) $raw );
		return $slug;
	}

	public static function getProtectedUploadDir( bool $return_url = false ) {

		static $cached_dir = array();
		static $cached_url = array();
		$blog_id           = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0;

		if ( $return_url && isset( $cached_url[ $blog_id ] ) ) {
			return $cached_url[ $blog_id ];
		}
		if ( ! $return_url && isset( $cached_dir[ $blog_id ] ) ) {
			return $cached_dir[ $blog_id ];
		}

		$uploads = wp_upload_dir();
		$slug    = self::getProtectedUploadSlug();
		$dir     = trailingslashit( $uploads['basedir'] ) . $slug . '/';

		$dir_url = isset( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) . $slug . '/' : null;

		if ( ! is_dir( $dir ) ) {
			return null;
		}

		$marker = $dir . 'bbcs-owner.txt';
		if ( ! file_exists( $marker ) ) {
			return null;
		}

		$cached_dir[ $blog_id ] = $dir;
		$cached_url[ $blog_id ] = $dir_url;
		return $return_url ? $dir_url : $dir;
	}

	public static function createProtectedUploadDir( bool $return_url = false ) {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return new WP_Error( 'no_wp_context', 'wp_upload_dir() unavailable' );
		}

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'uploads_error', $uploads['error'] );
		}

		$slug = self::getProtectedUploadSlug();
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
}
