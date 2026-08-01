<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerTlsTrait {

	public function check_tls_fingerprint(): bool {
		if ( ! isset( $this->settings->tls_fingerprint_check ) || $this->settings->tls_fingerprint_check != 1 ) {
			return false;
		}

		$fingerprint = $this->visitor_ja4;
		if ( empty( $fingerprint ) ) {
			$fingerprint = $this->visitor_ja3;
		}
		if ( empty( $fingerprint ) ) {
			return false;
		}

		if ( method_exists( $this, 'lazy_load_tls_fingerprints' ) ) {
			$this->lazy_load_tls_fingerprints();
		}

		$fingerprint = strtolower( trim( $fingerprint ) );
		$fingerprint = preg_replace( '/[^a-z0-9,._-]/', '', $fingerprint );

		if ( isset( $this->bbcs_tls_fingerprints[ $fingerprint ] ) ) {
			$entry    = $this->bbcs_tls_fingerprints[ $fingerprint ];
			$category = isset( $entry['category'] ) ? (string) $entry['category'] : 'unknown';
		} else {
			$category = 'unknown';
		}

		$this->visitor_tls_fingerprint_category = $category;

		$ua_family = $this->detect_ua_family();

		if ( $category === 'browser' || $category === 'mobile' ) {
			if ( $this->matches_ua_family( $ua_family, $entry['ua_family'] ?? '' ) ) {
				return false;
			}
			$this->suspect_status   = 1;
			$this->result_of_action = 'TLS fingerprint mismatch: UA=' . $ua_family . ' TLS=' . $category;
			return true;
		}

		if ( $category === 'bot_legitimate' ) {
			return false;
		}

		if ( $category === 'malicious' ) {
			$this->suspect_status   = 3;
			$this->result_of_action = 'TLS fingerprint: malicious tool (' . ( $entry['ua_family'] ?? 'unknown' ) . ')';
			$this->redirect_to_dark( 'Malicious TLS fingerprint: ' . $fingerprint );
			return true;
		}

		if ( $category === 'automation' ) {
			$this->suspect_status   = 2;
			$this->result_of_action = 'TLS fingerprint: automation tool';
			if ( ! $this->matches_ua_family( $ua_family, $entry['ua_family'] ?? '' ) ) {
				$this->visitorType      = self::VISITOR_FAKEBOT;
				$this->result_of_action = 'TLS fingerprint mismatch: UA=' . $ua_family . ' TLS=automation';
				$this->redirect_to_dark( 'Automation TLS fingerprint mismatch: ' . $fingerprint );
				return true;
			}
			return true;
		}

		if ( $category === 'headless' ) {
			$this->suspect_status   = 1;
			$this->result_of_action = 'TLS fingerprint: headless browser';
			return true;
		}

		$this->suspect_status   = 1;
		$this->result_of_action = 'TLS fingerprint: unknown category';
		return true;
	}

	private function detect_ua_family(): string {
		$ua = strtolower( $this->useragent );

		if ( strpos( $ua, 'chrome' ) !== false && strpos( $ua, 'edg' ) === false && strpos( $ua, 'opr' ) === false ) {
			return 'chrome';
		}
		if ( strpos( $ua, 'firefox' ) !== false ) {
			return 'firefox';
		}
		if ( strpos( $ua, 'safari' ) !== false && strpos( $ua, 'chrome' ) === false ) {
			return 'safari';
		}
		if ( strpos( $ua, 'edg' ) !== false ) {
			return 'edge';
		}
		if ( strpos( $ua, 'opr' ) !== false || strpos( $ua, 'opera' ) !== false ) {
			return 'opera';
		}
		if ( strpos( $ua, 'bot' ) !== false || strpos( $ua, 'crawl' ) !== false || strpos( $ua, 'spider' ) !== false ) {
			return 'bot';
		}
		if ( strpos( $ua, 'curl' ) !== false || strpos( $ua, 'wget' ) !== false || strpos( $ua, 'python' ) !== false || strpos( $ua, 'httpx' ) !== false ) {
			return 'automation';
		}

		return 'unknown';
	}

	private function matches_ua_family( string $ua_family, string $expected_ua_family ): bool {
		if ( empty( $expected_ua_family ) ) {
			return true;
		}
		return strtolower( $ua_family ) === strtolower( $expected_ua_family );
	}
}
