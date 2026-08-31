<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerMarketingBlocks {

	public static function parseChangelogSection( string $raw ): array {
		if ( $raw === '' ) {
			return array();
		}
		$raw = (string) preg_replace( '/^<h4[^>]*>(.*?)<\/h4>\s*$/mi', '= $1 =', $raw );
		$section = $raw;
		if ( preg_match( '/^==\s*Changelog\s*==(.*?)(^==|\z)/ims', $raw, $m ) ) {
			$section = $m[1];
		}
		if ( ! preg_match_all( '/^=\s*([^=\r\n]+?)\s*=\s*$\R?(.*?)(?=^=\s*[^=\r\n]+?\s*=\s*$|\z)/ms', $section, $matches, PREG_SET_ORDER ) ) {
			return array();
		}
		$out = array();
		foreach ( $matches as $entry ) {
			$version = trim( $entry[1] );
			$body    = isset( $entry[2] ) ? trim( $entry[2] ) : '';
			if ( $version === '' ) {
				continue;
			}
			$lines           = array_values(
				array_filter(
					array_map(
						static function ( $l ) {
							return trim( wp_strip_all_tags( $l ) );
						},
						preg_split( '/\r?\n/', $body )
					),
					static function ( $l ) {
						return $l !== '';
					}
				)
			);
			$out[ $version ] = $lines;
		}
		return $out;
	}

	public static function getChangelogForVersion( string $version, int $max_lines = 8, string $raw = '' ): array {
		$version = trim( $version );
		if ( $version === '' ) {
			return array();
		}
		$cache_key = 'bbcs_chlog_' . md5( $version . '|' . $max_lines );
		$cached    = get_site_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}
		if ( $raw === '' ) {
			$file = BOTBLOCKER_DIR . 'readme.md';
			if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
				return array();
			}
			$raw = (string) file_get_contents( $file );
		}
			$all = self::parseChangelogSection( $raw );
			if ( ! isset( $all[ $version ] ) ) {
				return array();
			}
			$lines = array_slice( $all[ $version ], 0, max( 1, $max_lines ) );
			set_site_transient( $cache_key, $lines, DAY_IN_SECONDS );
			return $lines;
	}

	public static function renderInPluginUpdateMessage( array $plugin_data, $response ): void {
		if ( ! is_object( $response ) || empty( $response->new_version ) ) {
			return;
		}
		$version = (string) $response->new_version;
		$lines   = array();

		$sections   = is_object( $response->sections ) ? (array) $response->sections : ( is_array( $response->sections ) ? $response->sections : array() );
		$api_changelog = isset( $sections['changelog'] ) && is_string( $sections['changelog'] ) ? $sections['changelog'] : '';
		if ( $api_changelog !== '' ) {
			$lines = self::getChangelogForVersion( $version, 8, $api_changelog );
		}
		if ( empty( $lines ) ) {
			$lines = self::getChangelogForVersion( $version );
		}

		$html = '</p>';
		/*
		$html .= '<div class="notice notice-warning inline bbcs-update-alert-warning"><p>';
		$html .= '<strong>' . esc_html__( 'Heads up - please back up before upgrade!', 'botblocker-security' ) . '</strong><br>';
		$html .= esc_html__( 'BotBlocker is a security plugin that runs early in the request lifecycle. We highly recommend you back up your site and test the new version on a staging environment first.', 'botblocker-security' );
		$html .= '</p></div>';
		*/
	    if ( ! empty( $lines ) ) {
	            $html .= '<div class="bbcs-update-changelog-box" style="margin:8px 0;padding:8px 12px;border-left:4px solid #2271b1;background:#f0f6fc;">';
	            $html .= '<strong>' . esc_html__( "What's new in", 'botblocker-security' ) . ' ' . esc_html( $version ) . '</strong>';
	            $html .= '<ul style="margin:4px 0 0 16px;list-style:disc;">';
	            foreach ( $lines as $line ) {
	                $html .= '<li>' . esc_html( wp_strip_all_tags( (string) $line ) ) . '</li>';
	            }
	            $html .= '</ul></div>';
	        } else {
	            $upgrade_notice = '';
	            if ( isset( $response->upgrade_notice ) && is_string( $response->upgrade_notice ) ) {
	                $upgrade_notice = trim( wp_strip_all_tags( $response->upgrade_notice ) );
	            }

	            $html .= $upgrade_notice !== ''
	                ? '<p style="margin:4px 0;">' . esc_html( $upgrade_notice ) . '</p>'
	                : '<p style="margin:4px 0;">' . esc_html__( 'Back up your site and review the update before upgrading.', 'botblocker-security' ) . '</p>';
	        }

			echo wp_kses(
				$html,
	            array(
	                'div'    => array( 'class' => true, 'style' => true ),
	                'strong' => array(),
	                'br'     => array(),
	                'ul'     => array(
	                    'class' => true,
	                    'style' => true,
	                ),
	                'li'     => array(),
	                'p'      => array( 'class' => true, 'style' => true ),
	            )
			);
	}

	public static function getWpOrgStats(): ?array {
		$slug      = 'botblocker-security';
		$cache_key = 'bbcs_wp_org_stats_' . $slug;
		$cached    = get_site_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		if ( $cached === 'none' ) {
			return null;
		}
		$url = 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json';
		$res = wp_remote_get( $url, array( 'timeout' => 5 ) );
		if ( is_wp_error( $res ) || (int) wp_remote_retrieve_response_code( $res ) !== 200 ) {
			set_site_transient( $cache_key, 'none', 6 * HOUR_IN_SECONDS );
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $body ) || ! empty( $body['error'] ) ) {
			set_site_transient( $cache_key, 'none', 6 * HOUR_IN_SECONDS );
			return null;
		}
		$stats = array(
			'active_installs' => isset( $body['active_installs'] ) ? (int) $body['active_installs'] : 0,
			'rating'          => isset( $body['rating'] ) ? (float) $body['rating'] : 0.0,
			'num_ratings'     => isset( $body['num_ratings'] ) ? (int) $body['num_ratings'] : 0,
			'downloaded'      => isset( $body['downloaded'] ) ? (int) $body['downloaded'] : 0,
			'last_updated'    => isset( $body['last_updated'] ) ? (string) $body['last_updated'] : '',
			'tested'          => isset( $body['tested'] ) ? (string) $body['tested'] : '',
		);
		set_site_transient( $cache_key, $stats, 12 * HOUR_IN_SECONDS );
		return $stats;
	}
}
