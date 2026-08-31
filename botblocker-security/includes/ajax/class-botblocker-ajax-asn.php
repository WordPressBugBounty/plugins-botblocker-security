<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxAsn {

	private static function normalizePostedAsn( $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		return BotBlockerAsnValue::normalize( sanitize_text_field( wp_unslash( $value ) ) );
	}

	public static function handleGetAsn(): void {
		$bbcs_action = 'asn_get_asn';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;

		$start  = isset( $_POST['start'] ) ? absint( wp_unslash( $_POST['start'] ) ) : 0;
		$length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
		$draw   = isset( $_POST['draw'] ) ? absint( wp_unslash( $_POST['draw'] ) ) : 0;
		$search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$records_total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}` WHERE 1 = %d", 1 )
		);
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' records_total=' . $records_total . ' search=' . $search );
		}

		if ( $search !== '' ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$records_filtered = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}`
	                 WHERE CAST(asnum AS CHAR) LIKE %s
	                    OR asname LIKE %s
	                    OR `rule` LIKE %s
	                    OR comment LIKE %s",
					$like,
					$like,
					$like,
					$like
				)
			);
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, priority, CAST(asnum AS CHAR) AS asnum, asname, `rule`, comment, disable
	                 FROM `{$wpdb->bbcs_asn}`
	                 WHERE CAST(asnum AS CHAR) LIKE %s
	                    OR asname LIKE %s
	                    OR `rule` LIKE %s
	                    OR comment LIKE %s
	                 ORDER BY priority ASC
	                 LIMIT %d, %d",
					$like,
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
					"SELECT id, priority, CAST(asnum AS CHAR) AS asnum, asname, `rule`, comment, disable
	                 FROM `{$wpdb->bbcs_asn}`
	                 WHERE 1 = %d
	                 ORDER BY priority ASC
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
				'id'       => $row['id'],
				'priority' => $row['priority'],
				'asnum'    => $row['asnum'],
				'asname'   => $row['asname'],
				'rule'     => $row['rule'],
				'comment'  => $row['comment'],
				'disable'  => $row['disable'],
			);
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' sending ' . count( $data ) . ' rows' );
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

	public static function handleGetDetails(): void {
		$bbcs_action = 'asn_get_details';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' id=' . $id );
		}
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, priority, CAST(asnum AS CHAR) AS asnum, asname, `rule`, comment, disable
				 FROM `{$wpdb->bbcs_asn}` WHERE id = %d",
				$id
			),
			ARRAY_A
		);
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' row found=' . ( $row ? 'true' : 'false' ) );
		}

		if ( $row ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' sending success' );
			}
			wp_send_json_success( $row );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: not found' );
			}
			wp_send_json_error( __( 'ASN rule not found.', 'botblocker-security' ) );
		}
	}

	public static function handleCreateAsn(): void {
		$bbcs_action = 'asn_create';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

	    /* phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
		$required = array( 'asnum', 'rule' );
		foreach ( $required as $field ) {
			if ( ! isset( $_POST[ $field ] ) || $_POST[ $field ] === '' ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing field: ' . $field );
				}
				/* translators: %s is the field name */
				wp_send_json_error( sprintf( __( 'Field \'%s\' is required.', 'botblocker-security' ), $field ) );
			}
		}

		global $wpdb;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslash+sanitize inside normalizePostedAsn
		$asnum = self::normalizePostedAsn( $_POST['asnum'] );
		if ( $asnum === null ) {
			wp_send_json_error( __( 'Invalid ASN number.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' asnum=' . $asnum );
		}
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}` WHERE asnum = %s",
				$asnum
			)
		);

		if ( $exists ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' ASN already exists' );
			}
			wp_send_json_error( __( 'ASN already exists.', 'botblocker-security' ) );
		}

		$data = array(
			'priority' => isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 50,
			'asnum'    => $asnum,
			'asname'   => isset( $_POST['asname'] ) ? sanitize_text_field( wp_unslash( $_POST['asname'] ) ) : '',
			'rule'     => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
			'comment'  => isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
			'disable'  => 0,
		);
	    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( $wpdb->bbcs_asn, $data, array( '%d', '%s', '%s', '%s', '%s', '%d' ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' insert result=' . ( false === $result ? 'false' : 'true' ) . ' last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			BotBlockerFileRenderer::renderAsn();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done' );
			}
			BotBlockerCache::clearFileCache();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, sending success' );
			}
			BotBlockerAudit::ruleChanged( BotBlockerAuditEvents::RULE_LIST_ASN, BotBlockerAuditEvents::RULE_ACTION_CREATED, array( 'id' => (int) $wpdb->insert_id ) );

			wp_send_json_success( __( 'ASN rule created successfully.', 'botblocker-security' ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: insert failed' );
			}
			wp_send_json_error( __( 'Failed to create ASN rule.', 'botblocker-security' ) );
		}
	}

	public static function handleUpdateAsn(): void {
		$bbcs_action = 'asn_update';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

	    /* phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
		$required = array( 'id', 'asnum', 'rule' );
		foreach ( $required as $field ) {
			if ( ! isset( $_POST[ $field ] ) || $_POST[ $field ] === '' ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing field: ' . $field );
				}
				/* translators: %s is the field name */
				wp_send_json_error( sprintf( __( 'Field \'%s\' is required.', 'botblocker-security' ), $field ) );
			}
		}

		global $wpdb;

		$id = absint( wp_unslash( $_POST['id'] ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' id=' . $id );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslash+sanitize inside normalizePostedAsn
		$asnum = self::normalizePostedAsn( $_POST['asnum'] );
		if ( $asnum === null ) {
			wp_send_json_error( __( 'Invalid ASN number.', 'botblocker-security' ) );
		}
		$data = array(
			'priority' => isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 50,
			'asnum'    => $asnum,
			'asname'   => isset( $_POST['asname'] ) ? sanitize_text_field( wp_unslash( $_POST['asname'] ) ) : '',
			'rule'     => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
			'comment'  => isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
		);
	    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update( $wpdb->bbcs_asn, $data, array( 'id' => $id ), array( '%d', '%s', '%s', '%s', '%s' ), array( '%d' ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' update result=' . ( false === $result ? 'false' : 'true' ) . ' last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			BotBlockerFileRenderer::renderAsn();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done' );
			}
			BotBlockerCache::clearFileCache();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, sending success' );
			}
			BotBlockerAudit::ruleChanged( BotBlockerAuditEvents::RULE_LIST_ASN, BotBlockerAuditEvents::RULE_ACTION_UPDATED, array( 'id' => $id ) );

			wp_send_json_success( __( 'ASN rule updated successfully.', 'botblocker-security' ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: update failed' );
			}
			wp_send_json_error( __( 'Failed to update ASN rule.', 'botblocker-security' ) );
		}
	}

	public static function handleDeleteAsn(): void {
		$bbcs_action = 'asn_delete';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' id=' . $id );
		}
		if ( $id === 0 ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid ID' );
			}
			wp_send_json_error( __( 'ID is required for deletion.', 'botblocker-security' ) );
		}

		global $wpdb;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $wpdb->bbcs_asn, array( 'id' => $id ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' delete result=' . ( false === $result ? 'false' : 'rows=' . $result ) . ' last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			BotBlockerFileRenderer::renderAsn();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done' );
			}
			BotBlockerCache::clearFileCache();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, sending success' );
			}
			BotBlockerAudit::ruleChanged( BotBlockerAuditEvents::RULE_LIST_ASN, BotBlockerAuditEvents::RULE_ACTION_DELETED, array( 'id' => $id ) );

			wp_send_json_success( __( 'ASN rule deleted successfully.', 'botblocker-security' ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: delete failed' );
			}
			wp_send_json_error( __( 'Failed to delete ASN rule.', 'botblocker-security' ) );
		}
	}

	public static function handleToggleAsn(): void {
		$bbcs_action = 'asn_toggle';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' id=' . $id );
		}
		if ( $id < 1 ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid ID (< 1)' );
			}
			wp_send_json_error( __( 'Missing or invalid ASN rule ID.', 'botblocker-security' ) );
		}
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$wpdb->bbcs_asn}` SET disable = 1 - disable WHERE id = %d",
				$id
			)
		);
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' toggle result=' . ( false === $result ? 'false' : 'rows=' . $result ) . ' last_error=' . $wpdb->last_error );
		}

		if ( false !== $result ) {
			BotBlockerFileRenderer::renderAsn();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done' );
			}
			BotBlockerCache::clearFileCache();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, sending success' );
			}
			BotBlockerAudit::ruleChanged( BotBlockerAuditEvents::RULE_LIST_ASN, BotBlockerAuditEvents::RULE_ACTION_TOGGLED, array( 'id' => $id ) );

			wp_send_json_success( __( 'ASN rule toggled successfully.', 'botblocker-security' ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: toggle failed' );
			}
			wp_send_json_error( __( 'Failed to toggle ASN rule.', 'botblocker-security' ) );
		}
	}

	public static function handleExport(): void {
		$bbcs_action = 'asn_export';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT id, priority, CAST(asnum AS CHAR) AS asnum, asname, `rule`, comment, disable
			 FROM `{$wpdb->bbcs_asn}`",
			ARRAY_A
		);
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' exported ' . count( (array) $rows ) . ' rows' );
		}
		wp_send_json_success( $rows );
	}

	public static function handleImport(): void {
		$bbcs_action = 'asn_import';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		if ( ! isset( $_POST['asn_rules'] ) || empty( $_POST['asn_rules'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing asn_rules POST param' );
			}
			wp_send_json_error( __( 'ASN data is required for import.', 'botblocker-security' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is decoded, each field sanitized individually below
		$items = json_decode( wp_unslash( $_POST['asn_rules'] ), true, 512, JSON_BIGINT_AS_STRING );

		if ( ! is_array( $items ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid JSON' );
			}
			wp_send_json_error( __( 'Invalid JSON format.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' items count=' . count( $items ) );
		}

		$imported = 0;
		$skipped  = 0;
		foreach ( $items as $item ) {
			$asnum = is_array( $item ) && isset( $item['asnum'] ) ? BotBlockerAsnValue::normalize( $item['asnum'] ) : null;
			if ( $asnum === null ) {
				++$skipped;
				continue; }

	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}` WHERE asnum = %s",
					$asnum
				)
			);

			if ( $exists == 0 ) {
	            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->insert(
					$wpdb->bbcs_asn,
					array(
						'priority' => isset( $item['priority'] ) ? absint( $item['priority'] ) : 50,
						'asnum'    => $asnum,
						'asname'   => isset( $item['asname'] ) ? sanitize_text_field( $item['asname'] ) : '',
						'rule'     => isset( $item['rule'] ) ? sanitize_text_field( $item['rule'] ) : 'block',
						'comment'  => isset( $item['comment'] ) ? sanitize_textarea_field( $item['comment'] ) : '',
						'disable'  => isset( $item['disable'] ) ? absint( $item['disable'] ) : 0,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%d' )
				);
				if ( $result !== false ) {
					++$imported; }
			} else {
				++$skipped;
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' imported=' . $imported . ' skipped=' . $skipped );
		}

		BotBlockerFileRenderer::renderAsn();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done' );
		}
		BotBlockerCache::clearFileCache();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, sending success' );
		}

		BotBlockerAudit::ruleChanged( BotBlockerAuditEvents::RULE_LIST_ASN, BotBlockerAuditEvents::RULE_ACTION_IMPORTED, array( 'imported' => $imported, 'skipped' => $skipped ) );

		wp_send_json_success(
			array(
				'imported' => $imported,
				'skipped'  => $skipped,
			)
		);
	}

	public static function handleClearAll(): void {
		$bbcs_action = 'asn_clear_all';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		global $wpdb;
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "TRUNCATE TABLE `{$wpdb->bbcs_asn}`" );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' truncate result=' . ( false === $result ? 'false' : 'true' ) . ' last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			BotBlockerFileRenderer::renderAsn();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done' );
			}
			BotBlockerCache::clearFileCache();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, sending success' );
			}
			BotBlockerAudit::ruleChanged( BotBlockerAuditEvents::RULE_LIST_ASN, BotBlockerAuditEvents::RULE_ACTION_CLEARED );

			wp_send_json_success( __( 'All ASN rules have been cleared.', 'botblocker-security' ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: truncate failed' );
			}
			wp_send_json_error( __( 'Failed to clear ASN rules.', 'botblocker-security' ) );
		}
	}

	public static function handleRegenerateFile(): void {
		$bbcs_action = 'asn_regenerate';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}
		try {
			BotBlockerFileRenderer::renderAsn();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' renderAsn done, sending success' );
			}
			wp_send_json_success( __( 'ASN rules file generated successfully.', 'botblocker-security' ) );
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' exception: ' . $e->getMessage() );
			}
			wp_send_json_error( __( 'Failed to generate ASN rules file.', 'botblocker-security' ) );
		}
	}
}
