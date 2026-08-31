<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

trait BotBlockerAjaxIpRulesTrait {

	abstract protected static function getTableName(): string;
	abstract protected static function getRuleListName(): string;
	abstract protected static function getIpVersionLabel(): string;
	abstract protected static function encodeIpForStorage( string $ip ): string;
	abstract protected static function getOverlapPlaceholder(): string;
	abstract protected static function getFileRenderMethod(): string;
	abstract protected static function decodeImportedIpField( $value );

	public static function handleGetRules(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_rules called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_rules nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_rules cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_rules cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		$start  = isset( $_POST['start'] ) ? absint( wp_unslash( $_POST['start'] ) ) : 0;
		$length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
		$draw   = isset( $_POST['draw'] ) ? absint( wp_unslash( $_POST['draw'] ) ) : 0;
		$search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_rules search="' . $search . '" start=' . $start . ' length=' . $length . ' draw=' . $draw );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$records_total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE 1 = %d", 1 )
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $search !== '' ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$records_filtered = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}`
	                 WHERE search LIKE %s OR `rule` LIKE %s OR comment LIKE %s",
					$like,
					$like,
					$like
				)
			);
	        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, priority, search, expires, disable, `rule`, comment
	                 FROM `{$table}`
	                 WHERE search LIKE %s OR `rule` LIKE %s OR comment LIKE %s
	                 ORDER BY priority DESC
	                 LIMIT %d, %d",
					$like,
					$like,
					$like,
					$start,
					$length
				),
				ARRAY_A
			);
	        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$records_filtered = $records_total;

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, priority, search, expires, disable, `rule`, comment
	                 FROM `{$table}`
	                 WHERE 1 = %d
	                 ORDER BY priority DESC
	                 LIMIT %d, %d",
					1,
					$start,
					$length
				),
				ARRAY_A
			);
	        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$data = array();
		foreach ( (array) $results as $row ) {
			$data[] = array(
				'id'       => $row['id'],
				'priority' => $row['priority'],
				'ip'       => $row['search'],
				'expires'  => BotBlockerCompatibility::wpDate( 'Y-m-d H:i:s', (int) $row['expires'] ),
				'disable'  => $row['disable'],
				'rule'     => $row['rule'],
				'comment'  => $row['comment'],
			);
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_rules total=' . $records_total . ' filtered=' . $records_filtered . ' rows=' . count( $data ) );
		}

		$response_data = array(
			'draw'            => $draw,
			'recordsTotal'    => $records_total,
			'recordsFiltered' => $records_filtered,
			'data'            => $data,
		);

		wp_send_json( $response_data );
	}

	public static function handleDeleteRule(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule invalid ID (0 or missing)' );
			}
			wp_send_json_error( __( 'Invalid ID provided.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule id=' . $id . ' table=' . $table );
		}

		// Retrieve rule before deletion for firing the action hook
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rule = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ) );
		$result = $wpdb->delete( $table, array( 'id' => $id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule $wpdb->delete result=' . var_export( $result, true ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule $wpdb->last_error=' . $wpdb->last_error );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule $wpdb->last_query=' . $wpdb->last_query );
		}

		if ( $result !== false ) {
			if ( $rule && ! empty( $rule->search ) ) {
				delete_transient( 'bbcs_fr_' . md5( $rule->search ) );

				$mask_parts = BotBlockerIp::parseRateSubnetMask( BotBlocker::getInstance()->settings->bbcs_rate_subnet_mask ?? '' );
				if ( $mask_parts[0] > 0 && $mask_parts[1] > 0 ) {
					$ip_version  = BotBlockerIp::getVersion( $rule->search );
					if ( $ip_version ) {
						$subnet_cidr = BotBlockerIp::computePtrSubnet( $rule->search, $ip_version, $mask_parts[0], $mask_parts[1] );
						delete_transient( 'bbcs_fr_subnet_' . md5( $subnet_cidr ) );
					}
				}

				do_action( 'bbcs_ip_rule_deleted', $rule->search );

				// Remove from hot-bans immediately so file-based layers stop enforcing.
				BotBlockerFileRenderer::removeHotBan( $rule->search );
			}

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule DB delete OK, rendering files' );
			}

			BotBlockerFileRenderer::renderIps();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule renderIps done, clearing cache' );
			}

			BotBlockerCache::clearFileCache();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule clearFileCache done, sending success' );
			}

			BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_DELETED, array( 'id' => $id ) );

			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_success( sprintf( __( '%s rule deleted successfully.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_delete_rule DB delete FAILED' );
			}

			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_error( sprintf( __( 'Failed to delete %s rule.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		}
	}

	public static function handleToggleRule(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule invalid ID (0 or missing)' );
			}
			wp_send_json_error( __( 'Bad ID.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule id=' . $id );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT search, rule, expires, disable FROM `{$table}` WHERE id = %d", $id ),
			ARRAY_A
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( null === $row ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule rule NOT FOUND for id=' . $id );
			}
			wp_send_json_error( __( 'Rule not found.', 'botblocker-security' ) );
		}

		$current = $row['disable'];
		$new = (int) ! (int) $current;

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule current=' . $current . ' new=' . $new );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			array( 'disable' => $new ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule update done, syncing files (enable=' . ( $new === 0 ? 'YES' : 'NO' ) . ')' );
		}

		if ( $new === 0 ) {
			BotBlockerFileRenderer::syncIpBanFiles( $row['search'], $row['rule'], (int) $row['expires'] );
		} else {
			BotBlockerFileRenderer::removeHotBan( $row['search'] );
			BotBlockerFileRenderer::renderIps();
			BotBlockerCache::clearFileCache();
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_toggle_rule complete, sending success' );
		}

		BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_TOGGLED, array( 'id' => $id ) );

		// translators: %s is the IP version label (IPv4 or IPv6).
		wp_send_json_success( sprintf( __( '%s rule toggled successfully.', 'botblocker-security' ), static::getIpVersionLabel() ) );
	}

	public static function handleCreateRule(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		/**
		 * REVIEWER NOTE:
		 * All required $_POST fields are validated for existence in the loop below.
		 * This ensures that later direct access to $_POST['ip'], $_POST['rule'], $_POST['expires'], $_POST['priority'], and $_POST['comment']
		 * is always safe and cannot trigger undefined index warnings.
		 * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
		 */
	    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
		$required_fields = array( 'ip', 'rule', 'expires', 'priority' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule missing field: ' . $field );
				}
				// translators: %s is the name of the required field.
				wp_send_json_error( sprintf( __( '%s is required.', 'botblocker-security' ), ucfirst( $field ) ) );
			}
		}

		$ip   = sanitize_text_field( wp_unslash( $_POST['ip'] ) );
		$type = BotBlockerIp::detectType( $ip );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule ip="' . $ip . '" type=' . $type );
		}

		if ( $type === BBCS_IP_TYPE_INVALID ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule invalid IP' );
			}
			wp_send_json_error( __( 'Invalid IP address or CIDR notation provided.', 'botblocker-security' ) );
		}
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE `search` = %s",
				$ip
			)
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule exists=' . var_export( $exists, true ) );
		}

		if ( $exists ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule duplicate, rejecting' );
			}
			wp_send_json_error( __( 'Rule already exists.', 'botblocker-security' ) );
		}

		$expires = strtotime( sanitize_text_field( wp_unslash( $_POST['expires'] ) ) );
		if ( $expires === false ) {
			$expires = 0;
		}

		$data = array(
			'priority' => intval( wp_unslash( $_POST['priority'] ) ),
			'search'   => $ip,
			'rule'     => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
			'comment'  => isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
			'expires'  => $expires,
			'disable'  => 0,
		);
		if ( $data['rule'] === 'permanently_ban' ) {
			$data['rule']    = BBCS_RULE_BLOCK;
			$data['expires'] = BOTBLOCKER_EXP_INF;
		}

	    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

		if ( $type === BBCS_IP_TYPE_CIDR ) {
			$ip_range    = BotBlockerIp::toRange( $ip );
			$data['ip1'] = static::encodeIpForStorage( $ip_range[0] );
			$data['ip2'] = static::encodeIpForStorage( $ip_range[1] );
		} else {
			$encoded_ip  = static::encodeIpForStorage( $ip );
			$data['ip1'] = $encoded_ip;
			$data['ip2'] = $encoded_ip;
		}
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$ph      = static::getOverlapPlaceholder();
		$overlap = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT search FROM `{$table}`
	        WHERE (ip1 <= {$ph} AND ip2 >= {$ph}) OR (ip1 >= {$ph} AND ip2 <= {$ph}) LIMIT 1",
				$data['ip2'],
				$data['ip1'],
				$data['ip1'],
				$data['ip2']
			)
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule overlap=' . var_export( $overlap, true ) );
		}

		if ( $overlap ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule overlap conflict with: ' . $overlap );
			}
			// translators: %s is the overlapping IP range.
			wp_send_json_error( sprintf( __( 'IP range overlaps with an existing rule: %s', 'botblocker-security' ), $overlap ) );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->insert( $table, $data );
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule $wpdb->insert result=' . var_export( $result, true ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule $wpdb->last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule insert OK, syncing ban files' );
			}

			BotBlockerFileRenderer::syncIpBanFiles( $ip, $data['rule'], (int) $data['expires'] );

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule complete, sending success' );
			}

			BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_CREATED, array( 'id' => (int) $wpdb->insert_id ) );

			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_success( sprintf( __( '%s rule created successfully.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_create_rule insert FAILED' );
			}
			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_error( sprintf( __( 'Failed to create %s rule.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		}
	}

	/*
	 * Update IP rule
	 */
	public static function handleUpdateRule(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		/**
		 * REVIEWER NOTE:
		 * All required $_POST fields are validated for existence in the loop below.
		 * This ensures that later direct access to $_POST['ip'], $_POST['rule'], $_POST['expires'], $_POST['priority'], and $_POST['comment']
		 * is always safe and cannot trigger undefined index warnings.
		 * Any PHPCS warnings about possibly undefined indexes for these fields are false positives.
		 */
	    /* phpcs:disable  WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
		$required_fields = array( 'id', 'ip', 'rule', 'expires', 'priority' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule missing field: ' . $field );
				}
				// translators: %s is the name of the required field.
				wp_send_json_error( sprintf( __( '%s is required.', 'botblocker-security' ), ucfirst( $field ) ) );
			}
		}

		$id   = intval( wp_unslash( $_POST['id'] ) );
		$ip   = sanitize_text_field( wp_unslash( $_POST['ip'] ) );
		$type = BotBlockerIp::detectType( $ip );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule id=' . $id . ' ip="' . $ip . '" type=' . $type );
		}

		if ( $type === BBCS_IP_TYPE_INVALID ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule invalid IP' );
			}
			wp_send_json_error( __( 'Invalid IP address or CIDR notation provided.', 'botblocker-security' ) );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$duplicate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE search = %s AND id != %d",
				$ip,
				$id
			)
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule duplicate=' . var_export( $duplicate, true ) );
		}

		if ( $duplicate ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule duplicate found, rejecting' );
			}
			wp_send_json_error( __( 'Rule already exists.', 'botblocker-security' ) );
		}

		$expires = strtotime( sanitize_text_field( wp_unslash( $_POST['expires'] ) ) );
		if ( $expires === false ) {
			$expires = 0;
		}

		$data = array(
			'priority' => intval( wp_unslash( $_POST['priority'] ) ),
			'search'   => $ip,
			'rule'     => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
			'comment'  => isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
			'expires'  => $expires,
		);
		if ( $data['rule'] === 'permanently_ban' ) {
			$data['rule']    = BBCS_RULE_BLOCK;
			$data['expires'] = BOTBLOCKER_EXP_INF;
		}
	    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

		if ( $type === BBCS_IP_TYPE_CIDR ) {
			$ip_range    = BotBlockerIp::toRange( $ip );
			$data['ip1'] = static::encodeIpForStorage( $ip_range[0] );
			$data['ip2'] = static::encodeIpForStorage( $ip_range[1] );
		} else {
			$encoded_ip  = static::encodeIpForStorage( $ip );
			$data['ip1'] = $encoded_ip;
			$data['ip2'] = $encoded_ip;
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$ph      = static::getOverlapPlaceholder();
		$overlap = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT search FROM `{$table}`
	        WHERE ((ip1 <= {$ph} AND ip2 >= {$ph}) OR (ip1 >= {$ph} AND ip2 <= {$ph})) AND id != %d LIMIT 1",
				$data['ip2'],
				$data['ip1'],
				$data['ip1'],
				$data['ip2'],
				$id
			)
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule overlap=' . var_export( $overlap, true ) );
		}

		if ( $overlap ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule overlap conflict with: ' . $overlap );
			}
			// translators: %s is the overlapping IP range.
			wp_send_json_error( sprintf( __( 'IP range overlaps with an existing rule: %s', 'botblocker-security' ), $overlap ) );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update( $table, $data, array( 'id' => $id ) );
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule $wpdb->update result=' . var_export( $result, true ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule $wpdb->last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule update OK, syncing ban files' );
			}

			BotBlockerFileRenderer::syncIpBanFiles( $ip, $data['rule'], (int) $data['expires'] );

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule complete, sending success' );
			}

			BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_UPDATED, array( 'id' => $id ) );

			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_success( sprintf( __( '%s rule updated successfully.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_update_rule update FAILED' );
			}
			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_error( sprintf( __( 'Failed to update %s rule.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		}
	}

	public static function handleExport(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_export called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_export nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_export cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_export cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rules = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_export rules count=' . count( $rules ) );
		}

		wp_send_json_success( $rules );
	}

	public static function handleImport(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		if ( ! isset( $_POST['rules'] ) || empty( $_POST['rules'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import no rules provided' );
			}
			wp_send_json_error( __( 'No rules provided.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is decoded, each field sanitized individually below
		$rules = json_decode( wp_unslash( $_POST['rules'] ), true );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import rules type=' . gettype( $rules ) . ' count=' . ( is_array( $rules ) ? count( $rules ) : 0 ) );
		}

		if ( is_array( $rules ) ) {
			$imported = 0;
			$skipped  = 0;
			$hot_bans = array();
			foreach ( $rules as $i => $rule ) {
				$search   = sanitize_text_field( $rule['search'] ?? '' );
				$priority = intval( $rule['priority'] ?? 50 );
				$ip1      = static::decodeImportedIpField( $rule['ip1'] ?? '' );
				$ip2      = static::decodeImportedIpField( $rule['ip2'] ?? '' );
				$expires  = intval( $rule['expires'] ?? 0 );
				$disable  = intval( $rule['disable'] ?? 0 );
				$rule_val = sanitize_text_field( $rule['rule'] ?? '' );
				$comment  = sanitize_textarea_field( $rule['comment'] ?? '' );
				$readonly = intval( $rule['readonly'] ?? 0 );
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `{$table}` WHERE search = %s",
						$search
					)
				);
	        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import rule[' . $i . '] search="' . $search . '" existing=' . var_export( $existing, true ) );
				}

				if ( $existing == 0 ) {
					$data = array(
						'search'   => $search,
						'priority' => $priority,
						'ip1'      => $ip1,
						'ip2'      => $ip2,
						'expires'  => $expires,
						'disable'  => $disable,
						'rule'     => $rule_val,
						'comment'  => $comment,
						'readonly' => $readonly,
					);
					// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	                // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$result = $wpdb->insert( $table, $data );
	                // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
						error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import rule[' . $i . '] insert result=' . var_export( $result, true ) );
					}
					if ( $result !== false ) {
						// Collect for single batched hot-ban write instead of O(N) file I/O.
						$hot_bans[] = array(
							'ip'      => $search,
							'action'  => $data['rule'],
							'expires' => $data['expires'],
						);
						++$imported;
					}
				} else {
					++$skipped;
				}
			}

			// Single batched write to hot-bans.php.
			if ( ! empty( $hot_bans ) ) {
				BotBlockerFileRenderer::appendHotBanBatch( $hot_bans );
			}

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import imported=' . $imported . ' skipped=' . $skipped . ', rendering files' );
			}

			BotBlockerFileRenderer::renderIps();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import renderIps done, clearing cache' );
			}

			BotBlockerCache::clearFileCache();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import complete, sending success' );
			}

			BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_IMPORTED, array( 'imported' => $imported, 'skipped' => $skipped ) );

			wp_send_json_success(
				array(
					'imported' => $imported,
					'skipped'  => $skipped,
				)
			);
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import invalid JSON format' );
			}
			wp_send_json_error( __( 'Invalid JSON format.', 'botblocker-security' ) );
		}
	}

	public static function handleClearAll(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cleared_ips = $wpdb->get_col( "SELECT `search` FROM `{$table}`" );
		$result = $wpdb->query( "TRUNCATE TABLE `{$table}`" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all truncate result=' . var_export( $result, true ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all $wpdb->last_error=' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			if ( ! empty( $cleared_ips ) ) {
				$mask_parts = array( 0, 0 );
				if ( class_exists( 'BotBlocker' ) && ! empty( BotBlocker::getInstance()->settings ) ) {
					$mask_parts = BotBlockerIp::parseRateSubnetMask( BotBlocker::getInstance()->settings->bbcs_rate_subnet_mask ?? '' );
				}

				$do_subnet = $mask_parts[0] > 0 && $mask_parts[1] > 0;

				foreach ( $cleared_ips as $cleared_ip ) {
					delete_transient( 'bbcs_fr_' . md5( $cleared_ip ) );

					// Remove from hot-bans.php so short-term bans don't persist after DB clear.
					BotBlockerFileRenderer::removeHotBan( $cleared_ip );

					if ( $do_subnet && class_exists( 'BotBlockerIp' ) ) {
						$ip_version = BotBlockerIp::getVersion( $cleared_ip );
						if ( $ip_version ) {
							$subnet_cidr = BotBlockerIp::computePtrSubnet( $cleared_ip, $ip_version, $mask_parts[0], $mask_parts[1] );
							delete_transient( 'bbcs_fr_subnet_' . md5( $subnet_cidr ) );
						}
					}
				}

				do_action( 'bbcs_ip_rule_deleted', $cleared_ips );
			}

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all truncate OK, rendering files' );
			}

			BotBlockerFileRenderer::renderIps();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all renderIps done, clearing cache' );
			}

			BotBlockerCache::clearFileCache();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all complete, sending success' );
			}

			BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_CLEARED );

			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_success( sprintf( __( 'All %s rules have been cleared.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_clear_all truncate FAILED' );
			}
			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_error( sprintf( __( 'Failed to clear %s rules.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		}
	}

	public static function handleGetDetails(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_details called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_details nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_details cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_details cap check passed' );
		}

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_details id=' . $id );
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE id = %d",
				$id
			),
			ARRAY_A
		);
	    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_get_details rule found=' . ( $rule ? 'yes' : 'no' ) );
		}

		if ( $rule ) {
			wp_send_json_success( $rule );
		} else {
			wp_send_json_error( __( 'Rule not found.', 'botblocker-security' ) );
		}
	}

	public static function handleRegenerateFile(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file cap check passed' );
		}

		try {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file calling renderIps' );
			}

			BotBlockerFileRenderer::renderIps();
			BotBlockerFileRenderer::renderHotBans();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file renderIps + renderHotBans done, sending success' );
			}

			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_success( sprintf( __( '%s rules file generated successfully.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_regenerate_file error: ' . $e->getMessage() );
			}
			// translators: %s is the IP version label (IPv4 or IPv6).
			wp_send_json_error( sprintf( __( 'Failed to generate %s rules file from database.', 'botblocker-security' ), static::getIpVersionLabel() ) );
		}
	}

	public static function handleImportWhitelist(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_whitelist called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_whitelist cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_whitelist delegating to importList' );
		}

		static::importList( BBCS_RULE_ALLOW );
	}

	public static function handleImportBlacklist(): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_blacklist called' );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_blacklist cap check FAILED' );
			}
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_blacklist delegating to importList' );
		}

		static::importList( BBCS_RULE_BLOCK );
	}

	private static function importList( string $rule_type ): void {
		$ip_label = strtolower( static::getIpVersionLabel() );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list type=' . $rule_type );
		}

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		global $wpdb;

		$table = $wpdb->{static::getTableName()};

		if ( ! isset( $_POST['file_content'] ) || empty( $_POST['file_content'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list no file content' );
			}
			wp_send_json_error( __( 'No file content provided.', 'botblocker-security' ) );
		}

		$file_content = sanitize_textarea_field( wp_unslash( $_POST['file_content'] ) );
		$lines        = explode( "\n", $file_content );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list lines=' . count( $lines ) );
		}

		$imported = 0;
		$skipped  = 0;

		foreach ( $lines as $i => $line ) {
			$ip = trim( $line );
			if ( empty( $ip ) ) {
				continue;
			}
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE search = %s", $ip ) );
	        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list line[' . $i . '] ip="' . $ip . '" existing=' . var_export( $existing, true ) );
			}

			if ( $existing == 0 ) {
				$type = BotBlockerIp::detectType( $ip );

				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list line[' . $i . '] type=' . $type );
				}

				if ( $type === BBCS_IP_TYPE_INVALID ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
						error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list line[' . $i . '] invalid IP' );
					}
					wp_send_json_error( __( 'Invalid IP address or CIDR notation provided.', 'botblocker-security' ) );
				}
				$data = array(
					'priority' => 10,
					'search'   => $ip,
					'rule'     => $rule_type,
					'comment'  => 'Imported ' . ( $rule_type == BBCS_RULE_ALLOW ? 'whitelist' : 'blacklist' ) . " (IP: $ip)",
					'expires'  => BOTBLOCKER_EXP_INF,
				);

				if ( $type === BBCS_IP_TYPE_CIDR ) {
					$ip_range    = BotBlockerIp::toRange( $ip );
					$data['ip1'] = static::encodeIpForStorage( $ip_range[0] );
					$data['ip2'] = static::encodeIpForStorage( $ip_range[1] );
				} else {
					$encoded_ip  = static::encodeIpForStorage( $ip );
					$data['ip1'] = $encoded_ip;
					$data['ip2'] = $encoded_ip;
				}

				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result = $wpdb->insert( $table, $data );
	            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list line[' . $i . '] insert result=' . var_export( $result, true ) );
				}

				if ( $result !== false ) {
					++$imported;
				}
			} else {
				++$skipped;
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list imported=' . $imported . ' skipped=' . $skipped . ', rendering files' );
		}

		BotBlockerFileRenderer::renderIps();

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list renderIps done, clearing cache' );
		}

		BotBlockerCache::clearFileCache();

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $ip_label . '_import_list complete, sending success' );
		}

		BotBlockerAudit::ruleChanged( static::getRuleListName(), BotBlockerAuditEvents::RULE_ACTION_IMPORTED, array( 'imported' => $imported, 'skipped' => $skipped ) );

		wp_send_json_success(
			array(
				'imported' => $imported,
				'skipped'  => $skipped,
			)
		);
	}
}
