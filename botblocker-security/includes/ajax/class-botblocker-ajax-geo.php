<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxGeo {

	public static function handleGetCountries(): void {
		$bbcs_action = 'geo_get_countries';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce check passed' );
		}
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check FAILED' );
			}
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$blockedCountries = BotBlockerMultisite::getOption( 'bbcs_blocked_countries', array() );
		if ( is_string( $blockedCountries ) ) {
			$decoded = json_decode( $blockedCountries, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$blockedCountries = $decoded;
			} else {
				$blockedCountries = array_filter( array_map( 'trim', explode( ',', $blockedCountries ) ) );
			}
		}

		if ( ! is_array( $blockedCountries ) ) {
			$blockedCountries = array();
		}

		$blockedCountries = array_values(
			array_unique(
				array_filter(
					array_map(
						function ( $item ) {
							$country = is_string( $item ) ? strtoupper( trim( $item ) ) : '';
							return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : '';
						},
						$blockedCountries
					)
				)
			)
		);

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' count=' . count( $blockedCountries ) . ' sending response' );
		}
		wp_send_json_success( $blockedCountries );
	}

	public static function handleSaveCountries(): void {
		$bbcs_action = 'geo_save_countries';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce check passed' );
		}
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check FAILED' );
			}
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$countries = array();
		if ( isset( $_POST['countries'] ) ) {
			$countriesRaw = sanitize_text_field( wp_unslash( $_POST['countries'] ) );
			$decoded      = json_decode( $countriesRaw, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$countries = $decoded;
			} else {
				$countries = array_filter( array_map( 'trim', explode( ',', $countriesRaw ) ) );
			}
		}

		if ( ! is_array( $countries ) ) {
			$countries = array();
		}

		$sanitized = array_values(
			array_unique(
				array_filter(
					array_map(
						function ( $item ) {
							$country = is_string( $item ) ? strtoupper( trim( $item ) ) : '';
							return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : '';
						},
						$countries
					)
				)
			)
		);

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' sanitized_count=' . count( $sanitized ) . ' before updateOption' );
		}
		BotBlockerMultisite::updateOption( 'bbcs_blocked_countries', $sanitized );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' success' );
		}
		wp_send_json_success( $sanitized );
	}
}
