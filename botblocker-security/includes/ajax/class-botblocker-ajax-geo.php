<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxGeo {

	private static function validCode( string $code ): bool {
		return preg_match( '/^[A-Z]{2}$/', strtoupper( trim( $code ) ) ) === 1;
	}

	public static function handleGetCountries(): void {
		$bbcs_action = 'geo_get_countries';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		global $wpdb;


		$start  = isset( $_POST['start'] ) ? absint( wp_unslash( $_POST['start'] ) ) : 0;
		$length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
		$draw   = isset( $_POST['draw'] ) ? absint( wp_unslash( $_POST['draw'] ) ) : 0;
		$search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$records_total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_countries}` WHERE 1 = %d", 1 )
		);

		if ( $search !== '' ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$records_filtered = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_countries}`
	                 WHERE code LIKE %s
	                    OR `rule` LIKE %s
	                    OR comment LIKE %s",
					$like,
					$like,
					$like
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, priority, code, `rule`, comment, disable
	                 FROM `{$wpdb->bbcs_countries}`
	                 WHERE code LIKE %s
	                    OR `rule` LIKE %s
	                    OR comment LIKE %s
	                 ORDER BY priority ASC, code ASC
	                 LIMIT %d, %d",
					$like,
					$like,
					$like,
					$start,
					$length
				),
				ARRAY_A
			);
		} else {
			$records_filtered = $records_total;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, priority, code, `rule`, comment, disable
	                 FROM `{$wpdb->bbcs_countries}`
	                 WHERE 1 = %d
	                 ORDER BY priority ASC, code ASC
	                 LIMIT %d, %d",
					1,
					$start,
					$length
				),
				ARRAY_A
			);
		}

		$data = array();
		foreach ( (array) $results as $row ) {
			$data[] = array(
				'id'       => (int) $row['id'],
				'priority' => (int) $row['priority'],
				'code'     => $row['code'],
				'name'     => function_exists( 'bbcs_get_country_by_code' ) ? bbcs_get_country_by_code( $row['code'] ) : $row['code'],
				'rule'     => $row['rule'],
				'comment'  => $row['comment'],
				'disable'  => (int) $row['disable'],
			);
		}

		wp_send_json(
			array(
				'draw'            => $draw,
				'recordsTotal'    => $records_total,
				'recordsFiltered' => $records_filtered,
				'data'            => $data,
			)
		);
	}

	public static function handleToggleCountry(): void {
		$bbcs_action = 'geo_toggle_country';
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( $id <= 0 ) {
			wp_send_json_error( __( 'Invalid country ID.', 'botblocker-security' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$current = $wpdb->get_var(
			$wpdb->prepare( "SELECT `disable` FROM `{$wpdb->bbcs_countries}` WHERE id = %d", $id )
		);
		if ( $current === null || $current === false ) {
			wp_send_json_error( __( 'Country rule not found.', 'botblocker-security' ) );
		}
		$current = (int) $current;

		$new_value = $current ? 0 : 1;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$wpdb->bbcs_countries,
			array( 'disable' => $new_value ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
		if ( $result === false ) {
			wp_send_json_error( __( 'Failed to toggle country rule.', 'botblocker-security' ) );
		}

		BotBlockerFileRenderer::renderCountries();
		BotBlockerCache::clearFileCache();

		wp_send_json_success( array( 'disable' => $new_value ) );
	}

	public static function handleCreateCountry(): void {
		$bbcs_action = 'geo_create_country';
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		$code = isset( $_POST['code'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) ) : '';
		if ( ! self::validCode( $code ) ) {
			wp_send_json_error( __( 'Invalid country code.', 'botblocker-security' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM `{$wpdb->bbcs_countries}` WHERE code = %s", $code )
		);
		if ( $exists ) {
			wp_send_json_error( __( 'Country already added.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$wpdb->bbcs_countries,
			array(
				'priority' => 50,
				'code'     => $code,
				'rule'     => 'block',
				'comment'  => '',
				'disable'  => 0,
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);
		if ( $result === false ) {
			wp_send_json_error( __( 'Failed to add country.', 'botblocker-security' ) );
		}

		BotBlockerFileRenderer::renderCountries();
		BotBlockerCache::clearFileCache();

		wp_send_json_success(
			array(
				'id'       => (int) $wpdb->insert_id,
				'priority' => 50,
				'code'     => $code,
				'name'     => function_exists( 'bbcs_get_country_by_code' ) ? bbcs_get_country_by_code( $code ) : $code,
				'rule'     => 'block',
				'comment'  => '',
				'disable'  => 0,
			)
		);
	}

	public static function handleDeleteCountry(): void {
		$bbcs_action = 'geo_delete_country';
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( $id <= 0 ) {
			wp_send_json_error( __( 'Invalid country ID.', 'botblocker-security' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $wpdb->bbcs_countries, array( 'id' => $id ), array( '%d' ) );
		if ( $result === false ) {
			wp_send_json_error( __( 'Failed to delete country rule.', 'botblocker-security' ) );
		}

		BotBlockerFileRenderer::renderCountries();
		BotBlockerCache::clearFileCache();

		wp_send_json_success();
	}

	public static function handleClearAll(): void {
		$bbcs_action = 'geo_clear_all';
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( "TRUNCATE TABLE `{$wpdb->bbcs_countries}`" );
		if ( $result === false ) {
			wp_send_json_error( __( 'Failed to clear countries.', 'botblocker-security' ) );
		}

		BotBlockerFileRenderer::renderCountries();
		BotBlockerCache::clearFileCache();

		wp_send_json_success();
	}

	/**
	 * Legacy option-based actions kept for compatibility with the deprecated GEO UI.
	 */
	public static function handleGetCountriesLegacy(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
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

		wp_send_json_success( $blockedCountries );
	}

	public static function handleSaveCountriesLegacy(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
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

		BotBlockerMultisite::updateOption( 'bbcs_blocked_countries', $sanitized );

		wp_send_json_success( $sanitized );
	}
}
