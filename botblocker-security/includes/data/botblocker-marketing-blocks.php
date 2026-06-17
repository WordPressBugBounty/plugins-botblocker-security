<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_parse_changelog_section' ) ) {
	function bbcs_parse_changelog_section( string $raw ): array {
		if ( $raw === '' ) {
			return array();
		}
		if ( ! preg_match( '/^==\s*Changelog\s*==(.*?)(^==|\z)/ims', $raw, $m ) ) {
			return array();
		}
		$section = $m[1];
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
					array_map( 'trim', preg_split( '/\r?\n/', $body ) ),
					static function ( $l ) {
						return $l !== '';
					}
				)
			);
			$out[ $version ] = $lines;
		}
		return $out;
	}
}

if ( ! function_exists( 'bbcs_get_changelog_for_version' ) ) {
	function bbcs_get_changelog_for_version( string $version, int $max_lines = 8 ): array {
		$version = trim( $version );
		if ( $version === '' ) {
			return array();
		}
		$cache_key = 'bbcs_chlog_' . md5( $version . '|' . $max_lines );
		$cached    = get_site_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$file = BOTBLOCKER_DIR . 'readme.md';
		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return array();
		}
		$raw = (string) file_get_contents( $file );
		$all = bbcs_parse_changelog_section( $raw );
		if ( ! isset( $all[ $version ] ) ) {
			return array();
		}
		$lines = array_slice( $all[ $version ], 0, max( 1, $max_lines ) );
		set_site_transient( $cache_key, $lines, DAY_IN_SECONDS );
		return $lines;
	}
}

if ( ! function_exists( 'bbcs_render_in_plugin_update_message' ) ) {
	function bbcs_render_in_plugin_update_message( array $plugin_data, $response ): void {
		if ( ! is_object( $response ) || empty( $response->new_version ) ) {
			return;
		}
		$version = (string) $response->new_version;
		$lines   = bbcs_get_changelog_for_version( $version );

		$html = '</p>';
		/*
		$html .= '<div class="notice notice-warning inline bbcs-update-alert-warning"><p>';
		$html .= '<strong>' . esc_html__( 'Heads up - please back up before upgrade!', 'botblocker-security' ) . '</strong><br>';
		$html .= esc_html__( 'BotBlocker is a security plugin that runs early in the request lifecycle. We highly recommend you back up your site and test the new version on a staging environment first.', 'botblocker-security' );
		$html .= '</p></div>';
		*/
		if ( ! empty( $lines ) ) {
			$html .= '<div class="notice notice-info inline bbcs-update-alert-info"><p>';
			$html .= '<strong>' . esc_html__( "What's new in", 'botblocker-security' ) . ' ' . esc_html( $version ) . '</strong>';
			$html .= '</p><ul class="bbcs-update-changelog" style="margin:4px 0 0 20px;list-style:disc;">';
			foreach ( $lines as $line ) {
				$html .= '<li>' . esc_html( $line ) . '</li>';
			}
			$html .= '</ul></div>';
		} else {
			$upgrade_notice = '';
			if ( isset( $response->upgrade_notice ) && is_string( $response->upgrade_notice ) ) {
				$upgrade_notice = trim( wp_strip_all_tags( $response->upgrade_notice ) );
			}

			$html .= '<div class="notice notice-warning inline bbcs-update-alert-warning"><p>';
			$html .= '<strong>' . esc_html__( 'New BotBlocker version available', 'botblocker-security' ) . ': ' . esc_html( $version ) . '</strong><br>';
			$html .= $upgrade_notice !== ''
				? esc_html( $upgrade_notice )
				: esc_html__( 'BotBlocker runs early in the WordPress request lifecycle. Back up your site and review the update before upgrading.', 'botblocker-security' );
			$html .= '</p></div>';
		}

		$html .= '<p class="bbcs-update-spacer">';

		echo wp_kses(
			$html,
			array(
				'div'    => array( 'class' => true ),
				'strong' => array(),
				'br'     => array(),
				'ul'     => array(
					'class' => true,
					'style' => true,
				),
				'li'     => array(),
				'p'      => array( 'class' => true ),
			)
		);
	}
}

