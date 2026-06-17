<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerLocalValidationTrait {

	private function processRequiredParams() {
		if ( $this->version === '' ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Version', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->post_width < 300 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Monitor Width', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->post_height < 300 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Monitor Height', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->post_client_width < 250 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Browser Window Width', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->post_client_height < 250 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Browser Window Height', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->post_color_depth < 24 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Color Depth', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->post_pixel_depth < 24 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Pixel Depth', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->referer == '' ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Empty Referer', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->useragent == '' ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Empty User-agent', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->accept_lang == '' ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Empty Lang', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		if ( $this->post_hosting_detected == 1 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Hosting or Bad IP', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
		if ( $this->check_bot_by_useragent( $this->useragent ) ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Bot', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		if ( ! hash_equals( $this->post_hash_code, hash( 'sha256', $this->settings->cloud_api_email . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->date ) ) ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'H1 Hash Error', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		if ( ! hash_equals( $this->post_test_code, hash( 'sha256', $this->useragent . $this->ip . $this->date . $this->country . $this->ptr . $this->settings->salt ) ) ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Test Hash Error', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}

		if ( $this->post_start_time - $this->time > 20 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Long Request Error', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
	}

	private function processAdblockerDetect() {
		if ( $this->settings->block_adblocker_users == 1 && $this->post_adblocker_found == 1 ) {
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'Ads blocking software is strong disabled', '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
	}

	private function processAntidetect() {
		$groups = array(
			'block_incognito_users'    => array( 'crossbrowserIncognito' ),
			'block_simple_antidetect'  => array( 'navigatorMismatch', 'chromiumProperties' ),
			'block_override'           => array( 'fakePlugins', 'fontRenderMismatch', 'languageMismatch' ),
			'block_web_engine_options' => array( 'unsupportedFeatures', 'webGLMismatch', 'permissionsMismatch' ),
			'block_device_options'     => array( 'touchEventMismatch', 'batteryAPIMismatch', 'mediaDevicesMismatch', 'jitter' ),
		);

		$checkWeights = array(
			'navigatorMismatch'     => 2,
			'unsupportedFeatures'   => 2,
			'fakePlugins'           => 3,
			'fontRenderMismatch'    => 2,
			'chromiumProperties'    => 3,
			'jitter'                => 1,
			'webGLMismatch'         => 2,
			'touchEventMismatch'    => 2,
			'batteryAPIMismatch'    => 1,
			'mediaDevicesMismatch'  => 2,
			'permissionsMismatch'   => 1,
			'languageMismatch'      => 3,
			'crossbrowserIncognito' => 2,
		);

		$groupThresholds = array(
			'block_incognito_users'    => 2,
			'block_simple_antidetect'  => 4,
			'block_override'           => 5,
			'block_web_engine_options' => 4,
			'block_device_options'     => 4,
		);

		$minGroupsToBlock = 2;

		$totalPositiveThreshold = 6;

		$activeGroups       = 0;
		$triggeredGroups    = 0;
		$totalPositiveScore = 0;
		$totalEnabledChecks = 0;
		$blockReasons       = array();
		$groupDetails       = array();

		foreach ( $groups as $settingKey => $checks ) {
			if ( ! empty( $this->settings->$settingKey ) && $this->settings->$settingKey == true ) {
				++$activeGroups;
				$groupScore     = 0;
				$groupMaxScore  = 0;
				$groupPositives = array();

				foreach ( $checks as $checkKey ) {
					++$totalEnabledChecks;
					$checkWeight    = $checkWeights[ $checkKey ] ?? 1;
					$groupMaxScore += $checkWeight;

					if ( isset( $this->post_antidetect_scope[ $checkKey ] ) && $this->post_antidetect_scope[ $checkKey ] == true ) {
						$groupScore         += $checkWeight;
						$totalPositiveScore += $checkWeight;
						$groupPositives[]    = $checkKey;
					}
				}

				$groupPercentage = ( $groupMaxScore > 0 ) ? ( $groupScore / $groupMaxScore ) * 100 : 0;

				$groupThreshold = $groupThresholds[ $settingKey ] ?? 3;
				$isTriggered    = ( $groupScore >= $groupThreshold ) || ( $groupPercentage >= 70 );

				$groupDetails[ $settingKey ] = array(
					'score'      => $groupScore,
					'maxScore'   => $groupMaxScore,
					'percentage' => $groupPercentage,
					'triggered'  => $isTriggered,
					'positives'  => $groupPositives,
				);

				if ( $isTriggered ) {
					++$triggeredGroups;
					$blockReasons = array_merge( $blockReasons, $groupPositives );
				}
			}
		}

		$totalMaxScore = 0;
		foreach ( $checkWeights as $key => $weight ) {
			if ( isset( $this->post_antidetect_scope[ $key ] ) ) {
				$totalMaxScore += $weight;
			}
		}

		$totalPercentage = ( $totalMaxScore > 0 ) ? ( $totalPositiveScore / $totalMaxScore ) * 100 : 0;

		$percentageThreshold = 70;

		$combinationDetected = false;

		$enabledChecks = array();
		foreach ( $groups as $settingKey => $checks ) {
			if ( ! empty( $this->settings->$settingKey ) && $this->settings->$settingKey == true ) {
				foreach ( $checks as $checkKey ) {
					$enabledChecks[ $checkKey ] = true;
				}
			}
		}

		$criticalCombinations = array(
			array( 'navigatorMismatch', 'webGLMismatch', 'critical_combination_navigator_webgl' ),
			array( 'fakePlugins', 'chromiumProperties', 'critical_combination_plugins_chrome' ),
			array( 'fontRenderMismatch', 'languageMismatch', 'critical_combination_font_language' ),
		);

		foreach ( $criticalCombinations as $combination ) {
			[$firstCheck, $secondCheck, $reason] = $combination;
			if (
				! empty( $enabledChecks[ $firstCheck ] ) &&
				! empty( $enabledChecks[ $secondCheck ] ) &&
				isset( $this->post_antidetect_scope[ $firstCheck ] ) &&
				$this->post_antidetect_scope[ $firstCheck ] &&
				isset( $this->post_antidetect_scope[ $secondCheck ] ) &&
				$this->post_antidetect_scope[ $secondCheck ]
			) {
				$combinationDetected = true;
				$blockReasons[]      = $reason;
			}
		}

		$shouldBlock = false;
		$blockReason = '';

		if ( $triggeredGroups >= $minGroupsToBlock && $activeGroups > 0 ) {
			$shouldBlock = true;
			$blockReason = 'Multiple detection groups triggered';
		}

		if ( $totalPositiveScore >= $totalPositiveThreshold && $totalEnabledChecks >= 8 ) {
			$shouldBlock = true;
			$blockReason = 'High total detection score';
		}

		if ( $totalPercentage >= $percentageThreshold && $totalEnabledChecks >= 5 ) {
			$shouldBlock = true;
			$blockReason = 'High percentage of positive detections';
		}

		if ( $combinationDetected ) {
			$shouldBlock = true;
			$blockReason = 'Critical combination of detections';
		}

		$blockReasons = array_unique( $blockReasons );

		if ( $shouldBlock && ! empty( $blockReasons ) ) {
			usort(
				$blockReasons,
				function ( $a, $b ) use ( $checkWeights ) {
					return ( $checkWeights[ $b ] ?? 1 ) - ( $checkWeights[ $a ] ?? 1 );
				}
			);

			$displayReasons = array_slice( $blockReasons, 0, 3 );

			$message = 'Browser_Check: ' . implode( ', ', $displayReasons );
			if ( count( $blockReasons ) > 3 ) {
				$message .= ' and ' . ( count( $blockReasons ) - 3 ) . ' more';
			}
			$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, $message, '' );
			if ( $this->settings->bbcs_ddos_resilience == 1 ) {
				$payload = $this->sign_response_payload( (array) $payload );
			}
			wp_send_json( $payload );
		}
	}

	private function processTimeZone() {
		global $wpdb;
		$search = 'timezone=' . $this->post_timezone;

		if ( $this->rule_record_id > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$BBCSRulesCheck = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_rules}` WHERE search = %s OR id = %d ORDER BY priority ASC",
					$search,
					$this->rule_record_id
				),
				ARRAY_A
			);
		} else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$BBCSRulesCheck = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->bbcs_rules}` WHERE search = %s",
					$search
				),
				ARRAY_A
			);
		}

		foreach ( $BBCSRulesCheck as $echo ) {
			if ( $echo['disable'] == '0' ) {
				if ( $echo['search'] == $search ) {
					if ( $echo['rule'] == BBCS_RULE_DARK ) {
						$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'DARK By rule: timezone=' . $this->post_timezone, '' );
						if ( $this->settings->bbcs_ddos_resilience == 1 ) {
							$payload = $this->sign_response_payload( (array) $payload );
						}
						wp_send_json( $payload );
								} elseif ( $echo['rule'] == BBCS_RULE_BLOCK ) {
						$payload = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_ERROR, 'BLOCK By rule: timezone=' . $this->post_timezone, '' );
						if ( $this->settings->bbcs_ddos_resilience == 1 ) {
							$payload = $this->sign_response_payload( (array) $payload );
						}
						wp_send_json( $payload );
								} elseif ( $echo['rule'] == BBCS_RULE_ALLOW ) {
		$this->post_hash_cookie = $this->create_session_token( $this->time, $this->ip, $this->uid ) . '-' . $this->time;
						$payload                = BotBlockerStore::localCheckResult( BBCS_LOCAL_RESULT_COOKIE, 'ALLOW By rule: timezone=' . $this->post_timezone, $this->post_hash_cookie );
						if ( $this->settings->bbcs_ddos_resilience == 1 ) {
							$payload = $this->sign_response_payload( (array) $payload );
							if ( ! headers_sent() && ! empty( $this->uid ) ) {
								header( 'X-BBCS-' . $this->uid . ': ' . $this->post_hash_cookie, false );
							}
						}
						wp_send_json( $payload );
								} elseif ( $echo['rule'] == BBCS_RULE_GRAY ) {
						$this->post_from_suspect = 1;
						$this->result_of_action  = 'GRAY by RULE';
					}
				}
			}
		}
	}
}
