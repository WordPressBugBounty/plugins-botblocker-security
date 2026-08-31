<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxCache {

	public static function handleToggleRedisMemcached(): void {
		$bbcs_action = 'cache_toggle_redis_memcached';
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

		global $wpdb;

		if ( ! isset( $_POST['redis_enable'] ) || ! isset( $_POST['memcached_enable'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing POST params' );
			}
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'botblocker-security' ) ) );
		}

		$redis_enable     = intval( wp_unslash( $_POST['redis_enable'] ) );
		$memcached_enable = intval( wp_unslash( $_POST['memcached_enable'] ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' redis_enable=' . $redis_enable . ' memcached_enable=' . $memcached_enable );
		}

		// Truth-toggle: an enable is persisted only when the backend answers a
		// real connection probe; the two backends stay mutually exclusive.
		if ( $redis_enable === 1 ) {
			$probe = BotBlockerCache::testRedisConnection();
			if ( ! $probe['ok'] ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' redis probe failed: ' . (string) $probe['error'] );
				}
				wp_send_json_error(
					array(
						'message' => __( 'Redis backend is not reachable.', 'botblocker-security' ),
						'error'   => (string) $probe['error'],
					)
				);
			}
			$memcached_enable = 0;
		} elseif ( $memcached_enable === 1 ) {
			$probe = BotBlockerCache::testMemcachedConnection();
			if ( ! $probe['ok'] ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' memcached probe failed: ' . (string) $probe['error'] );
				}
				wp_send_json_error(
					array(
						'message' => __( 'Memcached backend is not reachable.', 'botblocker-security' ),
						'error'   => (string) $probe['error'],
					)
				);
			}
			$redis_enable = 0;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $wpdb->bbcs_settings, array( 'value' => $redis_enable ), array( 'key' => 'redis_enable' ) );
		$wpdb->update( $wpdb->bbcs_settings, array( 'value' => $memcached_enable ), array( 'key' => 'memcached_enable' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' DB updates done' );
		}

		BotBlockerFileRenderer::generateSettingsFile();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' settings file generated' );
		}
		BotBlockerCache::flush();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache flushed, sending success' );
		}

		wp_send_json_success( array( 'message' => __( 'Success!', 'botblocker-security' ) ) );
	}

	public static function handleSwitchPtrCacheInDb(): void {
		$bbcs_action = 'cache_switch_ptr';
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

		global $wpdb;

		if ( ! isset( $_POST['ptr_cache_in_db'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing POST params' );
			}
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'botblocker-security' ) ) );
		}

		$ptr_cache_in_db = intval( wp_unslash( $_POST['ptr_cache_in_db'] ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' ptr_cache_in_db=' . $ptr_cache_in_db );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $wpdb->bbcs_settings, array( 'value' => $ptr_cache_in_db ), array( 'key' => 'ptr_cache_in_db' ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' DB update done' );
		}

		BotBlockerFileRenderer::generateSettingsFile();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' settings file generated' );
		}
		BotBlockerCache::flush();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache flushed, sending success' );
		}

		wp_send_json_success( array( 'message' => __( 'Success!', 'botblocker-security' ) ) );
	}

	public static function handleSwitchUiCacheInDb(): void {
		$bbcs_action = 'cache_switch_ui';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		global $wpdb;

		if ( ! isset( $_POST['cache_ui_data'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'botblocker-security' ) ) );
		}

		$cache_ui_data = intval( wp_unslash( $_POST['cache_ui_data'] ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $wpdb->bbcs_settings, array( 'value' => $cache_ui_data ), array( 'key' => 'cache_ui_data' ) );

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::flush();

		wp_send_json_success( array( 'message' => __( 'Success!', 'botblocker-security' ) ) );
	}
}
