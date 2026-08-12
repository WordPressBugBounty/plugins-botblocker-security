<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxBackup {

	private static function getTables(): array {
		global $wpdb;
		return array(
			'settings'  => $wpdb->bbcs_settings,
			'se'        => $wpdb->bbcs_se,
			'rules'     => $wpdb->bbcs_rules,
			'path'      => $wpdb->bbcs_path,
			'ipv6rules' => $wpdb->bbcs_ipv6rules,
			'ipv4rules' => $wpdb->bbcs_ipv4rules,
			'countries' => $wpdb->bbcs_countries,
		);
	}

	public static function handleBackup(): void {
		$bbcs_action = 'backup_handle_backup';
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

		// This approach avoids code duplication by programmatically truncating all relevant tables.

		global $wpdb;

		$tables = self::getTables();

		$backup_dir = BotBlockerMultisite::getDataDir() . 'backups';
		$temp_dir   = $backup_dir;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( ! WP_Filesystem() ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' filesystem init FAILED' );
			}
			wp_send_json_error( array( 'message' => __( 'Failed to initialize filesystem.', 'botblocker-security' ) ) );
		}
		global $wp_filesystem;

		if ( ! $wp_filesystem->is_dir( $temp_dir ) ) {
			$wp_filesystem->mkdir( $temp_dir, defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755 );
		}

		$token = bin2hex( random_bytes( 16 ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' token=' . $token );
		}
		if ( ! extension_loaded( 'zip' ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' zip extension not available' );
			}
			wp_send_json_error( array( 'message' => __( 'PHP zip extension is required for backups.', 'botblocker-security' ) ) );
		}

		$zip_file = $temp_dir . '/botblocker_backup_' . $token . '.zip';
		$zip      = new \ZipArchive();
		if ( $zip->open( $zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' ZIP create FAILED' );
			}
			wp_send_json_error( array( 'message' => __( 'Failed to create ZIP archive.', 'botblocker-security' ) ) );
		}

		foreach ( $tables as $table => $table_name ) {
			$dump_file = $temp_dir . '/' . $table . '_' . $token . '.sql';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->get_results( "SELECT * FROM `{$table_name}`", ARRAY_A );
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' table=' . $table . ' rows=' . ( is_array( $result ) ? count( $result ) : 0 ) );
			}

			$dump_content = '';
			if ( $result ) {
				foreach ( $result as $row ) {
					$dump_content .= wp_json_encode( $row ) . "\n";
				}
			}

			if ( false === file_put_contents( $dump_file, $dump_content ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[BBCS DEBUG] [AJAX] Failed to write backup dump file: ' . $dump_file );
				}
				$zip->close();
				foreach ( $tables as $t => $tn ) {
					$df = trailingslashit( $temp_dir ) . $t . '_' . $token . '.sql';
					if ( file_exists( $df ) ) {
						wp_delete_file( $df );
					}
				}
				BotBlockerCache::clearFileCache();
				wp_send_json_error( array( 'message' => __( 'Failed to create backup file.', 'botblocker-security' ) ) );
			}
			$zip->addFile( $dump_file, $table . '.sql' );
		}

		$zip->close();

		foreach ( $tables as $table => $table_name ) {
			$dump_file = trailingslashit( $temp_dir ) . $table . '_' . $token . '.sql';

			if ( file_exists( $dump_file ) ) {
				wp_delete_file( $dump_file );
			}
		}

		BotBlockerCache::clearFileCache();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cache cleared, backup complete' );
		}

		set_transient( 'bbcs_backup_download_' . $token, $zip_file, 600 );

		$download_url = add_query_arg(
			array(
				'action' => 'bbcs_download_backup',
				'token'  => $token,
				'nonce'  => wp_create_nonce( 'bbcs_download_backup_' . $token ),
			),
			admin_url( 'admin-ajax.php' )
		);

		wp_send_json_success(
			array(
				'download_url' => $download_url,
				'message'      => __( 'Backup was successful.', 'botblocker-security' ),
			)
		);
	}

	public static function handleDownload(): void {
		$bbcs_action = 'backup_handle_download';
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' called' );
		}
		if ( ! isset( $_GET['token'] ) || ! isset( $_GET['nonce'] ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' missing token or nonce' );
			}
			wp_die( esc_html__( 'Invalid request.', 'botblocker-security' ) );
		}

		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' token=' . $token );
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'bbcs_download_backup_' . $token ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce verification FAILED' );
			}
			wp_die( esc_html__( 'Security check failed.', 'botblocker-security' ) );
		}
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' nonce verification passed' );
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

		$zip_file = get_transient( 'bbcs_backup_download_' . $token );
		if ( ! $zip_file || ! file_exists( $zip_file ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' zip_file not found or expired: ' . ( $zip_file ?: 'empty' ) );
			}
			wp_die( esc_html__( 'Backup file not found or expired.', 'botblocker-security' ) );
		}

		delete_transient( 'bbcs_backup_download_' . $token );

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="botblocker_backup_' . gmdate( 'Ymd' ) . '.zip"' );
		header( 'Content-Length: ' . filesize( $zip_file ) );
		header( 'X-Robots-Tag: noindex' );

		$sent = readfile( $zip_file );    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		if ( $sent === false ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS] Backup download failed for: ' . $zip_file );
		}

		wp_delete_file( $zip_file );
		wp_die();
	}

	public static function handleImport(): void {
		$bbcs_action = 'backup_handle_import';
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

		$tables = self::getTables();

		$uploads  = wp_upload_dir();
		$temp_dir = trailingslashit( $uploads['basedir'] ) . 'botblocker_backups';

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( WP_Filesystem() ) {
			global $wp_filesystem;
			if ( ! $wp_filesystem->is_dir( $temp_dir ) ) {
				$wp_filesystem->mkdir( $temp_dir, defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755 );
			}
		}

		$zip_file = '';
		if ( isset( $_FILES['zip_file'] ) && isset( $_FILES['zip_file']['tmp_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- is_uploaded_file() validates authenticity below
			$zip_file = wp_normalize_path( wp_unslash( $_FILES['zip_file']['tmp_name'] ) );
		}
		if ( $zip_file === '' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' zip_file not provided' );
			}
			wp_send_json_error( array( 'message' => __( 'ZIP file not provided.', 'botblocker-security' ) ) );
		}
		if ( ! apply_filters( 'bbcs_test_allow_fake_upload', false, $zip_file ) && ! is_uploaded_file( $zip_file ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' invalid upload: ' . $zip_file );
			}
			wp_send_json_error( array( 'message' => __( 'Invalid upload.', 'botblocker-security' ) ) );
		}

		if ( ! extension_loaded( 'zip' ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' zip extension not available' );
			}
			wp_send_json_error( array( 'message' => __( 'PHP zip extension is required for imports.', 'botblocker-security' ) ) );
		}

		$zip = new \ZipArchive();
		if ( $zip->open( $zip_file ) !== true ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' failed to open ZIP: ' . $zip_file );
			}
			wp_send_json_error( array( 'message' => __( 'Failed to open ZIP archive.', 'botblocker-security' ) ) );
		}

		$allowed_files = array_keys( $tables );
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry = $zip->getNameIndex( $i );
			$name  = basename( $entry );
			if ( $name !== pathinfo( $name, PATHINFO_FILENAME ) . '.sql' || ! in_array( pathinfo( $name, PATHINFO_FILENAME ), $allowed_files, true ) ) {
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' unexpected file in ZIP: ' . $name );
				}
				$zip->close();
				wp_send_json_error( array( 'message' => __( 'ZIP contains unexpected file:', 'botblocker-security' ) . ' ' . $name ) );
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' ZIP validated, extracting' );
		}
		$zip->extractTo( $temp_dir );
		$zip->close();

		self::processImportData( $temp_dir, $tables );

		$dir_handle = opendir( $temp_dir );
		if ( $dir_handle ) {
			while ( ( $file = readdir( $dir_handle ) ) !== false ) {
				if ( $file === '.' || $file === '..' ) {
					continue; }
				$full = $temp_dir . '/' . $file;
				if ( is_file( $full ) ) {
					wp_delete_file( $full ); }
			}
			closedir( $dir_handle );
		}
		if ( WP_Filesystem() ) {
			global $wp_filesystem;
			if ( $wp_filesystem->is_dir( $temp_dir ) ) {
				$wp_filesystem->rmdir( $temp_dir );
			}
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerDb::generateAllFiles();
		BotBlockerSummary::truncate();

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' import successful' );
		}
		wp_send_json_success(
			array(
				'message' => __( 'Import was successful.', 'botblocker-security' ),
			)
		);
	}

	public static function processImportData( string $temp_dir, array $tables ): void {
		global $wpdb;

		$all_data = array();
		foreach ( $tables as $table => $table_name ) {
			$dump_file = $temp_dir . '/' . $table . '.sql';

			if ( ! file_exists( $dump_file ) ) {
				// translators: %s is the database table name.
				wp_send_json_error( array( 'message' => sprintf( __( 'Dump file for table %s not found.', 'botblocker-security' ), $table ) ) );
			}

			$dump_content = file_get_contents( $dump_file );
			if ( $dump_content === false ) {
				// translators: %s is the database table name.
				wp_send_json_error( array( 'message' => sprintf( __( 'Failed to read dump file for table %s.', 'botblocker-security' ), $table ) ) );
			}

			$rows       = explode( "\n", $dump_content );
			$valid_rows = array();
			$line       = 0;

			foreach ( $rows as $row ) {
				++$line;
				if ( empty( $row ) ) {
					continue;
				}
				$data = json_decode( $row, true );
				if ( $data === null || ! is_array( $data ) ) {
					// translators: %1$s is the table name, %2$d is the line number.
					wp_send_json_error( array( 'message' => sprintf( __( 'Invalid JSON in %1$s at line %2$d.', 'botblocker-security' ), $table, $line ) ) );
				}
				if ( $table === 'rules' && isset( $data['search'] ) ) {
					unset( $data['search'] );
				}
				$valid_rows[] = $data;
			}

			$all_data[ $table ] = $valid_rows;
		}

		foreach ( $all_data as $table => $rows ) {
			$table_name = $tables[ $table ];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE TABLE `{$table_name}`" );

			foreach ( $rows as $data ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->insert( $table_name, $data );
				if ( $result === false ) {
					// translators: %s is the table name.
					wp_send_json_error( array( 'message' => sprintf( __( 'Failed to insert row into %s.', 'botblocker-security' ), $table ) ) );
				}
			}
		}
	}
}
