<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerHoneypotTrait {

	/**
	 * Challenge-page honeypot check (spec-challenge-page-hardening):
	 * a CSS-hidden autofill trap plus a JS submit-timing signal. Shared by
	 * the local (AJAX) and POST verification flows. The page echoes the
	 * field name via bbcs_hp_name (validated as f + 32 hex); a
	 * BOTBLOCKER_HONEYPOT_NAME_WINDOW (default 60s) window on the salt+time
	 * name tolerates pages rendered earlier by a non-JS client.
	 * Timing applies only to the POST (captcha-click) flow, measured from
	 * challenge display; it is skipped in silent auto-verify mode and on
	 * the local pre-check.
	 *
	 * @param array $post        Raw $_POST payload.
	 * @param mixed $settings    Settings object with salt + bbcs_captcha_mode.
	 * @param bool  $with_timing Enforce the submit-timing signal (POST flow only).
	 * @return string Reason string when the request must be rejected, '' otherwise.
	 */
	public static function honeypotViolation( array $post, $settings, bool $with_timing = false ): string {
		if ( empty( $settings->bbcs_honeypot_enabled ) ) {
			return '';
		}
		$salt = isset( $settings->salt ) ? (string) $settings->salt : '';
		$now  = time();

		$echoed = isset( $post['bbcs_hp_name'] ) ? sanitize_text_field( wp_unslash( $post['bbcs_hp_name'] ) ) : '';
		if ( $echoed !== '' && preg_match( '/^f[0-9a-f]{32}$/', $echoed ) === 1 ) {
			$hp_value = isset( $post[ $echoed ] ) ? sanitize_text_field( wp_unslash( $post[ $echoed ] ) ) : '';
			if ( $hp_value !== '' ) {
				return 'Honeypot triggered';
			}
		}

		$window = defined( 'BOTBLOCKER_HONEYPOT_NAME_WINDOW' ) ? (int) BOTBLOCKER_HONEYPOT_NAME_WINDOW : 1;
		$hp_value = '';
		for ( $i = -$window; $i <= $window; $i++ ) {
			$name = 'f' . md5( 'honeypot' . $salt . ( $now + $i ) );
			if ( isset( $post[ $name ] ) ) {
				$hp_value = sanitize_text_field( wp_unslash( $post[ $name ] ) );
				break;
			}
		}
		if ( $hp_value !== '' ) {
			return 'Honeypot triggered';
		}

		if ( $with_timing ) {
			$hp_time = isset( $post['bbcs_hp_time'] ) ? (int) $post['bbcs_hp_time'] : -1;
			$mode    = isset( $settings->bbcs_captcha_mode ) ? (int) $settings->bbcs_captcha_mode : 0;
			if ( $hp_time >= 0 && $mode !== BOTBLOCKER_CAPTCHA_MODE_SILENT && $hp_time < 300 ) {
				return 'Honeypot timing triggered';
			}
		}

		return '';
	}
}
