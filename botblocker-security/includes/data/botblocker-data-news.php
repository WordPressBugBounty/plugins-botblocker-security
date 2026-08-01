<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_get_news_items( int $count = 5, &$error = null ): array {
	$cache_key   = 'bbcs_news_items';

	if ( BOTBLOCKER_CACHE_NEWS == true ) {
		$cached      = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$attempt      = 0;
	$max_attempts = 3;
	$rss          = null;

	while ( $attempt < $max_attempts ) {
		$rss = fetch_feed( BOTBLOCKER_FEED_URL );
		if ( ! is_wp_error( $rss ) ) {
			break;
		}
		++$attempt;
		sleep( 1 );
	}

	if ( is_wp_error( $rss ) ) {
		$error = $rss->get_error_message();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( 'BBCS News RSS error after ' . $max_attempts . ' attempts: ' . $error );
		}
		return array();
	}

	$maxitems  = $rss->get_item_quantity( 0 );
	if ( $maxitems == 0 ) {
		return array();
	}

	$rss_items = $rss->get_items( 0, $maxitems );
	usort(
		$rss_items,
		function ( $a, $b ) {
			return $b->get_date( 'U' ) - $a->get_date( 'U' );
		}
	);

	$rss_items = array_slice( $rss_items, 0, $count );

	$items = array();
	foreach ( $rss_items as $item ) {
		$items[] = array(
			'link'  => $item->get_link(),
			'title' => $item->get_title(),
			'date'  => $item->get_date( 'j F Y' ),
			'time'  => $item->get_date( 'H:i' ),
		);
	}

	if ( BOTBLOCKER_CACHE_NEWS == true ) {
		set_transient( $cache_key, $items, BOTBLOCKER_CACHE_NEWS_TIME );
	}

	return $items;
}
