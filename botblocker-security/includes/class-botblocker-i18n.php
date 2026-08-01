<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Botblocker_i18n {

	public function load_plugin_textdomain(): void {
		add_filter( 'plugin_locale', array( $this, 'filter_plugin_locale' ), 10, 2 );
		$locale = determine_locale();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WP hook, not a custom hook
		$locale = apply_filters( 'plugin_locale', $locale, 'botblocker-security' );
		$mofile = WP_LANG_DIR . '/plugins/botblocker-security-' . $locale . '.mo';
		if ( ! file_exists( $mofile ) ) {
			$mofile = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo';
		}
		load_textdomain( 'botblocker-security', $mofile );
	}

	public function filter_plugin_locale( string $locale, string $domain ): string {
		if ( $domain === 'botblocker-security' ) {
			$preferred = $this->get_preferred_language();
			if ( ! empty( $preferred ) ) {
				return $preferred;
			}
		}
		return $locale;
	}

	private function get_preferred_language(): string {
		if ( isset( $_COOKIE['bbcs_preferred_language'] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE['bbcs_preferred_language'] ) );
		}
		return get_locale();
	}
}
