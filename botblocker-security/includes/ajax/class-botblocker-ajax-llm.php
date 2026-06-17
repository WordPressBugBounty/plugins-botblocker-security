<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BotBlockerAjaxLlm {

	/**
	 * Get LLM providers list for DataTables (one row per provider, aggregated).
	 */
	public static function handleGetProviders(): void {
		$bbcs_action = 'llm_get_providers';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;

		$start  = isset( $_POST['start'] ) ? absint( wp_unslash( $_POST['start'] ) ) : 0;
		$length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 25;
		$draw   = isset( $_POST['draw'] ) ? absint( wp_unslash( $_POST['draw'] ) ) : 0;
	    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$search = isset( $_POST['search']['value'] ) ? sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) : '';

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$records_total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(DISTINCT provider) FROM `{$wpdb->bbcs_llm_trusted}` WHERE 1=%d", 1 )
		);
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' records_total=' . $records_total );
		}

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$records_filtered = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT provider) FROM `{$wpdb->bbcs_llm_trusted}` WHERE (provider_label LIKE %s OR `search` LIKE %s)",
					$like,
					$like
				)
			);
		} else {
			$records_filtered = $records_total;
		}

		$status = BotBlockerLlmSync::getStatus();

		$rows = self::getProviderRows( $start, $length, $search );
		foreach ( $rows as &$row ) {
			$row['last_sync'] = ! empty( $status['last_sync'] ) ? date_i18n( 'Y-m-d H:i', (int) $status['last_sync'] ) : '';
		}
		unset( $row );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' sending ' . count( $rows ) . ' rows' );
		}

		wp_send_json(
			array(
				'draw'            => $draw,
				'recordsTotal'    => $records_total,
				'recordsFiltered' => $records_filtered,
				'data'            => $rows,
				'sync_status'     => $status,
			)
		);
	}

	/**
	 * Fetch LLM provider rows (one row per provider), with optional LIMIT/OFFSET and search filter.
	 */
	private static function getProviderRows( int $offset = 0, int $length = -1, string $search = '' ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$limit_clause = $length > 0
			? $wpdb->prepare( ' LIMIT %d, %d', $offset, $length )
			: '';
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT provider, provider_label, `search`, verified_ip_ranges, disabled
	                 FROM `{$wpdb->bbcs_llm_trusted}`
	                 WHERE (provider_label LIKE %s OR `search` LIKE %s)
	                 ORDER BY provider_label ASC
	                 {$limit_clause}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
					$like,
					$like
				),
				ARRAY_A
			);
		} else {
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT provider, provider_label, `search`, verified_ip_ranges, disabled
	             FROM `{$wpdb->bbcs_llm_trusted}`
	             WHERE 1 = %d
	             ORDER BY provider_label ASC
	             {$limit_clause}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
					1
				),
				ARRAY_A
			);
		}

		$data = array();
		foreach ( (array) $rows as $row ) {
			$ranges    = array_filter( preg_split( '/\s+/', trim( (string) $row['verified_ip_ranges'] ), -1, PREG_SPLIT_NO_EMPTY ) );
			$ua_tokens = array_filter( preg_split( '/\s+/', trim( (string) $row['search'] ), -1, PREG_SPLIT_NO_EMPTY ) );
			$data[]    = array(
				'provider'       => $row['provider'],
				'provider_label' => $row['provider_label'],
				'ua_count'       => count( $ua_tokens ),
				'range_count'    => count( $ranges ),
				'ua_tokens'      => trim( (string) $row['search'] ),
				'ip_ranges'      => array_values( $ranges ),
				'disabled'       => (int) $row['disabled'],
			);
		}

		return $data;
	}

	/**
	 * Enable or disable all ua_token rows for a given provider.
	 */
	public static function handleToggleProvider(): void {
		$bbcs_action = 'llm_toggle_provider';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		if ( '' === $provider ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid provider' );
			}
			wp_send_json_error( __( 'Invalid provider.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' provider=' . $provider );
		}

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$current = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT MAX(disabled) FROM `{$wpdb->bbcs_llm_trusted}` WHERE provider = %s", $provider )
		);

		$new = ( 1 === $current ) ? 0 : 1;
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' current=' . $current . ' new=' . $new );
		}

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->bbcs_llm_trusted,
			array( 'disabled' => $new ),
			array( 'provider' => $provider ),
			array( '%d' ),
			array( '%s' )
		);

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' before renderLlmTrusted' );
		}
		BotBlockerFileRenderer::renderLlmTrusted();
		BotBlockerCache::clearFileCache();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' after clearFileCache' );
		}

		wp_send_json_success( array( 'disabled' => $new ) );
	}

	/**
	 * Trigger a manual LLM cloud sync.
	 */
	public static function handleSyncCloud(): void {
		$bbcs_action = 'llm_sync_cloud';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$ok = BotBlockerLlmSync::doSync( 'manual', true );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' doSync result=' . ( $ok ? 'success' : 'fail' ) );
		}

		if ( ! $ok ) {
			$status = BotBlockerLlmSync::getStatus();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' sync failed: ' . ( $status['last_error'] ?? '' ) );
			}
			wp_send_json_error(
				__( 'LLM sync failed: ', 'botblocker-security' ) . ( $status['last_error'] ?? '' )
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'LLM providers synced successfully.', 'botblocker-security' ),
			)
		);
	}

	/**
	 * Get current LLM sync status.
	 */
	public static function handleGetSyncStatus(): void {
		$bbcs_action = 'llm_get_sync_status';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$status = BotBlockerLlmSync::getStatus();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' sync_status=' . wp_json_encode( $status ) );
		}
		wp_send_json_success( $status );
	}

	/**
	 * Export all LLM providers as JSON download data.
	 */
	public static function handleExportJson(): void {
		$bbcs_action = 'llm_export_json';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		wp_send_json_success( self::getProviderRows() );
	}

	/**
	 * Regenerate the LLM trusted PHP file from the database.
	 */
	public static function handleRegenerateFile(): void {
		$bbcs_action = 'llm_regenerate_file';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}
		try {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' before renderLlmTrusted' );
			}
			BotBlockerFileRenderer::renderLlmTrusted();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderLlmTrusted succeeded' );
			}
			wp_send_json_success( __( 'LLM file generated successfully.', 'botblocker-security' ) );
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderLlmTrusted FAILED: ' . $e->getMessage() );
			}
			wp_send_json_error( __( 'Failed to generate LLM file from database.', 'botblocker-security' ) );
		}
	}
}
