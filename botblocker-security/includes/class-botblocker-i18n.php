<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Botblocker_i18n {

	public function load_plugin_textdomain(): void {
		add_filter( 'plugin_locale', array( $this, 'filter_plugin_locale' ), 10, 2 );
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- backward compatibility with WP <4.6
		load_plugin_textdomain(
			'botblocker-security',
			false,
			dirname( BOTBLOCKER_BASENAME ) . '/languages'
		);
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
