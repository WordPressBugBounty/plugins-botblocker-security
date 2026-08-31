<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxEarlyPhase {

	public static function handleToggleEarlyPhase(): void {
		$bbcs_action = 'early_phase_toggle_early_phase';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce check passed' );
		}
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check FAILED' );
			}
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		// Start output buffering to avoid breaking JSON with warnings/notices

		if ( ! isset( $_POST['setting'] ) || ! in_array( wp_unslash( $_POST['setting'] ), array( 'mu_enable', 'early_init_enable', 'disable', 'early_geo_enable', 'mu_geo_enable' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid setting.', 'botblocker-security' ) ) );
		}

		$setting_key = sanitize_text_field( wp_unslash( $_POST['setting'] ) );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $setting_key is sanitized above
		if ( ! isset( $_POST[ $setting_key ] ) || ! is_numeric( wp_unslash( $_POST[ $setting_key ] ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid setting value.', 'botblocker-security' ) ) );
		}

		$setting_value = intval( wp_unslash( $_POST[ $setting_key ] ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' setting_key=' . $setting_key . ' setting_value=' . $setting_value );
		}

		global $wpdb;

		if ( $setting_key === 'early_init_enable' && $setting_value === 1 ) {
			$cloud_api_active   = ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() );
			$early_addon_active = class_exists( 'BotBlockerGateway' ) && BotBlockerGateway::isRegistered( 'early_init' );
			if ( ! $early_addon_active || ! $cloud_api_active ) {
				wp_send_json_error( array( 'message' => __( 'Early Init requires Cloud API and the Early Init addon to be enabled.', 'botblocker-security' ) ) );
			}
		}

		$fs_error       = null;
		$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php';

		try {
			if ( $setting_key === 'mu_enable' ) {
				if ( $setting_value ) {
					BotBlockerInstall::setEarlyInitEnabled( false, array( 'reason' => 'mu_switch' ) );
					BotBlockerInstall::installMuPlugin();
					if ( ! file_exists( $mu_plugin_file ) ) {
						$fs_error = __( 'Failed to install MU plugin. Check filesystem permissions.', 'botblocker-security' );
					}
				} else {
					BotBlockerInstall::uninstallMuPlugin();
				}
			} elseif ( $setting_key === 'early_init_enable' ) {
				if ( $setting_value ) {
					if ( ! BotBlockerInstall::setEarlyInitEnabled( true ) ) {
						$fs_error = __( 'Failed to write to wp-config.php. Check filesystem permissions.', 'botblocker-security' );
					}
				} else {
					BotBlockerInstall::setEarlyInitEnabled( false );
				}
			}

			if ( $fs_error !== null ) {
				wp_send_json_error( array( 'message' => $fs_error ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$old_value = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", $setting_key ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace( $wpdb->bbcs_settings, array( 'key' => $setting_key, 'value' => $setting_value ), array( '%s', '%s' ) );

			do_action( 'bbcs_audit_protection_toggled', $setting_key, $old_value, $setting_value );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' DB update executed' );
			}

			// Shared dedup – ensures mutual exclusion between early_init_enable and mu_enable.
			$final_state = BotBlockerEarlyPhaseDedup::dedup( $setting_key, $setting_value );

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' before generateSettingsFile' );
			}
			BotBlockerFileRenderer::generateSettingsFile();

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' success' );
			}
			wp_send_json_success( array(
				'message'          => __( 'Success!', 'botblocker-security' ),
				'final_state'      => $final_state,
				'mu_loader_exists' => ( $setting_key === 'mu_enable' && file_exists( $mu_plugin_file ) ),
			) );
		} catch ( \Throwable $e ) {
			if ( $e instanceof \WPDieException ) {
				throw $e;
			}
			if ( $setting_key === 'early_init_enable' && class_exists( 'BotBlockerAddons' ) ) {
				BotBlockerAddons::panicEarlyInitLayer( $e );
				wp_send_json_error( array( 'message' => __( 'Early Init failed and was switched off to keep the site running.', 'botblocker-security' ) ) );
			}
			wp_send_json_error( array( 'message' => __( 'Operation failed.', 'botblocker-security' ) ) );
		}
	}
}
