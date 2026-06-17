<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerPaymentTrait {

	public function check_payment_bypass(): bool {
		if ( ! isset( $this->settings ) ) {
			return false;
		}

		$enabled = isset( $this->settings->payment_bypass_enable )
			? (int) $this->settings->payment_bypass_enable
			: 0;

		if ( $enabled !== 1 ) {
			return false;
		}

		if ( ! $this->is_payment_callback_request() ) {
			return false;
		}

		$reason = $this->payment_bypass_reason ?? 'Payment gateway callback';

		$keep_ip_rules = isset( $this->settings->payment_keep_ip_rules )
			? (int) $this->settings->payment_keep_ip_rules : 0;

		if ( $keep_ip_rules === 1 ) {
			$this->payment_bypass_partial = true;
		} else {
			$this->visitorType = self::VISITOR_LEGALBOT;
			$this->white_bot   = 'payment-gateway';
		}

		$this->result_of_action = $reason;

		$log_enabled = isset( $this->settings->payment_bypass_log )
			? (int) $this->settings->payment_bypass_log
			: 1;

		if ( $log_enabled === 1 && class_exists( 'BotBlockerStore' ) ) {
			BotBlockerStore::storeData( $reason, 81 );
		}

		if ( class_exists( 'BotBlockerCounters' ) ) {
			BotBlockerCounters::processHit( 81 );
		}

		return true;
	}

	public function is_payment_callback_request(): bool {
		$uri = isset( $this->uri ) ? (string) $this->uri : '';
		if ( $uri === '' && isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = (string) wp_unslash( $_SERVER['REQUEST_URI'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REQUEST_URI is not user input and does not require nonce verification. It is used for matching against known payment gateway callback patterns.
		}

		if ( $uri === '' ) {
			return false;
		}

		$method = isset( $this->request_method ) ? strtoupper( (string) $this->request_method ) : '';
		if ( $method === '' && isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$method = strtoupper( (string) preg_replace( '/[^A-Za-z]/', '', (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REQUEST_METHOD is not user input and does not require nonce verification. It is sanitized to contain only letters for safety.
		}

		if ( $method !== '' && ! in_array( $method, array( 'GET', 'POST', 'HEAD' ), true ) ) {
			return false;
		}

		$strict = isset( $this->settings->payment_strict_method )
			? (int) $this->settings->payment_strict_method : 0;

		$matched = $this->match_payment_path( $uri );
		if ( $matched !== null && ( $strict !== 1 || $method === 'POST' ) ) {
			$this->payment_bypass_reason = 'Payment bypass: path ' . $matched;
			return true;
		}

		$matched = $this->match_payment_query_key( $uri );
		if ( $matched !== null && ( $strict !== 1 || $method === 'POST' ) ) {
			$this->payment_bypass_reason = 'Payment bypass: query ' . $matched;
			return true;
		}

		$matched = $this->match_payment_action( $uri );
		if ( $matched !== null ) {
			$this->payment_bypass_reason = 'Payment bypass: action ' . $matched;
			return true;
		}

		$matched = $this->match_payment_signature_header( $strict );
		if ( $matched !== null ) {
			$this->payment_bypass_reason = 'Payment bypass: header ' . $matched;
			return true;
		}

		return false;
	}

	public function match_payment_path( string $uri ): ?string {
		if ( $uri === '' ) {
			return null;
		}

		$path = function_exists( 'wp_parse_url' )
			? wp_parse_url( $uri, PHP_URL_PATH )
			: parse_url( $uri, PHP_URL_PATH );  // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		if ( ! is_string( $path ) || $path === '' ) {
			$path = explode( '?', $uri, 2 )[0];
		}

		foreach ( BotBlockerPaymentData::getPaths() as $needle ) {
			if ( $needle === '' ) {
				continue;
			}
			if ( stripos( $path, $needle ) !== false ) {
				return $needle;
			}
		}
		return null;
	}

	public function match_payment_query_key( string $uri ): ?string {
		$query = '';
		$qpos  = strpos( $uri, '?' );
		if ( $qpos !== false ) {
			$query = substr( $uri, $qpos + 1 );
		}

		$params = array();
		if ( $query !== '' ) {
			parse_str( $query, $params );
		}

		if ( empty( $params ) && ! empty( $_GET ) ) {   // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET is used for matching against known payment gateway callback patterns and does not require nonce verification.
			$params = wp_unslash( $_GET );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( empty( $params ) || ! is_array( $params ) ) {
			return null;
		}

		$keys_lc = array_change_key_case( $params, CASE_LOWER );

		foreach ( BotBlockerPaymentData::getQueryKeys() as $key ) {
			$key_lc = strtolower( $key );
			if ( array_key_exists( $key_lc, $keys_lc ) ) {
				return $key;
			}
		}
		return null;
	}

	public function match_payment_action( string $uri ): ?string {
		if ( stripos( $uri, 'admin-ajax.php' ) === false
			&& stripos( $uri, 'admin-post.php' ) === false ) {
			return null;
		}

		$action = '';
		if ( ! empty( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 'action' parameter is used for matching against known payment gateway callback patterns and does not require nonce verification.
			$action = strtolower( sanitize_text_field( wp_unslash( (string) $_REQUEST['action'] ) ) );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 'action' parameter is used for matching against known payment gateway callback patterns and does not require nonce verification.
		}

		if ( $action === '' ) {
			$qpos = strpos( $uri, '?' );
			if ( $qpos !== false ) {
				parse_str( substr( $uri, $qpos + 1 ), $parsed );
				if ( ! empty( $parsed['action'] ) ) {
					$action = strtolower( (string) $parsed['action'] );
				}
			}
		}

		if ( $action === '' ) {
			return null;
		}

		$exact = BotBlockerPaymentData::getActions();
		foreach ( $exact as $known ) {
			if ( strtolower( $known ) === $action ) {
				return $known;
			}
		}

		foreach ( BotBlockerPaymentData::getActionSubstrings() as $needle ) {
			if ( $needle === '' ) {
				continue;
			}
			if ( stripos( $action, $needle ) !== false ) {
				return $needle;
			}
		}

		return null;
	}

	public function match_payment_signature_header( int $strict = 0 ): ?string {
		foreach ( BotBlockerPaymentData::getSignatureHeaders() as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( $strict === 1 && strlen( (string) $_SERVER[ $header ] ) < 8 ) {
					continue;
				}
				return $header;
			}
		}
		return null;
	}
}
