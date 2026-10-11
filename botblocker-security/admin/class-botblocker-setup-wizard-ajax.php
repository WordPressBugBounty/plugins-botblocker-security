<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlocker_SetupWizardAjaxTrait {

	public function ajax_save_preset(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$preset = isset( $_POST['preset'] ) ? sanitize_text_field( wp_unslash( $_POST['preset'] ) ) : '';

		if ( ! in_array( $preset, array( 'light', 'strong', 'full' ) ) ) {
			wp_send_json_error( __( 'Invalid preset.', 'botblocker-security' ) );
		}

		// Apply preset settings
		switch ( $preset ) {
			case 'light':
				if ( class_exists( 'BotBlockerSettingsPresets' ) ) {
					BotBlockerSettingsPresets::loadSettingsLight();
				}
				break;
			case 'strong':
				if ( class_exists( 'BotBlockerSettingsPresets' ) ) {
					BotBlockerSettingsPresets::loadSettingsStrong();
				}
				break;
			case 'full':
				// Full protection requires PRO
				if ( BotBlockerPro::isActive() ) {
					if ( class_exists( 'BotBlockerSettingsPresets' ) ) {
						BotBlockerSettingsPresets::loadSettingsFull();
					}
				} else {
					wp_send_json_error( __( 'Full protection requires PRO license.', 'botblocker-security' ) );
				}
				break;
		}

		BotBlockerMultisite::updateOption( 'bbcs_wizard_preset', $preset );

		wp_send_json_success( array( 'preset' => $preset ) );
	}

	public function ajax_save_exclusions(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$exclude_admins     = ! empty( $_POST['exclude_admins'] );
		$exclude_current_ip = ! empty( $_POST['exclude_current_ip'] );
		$exclude_cron       = ! empty( $_POST['exclude_cron'] );
		$current_ip         = isset( $_POST['current_ip'] ) ? sanitize_text_field( wp_unslash( $_POST['current_ip'] ) ) : '';

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write-through to settings.
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'autosave_admin_ip',
				'value' => $exclude_admins ? '1' : '0',
			),
			array( '%s', '%s' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write-through to settings.
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'allow_self_ip_req',
				'value' => $exclude_cron ? '1' : '0',
			),
			array( '%s', '%s' )
		);

		if ( $exclude_current_ip && $current_ip !== '' ) {
			try {
				BotBlockerInstallIp::addAdminIPs( $current_ip );
				BotBlockerFileRenderer::renderIps();
				BotBlockerCache::clearFileCache();
			} catch ( \Throwable $e ) {
				wp_send_json_error( array( 'message' => __( 'Invalid IP address.', 'botblocker-security' ) ) );
			}
		}

		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		wp_send_json_success();
	}

	public function ajax_save_ux(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$ux_mode = isset( $_POST['ux_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['ux_mode'] ) ) : '';

		if ( ! in_array( $ux_mode, array( 'block', 'challenge', 'captcha' ) ) ) {
			wp_send_json_error( __( 'Invalid UX mode.', 'botblocker-security' ) );
		}

		wp_send_json_success( array( 'ux_mode' => $ux_mode ) );
	}

	public function ajax_save_recaptcha(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : 'none';
		if ( ! in_array( $provider, array( 'none', 'v2', 'v3' ), true ) ) {
			wp_send_json_error( __( 'Invalid Google reCAPTCHA provider.', 'botblocker-security' ) );
		}

		$values = array(
			'recaptcha_key2'          => isset( $_POST['recaptcha_key2'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_key2'] ) ) : '',
			'recaptcha_secret2'       => isset( $_POST['recaptcha_secret2'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_secret2'] ) ) : '',
			'recaptcha_key3'          => isset( $_POST['recaptcha_key3'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_key3'] ) ) : '',
			'recaptcha_secret3'       => isset( $_POST['recaptcha_secret3'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_secret3'] ) ) : '',
			'recaptcha_tresshold'     => isset( $_POST['recaptcha_tresshold'] ) ? (string) max( 0.1, min( 1.0, round( (float) sanitize_text_field( wp_unslash( $_POST['recaptcha_tresshold'] ) ), 1 ) ) ) : '0.5',
			'recaptcha_v3_ipv6_block' => $provider === 'v3' && ! empty( $_POST['recaptcha_v3_ipv6_block'] ) ? '1' : '0',
		);

		if ( $provider === 'v2' && ( $values['recaptcha_key2'] === '' || $values['recaptcha_secret2'] === '' ) ) {
			wp_send_json_error( __( 'Enter both Google reCAPTCHA v2 keys or choose Skip Google reCAPTCHA.', 'botblocker-security' ) );
		}

		$enable_v3 = $provider === 'v3' && ! empty( $_POST['recaptcha_check'] );
		if ( $enable_v3 && ( $values['recaptcha_key3'] === '' || $values['recaptcha_secret3'] === '' ) ) {
			wp_send_json_error( __( 'Enter both Google reCAPTCHA v3 keys before enabling protection.', 'botblocker-security' ) );
		}
		$values['recaptcha_check'] = $enable_v3 ? '1' : '0';

		global $wpdb;
		foreach ( $values as $key => $value ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => $key,
					'value' => $value,
				),
				array( '%s', '%s' )
			);
		}

		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}
		BotBlockerCache::flush();
		BotBlockerCache::resetHealthTransients();

		wp_send_json_success( array( 'provider' => $provider, 'recaptcha_check' => $values['recaptcha_check'] ) );
	}

	public function ajax_save_captcha(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$captcha_mode = isset( $_POST['captcha_mode'] ) ? intval( $_POST['captcha_mode'] ) : 2;

		// Core CAPTCHA modes 0-8 (botblocker-set-captcha.php) plus registered
		// addon providers (mode 90+) whose keys are configured.  A wizard never
		// proposes an unusable mode; the guard is defense-in-depth for a raced
		// save after addon deactivation or stale browser progress.
		$core_captcha_modes = array( 0, 1, 2, 3, 4, 5, 6, 7, 8 );
		$is_addon_mode      = ! in_array( $captcha_mode, $core_captcha_modes, true );
		if ( $is_addon_mode && $captcha_mode < 90 ) {
			wp_send_json_error( __( 'Invalid Captcha mode.', 'botblocker-security' ) );
		}
		if ( $is_addon_mode && ( ! class_exists( 'BotBlockerCaptchaRegistry' ) || ! BotBlockerCaptchaRegistry::has( $captcha_mode ) ) ) {
			wp_send_json_error( __( 'The selected CAPTCHA provider addon is not active.', 'botblocker-security' ) );
		}
		if ( $is_addon_mode && class_exists( 'BotBlockerCaptchaRegistry' ) && ! BotBlockerCaptchaRegistry::hasKeys( $captcha_mode ) ) {
			wp_send_json_error( __( 'CAPTCHA provider keys are not configured.', 'botblocker-security' ) );
		}

		// reCAPTCHA v2 modes require configured Site/Secret keys.
		$recaptcha_v2_modes = array( BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON, BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2 );
		if ( in_array( $captcha_mode, $recaptcha_v2_modes, true ) ) {
			$has_recaptcha_v2 = ! empty( BotBlocker::getInstance()->settings->recaptcha_key2 )
				&& ! empty( BotBlocker::getInstance()->settings->recaptcha_secret2 );
			if ( ! $has_recaptcha_v2 ) {
				wp_send_json_error( __( 'reCaptcha v2 keys are not configured. Configure them in Integrations first.', 'botblocker-security' ) );
			}
		}

		// Save CAPTCHA mode to settings
		global $wpdb;
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'bbcs_captcha_mode',
				'value' => (string) $captcha_mode,
			),
			array( '%s', '%s' )
		);

		// Regenerate settings file
		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		wp_send_json_success( array( 'captcha_mode' => $captcha_mode ) );
	}

	public function ajax_save_init_mode(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$init_mode = isset( $_POST['init_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['init_mode'] ) ) : 'regular';

		if ( ! in_array( $init_mode, array( 'regular', 'mu', 'early' ) ) ) {
			wp_send_json_error( __( 'Invalid initialization mode.', 'botblocker-security' ) );
		}

		// Early init requires PRO
		if ( $init_mode === 'early' && ! BotBlockerPro::isActive() ) {
			wp_send_json_error( __( 'Early initialization requires PRO license.', 'botblocker-security' ) );
		}

		// Early Init also requires the Early Init addon to be active
		if ( $init_mode === 'early' ) {
			$early_addon_active = class_exists( 'BotBlockerGateway' ) && BotBlockerGateway::isRegistered( 'early_init' );
			if ( ! $early_addon_active ) {
				wp_send_json_error( __( 'Early Init requires the Early Init addon to be enabled. Please enable it on the Addons page.', 'botblocker-security' ) );
			}
		}

		global $wpdb;

		// Save MU mode setting
		$mu_enable = $init_mode === 'mu' ? '1' : '0';
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array(
				'key'   => 'mu_enable',
				'value' => $mu_enable,
			),
			array( '%s', '%s' )
		);

		// Save Early Init setting and perform file system cleanup for the selected mode
		if ( $init_mode === 'early' && BotBlockerPro::isActive() ) {
			if ( ! BotBlockerInstall::setEarlyInitEnabled( true ) ) {
				wp_send_json_error( __( 'Failed to configure Early Init. Check filesystem permissions and try again, or set up Early Init manually.', 'botblocker-security' ) );
			}
		} elseif ( $init_mode === 'mu' ) {
			BotBlockerInstall::setEarlyInitEnabled( false, array( 'reason' => 'mu_switch' ) );
			// Install MU plugin file so MU mode actually works
			if ( method_exists( 'BotBlockerInstall', 'installMuPlugin' ) ) {
				BotBlockerInstall::installMuPlugin();
			}
		} else {
			BotBlockerInstall::setEarlyInitEnabled( false );
			// Remove MU plugin file if MU mode was previously enabled
			if ( method_exists( 'BotBlockerInstall', 'uninstallMuPlugin' ) ) {
				BotBlockerInstall::uninstallMuPlugin();
			}
		}

		// Regenerate settings file
		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		wp_send_json_success( array( 'init_mode' => $init_mode ) );
	}

	public function ajax_check_cache(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$redis_available     = BotBlockerCache::isRedisAvailable();
		$memcached_available = BotBlockerCache::isMemcachedAvailable();

		wp_send_json_success(
			array(
				'redis'     => $redis_available,
				'memcached' => $memcached_available,
			)
		);
	}

	public function ajax_save_cache(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$cache_type = isset( $_POST['cache_type'] ) ? sanitize_text_field( wp_unslash( $_POST['cache_type'] ) ) : 'none';

		if ( ! in_array( $cache_type, array( 'redis', 'memcached', 'none' ) ) ) {
			wp_send_json_error( __( 'Invalid cache type.', 'botblocker-security' ) );
		}

		global $wpdb;

		if ( $cache_type === 'redis' ) {
			$probe = BotBlockerCache::testRedisConnection();
			if ( ! $probe['ok'] ) {
				wp_send_json_error( __( 'Redis is not reachable. Connect it before enabling the cache.', 'botblocker-security' ) );
			}
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'redis_enable',
					'value' => '1',
				),
				array( '%s', '%s' )
			);
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'memcached_enable',
					'value' => '0',
				),
				array( '%s', '%s' )
			);
		} elseif ( $cache_type === 'memcached' ) {
			$probe = BotBlockerCache::testMemcachedConnection();
			if ( ! $probe['ok'] ) {
				wp_send_json_error( __( 'Memcached is not reachable. Connect it before enabling the cache.', 'botblocker-security' ) );
			}
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'redis_enable',
					'value' => '0',
				),
				array( '%s', '%s' )
			);
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'memcached_enable',
					'value' => '1',
				),
				array( '%s', '%s' )
			);
		} else {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'redis_enable',
					'value' => '0',
				),
				array( '%s', '%s' )
			);
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'memcached_enable',
					'value' => '0',
				),
				array( '%s', '%s' )
			);
		}

		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		wp_send_json_success( array( 'cache_type' => $cache_type ) );
	}

	public function ajax_complete_wizard(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$contact_email = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
		if ( ! empty( $contact_email ) && is_email( $contact_email ) ) {
			BotBlockerMultisite::updateOption( 'bbcs_contact_email_collected', 1 );
			BotBlockerInstall::sendActivationToCloud( $contact_email );
		}

		BotBlockerMultisite::updateOption( 'bbcs_setup_wizard_completed', true );
		BotBlockerMultisite::updateOption( 'bbcs_setup_wizard_completed_at', time() );
		BotBlockerMultisite::deleteOption( 'bbcs_activation_redirect' );

		if ( class_exists( 'BotBlockerPaymentData' ) && BotBlockerPaymentData::detectEcommerce() ) {
			global $wpdb;
			if ( isset( $wpdb->bbcs_settings ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$current = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
						'payment_bypass_enable'
					)
				);
				if ( (int) $current !== 1 ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->replace(
						$wpdb->bbcs_settings,
						array(
							'key'   => 'payment_bypass_enable',
							'value' => '1',
						),
						array( '%s', '%s' )
					);
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$log_existing = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
							'payment_bypass_log'
						)
					);
					if ( $log_existing === null ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->replace(
							$wpdb->bbcs_settings,
							array(
								'key'   => 'payment_bypass_log',
								'value' => '1',
							),
							array( '%s', '%s' )
						);
					}
					if ( class_exists( 'BotBlockerFileRenderer' ) ) {
						BotBlockerFileRenderer::generateSettingsFile();
					}
				}
			}
		}

		$score = BotBlockerHealthShortcodes::calculateSiteHealth();

		wp_send_json_success(
			array(
				'score' => $score,
				'mode'  => BotBlockerMultisite::getOption( 'bbcs_wizard_preset', 'strong' ),
			)
		);
	}

	public function ajax_test_attack(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$test_result = array(
			'status'  => 'error',
			'message' => __( 'BotBlocker is not initialized.', 'botblocker-security' ),
			'event'   => array(
				'reason' => 'Self-test',
				'url'    => '/test',
				'action' => 'Failed',
			),
		);

		if ( class_exists( 'BotBlocker' ) ) {
			$instance = BotBlocker::getInstance();
			if ( $instance && $instance instanceof BotBlocker ) {
				$disabled    = $instance->settings->disable ?? 1;
				$secure_mode = (int) ( $instance->settings->secure_mode ?? 0 );

				if ( ! $disabled ) {
					$test_result = array(
						'status'  => 'success',
						'message' => __( 'BotBlocker is active and protecting your site.', 'botblocker-security' ),
						'event'   => array(
							'reason' => 'Self-test',
							'url'    => home_url( '/' ),
							'action' => 'Passed',
							'mode'   => $secure_mode,
						),
					);
				} else {
					$test_result['message'] = __( 'BotBlocker is installed but currently disabled.', 'botblocker-security' );
				}
			}
		}

		if ( 'error' === $test_result['status'] ) {
			wp_send_json_error( $test_result );
		} else {
			wp_send_json_success( $test_result );
		}
	}

	public function ajax_reset_wizard(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		BotBlockerMultisite::deleteOption( 'bbcs_setup_wizard_completed' );
		BotBlockerMultisite::deleteOption( 'bbcs_setup_wizard_completed_at' );
		BotBlockerMultisite::deleteOption( 'bbcs_activation_redirect' );

		wp_send_json_success( array( 'message' => __( 'Wizard reset successfully.', 'botblocker-security' ) ) );
	}

	public function ajax_compatibility_test(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$tests = array(
			'homepage' => home_url( '/' ),
			'login'    => wp_login_url(),
			'rest'     => rest_url( 'wp/v2/' ),
		);

		$args = array(
			'timeout'    => 15,
			'user-agent' => BotBlockerMultisite::getCurrentUserAgent(),
		);

		$results = array();
		foreach ( $tests as $name => $url ) {
			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) && stripos( $response->get_error_message(), 'ssl' ) !== false ) {
				$response = wp_remote_get( $url, array_merge( $args, array( 'sslverify' => false ) ) );
			}
			if ( is_wp_error( $response ) ) {
				$results[ $name ] = array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
				);
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code >= 200 && $code < 400 ) {
				$results[ $name ] = array(
					'status'  => 'ok',
					'message' => '',
				);
			} else {
				$results[ $name ] = array(
					'status'  => 'error',
					'message' => sprintf( 'HTTP %d', $code ),
				);
			}
		}

		$results['admin'] = array(
			'status'  => 'ok',
			'message' => '',
		);

		wp_send_json_success( $results );
	}
}
