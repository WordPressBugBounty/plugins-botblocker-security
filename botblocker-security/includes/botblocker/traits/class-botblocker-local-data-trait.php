<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerLocalDataTrait {

	private function processPostData(): void {

		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( isset( $_POST['error'] ) && sanitize_text_field( wp_unslash( $_POST['error'] ) ) == 'detection_failed' ) {
			$this->post_antidetect_scope = BOTBLOCKER_EMPTY;
		} else {
			$detection_booleans          = array(
				'navigatorMismatch',
				'unsupportedFeatures',
				'fakePlugins',
				'fontRenderMismatch',
				'chromiumProperties',
				'jitter',
				'webGLMismatch',
				'touchEventMismatch',
				'batteryAPIMismatch',
				'mediaDevicesMismatch',
				'permissionsMismatch',
				'languageMismatch',
				'crossbrowserIncognito',
			);
			$this->post_antidetect_scope = array();
			foreach ( $detection_booleans as $key ) {
				if ( isset( $_POST[ $key ] ) ) {
					$prepared_value                      = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					$this->post_antidetect_scope[ $key ] = $prepared_value == 'true';
				} else {
					$this->post_antidetect_scope[ $key ] = false;
				}
			}

			if ( isset( $_POST['browserFingerprint'] ) ) {
				$this->post_antidetect_scope['browserFingerprint'] = sanitize_text_field( wp_unslash( $_POST['browserFingerprint'] ) );
			} else {
				$this->post_antidetect_scope['browserFingerprint'] = '';
			}
		}

		if ( isset( $_POST['cookieoff'] ) ) {
			$this->post_cookie_disabled = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['cookieoff'] ) ) ) );
		} else {
			$this->post_cookie_disabled = 0;
		}

		if ( isset( $_POST['from_suspect'] ) ) {
			$this->post_from_suspect = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['from_suspect'] ) ) ) );
			$this->result_of_action  = 'GRAY by POST - suspect option';
		} else {
			$this->post_from_suspect = 0;
		}

		if ( isset( $_POST['rowid'] ) ) {
			$this->rule_record_id = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['rowid'] ) ) ) );
		} else {
			$this->rule_record_id = 0;
		}

		if ( isset( $_POST['date'] ) ) {
			$this->date = trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) );
		} else {
			$this->date = 0;
		}

		if ( isset( $_POST['h1'] ) ) {
			$this->post_hash_code = trim( preg_replace( '/[^0-9a-z]/', '', sanitize_text_field( wp_unslash( $_POST['h1'] ) ) ) );
		} else {
			$this->post_hash_code = 'xxx';
		}

		if ( isset( $_POST['test'] ) ) {
			$this->post_test_code = trim( preg_replace( '/[^0-9a-z]/', '', sanitize_text_field( wp_unslash( $_POST['test'] ) ) ) );
		} else {
			$this->post_test_code = 'xxx';
		}

		if ( isset( $_POST['hdc'] ) ) {
			$this->post_hosting_detected = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['hdc'] ) ) ) );
		} else {
			$this->post_hosting_detected = 0;
		}

		if ( isset( $_POST['a'] ) ) {
			$this->post_adblocker_found = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['a'] ) ) ) );
		} else {
			$this->post_adblocker_found = 0;
		}

		if ( isset( $_POST['country'] ) ) {
			$this->country = strtoupper( trim( preg_replace( '/[^A-Za-z]/', '', sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) ) );
		} else {
			$this->country = BOTBLOCKER_EMPTY;
		}

		if ( isset( $_POST['ip'] ) ) {
			$this->ip = trim( preg_replace( '/[^0-9a-zA-Z\.\:]/', '', sanitize_text_field( wp_unslash( $_POST['ip'] ) ) ) );
			if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$this->ip = BotBlockerIp::expandIPv6( $this->ip );
			} elseif ( ! filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$this->ip = '';
			}
		} else {
			$this->ip = '';
		}

		if ( isset( $_POST['version'] ) ) {
			$this->version = trim( preg_replace( '/[^0-9\.]/', '', sanitize_text_field( wp_unslash( $_POST['version'] ) ) ) );
		} else {
			$this->version = '';
		}

		if ( isset( $_POST['ptr'] ) ) {
			$this->ptr = trim( preg_replace( '/[^0-9a-zA-Z\.\:\-]/', '', sanitize_text_field( wp_unslash( $_POST['ptr'] ) ) ) );
		} else {
			$this->ptr = '';
		}

		if ( isset( $_POST['w'] ) ) {
			$this->post_width = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['w'] ) ) ) );
		} else {
			$this->post_width = 0;
		}

		if ( isset( $_POST['h'] ) ) {
			$this->post_height = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['h'] ) ) ) );
		} else {
			$this->post_height = 0;
		}

		if ( isset( $_POST['cw'] ) ) {
			$this->post_client_width = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['cw'] ) ) ) );
		} else {
			$this->post_client_width = 0;
		}

		if ( isset( $_POST['ch'] ) ) {
			$this->post_client_height = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['ch'] ) ) ) );
		} else {
			$this->post_client_height = 0;
		}

		if ( isset( $_POST['co'] ) ) {
			$this->post_color_depth = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['co'] ) ) ) );
		} else {
			$this->post_color_depth = 0;
		}

		if ( isset( $_POST['pi'] ) ) {
			$this->post_pixel_depth = (int) trim( preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['pi'] ) ) ) );
		} else {
			$this->post_pixel_depth = 0;
		}

		if ( isset( $_POST['ref'] ) ) {
			$this->post_referrer = trim( wp_strip_all_tags( wp_unslash( $_POST['ref'] ) ) );
		} else {
			$this->post_referrer = '';
		}

		if ( isset( $_POST['tz'] ) ) {
			$this->post_timezone = trim( preg_replace( '/[^0-9a-zA-Z\-\/\_\+]/', '', sanitize_text_field( wp_unslash( $_POST['tz'] ) ) ) );
		} else {
			$this->post_timezone = '';
		}

		if ( isset( $_POST['ipdbc'] ) ) {
			$this->post_ip_database_result = trim( preg_replace( '/[^A-Z]/', '', sanitize_text_field( wp_unslash( $_POST['ipdbc'] ) ) ) );
		} else {
			$this->post_ip_database_result = '';
		}

		if ( isset( $_POST['ipv4'] ) ) {
			$this->post_ipv4_value = trim( preg_replace( '/[^0-9\.]/', '', sanitize_text_field( wp_unslash( $_POST['ipv4'] ) ) ) );
		} else {
			$this->post_ipv4_value = '';
		}

		if ( isset( $_POST['rct'] ) ) {
			$this->post_recaptcha_token = trim( wp_strip_all_tags( wp_unslash( $_POST['rct'] ) ) );
		} else {
			$this->post_recaptcha_token = '';
		}

		if ( isset( $_POST['xxx'] ) ) {
			$this->post_extra_data = trim( wp_strip_all_tags( wp_unslash( $_POST['xxx'] ) ) );
		} else {
			$this->post_extra_data = '';
		}

		if ( isset( $_POST['accept'] ) ) {
			$this->post_http_accept = trim( wp_strip_all_tags( wp_unslash( $_POST['accept'] ) ) );
		} else {
			$this->post_http_accept = '';
		}
	}

	private function processServerData(): void {

		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
			$this->scheme = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) );
		} elseif ( isset( $_SERVER['REQUEST_SCHEME'] ) ) {
			$this->scheme = trim( wp_strip_all_tags( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) );
		} else {
			$this->scheme = 'https';
		}

		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$val = strtoupper( trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ) );
			if ( preg_match( '/^(?:[A-Z]{2}|T1)$/', $val ) === 1 ) {
				$this->post_cloudflare_country = $val;
			} else {
				$this->post_cloudflare_country = '';
			}
		} else {
			$this->post_cloudflare_country = '';
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$this->uri = trim( wp_strip_all_tags( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			$this->uri = str_replace( array( "\r", "\n" ), '', $this->uri );
		} else {
			$this->uri = '/';
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$ref_raw = wp_get_raw_referer();
			if ( is_string( $ref_raw ) && $ref_raw !== '' ) {
				$ref_raw       = esc_url_raw( trim( str_replace( ' ', '+', $ref_raw ) ) );
				$sch           = $ref_raw ? wp_parse_url( $ref_raw, PHP_URL_SCHEME ) : null;
				$this->referer = ( $ref_raw && in_array( $sch, array( 'http', 'https' ), true ) ) ? $ref_raw : '';
			} else {
				$this->referer = '';
			}
		} else {
			$this->referer = '';
		}

		$parts         = wp_parse_url( $this->referer );
		$host          = $parts['host'] ?? '';
		$this->refhost = $host ? strtolower( preg_replace( '/[^a-z0-9\.\-]/i', '', $host ) ) : '';

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$this->useragent = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
		} else {
			$this->useragent = '';
		}

		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$this->accept_lang = trim( wp_strip_all_tags( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
		} else {
			$this->accept_lang = '';
		}
	}
}
