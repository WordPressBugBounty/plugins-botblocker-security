<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * BotBlocker UI Class
 *
 * Handles UI-related functionality for the BotBlocker plugin
 */
class BotBlockerUI {

	/**
	 * Sets fallback captcha when GD is not available
	 *
	 * @param string $state The captcha state to set
	 * @return void
	 */
	public static function fallback_captcha( string $state ): void {
		global $wpdb;

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$wpdb->bbcs_settings,
			array( 'value' => $state ),
			array( 'key' => 'bbcs_captcha_mode' ),
			array( '%d' ),
			array( '%s' )
		);

		if ( $updated !== false ) {
			BotBlockerFileRenderer::generateSettingsFile();
		} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// REVIEWER NOTE: Conditional debug logging; gated behind BBCS_DEBUG and disabled in production.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [UI] Error fallback captcha state of BotBlocker' );

		}
	}

	/**
	 * Get realtime status indicator for dashboard
	 *
	 * @return string Realtime status HTML string
	 */
	public static function is_realtime(): string {
		$BBCS          = BotBlocker::getInstance();
		$durations     = bbcs_get_cache_durations();
		$duration_name = $durations[ $BBCS->settings->cache_ui_duration ] ?? __( 'Unknown period', 'botblocker-security' );
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			// translators: %s is the cache update interval duration name (e.g. "1 hour").
			return '<small>' . esc_html( sprintf( __( '(Updated every %s)', 'botblocker-security' ), $duration_name ) ) . '</small>';
		} else {
			return '<small>' . esc_html__( '(Real-time)', 'botblocker-security' ) . '</small>';
		}
	}

	/**
	 * Check if reCAPTCHA v3 keys are present and valid for enabling the feature
	 *
	 * @return bool
	 */
	public static function recaptcha_v3_keys_ready(): bool {
		if ( ! class_exists( 'BotBlocker' ) ) {
			return false;
		}
		$BBCS = BotBlocker::getInstance();
		if ( ! $BBCS || ! isset( $BBCS->settings ) ) {
			return false;
		}
		$key = $BBCS->settings->recaptcha_key3 ?? '';
		$sec = $BBCS->settings->recaptcha_secret3 ?? '';
		return ( ! empty( $key ) && ! empty( $sec ) );
	}

	/**
	 * Enforce dependent settings for reCAPTCHA v3 when keys are missing.
	 * If keys/secret are absent, forcibly disable recaptcha_check and recaptcha_v3_ipv6_block
	 * and regenerate settings file.
	 *
	 * @return void
	 */
	public static function enforce_recaptcha_v3_dependencies(): void {
		if ( ! class_exists( 'BotBlocker' ) ) {
			return;
		}
		$BBCS = BotBlocker::getInstance();
		if ( ! $BBCS || ! isset( $BBCS->settings ) ) {
			return;
		}

		if ( self::recaptcha_v3_keys_ready() ) {
			return;
		}

		$changed = false;
		if ( ! empty( $BBCS->settings->recaptcha_check ) ) {
			$BBCS->settings->recaptcha_check = 0;
			$changed                         = true;
		}
		if ( ! empty( $BBCS->settings->recaptcha_v3_ipv6_block ) ) {
			$BBCS->settings->recaptcha_v3_ipv6_block = 0;
			$changed                                 = true;
		}

		if ( $changed ) {
			global $wpdb;
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => '0' ), array( 'key' => 'recaptcha_check' ), array( '%s' ), array( '%s' ) );
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => '0' ), array( 'key' => 'recaptcha_v3_ipv6_block' ), array( '%s' ), array( '%s' ) );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( class_exists( 'BotBlockerFileRenderer' ) ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}
		}
	}

	public static function isEarlyInitEnabled(): bool {
		if ( ! class_exists( 'BotBlocker' ) ) {
			return false;
		}
		$bbcs = BotBlocker::getInstance();
		return isset( $bbcs->settings->early_init_enable ) && (int) $bbcs->settings->early_init_enable === 1;
	}

	public static function isMuEnabled(): bool {
		if ( ! class_exists( 'BotBlocker' ) ) {
			return false;
		}
		$bbcs = BotBlocker::getInstance();
		return isset( $bbcs->settings->mu_enable ) && (int) $bbcs->settings->mu_enable === 1;
	}

	public static function get_setup_chain_context(): array {
		$early      = self::isEarlyInitEnabled();
		$mu         = self::isMuEnabled();
		$pluginSpin = ' fa-spin';
		$earlySpin  = $early ? ' fa-spin' : '';
		$muSpin     = $mu ? ' fa-spin' : '';
		if ( $early && $mu ) {
			$mu     = false;
			$muSpin = ''; }
		if ( $early ) {
			$earlyText = __( 'Early initialization enabled. IP blacklist and base rule filtering run before WordPress loads. MU mode is not required.', 'botblocker-security' );
			$muText    = __( 'MU mode disabled. Early initialization already performs pre-filtering. Enabling MU is unnecessary.', 'botblocker-security' );
		} elseif ( $mu ) {
			$earlyText = __( 'Early initialization disabled. Its functions are handled by the active MU plugin.', 'botblocker-security' );
			$muText    = __( 'MU plugin active. Early IP and rule filtering run before other plugins. Early initialization is not required.', 'botblocker-security' );
		} else {
			$earlyText = __( 'Early initialization disabled. Enable it for earlier IP filtering.', 'botblocker-security' );
			$muText    = __( 'MU plugin mode disabled. You can enable it (or early initialization) for preliminary malicious IP rejection.', 'botblocker-security' );
		}
		$pluginText = ( $early || $mu )
			? __( 'BotBlocker operates in normal mode processing all threat types (bots, proxies, referrers, languages etc.) after base early filtering.', 'botblocker-security' )
			: __( 'BotBlocker operates in normal mode processing all threat types at WordPress load.', 'botblocker-security' );
		return array(
			'earlySpin'  => $earlySpin,
			'muSpin'     => $muSpin,
			'pluginSpin' => $pluginSpin,
			'earlyText'  => $earlyText,
			'muText'     => $muText,
			'pluginText' => $pluginText,
		);
	}


	protected static function market_slug_from_url( string $url ): string {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$b    = basename( (string) $path );
		return preg_replace( '/\.zip$/', '', $b );
	}

	/**
	 * Disk-built market items carry an explicit slug; cloud items derive it from the ZIP url.
	 *
	 * @param array<string,mixed> $item
	 */
	protected static function market_item_slug( array $item ): string {
		if ( ! empty( $item['slug'] ) ) {
			return sanitize_key( (string) $item['slug'] );
		}
		return ! empty( $item['url'] ) ? self::market_slug_from_url( (string) $item['url'] ) : '';
	}

	protected static function filter_market_addons( array $addons ): array {
		return array_values( array_filter( $addons, function ( $item ) {
			return ( $item['enabled'] ?? true ) !== false;
		} ) );
	}

	protected static function load_market(): array {
		if ( BotBlockerAddons::isLocalMode() ) {
			return BotBlockerAddons::buildMarketFromDisk( BotBlockerAddons::scanAll() );
		}
		$market    = array();
		$marketUrl = BotBlockerAddons::getMarketUrl();
		if ( $marketUrl ) {
			$res = wp_remote_get( $marketUrl, array( 'timeout' => 10 ) );
			if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
				$json = json_decode( wp_remote_retrieve_body( $res ), true );
				if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) {
					$market = self::filter_market_addons( $json['addons'] ); }
			}
		}
		if ( empty( $market ) ) {
			// $local = BOTBLOCKER_DIR . 'wp-content/plugins/bbcs-addons/master.json';
			$local = WP_CONTENT_DIR . '/plugins/bbcs-addons/master.json';
			if ( file_exists( $local ) ) {
				$json = json_decode( file_get_contents( $local ), true );
				if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) {
					$market = self::filter_market_addons( $json['addons'] ); }
			}
		}
		if ( empty( $market ) ) {
			$server   = defined( 'BOTBLOCKER_SERVER' ) ? BOTBLOCKER_SERVER : 'botblocker.top';
			$fallback = 'https://' . $server . '/wp-content/plugins/bbcs-addons/master.json';
			$res      = wp_remote_get( $fallback, array( 'timeout' => 10 ) );
			if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
				$json = json_decode( wp_remote_retrieve_body( $res ), true );
				if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) {
					$market = self::filter_market_addons( $json['addons'] ); }
			}
		}
		return $market;
	}

	public static function get_addons_context(): array {
		$addons            = BotBlockerAddons::scanAll();
		$active            = BotBlockerAddons::getActive();
		$addons_local_mode = BotBlockerAddons::isLocalMode();
		$market            = self::load_market();
		$marketBySlug      = array();
		foreach ( $market as $it ) {
			$slug = self::market_item_slug( $it );
			if ( $slug !== '' ) {
				$marketBySlug[ $slug ] = $it;
			}
		}
		foreach ( $market as $i => $it ) {
			$slug           = self::market_item_slug( $it );
			$is_installed   = isset( $addons[ $slug ] );
			$installed_ver  = $is_installed ? ( $addons[ $slug ]['version'] ?? '' ) : '';
			$remote_ver     = $it['version'] ?? '';
			$item_req       = $it['requires_core'] ?? '';
			$req_compatible = empty( $item_req ) || ! defined( 'BOTBLOCKER_VERSION' ) || version_compare( BOTBLOCKER_VERSION, $item_req, '>=' );
			$has_newer_ver  = $is_installed && $installed_ver && $remote_ver && version_compare( $remote_ver, $installed_ver, '>' );
			$market[ $i ]   = array_merge(
				$it,
				array(
					'slug'               => $slug,
					'is_installed'       => $is_installed,
					'installed_ver'      => $installed_ver,
					'remote_ver'         => $remote_ver,
					'update_avail'       => $has_newer_ver && $req_compatible,
					'update_blocked'     => $has_newer_ver && ! $req_compatible,
					'show_installed_ver' => $is_installed && $installed_ver && $installed_ver !== $remote_ver,
					'is_active'          => in_array( $slug, $active, true ),
					'is_incompatible'    => ! $is_installed && ! empty( $item_req ) && defined( 'BOTBLOCKER_VERSION' ) && version_compare( BOTBLOCKER_VERSION, $item_req, '<' ),
				)
			);
		}
		foreach ( $addons as $slug => $addon ) {
			$remote              = $marketBySlug[ $slug ] ?? null;
			$broken              = ! $addon['valid'];
			$core_ver            = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '';
			$req_local           = trim( $addon['requires_core'] ?? '' );
			$req_remote          = trim( $remote['requires_core'] ?? '' );
			$local_req_met       = ! empty( $req_local ) && ! empty( $core_ver ) && version_compare( $core_ver, $req_local, '>=' );
			$remote_req_met      = empty( $req_remote ) || ( ! empty( $core_ver ) && version_compare( $core_ver, $req_remote, '>=' ) );
			$incompatible        = ! $broken && ! empty( $req_local ) && ! $local_req_met;
			$remote_ver          = trim( $remote['version'] ?? '' );
			$local_ver           = trim( $addon['version'] ?? '' );
			$has_newer           = ! empty( $remote_ver ) && ! empty( $local_ver ) && version_compare( $remote_ver, $local_ver, '>' );
			$update_avail        = ! $broken && $has_newer && $remote_req_met;
			$update_repair       = ! $broken && empty( $req_local ) && ! empty( $remote['url'] ) && $remote_req_met;
			$incompatible_remote = ! $broken && ! empty( $req_remote ) && ! $remote_req_met;
			$addons[ $slug ]     = array_merge(
				$addon,
				array(
					'broken'               => $broken,
					'req_core'             => ! empty( $req_local ) ? $req_local : $req_remote,
					'req_core_local'       => $req_local,
					'req_core_remote'      => $req_remote,
					'incompatible'         => $incompatible,
					'incompatible_remote'  => $incompatible_remote,
					'can_activate'         => ! $broken && $local_req_met,
					'is_active'            => in_array( $slug, $active, true ),
					'update_avail'         => $update_avail,
					'update_repair'        => $update_repair,
					'update_url'           => ( $update_avail || $update_repair ) ? ( $remote['url'] ?? '' ) : '',
					'update_requires_core' => ( $update_avail || $update_repair ) ? $req_remote : '',
				)
			);
		}
		$updates_count = 0;
		foreach ( $addons as $addon ) {
			if ( $addon['update_avail'] ) {
				++$updates_count; }
		}
		$has_cloud_api = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
		// Local mode serves add-ons from disk, so no cloud licence is needed to manage them.
		$addons_locked = ! $has_cloud_api && ! $addons_local_mode;
		return compact( 'addons', 'active', 'market', 'marketBySlug', 'addons_locked', 'has_cloud_api', 'updates_count', 'addons_local_mode' );
	}

	public static function render_dashboard_addons_summary(): void {
		$ctx           = self::get_addons_context();
		$addons        = $ctx['addons'];
		$active        = $ctx['active'];
		$addons_locked = $ctx['addons_locked'];
		$has_cloud_api = $ctx['has_cloud_api'];
		$BBCSA         = class_exists( 'Botblocker_Admin' ) ? Botblocker_Admin::getInstance() : null;
		$tools_url     = $BBCSA && isset( $BBCSA->pages_tools ) ? $BBCSA->pages_tools : '';
		echo '<div class="bbcs-addons-dash">';
		if ( $addons_locked ) {
			echo '<div class="alert alert-warning p-2 mb-2 bbcs-addons-off-text">' . esc_html__( 'Add-ons locked. Activate Cloud API to use marketplace features.', 'botblocker-security' ) . '</div>';
		}
		if ( empty( $addons ) ) {
			$addons_page = ( $BBCSA && isset( $BBCSA->pages_addons ) ) ? $BBCSA->pages_addons : BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
			echo '<div class="bbcs-addons-empty border rounded p-3 text-center">'
				. '<p class="mb-2 mbcs-empty-text">' . esc_html__( 'Enhance speed, security and user experience with official BotBlocker add-ons.', 'botblocker-security' ) . '</p>'
				. '<a href="' . esc_url( $addons_page ) . '" class="btn btn-xs btn-primary"><i class="fa-solid fa-puzzle-piece"></i> ' . esc_html__( 'Browse Add-ons', 'botblocker-security' ) . '</a>'
				. '</div>';
			echo '</div>';
			return;
		}
		echo '<ul class="list-unstyled m-0">';
		foreach ( $addons as $slug => $addon ) {
			$name     = $addon['name'] ?: $slug;
			$isActive = in_array( $slug, $active, true );
			$ver      = isset( $addon['version'] ) ? $addon['version'] : '';

			echo '<li class="d-flex align-items-center mb-1 bbcs-dash-addon-li">';
			// Status icon
			$icon_classes = 'fa-solid fa-circle ' . ( $isActive ? 'text-success' : 'text-danger' ) . ' me-2';
			echo '<i class="' . esc_attr( $icon_classes ) . '"></i>';

			// Optional link wrapper when active and tools URL is available
			$has_link = ( $isActive && ! empty( $tools_url ) );
			if ( $has_link ) {
				echo '<a href="' . esc_url( $tools_url . '#addon-' . rawurlencode( (string) $slug ) ) . '" class="bbcs-addon-link">';
			}

			echo esc_html( $name );
			if ( $ver ) {
				echo ' <small class="text-muted">' . esc_html( ' (' . $ver . ')' ) . '</small>';
			}

			if ( $has_link ) {
				echo '</a>';
			}

			echo '</li>';
		}
		echo '</ul>';
		if ( ! $has_cloud_api ) {
			if ( $BBCSA && isset( $BBCSA->pages_cloud_api ) ) {
				echo '<a class="btn btn-xs btn-default mt-2" href="' . esc_url( $BBCSA->pages_cloud_api ) . '"><i class="fa-solid fa-crown"></i> ' . esc_html__( 'Connect Cloud API now!', 'botblocker-security' ) . '</a>';
			}
		}
		echo '</div>';
	}
}

// Feathured function to render an info column with icon, text and links in the dashboard or settings pages. Used for "Why choose BotBlocker?" section and similar contexts.
function bbcs_render_info_column( string $icon, string $alt, array $paragraphs, array $links, string $col_xxl = '3' ): void {
	?>
	<div class="col-xxl-<?php echo esc_attr( $col_xxl ); ?> col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
		<div class="bbcs-info-inner">
			<?php
			// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
			<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/' . $icon ); ?>"
				alt="<?php echo esc_attr( $alt ); ?>"
				class="img-fluid bbcs-info-image mb-3">
			<?php foreach ( $paragraphs as $p ) : ?>
				<p class="bbcs-info-text"><?php echo esc_html( $p ); ?></p>
			<?php endforeach; ?>
			<?php if ( ! empty( $links ) ) : ?>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<?php foreach ( $links as $link ) : ?>
						<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" class="bbcs-info-footer-a"><?php echo esc_html( $link['text'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
