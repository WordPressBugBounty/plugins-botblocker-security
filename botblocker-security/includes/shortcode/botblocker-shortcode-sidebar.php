<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_get_botblocker_news( $atts ): string {
	if ( BOTBLOCKER_CACHE_NEWS == true ) {
		$cache_key   = 'bbcs_botblocker_news_feed';
		$cached_feed = get_transient( $cache_key );
		if ( $cached_feed ) {
			return $cached_feed;
		}
	}

	$atts = shortcode_atts(
		array(
			'count' => 5,
		),
		$atts
	);

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
		return 'Error fetching news: ' . esc_html( $rss->get_error_message() );
	}
	$maxitems = $rss->get_item_quantity( 0 );
	if ( $maxitems == 0 ) {
		return esc_html__( 'No news items available', 'botblocker-security' );
	}

	$rss_items = $rss->get_items( 0, $maxitems );
	usort(
		$rss_items,
		function ( $a, $b ) {
			return $b->get_date( 'U' ) - $a->get_date( 'U' );
		}
	);

	$rss_items = array_slice( $rss_items, 0, (int) $atts['count'] );

	$output = '<ul class="bbcs_botblocker-news">';
	foreach ( $rss_items as $item ) {
		$output .= '<li class="bbcs_news-item">';
		$output .= '<a href="' . esc_url( $item->get_link() ) . '" target="_blank" class="bbcs_news_a">' . esc_html( $item->get_title() ) . '</a>';
		$output .= '<span class="bbcs_news-date">' . esc_html( $item->get_date( 'j F Y' ) ) . ' at ' . esc_html( $item->get_date( 'H:i' ) ) . '</span>';
		$output .= '</li>';
	}
	$output .= '</ul>';

	if ( BOTBLOCKER_CACHE_NEWS == true ) {
		set_transient( $cache_key, $output, BOTBLOCKER_CACHE_NEWS_TIME );
	}

	return $output;
}
add_shortcode( 'bbcs_botblocker_news', 'bbcs_get_botblocker_news' );


function bbcs_getDatabaseUpdate(): string {
	$cache_key = 'bbcs_database_update';
	if ( BOTBLOCKER_CACHE_SIDEBAR_STATS ) {
		$cached_data = get_transient( $cache_key );
		if ( $cached_data ) {
			return $cached_data;
		}
	}

	$url      = BOTBLOCKER_BASE_UPDATE;
	$args     = array(
		'method'      => 'GET',
		'timeout'     => 3,
		'redirection' => 0,
		'httpversion' => '1.1',
		'headers'     => array(
			'User-Agent' => method_exists( 'BotBlockerMultisite', 'getCurrentUserAgent' ) ? BotBlockerMultisite::getCurrentUserAgent() : 'BotBlocker/Stats', //BBCS-MULTISITE
		),
	);
	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) ) {
		return 'Error fetching data';
	}
	$http_code = wp_remote_retrieve_response_code( $response );
	$body      = wp_remote_retrieve_body( $response );
	if ( $http_code !== 200 || empty( $body ) ) {
		return 'Error fetching data';
	}
	$number = intval( $body );
	$output = esc_html__( 'New bots discovered today:', 'botblocker-security' ) . ' <b>' . $number . '</b>';

	if ( BOTBLOCKER_CACHE_SIDEBAR_STATS ) {
		set_transient( $cache_key, $output, BOTBLOCKER_CACHE_SIDEBAR_STATS_TIME );
	}
	return $output;
}
add_shortcode( 'bbcs_database_update', 'bbcs_getDatabaseUpdate' );


