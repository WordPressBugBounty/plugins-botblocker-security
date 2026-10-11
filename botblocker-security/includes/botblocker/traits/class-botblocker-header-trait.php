<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerHeaderTrait {
	protected function start_output_guard(): int {
		$level = ob_get_level();
		ob_start();
		return $level;
	}

	protected function end_output_guard( int $guard_level ): void {
		while ( ob_get_level() > $guard_level ) {
			ob_end_flush();
		}
	}

	/**
	 * Finalizes the header set for an allowed request.
	 *
	 * Single exit for allowed requests (all early-return branches of run() and
	 * admin/login/REST contexts): queues the plugin's X-Robots-Tag, then the
	 * security-headers addon set. Security pages never reach this method:
	 * check/block/denied die inside run() in FULL mode, and in FRONTEND mode
	 * the should_show_* flags short-circuit it.
	 *
	 * @since 1.1.2
	 *
	 * @return bool True when the addon header set was sent (X-Robots-Tag is not counted).
	 */
	public function finalize_allowed_headers(): bool {
		if ( $this->should_show_check_page || $this->should_show_block_page || $this->should_show_denied_page ) {
			return false;
		}

		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() || wp_doing_ajax() ) {
			return false;
		}

		if ( ! $this->isDisabled ) {
			$this->process_headers();
			$this->set_x_robot_headers();
		}

		if ( ! class_exists( 'Botblocker_Security_Headers' ) ) {
			return false;
		}

		$headers = Botblocker_Security_Headers::get_instance()->get_headers();

		if ( empty( $headers ) ) {
			return false;
		}

		if ( class_exists( 'BotBlockerBootstrap' ) ) {
			BotBlockerBootstrap::sendSecurityHeaders( $headers );
		}

		return true;
	}

	public function set_iframe_headers(): void {
		if ( $this->settings->iframe_stop == 1 && $this->header_can_send() ) {
			$this->emit_header( 'X-Frame-Options: SAMEORIGIN' );
		}
	}

	/**
	 * Prevent caching of security-critical pages (check, denied, block, AJAX).
	 * Mirrors MU-phase headers from mu-botblocker-header.php.
	 */
	private function send_no_cache_headers(): void {
		if ( $this->header_can_send() ) {
			$this->emit_header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			$this->emit_header( 'Pragma: no-cache' );
			$this->emit_header( 'Expires: Thu, 10 Aug 2000 06:00:00 GMT' );
			$this->emit_header( 'X-LiteSpeed-Cache-Control: no-cache' );
			$this->emit_header( 'X-Accel-Expires: 0' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$this->emit_header( 'CDN-Cache-Control: no-store, private' );
				$this->emit_header( 'Surrogate-Control: no-store, max-age=0' );
			}
		}
	}

	/**
	 * Emits the HTTP status line. Overridable I/O seam: harnesses capture the
	 * status instead of sending it (WP-PHPUnit CLI always has headers_sent()).
	 */
	protected function send_status_header( string $status_line ): void {
		if ( $this->header_can_send() ) {
			$this->emit_header( $this->protocol . ' ' . $status_line );
			$this->emit_header( 'Status: ' . $status_line );
		}
	}

	/**
	 * Send Vary: Cookie header so caches distinguish responses by cookie set.
	 * Controlled by the vary_cookie plugin setting.
	 */
	public function send_vary_cookie_header(): void {
		if ( isset( $this->settings->vary_cookie ) && $this->settings->vary_cookie == 1 ) {
			if ( $this->header_can_send() ) {
				$this->emit_header( 'Vary: Cookie', false );
			}
		}
	}

	public function set_work_headers(): void {
		if ( ! $this->header_can_send() ) {
			return;
		}
		$this->send_no_cache_headers();
		$this->emit_header( 'Content-Type: text/html; charset=UTF-8' );
		if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
			$request_origin = esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
			$site_host      = wp_parse_url( home_url(), PHP_URL_HOST );
			$origin_host    = wp_parse_url( $request_origin, PHP_URL_HOST );
			if ( $site_host && $origin_host && BotBlockerCheck::hosts_equal_www( $site_host, $origin_host ) ) {
				$this->emit_header( 'Access-Control-Allow-Origin: ' . $request_origin );
				$this->emit_header( 'Access-Control-Allow-Credentials: true' );
				$this->emit_header( 'Vary: Origin', false );
			}
		}
		$this->emit_header( 'Access-Control-Allow-Methods: POST' );
		if ( ! empty( $this->settings->bbcs_cors_strict_headers ) ) {
			$this->emit_header( 'Access-Control-Allow-Headers: Content-Type, X-Requested-With' );
		} else {
			$this->emit_header( 'Access-Control-Allow-Headers: *' );
		}
		$this->emit_header( 'X-Robots-Tag: noindex' );
	}

	public function set_check_headers(): void {
		$this->send_no_cache_headers();
		if ( $this->header_can_send() ) {
			$this->emit_header( 'Content-Type: text/html; charset=UTF-8' );
			$this->emit_header( 'X-Robots-Tag: noindex' );
		}
		$test_code = isset( $this->settings->header_test_code ) && isset( $this->error_headers[ $this->settings->header_test_code ] )
			? $this->error_headers[ $this->settings->header_test_code ]
			: '200 OK';
		$this->send_status_header( $test_code );
	}

	public function set_denied_headers(): void {
		$this->send_no_cache_headers();
		if ( $this->header_can_send() ) {
			$this->emit_header( 'X-Robots-Tag: noindex, noarchive' );
		}
		$error_code = isset( $this->settings->header_error_code ) && isset( $this->error_headers[ $this->settings->header_error_code ] )
			? $this->error_headers[ $this->settings->header_error_code ]
			: '403 Forbidden';
		$this->send_status_header( $error_code );
	}

	public function reset_post_headers(): void {
		if ( $this->request_method == 'POST' ) {
			if ( class_exists( 'BotBlockerCheck' )
				&& BotBlockerCheck::responseFormatForUri( (string) ( $this->uri ?? '' ) ) !== 'html' ) {
				// API clients cannot follow a 302-to-self PRG loop — the
				// format-correct refusal replaces the redirect.
				$this->emit_api_shaped_response(
					BotBlockerCheck::responseFormatForUri( (string) ( $this->uri ?? '' ) ),
					$this->denied_status_line(),
					'bbcs_challenge_required'
				);
				return;
			}
			if ( $this->header_can_send() ) {
				$this->emit_header( 'Location: ' . $this->uri );
			}
			$this->process_die();
		}
	}

	public function set_x_robot_headers(): void {
		if ( ! isset( $this->x_robots_tag ) || ! is_array( $this->x_robots_tag ) ) {
			$this->x_robots_tag = array();
		}

		$auto_directives    = $this->x_robots_tag;
		$this->x_robots_tag = array();

		$available_directives = BotBlockerData::getXRobotTags();

		if ( isset( $this->settings->x_robots_directives ) && ! empty( $this->settings->x_robots_directives ) ) {
			if ( is_string( $this->settings->x_robots_directives ) ) {
				$user_directives = json_decode( $this->settings->x_robots_directives, true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[BBCS DEBUG] [Header] Invalid JSON in x_robots_directives: ' . json_last_error_msg() );
					}
					$user_directives = array();
				}
			} else {
				$user_directives = $this->settings->x_robots_directives;
			}

			if ( is_array( $user_directives ) ) {
				foreach ( $user_directives as $directive ) {
					$directive = trim( $directive );
					if ( ! empty( $directive ) && isset( $available_directives[ $directive ] ) ) {
						if ( ! empty( $available_directives[ $directive ] ) ) {
							$this->x_robots_tag[] = $directive . ':' . $available_directives[ $directive ];
						} else {
							$this->x_robots_tag[] = $directive;
						}
					}
				}
			}
		}

		if ( $this->settings->noarchive == 1 && ! in_array( 'noarchive', $this->x_robots_tag ) ) {
			$this->x_robots_tag[] = 'noarchive';
		}

		foreach ( $auto_directives as $auto_directive ) {
			$directive_name = explode( ':', $auto_directive )[0];

			$already_added = false;
			foreach ( $this->x_robots_tag as $added_directive ) {
				if ( strpos( $added_directive, $directive_name . ':' ) === 0 || $added_directive === $directive_name ) {
					$already_added = true;
					break;
				}
			}

			if ( ! $already_added ) {
				$this->x_robots_tag[] = $auto_directive;
			}
		}

		if ( count( $this->x_robots_tag ) > 0 && $this->header_can_send() ) {
			$this->emit_header( 'X-Robots-Tag: ' . implode( ', ', $this->x_robots_tag ) );
		}
	}
}