if ( ! function_exists( 'bbcs_get_wp_org_stats' ) ) {
	function bbcs_get_wp_org_stats(): ?array {
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

if ( ! function_exists( 'bbcs_render_social_proof_card' ) ) {
	function bbcs_render_social_proof_card(): void {
		$stats = bbcs_get_wp_org_stats();
		if ( ! $stats || ( $stats['active_installs'] < 10 && $stats['num_ratings'] < 1 ) ) {
			return;
		}
		$rating_5       = ( $stats['rating'] / 100 ) * 5;
		$full           = (int) floor( $rating_5 );
		$half           = ( $rating_5 - $full ) >= 0.25 && ( $rating_5 - $full ) < 0.75;
		$installs       = $stats['active_installs'];
		$installs_label = $installs >= 1000
			? sprintf( /* translators: %s is the number of active installs */ __( '%s+ active installs', 'botblocker-security' ), number_format_i18n( $installs ) )
			: sprintf( /* translators: %s is the number of active installs */ __( '%s active installs', 'botblocker-security' ), number_format_i18n( $installs ) );
		?>
		<section class="card bbcs-card-border-left">
			<header class="card-header bbcs_small_header">
				<h2 class="card-title"><?php esc_html_e( 'Trusted by users', 'botblocker-security' ); ?></h2>
			</header>
			<div class="card-body">
				<?php if ( $stats['num_ratings'] > 0 ) : ?>
					<div class="bbcs-social-proof-rating">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<?php if ( $i <= $full ) : ?>
								<i class="fa-solid fa-star bbcs-cloud-api-color"></i>
							<?php elseif ( $i === $full + 1 && $half ) : ?>
								<i class="fa-solid fa-star-half-stroke bbcs-cloud-api-color"></i>
							<?php else : ?>
								<i class="fa-regular fa-star bbcs-text-muted"></i>
							<?php endif; ?>
						<?php endfor; ?>
						<span class="ms-1"><b><?php echo esc_html( number_format_i18n( $rating_5, 1 ) ); ?></b>
							<small class="bbcs-text-muted">
								<?php
								echo esc_html(
									sprintf(
									/* translators: %s is the number of ratings */
										_n( '(%s rating)', '(%s ratings)', $stats['num_ratings'], 'botblocker-security' ),
										number_format_i18n( $stats['num_ratings'] )
									)
								);
								?>
							</small>
						</span>
					</div>
				<?php endif; ?>
				<?php if ( $installs > 0 ) : ?>
					<div class="mt-2">
						<i class="fa-solid fa-shield-halved bbcs_color_green me-1"></i>
						<small><?php echo esc_html( $installs_label ); ?></small>
					</div>
				<?php endif; ?>
				<a href="https://wordpress.org/plugins/botblocker-security/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a mt-2 d-inline-block">
					<?php esc_html_e( 'View on WordPress.org', 'botblocker-security' ); ?>
				</a>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'bbcs_render_header_narrative' ) ) {
	function bbcs_render_header_narrative(): void {
		$today     = (int) do_shortcode( '[bbcs_blocked_today]' );
		$total     = (int) do_shortcode( '[bbcs_blocked_total]' );
		$today_str = number_format_i18n( $today );
		$total_str = number_format_i18n( $total );
		?>
		<div class="bbcs-header-narrative d-none d-md-flex">
			<i class="fa-solid fa-shield-halved bbcs_color_green me-2"></i>
			<span class="bbcs-header-narrative-text">
				<?php
				if ( $today > 0 ) {
					echo wp_kses_post(
						sprintf(
						/* translators: %1$s is the number of blocked requests today, %2$s is the total number of blocked requests */
							_n(
								'Blocked today: <b>%1$s</b> &middot; Total: <b>%2$s</b>',
								'Blocked today: <b>%1$s</b> &middot; Total: <b>%2$s</b>',
								$today,
								'botblocker-security'
							),
							$today_str,
							$total_str
						)
					);
				} elseif ( $total > 0 ) {
					echo wp_kses_post(
						sprintf(
						/* translators: %s is the total number of blocked requests */
							__( 'Blocked today: <b>0</b> &middot; Total: <b>%s</b>', 'botblocker-security' ),
							$total_str
						)
					);
				} else {
					esc_html_e( 'BotBlocker is active &middot; awaiting traffic', 'botblocker-security' );
				}
				?>
			</span>
		</div>
		<?php
	}
}
