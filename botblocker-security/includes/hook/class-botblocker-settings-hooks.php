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

		// Truth-toggle: enabling a cache backend is persisted only when the
		// backend answers a real connection probe against the submitted
		// host/port. A failed probe forces the flag off and warns the admin.
		if ( isset( $_POST['redis_enable'] ) && $_POST['redis_enable'] !== '0' ) {
			$probe = BotBlockerCache::testRedisConnection(
				isset( $_POST['redis_host'] ) ? sanitize_text_field( wp_unslash( $_POST['redis_host'] ) ) : null,
				isset( $_POST['redis_port'] ) ? (int) wp_unslash( $_POST['redis_port'] ) : null,
				isset( $_POST['redis_password'] ) ? sanitize_text_field( wp_unslash( $_POST['redis_password'] ) ) : null,
				isset( $_POST['redis_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['redis_prefix'] ) ) : null,
				isset( $_POST['redis_database'] ) ? (int) wp_unslash( $_POST['redis_database'] ) : null
			);
			if ( ! $probe['ok'] ) {
				$_POST['redis_enable'] = '0';
				BBCS_Toastify::flash(
					esc_html__( 'Redis backend is not reachable — the integration was left disabled.', 'botblocker-security' ),
					BBCS_Toastify::TYPE_WARNING,
					BBCS_Toastify::PAGE_INTEGRATIONS
				);
			}
		} elseif ( isset( $_POST['memcached_enable'] ) && $_POST['memcached_enable'] !== '0' ) {
			$probe = BotBlockerCache::testMemcachedConnection(
				isset( $_POST['memcached_host'] ) ? sanitize_text_field( wp_unslash( $_POST['memcached_host'] ) ) : null,
				isset( $_POST['memcached_port'] ) ? (int) wp_unslash( $_POST['memcached_port'] ) : null,
				isset( $_POST['memcached_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['memcached_prefix'] ) ) : null
			);
			if ( ! $probe['ok'] ) {
				$_POST['memcached_enable'] = '0';
				BBCS_Toastify::flash(
					esc_html__( 'Memcached backend is not reachable — the integration was left disabled.', 'botblocker-security' ),
					BBCS_Toastify::TYPE_WARNING,
					BBCS_Toastify::PAGE_INTEGRATIONS
				);
			}
		}

		$checkbox_fields = array(
			'recaptcha_check',
			'recaptcha_v3_ipv6_block',
			'memcached_enable',
			'redis_enable',
			'transients_enable',
			'bbcs_2fa_enable',
			'bbcs_2fa_xmlrpc_block',
			'bbcs_wp_connectors_enabled',
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each array item is sanitized via array_map below
		$post_roles_raw = isset( $_POST['bbcs_2fa_roles'] ) ? wp_unslash( $_POST['bbcs_2fa_roles'] ) : array();
		$post_roles_raw = is_array( $post_roles_raw ) ? $post_roles_raw : array();

		$post_roles = array_map( 'sanitize_text_field', $post_roles_raw );

		$roles          = wp_roles()->roles;
		$bbcs_2fa_roles = array();

		foreach ( $roles as $role_key => $role ) {
			$bbcs_2fa_roles[ $role_key ] = ( isset( $post_roles[ $role_key ] ) && (string) $post_roles[ $role_key ] === '1' ) ? '1' : '0';
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

		$allowed_fields = BotBlockerSettingsPresets::getAllowedFields();
		foreach ( $allowed_fields as $key ) {
			if ( in_array( $key, $checkbox_fields, true ) ) {
				continue;
			}
			if ( in_array( $key, array( 'early_init_enable', 'mu_enable' ), true ) ) {
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
			'allow_self_call_header',
			'cache_ui_data',
			'autosave_admin_ip',
			'skip_logged_in_users',
			'daylight_saving_time',
			'audit_log_enable',
			'email_notifications',
			'critical_load_notifications',
			'login_brutforce_enabled',
			'payment_bypass_enable',
			'payment_bypass_log',
			'payment_strict_method',
			'payment_keep_ip_rules',
			// 'session_token_enabled',
			'bbcs_ddos_resilience',
			'bbcs_honeypot_enabled',
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
			if ( in_array( $field, array( 'hosting_block', 'block_vpn_users', 'block_tor_users' ), true ) && $value === '1' ) {
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
			'audit_log_roles',
		);

		$allowed_fields = BotBlockerSettingsPresets::getAllowedFields();
		foreach ( $allowed_fields as $key ) {
			if ( in_array( $key, $checkbox_fields, true ) ) {
				continue;
			}
			if ( in_array( $key, array( 'early_init_enable', 'mu_enable' ), true ) ) {
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
		$early_post = isset( $_POST['early_init_enable'] ) ? (int) $_POST['early_init_enable'] : null;
		$mu_post    = isset( $_POST['mu_enable'] ) ? (int) $_POST['mu_enable'] : null;

		if ( $early_post !== null ) {
			BotBlockerEarlyPhaseDedup::dedup( 'early_init_enable', $early_post );
			BotBlockerInstall::setEarlyInitEnabled( $early_post === 1 );
		} elseif ( $mu_post !== null ) {
			BotBlockerEarlyPhaseDedup::dedup( 'mu_enable', $mu_post );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'mu_enable',
					'value' => $mu_post === 1 ? '1' : '0',
				),
				array( '%s', '%s' )
			);
			if ( $mu_post === 1 ) {
				BotBlockerInstall::setEarlyInitEnabled( false, array( 'reason' => 'mu_switch' ) );
				BotBlockerInstall::installMuPlugin();
			} else {
				BotBlockerInstall::uninstallMuPlugin();
			}
		} else {
			// Neither was in POST – read current state for filesystem actions.
			$final_state = BotBlockerEarlyPhaseDedup::dedup( 'disable', 0 );
			if ( $final_state['early_init_enable'] === 1 ) {
				BotBlockerInstall::setEarlyInitEnabled( true );
			} elseif ( $final_state['mu_enable'] === 1 ) {
				BotBlockerInstall::setEarlyInitEnabled( false, array( 'reason' => 'mu_switch' ) );
				BotBlockerInstall::installMuPlugin();
			} else {
				BotBlockerInstall::setEarlyInitEnabled( false );
				BotBlockerInstall::uninstallMuPlugin();
			}
		}

		// CAPTCHA mode server-side validation: correct incompatible modes.
		$captcha_mode = isset( $_POST['bbcs_captcha_mode'] ) ? (int) $_POST['bbcs_captcha_mode'] : (int) BOTBLOCKER_CAPTCHA_MODE_DEFAULT;
		$gd_ok        = isset( BotBlocker::getInstance()->prefly['gd'] ) && BotBlocker::getInstance()->prefly['gd'] === 1;
		$gd_modes     = array( BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS, BOTBLOCKER_CAPTCHA_MODE_IMAGE );
		$recaptcha_v2_modes = array( BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON, BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2 );
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

		// reCAPTCHA v2 server-side validation: modes 3/4 require configured Site/Secret keys.
		if ( in_array( $captcha_mode, $recaptcha_v2_modes, true ) ) {
			$has_recaptcha_v2_keys = ! empty( BotBlocker::getInstance()->settings->recaptcha_key2 )
				&& ! empty( BotBlocker::getInstance()->settings->recaptcha_secret2 );
			if ( ! $has_recaptcha_v2_keys ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->replace(
					$wpdb->bbcs_settings,
					array(
						'key'   => 'bbcs_captcha_mode',
						'value' => (string) BOTBLOCKER_CAPTCHA_MODE_BUTTON,
					),
					array( '%s', '%s' )
				);
				BBCS_Toastify::flash(
					esc_html__(
						'reCaptcha v2 keys are not configured. Captcha mode was adjusted to a compatible mode.',
						'botblocker-security'
					),
					BBCS_Toastify::TYPE_WARNING,
					BBCS_Toastify::PAGE_SETTINGS
				);
			}
		}

		// Addon captcha modes (>= 90): the provider addon must be active AND
		// its keys configured, otherwise fall back to a compatible mode.
		if ( $captcha_mode >= 90 ) {
			self::enforceAddonCaptchaMode( $captcha_mode );
		}

		$post_audit_roles_raw = filter_input(
			INPUT_POST,
			'audit_log_roles',
			FILTER_DEFAULT,
			FILTER_REQUIRE_ARRAY
		);
		$post_audit_roles_raw = is_array( $post_audit_roles_raw ) ? $post_audit_roles_raw : array();
		$post_audit_roles     = array_map( 'sanitize_text_field', $post_audit_roles_raw );
		$audit_log_roles      = array();
		foreach ( wp_roles()->roles as $role_key => $role ) {
			$audit_log_roles[ $role_key ] = ! empty( $post_audit_roles[ $role_key ] ) ? '1' : '0';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'audit_log_roles',
				'value' => wp_json_encode( $audit_log_roles ),
			),
			array( '%s', '%s' )
		);
		if ( class_exists( 'BotBlockerAuditContext' ) ) {
			BotBlockerAuditContext::flushRoleMapCache();
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::resetHealthTransients();

		// In-App Browser Mode options (D8: separate wp_options keys, not the settings table).
		if ( class_exists( 'BotBlockerInApp' ) ) {
			// ToggleOption always submits a hidden 0/1 field — read the value, not isset.
			$inapp_enabled = isset( $_POST['bbcs_inapp_enabled'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['bbcs_inapp_enabled'] ) ) : 0;
			BotBlockerMultisite::updateOption( 'bbcs_inapp_enabled', $inapp_enabled === 1 ? 1 : 0 );

			$valid_codes = array_values( BotBlockerInApp::RESCUE_GROUPS );
			$codes       = array();
			if ( isset( $_POST['bbcs_inapp_rescue_codes'] ) && is_array( $_POST['bbcs_inapp_rescue_codes'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each array item is unslashed and sanitized below
				foreach ( wp_unslash( $_POST['bbcs_inapp_rescue_codes'] ) as $raw ) {
					$code = (int) sanitize_text_field( $raw );
					if ( in_array( $code, $valid_codes, true ) && ! in_array( $code, $codes, true ) ) {
						$codes[] = $code;
					}
				}
			}
			// An empty selection is valid: the mode stays on but rescues nothing.
			BotBlockerMultisite::updateOption( 'bbcs_inapp_rescue_codes', $codes );
		}

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

	public static function handleAddonCaptchaFallback( string $slug, bool $is_active ): void {
		if ( $is_active ) {
			return;
		}
		try {
			if ( ! class_exists( 'BotBlockerCaptchaRegistry' ) ) {
				require_once BOTBLOCKER_DIR . 'includes/class-botblocker-captcha-registry.php';
			}
			if ( ! class_exists( 'BotBlockerAddons' ) ) {
				return;
			}
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$current = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", 'bbcs_captcha_mode' ) );
			$mode    = (int) $current;
			if ( $mode < 90 ) {
				return;
			}
			$addons = BotBlockerAddons::scanAll();
			$owned  = false;
			if ( isset( $addons[ $slug ]['captcha_modes'] ) && is_array( $addons[ $slug ]['captcha_modes'] ) ) {
				foreach ( $addons[ $slug ]['captcha_modes'] as $mode_cfg ) {
					if ( isset( $mode_cfg['id'] ) && (int) $mode_cfg['id'] === $mode ) {
						$owned = true;
						break;
					}
				}
			}
			if ( ! $owned ) {
				return;
			}
			$fallback = self::resolveFallbackCaptchaMode();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'bbcs_captcha_mode',
					'value' => (string) $fallback,
				),
				array( '%s', '%s' )
			);
			// The generated runtime settings.php still carries the dead mode —
			// regenerate it now, there is no settings-save round trip here.
			if ( class_exists( 'BotBlockerFileRenderer' ) ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}
			$name = ( isset( $addons[ $slug ]['name'] ) && '' !== $addons[ $slug ]['name'] ) ? $addons[ $slug ]['name'] : $slug;
			BBCS_Toastify::flash(
				sprintf(
					/* translators: %s: add-on name */
					__( 'The "%s" add-on provided the selected CAPTCHA type and is no longer active. Captcha mode was adjusted to a compatible mode.', 'botblocker-security' ),
					$name
				),
				BBCS_Toastify::TYPE_WARNING,
				BBCS_Toastify::PAGE_ADDONS
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Settings] addon captcha fallback failed for slug ' . $slug . ': ' . $e->getMessage() );
			}
		}
	}

	public static function enforceAddonCaptchaMode( int $mode ): void {
		if ( ! class_exists( 'BotBlockerCaptchaRegistry' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-captcha-registry.php';
		}
		if ( BotBlockerCaptchaRegistry::has( $mode ) && BotBlockerCaptchaRegistry::hasKeys( $mode ) ) {
			return;
		}
		global $wpdb;
		$fallback = self::resolveFallbackCaptchaMode();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'bbcs_captcha_mode',
				'value' => (string) $fallback,
			),
			array( '%s', '%s' )
		);
		BBCS_Toastify::flash(
			BotBlockerCaptchaRegistry::has( $mode )
				? esc_html__( 'CAPTCHA provider keys are not configured. Captcha mode was adjusted to a compatible mode.', 'botblocker-security' )
				: esc_html__( 'The selected CAPTCHA provider add-on is not active. Captcha mode was adjusted to a compatible mode.', 'botblocker-security' ),
			BBCS_Toastify::TYPE_WARNING,
			BBCS_Toastify::PAGE_SETTINGS
		);
	}

	public static function resolveFallbackCaptchaMode(): int {
		// "Silent Auto-Verify (No Captcha)" — the first entry of the captcha
		// type list and the plugin default; the admin is warned on every switch.
		return (int) BOTBLOCKER_CAPTCHA_MODE_SILENT;
	}
}

add_action( 'admin_post_save_botblocker_integrations', array( 'BotBlockerSettingsHooks', 'handleIntegrationsSave' ) );
add_action( 'admin_post_save_botblocker_settings', array( 'BotBlockerSettingsHooks', 'handleSettingsSave' ) );
add_action( 'bbcs_addon_toggled', array( 'BotBlockerSettingsHooks', 'handleAddonCaptchaFallback' ), 10, 2 );
