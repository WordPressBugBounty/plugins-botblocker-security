<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
		if ( ! in_array( $mode, array( 'light', 'strong', 'full' ), true ) ) {
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
			if ( ! function_exists( 'bbcs_loadSettingsFull' ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' bbcs_loadSettingsFull not available' );
				}
				wp_send_json_error( array( 'message' => __( 'Full profile function is not available.', 'botblocker-security' ) ) );
			}
			bbcs_loadSettingsFull();
		} elseif ( $mode === 'strong' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' applying strong profile' );
			}
			if ( ! function_exists( 'bbcs_loadSettingsStrong' ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' bbcs_loadSettingsStrong not available' );
				}
				wp_send_json_error( array( 'message' => __( 'Strong profile function is not available.', 'botblocker-security' ) ) );
			}
			bbcs_loadSettingsStrong();
		} elseif ( $mode === 'light' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' applying light profile' );
			}
			if ( ! function_exists( 'bbcs_loadSettingsLight' ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' bbcs_loadSettingsLight not available' );
				}
				wp_send_json_error( array( 'message' => __( 'Light profile function is not available.', 'botblocker-security' ) ) );
			}
			bbcs_loadSettingsLight();
		}

		BotBlockerCache::resetHealthTransients();

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' profile applied successfully' );
		}
		wp_send_json_success( array( 'message' => __( 'Profile applied.', 'botblocker-security' ) ) );
	}
}
