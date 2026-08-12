<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerStore {

	/** Unix time: rows with date >= this have a trustworthy `filtered` flag. 0 = trust all. */
	public const FILTERED_WATERMARK_OPTION = 'bbcs_filtered_watermark';

	/** Extra seconds on upgrade/invalidation so late writes land below the watermark. */
	public const FILTERED_WATERMARK_BUFFER = 300;

	/** '1' after column verified. Autoloaded — hot path is an in-memory get_option. */
	public const FILTERED_COLUMN_OPTION = 'bbcs_filtered_column';

	/** 1 if page matches a page_filters LIKE pattern, 0 otherwise; null on DB error. */
	public static function computeFilteredFlag( string $page ): ?int {
		global $wpdb;
		if ( empty( $wpdb->bbcs_page_filters ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$match = $wpdb->get_var( $wpdb->prepare(
			"SELECT EXISTS(SELECT 1 FROM `{$wpdb->bbcs_page_filters}` WHERE %s LIKE `pattern`)",
			$page
		) );
		if ( '' !== $wpdb->last_error ) {
			return null;
		}

		return $match ? 1 : 0;
	}


	public static function storeData( $reason_message = null, $reason = null ): void {
		global $wpdb;
		$BBCS = BotBlocker::getInstance();
		if ( ( BotBlockerWpUtility::is_admin_area_page( $BBCS->save_page ) && $BBCS->settings->botblocker_log_admin !== 1 ) ||
			( BotBlockerWpUtility::is_wordpress_system_page( $BBCS->save_page ) && $BBCS->settings->botblocker_log_wp !== 1 ) ||
			( BotBlockerWpUtility::is_botblocker_page() && $BBCS->settings->botblocker_log_bbcs !== 1 )
		) {
			return;
		}

		$result                 = $reason_message ?? $BBCS->result_of_action ?? BOTBLOCKER_EMPTY;
		$BBCS->result_of_action = sanitize_text_field( $result );

		if ( is_numeric( $reason ) ) {
			$BBCS->reason_for_action = (int) $reason;
		}

		$reason     = is_numeric( $reason ) ? (int) $reason : null;
		$code       = bbcs_codeList( $reason );
		$table_name = $wpdb->bbcs_hits_suspicious;
		if ( $code['allow'] ) {
			$table_name = $wpdb->bbcs_hits;
		}

		$execution_time = round( microtime( true ) - $BBCS->time, 3 );
		$page           = sanitize_text_field( $BBCS->save_page ?? BOTBLOCKER_EMPTY );
		$query_data     = array(
			'cid'          => sanitize_text_field( $BBCS->cid ?? BOTBLOCKER_EMPTY ),
			'date'         => $BBCS->time,
			'ip'           => sanitize_text_field( $BBCS->ip ?? BOTBLOCKER_EMPTY ),
			'ptr'          => sanitize_text_field( $BBCS->ptr ?? BOTBLOCKER_EMPTY ),
			'useragent'    => sanitize_text_field( $BBCS->useragent ?? BOTBLOCKER_EMPTY ),
			'uid'          => sanitize_text_field( $BBCS->uid ?? BOTBLOCKER_EMPTY ),
			'country'      => sanitize_text_field( $BBCS->country ?? BOTBLOCKER_EMPTY ),
			'country_name' => sanitize_text_field( $BBCS->country_name ?? BOTBLOCKER_EMPTY ),
			'referer'      => sanitize_text_field( $BBCS->save_referer ?? BOTBLOCKER_EMPTY ),
			'page'         => $page,
			'lang'         => sanitize_text_field( $BBCS->lang ?? BOTBLOCKER_EMPTY ),
			'accept_lang'  => sanitize_text_field( $BBCS->accept_lang ?? BOTBLOCKER_EMPTY ),
			'name_lang'    => sanitize_text_field( $BBCS->name_lang ?? BOTBLOCKER_EMPTY ),
			'generation'   => $execution_time,
			'passed'       => sanitize_text_field( $BBCS->reason_for_action ?? BOTBLOCKER_EMPTY ),
			'recaptcha'    => sanitize_text_field( $BBCS->recaptcha ?? BOTBLOCKER_EMPTY ),
			'js_w'         => sanitize_text_field( $BBCS->js_w ?? BOTBLOCKER_EMPTY ),
			'js_h'         => sanitize_text_field( $BBCS->js_h ?? BOTBLOCKER_EMPTY ),
			'js_cw'        => sanitize_text_field( $BBCS->js_cw ?? BOTBLOCKER_EMPTY ),
			'js_ch'        => sanitize_text_field( $BBCS->js_ch ?? BOTBLOCKER_EMPTY ),
			'js_co'        => sanitize_text_field( $BBCS->js_co ?? BOTBLOCKER_EMPTY ),
			'js_pi'        => sanitize_text_field( $BBCS->js_pi ?? BOTBLOCKER_EMPTY ),
			'refhost'      => sanitize_text_field( $BBCS->refhost ?? BOTBLOCKER_EMPTY ),
			'adblock'      => sanitize_text_field( $BBCS->post_adblocker_found ?? BOTBLOCKER_EMPTY ),
			'asnum'        => sanitize_text_field( $BBCS->asnum ?? BOTBLOCKER_EMPTY ),
			'asname'       => sanitize_text_field( $BBCS->asname ?? BOTBLOCKER_EMPTY ),
			'result'       => $BBCS->result_of_action,
			'http_accept'  => sanitize_text_field( $BBCS->http_accept ?? BOTBLOCKER_EMPTY ),
			'method'       => sanitize_text_field( $BBCS->request_method ?? BOTBLOCKER_EMPTY ),
			'ym_uid'       => sanitize_text_field( $BBCS->ym_uid ?? BOTBLOCKER_EMPTY ),
			'ga_uid'       => sanitize_text_field( $BBCS->ga_uid ?? BOTBLOCKER_EMPTY ),
			'ip_short'     => sanitize_text_field( $BBCS->ip_short ?? BOTBLOCKER_EMPTY ),
			'hosting'      => sanitize_text_field( $BBCS->hosting ?? BOTBLOCKER_EMPTY ),
			'hit'          => sanitize_text_field( $BBCS->cookie_hits_counter ?? BOTBLOCKER_EMPTY ),
			'browser'      => sanitize_text_field( $BBCS->browser ?? BOTBLOCKER_EMPTY ),
			'os'           => sanitize_text_field( $BBCS->os ?? BOTBLOCKER_EMPTY ),
			'device'       => sanitize_text_field( $BBCS->device ?? BOTBLOCKER_EMPTY ),
			'fp'           => is_array( $BBCS->post_antidetect_scope )
				? wp_json_encode( $BBCS->post_antidetect_scope, JSON_UNESCAPED_UNICODE )
				: sanitize_text_field( $BBCS->post_antidetect_scope ?? BOTBLOCKER_EMPTY ),
			'wbot'         => sanitize_text_field( $BBCS->white_bot ?? BOTBLOCKER_EMPTY ),
		);

		// Skip filtered key if column missing (DDL denied) — else every insert breaks.
		if ( get_option( self::FILTERED_COLUMN_OPTION ) === '1' ) {
			$flag = self::computeFilteredFlag( $page );
			if ( null === $flag ) {
				// DB error: leave DEFAULT 0, bump watermark so reads distrust this window.
				update_option( self::FILTERED_WATERMARK_OPTION, time(), true );
			} else {
				$query_data['filtered'] = $flag;
			}
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table_name}` WHERE cid = %s", $BBCS->cid ) );
		if ( $existing > 0 ) {
			$wpdb->update( $table_name, $query_data, array( 'cid' => $BBCS->cid ) );
		} else {
			$wpdb->insert( $table_name, $query_data );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( is_array( $BBCS->post_antidetect_scope ) && ! empty( $BBCS->post_antidetect_scope['browserFingerprint'] ) ) {
			self::trackFingerprint(
				$BBCS->post_antidetect_scope['browserFingerprint'],
				$BBCS->ip,
				$reason === null ? 0 : (int) $reason,
				$BBCS->country ?? ''
			);
		}
	}

	public static function localCheckResult( string $status, string $msg, ?string $cookie = null ): array {
		global $wpdb;
		$BBCS = BotBlocker::getInstance();

		$safe_msg = sanitize_text_field( (string) $msg );

		if ( $status === BBCS_LOCAL_RESULT_COOKIE ) {
			$payload = array( 'cookie' => (string) $cookie );
			$passed  = 1;
			if ( $safe_msg === 'ALLOW By rule: timezone=' . $BBCS->post_timezone ) {
				$passed = 4;
			}
			$result = $safe_msg;
		} else {
			$passed = 9;
			$result = $safe_msg;

			if ( $safe_msg === 'BLOCK By rule: timezone=' . $BBCS->post_timezone ) {
				$safe_msg = $BBCS->js_error_message;
				$passed   = 6;
			}

			$payload = array( 'error' => $safe_msg );
		}

		if ( $BBCS->settings->botblocker_log_tests == 1 ) {
			$data_fields = array(
				'ipv4'        => ! empty( $BBCS->post_ipv4_value ) ? $BBCS->post_ipv4_value : BOTBLOCKER_EMPTY,
				'generation2' => round( microtime( true ) - $BBCS->post_start_time, 4 ),
				'recaptcha'   => ! empty( $BBCS->post_recaptcha_score ) ? $BBCS->post_recaptcha_score : BOTBLOCKER_EMPTY,
				'js_w'        => ! empty( $BBCS->post_width ) ? $BBCS->post_width : BOTBLOCKER_EMPTY,
				'js_h'        => ! empty( $BBCS->post_height ) ? $BBCS->post_height : BOTBLOCKER_EMPTY,
				'js_cw'       => ! empty( $BBCS->post_client_width ) ? $BBCS->post_client_width : BOTBLOCKER_EMPTY,
				'js_ch'       => ! empty( $BBCS->post_client_height ) ? $BBCS->post_client_height : BOTBLOCKER_EMPTY,
				'js_co'       => ! empty( $BBCS->post_color_depth ) ? $BBCS->post_color_depth : BOTBLOCKER_EMPTY,
				'js_pi'       => ! empty( $BBCS->post_pixel_depth ) ? $BBCS->post_pixel_depth : BOTBLOCKER_EMPTY,
				'adblock'     => ! empty( $BBCS->post_adblocker_found ) ? $BBCS->post_adblocker_found : BOTBLOCKER_EMPTY,
				'timezone'    => ! empty( $BBCS->post_timezone ) ? $BBCS->post_timezone : BOTBLOCKER_EMPTY,
				'passed'      => ! empty( $passed ) ? $passed : BOTBLOCKER_EMPTY,
				'result'      => $result != BBCS_RULE_GRAY ? $result : BOTBLOCKER_EMPTY,
				'cloud_data'  => ! empty( $BBCS->cloud_data )
					? wp_json_encode( $BBCS->cloud_data, JSON_UNESCAPED_UNICODE )
					: BOTBLOCKER_EMPTY,
				'fp'          => ! empty( $BBCS->post_antidetect_scope )
					? wp_json_encode( $BBCS->post_antidetect_scope, JSON_UNESCAPED_UNICODE )
					: BOTBLOCKER_EMPTY,
			);

			foreach ( $data_fields as $key => $value ) {
				if ( empty( $value ) ) {
					$data_fields[ $key ] = BOTBLOCKER_EMPTY;
				}
			}

			$data = $data_fields;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$update = $wpdb->update(
				$wpdb->bbcs_hits,
				$data,
				array( 'cid' => $BBCS->cid )
			);

			if ( $wpdb->rows_affected == 0 ) {
				$wpdb->update(
					$wpdb->bbcs_hits_suspicious,
					$data,
					array( 'cid' => $BBCS->cid )
				);
			}
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return $payload;
	}

	public static function trackFingerprint( string $fp, string $ip, int $reason, string $country ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM `{$wpdb->bbcs_fingerprint}` WHERE fingerprint = %s", $fp
		) );

		$now      = time();
		$code     = bbcs_codeList( $reason );
		$is_block = ( $reason > 0 && ! empty( $code ) && ! $code['allow'] );

		if ( $existing ) {
			$update = array(
				'last_seen' => $now,
				'ip'        => $ip,
			);

			$existing_block_count = (int) $existing->block_count;
			$existing_allow_count = (int) $existing->allow_count;
			$existing_status      = (string) $existing->status;

			if ( $is_block ) {
				$update['block_count']       = $existing_block_count + 1;
				$update['last_block_reason'] = ! empty( $code['msg'] ) ? $code['msg'] : '';
				$update['last_country']      = $country;
			} else {
				$update['allow_count'] = $existing_allow_count + 1;
			}

			$new_allow = $is_block ? $existing_allow_count : $existing_allow_count + 1;
			$new_block = $is_block ? $existing_block_count + 1 : $existing_block_count;

			if ( $new_allow > 10 && $existing_status !== 'blocked' ) {
				$update['status'] = 'trusted';
			} elseif ( $new_block >= 3 ) {
				$update['status'] = 'blocked';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->bbcs_fingerprint, $update, array( 'fingerprint' => $fp ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $wpdb->bbcs_fingerprint, array(
				'fingerprint'       => $fp,
				'ip'                => $ip,
				'first_seen'        => $now,
				'last_seen'         => $now,
				'block_count'       => $is_block ? 1 : 0,
				'allow_count'       => $is_block ? 0 : 1,
				'last_block_reason' => ( $is_block && ! empty( $code['msg'] ) ) ? $code['msg'] : '',
				'last_country'      => $country,
				'status'            => 'watch',
			) );
		}
	}

	public static function checkFingerprint( string $fp ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT status, block_count, last_block_reason FROM `{$wpdb->bbcs_fingerprint}` WHERE fingerprint = %s",
			$fp
		), ARRAY_A );
		return $row ?: null;
	}
}
