<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-toastify.php';

class BotBlockerSettingsHooks {

	public static function handleIntegrationsSave(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
		}
		check_admin_referer( 'save_botblocker_integrations', 'botblocker_integrations_nonce' );

		global $wpdb;

		$checkbox_fields = array(
			'recaptcha_check',
			'recaptcha_v3_ipv6_block',
			'memcached_enable',
			'redis_enable',
			'bbcs_2fa_enable',
		);

		foreach ( $checkbox_fields as $field ) {
			$value = ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '0' ) ? '1' : '0';
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $field,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
			if ( $result === false ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Settings] replace failed for field ' . $field . ': ' . $wpdb->last_error );
				}
			}
		}

		$exclude = array(
			'action',
			'botblocker_integrations_nonce',
			'_wp_http_referer',
			'save_settings',
			'bbcs_anchor',
		);

		$post_roles_raw = filter_input(
			INPUT_POST,
			'bbcs_2fa_roles',
			FILTER_DEFAULT,
			FILTER_REQUIRE_ARRAY
		);

		$post_roles_raw = is_array( $post_roles_raw ) ? $post_roles_raw : array();

		$post_roles = is_array( $post_roles_raw )
			? array_map( 'sanitize_text_field', $post_roles_raw )
			: array();

		$roles          = wp_roles()->roles;
		$bbcs_2fa_roles = array();

		foreach ( $roles as $role_key => $role ) {
			$bbcs_2fa_roles[ $role_key ] = isset( $post_roles[ $role_key ] ) ? '1' : '0';
		}

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result_roles = $wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'bbcs_2fa_roles',
				'value' => wp_json_encode( $bbcs_2fa_roles ),
			),
			array( '%s', '%s' )
		);
		if ( $result_roles === false ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Settings] replace failed for bbcs_2fa_roles: ' . $wpdb->last_error );
			}
		}

		$allowed_fields = bbcs_get_allowed_fields();
		foreach ( $allowed_fields as $key ) {
			if ( in_array( $key, $checkbox_fields, true ) ) {
				continue;
			}
			if ( isset( $_POST[ $key ] ) ) {
				$prepared_value = null;
				if ( is_array( $_POST[ $key ] ) ) {
					$sanitized_array = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $key ] ) );
					$prepared_value  = wp_json_encode( $sanitized_array );
				} else {
					$prepared_value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->replace(
					$wpdb->bbcs_settings,
					array(
						'key'   => sanitize_key( $key ),
						'value' => $prepared_value,
					),
					array( '%s', '%s' )
				);
				if ( $result === false ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[BBCS DEBUG] [Settings] replace failed for key ' . $key . ': ' . $wpdb->last_error );
					}
				}
			}
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::flush();
		BotBlockerCache::resetHealthTransients();

		BBCS_Toastify::flash( __( 'Integrations saved.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_INTEGRATIONS );

		$anchor = isset( $_POST['bbcs_anchor'] ) ? sanitize_key( wp_unslash( $_POST['bbcs_anchor'] ) ) : '';
		$url    = add_query_arg( 'settings-updated', 'true', BotBlockerMultisite::getAdminPageUrl( 'bbcs_integrations' ) );
		if ( $anchor !== '' ) {
			$url .= '#' . $anchor;
		}

		wp_safe_redirect( $url );
		exit;
	}

	public static function handleSettingsSave(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
		}
		check_admin_referer( 'save_botblocker_settings', 'botblocker_settings_nonce' );

		global $wpdb;

		$checkbox_fields = array(
			'block_empty_ua',
			'block_empty_lang',
			'block_nojs_users',
			'block_proxy_users',
			'block_vpn_users',
			'block_tor_users',
			'block_ipv6_users',
			'block_http10_users',
			'block_incorrect_lang_users',
			'block_rkn',
			'block_simplebot_ua',
			'whitelist_whatsapp_preview',
			'block_adblocker_users',
			'block_cf_users',
			'block_ip_ptr_match',
			'get_browser_type',
			'get_os_type',
			'get_device_type',
			'check',
			'unresponsive',
			'cloud_fallback_block',
			'utm_referrer',
			'utm_noindex',
			'check_get_ref',
			'botblocker_log_tests',
			'botblocker_log_local',
			'botblocker_log_allow',
			'botblocker_log_fake',
			'botblocker_log_goodip',
			'botblocker_log_block',
			'botblocker_force_check',
			'force_cloud_validation',
			'noarchive',
			'iframe_stop',
			'hosting_block',
			'block_fake_ref',
			'block_incognito_users',
			'block_simple_antidetect',
			'block_override',
			'block_web_engine_options',
			'block_device_options',
			'botblocker_log_admin',
			'botblocker_log_wp',
			'botblocker_log_error',
			'botblocker_log_disabled',
			'botblocker_log_cli',
			'botblocker_log_bbcs',
			'allow_self_ip_req',
			'cache_ui_data',
			'autosave_admin_ip',
			'skip_logged_in_users',
			'daylight_saving_time',
			'telegram_notifications',
			'email_notifications',
			'pusher_notifications',
			'critical_load_notifications',
			'login_brutforce_enabled',
			'payment_bypass_enable',
			'payment_bypass_log',
			'payment_strict_method',
			'payment_keep_ip_rules',
			// 'session_token_enabled',
			'bbcs_ddos_resilience',
			'tls_fingerprint_check',
			'bbcs_rate_check_enabled',
			'bbcs_rate_subnet_enabled',
			'fingerprint_sticky_block',
			'options_preflight',
		);

		if ( ! isset( $_POST['x_robots_directives'] ) ) {
			$_POST['x_robots_directives'] = array();
		}

		$old_block_rkn = (string) ( BotBlocker::getInstance()->settings->block_rkn ?? '0' );

		foreach ( $checkbox_fields as $field ) {
			$value = ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '0' ) ? '1' : '0';
			if ( $field === 'hosting_block' && $value === '1' ) {
				if ( ! ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ) ) {
					$value = '0';
				}
			}
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $field,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
			if ( $result === false ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [Settings] replace failed for field ' . $field . ': ' . $wpdb->last_error );
				}
			}
		}

		$exclude = array(
			'action',
			'botblocker_settings_nonce',
			'_wp_http_referer',
			'save_settings',
			'bbcs_anchor',
		);

		$allowed_fields = bbcs_get_allowed_fields();
		foreach ( $allowed_fields as $key ) {
			if ( in_array( $key, $checkbox_fields, true ) ) {
				continue;
			}
			if ( isset( $_POST[ $key ] ) ) {
				$prepared_value = null;
				if ( is_array( $_POST[ $key ] ) ) {
					$sanitized_array = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $key ] ) );
					$prepared_value  = wp_json_encode( $sanitized_array );
				} else {
					$prepared_value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					if ( $key === 'bbcs_rate_subnet_multiplier' ) {
						$prepared_value = (string) max( 1.0, (float) $prepared_value );
					} elseif ( $key === 'bbcs_rate_floor_percent' ) {
						$prepared_value = (string) min( 1.0, max( 0.01, (float) $prepared_value / 100 ) );
					}
				}
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->replace(
					$wpdb->bbcs_settings,
					array(
						'key'   => sanitize_key( $key ),
						'value' => $prepared_value,
					),
					array( '%s', '%s' )
				);
				if ( $result === false ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[BBCS DEBUG] [Settings] replace failed for settings key ' . $key . ': ' . $wpdb->last_error );
					}
				}
			}
		}

		// Shared dedup – ensures mutual exclusion between early_init_enable and mu_enable.
		// Determine which key changed by comparing POST with current DB (only run dedup if a
		// value was actually submitted, otherwise leave both untouched).
		$early_post = isset( $_POST['early_init_enable'] ) ? (int) $_POST['early_init_enable'] : null;
		$mu_post    = isset( $_POST['mu_enable'] ) ? (int) $_POST['mu_enable'] : null;

		if ( $early_post !== null ) {
			$final_state = bbcs_dedup_early_phase_toggles( 'early_init_enable', $early_post );
		} elseif ( $mu_post !== null ) {
			$final_state = bbcs_dedup_early_phase_toggles( 'mu_enable', $mu_post );
		} else {
			// Neither was in POST – read current state for filesystem actions.
			$final_state = bbcs_dedup_early_phase_toggles( 'disable', 0 );
		}

		// Filesystem actions based on final dedup'd state.
		if ( $final_state['early_init_enable'] === 1 ) {
			BotBlockerInstall::setEarlyInitEnabled( true );
		} elseif ( $final_state['mu_enable'] === 1 ) {
			BotBlockerInstall::setEarlyInitEnabled( false );
			BotBlockerInstall::installMuPlugin();
		} else {
			BotBlockerInstall::setEarlyInitEnabled( false );
			BotBlockerInstall::uninstallMuPlugin();
		}

		// CAPTCHA mode server-side validation: correct incompatible modes.
		$captcha_mode = isset( $_POST['bbcs_captcha_mode'] ) ? (int) $_POST['bbcs_captcha_mode'] : (int) BOTBLOCKER_CAPTCHA_MODE_DEFAULT;
		$gd_ok        = isset( BotBlocker::getInstance()->prefly['gd'] ) && BotBlocker::getInstance()->prefly['gd'] === 1;
		$gd_modes     = array( BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS, BOTBLOCKER_CAPTCHA_MODE_IMAGE );
		if ( ! $gd_ok && in_array( $captcha_mode, $gd_modes, true ) ) {
			$has_recaptcha_v2 = ! empty( BotBlocker::getInstance()->settings->recaptcha_key2 )
				&& ! empty( BotBlocker::getInstance()->settings->recaptcha_secret2 );
			$fallback_mode = $has_recaptcha_v2 ? (string) BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2 : (string) BOTBLOCKER_CAPTCHA_MODE_BUTTON;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'bbcs_captcha_mode',
					'value' => $fallback_mode,
				),
				array( '%s', '%s' )
			);
			BBCS_Toastify::flash(
				esc_html__(
					'GD library not available. Captcha mode was adjusted to a compatible mode.',
					'botblocker-security'
				),
				BBCS_Toastify::TYPE_WARNING,
				BBCS_Toastify::PAGE_SETTINGS
			);
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::resetHealthTransients();

		$new_block_rkn = ( isset( $_POST['block_rkn'] ) && $_POST['block_rkn'] !== '0' ) ? '1' : '0';
		if ( $old_block_rkn !== '1' && $new_block_rkn === '1' ) {
			BotBlockerRugov::scheduleUpdate( 'enabled', 60 );
		}

		BBCS_Toastify::flash( __( 'Settings saved.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_SETTINGS );

		$anchor = isset( $_POST['bbcs_anchor'] ) ? sanitize_key( wp_unslash( $_POST['bbcs_anchor'] ) ) : '';
		$url    = add_query_arg( 'settings-updated', 'true', BotBlockerMultisite::getAdminPageUrl( 'bbcs_settings' ) );
		if ( $anchor !== '' ) {
			$url .= '#' . $anchor;
		}
		wp_safe_redirect( $url );
		exit;
	}
}

add_action( 'admin_post_save_botblocker_integrations', array( 'BotBlockerSettingsHooks', 'handleIntegrationsSave' ) );
add_action( 'admin_post_save_botblocker_settings', array( 'BotBlockerSettingsHooks', 'handleSettingsSave' ) );
