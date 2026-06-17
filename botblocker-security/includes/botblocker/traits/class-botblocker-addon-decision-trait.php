<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerAddonDecisionTrait {

	public function addon_traffic_decision_stops_request(): bool {
		return ! empty( $this->addon_traffic_decision_stop );
	}

	public function apply_addon_traffic_decisions( string $stage ): bool {
		$stage = sanitize_key( $stage );
		if ( $stage === '' ) {
			return false;
		}

		do_action( 'bbcs_botblocker_before_traffic_decision', $this, $stage );

		$providers = BotBlockerAddons::getTrafficDecisionProviders();
		foreach ( $providers as $provider ) {
			if ( empty( $provider['callback'] ) || ! is_callable( $provider['callback'] ) ) {
				continue;
			}

			try {
				$decision = call_user_func( $provider['callback'], $this, $stage, $provider );
			} catch ( Throwable $exception ) {
				do_action( 'bbcs_botblocker_traffic_decision_error', $exception, $this, $stage, $provider );
				continue;
			}

			if ( $this->handle_addon_traffic_decision( $decision, $stage, $provider ) ) {
				return true;
			}
		}

		$filtered_decision = apply_filters( 'bbcs_botblocker_traffic_decision', null, $this, $stage );
		return $this->handle_addon_traffic_decision( $filtered_decision, $stage, array( 'slug' => 'filter' ) );
	}

	private function handle_addon_traffic_decision( $decision, string $stage, array $provider ): bool {
		$decision = $this->normalize_addon_traffic_decision( $decision, $stage, $provider );
		if ( empty( $decision ) ) {
			return false;
		}

		$this->addon_traffic_decision = $decision;
		do_action( 'bbcs_botblocker_decision_selected', $decision, $this, $stage, $provider );

		return $this->execute_addon_traffic_decision( $decision, $stage, $provider );
	}

	private function normalize_addon_traffic_decision( $decision, string $stage, array $provider ): array {
		if ( ! is_array( $decision ) || empty( $decision['action'] ) ) {
			return array();
		}

		$action          = sanitize_key( (string) $decision['action'] );
		$allowed_actions = array( BBCS_ADDON_ACTION_ALLOW, BBCS_ADDON_ACTION_BYPASS, BBCS_ADDON_ACTION_BLOCK, BBCS_ADDON_ACTION_CAPTCHA, BBCS_ADDON_ACTION_REDIRECT, BBCS_ADDON_ACTION_LOG_ONLY );
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			return array();
		}

		$status = isset( $decision['status'] ) ? (int) $decision['status'] : 302;
		if ( ! in_array( $status, array( 301, 302, 303, 307, 308 ), true ) ) {
			$status = 302;
		}

		$source = isset( $decision['source'] ) ? sanitize_key( (string) $decision['source'] ) : '';
		if ( $source === '' ) {
			$source = isset( $provider['slug'] ) ? sanitize_key( (string) $provider['slug'] ) : 'addon';
		}

		return array(
			'action' => $action,
			'stage'  => $stage,
			'source' => $source,
			'reason' => isset( $decision['reason'] ) ? sanitize_text_field( (string) $decision['reason'] ) : '',
			'code'   => isset( $decision['code'] ) ? (int) $decision['code'] : 901,
			'url'    => isset( $decision['url'] ) ? esc_url_raw( (string) $decision['url'] ) : '',
			'status' => $status,
		);
	}

	private function execute_addon_traffic_decision( array $decision, string $stage, array $provider ): bool {
		$reason = $decision['reason'] !== '' ? $decision['reason'] : 'BotBlocker add-on traffic decision';
		$code   = (int) $decision['code'];

		if ( $decision['action'] === BBCS_ADDON_ACTION_LOG_ONLY ) {
			if ( class_exists( 'BotBlockerStore' ) ) {
				BotBlockerStore::storeData( $reason, $code );
			}
			do_action( 'bbcs_botblocker_log_only_request', $decision, $this, $stage, $provider );
			return false;
		}

		$this->addon_traffic_decision_stop = true;

		if ( $decision['action'] === BBCS_ADDON_ACTION_ALLOW || $decision['action'] === BBCS_ADDON_ACTION_BYPASS ) {
			$this->visitorType      = self::VISITOR_HUMAN;
			$this->result_of_action = $reason;
			do_action( 'bbcs_botblocker_allowed_request', $decision, $this, $stage, $provider );
			return true;
		}

		if ( $decision['action'] === BBCS_ADDON_ACTION_REDIRECT ) {
			$url = $decision['url'];
			if ( $url === '' || ( PHP_VERSION_ID < 80000 ? apply_filters( 'bbcs_redirect_check_headers_sent', headers_sent() ) : headers_sent() ) ) {
				$this->addon_traffic_decision_stop = false;
				return false;
			}
			do_action( 'bbcs_botblocker_redirected_request', $decision, $this, $stage, $provider );
			wp_safe_redirect( $url, (int) $decision['status'] );
			// @codeCoverageIgnoreStart
			exit; 
			// @codeCoverageIgnoreEnd
		}

		do_action( 'bbcs_botblocker_blocked_request', $decision, $this, $stage, $provider );

		if ( $decision['action'] === BBCS_ADDON_ACTION_CAPTCHA ) {
			// A CAPTCHA addon decision is a DARK challenge: skip it for a visitor
			// who already holds a valid verification cookie, and let the pipeline continue.
			if ( $this->is_verified() ) {
				$this->addon_traffic_decision_stop = false;
				return false;
			}
			$this->redirect_to_dark( $reason );
			return true;
		}

		$this->redirect_to_denied( $code, $reason );
		return true;
	}
}
