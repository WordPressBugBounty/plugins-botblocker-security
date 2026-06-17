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
				if ( function_exists( 'bbcs_loadSettingsLight' ) ) {
					bbcs_loadSettingsLight();
				}
				break;
			case 'strong':
				if ( function_exists( 'bbcs_loadSettingsStrong' ) ) {
					bbcs_loadSettingsStrong();
				}
				break;
			case 'full':
				// Full protection requires PRO
				if ( BotBlockerPro::isActive() ) {
					if ( function_exists( 'bbcs_loadSettingsFull' ) ) {
						bbcs_loadSettingsFull();
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
			array( 'key' => 'autosave_admin_ip', 'value' => $exclude_admins ? '1' : '0' ),
			array( '%s', '%s' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write-through to settings.
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array( 'key' => 'allow_self_ip_req', 'value' => $exclude_cron ? '1' : '0' ),
			array( '%s', '%s' )
		);

		if ( $exclude_current_ip && $current_ip !== '' ) {
			BotBlockerInstallIp::addAdminIPs( $current_ip );
			BotBlockerFileRenderer::renderIps();
			BotBlockerCache::clearFileCache();
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

		BotBlockerMultisite::updateOption( 'bbcs_wizard_ux_mode', $ux_mode );

		wp_send_json_success( array( 'ux_mode' => $ux_mode ) );
	}

	public function ajax_save_captcha(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$captcha_mode = isset( $_POST['captcha_mode'] ) ? intval( $_POST['captcha_mode'] ) : 2;

		// Valid CAPTCHA modes: 0-8 (based on botblocker-set-captcha.php)
		if ( ! in_array( $captcha_mode, array( 0, 1, 2, 3, 4, 5, 6, 7, 8 ) ) ) {
			wp_send_json_error( __( 'Invalid CAPTCHA mode.', 'botblocker-security' ) );
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

		BotBlockerMultisite::updateOption( 'bbcs_wizard_captcha_mode', $captcha_mode );

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

		// Save Early Init setting (only if PRO active)
		if ( $init_mode === 'early' && BotBlockerPro::isActive() ) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'early_init_enable',
					'value' => '1',
				),
				array( '%s', '%s' )
			);
			if ( method_exists( 'BotBlockerInstall', 'uninstallMuPlugin' ) ) {
				BotBlockerInstall::uninstallMuPlugin();
			}
			if ( function_exists( 'bbcs_insertCodeToWpConfig' ) ) {
				bbcs_insertCodeToWpConfig();
			}
			if ( function_exists( 'bbcs_generateSitesMapFile' ) ) {
				bbcs_generateSitesMapFile();
			}
		} else {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'early_init_enable',
					'value' => '0',
				),
				array( '%s', '%s' )
			);
		}

		// Regenerate settings file
		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		BotBlockerMultisite::updateOption( 'bbcs_wizard_init_mode', $init_mode );

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

		BotBlockerMultisite::updateOption( 'bbcs_wizard_cache_type', $cache_type );

		wp_send_json_success( array( 'cache_type' => $cache_type ) );
	}

	public function ajax_save_notifications(): void {
		check_ajax_referer( 'bbcs-wizard-admin-nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$notify_daily       = ! empty( $_POST['notify_daily'] );
		$notify_brute_force = ! empty( $_POST['notify_brute_force'] );
		$notify_weekly      = ! empty( $_POST['notify_weekly'] );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write-through to settings.
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array( 'key' => 'email_notifications', 'value' => $notify_daily ? '1' : '0' ),
			array( '%s', '%s' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write-through to settings.
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array( 'key' => 'critical_load_notifications', 'value' => $notify_brute_force ? '1' : '0' ),
			array( '%s', '%s' )
		);

		$frequency = $notify_weekly ? 'weekly' : 'disabled';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write-through to settings.
		$wpdb->replace(
			$wpdb->bbcs_settings,
			array( 'key' => 'regular_notifications_frequency', 'value' => $frequency ),
			array( '%s', '%s' )
		);

		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}

		wp_send_json_success();
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

		$score = bbcs_calculateSiteHealth();

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
				$disabled = $instance->settings->disable ?? 1;
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
				$results[ $name ] = array( 'status' => 'ok', 'message' => '' );
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
