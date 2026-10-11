<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxDebug {

	public static function resolveDebugLogPath(): string {
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		if ( defined( 'WP_DEBUG_LOG' ) ) {
			$wp_debug_log = constant( 'WP_DEBUG_LOG' );
			if ( is_string( $wp_debug_log ) && $wp_debug_log !== '' && $wp_debug_log !== '1' && $wp_debug_log !== '0' ) {
				$debug_log_path = $wp_debug_log;
			}
		}
		return $debug_log_path;
	}

	public static function handleCreateSaltFile(): void {
		$bbcs_action = 'debug_create_salt';
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
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$rotated = self::rotateVisitorSalt();

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' rotateVisitorSalt result=' . ( $rotated ? 'true' : 'false' ) );
		}
		if ( $rotated ) {
			BotBlockerCache::flush();
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache flushed, sending success' );
			}
			wp_send_json_success( array( 'message' => __( 'Visitor cookies cleared. Old cookies no longer work.', 'botblocker-security' ) ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: rotation failed' );
			}
			wp_send_json_error( array( 'message' => __( 'Could not clear visitor cookies.', 'botblocker-security' ) ) );
		}
	}

	private static function rotateVisitorSalt(): bool {
		global $wpdb;
		if ( ! isset( $wpdb->bbcs_settings ) || $wpdb->bbcs_settings === '' ) {
			return false;
		}

		$new_salt = bin2hex( random_bytes( 16 ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- per-site visitor-salt rotation.
		$updated = $wpdb->update( $wpdb->bbcs_settings, array( 'value' => $new_salt ), array( 'key' => 'salt' ), array( '%s' ), array( '%s' ) );

		$bb = BotBlocker::getInstance();
		if ( isset( $bb->settings ) ) {
			$bb->settings->salt = $new_salt;
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::clearFileCache();

		return $updated !== false;
	}

	public static function handleClearDebugLog(): void {
		$bbcs_action = 'debug_clear_log';
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
			wp_send_json_error( array( 'message' => __( 'Not enough permissions to perform the operation.', 'botblocker-security' ) ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$debug_log_path = self::resolveDebugLogPath();
		$result         = false;

		if ( file_exists( $debug_log_path ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' log file exists, clearing' );
			}
			// phpcs:ignore PluginCheck.CodeAnalysis.WriteFile.PluginDirectoryWrite
			$result = file_put_contents( $debug_log_path, '' );
		} elseif ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' log file not found' );
		}

		if ( $result !== false ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cleared, sending success' );
			}
			wp_send_json_success( array( 'message' => __( 'Log file cleared successfully.', 'botblocker-security' ) ) );
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: not cleared' );
			}
			wp_send_json_error( array( 'message' => __( 'Not cleared: log file not found or insufficient permissions.', 'botblocker-security' ) ) );
		}
	}

	public static function handleDownloadDebugLog(): void {
		$bbcs_action = 'debug_download_log';
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
			wp_send_json_error( array( 'message' => __( 'Not enough permissions to perform the operation.', 'botblocker-security' ) ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$debug_log_path = self::resolveDebugLogPath();

		if ( file_exists( $debug_log_path ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' log file exists, creating download URL' );
			}
			$token        = bin2hex( random_bytes( 16 ) );
			$download_url = add_query_arg(
				array(
					'action' => 'bbcs_download_debug_log',
					'token'  => $token,
					'nonce'  => wp_create_nonce( 'bbcs_debug_log_' . $token ),
				),
				admin_url( 'admin-ajax.php' )
			);

			set_transient( 'bbcs_debug_log_' . $token, $debug_log_path, 300 );

			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' token=' . $token . ', sending success' );
			}
			wp_send_json_success(
				array(
					'download_url' => $download_url,
					'message'      => __( 'Log file is ready for download.', 'botblocker-security' ),
				)
			);
		} else {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' send error: log file not found' );
			}
			wp_send_json_error( array( 'message' => __( 'Log file not found.', 'botblocker-security' ) ) );
		}
	}

	public static function handleStreamDebugLog(): void {
		$bbcs_action = 'debug_stream_log';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		if ( ! isset( $_GET['token'] ) || ! isset( $_GET['nonce'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing GET params' );
			}
			wp_die( esc_html__( 'Invalid request.', 'botblocker-security' ) );
		}

		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'bbcs_debug_log_' . $token ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce verification FAILED' );
			}
			wp_die( esc_html__( 'Security check failed.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce check passed' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check FAILED' );
			}
			wp_die( esc_html__( 'Unauthorized.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check passed' );
		}

		$log_path = get_transient( 'bbcs_debug_log_' . $token );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' token=' . $token . ' log_path=' . ( $log_path ? $log_path : 'null' ) );
		}
		if ( ! $log_path || ! file_exists( $log_path ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' log file not found or expired' );
			}
			wp_die( esc_html__( 'Log file not found or expired.', 'botblocker-security' ) );
		}

		delete_transient( 'bbcs_debug_log_' . $token );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' streaming file: ' . $log_path );
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="wordpress_debug_' . gmdate( 'Ymd' ) . '.log"' );
			header( 'Content-Length: ' . filesize( $log_path ) );
			header( 'X-Robots-Tag: noindex' );
		}

		$sent = readfile( $log_path );    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		if ( $sent === false ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS] Debug log download failed for: ' . $log_path );
			}
		}
		wp_die();
	}
}
