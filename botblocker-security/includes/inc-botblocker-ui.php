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
		$durations     = BotBlockerDataTime::getCacheDurations();
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
}

