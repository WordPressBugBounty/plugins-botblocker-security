<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerVisitorTrait {

	public function collect_basic_request_data(): void {
		$this->read_host();
		$this->read_method();
		$this->read_ip();
		$this->read_scheme();
		$this->read_user_agent();
		$this->read_uri();
	}

	public function collect_visitor_data(): void {
		$this->read_host();
		$this->read_method();
		$this->read_ip();
		$this->read_ptr();
		$this->read_scheme();
		$this->read_user_agent();
		$this->read_uri();
		$this->read_referer();
		$this->read_language_data();
		$this->read_protocol();
		$this->read_http_accept();
		$this->read_tls_fingerprints();
		$this->generate_page_url();
		$this->process_referer();
		$this->process_page();

		do_action( 'bbcs_botblocker_after_request_data', $this );
		if ( $this->apply_addon_traffic_decisions( 'after_request_data' ) ) {
			return;
		}

		$this->check_proxy();
		$this->identify_by_user_agent();
		$this->get_ip_info();
		$this->check_restricted_country();
		$this->check_blocked_country();
	}

	public function read_tls_fingerprints(): void {

		if ( ! $this->is_tls_fingerprint_source_trusted() ) {
			return;
		}

		$ja3_header = isset( $this->settings->tls_fingerprint_header_ja3 )
			? (string) $this->settings->tls_fingerprint_header_ja3 : 'X-TLS-JA3';
		$ja4_header = isset( $this->settings->tls_fingerprint_header_ja4 )
			? (string) $this->settings->tls_fingerprint_header_ja4 : 'X-TLS-JA4';

		$server_ja3_header = 'HTTP_' . strtoupper( str_replace( '-', '_', $ja3_header ) );
		$server_ja4_header = 'HTTP_' . strtoupper( str_replace( '-', '_', $ja4_header ) );

		if ( isset( $_SERVER[ $server_ja3_header ] ) ) {
			$this->visitor_ja3 = trim( wp_strip_all_tags( wp_unslash( $_SERVER[ $server_ja3_header ] ) ) );
		}
		if ( isset( $_SERVER['HTTP_CF_JA3_FINGERPRINT'] ) && empty( $this->visitor_ja3 ) ) {
			$this->visitor_ja3 = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_CF_JA3_FINGERPRINT'] ) ) );
		}
		if ( isset( $_SERVER[ $server_ja4_header ] ) ) {
			$this->visitor_ja4 = trim( wp_strip_all_tags( wp_unslash( $_SERVER[ $server_ja4_header ] ) ) );
		}
	}

	private function is_tls_fingerprint_source_trusted(): bool {
		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( $remote !== '' && $this->is_cloudflare_edge_request( $remote ) ) {
			return true;
		}

		$trusted = isset( $this->settings->tls_fingerprint_trusted_proxy )
			? trim( (string) $this->settings->tls_fingerprint_trusted_proxy ) : '';
		if ( $trusted === '' || $remote === '' ) {
			return false;
		}
		if ( BotBlockerIp::netMatch( $trusted, $remote ) == 1 ) {
			return true;
		}
		return false;
	}

	private function is_cloudflare_edge_request( string $remote ): bool {
		if ( ! isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return false;
		}
		if ( method_exists( $this, 'lazy_load_proxy' ) ) {
			$this->lazy_load_proxy();
		}
		$proxy_map = isset( $this->bbcs_proxy ) && is_array( $this->bbcs_proxy )
			? $this->bbcs_proxy : array();
		foreach ( $proxy_map as $proxy_mask => $proxy_attr ) {
			if ( $proxy_attr === 'HTTP_CF_CONNECTING_IP' && BotBlockerIp::netMatch( (string) $proxy_mask, $remote ) == 1 ) {
				return true;
			}
		}
		return false;
	}

	public function check_admin_status(): void {
		$allowed_roles = array( 'administrator', 'editor', 'moderator' );
		$user          = wp_get_current_user();
		if ( is_user_logged_in() && ! empty( array_intersect( $allowed_roles, $user->roles ) ) ) {
			$this->isAdmin = true;
		} else {
			$this->isAdmin = false;
		}
	}

	/**
	 * Resolve visitor identity: uid from cookie (or mint new), parse
	 * verification data, classify via cookie_hash_kind(). Called once in run()
	 * before rules, then idempotent (no-op on second call from process_cookies).
	 * Sets $this->verification_state, $this->cookie_kind, $this->uid, and
	 * cookie parse properties (cookie_stored_hash, cookie_timestamp,
	 * cookie_visitor_data) - everything is available after one call.
	 */
	protected function resolve_cookie_identity(): void {
		if ( $this->verification_state !== null ) {
			return;
		}

		// --- uid ---
		if ( empty( $this->uid ) ) {
			$this->uid = $this->sanitize_cookie_uid();
		}
		if ( empty( $this->uid ) ) {
			$this->uid = $this->generate_uid();
			$this->set_bot_blocker_cookie();
			$this->verification_state = self::VERIFY_INVALID;
			return;
		}

		// --- parse cookie data ---
		$this->cookie_visitor_data = isset( $_COOKIE[ $this->uid ] )
			? trim( wp_strip_all_tags( wp_unslash( $_COOKIE[ $this->uid ] ) ) ) : '';
		$parts                     = explode( '-', $this->cookie_visitor_data );
		$stored_hash               = isset( $parts[0] ) ? trim( $parts[0] ) : '';
		$timestamp                 = isset( $parts[1] ) ? (int) trim( $parts[1] ) : 0;

		$this->cookie_stored_hash = $stored_hash;
		$this->cookie_timestamp   = $timestamp;

		// --- classify ---
		$kind              = $this->cookie_hash_kind( (string) $stored_hash, $timestamp );
		$this->cookie_kind = $kind;

		if ( $kind === 'session' ) {
			$this->verification_state = self::VERIFY_VALID;
		} elseif ( $kind === 'none' ) {
			$this->verification_state = self::VERIFY_INVALID;
		} else {
			$this->verification_state = ( $this->time - $timestamp > $this->settings->cookie_lifetime )
				? self::VERIFY_EXPIRED : self::VERIFY_VALID;
		}
	}

	/**
	 * Which hash form matches the stored verification value for $timestamp.
	 * Returns 'session' | 'sha' | 'legacy' | 'none'. Same precedence as the
	 * original process_cookies(): session token first, then sha256, then legacy
	 * md5.
	 */
	private function cookie_hash_kind( string $stored_hash, int $timestamp ): string {
		if ( strpos( $stored_hash, '.' ) !== false && $this->verify_session_token( $stored_hash ) ) {
			return 'session';
		}
		$expected = hash( 'sha256', $this->settings->salt . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $timestamp );
		if ( hash_equals( $expected, $stored_hash ) ) {
			return 'sha';
		}
		$legacy = md5( $this->settings->salt . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $timestamp );
		if ( hash_equals( $legacy, $stored_hash ) ) {
			return 'legacy';
		}
		return 'none';
	}

	public function process_cookies(): bool {
		if ( isset( $_COOKIE['_ym_uid'] ) ) {
			$this->ym_uid = trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE['_ym_uid'] ) ) ) );
		} else {
			$this->ym_uid = '';
		}
		if ( isset( $_COOKIE['_ga'] ) ) {
			$this->ga_uid = trim( preg_replace( '/[^a-zA-Z0-9\.]/', '', sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) ) ) );
		} else {
			$this->ga_uid = '';
		}
		if ( isset( $_COOKIE[ $this->settings->cookie . '_hits' ] ) ) {
			$current_value             = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ $this->settings->cookie . '_hits' ] ) ) ) );
			$this->is_asset_request    = (bool) preg_match( '/\.(js|css|map|jpe?g|png|gif|svg|webp|ico|woff2?|ttf|otf|eot|mp[34]|webm|ogg|wav|pdf)$/i', $this->uri );
			$this->cookie_hits_counter = $this->is_asset_request ? $current_value : $current_value + 1;
		} else {
			$this->is_asset_request    = (bool) preg_match( '/\.(js|css|map|jpe?g|png|gif|svg|webp|ico|woff2?|ttf|otf|eot|mp[34]|webm|ogg|wav|pdf)$/i', $this->uri );
			$this->cookie_hits_counter = $this->is_asset_request ? 0 : 1;
		}
		$this->resolve_cookie_identity();
		$kind = $this->cookie_kind;
		if ( $kind === 'session' ) {
			$this->update_cookie_counter();
			return true;
		}
		$cookie_valid = ( $kind === 'sha' || $kind === 'legacy' ) && $this->verification_state !== self::VERIFY_EXPIRED;
		if ( $cookie_valid && $kind === 'legacy' && method_exists( $this, 'set_allow_cookie_uid' ) ) {
			$this->set_allow_cookie_uid();
		}
		if ( $this->time - $this->cookie_timestamp > $this->settings->cookie_lifetime ) {
			if ( ! empty( $this->uid ) ) {
				$this->delete_cookie( $this->uid );
			}
		}
		if ( $cookie_valid ) {
			$this->update_cookie_counter();
			return true;
		}
		$this->redirect_to_dark( 'No cookies users(Human?)' );
		return false;
	}

	/**
	 * Read the uid from the main cookie, sanitized and length-capped.
	 * Returns '' when the cookie is absent, contains no alphanumeric
	 * characters, or exceeds BOTBLOCKER_UID_MAX_LEN (oversized uids flow
	 * into X-BBCS-* header names and cookie names - nginx 502 vector).
	 */
	public function sanitize_cookie_uid(): string {
		$cookie_uid = isset( $_COOKIE[ $this->settings->cookie ] )
			? preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ $this->settings->cookie ] ) ) )
			: '';
		return strlen( $cookie_uid ) <= BOTBLOCKER_UID_MAX_LEN ? $cookie_uid : '';
	}

	public function generate_uid(): string {
		$length = 30;
		$bytes  = '';

		if ( function_exists( 'random_bytes' ) ) {
			try {
				$bytes = random_bytes( $length );
			} catch ( \Exception $e ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback path logging; intended for diagnostics.
					error_log( '[BBCS DEBUG] [Visitor] random_bytes() failed, CSPRNG fallback level 1 -> openssl. ' . $e->getMessage() );
				}
			}
		}

		if ( empty( $bytes ) && function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$bytes = openssl_random_pseudo_bytes( $length, $strong );
			if ( ! $strong ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback path logging; intended for diagnostics.
					error_log( '[BBCS DEBUG] [Visitor] openssl_random_pseudo_bytes() returned non-cryptographically strong bytes, CSPRNG fallback level 2 -> composite entropy.' );
				}
				$bytes = '';
			}
		}

		if ( empty( $bytes ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback path logging; intended for diagnostics.
				error_log( '[BBCS DEBUG] [Visitor] CSPRNG unavailable, using composite entropy fallback for UID generation.' );
			}

			$entropy_parts = array(
				microtime( true ),
				getmypid(),
				memory_get_usage( true ),
				isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Composite entropy fallback; not used as sole RNG source.
				mt_rand(),
				php_uname(),
				function_exists( 'getmyinode' ) ? getmyinode() : 0,
				function_exists( 'random_int' ) ? random_int( 0, PHP_INT_MAX ) : time(),
			);

			$context = hash( 'sha256', implode( '|', $entropy_parts ) );

			return substr( $context, 0, $length );
		}

		$context   = hash( 'sha256', $this->ip . $this->useragent . $this->time );
		$bytes_hex = bin2hex( $bytes );
		$uid       = substr( $bytes_hex, 0, 15 ) . substr( $context, 0, 15 );

		return substr( $uid, 0, $length );
	}

	public function update_cookie_counter(): void {
		if ( $this->is_asset_request ) {
			$this->visitorType = self::VISITOR_HUMAN;
			return;
		}
		if ( $this->cookie_hits_counter > $this->settings->hits_per_user ) {
			$this->reset_cookies_by_overflow();
			if ( $this->settings->botblocker_log_local == 1 ) {
				BotBlockerStore::storeData( 'Cookies reset by overflow', 3 );
			}
			BotBlockerCounters::processHit( 3 );
		} else {
			$this->increment_hit_cookie_counter();
			if ( $this->settings->botblocker_log_local == 1 ) {
				BotBlockerStore::storeData( 'Cookies counter update', 3 );
			}
			BotBlockerCounters::processHit( 3 );
		}
		$this->visitorType = self::VISITOR_HUMAN;
	}

	public function check_referer_get_params(): void {
		if ( $this->settings->check_get_ref != 1 ) {
			return;
		}

		$parts = wp_parse_url( $this->referer );
		if ( empty( $parts['query'] ) ) {
			return;
		}

		$params = array();
		wp_parse_str( $parts['query'], $params );

		$bad_list = is_array( $this->list_of_bad_get_params_in_referrer )
			? $this->list_of_bad_get_params_in_referrer
			: array_filter( array_map( 'trim', explode( ',', (string) $this->list_of_bad_get_params_in_referrer ) ) );

		foreach ( $bad_list as $bad_get_ref ) {
			if ( array_key_exists( $bad_get_ref, $params ) ) {
				$this->set_gray_status( 'GRAY by referrer bad structure: ' . $bad_get_ref );
				break;
			}
		}
	}


	public function check_proxy(): void {
		if ( method_exists( $this, 'lazy_load_proxy' ) ) {
			$this->lazy_load_proxy();
		}
		if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			foreach ( $this->bbcs_proxy as $proxy_mask => $proxy_attr ) {
				if ( BotBlockerIp::netMatch( $proxy_mask, $this->ip ) == 1 && isset( $_SERVER[ $proxy_attr ] ) ) {
					$proxy_ip = $this->extract_proxy_header_ip( $_SERVER[ $proxy_attr ] );    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( $proxy_ip !== '' && $this->apply_visitor_ip( $proxy_ip ) ) {
						$this->read_ptr();
						$this->isProxy      = 'PROXY_v4';
						$this->is_proxy_det = $proxy_attr;
						break;
					}
				}
			}
		}
		if ( $this->isProxy === BOTBLOCKER_EMPTY && filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			foreach ( $this->bbcs_proxy as $proxy_mask => $proxy_attr ) {
				if ( BotBlockerIp::netMatch( $proxy_mask, $this->ip ) == 1 && isset( $_SERVER[ $proxy_attr ] ) ) {
					$proxy_ip = $this->extract_proxy_header_ip( $_SERVER[ $proxy_attr ] );    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( $proxy_ip !== '' && $this->apply_visitor_ip( $proxy_ip ) ) {
						$this->read_ptr();
						$this->isProxy      = 'PROXY_v6';
						$this->is_proxy_det = $proxy_attr;
						break;
					}
				}
			}
		}
		$proxy_headers = BotBlockerData::getProxyHeaders();
		if ( $this->isProxy === BOTBLOCKER_EMPTY ) {
			foreach ( $this->bbcs_proxy as $proxy_mask => $proxy_attr ) {
				if ( BotBlockerIp::netMatch( $proxy_mask, $this->ip ) == 1 ) {
					foreach ( $proxy_headers as $header ) {
						if ( ! empty( $_SERVER[ $header ] ) && $this->ip !== '127.0.0.1' ) {
							$this->isProxy      = 'DETECTED';
							$this->is_proxy_det = 'CLASSIC';
							break 2;
						}
					}
				}
			}
		}
	}

	private function extract_proxy_header_ip( $raw_header ): string {
		$header     = sanitize_text_field( wp_unslash( (string) $raw_header ) );
		$candidates = explode( ',', $header );
		for ( $i = count( $candidates ) - 1; $i >= 0; $i-- ) {
			$candidate = trim( $candidates[ $i ] );
			if ( $candidate === '' ) {
				continue;
			}
			$candidate = trim( $candidate, " \t\n\r\0\x0B[]" );
			if ( ! filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				continue;
			}
			if ( $this->isTrustedProxyIp( $candidate ) ) {
				continue;
			}
			return $candidate;
		}
		return '';
	}

	private function isTrustedProxyIp( string $ip ): bool {
		foreach ( $this->bbcs_proxy as $proxy_mask => $proxy_attr ) {
			if ( BotBlockerIp::netMatch( $proxy_mask, $ip ) == 1 ) {
				return true;
			}
		}
		return false;
	}

	public function validate_referer(): void {
		if (
			$this->settings->block_fake_ref == 1 &&
			$this->referer !== '' &&
			$this->visitorType === self::VISITOR_UNDEFINED
		) {
			$parts = wp_parse_url( $this->referer );

			if ( ! $parts || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
				$this->redirect_to_denied( 58, 'Denied By rule: FAKE REFERER' );
			}
		}
	}

	public function check_language_mismatch(): void {
		if ( $this->settings->block_incorrect_lang_users ) {
			/*
			// Don't delete this comment block, it may be useful in the future
			// For future use, if we want to allow global languages to bypass the language-to-country check
						if ( in_array( $this->lang, BBCS_GLOBAL_LANGUAGES, true ) ) {
							return;
						}
			*/
			if ( BotBlockerCheck::languageToCountryCompare( $this->country, $this->lang ) === false ) {
				$this->redirect_to_denied( 57, 'Language-to-country incorrect' );
			}
		}
	}

	public function identify_by_user_agent(): void {
		if ( $this->settings->get_browser_type == 1 ) {
			$this->browser = BotBlockerDataReports::getBrowserType( $this->useragent );
		} else {
			$this->browser = BOTBLOCKER_EMPTY;
		}
		if ( $this->settings->get_os_type == 1 ) {
			$this->os = BotBlockerDataReports::getOSType( $this->useragent );
		} else {
			$this->os = BOTBLOCKER_EMPTY;
		}
		if ( $this->settings->get_device_type == 1 ) {
			$this->device = $this->identify_device_type( $this->useragent );
		} else {
			$this->device = BOTBLOCKER_EMPTY;
		}
	}

	/**
	 * IP-info source selector — test seam (HG-11). The constant read is moved
	 * behind this protected method so harnesses can switch the source without
	 * defining globals (BOTBLOCKER_VISITOR_IP_SOURCE is never defined in tests).
	 */
	protected function get_ip_info_source(): string {
		return defined( 'BOTBLOCKER_VISITOR_IP_SOURCE' ) ? BOTBLOCKER_VISITOR_IP_SOURCE : 'local';
	}

	public function identify_device_type( $userAgent ): string {
		static $cache = array();

		if ( array_key_exists( $userAgent, $cache ) ) {
			return $cache[ $userAgent ];
		}

		$detect = null;
		if ( version_compare( phpversion(), '8.0', '>=' ) ) {
			require_once BOTBLOCKER_DIR . 'vendor/MobileDetect/4.8.10/standalone/autoloader.php';
			require_once BOTBLOCKER_DIR . 'vendor/MobileDetect/4.8.10/src/MobileDetectStandalone.php';
			$detect = new \BotBlocker\Vendor\Detection\MobileDetectStandalone();
		} else {
			require_once BOTBLOCKER_DIR . 'vendor/MobileDetect/3.74.3/MobileDetect.php';
			$detect = new \BotBlocker\Vendor\Detection\MobileDetect();
		}
		if ( $detect === null ) {
			$cache[ $userAgent ] = BOTBLOCKER_EMPTY;
			return BOTBLOCKER_EMPTY;
		}
		$detect->setUserAgent( $userAgent );
		if ( $detect->isTablet() ) {
			$cache[ $userAgent ] = 'tablet';
			return 'tablet';
		} elseif ( $detect->isMobile() ) {
			$cache[ $userAgent ] = 'phone';
			return 'phone';
		} elseif ( preg_match( '/smart-tv|smarttv|googletv|appletv|hbbtv|pov_tv|netcast.tv/i', $userAgent ) ) {
			$cache[ $userAgent ] = 'tv';
			return 'tv';
		} elseif ( preg_match( '/xbox|playstation|nintendo/i', $userAgent ) ) {
			$cache[ $userAgent ] = 'box';
			return 'box';
		} else {
			$cache[ $userAgent ] = 'pc';
			return 'pc';
		}
	}

	public function check_hosting(): void {
		if ( class_exists( 'BotBlockerPro' ) && ! BotBlockerPro::isActive() ) {
			return;
		}
		if (
			$this->settings->hosting_block == 1 &&
			in_array( $this->hosting, array( 1, '1' ), true ) &&
			$this->visitorType == self::VISITOR_UNDEFINED
		) {
			$this->redirect_to_denied( 17, 'DENIED By rule: Hosting or Bad IP' );
		}
	}

	private function normalize_asnum_value( $value ): string {
		if ( $value === null || $value === '' || ! is_scalar( $value ) ) {
			return BOTBLOCKER_EMPTY;
		}

		$raw = trim( (string) $value );
		if ( strlen( $raw ) >= 2 && strncasecmp( $raw, 'AS', 2 ) === 0 ) {
			$raw = substr( $raw, 2 );
		}

		$normalized = BotBlockerAsnValue::normalize( $raw );
		if ( $normalized === null ) {
			return BOTBLOCKER_EMPTY;
		}

		return $normalized;
	}

	public function get_ip_info(): void {
		$source = $this->get_ip_info_source();

		if ( $source !== 'cloud' ) {
			$cache_key = 'bbcs_visitor_' . md5( $this->ip );
			$cached    = BotBlockerCache::getCacheData( $cache_key );
			if ( $cached !== null ) {
				$this->country  = $cached['country'] ?? BOTBLOCKER_EMPTY;
				$this->asnum    = $this->normalize_asnum_value( $cached['asnum'] ?? null );
				$this->asname   = $cached['asname'] ?? BOTBLOCKER_EMPTY;
				$this->cidr     = $cached['cidr'] ?? BOTBLOCKER_EMPTY;
				$this->hosting  = $cached['hosting'] ?? BOTBLOCKER_EMPTY;
				if ( $this->country != BOTBLOCKER_EMPTY && ! empty( $this->country ) ) {
					$this->country_name = BotBlockerGeo::getCountryByCode( $this->country );
				}
				return;
			}
		}

		if ( $source === 'cloud' ) {
			$this->get_ip_info_legacy();
		} else {
			$this->get_ip_info_pipeline();
		}

		if ( $this->country != BOTBLOCKER_EMPTY && ! empty( $this->country ) ) {
			$this->country_name = BotBlockerGeo::getCountryByCode( $this->country );
		}

		if ( $source !== 'cloud' ) {
			BotBlockerCache::setCacheData(
				'bbcs_visitor_' . md5( $this->ip ),
				array(
					'country' => $this->country,
					'asnum'   => $this->asnum,
					'asname'  => $this->asname,
					'cidr'    => $this->cidr,
					'hosting' => $this->hosting,
				),
				HOUR_IN_SECONDS
			);
		}
	}

	private function get_ip_info_legacy(): void {
		if ( $this->has_valid_cloud_api() ) {
			$cloud = $this->collect_cloud_data();
			if ( $cloud === false || isset( $cloud['error'] ) ) {
				$this->country = $this->get_alternative_ip_info();
			} else {
				$this->country = $cloud['country'] ?? BOTBLOCKER_EMPTY;
				$this->cidr    = $cloud['cidr'] ?? BOTBLOCKER_EMPTY;
				$this->asname  = $cloud['asname'] ?? BOTBLOCKER_EMPTY;
				$this->asnum   = $this->normalize_asnum_value( $cloud['asnum'] ?? null );
				$this->hosting = $cloud['hosting'] ?? BOTBLOCKER_EMPTY;
			}
		} else {
			$this->country = $this->get_alternative_ip_info();
		}
		$this->country = strtoupper( $this->country );
	}

	private function get_ip_info_pipeline(): void {
		$local = class_exists( 'BotBlockerAsnDb' ) ? BotBlockerAsnDb::lookup( $this->ip ) : null;
		if ( is_array( $local ) ) {
			$this->asnum  = $this->normalize_asnum_value( $local['asn'] ?? null );
			$this->asname = $local['as_name'] ?? BOTBLOCKER_EMPTY;
			$this->cidr   = $local['cidr'] ?? BOTBLOCKER_EMPTY;
			if ( ! empty( $local['country'] ) && $local['country'] !== BOTBLOCKER_EMPTY ) {
				$this->country = $local['country'];
			}
		}

		if ( $this->country == BOTBLOCKER_EMPTY || empty( $this->country ) ) {
			$this->country = $this->get_sxgeo_country();
		}

		$strict         = defined( 'BOTBLOCKER_VISITOR_IP_STRICT' ) && BOTBLOCKER_VISITOR_IP_STRICT;
		$cloud_active   = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
		$hosting_wanted = ( $this->settings->hosting_block == 1 && $cloud_active );

		if ( $strict ) {
			if ( $hosting_wanted && $this->has_valid_cloud_api() ) {
				$cloud = $this->collect_cloud_data();
				if ( is_array( $cloud ) && ! isset( $cloud['error'] ) && $cloud !== false ) {
					$this->hosting = $cloud['hosting'] ?? BOTBLOCKER_EMPTY;
				}
			}
			if ( empty( $this->country ) ) {
				$this->country = BOTBLOCKER_EMPTY;
			}
			return;
		}

		$needs_country = ( $this->country == BOTBLOCKER_EMPTY || empty( $this->country ) );
		if ( ( $needs_country || $hosting_wanted ) && $this->has_valid_cloud_api() ) {
			$cloud = $this->collect_cloud_data();
			if ( is_array( $cloud ) && ! isset( $cloud['error'] ) && $cloud !== false ) {
				if ( $needs_country ) {
					$this->country = $cloud['country'] ?? BOTBLOCKER_EMPTY;
				}
				if ( $this->cidr === BOTBLOCKER_EMPTY ) {
					$this->cidr = $cloud['cidr'] ?? BOTBLOCKER_EMPTY;
				}
				if ( $this->asname === BOTBLOCKER_EMPTY ) {
					$this->asname = $cloud['asname'] ?? BOTBLOCKER_EMPTY;
				}
				if ( $this->asnum === BOTBLOCKER_EMPTY ) {
					$this->asnum = $this->normalize_asnum_value( $cloud['asnum'] ?? null );
				}
				if ( $cloud_active ) {
					$this->hosting = $cloud['hosting'] ?? BOTBLOCKER_EMPTY;
				}
			}
		}

		if ( ( $this->country == BOTBLOCKER_EMPTY || empty( $this->country ) ) && $this->ip_version == 4 ) {
			$fallback = BotBlockerWpRequest::ip2c( $this->ip );
			if ( ! empty( $fallback ) ) {
				$this->country = $fallback;
			}
		}
		$this->country = strtoupper( $this->country );
	}

	private function get_sxgeo_country() {
		if ( $this->ip_version == 4 ) {
			require_once $this->dirs['vendor'] . 'SypexGeo/SxGeo.php';
			$SxGeo   = new SxGeo( 'SxGeo.dat' );
			$country = $SxGeo->getCountry( $this->ip );
			if ( ! empty( $country ) ) {
				return $country;
			}
		}
		return BOTBLOCKER_EMPTY;
	}

	private function get_alternative_ip_info() {
		if ( $this->ip_version == 4 ) {
			require_once $this->dirs['vendor'] . 'SypexGeo/SxGeo.php';
			$SxGeo   = new SxGeo( 'SxGeo.dat' );
			$country = $SxGeo->getCountry( $this->ip );
			if ( empty( $country ) ) {
				return BotBlockerWpRequest::ip2c( $this->ip );
			} else {
				return $country;
			}
		}
		if ( $this->ip_version == 6 ) {
			return BOTBLOCKER_EMPTY;
		}
	}

	public function check_restricted_country(): void {
		$restrictedCountries = BotBlockerData::getRestrictedCountries();
		if ( in_array( $this->country, $restrictedCountries, true ) ) {
			$this->settings->recaptcha_check = 0;
		}
	}

	public function check_blocked_country(): void {
		if ( $this->isAdmin ) {
			return;
		}

		$blockedCountries = BotBlockerData::getBlockedCountries();
		if ( ! empty( $blockedCountries ) && in_array( $this->country, $blockedCountries, true ) ) {
			$this->redirect_to_denied( 13, 'Denied by country: ' . $this->country );
		}
	}

	public function is_safe_request(): bool {
		if ( method_exists( $this, 'lazy_load_ip_rules' ) ) {
			$this->lazy_load_ip_rules();
		}
		$isCron      = $this->is_wordpress_system_cron();
		$isHeartbeat = $this->is_wordpress_heartbeat();

		if ( $this->isAdmin ) {
			$isAdminIP = isset( $this->admin_ips[ $this->ip ] ) && $this->admin_ips[ $this->ip ] === BBCS_RULE_ALLOW;
			if ( ! $isAdminIP && $this->settings->autosave_admin_ip == 1 ) {
				BotBlockerInstallIp::addAdminIPs( $this->ip );
				BotBlockerFileRenderer::renderIps();
				BotBlockerCache::clearFileCache();
			}

			if ( $this->settings->botblocker_log_admin == 1 ) {
				$this->collect_visitor_data();
				$this->visitorType = self::VISITOR_ADMIN;
				BotBlockerStore::storeData( 'Admin access', 59 );
			}
			BotBlockerCounters::processHit( 59 );
			return true;
		}

		$skip_logged_in = isset( $this->settings->skip_logged_in_users )
			? (int) $this->settings->skip_logged_in_users : 0;
		if ( $skip_logged_in === 1 && function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			if ( isset( $this->settings->botblocker_log_admin ) && $this->settings->botblocker_log_admin == 1 ) {
				$this->visitorType = self::VISITOR_HUMAN;
				BotBlockerStore::storeData( 'Logged-in user bypass', 59 );
			}
			BotBlockerCounters::processHit( 59 );
			return true;
		}

		$isSelfIP       = isset( $this->self_ips[ $this->ip ] ) && $this->self_ips[ $this->ip ] === BBCS_RULE_ALLOW;
		$allowSelfIPReq = $this->settings->allow_self_ip_req ?? false;

		if ( $isHeartbeat && ( is_user_logged_in() || ( $isSelfIP && $allowSelfIPReq ) ) ) {
			BotBlockerStore::storeData( 'WordPress heartbeat request', 73 );
			BotBlockerCounters::processHit( 73 );
			return true;
		}

		if ( $isSelfIP ) {
			if ( $isCron ) {
				BotBlockerStore::storeData( 'WordPress self request', 70 );
				BotBlockerCounters::processHit( 70 );
				return true;
			} elseif ( $allowSelfIPReq ) {
				BotBlockerStore::storeData( 'Self ip request', 12 );
				BotBlockerCounters::processHit( 12 );
				return true;
			}
		}

		if ( ! $this->isAdmin && $this->is_wordpress_http_api_request()
			&& ( $this->has_valid_self_call_header() || ( $isSelfIP && $allowSelfIPReq ) ) ) {
			BotBlockerStore::storeData( 'WordPress API request', 71 );
			BotBlockerCounters::processHit( 71 );
			return true;
		}

		return false;
	}

	private function is_wordpress_http_api_request(): bool {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		if ( ! preg_match( '/^WordPress\/[\d\.]+;\s*https?:\/\/.+/', $ua ) ) {
			return false;
		}
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! $home_host ) {
			return false;
		}
		return BotBlockerCheck::string_contains_host_www( $ua, (string) $home_host );
	}

	public function inject_self_call_header( array $args, $url ): array {
		if ( ! $this->is_self_call_header_enabled() ) {
			return $args;
		}
		if ( ! is_string( $url ) || $url === '' || ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
			return $args;
		}
		$target_host = wp_parse_url( $url, PHP_URL_HOST );
		$home_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $target_host ) || $target_host === '' || ! is_string( $home_host ) || $home_host === '' ) {
			return $args;
		}
		if ( ! BotBlockerCheck::hosts_equal_www( $target_host, $home_host ) ) {
			return $args;
		}
		$salt = isset( $this->settings->salt ) ? (string) $this->settings->salt : '';
		if ( $salt === '' ) {
			return $args;
		}
		$ts                                   = time();
		$args['headers']['X-BotBlocker-Self'] = $ts . '.' . hash_hmac( 'sha256', 'self-call:' . $ts, $salt );
		return $args;
	}

	private function has_valid_self_call_header(): bool {
		if ( ! $this->is_self_call_header_enabled() ) {
			return false;
		}
		if ( ! isset( $_SERVER['HTTP_X_BOTBLOCKER_SELF'] ) ) {
			return false;
		}
		$header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_BOTBLOCKER_SELF'] ) );
		$parts  = explode( '.', $header, 2 );
		if ( count( $parts ) !== 2 || $parts[0] === '' || ! ctype_digit( $parts[0] ) ) {
			return false;
		}
		$ts = (int) $parts[0];
		if ( abs( time() - $ts ) > BOTBLOCKER_SELF_CALL_WINDOW ) {
			return false;
		}
		$salt = isset( $this->settings->salt ) ? (string) $this->settings->salt : '';
		if ( $salt === '' ) {
			return false;
		}
		return hash_equals( hash_hmac( 'sha256', 'self-call:' . $ts, $salt ), $parts[1] );
	}

	private function is_self_call_header_enabled(): bool {
		$enabled = isset( $this->settings->allow_self_call_header )
			? (int) $this->settings->allow_self_call_header : 1;
		return $enabled === 1;
	}

	public function is_wordpress_system_cron(): bool {
		if ( wp_doing_cron() ) {
			if ( ! defined( 'REST_REQUEST' ) && ! wp_doing_ajax() && ! defined( 'WP_CLI' ) ) {
				if ( isset( $this->uri ) && strpos( $this->uri, '/wp-cron.php' ) !== false ) {
					return true;
				}
				if ( isset( $this->useragent ) && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
					$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
					if ( preg_match( '/^WordPress\/[\d\.]+;.+$/', $ua ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function is_wordpress_heartbeat(): bool {
		if ( isset( $this->uri ) && strpos( $this->uri, '/wp-admin/admin-ajax.php' ) !== false ) {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) && $_POST['action'] === 'heartbeat' ) {    // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return true;
			}
		}
		if ( wp_doing_ajax() ) {
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'heartbeat' ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return true;
			}
		}
		return false;
	}

	public function perform_simple_bot_checks(): void {
		if ( $this->settings->block_empty_ua && BotBlockerCheck::isNotEmptyUA( $this->useragent ) === false ) {
			$this->redirect_to_denied( 50, 'Empty UA' );
		}
		if ( $this->settings->block_ipv6_users && $this->ip_version == 6 ) {
			$this->redirect_to_denied( 51, 'IPv6 connect' );
		}
		if ( $this->settings->block_empty_lang && BotBlockerCheck::isEmptyLanguage( $this->accept_lang, $this->lang ) === false ) {
			$this->redirect_to_denied( 52, 'Empty language' );
		}
		if ( $this->settings->block_http10_users && $this->protocol == 'HTTP/1.0' ) {
			$this->redirect_to_denied( 53, 'Http/1.0' );
		}
		if ( $this->settings->block_ip_ptr_match && $this->ptr === $this->ip ) {
			$this->redirect_to_denied( 60, 'IP equals PTR record' );
		}
		if (
			$this->settings->block_cf_users &&
			in_array( $this->isProxy, array( 'PROXY_v4', 'PROXY_v6' ), true ) &&
			$this->is_proxy_det === 'HTTP_CF_CONNECTING_IP'
		) {
			$this->redirect_to_denied( 55, 'Cloudflare' );
		}
		if ( $this->settings->block_proxy_users && $this->isProxy === 'DETECTED' && $this->is_proxy_det === 'CLASSIC' ) {
			$this->redirect_to_denied( 56, 'Classic proxy' );
		}
		if ( $this->settings->block_simplebot_ua && $this->check_bot_by_useragent( $this->useragent ) !== false ) {
			$this->redirect_to_denied( 54, 'Black UA' );
		}
	}

	public function read_host(): void {
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$this->host = preg_replace( '/[^0-9a-z.:-]/', '', strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) );
		} else {
			$this->host = 'errorhost.local';
		}
		$this->host = rtrim( $this->host, '.' );
		if ( substr_count( $this->host, ':' ) === 1 ) {
			$this->host = explode( ':', $this->host, 2 )[0];
		}
		$this->host = BotBlockerCheck::strip_www( $this->host );
	}

	public function read_method(): void {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$this->request_method = (string) trim( preg_replace( '/[^a-zA-Z]/', '', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) );
		} else {
			$this->request_method = '';
		}

		if ( $this->request_method === 'OPTIONS' ) {
			if ( BotBlockerCheck::isValidOptionsRequest() ) {
				return;
			}
			if ( $this->settings->botblocker_log_block == 1 ) {
				BotBlockerStore::storeData( 'Not allowed HTTP method', 15 );
			}
			BotBlockerCounters::processHit( 15 );
			$this->process_die();
			return;
		}

		if ( BotBlockerCheck::isValidMethod( $this->request_method ) == false ) {
			if ( $this->settings->botblocker_log_block == 1 ) {
				BotBlockerStore::storeData( 'Not allowed HTTP method', 15 );
			}
			BotBlockerCounters::processHit( 15 );
			$this->process_die();
		}
	}

	public function read_ip(): void {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = trim( wp_strip_all_tags( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
		} else {
			if ( $this->settings->botblocker_log_block == 1 ) {
				BotBlockerStore::storeData( 'IP not reading', 15 );
			}
			BotBlockerCounters::processHit( 15 );
			$this->process_die();
		}

		if ( ! $this->apply_visitor_ip( $ip ) ) {
			if ( $this->settings->botblocker_log_block == 1 ) {
				BotBlockerStore::storeData( 'IP not valid', 15 );
			}
			BotBlockerCounters::processHit( 15 );
			$this->process_die();
		}
	}

	private function apply_visitor_ip( string $ip ): bool {
		$ip = trim( $ip );
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$this->ip         = $ip;
			$this->ip_version = 4;
			$bbcsIParray      = explode( '.', $this->ip );
			$this->ip_short   = $bbcsIParray[0] . '.' . $bbcsIParray[1] . '.' . $bbcsIParray[2] . '.0/24';
			$this->ipnum      = BotBlockerIp::toNumeric( $this->ip );
			return true;
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$this->ip         = BotBlockerIp::expandIPv6( $ip );
			$this->ip_version = 6;
			$bbcsIParray      = explode( ':', $this->ip );
			$this->ip_short   = $bbcsIParray[0] . ':' . $bbcsIParray[1] . ':' . $bbcsIParray[2] . ':' . $bbcsIParray[3] . ':0000:0000:0000:0000/64';
			$this->ipnum      = BotBlockerIp::toBinary( $this->ip );
			return true;
		}
		return false;
	}

	public function read_ptr(): void {
		$this->ptr = BotBlockerIp::getPtr( $this->ip, $this->time, $this->settings->ptrcache_time );
	}

	public function read_scheme(): void {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
			$this->scheme = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) );
		} elseif ( isset( $_SERVER['REQUEST_SCHEME'] ) ) {
			$this->scheme = trim( wp_strip_all_tags( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) );
		} else {
			$this->scheme = 'https';
		}
	}

	public function read_user_agent(): void {
		$this->useragent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) )
			: '';
	}

	public function read_uri(): void {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$this->uri = trim( wp_strip_all_tags( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			$this->uri = str_replace( array( "\r", "\n" ), '', $this->uri );
			$this->uri = preg_replace( '/\/+/', '/', $this->uri );
		} else {
			$this->uri = '/';
		}
	}

	public function read_referer(): void {
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$this->referer = str_replace( array( "\r", "\n" ), '', trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) );

			$parts = wp_parse_url( $this->referer );
			$host  = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';

			$host = $host ? preg_replace( '/[^0-9a-z\-\.:]/i', '', $host ) : '';

			$this->refhost = $host;

			$scheme               = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';
			$scheme               = $scheme ? preg_replace( '/[^a-z]/', '', $scheme ) : '';
			$this->refhost_scheme = $scheme;

		} else {
			$this->referer        = '';
			$this->refhost        = '';
			$this->refhost_scheme = '';
		}
	}

	public function read_language_data(): void {
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$this->accept_lang = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
			$this->lang        = $this->read_real_language( $this->accept_lang );
			$this->name_lang   = BotBlockerGeo::getLanguageByCode( $this->lang );
		} else {
			$this->accept_lang = '';
			$this->lang        = '';
			$this->name_lang   = '';
		}
	}

	public function read_real_language( $acceptLanguage ): string {
		$languages = $this->parse_accept_language( $acceptLanguage );
		$lang      = (string) $languages[0];
		$langCode  = preg_match( '/^[a-z]{2,3}/', $lang, $matches ) ? $matches[0] : BOTBLOCKER_EMPTY;
		return $langCode;
	}

	public function parse_accept_language( $acceptLanguage ): array {
		$langs          = array();
		$acceptLanguage = strtolower( $acceptLanguage );
		$lang_parse     = explode( ',', $acceptLanguage );
		foreach ( $lang_parse as $lang ) {
			$parts              = explode( ';q=', trim( $lang ) );
			$langCode           = trim( $parts[0] );
			$quality            = isset( $parts[1] ) ? floatval( $parts[1] ) : 1.0;
			$langs[ $langCode ] = $quality;
		}
		arsort( $langs, SORT_NUMERIC );
		return array_keys( $langs );
	}

	public function read_protocol(): void {
		$this->protocol = isset( $_SERVER['SERVER_PROTOCOL'] )
			? trim( wp_strip_all_tags( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) )
			: 'HTTP/1.0';
	}

	public function read_http_accept(): void {
		$this->http_accept = isset( $_SERVER['HTTP_ACCEPT'] )
			? trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) )
			: '';
	}

	public function generate_page_url(): void {
		$this->page = $this->scheme . '://' . $this->host . $this->uri;
	}

	public function process_referer(): void {
		if ( $this->delete_query_string_from_referrer == 1 ) {
			$this->save_referer = explode( '?', $this->referer )[0];
		} else {
			$this->save_referer = $this->referer;
		}
	}

	public function process_page(): void {
		if ( $this->delete_page_query_string_from_URL == 1 ) {
			$this->save_page = explode( '?', $this->page )[0];
		} else {
			$this->save_page = $this->page;
		}
	}

	public function check_bot_by_useragent( $useragent ) {
		static $cache = array();

		if ( array_key_exists( $useragent, $cache ) ) {
			return $cache[ $useragent ];
		}

		foreach ( BotBlockerData::getBotSignatures() as $signature ) {
			if ( stripos( $useragent, $signature ) !== false ) {
				$cache[ $useragent ] = $signature;
				return $signature;
			}
		}

		foreach ( BotBlockerData::getBotSignaturePatterns() as $pattern ) {
			if ( preg_match( '~' . $pattern . '~i', $useragent ) === 1 ) {
				$cache[ $useragent ] = $pattern;
				return $pattern;
			}
		}

		$cache[ $useragent ] = false;
		return false;
	}

	private function session_debug_log( string $msg ): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Session] ' . $msg );
		}
	}

	public function verify_session_token( string $raw_cookie_value ): bool {
		$parts = explode( '.', $raw_cookie_value, 2 );
		if ( count( $parts ) !== 2 || strlen( $parts[0] ) !== 32 || strlen( $parts[1] ) !== 64 ) {
			$this->session_debug_log( 'VERIFY FAIL: bad format' );
			return false;
		}

		$session_id   = $parts[0];
		$signature    = $parts[1];
		$expected_sig = hash_hmac( 'sha256', $session_id, $this->settings->salt );
		$id_short     = substr( $session_id, 0, 8 );

		if ( ! hash_equals( $expected_sig, $signature ) ) {
			$this->session_debug_log( "VERIFY FAIL: signature mismatch id={$id_short}…" );
			return false;
		}

		$payload = $this->session_bucket_get( $session_id );

		if ( $payload !== null && is_array( $payload ) ) {
			if ( isset( $payload['t'] ) && $this->time - (int) $payload['t'] > $this->settings->cookie_lifetime ) {
				$this->session_debug_log( "VERIFY FAIL: expired id={$id_short}… age=" . ( $this->time - (int) $payload['t'] ) . 's' );
				return false;
			}
			// CRITICAL SECURITY: UID binding - token from a different visitor must be rejected
			$payload_u = isset( $payload['u'] ) ? (string) $payload['u'] : '?';
			if ( ! isset( $payload['u'] ) || $payload['u'] !== $this->uid ) {
				$this->session_debug_log( "VERIFY FAIL: UID binding mismatch id={$id_short}… payload.u={$payload_u} current.uid={$this->uid} (stolen/replayed token?)" );
				return false;
			}
			// END CRITICAL SECURITY: UID binding
			$this->visitorType = self::VISITOR_HUMAN;
			$issued_ip = isset( $payload['i'] ) ? (string) $payload['i'] : '?';
			$ip_note   = ( $issued_ip !== '?' && $issued_ip !== (string) $this->ip ) ? ' IP CHANGED (survived)' : '';
			$this->session_debug_log( "VERIFY OK id={$id_short}… payload.u={$payload_u} current.uid={$this->uid} age=" . ( $this->time - (int) ( $payload['t'] ?? $this->time ) ) . 's | issued_ip=' . $issued_ip . ' now_ip=' . $this->ip . ' (IP not checked)' . $ip_note );
			return true;
		}

		$this->session_debug_log( "VERIFY FAIL: id not in bucket id={$id_short}… (expired/evicted/forged)" );
		return false;
	}

	public function create_session_token( $time = null, $ip = null, $uid = null ): string {
		$t = $time ?? $this->time;
		$i = $ip ?? $this->ip;
		$u = $uid ?? $this->uid;

		if ( ! $this->settings->session_token_enabled ) {
			return hash(
				'sha256',
				$this->settings->salt .
				$this->settings->cloud_api_pass .
				$this->host .
				$this->useragent .
				$i .
				$t
			);
		}

		$session_id = bin2hex( random_bytes( 16 ) );
		$signature  = hash_hmac( 'sha256', $session_id, $this->settings->salt );
		$payload    = array(
			'u' => $u,
			't' => $t,
			'i' => $i,
		);

		$this->session_bucket_set( $session_id, $payload );

		$this->session_debug_log( 'CREATE id=' . substr( $session_id, 0, 8 ) . '… uid=' . $u . ' stored in bucket' );

		return $session_id . '.' . $signature;
	}

	public function session_bucket_cleanup(): void {
		global $wpdb;
		if ( ! empty( $wpdb->bbcs_sessions ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "TRUNCATE TABLE `{$wpdb->bbcs_sessions}`" );
		}
	}

	private function session_bucket_get( string $session_id ): ?array {
		global $wpdb;
		if ( empty( $wpdb->bbcs_sessions ) ) {
			return null;
		}
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT uid, created, ip FROM `{$wpdb->bbcs_sessions}` WHERE session_id = %s",
				$session_id
			),
			ARRAY_A
		);
		if ( $row === null ) {
			return null;
		}
		return array(
			'u' => $row['uid'],
			't' => (int) $row['created'],
			'i' => $row['ip'],
		);
	}

	private function session_bucket_set( string $session_id, array $payload ): void {
		global $wpdb;
		if ( empty( $wpdb->bbcs_sessions ) ) {
			return;
		}

		$now = time();
		$ttl = (int) $this->settings->cookie_lifetime;

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$wpdb->bbcs_sessions}` (session_id, uid, ip, created, expires)
				VALUES (%s, %s, %s, %d, %d)
				ON DUPLICATE KEY UPDATE uid = VALUES(uid), ip = VALUES(ip), expires = VALUES(expires)",
				$session_id,
				$payload['u'],
				$payload['i'],
				$payload['t'],
				$now + $ttl
			)
		);

		// Hysteresis cap: if 50000+ rows, delete oldest 5000 to avoid unbounded growth.
		// Capped DELETE avoids long table locks on large session stores.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_sessions}`" );
		if ( $count > 50000 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "DELETE FROM `{$wpdb->bbcs_sessions}` ORDER BY expires ASC LIMIT 5000" );
		}
	}

	public function update_settings_based_on_visitor_data(): void {
		if ( $this->ip_version == 6 && $this->settings->recaptcha_v3_ipv6_block ) {
			$this->settings->recaptcha_check = 0;
		}
	}
}
