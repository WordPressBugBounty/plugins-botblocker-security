<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxProfile {

	public static function handleApplyProfile(): void {
		$bbcs_action = 'profile_apply';
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
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}
		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' mode=' . $mode );
		}
		if ( ! in_array( $mode, array( 'light', 'medium', 'strong', 'strong_api', 'full', 'emergency' ), true ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid mode' );
			}
			wp_send_json_error( array( 'message' => __( 'Invalid mode.', 'botblocker-security' ) ) );
		}
		if ( $mode === 'full' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' applying full profile' );
			}
			if ( ! ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' BotBlockerPro not active' );
				}
				wp_send_json_error( array( 'message' => __( 'Full profile requires Cloud API connection to be active.', 'botblocker-security' ) ) );
			}
			if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' settings presets not available' );
				}
				wp_send_json_error( array( 'message' => __( 'Full profile function is not available.', 'botblocker-security' ) ) );
			}
			BotBlockerSettingsPresets::loadSettingsFull();
		} elseif ( $mode === 'emergency' ) {
			if ( ! ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ) ) {
				wp_send_json_error( array( 'message' => __( 'Emergency profile requires Cloud API connection to be active.', 'botblocker-security' ) ) );
			}
			if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
				wp_send_json_error( array( 'message' => __( 'Emergency profile function is not available.', 'botblocker-security' ) ) );
			}
			BotBlockerSettingsPresets::loadSettingsEmergency();
		} elseif ( $mode === 'medium' ) {
			if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
				wp_send_json_error( array( 'message' => __( 'Medium profile function is not available.', 'botblocker-security' ) ) );
			}
			BotBlockerSettingsPresets::loadSettingsMedium();
		} elseif ( $mode === 'strong' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' applying strong profile' );
			}
			if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' settings presets not available' );
				}
				wp_send_json_error( array( 'message' => __( 'Strong profile function is not available.', 'botblocker-security' ) ) );
			}
			BotBlockerSettingsPresets::loadSettingsStrong();
		} elseif ( 'strong_api' === $mode ) {
			if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
				wp_send_json_error( array( 'message' => __( 'Strong API profile function is not available.', 'botblocker-security' ) ) );
			}
			BotBlockerSettingsPresets::loadSettingsStrongApi();
		} elseif ( $mode === 'light' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' applying light profile' );
			}
			if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' settings presets not available' );
				}
				wp_send_json_error( array( 'message' => __( 'Balanced profile function is not available.', 'botblocker-security' ) ) );
			}
			BotBlockerSettingsPresets::loadSettingsLight();
		}

		BotBlockerMultisite::updateOption( 'bbcs_wizard_preset', $mode );
		BotBlockerCache::resetHealthTransients();

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' profile applied successfully' );
		}
		wp_send_json_success( array( 'message' => __( 'Profile applied.', 'botblocker-security' ) ) );
	}
}
