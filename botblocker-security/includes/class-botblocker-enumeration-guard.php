<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerEnumerationGuard {

	public static function register( $settings ): void {
		if ( ! self::enabled( $settings ) ) {
			return;
		}
		add_filter( 'rest_endpoints', array( __CLASS__, 'filterRestEndpoints' ) );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'blockRestRequest' ), 10, 3 );
		add_action( 'template_redirect', array( __CLASS__, 'blockAuthorArchive' ), 0 );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'filterSitemapProvider' ), 10, 2 );
		add_filter( 'oembed_response_data', array( __CLASS__, 'filterOembedData' ), 10, 3 );
	}

	public static function enabled( $settings ): bool {
		if ( ! apply_filters( 'bbcs_hide_user_enumeration_enabled', true ) ) {
			return false;
		}
		if ( ! is_object( $settings ) ) {
			return true;
		}
		return (int) ( $settings->bbcs_hide_user_enumeration ?? 1 ) === 1;
	}

	public static function userCanListUsers(): bool {
		return current_user_can( 'list_users' );
	}

	public static function filterRestEndpoints( $endpoints ) {
		if ( ! is_array( $endpoints ) || self::userCanListUsers() ) {
			return $endpoints;
		}
		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		return $endpoints;
	}

	public static function blockRestRequest( $result, $server, $request ) {
		if ( null !== $result || self::userCanListUsers() ) {
			return $result;
		}
		$route = '';
		if ( is_object( $request ) && method_exists( $request, 'get_route' ) ) {
			$route = (string) $request->get_route();
		}
		if ( '' !== $route && preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
			if ( class_exists( 'BotBlockerHeaderGuard' ) ) {
				BotBlockerHeaderGuard::sendBaselineHeaders();
			}
			return new WP_Error(
				'rest_no_route',
				__( 'No route was found matching the URL and request method.', 'botblocker-security' ),
				array( 'status' => 404 )
			);
		}
		return $result;
	}

	public static function blockAuthorArchive(): void {
		if ( self::userCanListUsers() ) {
			return;
		}
		if ( ! is_author() && ! ( is_feed() && is_author() ) ) {
			return;
		}
		global $wp_query;
		if ( is_object( $wp_query ) && method_exists( $wp_query, 'set_404' ) ) {
			$wp_query->set_404();
		}
		if ( class_exists( 'BotBlockerHeaderGuard' ) ) {
			BotBlockerHeaderGuard::sendBaselineHeaders();
		}
		status_header( 404 );
		nocache_headers();
	}

	public static function filterSitemapProvider( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	public static function filterOembedData( $data, $post, $width ) {
		unset( $post, $width );
		if ( is_array( $data ) ) {
			unset( $data['author_url'], $data['author_name'] );
		}
		return $data;
	}
}
