<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerSidebarShortcodes {

	public static function register(): void {
		add_shortcode( 'bbcs_botblocker_news', array( self::class, 'getBotblockerNews' ) );
		add_shortcode( 'bbcs_database_update', array( self::class, 'getDatabaseUpdate' ) );
		add_shortcode( 'bbcs_database_total', array( self::class, 'getDatabaseAll' ) );
		add_shortcode( 'bbcs_system_status', array( self::class, 'systemStatusView' ) );
		add_shortcode( 'bbcs_blocked_today', array( self::class, 'blockedToday' ) );
		add_shortcode( 'bbcs_blocked_total', array( self::class, 'blockedTotal' ) );
		add_shortcode( 'bbcs_plugins_themes', array( self::class, 'pluginsThemesView' ) );
	}

	public static function getBotblockerNews( $atts ): string {
		$atts = shortcode_atts(
			array(
				'count' => 5,
			),
			$atts
		);

		if ( ! class_exists( 'BotBlockerNews' ) ) {
			return esc_html__( 'No news items available', 'botblocker-security' );
		}

		$error = null;
		$items = BotBlockerNews::getItems( (int) $atts['count'], $error );

		if ( empty( $items ) ) {
			if ( $error ) {
				return esc_html( 'Error fetching news: ' . $error );
			}
			return esc_html__( 'No news items available', 'botblocker-security' );
		}

		$output = '<ul class="bbcs_botblocker-news">';
		foreach ( $items as $item ) {
			$output .= '<li class="bbcs_news-item">';
			$output .= '<a href="' . esc_url( $item['link'] ) . '" target="_blank" class="bbcs_news_a">' . esc_html( $item['title'] ) . '</a>';
			$output .= '<span class="bbcs_news-date">' . esc_html( $item['date'] ) . ' at ' . esc_html( $item['time'] ) . '</span>';
			$output .= '</li>';
		}
		$output .= '</ul>';

		return $output;
	}

	public static function getDatabaseUpdate(): string {
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

	public static function getDatabaseAll(): string {

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

	public static function systemStatusView(): string {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return esc_html__( 'You do not have permission to view this information.', 'botblocker-security' );
		}

		$info = BotBlockerSystemInfoData::getInstance();

		$output  = '<pre class="bbcs_pre">';
		$output .= 'OS: ' . esc_html( $info->os ) . "\n";
		$output .= 'Web: ' . esc_html( $info->web ) . "\n";
		$output .= 'DB v.' . esc_html( $info->db_version ) . "\n";
		$output .= 'PHP v.' . esc_html( $info->php ) . "\n";
		$output .= "\nWordPress v." . esc_html( $info->wp ) . "\n";
		if ( ! empty( $info->bb_version ) ) {
			$output .= 'BotBlocker v.' . esc_html( $info->bb_version ) . "\n";
		}
		$output .= "\nPHP vars:\n";
		$output .= 'memory_limit: ' . esc_html( $info->memory ) . "\n";
		$output .= 'max_execution_time: ' . esc_html( $info->max_exec ) . "\n";
		$output .= 'post_max_size: ' . esc_html( $info->post_max ) . "\n";
		$output .= 'upload_max_filesize: ' . esc_html( $info->upload_max ) . "\n";
		$output .= '</pre>';
		return $output;
	}

	public static function blockedToday(): string {
		return BotBlockerStats::blockedToday();
	}

	public static function blockedTotal(): string {
		return BotBlockerStats::blockedTotal();
	}

	public static function pluginsThemesView(): string {

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
}