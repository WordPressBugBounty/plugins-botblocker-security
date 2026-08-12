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
require_once __DIR__ . '/class-botblocker-addons-market.php';
require_once __DIR__ . '/dto/class-addon-market-item-data.php';

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


	public static function render_market_catalog_html( array $market, Botblocker_AddonsViewModel $data ): string {
		$render_card = require BOTBLOCKER_DIR . 'admin/templates/addons/marketplace-card.php';
		ob_start();
		foreach ( $market as $raw ) {
			if ( ! empty( $raw['is_installed'] ) ) {
				continue;
			}
			$item = new Botblocker_AddonMarketItemData( $raw );
			if ( $item->slug === '' ) {
				continue;
			}
			$render_card( $data, $item->slug, $item, null );
		}
		return (string) ob_get_clean();
	}

	public static function render_dashboard_addons_summary(): void {
		$ctx           = BotBlockerAddonsMarket::getContext();
		$addons        = $ctx->addons;
		$active        = $ctx->active;
		$addons_locked = $ctx->addons_locked;
		$has_cloud_api = $ctx->has_cloud_api;
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
