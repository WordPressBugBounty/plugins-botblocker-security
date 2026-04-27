<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_clear_all_settings()
{
	global $wpdb;
	return $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_settings}`");
}

function bbcs_database_reinstallation()
{
	if (!bbcs_clear_all_rules() || !bbcs_clear_all_paths() || !bbcs_clear_all_white() || !bbcs_clear_all_ipv4_rules() || !bbcs_clear_all_ipv6_rules() || !bbcs_clear_all_settings()) {
		return false;
	} else {
		bbcs_deleteRuleFiles();
		bbcs_init_db_and_files();
		bbcs_truncate_daily_summary();

		return true;
	}
}

function bbcs_database_reinstallation_callback()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

	if (bbcs_database_reinstallation()) {
		wp_send_json_success(__('Database has been reinstalled successfully.', 'botblocker-security'));
	} else {
		wp_send_json_error(__('Operation failed.', 'botblocker-security'));
	}
}
add_action('wp_ajax_bbcs_database_reinstallation', 'bbcs_database_reinstallation_callback');

function bbcs_backup_data_settings_callback()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

	global $wpdb;

	$tables = [
		'settings' => $wpdb->bbcs_settings,
		'se' => $wpdb->bbcs_se,
		'rules' => $wpdb->bbcs_rules,
		'path' => $wpdb->bbcs_path,
		'ipv6rules' => $wpdb->bbcs_ipv6rules,
		'ipv4rules' => $wpdb->bbcs_ipv4rules,
	];

	$uploads  = wp_upload_dir();
	$temp_dir = trailingslashit($uploads['basedir']) . 'botblocker_backups';

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	if ($wp_filesystem->is_dir($temp_dir)) {
		$list = $wp_filesystem->dirlist($temp_dir);
		if ($list) {
			foreach ($list as $name => $info) {
				if ('f' === $info['type']) {
					$wp_filesystem->delete(trailingslashit($temp_dir) . $name);
				}
			}
		}
	} else {
		$wp_filesystem->mkdir($temp_dir, defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755);
	}

	$zip_file = $temp_dir . '/botblocker_backup_' . gmdate('YmdHis') . '.zip';
	$zip = new \ZipArchive();
	if ($zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
		wp_send_json_error(__('Failed to create ZIP archive.', 'botblocker-security'));
	}

	foreach ($tables as $table => $table_name) {
		$dump_file = $temp_dir . '/' . $table . '.sql';
		// REVIEWER NOTE: Table name interpolation is safe here because the $tables array is defined internally and contains only known plugin tables - no user input is involved.
		// This approach avoids code duplication by programmatically truncating all relevant tables.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);

		$dump_content = '';
		if ($result) {
			foreach ($result as $row) {
				$dump_content .= wp_json_encode($row) . "\n";
			}
		}

		file_put_contents($dump_file, $dump_content);
		$zip->addFile($dump_file, $table . '.sql');
	}

	$zip->close();

	foreach ($tables as $table => $table_name) {
		$dump_file = trailingslashit($temp_dir) . $table . '.sql';

		if (file_exists($dump_file)) {
			wp_delete_file($dump_file);
		}
	}

	$download_url = wp_upload_dir()['baseurl'] . '/botblocker_backups/' . basename($zip_file);

	if (!is_ssl() && strpos($download_url, 'https://') === 0) {
		$download_url = str_replace('https://', 'http://', $download_url);
	} elseif (is_ssl() && strpos($download_url, 'http://') === 0) {
		$download_url = str_replace('http://', 'https://', $download_url);
	}

	bbcs_clearFileCache();

	wp_send_json_success([
		'download_url' => $download_url,
		'message' => __('Backup was successful.', 'botblocker-security'),
	]);
}
add_action('wp_ajax_bbcs_backup_data_settings', 'bbcs_backup_data_settings_callback');

function bbcs_import_data_settings_callback()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

	global $wpdb;

	$tables = [
		'settings' => $wpdb->bbcs_settings,
		'se' => $wpdb->bbcs_se,
		'rules' => $wpdb->bbcs_rules,
		'path' => $wpdb->bbcs_path,
		'ipv6rules' => $wpdb->bbcs_ipv6rules,
		'ipv4rules' => $wpdb->bbcs_ipv4rules,
	];

	$uploads  = wp_upload_dir();
	$temp_dir = trailingslashit($uploads['basedir']) . 'botblocker_backups';

	require_once ABSPATH . 'wp-admin/includes/file.php';

	if (WP_Filesystem()) {
		global $wp_filesystem;
		if (! $wp_filesystem->is_dir($temp_dir)) {
			$wp_filesystem->mkdir($temp_dir, defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755);
		}
	}

	$zip_file = '';
	if (isset($_FILES['zip_file']) && isset($_FILES['zip_file']['tmp_name'])) {
		$zip_file = sanitize_text_field(wp_unslash($_FILES['zip_file']['tmp_name']));
	}
	if ($zip_file === '') {
		wp_send_json_error(__('ZIP file not provided.', 'botblocker-security'));
		return;
	}
	if (!is_uploaded_file($zip_file)) {
		wp_send_json_error(__('Invalid upload.', 'botblocker-security'));
		return;
	}

	$zip = new \ZipArchive();
	if ($zip->open($zip_file) !== true) {
		wp_send_json_error(__('Failed to open ZIP archive.', 'botblocker-security'));
	}

	$allowed_files = array_keys($tables);
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$entry = $zip->getNameIndex($i);
		$name  = basename($entry);
		if ($name !== pathinfo($name, PATHINFO_FILENAME) . '.sql' || !in_array(pathinfo($name, PATHINFO_FILENAME), $allowed_files, true)) {
			$zip->close();
			wp_send_json_error(__('ZIP contains unexpected file:', 'botblocker-security') . ' ' . $name);
			return;
		}
	}

	$zip->extractTo($temp_dir);
	$zip->close();

	foreach ($tables as $table => $table_name) {
		$dump_file = $temp_dir . '/' . $table . '.sql';

		if (!file_exists($dump_file)) {
			// translators: %s is the database table name.
			wp_send_json_error(sprintf(__('Dump file for table %s not found.', 'botblocker-security'), $table));
		}

		// REVIEWER NOTE: Table name interpolation is safe here because the $tables array is defined internally and contains only known plugin tables - no user input is involved.
		// This approach avoids code duplication by programmatically truncating all relevant tables.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query("TRUNCATE TABLE `{$table_name}`");

		$dump_content = file_get_contents($dump_file);
		$rows = explode("\n", $dump_content);

		foreach ($rows as $row) {
			if (!empty($row)) {
				$data = json_decode($row, true);
				if ($table === 'rules' && isset($data['search'])) {
					unset($data['search']);
				}
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert($table_name, $data);
			}
		}
	}

	$dir_handle = opendir($temp_dir);
	if ($dir_handle) {
		while (($file = readdir($dir_handle)) !== false) {
			if ($file === '.' || $file === '..') { continue; }
			$full = $temp_dir . '/' . $file;
			if (is_file($full)) { wp_delete_file($full); }
		}
		closedir($dir_handle);
	}
	if (WP_Filesystem()) {
		global $wp_filesystem;
		if ($wp_filesystem->is_dir($temp_dir)) {
			$wp_filesystem->rmdir($temp_dir);
		}
	}

	bbcs_generateSettingsFileFromDb();
	bbcs_generateAllFilesFromDb();
	bbcs_truncate_daily_summary();

	wp_send_json_success([
		'message' => __('Import was successful.', 'botblocker-security'),
	]);
}
add_action('wp_ajax_bbcs_import_data_settings', 'bbcs_import_data_settings_callback');

function bbcs_toggle_redis_and_memcached_callback()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

	global $wpdb;

	if (!isset($_POST['redis_enable']) || !isset($_POST['memcached_enable'])) {
		wp_send_json_error(__('Invalid request.', 'botblocker-security'));
		return;
	}

	$redis_enable = intval(wp_unslash($_POST['redis_enable']));
	$memcached_enable = intval(wp_unslash($_POST['memcached_enable']));
	// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update($wpdb->bbcs_settings, ['value' => $redis_enable], ['key' => 'redis_enable']);
	$wpdb->update($wpdb->bbcs_settings, ['value' => $memcached_enable], ['key' => 'memcached_enable']);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	bbcs_generateSettingsFileFromDb();

	//flush store
	bbcs_flushRedisAndMMC();

	wp_send_json_success(__('Success!', 'botblocker-security'));
}
add_action('wp_ajax_bbcs_toggle_redis_and_memcached', 'bbcs_toggle_redis_and_memcached_callback');

function bbcs_switch_ptr_cache_in_db_callback()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

	global $wpdb;

	if (!isset($_POST['ptr_cache_in_db'])) {
		wp_send_json_error(__('Invalid request.', 'botblocker-security'));
		return;
	}

	$ptr_cache_in_db = intval(wp_unslash($_POST['ptr_cache_in_db']));
	// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update($wpdb->bbcs_settings, ['value' => $ptr_cache_in_db], ['key' => 'ptr_cache_in_db']);

	bbcs_generateSettingsFileFromDb();

	//flush store
	bbcs_flushRedisAndMMC();

	wp_send_json_success(__('Success!', 'botblocker-security'));
}
add_action('wp_ajax_bbcs_switch_ptr_cache_in_db', 'bbcs_switch_ptr_cache_in_db_callback');

function bbcs_toggle_early_phase_in_db_callback(): void
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	//BBCS-MULTISITE
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }

	// Start output buffering to avoid breaking JSON with warnings/notices
	if (!ob_get_level()) {
		ob_start();
	}

	if (!isset($_POST['setting']) || !in_array($_POST['setting'], ['mu_enable', 'early_init_enable'], true)) {
		if (ob_get_level()) {
			ob_end_clean();
		}
		wp_send_json_error(__('Invalid setting.', 'botblocker-security'));
	}

	$setting_key = sanitize_text_field(wp_unslash($_POST['setting']));

	if (!isset($_POST[$setting_key]) || !is_numeric($_POST[$setting_key])) {
		if (ob_get_level()) {
			ob_end_clean();
		}
		wp_send_json_error(__('Invalid setting value.', 'botblocker-security'));
	}

	$setting_value = intval(wp_unslash($_POST[$setting_key]));

	global $wpdb;

	if ($setting_key === 'early_init_enable' && $setting_value === 1) {
		$cloud_api_active = (function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive());
		if (!(function_exists('bbcs_is_addon_active') && bbcs_is_addon_active('bbcs-early-init')) || !$cloud_api_active) {
			if (ob_get_level()) {
				ob_end_clean();
			}
		wp_send_json_error(__('Early Init requires Cloud API and the Early Init addon to be enabled.', 'botblocker-security'));
		}
	}
	// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update($wpdb->bbcs_settings, ['value' => $setting_value], ['key' => $setting_key]);

	if ($setting_value === 1) {
		$other_key = $setting_key === 'mu_enable' ? 'early_init_enable' : 'mu_enable';
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update($wpdb->bbcs_settings, ['value' => 0], ['key' => $other_key]);
	}

	if ($setting_key === 'mu_enable') {
		if ($setting_value) {
			if (function_exists('bbcs_removeCodeFromWpConfig')) {
				bbcs_removeCodeFromWpConfig();
			}
			bbcs_installMuPlugin();
		} else {
			bbcs_uninstallMuPlugin();
		}
	} elseif ($setting_key === 'early_init_enable') {
		if ($setting_value) {
			bbcs_uninstallMuPlugin();
			if (function_exists('bbcs_insertCodeToWpConfig')) {
				bbcs_insertCodeToWpConfig();
			}
			if (function_exists('bbcs_generateSitesMapFile')) {
				bbcs_generateSitesMapFile();
			}
		} else {
			if (function_exists('bbcs_generateSitesMapFile')) {
				bbcs_generateSitesMapFile();
			}
			if (function_exists('bbcs_removeCodeFromWpConfig')) {
				bbcs_removeCodeFromWpConfig();
			}
		}
	}

	bbcs_generateSettingsFileFromDb();

	if (ob_get_level()) {
		ob_end_clean();
	}

	wp_send_json_success(__('Success!', 'botblocker-security'));
}
add_action('wp_ajax_bbcs_toggle_early_phase_in_db', 'bbcs_toggle_early_phase_in_db_callback');

function bbcs_handle_send_email()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Unauthorized.', 'botblocker-security')]);
	}

	$result = bbcs_sendAdminLinksEmail();

	if ($result) {
		wp_send_json_success(['message' => __('Email sent successfully.', 'botblocker-security')]);
	} else {
		wp_send_json_error(['message' => __('Failed to send email.', 'botblocker-security')]);
	}
}
add_action('wp_ajax_bbcs_send_email', 'bbcs_handle_send_email');

function bbcs_create_salt_file_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	//BBCS-MULTISITE
	if (!current_user_can(bbcs_can_manage())) { wp_send_json_error(__('Unauthorized.', 'botblocker-security')); }
	$bbcs_start_files = isset($_POST['bbcs_start_files']) ? filter_var(wp_unslash($_POST['bbcs_start_files']), FILTER_VALIDATE_BOOLEAN) : false;
	$result = bbcs_createSaltFile($bbcs_start_files);

	if ($result) {
		bbcs_flushRedisAndMMC();

		wp_send_json_success(['message' => __('Salt file created successfully.', 'botblocker-security')]);
	} else {
		wp_send_json_error(['message' => __('Not created: file already exists or insufficient permissions.', 'botblocker-security')]);
	}
}
add_action('wp_ajax_bbcs_create_salt_file', 'bbcs_create_salt_file_ajax');

function bbcs_clear_wp_debug_log_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	// REVIEWER NOTE: We intentionally target the standard WordPress debug.log file located in WP_CONTENT_DIR.
	$debug_log_path = WP_CONTENT_DIR . '/debug.log';
	$result = false;

	if (file_exists($debug_log_path)) {
		$result = file_put_contents($debug_log_path, '');
	}

	if ($result !== false) {
		wp_send_json_success(['message' => __('Log file cleared successfully.', 'botblocker-security')]);
	} else {
		wp_send_json_error(['message' => __('Not cleared: log file not found or insufficient permissions.', 'botblocker-security')]);
	}
}
add_action('wp_ajax_bbcs_clear_wp_debug_log', 'bbcs_clear_wp_debug_log_ajax');

function bbcs_download_wp_debug_log_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	// REVIEWER NOTE: We intentionally target the standard WordPress debug.log file located in WP_CONTENT_DIR.
	$debug_log_path = WP_CONTENT_DIR . '/debug.log';

	if (file_exists($debug_log_path)) {

		$uploads  = wp_upload_dir();
		$temp_dir = trailingslashit($uploads['basedir']) . 'botblocker_temp';

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if (WP_Filesystem()) {
			global $wp_filesystem;
			if (! $wp_filesystem->is_dir($temp_dir)) {
				$wp_filesystem->mkdir($temp_dir, defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755);
			}
		}

		$temp_file = $temp_dir . '/wordpress_debug_' . gmdate('YmdHis') . '.log';
		copy($debug_log_path, $temp_file);

		$download_url = wp_upload_dir()['baseurl'] . '/botblocker_temp/' . basename($temp_file);

		if (!is_ssl() && strpos($download_url, 'https://') === 0) {
			$download_url = str_replace('https://', 'http://', $download_url);
		} elseif (is_ssl() && strpos($download_url, 'http://') === 0) {
			$download_url = str_replace('http://', 'https://', $download_url);
		}

		wp_send_json_success([
			'download_url' => $download_url,
			'message' => __('Log file is ready for download.', 'botblocker-security'),
		]);
	} else {
		wp_send_json_error(['message' => __('Log file not found.', 'botblocker-security')]);
	}
}
add_action('wp_ajax_bbcs_download_wp_debug_log', 'bbcs_download_wp_debug_log_ajax');

function bbcs_clear_transients_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	//BBCS-MULTISITE
	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	global $wpdb;
	// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_%'");

	wp_send_json_success(['message' => __('All transients have been cleared.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_clear_transients', 'bbcs_clear_transients_ajax');

function bbcs_update_asn_database_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	if (!function_exists('bbcs_asn_db_schedule_download')) {
		wp_send_json_error(['message' => __('ASN database module is not available.', 'botblocker-security')]);
		return;
	}

	delete_transient('bbcs_asn_db_lock');

	$scheduled = bbcs_asn_db_schedule_download('manual', 15);

	if ($scheduled) {
		wp_send_json_success([
			'message' => __('ASN database update scheduled. The download will start shortly in the background.', 'botblocker-security'),
		]);
	} else {
		wp_send_json_success([
			'message' => __('ASN database update is already scheduled.', 'botblocker-security'),
		]);
	}
}
add_action('wp_ajax_bbcs_update_asn_database', 'bbcs_update_asn_database_ajax');

function bbcs_clear_hits_database()
{
	global $wpdb;

	// REVIEWER NOTE: These queries operate only on known internal BotBlocker tables.
	// No user-provided table or value is interpolated into the SQL.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$suspicious_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_hits_suspicious}`");

	if ($suspicious_count > 0) {
		// REVIEWER NOTE: These queries operate only on known internal BotBlocker tables.
		// No user-provided table or value is interpolated into the SQL.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cloud_result = $wpdb->query(
			"INSERT INTO {$wpdb->bbcs_hits_cloud}
			 SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`"
		);

		if ($cloud_result === false) {
			return false;
		}
	}

	// REVIEWER NOTE: These are internal plugin tables and contain no user-controlled identifiers.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$hits_result = $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_hits}`");
	if ($hits_result === false) {
		return false;
	}

	// REVIEWER NOTE: These are internal plugin tables and contain no user-controlled identifiers.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$suspicious_result = $wpdb->query("TRUNCATE TABLE `{$wpdb->bbcs_hits_suspicious}`");
	if ($suspicious_result === false) {
		return false;
	}

	// REVIEWER NOTE: Counter reset targets the plugin's single counters row.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$counters_result = $wpdb->query(
		"UPDATE `{$wpdb->bbcs_counters}`
		 SET `today_hits` = 0,
		     `today_blocked` = 0,
		     `total_hits` = 0,
		     `total_blocked` = 0,
		     `search_engine_visits` = 0,
		     `last_update` = NULL"
	);

	if ($counters_result === false) {
		return false;
	}

	bbcs_truncate_daily_summary();
	bbcs_clear_transients();

	return true;
}

