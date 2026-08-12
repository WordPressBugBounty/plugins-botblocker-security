<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxHits {

	private static function clean_modal_row( array $row ): array {
		$empty_marker = defined( 'BOTBLOCKER_EMPTY' ) ? BOTBLOCKER_EMPTY : '-';
		$modal        = array(
			'ip'           => ( isset( $row['ip'] ) && $row['ip'] !== $empty_marker ) ? (string) $row['ip'] : '',
			'useragent'    => ( isset( $row['useragent'] ) && $row['useragent'] !== $empty_marker ) ? (string) $row['useragent'] : '',
			'ptr'          => ( isset( $row['ptr'] ) && $row['ptr'] !== $empty_marker ) ? (string) $row['ptr'] : '',
			'referer'      => ( isset( $row['referer'] ) && $row['referer'] !== $empty_marker ) ? (string) $row['referer'] : '',
			'country_name' => ( isset( $row['country_name'] ) && $row['country_name'] !== $empty_marker ) ? (string) $row['country_name'] : '',
			'asname'       => ( isset( $row['asname'] ) && $row['asname'] !== $empty_marker ) ? (string) $row['asname'] : '',
			'asnum'        => ( isset( $row['asnum'] ) && $row['asnum'] !== $empty_marker ) ? (string) $row['asnum'] : '',
			'name_lang'    => ( isset( $row['name_lang'] ) && $row['name_lang'] !== $empty_marker ) ? (string) $row['name_lang'] : '',
		);

		if ( ! empty( $modal['ptr'] ) && filter_var( $modal['ptr'], FILTER_VALIDATE_IP ) ) {
			$modal['ptr'] = '';
		}
		if ( $modal['asnum'] !== '' && ! is_numeric( $modal['asnum'] ) ) {
			$modal['asnum'] = '';
		}
		if ( ! empty( $modal['country_name'] ) && in_array( trim( $modal['country_name'] ), array( 'Unknown', '--' ), true ) ) {
			$modal['country_name'] = '';
		}

		return $modal;
	}

	/**
	 * Helper function to handle the common logic for retrieving bot blocker hits.
	 *
	 * @param string $where Additional WHERE conditions for the query.
	 * @return void
	 */
	public static function handleGetHits( string $where ): void {
		$bbcs_action = 'hits_get_hits';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		/**
		 * REVIEWER NOTE:
		 * - Table names are sanitized identifiers; $wpdb->prepare() cannot bind identifiers.
		 * - `$where` is built internally (no direct user input). Extra search term is sanitized with esc_like() and placeholders.
		 * - UNION + subquery force us to keep the SQL in variables; WPCS treats this as "NotPrepared" even though VALUES are bound.
		 * - Suppression is scoped only around those lines.
		 */
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
		$BBCS = BotBlocker::getInstance();

		$start  = isset( $_POST['start'] ) ? absint( wp_unslash( $_POST['start'] ) ) : 0;
		$length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
		$draw   = isset( $_POST['draw'] ) ? absint( wp_unslash( $_POST['draw'] ) ) : 0;

		$search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' search=' . $search . ' start=' . $start . ' length=' . $length );
		}

		$gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;

		if ( $search !== '' ) {
			$like               = '%' . $wpdb->esc_like( $search ) . '%';
			$gmt_offset_seconds = (int) round( $gmt_offset * HOUR_IN_SECONDS );
			$search_fields      = array(
				'ip',
				'ptr',
				'asnum',
				'asname',
				'country',
				'country_name',
				'lang',
				'name_lang',
				'useragent',
				'browser',
				'os',
				'device',
				'referer',
				'page',
				'method',
				'result',
				'passed',
				'cid',
			);
			$search_parts       = array();
			$search_values      = array();

			foreach ( $search_fields as $field ) {
				$search_parts[]  = "CAST({$field} AS CHAR) LIKE %s";
				$search_values[] = $like;
			}

			$search_parts[]  = "CONCAT('AS', asnum) LIKE %s";
			$search_values[] = $like;
			$search_parts[]  = "DATE_FORMAT(" . BotBlockerDb::localDatetimeExpr( 'date' ) . ", '%%Y-%%m-%%d %%H:%%i:%%s') LIKE %s";
			$search_values[] = $gmt_offset_seconds;
			$search_values[] = $like;

			$matched_codes = array();
			if ( function_exists( 'bbcs_codeList' ) ) {
				for ( $code = 0; $code <= 100; $code++ ) {
					$code_data = bbcs_codeList( $code );
					$message   = isset( $code_data['msg'] ) ? wp_strip_all_tags( (string) $code_data['msg'] ) : '';

					if ( $message !== '' && $message !== 'Unknown code' && stripos( $message, $search ) !== false ) {
						$matched_codes[] = $code;
					}
				}
			}

			if ( ! empty( $matched_codes ) ) {
				$matched_codes  = array_values( array_unique( $matched_codes ) );
				$search_parts[] = 'passed IN (' . implode( ', ', array_fill( 0, count( $matched_codes ), '%d' ) ) . ')';

				foreach ( $matched_codes as $code ) {
					$search_values[] = $code;
				}
			}

			$search_condition = $wpdb->prepare(
	            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				'(' . implode( ' OR ', $search_parts ) . ')',
				...$search_values
			);
			$where = trim( $where );
			if ( $where === '' ) {
				$where = 'WHERE ' . $search_condition;
			} else {
				$where .= ' AND ' . $search_condition;
			}
		}

		$where_clause = trim( $where );

		$union_query = "
	        SELECT date, cid, ip, ptr, asnum, asname, lang, name_lang, country_name, useragent,
	               js_w, js_h, js_cw, js_ch, js_co, js_pi, adblock, country, referer, page,
	               passed, result, method, browser, os, device
	        FROM (
	            SELECT * FROM `{$wpdb->bbcs_hits}`
	            UNION ALL
	            SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
	        ) AS combined_hits
	        {$where_clause}
	    ";

		$total_query = "SELECT (SELECT COUNT(*) FROM `{$wpdb->bbcs_hits}` {$where_clause}) + (SELECT COUNT(*) FROM `{$wpdb->bbcs_hits_suspicious}` {$where_clause})";
		// REVIEWER NOTE: Query string is dynamically built from sanitized input data.
		// $where is constructed internally by the plugin and all user input is sanitized
		// (esc_like, absint, sanitize_text_field) and values are prepared where possible.
	     // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total = (int) $wpdb->get_var( $total_query );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' total=' . $total . ' last_error=' . $wpdb->last_error );
		}
		$query = "{$union_query} ORDER BY date DESC LIMIT {$start}, {$length}";
		// REVIEWER NOTE: Query string is dynamically built from sanitized input data.
		// $where is constructed internally by the plugin and all user input is sanitized
		// (esc_like, absint, sanitize_text_field) and values are prepared where possible.
	     // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $wpdb->get_results( $query, ARRAY_A );

		$data = array();
		foreach ( (array) $results as $row ) {
			$datetime = new \DateTime( "@{$row['date']}", new \DateTimeZone( 'UTC' ) );

			if ( $gmt_offset !== 0.0 ) {
				$hours    = floor( abs( $gmt_offset ) );
				$minutes  = ( abs( $gmt_offset ) * 60 ) % 60;
				$interval = new \DateInterval( 'PT' . $hours . 'H' . $minutes . 'M' );
				if ( $gmt_offset > 0 ) {
					$datetime->add( $interval );
				} else {
					$datetime->sub( $interval );
				}
			}

			$data[] = array(
				'datetime' => array(
					'm'    => $row['method'],
					'date' => $datetime->format( 'Y-m-d' ),
					'time' => $datetime->format( 'H:i:s' ),
				),
				'ip_info'  => array(
					'ip'  => $row['ip'],
					'ptr' => $row['ptr'],
				),
				'as_info'  => array(
					'asnum'  => $row['asnum'],
					'asname' => $row['asname'],
				),
				'c_info'   => array(
					'c'  => $row['country'],
					'cn' => $row['country_name'],
					'l'  => $row['lang'],
					'ln' => $row['name_lang'],
				),
				'u_info'   => array(
					'br' => $row['browser'],
					'os' => $row['os'],
					'd'  => $row['device'],
					'ua' => $row['useragent'],
				),
				'p_info'   => array(
					'p' => $row['page'],
					'r' => $row['referer'],
				),
				'js_info'  => array(
					'js_w'  => $row['js_w'] !== '0' ? $row['js_w'] : BOTBLOCKER_EMPTY,
					'js_h'  => $row['js_h'] !== '0' ? $row['js_h'] : BOTBLOCKER_EMPTY,
					'js_cw' => $row['js_cw'] !== '0' ? $row['js_cw'] : BOTBLOCKER_EMPTY,
					'js_ch' => $row['js_ch'] !== '0' ? $row['js_ch'] : BOTBLOCKER_EMPTY,
					'js_co' => $row['js_co'] !== '0' ? $row['js_co'] : BOTBLOCKER_EMPTY,
					'js_pi' => $row['js_pi'] !== '0' ? $row['js_pi'] : BOTBLOCKER_EMPTY,
					'ad'    => $row['adblock'] !== '0' ? $row['adblock'] : BOTBLOCKER_EMPTY,
					'cid'   => $row['cid'] !== '' ? $row['cid'] : BOTBLOCKER_EMPTY,
				),
				'modal'    => self::clean_modal_row( $row ),
				'r_info'   => array(
					'passed' => $row['passed'],
					'pi'     => bbcs_codeList( $row['passed'] )['msg'],
					'result' => $row['result'],
				),
			);
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' data_count=' . count( $data ) . ' sending response' );
		}
		wp_send_json(
			array(
				'draw'            => $draw,
				'recordsTotal'    => $total,
				'recordsFiltered' => $total,
				'data'            => $data,
			)
		);
	}

	/**
	 * AJAX handler for retrieving bot blocker hits data.
	 */
	public static function handleGetBotHits(): void {
		$bbcs_action = 'hits_get_bot_hits';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		global $wpdb;
		$where                       = 'WHERE ' . BBCS_SQL_PAGE_NOT_LIKE . " AND method = 'GET'";
		[$ip_not_in_sql, $ip_params] = BotBlockerDb::getIPNotLikeSQL();
		// REVIEWER NOTE: IP values are sourced from the plugin's own
		// stored rules (not raw user input) and are fully escaped via $wpdb->prepare().
	    // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared
		$where .= empty( $ip_params ) ? $ip_not_in_sql : $wpdb->prepare( $ip_not_in_sql, ...$ip_params );
		self::handleGetHits( $where );
	}

	/**
	 * AJAX handler for retrieving bot blocker admin hits data.
	 */
	public static function handleGetAdminHits(): void {
		$bbcs_action = 'hits_get_admin_hits';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		$where = 'WHERE ' . BBCS_SQL_PAGE_LIKE_ADMIN . ' AND ' . BBCS_SQL_PAGE_NOT_LIKE_WP . " AND method = 'GET'";
		self::handleGetHits( $where );
	}

	/**
	 * AJAX handler for retrieving other bot blocker hits data.
	 */
	public static function handleGetOtherHits(): void {
		$bbcs_action = 'hits_get_other_hits';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		$where = 'WHERE ' . BBCS_SQL_PAGE_NOT_LIKE_ADMIN . ' AND ' . BBCS_SQL_PAGE_LIKE_WP . " AND method = 'GET'";
		self::handleGetHits( $where );
	}

	/**
	 * AJAX handler for retrieving all bot blocker hits data.
	 */
	public static function handleGetAllHits(): void {
		$bbcs_action = 'hits_get_all_hits';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		$where = '';
		self::handleGetHits( $where );
	}

	public static function handleHitToRule(): void {
		$bbcs_action = 'hits_hit_to_rule';
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

		if (
			! isset( $_POST['type'] ) || empty( $_POST['type'] ) ||
			! isset( $_POST['data'] ) || empty( $_POST['data'] )
		) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' type or data empty FAILED' );
			}
			wp_send_json_error( __( 'Type or data is not set or empty.', 'botblocker-security' ) );
		}

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' type=' . $type );
		}
		if ( $type == BBCS_IP_TYPE_IP ) {
			$ip = sanitize_text_field( wp_unslash( $_POST['data'] ) );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' ip=' . $ip );
			}

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$typeIP = 'IPv4';
			} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$typeIP = 'IPv6';
			} else {
				$typeIP = BBCS_IP_TYPE_INVALID;
			}

			if ( $typeIP === BBCS_IP_TYPE_INVALID ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid IP FAILED' );
				}
				wp_send_json_error( __( 'Invalid IP address provided.', 'botblocker-security' ) );
			}

			if ( $typeIP === 'IPv4' ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' typeIP=IPv4 triggering create_ipv4_rule' );
				}
				// Forward the IP so the handler finds it in $_POST['ip']
				$_POST['ip'] = $ip;
	            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_ajax_bbcs_create_ipv4_rule' );
			} else {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' typeIP=IPv6 triggering create_ipv6_rule' );
				}
				// Forward the IP so the handler finds it in $_POST['ip']
				$_POST['ip'] = $ip;
	            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_ajax_bbcs_create_ipv6_rule' );
			}
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' type=' . $type . ' triggering create_rule' );
			}
			// Other types - delegate to generic rule handler
	        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'wp_ajax_bbcs_create_rule' );
		}
	}
}
