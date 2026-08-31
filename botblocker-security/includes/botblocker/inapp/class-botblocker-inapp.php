<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * In-App Browser Mode — rescues legitimate humans opening the site from
 * Instagram/LinkedIn/Twitter/WhatsApp in-app browsers when a deny is a false
 * positive (their UA collides with a crawler signature and the mobile IP has
 * no matching PTR).
 *
 * OPT-IN: bbcs_inapp_enabled = 0 by default. Disabled = the redirect_to_denied()
 * flow is byte-identical to the pre-mode behavior (the seam returns false on
 * the first line).
 *
 * @see roadmap/md/2026-08-14-in-app-browser-mode-design.md
 */
class BotBlockerInApp {

	const RESCUE_GROUPS = array(
		'fake_bot' => 7,
		'hosting'  => 17,
		'language' => 57,
		'referer'  => 58,
	);

	const DEFAULT_SIGNATURES = array(
		'Instagram',
		'FBAN',
		'FBAV',
		'FBDV',
		'LinkedInApp',
		'Twitter for iPhone',
		'WhatsApp/',
	);

	const OPTION_ENABLED      = 'bbcs_inapp_enabled';
	const OPTION_RESCUE_CODES = 'bbcs_inapp_rescue_codes';

	/**
	 * Master toggle (D7). Reads the option live — WP caches get_option in
	 * memory, so no static cache needed (a static cache would require a
	 * reset on every option change and would make tests order-dependent).
	 */
	public static function enabled(): bool {
		return (bool) BotBlockerMultisite::getOption( self::OPTION_ENABLED, 0 );
	}

	/**
	 * Rescue codes configured for this install, intersected with the fixed
	 * rescue groups (D4). Invalid values are dropped, duplicates removed.
	 *
	 * @return int[]
	 */
	public static function getRescueCodes(): array {
		$codes = BotBlockerMultisite::getOption( self::OPTION_RESCUE_CODES, array_values( self::RESCUE_GROUPS ) );
		if ( ! is_array( $codes ) ) {
			$codes = array();
		}
		$valid = array_values( self::RESCUE_GROUPS );
		$codes = array_map( 'intval', $codes );
		$codes = array_values( array_unique( array_intersect( $codes, $valid ) ) );
		return $codes;
	}

	/**
	 * In-app browser detection — UA signatures only (D6), checked ONLY at the
	 * moment a deny is already accepted (D9). A UA spoofer gains nothing beyond
	 * the standard human flow.
	 *
	 * @param object $bbcs BotBlocker instance (or a harness with ->useragent).
	 */
	public static function isInAppBrowser( $bbcs ): bool {
		$signatures = apply_filters( 'bbcs_inapp_browser_signatures', self::DEFAULT_SIGNATURES );
		if ( ! is_array( $signatures ) ) {
			$signatures = self::DEFAULT_SIGNATURES;
		}
		$ua = isset( $bbcs->useragent ) ? (string) $bbcs->useragent : '';
		foreach ( $signatures as $signature ) {
			if ( is_string( $signature ) && $signature !== '' && stripos( $ua, $signature ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The seam (D1): called from redirect_to_denied() right before
	 * show_denied_page(). Returns true when the deny was rescued.
	 *
	 * @param object $bbcs    BotBlocker instance.
	 * @param mixed  $code    Deny reason code.
	 * @param mixed  $message Original deny message (D10 logging).
	 */
	public static function rescue( $bbcs, $code, $message = null ): bool {
		if ( ! self::enabled() ) {
			return false;
		}

		$code_int = is_numeric( $code ) ? (int) $code : 0;
		$log_block = isset( $bbcs->settings->botblocker_log_block ) && (int) $bbcs->settings->botblocker_log_block === 1;

		if ( ! in_array( $code_int, self::getRescueCodes(), true ) ) {
			if ( self::isInAppBrowser( $bbcs ) && $log_block ) {
				BotBlockerStore::storeData( 'In-app UA detected, deny ' . $code_int . ' NOT rescued', $code_int );
			}
			return false;
		}

		if ( ! self::isInAppBrowser( $bbcs ) ) {
			return false;
		}

		if ( $log_block ) {
			$reason = is_string( $message ) ? $message : '';
			BotBlockerStore::storeData( 'In-app browser rescue: ' . $reason, $code_int );
		}

		if ( $bbcs->is_verified() ) {
			$bbcs->should_show_denied_page = false;
			$bbcs->denied_data             = '';
			$bbcs->visitorType             = BotBlockerBase::VISITOR_HUMAN;
			return true;
		}

		$bbcs->should_show_denied_page = false;
		$bbcs->perform_check();
		if ( isset( $bbcs->settings->secure_mode ) && (int) $bbcs->settings->secure_mode === BotBlockerBase::SECURE_MODE_FULL ) {
			$bbcs->process_die();
		}
		return true;
	}
}