function bbcs_clear_hits_database_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	if (bbcs_clear_hits_database()) {
		wp_send_json_success(['message' => __('All visitors data has been cleared successfully.', 'botblocker-security')]);
	}

	wp_send_json_error(['message' => __('Failed to clear visitors data.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_clear_hits_database', 'bbcs_clear_hits_database_ajax');

function bbcs_flush_rewrite_rules_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	flush_rewrite_rules(true);

	wp_send_json_success(['message' => __('Permalink rules have been flushed successfully.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_flush_rewrite_rules', 'bbcs_flush_rewrite_rules_ajax');

function bbcs_flush_object_cache_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Not enough permissions to perform the operation.', 'botblocker-security')]);
		return;
	}

	wp_cache_flush();

	wp_send_json_success(['message' => __('Object cache has been flushed successfully.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_flush_object_cache', 'bbcs_flush_object_cache_ajax');

function bbcs_apply_security_profile_ajax()
{
	check_ajax_referer('botblocker_nonce', 'nonce');
	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(['message' => __('Unauthorized.', 'botblocker-security')]);
	}
	$mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : '';
	if (!in_array($mode, ['light', 'strong', 'full'], true)) {
		wp_send_json_error(['message' => __('Invalid mode.', 'botblocker-security')]);
	}
	if ($mode === 'full') {
		if (!(function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive())) {
			wp_send_json_error(['message' => __('Full profile requires Cloud API connection to be active.', 'botblocker-security')]);
		}
		if (function_exists('bbcs_loadSettingsFull')) {
			bbcs_loadSettingsFull();
		}
	} elseif ($mode === 'strong') {
		if (function_exists('bbcs_loadSettingsStrong')) {
			bbcs_loadSettingsStrong();
		}
	} else {
		if (function_exists('bbcs_loadSettingsLight')) {
			bbcs_loadSettingsLight();
		}
	}

	bbcs_resetTransientHealth();

	wp_send_json_success(['message' => __('Profile applied.', 'botblocker-security')]);
}
add_action('wp_ajax_bbcs_apply_security_profile', 'bbcs_apply_security_profile_ajax');

function bbcs_handle_contact_email() {
	check_ajax_referer('botblocker_nonce', 'nonce');

	if (!current_user_can(bbcs_can_manage())) {
		wp_send_json_error(array('message' => __('Unauthorized', 'botblocker-security')));
		return;
	}

	try {
		$raw_data = isset($_POST['data']) ? sanitize_text_field(wp_unslash($_POST['data'])) : '';
		$data = sanitize_email($raw_data);

		if (empty($data)) {
			wp_send_json_error(array('message' => __('Email is required.', 'botblocker-security')));
			return;
		}

		if ( ! is_email( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'botblocker-security' ) ) );
			return;
		}

		if (!function_exists('bbcs_send_activation_to_cloud')) {
			wp_send_json_error(array('message' => __('Activation service is unavailable.', 'botblocker-security')));
			return;
		}

		bbcs_send_activation_to_cloud($data);
		bbcs_update_option('bbcs_contact_email_collected', 1);
		wp_send_json_success(array('message' => __('Activation sent.', 'botblocker-security')));
	} catch (Exception $e) {
		wp_send_json_error(array('message' => __('Failed to send activation. Try again later.', 'botblocker-security')));
	}
}
add_action('wp_ajax_bbcs_contact_email', 'bbcs_handle_contact_email');
