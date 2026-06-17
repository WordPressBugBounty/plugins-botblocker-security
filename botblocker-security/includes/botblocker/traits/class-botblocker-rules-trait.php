<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerRulesTrait {

	private function get_asn_rule(): ?string {
		if ( empty( $this->bbcs_asn ) || ! is_array( $this->bbcs_asn ) ) {
			return null;
		}

		$asnum = $this->get_normalized_asnum();
		if ( $asnum === '' || ! isset( $this->bbcs_asn[ $asnum ] ) ) {
			return null;
		}

		$rule = strtolower( trim( (string) $this->bbcs_asn[ $asnum ] ) );
		return in_array( $rule, array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_DARK, BBCS_RULE_GRAY ), true ) ? $rule : null;
	}

	private function get_normalized_asnum(): string {
		if ( empty( $this->asnum ) || $this->asnum === BOTBLOCKER_EMPTY ) {
			return '';
		}

		return preg_replace( '/[^0-9]/', '', (string) $this->asnum );
	}

	private function has_matching_asn_token( array $tokens ): bool {
		$asnum = $this->get_normalized_asnum();
		if ( $asnum === '' ) {
			return false;
		}

		foreach ( $tokens as $token ) {
			if (
				is_string( $token ) &&
				strncmp( $token, 'asn:', 4 ) === 0 &&
				preg_replace( '/[^0-9]/', '', substr( $token, 4 ) ) === $asnum
			) {
				return true;
			}
		}
		return false;
	}

	private function has_pending_restrictive_response(): bool {
		return $this->visitorType === self::VISITOR_FAKEBOT
			|| $this->should_show_denied_page === true
			|| $this->should_show_block_page === true
			|| $this->should_show_check_page === true
			|| (int) $this->suspect_status === 2;
	}

	/**
	 * Resolve a rule sign for the current visitor.
	 *
	 * A DARK rule only asks the visitor to prove they are human. A visitor who
	 * already holds a valid verification cookie has proven that, so a DARK rule
	 * simply does not apply to them and is neutralized to BBCS_RULE_NOOP (no
	 * dispatch branch matches -> the visitor falls through and is counted as a
	 * normal human hit). BLOCK / GRAY / ALLOW are never affected: a ban or an
	 * allow is not lifted by holding a cookie.
	 */
	private function effective_sign( $sign ) {
		if ( $sign === BBCS_RULE_DARK && $this->is_verified() ) {
			return BBCS_RULE_NOOP;
		}
		return $sign;
	}

	private function should_allow_whatsapp_preview_by_useragent(): bool {
		return ! empty( $this->settings->whitelist_whatsapp_preview )
			&& preg_match( '/(?<![A-Za-z0-9_])WhatsApp\/\d+\.\d+\.\d+\.\d+(?![\d.])/', (string) $this->useragent ) === 1;
	}

	private function is_allowed_asn_for_white_bot( array $tokens ): bool {
		return $this->has_matching_asn_token( $tokens ) && $this->get_asn_rule() === BBCS_RULE_ALLOW;
	}

	private function has_matching_ip_token( array $tokens ): bool {
		$visitor_ip = (string) $this->ip;
		foreach ( $tokens as $token ) {
			if ( ! is_string( $token ) || strncmp( $token, 'ip:', 3 ) !== 0 ) {
				continue;
			}
			$value = substr( $token, 3 );
			if ( strpos( $value, '/' ) !== false ) {
				if ( BotBlockerIp::matches( $value, $visitor_ip ) ) {
					return true;
				}
			} elseif ( $value === $visitor_ip ) {
				return true;
			}
		}
		return false;
	}

	public function check_options_preflight(): bool {
		if ( ! isset( $this->settings->options_preflight ) || $this->settings->options_preflight != 1 ) {
			return false;
		}
		if ( $this->request_method !== 'OPTIONS' ) {
			return false;
		}
		if (
			isset( $_SERVER['HTTP_ORIGIN'] )
			&& isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] )
		) {
			return true;
		}
		return false;
	}

	public function check_llm_bot(): bool {
		if ( method_exists( $this, 'lazy_load_llm_trusted' ) ) {
			$this->lazy_load_llm_trusted();
		}
		foreach ( $this->bbcs_llm as $provider ) {
			$search = isset( $provider['search'] ) ? (string) $provider['search'] : '';
			if ( $search === '' ) {
				continue;
			}

			$matched_token = '';
			foreach ( preg_split( '/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
				if ( stripos( $this->useragent, $token ) !== false ) {
					$matched_token = $token;
					break;
				}
			}
			if ( $matched_token === '' ) {
				continue;
			}

			$cidrs = $provider['ranges'] ?? array();

			if ( empty( $cidrs ) ) {
				return false;
			}

			foreach ( $cidrs as $cidr ) {
				if ( BotBlockerIp::matches( $cidr, $this->ip ) ) {
					$this->result_of_action = "Legal LLM bot: {$matched_token}";
					$this->white_bot        = $matched_token;
					$this->visitorType      = self::VISITOR_LEGALBOT;
					BotBlockerStore::storeData( null, 5 );
					BotBlockerCounters::processHit( 5 );
					return true;
				}
			}

			$this->redirect_to_denied( 7, "Fake LLM bot: {$matched_token}" );
			return true;
		}

		return false;
	}

	// Nonce is intentionally omitted - this is a shared-secret emergency URL (param name + value are both salted hashes), not a CSRF-vulnerable form submission.
	public function check_secret_parameter(): bool {
		$param = $this->settings->secret_botblocker_get_param;
		if ( $param != '' && $param != BOTBLOCKER_EMPTY ) {
			$cookie_set = isset( $_COOKIE[ $param ] );

			if ( isset( $_GET[ $param ] ) || $cookie_set ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->x_robots_tag['noindex'] = 'noindex';
				$this->visitorType             = self::VISITOR_SECRET;

				if ( ! $cookie_set ) {
					$this->set_secret_cookie();
				}

				if ( isset( $_GET[ $param ] ) && hash_equals( (string) $this->action_disable, sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->isDisabled = true;
					BotBlockerStore::storeData( 'BotBlocker skip by secret', 23 );
					BotBlockerCounters::processHit( 23 );
					return true;
				}

				if ( isset( $_GET[ $param ] ) && hash_equals( (string) $this->action_off, sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->result_of_action = 'BotBlocker stop by secret';
					BotBlockerDb::togglePower( 1 );
					BotBlockerStore::storeData( 'BotBlocker stop by secret', 23 );
					BotBlockerCounters::processHit( 23 );

					delete_transient( 'bbcs_site_health_list' );
					delete_transient( 'bbcs_site_health' );

					return true;
				}

				if ( isset( $_GET[ $param ] ) && hash_equals( (string) $this->action_on, sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->result_of_action = 'BotBlocker start by secret';
					BotBlockerStore::storeData( 'BotBlocker start by secret', 98 );
					BotBlockerCounters::processHit( 98 );
					BotBlockerDb::togglePower( 0 );

					delete_transient( 'bbcs_site_health_list' );
					delete_transient( 'bbcs_site_health' );

					return false;
				}
			}
		}
		return false;
	}

	public function check_path_rules(): bool {
		if ( $this->has_pending_restrictive_response() ) {
			return true;
		}

		if ( method_exists( $this, 'lazy_load_paths' ) ) {
			$this->lazy_load_paths();
		}
		foreach ( $this->bbcs_path as $bbcs_line => $bbcs_sign ) {
			if ( stripos( $this->uri, $bbcs_line ) !== false ) {
				$bbcs_sign = $this->effective_sign( $bbcs_sign );
				if ( $bbcs_sign === BBCS_RULE_BLOCK ) {
					$this->redirect_to_block( 6, 'BLOCK By rule (url part): ' . $bbcs_line );
				} elseif ( $bbcs_sign === BBCS_RULE_DARK ) {
					$this->redirect_to_dark( 'DARK By rule (url part): ' . $bbcs_line );
				} elseif ( $bbcs_sign === BBCS_RULE_GRAY ) {
					$this->set_gray_status( 'GRAY By rule (url part): ' . $bbcs_line );
				} elseif ( $bbcs_sign === BBCS_RULE_ALLOW ) {
					if ( $this->has_pending_restrictive_response() ) {
						continue;
					}
					$this->visitorType = self::VISITOR_LEGALBOT;
					if ( $this->settings->botblocker_log_allow == 1 ) {
						BotBlockerStore::storeData( 'Allow access to path: ' . $bbcs_line, 4 );
					}
					BotBlockerCounters::processHit( 4 );
					break;
				}
			}
		}
		if ( $this->visitorType == self::VISITOR_LEGALBOT ) {
			return true;
		}
		return $this->has_pending_restrictive_response();
	}

	public function check_white_bot(): bool {
		if ( method_exists( $this, 'lazy_load_search_engines' ) ) {
			$this->lazy_load_search_engines();
		}
		// UA match + PTR or ASN-table verification.
		foreach ( $this->bbcs_se as $bbcs_line => $bbcs_sign ) {
			if ( stripos( $this->useragent, $bbcs_line ) === false ) {
				continue;
			}

			$rule = $this->effective_sign( $this->bbcs_rule[ $bbcs_line ] );

			if ( $rule === BBCS_RULE_BLOCK ) {
				$this->redirect_to_block( 6, "1 - <b>block</b> by user-agent: {$bbcs_line}" );
				break;
			} elseif ( $rule === BBCS_RULE_DARK ) {
				$this->redirect_to_dark( "1 - <b>dark</b> by user-agent: {$bbcs_line}" );
				break;
			} elseif ( $rule === BBCS_RULE_GRAY ) {
				$this->set_gray_status( "1 - <b>gray</b> by user-agent: {$bbcs_line}" );
				break;
			} elseif ( $rule === BBCS_RULE_ALLOW ) {
				if ( strtolower( (string) $bbcs_line ) === 'whatsapp' ) {
					if ( empty( $this->settings->whitelist_whatsapp_preview ) ) {
						continue;
					}

					if ( ! $this->should_allow_whatsapp_preview_by_useragent() ) {
						$this->redirect_to_denied( 7, "Fake WhatsApp preview detected: {$bbcs_line}" );
						break;
					}

					$this->result_of_action = 'WhatsApp preview by user-agent: ' . $bbcs_line;
					$this->white_bot        = $bbcs_line;
					$this->visitorType      = self::VISITOR_LEGALBOT;
					break;
				}

				if ( $this->is_allowed_asn_for_white_bot( $bbcs_sign ) ) {
					$this->result_of_action = 'Legal bot by ASN token: ' . $bbcs_line . ' (asn:' . (string) $this->asnum . ')';
					$this->white_bot        = $bbcs_line;
					$this->visitorType      = self::VISITOR_LEGALBOT;
					break;
				}

				if ( $this->has_matching_ip_token( $bbcs_sign ) ) {
					$this->result_of_action = 'Legal bot by IP token: ' . $bbcs_line . ' (ip:' . (string) $this->ip . ')';
					$this->white_bot        = $bbcs_line;
					$this->visitorType      = self::VISITOR_LEGALBOT;
					break;
				}

				// Strip asn: and ip: tokens - PTR verification only works with domain suffixes.
				$ptr_tokens = array();
				foreach ( $bbcs_sign as $token ) {
					if ( ! is_string( $token ) || strncmp( $token, 'asn:', 4 ) === 0 || strncmp( $token, 'ip:', 3 ) === 0 ) {
						continue;
					}
					$ptr_tokens[] = $token;
				}
				if ( empty( $ptr_tokens ) ) {
					$this->redirect_to_denied( 7, "Fake bot detected: {$bbcs_line}" );
				} elseif ( BotBlockerIp::isWhiteBot( $this->ip, $ptr_tokens, $this->time, $this->settings->ptrcache_time ) === true ) {
					if ( ! in_array( '.', $ptr_tokens, true ) ) {
						BotBlockerDb::storePTRrule();
					}
					$this->result_of_action = "Legal bot detected: {$bbcs_line}";
					$this->white_bot        = $bbcs_line;
					$this->visitorType      = self::VISITOR_LEGALBOT;
				} else {
					$this->redirect_to_denied( 7, "Fake bot detected: {$bbcs_line}" );
				}
				break;
			}
		}

		if ( $this->visitorType == self::VISITOR_LEGALBOT ) {
			BotBlockerStore::storeData( null, 5 );
			BotBlockerCounters::processHit( 5 );
			return true;
		}
		return false;
	}

	public function check_asn_rules(): bool {
		if ( $this->has_pending_restrictive_response() ) {
			return true;
		}

		if ( method_exists( $this, 'lazy_load_asn_rules' ) ) {
			$this->lazy_load_asn_rules();
		}
		$rule = $this->get_asn_rule();
		if ( $rule === null ) {
			return false;
		}

		$search    = 'asn=' . esc_sql( (string) $this->asnum );
		$rule_data = array(
			'rule'   => $rule,
			'search' => $search,
			'asnum'  => $this->asnum,
		);

		$rule = $this->effective_sign( $rule );

		if ( $rule === BBCS_RULE_ALLOW ) {
			if ( (int) $this->suspect_status > 0 ) {
				return false;
			}
			$this->result_of_action = '<b>Allow</b> by ASN rule: ' . $this->asnum;
			return $this->allow_access( $rule_data );
		} elseif ( $rule === BBCS_RULE_BLOCK ) {
			$this->redirect_to_block( 6, 'BLOCK by ASN rule: ' . $this->asnum, $rule_data );
			return true;
		} elseif ( $rule === BBCS_RULE_DARK ) {
			$this->redirect_to_dark( 'DARK by ASN rule: ' . $this->asnum );
			return true;
		} elseif ( $rule === BBCS_RULE_GRAY ) {
			$this->set_gray_status( 'GRAY by ASN rule: ' . $this->asnum );
		}

		return false;
	}

	public function check_rules_database(): bool {
		if ( $this->has_pending_restrictive_response() ) {
			return true;
		}

		global $wpdb;
		$rule        = null;
		$search      = null;
		$rule_data   = null;
		$bbcs_search = array();
		if ( ! empty( $this->useragent ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'useragent=' . $this->useragent ) . '%' );
		}
		if ( ! empty( $this->country ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'country=' . $this->country ) . '%' );
		}
		if ( ! empty( $this->lang ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'lang=' . $this->lang ) . '%' );
		}
		if ( ! empty( $this->page ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'page=' . $this->page ) . '%' );
		}
		if ( ! empty( $this->refhost ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'referer=' ) . '%' . $wpdb->esc_like( $this->refhost ) . '%' );
		}
		if ( ! empty( $this->ym_uid ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'ym_uid=' . $this->ym_uid ) . '%' );
		}
		if ( ! empty( $this->ga_uid ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'ga_uid=' . $this->ga_uid ) . '%' );
		}
		$this->ptr_arr = explode( '.', $this->ptr );
		$this->ptr_arr = array_reverse( $this->ptr_arr, false );
		if ( isset( $this->ptr_arr[1] ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'ptr=' . $this->ptr_arr[1] . '.' . $this->ptr_arr[0] ) . '%' );
		}
		if ( isset( $this->ptr_arr[2] ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'ptr=' . $this->ptr_arr[2] . '.' . $this->ptr_arr[1] . '.' . $this->ptr_arr[0] ) . '%' );
		}
		if ( ! empty( $this->asname ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'asname=' . $this->asname ) . '%' );
		}
		if ( ! empty( $this->asnum ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'asnum=' . $this->asnum ) . '%' );
		}
		if ( ! empty( $this->uri ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'uri=' . $this->uri ) . '%' );
		}
		if ( ! empty( $_SERVER['SCRIPT_NAME'] ) ) {
			$bbcs_search[] = $wpdb->prepare(
				'search LIKE %s',
				'%' . $wpdb->esc_like( 'scriptname=' . trim( wp_strip_all_tags( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) ) . '%'
			);
		}
		if ( ! empty( $this->http_accept ) ) {
			$bbcs_search[] = $wpdb->prepare( 'search LIKE %s', '%' . $wpdb->esc_like( 'httpaccept=' . trim( wp_strip_all_tags( $this->http_accept ) ) ) . '%' );
		}

		$bbcs_test_visitor = array();
		if ( ! empty( $bbcs_search ) ) {
			$query = "SELECT * FROM `{$wpdb->bbcs_rules}` WHERE " . implode( ' OR ', $bbcs_search ) . ' ORDER BY priority ASC';
			// REVIEWER NOTE: Dynamic WHERE clause is built using $wpdb->prepare() for each filter to ensure all values are properly escaped.
			// The final query string is assembled from individually prepared clauses. This pattern is secure and prevents SQL injection.
			// Suppressing the warning with phpcs:ignore is required for dynamic filtering in WordPress plugins.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$bbcs_test_visitor = $wpdb->get_results( $query, ARRAY_A );
		}

		foreach ( $bbcs_test_visitor as $echo ) {
			if ( $echo['disable'] == '0' ) {
				$rule      = $echo['rule'];
				$search    = isset( $echo['search'] ) ? $echo['search'] : null;
				$rule_data = $echo;

				break;
			}
		}

		if ( $rule === null ) {

			return false;
		}

		$rule = $this->effective_sign( $rule );

		if ( $rule === BBCS_RULE_ALLOW ) {
			if ( $this->allow_access( $rule_data ) ) {
				return true;
			}
		} elseif ( $rule === BBCS_RULE_BLOCK ) {
			$this->redirect_to_block( 6, 'BLOCK By rule: ' . $search, $rule_data );
		} elseif ( $rule === BBCS_RULE_DARK ) {
			$this->rule_record_id = isset( $rule_data['id'] ) ? $rule_data['id'] : null;
			$this->redirect_to_dark( 'DARK By rule: ' . $search );
		} elseif ( $rule === BBCS_RULE_GRAY ) {
			$this->set_gray_status( 'GRAY By rule: ' . $search );
		}
		return $rule === BBCS_RULE_ALLOW || $this->has_pending_restrictive_response();
	}

	public function check_ip_rules(): bool {
		if ( $this->has_pending_restrictive_response() ) {
			return true;
		}

		global $wpdb;
		$table_name = $this->ip_version == 4 ? $wpdb->bbcs_ipv4rules : $wpdb->bbcs_ipv6rules;

		if ( $this->ip_version == 4 ) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$bbcs_ip_test = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
					0,
					$this->ipnum,
					$this->ipnum
				),
				ARRAY_A
			);
		} else {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$bbcs_ip_test = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
					0,
					$this->ipnum,
					$this->ipnum
				),
				ARRAY_A
			);
		}

		if ( $bbcs_ip_test ) {
			if ( $bbcs_ip_test['expires'] <= $this->time ) {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table_name, array( 'id' => $bbcs_ip_test['id'] ) );
				$bbcs_ip_test['rule'] = BBCS_RULE_GRAY;

			}
			$ip_sign = $this->effective_sign( $bbcs_ip_test['rule'] );
			if ( $ip_sign === BBCS_RULE_ALLOW ) {

				if ( (int) $bbcs_ip_test['readonly'] === 1 && ( $bbcs_ip_test['comment'] === 'IPv4 BotBlocker Server' || $bbcs_ip_test['comment'] === 'IPv6 BotBlocker Server' ) ) {
					$this->visitorType = self::VISITOR_BOTBLOCKER;
					$this->isDisabled  = true;
					BotBlockerStore::storeData( 'BotBlocker server', 99 );
					BotBlockerCounters::processHit( 99 );
					return true; // BotBlocker Server activating cloud API or update bot database
				}

				$this->result_of_action = '<b>Allow</b> by IP rule: ' . $bbcs_ip_test['search'];
				return $this->allow_access( $bbcs_ip_test );
			} elseif ( $ip_sign === BBCS_RULE_BLOCK ) {
				$this->redirect_to_block( 6, 'BLOCK by IP rule: ' . $bbcs_ip_test['search'], $bbcs_ip_test );
			} elseif ( $ip_sign === BBCS_RULE_DARK ) {
				$this->redirect_to_dark( 'DARK by IP rule: ' . $bbcs_ip_test['search'] );
			} elseif ( $ip_sign === BBCS_RULE_GRAY ) {
				$this->set_gray_status( 'GRAY by IP rule: ' . $bbcs_ip_test['search'] );
			}
		}

		if ( $this->visitorType === self::VISITOR_HUMAN ) {
			BotBlockerStore::storeData( null, 4 );
			BotBlockerCounters::processHit( 4 );
			return true;
		}

		return false;
	}

	public function check_rugov_rules(): bool {
		if ( $this->has_pending_restrictive_response() ) {
			return true;
		}

		if ( ! isset( $this->settings->block_rkn ) || $this->settings->block_rkn != 1 ) {
			return false;
		}

		if ( BotBlockerRkn::isRknIp( (string) $this->ip ) ) {
			$this->redirect_to_denied( 62, 'RKN' );
			return true;
		}

		return false;
	}

	public function check_last_rule(): bool {
		$rule   = null;
		$search = null;

		if ( $this->settings->last_rule != '' ) {
			$rule   = $this->settings->last_rule;
			$search = 'LAST RULE';
		}

		$rule = $this->effective_sign( $rule );

		if ( $rule == BBCS_RULE_ALLOW ) {
			if ( $this->allow_access(
				array(
					'rule'   => $rule,
					'search' => $search,
				)
			) ) {
				return true;
			}
		} elseif ( $rule == BBCS_RULE_BLOCK ) {
			$this->redirect_to_block( 6, 'BLOCK By: ' . $search );
		} elseif ( $rule == BBCS_RULE_DARK ) {
			$this->redirect_to_dark( 'DARK By: ' . $search );
		} elseif ( $rule == BBCS_RULE_GRAY ) {
			$this->set_gray_status( 'GRAY By: ' . $search );
		}
		return false;
	}

	public function allow_access( $echo ): bool {
		// Read main cookie to get existing uid (process_cookies may not have run yet)
		if ( empty( $this->uid ) && isset( $_COOKIE[ $this->settings->cookie ] ) ) {
			$this->uid = preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ $this->settings->cookie ] ) ) );
		}
		if ( empty( $this->uid ) ) {
			$this->uid = $this->generate_uid();
			$this->set_bot_blocker_cookie();
		}
		// Set data cookie only if not already present
		if ( ! isset( $_COOKIE[ $this->uid ] ) ) {
			$this->set_allow_cookie_uid();
		}
		$this->visitorType = self::VISITOR_HUMAN;
		if ( $this->settings->botblocker_log_allow == 1 ) {
			$search = isset( $echo['search'] ) ? $echo['search'] : BOTBLOCKER_EMPTY;
			BotBlockerStore::storeData( 'ALLOW By rule:' . $search, 4 );
		}
		BotBlockerCounters::processHit( 4 );
		return true;
	}

	public function is_whitelisted_ip(): bool {
		if ( method_exists( $this, 'lazy_load_ip_rules' ) ) {
			$this->lazy_load_ip_rules();
		}
		global $wpdb;

		if ( $this->ip_version == 4 ) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$bbcs_ip_test = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
					0,
					$this->ipnum,
					$this->ipnum
				),
				ARRAY_A
			);
		} else {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$bbcs_ip_test = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND ip1 <= %s AND ip2 >= %s ORDER BY priority ASC",
					0,
					$this->ipnum,
					$this->ipnum
				),
				ARRAY_A
			);
		}

		if (
			$bbcs_ip_test
			&& $bbcs_ip_test['expires'] > $this->time
			&& $bbcs_ip_test['rule'] == BBCS_RULE_ALLOW
		) {
			return true;
		}
		return false;
	}
}