function bbcs_getDatabaseAll(): string {

	$cache_key = 'bbcs_database_total';
	if ( BOTBLOCKER_CACHE_SIDEBAR_STATS ) {
		$cached_data = get_transient( $cache_key );
		if ( $cached_data ) {
			return $cached_data;
		}
	}
	$url      = BOTBLOCKER_BASE_TOTAL;
	$args     = array(
		'method'      => 'GET',
		'timeout'     => 3,
		'redirection' => 0,
		'httpversion' => '1.1',
		'headers'     => array(
			'User-Agent' => method_exists( 'BotBlockerMultisite', 'getCurrentUserAgent' ) ? BotBlockerMultisite::getCurrentUserAgent() : 'BotBlocker/Stats', //BBCS-MULTISITE
		),
	);
	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) ) {
		return 'Error fetching data';
	}
	$http_code = wp_remote_retrieve_response_code( $response );
	$body      = wp_remote_retrieve_body( $response );
	if ( $http_code !== 200 || empty( $body ) ) {
		return 'Error fetching data';
	}
	$number = intval( $body );
	$output = esc_html__( 'Malicious IPs in cloud database:', 'botblocker-security' ) . ' <b>' . $number . '</b>';
	if ( BOTBLOCKER_CACHE_SIDEBAR_STATS ) {
		set_transient( $cache_key, $output, BOTBLOCKER_CACHE_SIDEBAR_STATS_TIME );
	}
	return $output;
}
add_shortcode( 'bbcs_database_total', 'bbcs_getDatabaseAll' );

function bbcs_system_status_view(): string {
	if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
		return esc_html__( 'You do not have permission to view this information.', 'botblocker-security' );
	}
	global $wpdb;

	$output  = '<pre class="bbcs_pre">';
	$output .= 'OS: ' . php_uname( 's' ) . ' ' . php_uname( 'r' ) . "\n";
	$output .= 'Web: ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : esc_html__( 'Unknown', 'botblocker-security' ) ) . "\n";
	$output .= 'DB v.' . $wpdb->db_version() . "\n";
	$output .= 'PHP v.' . phpversion() . "\n";

	$output .= "\nWordPress v." . get_bloginfo( 'version' ) . "\n";
	if ( defined( 'BOTBLOCKER_VERSION' ) ) {
		$output .= 'BotBlocker v.' . BOTBLOCKER_VERSION . "\n";
	}

	$output .= "\nPHP vars:\n";
	$output .= 'memory_limit: ' . ini_get( 'memory_limit' ) . "\n";
	$output .= 'max_execution_time: ' . ini_get( 'max_execution_time' ) . "\n";
	$output .= 'post_max_size: ' . ini_get( 'post_max_size' ) . "\n";
	$output .= 'upload_max_filesize: ' . ini_get( 'upload_max_filesize' ) . "\n";

	$output .= '</pre>';
	return $output;
}
add_shortcode( 'bbcs_system_status', 'bbcs_system_status_view' );


function bbcs_blockedToday(): string {
	$BBCS = BotBlocker::getInstance();
	if ( ! empty( $BBCS->statistics['today_blocked'] ) && $BBCS->statistics['today_blocked'] !== BOTBLOCKER_EMPTY ) {
		return $BBCS->statistics['today_blocked'];
	} else {
		return '0';
	}
}
add_shortcode( 'bbcs_blocked_today', 'bbcs_blockedToday' );

function bbcs_blockedTotal(): string {
	$BBCS = BotBlocker::getInstance();
	if ( ! empty( $BBCS->statistics['total_blocked'] ) && $BBCS->statistics['total_blocked'] !== BOTBLOCKER_EMPTY ) {
		return $BBCS->statistics['total_blocked'];
	} else {
		return '0';
	}
}
add_shortcode( 'bbcs_blocked_total', 'bbcs_blockedTotal' );

