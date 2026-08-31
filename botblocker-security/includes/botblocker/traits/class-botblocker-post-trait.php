<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerPostTrait {

	use BotBlockerHoneypotTrait;

	public function processPostRequest() {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( empty( $this->uid ) ) {
			$this->uid = $this->sanitize_cookie_uid();
			if ( empty( $this->uid ) ) {
				$this->uid = $this->generate_uid();
				$this->set_bot_blocker_cookie();
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] processPostRequest: entry. captcha_mode=' . $this->settings->bbcs_captcha_mode . ' has_challenge_token=' . ( empty( $_POST['challenge_token'] ) ? 'NO' : 'YES' ) . ' has_recaptcha=' . ( empty( $_POST['g-recaptcha-response'] ) ? 'NO' : 'YES' ) . ' post_data_keys=' . implode( ',', array_keys( $_POST ) ) );
		}

		global $wpdb;
		ignore_user_abort( true );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 30 );    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] STEP: start_input_validation' );
		}

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: NoPost' );
			}
			$payload = array( 'error' => 'Error NoPost' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		$honeypot_reason = self::honeypotViolation( $_POST, $this->settings, true );
		if ( $honeypot_reason !== '' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: ' . $honeypot_reason );
			}
			$payload = array( 'error' => $honeypot_reason );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( isset( $_POST['cid'] ) ) {
			$this->cid = trim( preg_replace( '/[^0-9\.]/', '', sanitize_text_field( wp_unslash( $_POST['cid'] ) ) ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: CID not set' );
			}
			$payload = array( 'error' => 'CID not set' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$this->useragent = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
		} else {
			$this->useragent = '';
		}

		if ( isset( $_POST['ip'] ) ) {
			$post_ip = trim( preg_replace( '/[^0-9a-zA-Z\.\:]/', '', sanitize_text_field( wp_unslash( $_POST['ip'] ) ) ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: IP not set' );
			}
			$payload = array( 'error' => 'IP not set' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( isset( $_POST['xxx'] ) ) {
			$post_xxx = trim( wp_strip_all_tags( wp_unslash( $_POST['xxx'] ) ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: XXX not set' );
			}
			$payload = array( 'error' => 'XXX not set' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( isset( $_POST['date'] ) ) {
			$post_date = (int) trim( wp_strip_all_tags( wp_unslash( $_POST['date'] ) ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: DATE not set' );
			}
			$payload = array( 'error' => 'DATE not set' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( isset( $_POST['country'] ) ) {
			$post_country = strtoupper( trim( preg_replace( '/[^A-Za-z]/', '', wp_strip_all_tags( wp_unslash( $_POST['country'] ) ) ) ) );
		} else {
			$post_country = BOTBLOCKER_EMPTY;
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$ref_raw = wp_get_raw_referer();
			/*
			if (! $ref_raw) {
				$raw_ref_data = filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
				if (is_string($raw_ref_data) && $raw_ref_data !== '') {
					$ref_raw = wp_unslash($raw_ref_data);
				}
			}
			*/
			if ( is_string( $ref_raw ) && $ref_raw !== '' ) {
				$ref_raw       = esc_url_raw( trim( str_replace( ' ', '+', $ref_raw ) ) );
				$sch           = $ref_raw ? wp_parse_url( $ref_raw, PHP_URL_SCHEME ) : null;
				$this->referer = ( $ref_raw && in_array( $sch, array( 'http', 'https' ), true ) ) ? $ref_raw : '';
			} else {
				$this->referer = '';
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Post] STEP_DIE: Referer not set (ref_raw empty)' );
				}
				$payload       = array( 'error' => 'Referer not set' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( $payload );
				}
				$this->process_die( wp_json_encode( $payload ) );
			}
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: Referer not set (HTTP_REFERER missing)' );
			}
			$referer = '';
			$payload = array( 'error' => 'Referer not set' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		// Domain (host) from which the script was called
		$parts   = wp_parse_url( $this->referer );
		$host    = $parts['host'] ?? '';
		$refhost = $host ? strtolower( preg_replace( '/[^a-z0-9\.\-]/i', '', $host ) ) : '';

		/*
		* if ipv4 exist - check base 1
		* if country ipv4 exist - check base 5
		* if country base 1 != country base 5 - FAKE
		*/

		if ( $post_date > $this->time ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: timeout (post_date > time)' );
			}
			$payload = array( 'error' => 'timeout' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( $this->time - $post_date > $this->settings->bbcs_captcha_wait
			&& (int) $this->settings->bbcs_captcha_mode !== BOTBLOCKER_CAPTCHA_MODE_SILENT
		) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: timeout (date diff=' . ( $this->time - $post_date ) . ' > captcha_wait=' . $this->settings->bbcs_captcha_wait . ')' );
			}
			$payload = array( 'error' => 'timeout' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] STEP: before_recaptcha_siteverify' );
		}

		if ( $this->settings->bbcs_captcha_mode == 3 || $this->settings->bbcs_captcha_mode == 4 ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: inside_recaptcha_branch' );
			}

			$g_recaptcha_response = isset( $_POST['g-recaptcha-response'] )
				? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) )
				: '';

			$args = array(
				'body'      => array(
					'secret'   => $this->settings->recaptcha_secret2,
					'response' => $g_recaptcha_response,
					'remoteip' => isset( $post_ip ) ? sanitize_text_field( wp_unslash( $post_ip ) ) : '',
				),
				'timeout'   => 15,
				'headers'   => array(
					'User-Agent' => $this->useragent,
					'Referer'    => '',
				),
				'sslverify' => true,
			);

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: before_wp_remote_post' );
			}
			$resp = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', $args );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: after_wp_remote_post' );
			}

			if ( is_wp_error( $resp ) ) {
				if ( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'BBCS reCAPTCHA siteverify WP_Error: ' . $resp->get_error_message() );
				}
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Post] STEP: recaptcha_wp_error_fallthrough' );
				}
				$this->settings->bbcs_captcha_mode = 1;
				if ( $this->settings->time_ban < 1 ) {
					$this->settings->time_ban = '1';
				}
			} else {
				$re = json_decode( wp_remote_retrieve_body( $resp ), true );
				if ( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ) {
					// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'BBCS reCAPTCHA siteverify response: ' . wp_json_encode( $re ) );
					error_log( 'BBCS reCAPTCHA g-recaptcha-response present: ' . ( empty( $g_recaptcha_response ) ? 'NO' : 'YES' ) );
					error_log( 'BBCS reCAPTCHA secret last4: ' . substr( $this->settings->recaptcha_secret2, -4 ) );
					// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Post] STEP: recaptcha_success_ok=' . ( isset( $re['success'] ) ? (int) $re['success'] : 'n/a' ) );
				}
				if ( isset( $re['success'] ) && (int) $re['success'] !== 1 ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
						error_log( '[BBCS DEBUG] [Post] STEP: recaptcha_success_false_fallthrough' );
					}
					$this->settings->bbcs_captcha_mode = 1;
					if ( $this->settings->time_ban < 1 ) {
						$this->settings->time_ban = '1';
					}
				}
			}
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] reCAPTCHA siteverify done. success=' . ( isset( $re['success'] ) ? (int) $re['success'] : 'n/a' ) . ' mode_after=' . $this->settings->bbcs_captcha_mode );
			}
		} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] STEP: recaptcha_branch_skipped_mode=' . $this->settings->bbcs_captcha_mode );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] STEP: before_challenge_token_check' );
		}
		$challenge_token_raw = isset( $_POST['challenge_token'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge_token'] ) ) : '';
		if ( ! empty( $challenge_token_raw ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: challenge_token_present' );
			}
			require_once dirname( __DIR__, 3 ) . '/public/class-botblocker-captcha-renderer.php';
			if ( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ) {
				$ct_diag = BotBlockerCaptchaRenderer::verifyChallengeTokenDiag(
					$this->settings->salt,
					$challenge_token_raw,
					sanitize_text_field( wp_unslash( $post_xxx ) ),
					sanitize_text_field( wp_unslash( (string) $post_date ) ),
					sanitize_text_field( wp_unslash( $post_ip ) )
				);
				if ( $ct_diag['ok'] === false ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
						error_log( '[BBCS DEBUG] [Post] STEP_DIE: wrong_click ct_diag=' . $ct_diag['reason'] );
					}
					$this->process_wrong_click( $ct_diag['reason'] );
				}
				$ct_result = $ct_diag['data'];
			} else {
				$ct_result = BotBlockerCaptchaRenderer::verifyChallengeToken(
					$this->settings->salt,
					$challenge_token_raw,
					sanitize_text_field( wp_unslash( $post_xxx ) ),
					sanitize_text_field( wp_unslash( (string) $post_date ) ),
					sanitize_text_field( wp_unslash( $post_ip ) )
				);
				if ( $ct_result === false ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
						error_log( '[BBCS DEBUG] [Post] STEP_DIE: wrong_click ct_result_false' );
					}
					$this->process_wrong_click();
				}
			}
		// Bind the challenge to the IP it was issued for: a stolen triplet
		// (token, hash, date) must not verify from another address. No ban —
		// IP rotation (mobile/IPv6 privacy) is indistinguishable from a steal;
		// the visitor reloads and gets a fresh challenge.
		if ( ! hash_equals( (string) ( $ct_result['i'] ?? '' ), (string) $this->ip ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: challenge_ip_mismatch' );
			}
			$payload = array( 'error' => 'Wrong Click' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] STEP: challenge_token_passed' );
		}
			// If reCAPTCHA verification changed the mode (failed), reject
			$token_mode = isset( $ct_result['m'] ) ? (int) $ct_result['m'] : -1;
			if ( $token_mode >= 90 ) {
				$addon_registered = class_exists( 'BotBlockerCaptchaRegistry' ) && BotBlockerCaptchaRegistry::has( $token_mode );
				if ( ! $addon_registered ) {
					// Provider deactivated/deleted between render and verify —
					// degrade like a provider error; never grant access unverified.
					$this->settings->bbcs_captcha_mode = 1;
					if ( $this->settings->time_ban < 1 ) {
						$this->settings->time_ban = '1';
					}
				} else {
					$addon_verified = BotBlockerCaptchaRegistry::verify( $token_mode, $_POST, $this );
					if ( null === $addon_verified ) {
						// Provider error — mirror reCAPTCHA network-failure semantics (degrade, never hard-ban).
						$this->settings->bbcs_captcha_mode = 1;
						if ( $this->settings->time_ban < 1 ) {
							$this->settings->time_ban = '1';
						}
					} elseif ( ! $addon_verified ) {
						$this->process_wrong_click( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ? 'AV' : '' );
					}
				}
			}
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: token_mode=' . $token_mode . ' captcha_mode=' . $this->settings->bbcs_captcha_mode );
			}
			if ( ( in_array( $token_mode, array( 3, 4 ) ) || $token_mode >= 90 ) && (int) $this->settings->bbcs_captcha_mode !== $token_mode ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Post] STEP_DIE: wrong_click mode_mismatch' );
				}
				$this->process_wrong_click( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ? 'RM' : '' );
			}
		} elseif ( (int) $this->settings->bbcs_captcha_mode === BOTBLOCKER_CAPTCHA_MODE_SILENT ) {
			// Silent mode but challenge_token is missing (WAF/CDN stripped it, or cached page)
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: silent_no_challenge_token' );
			}
			$payload = array( 'error' => 'timeout' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		} elseif ( $this->settings->bbcs_captcha_mode == 3 || $this->settings->bbcs_captcha_mode == 4 ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: no_challenge_token_mode34' );
			}
			$date_from_post = isset( $post_date ) ? sanitize_text_field( wp_unslash( (string) $post_date ) ) : '';
			$xxx_from_post  = isset( $post_xxx ) ? sanitize_text_field( wp_unslash( $post_xxx ) ) : '';
			$hash0          = '1|' . hash( 'sha256', $this->settings->salt . $date_from_post . $this->settings->cloud_api_pass );
			if ( ! hash_equals( $hash0, $xxx_from_post ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Post] STEP_DIE: wrong_click hash_mismatch' );
				}
				$this->process_wrong_click( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ? 'HM' : '' );
			}
		} elseif ( $this->settings->bbcs_captcha_mode == 2 ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP: no_challenge_token_mode2' );
			}
			$xxx2 = explode( '|', sanitize_text_field( wp_unslash( $post_xxx ) ) );
			if ( ! isset( $xxx2[1] ) ) {
				$payload = array( 'error' => 'Error NoPost 1' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( $payload );
				}
				$this->process_die( wp_json_encode( $payload ) );
			}
			$post_color      = $xxx2[0];
			$post_color_hash = $xxx2[1];
			if ( ! isset( $post_color, $post_color_hash, $post_date, $post_ip ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Post] STEP_DIE: mode2_missing_data' );
				}
				$payload = array( 'error' => 'Missing required POST data' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( $payload );
				}
				$this->process_die( wp_json_encode( $payload ) );
			}
			$submitted_hash = sanitize_text_field( wp_unslash( $post_color_hash ) );
			$expected_hash  = hash(
				'sha256',
				$this->settings->salt .
					sanitize_text_field( wp_unslash( $post_color ) ) .
					sanitize_text_field( wp_unslash( (string) $post_date ) ) .
					$this->settings->cloud_api_pass .
					sanitize_text_field( wp_unslash( $post_ip ) )
			);
			if ( ! hash_equals( $expected_hash, $submitted_hash ) ) {
				$this->process_wrong_click( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ? 'HM' : '' );
			}
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Post] STEP_DIE: wrong_click no_mode_match' );
			}
			$this->process_wrong_click( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ? 'NM' : '' );
		}

		if ( $this->settings->botblocker_log_tests == 1 ) {
			global $wpdb;

			$cid = isset( $_POST['cid'] ) ? sanitize_text_field( wp_unslash( $_POST['cid'] ) ) : '';

			$code_data = BotBlockerDataCodes::codeList( 0 );

			if ( $code_data['allow'] ) {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->bbcs_hits,
					array( 'passed' => 2 ),
					array(
						'passed' => 0,
						'cid'    => $cid,
					),
					array( '%d' ),
					array( '%d', '%s' )
				);
			} else {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->bbcs_hits_suspicious,
					array( 'passed' => 2 ),
					array(
						'passed' => 0,
						'cid'    => $cid,
					),
					array( '%d' ),
					array( '%d', '%s' )
				);
			}
			// bbcs_process_hit('2');
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] STEP: reached_success_exit' );
		}

		$hash = $this->create_session_token() . '-' . $this->time;
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] EXIT: SUCCESS. Setting cookie. ddos_resilience=' . $this->settings->bbcs_ddos_resilience . ' xbbcs_header=' . ( ( $this->settings->bbcs_ddos_resilience == 1 && ! empty( $this->uid ) ) ? 'YES' : 'NO' ) );
		}
		if ( $this->settings->bbcs_ddos_resilience == 1 ) {
			$payload = $this->sign_response_payload( array( 'cookie' => $hash ) );
			if ( ! headers_sent() && ! empty( $this->uid ) ) {
				header( 'X-BBCS-' . $this->uid . ': ' . $hash, false );
			}
			wp_send_json( $payload );
		} else {
			wp_send_json( array( 'cookie' => $hash ) );
		}
	}

	/**
	 * @param string $reason Diagnostic reason code:
	 *   TD = token decrypt failed, TT = transient missing/expired,
	 *   DM = date mismatch, HM = hash mismatch, RM = reCAPTCHA mode mismatch,
	 *   NM = no matching CAPTCHA mode (fallback)
	 */
	public function process_wrong_click( string $reason = '' ) {
		if ( defined( 'BBCS_CAPTCHA_DIAG' ) && BBCS_CAPTCHA_DIAG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'BBCS process_wrong_click reason: ' . $reason );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Post] EXIT: WRONG CLICK (CAPTCHA fail -> ban)' . ( $reason !== '' ? ' reason=' . $reason : '' ) );
		}
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		global $wpdb;

		// Ban the server-resolved client IP (trusted proxy map); REMOTE_ADDR is the fallback only.
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( preg_replace( '/[^0-9a-zA-Z\.\:]/', '', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) ) : '';
		$ip          = BotBlockerBanTargetResolver::resolve( (string) $this->ip, $remote_addr, (string) $this->isProxy, (int) $this->settings->block_proxy_users === 1 );

		if ( $ip === '' ) {
			$payload = array(
				'error' => BotBlockerBanTargetResolver::hasValidIp( (string) $this->ip, $remote_addr ) ? 'Wrong Click' : 'Bad IP',
			);
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( $payload );
			}
			$this->process_die( wp_json_encode( $payload ) );
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$this->ip_version = 4;
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$this->ip_version = 6;
		}

		$fromdate = $this->time - 86401;

		$ip_from_post = $ip;
		$passed_code  = 8;
		$fromdate     = (int) $fromdate;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$miss_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"
            SELECT (SELECT COUNT(*) FROM `{$wpdb->bbcs_hits}` WHERE date >= %d AND ip = %s AND passed = %d)
                 + (SELECT COUNT(*) FROM `{$wpdb->bbcs_hits_suspicious}` WHERE date >= %d AND ip = %s AND passed = %d)
            ",
				$fromdate,
				$ip_from_post,
				$passed_code,
				$fromdate,
				$ip_from_post,
				$passed_code
			)
		);

		if ( $miss_count > 0 ) {
			$this->settings->time_ban = $this->settings->time_ban_2;
		}

		if ( $this->settings->time_ban == 0 ) {
			$this->settings->time_ban = 400;
		}

		if ( $this->ip_version == 4 ) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_rule = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
					$ip
				)
			);
		} else {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_rule = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s",
					$ip
				)
			);
		}

		$table_name = $this->ip_version == 4 ? $wpdb->bbcs_ipv4rules : $wpdb->bbcs_ipv6rules;
		$expires    = $this->time + $this->settings->time_ban;
		if ( $existing_rule ) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				array( 'expires' => $expires ),
				array( 'search' => $ip )
			);
		} else {
			$ip2ban              = $this->ip_version == 4 ? BotBlockerIp::toNumeric( $ip ) : BotBlockerIp::toBinary( $ip );
			$country_transformed = isset( $_POST['country'] ) ? strtoupper( trim( preg_replace( '/[^A-Za-z]/', '', sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) ) ) : '';
			$data                = array(
				'priority' => '1',
				'search'   => $ip,
				'ip1'      => $ip2ban,
				'ip2'      => $ip2ban,
				'rule'     => 'block',
				'comment'  => 'CAPTCHA fail' . ( $reason !== '' ? ' [R:' . $reason . ']' : '' ) . ' ' . $country_transformed,
				'expires'  => $expires,
			);
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $table_name, $data );
		}

		BotBlockerFileRenderer::syncIpBanFiles( $ip, 'block', $expires );

		if ( $this->settings->botblocker_log_tests == 1 ) {

			$code_data = BotBlockerDataCodes::codeList( 8 );

			$cid_from_post = isset( $_POST['cid'] ) ? sanitize_text_field( wp_unslash( $_POST['cid'] ) ) : '';

			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $code_data['allow'] ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->bbcs_hits} SET passed = %d WHERE passed IN (%d, %d) AND cid = %s",
						8,
						0,
						9,
						$cid_from_post
					)
				);
			} else {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->bbcs_hits_suspicious} SET passed = %d WHERE passed IN (%d, %d) AND cid = %s",
						8,
						0,
						9,
						$cid_from_post
					)
				);
			}
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		$payload = array( 'error' => 'Wrong Click' );
		if ( $this->settings->bbcs_ddos_resilience == 1 ) {
			$payload = $this->sign_response_payload( $payload );
		}
		$this->process_die( wp_json_encode( $payload ) );
	}
}
