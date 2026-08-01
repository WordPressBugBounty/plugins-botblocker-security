<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerLocalCloudTrait {

	private function processCloudCheck() {
		$cache_key = BotBlockerCache::getPrefix( '_CLOUD_DATA_' );
		$cache_ttl = 86400; // 1 day

		$cached_data = BotBlockerCache::getCacheData( $cache_key );
		if ( $cached_data ) {
			$this->cloud_data  = $cached_data;
			$this->cloud_error = BOTBLOCKER_EMPTY;
		} else {
			$auth = BotBlockerPro::buildAuthPayload( $this->settings );
			$data = array_merge(
				$auth,
				array(
					'cid'                   => $this->cid,
					'score'                 => $this->post_recaptcha_score,
					'cfcountry'             => $this->post_cloudflare_country,
					'country'               => $this->country,
					'ip'                    => $this->ip,
					'version'               => $this->version,
					'ptr'                   => $this->ptr,
					'w'                     => $this->post_width,
					'h'                     => $this->post_height,
					'cw'                    => $this->post_client_width,
					'ch'                    => $this->post_client_height,
					'co'                    => $this->post_color_depth,
					'pi'                    => $this->post_pixel_depth,
					'ref'                   => $this->post_referrer,
					'tz'                    => $this->post_timezone,
					'adb'                   => $this->post_adblocker_found,
					'ipdbc'                 => $this->post_ip_database_result,
					'ipv4'                  => $this->post_ipv4_value,
					'accept'                => $this->post_http_accept,
					'referer'               => $this->referer,
					'useragent'             => $this->useragent,
					'accept_lang'           => $this->accept_lang,
					'post_antidetect_scope' => $this->post_antidetect_scope,
				)
			);

			$this->cloud_error = BOTBLOCKER_EMPTY;

			$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_URL );
			if ( $cloud === false || isset( $cloud['error'] ) ) {
				if ( $cloud === false ) {
					$this->cloud_error = 'BOTBLOCKER_API_URL connection failed.';
				} elseif ( isset( $cloud['error'] ) ) {
					$this->cloud_error = 'BOTBLOCKER_API_URL error: ' . $cloud['error'];
				}
				$cloud = BotBlockerWpRequest::send_to_cloud( $data, BOTBLOCKER_API_GS_URL );

				if ( $cloud === false || isset( $cloud['error'] ) ) {
					if ( $cloud === false ) {
						$this->cloud_error = 'BOTBLOCKER_API_GS_URL connection failed.';
					} elseif ( isset( $cloud['error'] ) ) {
						$this->cloud_error = 'BOTBLOCKER_API_GS_URL error: ' . $cloud['error'];
					}
					if ( $this->settings->unresponsive == 0 ) {
						$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, $this->cloud_error, '' );
						if ( $this->settings->bbcs_ddos_resilience == 1 ) {
							$payload = $this->sign_response_payload( (array) $payload );
						}
						wp_send_json( $payload );
					}
				}
			}

			$this->cloud_data = array();
			if ( is_array( $cloud ) ) {
				foreach ( $cloud as $key => $value ) {
					if ( is_array( $value ) ) {
						foreach ( $value as $sub_key => $sub_value ) {
							$this->cloud_data[ $key . '_' . $sub_key ] = $sub_value;
						}
					} else {
						$this->cloud_data[ $key ] = $value;
					}
				}
				$this->cloud_error = BOTBLOCKER_EMPTY;
				BotBlockerCache::setCacheData( $cache_key, $this->cloud_data, $cache_ttl );
			}
		}

		$status     = $this->cloud_data['status'] ?? BBCS_CLOUD_STATUS_UNKNOWN;
		$BBCS_score = $this->cloud_data['bbcs_score'] ?? 0;

		if ( ( $status === BBCS_CLOUD_STATUS_BAD || $BBCS_score >= 5 ) && $this->settings->unresponsive == 0 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Cloud API ident visitor as bad', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		if ( $status === BBCS_CLOUD_STATUS_GRAY && $this->settings->unresponsive == 0 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Cloud API ident visitor as gray', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		if ( $status !== BBCS_CLOUD_STATUS_GOOD && $this->settings->unresponsive == 1 ) {
			// $message = 'Cloud API mark visitor as not good, but access allowed due to unresponsive flag';
		}

		if ( $status == BBCS_CLOUD_STATUS_GOOD && ( $this->settings->unresponsive == 0 || $this->settings->unresponsive == 1 ) ) {
			// $message = 'Cloud API ident visitor as good';
		}

		$valid_statuses = array( BBCS_CLOUD_STATUS_BAD, BBCS_CLOUD_STATUS_GRAY, BBCS_CLOUD_STATUS_GOOD );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			BotBlockerCache::deleteCacheData( $cache_key );

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG, per project convention.
				error_log( '[BBCS DEBUG] [Cloud] Cloud API unexpected status: ' . $status . ', cache invalidated' );
			}

			if ( $this->settings->cloud_fallback_block == 1 && $this->settings->unresponsive == 0 ) {
				$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Cloud API unexpected status', '' );
				if ( $this->settings->bbcs_ddos_resilience == 1 ) {
					$payload = $this->sign_response_payload( (array) $payload );
				}
				wp_send_json( $payload );
			}
		}

		// TODO processFullCloud (for PRO)
	}
}