function bbcs_plugins_themes_view(): string {

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );
	//BBCS-MULTISITE
	if ( is_multisite() ) {
		$network_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
		$active_plugins  = array_unique( array_merge( $active_plugins, $network_plugins ) );
	}

	$themes        = wp_get_themes();
	$current_theme = wp_get_theme();

	$output = '<div class="bbcs-pt-list">';

	$output .= '<h4 class="bbcs-pt-title">' . esc_html__( 'Plugins', 'botblocker-security' ) . '</h4>';
	$output .= '<ul class="bbcs-pt-ul bbcs-mb-16">';
	foreach ( $plugins as $plugin_file => $plugin_data ) {
		$is_active  = in_array( $plugin_file, $active_plugins, true );
		$statusTxt  = $is_active ? esc_html__( 'Active', 'botblocker-security' ) : esc_html__( 'Inactive', 'botblocker-security' );
		$badgeClass = $is_active ? 'bbcs-pt-badge-active' : 'bbcs-pt-badge-inactive';

		$name    = isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';
		$version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
		$author  = isset( $plugin_data['AuthorName'] ) && $plugin_data['AuthorName'] !== '' ? $plugin_data['AuthorName'] : ( isset( $plugin_data['Author'] ) ? wp_strip_all_tags( $plugin_data['Author'] ) : '' );
		$uri     = ! empty( $plugin_data['PluginURI'] ) ? $plugin_data['PluginURI'] : '';

		$output .= '<li class="bbcs-pt-item">';
		$output .= '<div class="bbcs-pt-row">'
				. '<span class="bbcs-pt-name">' . esc_html( $name ) . '</span>'
				. '<span class="bbcs-pt-badge ' . esc_attr( $badgeClass ) . '">' . $statusTxt . '</span>'
				. '</div>';

		$output .= '<div class="bbcs-pt-meta">'
				. '<span class="bbcs-pt-ver">' . esc_html__( 'Version', 'botblocker-security' ) . ': ' . esc_html( $version ) . '</span>'
				. ' · '
				. '<span class="bbcs-pt-author">' . esc_html__( 'Author', 'botblocker-security' ) . ': ' . wp_strip_all_tags( $author ) . '</span>';
		if ( ! empty( $uri ) ) {
			$output .= ' <a href="' . esc_url( $uri ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'Plugin homepage', 'botblocker-security' ) . '"><i class="fa-solid fa-up-right-from-square bbcs-pt-icon"></i></a>';
		}
		$output .= '</div>';

		$output .= '</li>';
	}
	$output .= '</ul>';

	$output .= '<h4 class="bbcs-pt-title">' . esc_html__( 'Themes', 'botblocker-security' ) . '</h4>';
	$output .= '<ul class="bbcs-pt-ul">';
	foreach ( $themes as $theme ) {
		$is_active  = ( $theme->get_stylesheet() === $current_theme->get_stylesheet() );
		$statusTxt  = $is_active ? esc_html__( 'Active', 'botblocker-security' ) : esc_html__( 'Inactive', 'botblocker-security' );
		$badgeClass = $is_active ? 'bbcs-pt-badge-active' : 'bbcs-pt-badge-inactive';

		$name    = $theme->get( 'Name' );
		$version = $theme->get( 'Version' );
		$author  = $theme->get( 'Author' );
		$uri     = $theme->get( 'ThemeURI' );

		$output .= '<li class="bbcs-pt-item">';
		$output .= '<div class="bbcs-pt-row">'
				. '<span class="bbcs-pt-name">' . esc_html( $name ) . '</span>'
				. '<span class="bbcs-pt-badge ' . esc_attr( $badgeClass ) . '">' . $statusTxt . '</span>'
				. '</div>';

		$output .= '<div class="bbcs-pt-meta">'
				. '<span class="bbcs-pt-ver">' . esc_html__( 'Version', 'botblocker-security' ) . ': ' . esc_html( $version ) . '</span>'
				. ' · '
				. '<span class="bbcs-pt-author">' . esc_html__( 'Author', 'botblocker-security' ) . ': ' . wp_strip_all_tags( $author ) . '</span>';
		if ( ! empty( $uri ) ) {
			$output .= ' <a href="' . esc_url( $uri ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'Theme homepage', 'botblocker-security' ) . '"><i class="fa-solid fa-up-right-from-square bbcs-pt-icon"></i></a>';
		}
		$output .= '</div>';

		$output .= '</li>';
	}
	$output .= '</ul>';
	$output .= '</div>';
	return $output;
}
add_shortcode( 'bbcs_plugins_themes', 'bbcs_plugins_themes_view' );
