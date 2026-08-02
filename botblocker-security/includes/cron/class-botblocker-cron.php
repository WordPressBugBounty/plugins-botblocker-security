<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

class BotBlockerCron
{
	private const TASK_DEFINITIONS = array(
		'bbcs_daily_task'            => array(
			'schedule' => 'daily',
			'interval' => DAY_IN_SECONDS,
			'label'    => 'Clear History',
		),
		'bbcs_hourly_task'           => array(
			'schedule' => 'hourly',
			'interval' => HOUR_IN_SECONDS,
			'label'    => 'Update Statistics',
		),
		'bbcs_weekly_task'           => array(
			'schedule' => 'weekly',
			'interval' => WEEK_IN_SECONDS,
			'label'    => 'Weekly Maintenance',
		),
		'bbcs_one_time_task'         => array(
			'schedule' => null,
			'interval' => 600,
			'label'    => 'Sync Early Init Data',
		),
		'bbcs_asn_db_freshness_task' => array(
			'schedule' => 'every_five_days',
			'interval' => 5 * DAY_IN_SECONDS,
			'label'    => 'ASN Database Freshness Check',
		),
		'bbcs_asn_db_download_event' => array(
			'schedule' => null,
			'interval' => 300,
			'label'    => 'ASN Database Download/Update',
		),
		'bbcs_rugov_freshness_task'  => array(
			'schedule' => 'daily',
			'interval' => DAY_IN_SECONDS,
			'label'    => 'RUGOV Freshness Check',
		),
		'bbcs_rugov_update_event'    => array(
			'schedule' => null,
			'interval' => 300,
			'label'    => 'RUGOV Update',
		),
		'bbcs_llm_sync_event'        => array(
			'schedule' => 'weekly',
			'interval' => WEEK_IN_SECONDS,
			'label'    => 'LLM Provider Sync',
		),
		'bbcs_tls_fingerprints_sync_event' => array(
			'schedule' => 'weekly',
			'interval' => WEEK_IN_SECONDS,
			'label'    => 'TLS Fingerprint Sync',
		),
		'bbcs_summary_backfill'		=> array(
			'schedule' => null,
			'interval' => 120,
			'label'    => 'Daily Summary Backfill',
		),
		'bbcs_telegram_weekly_report_task' => array(
			'schedule' => 'weekly',
			'interval' => WEEK_IN_SECONDS,
			'label'    => 'Telegram Weekly Report',
		),
		'bbcs_cleanup_hot_bans'            => array(
			'schedule' => 'five_minutes',
			'interval' => 5 * MINUTE_IN_SECONDS,
			'label'    => 'Cleanup Hot Bans',
		),
	);

	public static function getAllTasks(): array
	{
		return self::TASK_DEFINITIONS;
	}

	public static function getTaskLabels(): array
	{
		return array(
			'bbcs_daily_task'            => __('Clear History', 'botblocker-security'),
			'bbcs_hourly_task'           => __('Update Statistics', 'botblocker-security'),
			'bbcs_weekly_task'           => __('Weekly Maintenance', 'botblocker-security'),
			'bbcs_one_time_task'         => __('Sync Early Init Data', 'botblocker-security'),
			'bbcs_asn_db_download_event' => __('ASN Database Download/Update', 'botblocker-security'),
			'bbcs_asn_db_freshness_task' => __('ASN Database Freshness Check', 'botblocker-security'),
			'bbcs_rugov_freshness_task'  => __('RUGOV Freshness Check', 'botblocker-security'),
			'bbcs_rugov_update_event'    => __('RUGOV Update', 'botblocker-security'),
			'bbcs_llm_sync_event'        => __('LLM Provider Sync', 'botblocker-security'),
			'bbcs_tls_fingerprints_sync_event' => __('TLS Fingerprint Sync', 'botblocker-security'),
			'bbcs_summary_backfill'		 => __('Daily Summary Backfill', 'botblocker-security'),
			'bbcs_telegram_weekly_report_task' => __('Telegram Weekly Report', 'botblocker-security'),
			'bbcs_cleanup_hot_bans'            => __('Cleanup Hot Bans', 'botblocker-security'),
		);
	}

	public static function getFallbackTasks(): array
	{
		$fallback = array();
		foreach (self::TASK_DEFINITIONS as $hook => $definition) {
			if ($definition['schedule'] !== null) {
				$fallback[$hook] = $definition;
			}
		}
		return $fallback;
	}

	public static function registerIntervals(array $schedules): array
	{
		$custom_intervals = array(
			'every_five_days' => array(
				'interval' => 5 * DAY_IN_SECONDS,
				'display'  => __('Every 5 Days', 'botblocker-security'),
			),
			'weekly'          => array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __('Weekly', 'botblocker-security'),
			),
			'five_minutes'    => array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __('Every 5 Minutes', 'botblocker-security'),
			),
		);
		return array_merge($schedules, $custom_intervals);
	}

	public static function registerTasks(): void
	{
		foreach (self::TASK_DEFINITIONS as $hook => $definition) {
			if (wp_next_scheduled($hook)) {
				continue;
			}
			if ($definition['schedule'] !== null) {
				wp_schedule_event(time() + 60, $definition['schedule'], $hook);
			} elseif ($hook === 'bbcs_one_time_task') {
				wp_schedule_single_event(time() + $definition['interval'], $hook);
			}
		}
	}

	public static function removeTasks(): void
	{
		foreach (array_keys(self::TASK_DEFINITIONS) as $hook) {
			wp_unschedule_hook($hook);
		}
	}

	public static function getTasksList(): void
	{
		check_ajax_referer('botblocker_nonce', 'nonce');

		if (! current_user_can(BotBlockerMultisite::canManage())) {
			wp_send_json_error(__('No permission.', 'botblocker-security'));
		}

		$one_time_event = bbcs_get_scheduled_event('bbcs_one_time_task');

		$cron_jobs = _get_cron_array();
		if (! is_array($cron_jobs)) {
			$cron_jobs = array();
		}
		$current_time = time();
		$plugin_tasks = self::getTaskLabels();
		$plugin_tasks = apply_filters('bbcs_cron_task_labels', $plugin_tasks);

		$tasks = array();

		$asn_download_hook = 'bbcs_asn_db_download_event';
		$asn_lock_active   = (bool) get_transient('bbcs_asn_db_lock');
		$asn_window        = 5 * MINUTE_IN_SECONDS;
		$asn_listed        = false;

		foreach ($plugin_tasks as $hook => $description) {
			foreach ($cron_jobs as $timestamp => $hooks) {
				if (isset($hooks[$hook])) {
					$task     = reset($hooks[$hook]);
					$interval = isset($task['interval']) ? $task['interval'] : 0;

					$time_remaining = $timestamp > $current_time
						? $timestamp - $current_time
						: 0;

					if (is_object($one_time_event) && $hook === 'bbcs_one_time_task') {
						$time_left = $one_time_event->timestamp - $current_time;
						$progress  = $time_left > 0
							? min(100, max(0, ((600 - $time_left) / 600) * 100))
							: 0;
					} elseif ($hook === $asn_download_hook) {
						$delta      = max(0, $timestamp - $current_time);
						$progress   = min(100, max(0, (($asn_window - $delta) / $asn_window) * 100));
						$asn_listed = true;
					} else {
						$progress = $interval > 0
							? min(100, max(0, (($current_time - ($timestamp - $interval)) / $interval) * 100))
							: 0;
					}

					$tasks[] = array(
						'description'    => $description,
						'time_remaining' => $time_remaining,
						'progress'       => round($progress, 2),
					);
				}
			}
		}

		if (! $asn_listed && $asn_lock_active) {
			$tasks[] = array(
				'description'    => __('ASN Database Download/Update', 'botblocker-security'),
				'time_remaining' => 0,
				'progress'       => 50,
			);
		}

		$tasks = apply_filters('bbcs_cron_tasks', $tasks, $cron_jobs, $current_time);

		wp_send_json_success($tasks);
	}

	public static function dailyHandler(): void
	{
		self::cleanOldHits();
		BotBlockerIp::clearExpiredPtrCache();
		BotBlockerCloudApiHooks::refreshApiInternal();
		self::updateIpFiles();
	}

	public static function hourlyHandler(): void
	{
		// REVIEWER NOTE: Conditional debug logging; gated behind BBCS_DEBUG and disabled in production.
		if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[BBCS DEBUG] [Cron] Hourly task executed at ' . current_time('mysql'));
		}
	}

	public static function weeklyHandler(): void
	{
		self::sendSuspiciousHits();
	}

	public static function telegramWeeklyReportHandler(): void
	{
		BotBlockerTelegram::sendWeeklyReport();
	}

	public static function oneTimeHandler(): void
	{
		if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[BBCS DEBUG] [Cron] One-time task executed at ' . current_time('mysql'));
		}
	}

	public static function updateIpFiles(): void
	{
		global $wpdb;
		$current_time = time();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_ipv4rules}` WHERE `expires` < %d", $current_time));
		$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_ipv6rules}` WHERE `expires` < %d", $current_time));
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		BotBlockerFileRenderer::renderIps();
		BotBlockerFileRenderer::renderHotBans();
	}

	public static function cleanupHotBansHandler(): void
	{
		BotBlockerFileRenderer::cleanupHotBans();
	}

	public static function handleRunCronTask(): void
	{
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$hook = isset( $_POST['hook'] ) ? sanitize_text_field( wp_unslash( $_POST['hook'] ) ) : '';

		$all_tasks = self::getAllTasks();
		if ( $hook === '' || ! isset( $all_tasks[ $hook ] ) ) {
			wp_send_json_error( __( 'Unknown cron task.', 'botblocker-security' ) );
		}

		self::runTask( $hook );

		$def   = $all_tasks[ $hook ];
		$now   = time();
		$event = bbcs_get_scheduled_event( $hook );
		if ( $event ) {
			wp_unschedule_event( $event->timestamp, $hook );
		}
		if ( $def['schedule'] !== null ) {
			wp_schedule_event( $now + $def['interval'], $def['schedule'], $hook );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				// translators: %s: cron hook name
				__( 'Task %s executed successfully.', 'botblocker-security' ),
				$hook
			),
		) );
	}

	public static function handleRunAllCronTasks(): void
	{
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$tasks = self::getFallbackTasks();
		$ran   = array();
		$now   = time();

		foreach ( $tasks as $hook => $config ) {
			self::runTask( $hook );

			$event = bbcs_get_scheduled_event( $hook );
			if ( $event ) {
				wp_unschedule_event( $event->timestamp, $hook );
			}
			if ( ! empty( $config['schedule'] ) ) {
				wp_schedule_event( $now + $config['interval'], $config['schedule'], $hook );
			}

			$ran[] = $hook;
		}

		wp_send_json_success( array(
			'ran'     => count( $ran ),
			'tasks'   => $ran,
			'message' => sprintf(
				// translators: %d: number of tasks executed
				_n( '%d task executed.', '%d tasks executed.', count( $ran ), 'botblocker-security' ),
				count( $ran )
			),
		) );
	}

	public static function handleRunStaleCronTasks(): void
	{
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'No permission.', 'botblocker-security' ) );
		}

		$current_time = time();
		$tasks        = self::getFallbackTasks();
		$ran          = array();

		foreach ( $tasks as $hook => $config ) {
			$event = bbcs_get_scheduled_event( $hook );
			if ( ! $event ) {
				continue;
			}

			$overdue_threshold = $event->timestamp + ( $config['interval'] * 1.5 );
			if ( $current_time > $overdue_threshold ) {
				self::runTask( $hook );
				$ran[] = $hook;

				wp_unschedule_event( $event->timestamp, $hook );
				if ( ! empty( $config['schedule'] ) ) {
					wp_schedule_event( $current_time + $config['interval'], $config['schedule'], $hook );
				}
			}
		}

		wp_send_json_success( array(
			'ran'     => count( $ran ),
			'tasks'   => $ran,
			'message' => count( $ran ) > 0
				? sprintf(
					// translators: %d: number of stale tasks executed
					_n( '%d stale task executed.', '%d stale tasks executed.', count( $ran ), 'botblocker-security' ),
					count( $ran )
				)
				: __( 'No stale tasks found.', 'botblocker-security' ),
		) );
	}

	public static function cleanOldHits(): void
	{
		global $wpdb;
		$BBCS = BotBlocker::getInstance();
		$store_period = isset($BBCS->settings->admin_store_period) ? (int) $BBCS->settings->admin_store_period : 30;
		$store_period = max(1, min($store_period, 30));
		$delete_before = time() - ($store_period * DAY_IN_SECONDS);
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// Should not be cached - it's a cron task for cleanup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cloud_result = $wpdb->query($wpdb->prepare(
			"INSERT INTO {$wpdb->bbcs_hits_cloud} SELECT * FROM `{$wpdb->bbcs_hits_suspicious}` WHERE `date` < %d",
			$delete_before
		));
		if ($cloud_result === false) {
			if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log('[BBCS DEBUG] [Cron] FAILED to copy suspicious hits to cloud table. Data preserved. Error: ' . $wpdb->last_error);
			}
			return; // preserve suspicious data, retry on next cron run
		}
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// Should not be cached - it's a cron task for cleanup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$hits_result = $wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_hits}` WHERE `date` < %d", $delete_before));
		if ($hits_result === false && defined('BBCS_DEBUG') && BBCS_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[BBCS DEBUG] [Cron] FAILED to delete old hits. Error: ' . $wpdb->last_error);
		}
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		// Should not be cached - it's a cron task for cleanup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$suspicious_result = $wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_hits_suspicious}` WHERE `date` < %d", $delete_before));
		if ($suspicious_result === false && defined('BBCS_DEBUG') && BBCS_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[BBCS DEBUG] [Cron] FAILED to delete old suspicious hits. Error: ' . $wpdb->last_error);
		}
		BotBlockerSummary::cleanOldData($store_period);

		if ( isset( $wpdb->bbcs_fingerprint ) ) {
			$fingerprint_cutoff = $delete_before;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM `{$wpdb->bbcs_fingerprint}` WHERE last_seen < %d AND status = 'watch'",
				$fingerprint_cutoff
			) );
		}
	}

	public static function sendSuspiciousHits(): void
	{
		if (! function_exists('gzencode')) {
			if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log('[BBCS DEBUG] [Cron] Compression not available');
			}
			return;
		}
		global $wpdb;
		$BBCS = BotBlocker::getInstance();
		$last_date        = 0;
		$request_auth     = BotBlockerPro::buildAuthPayload($BBCS->settings);
		$request_data     = array();
		$batch_start_date = null;
		$batch_end_date   = null;
		while (true) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
			// Should not be cached - it's a cron task for cleanup.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$suspicious_hits = $wpdb->get_results($wpdb->prepare(
				"SELECT ip, date FROM `{$wpdb->bbcs_hits_cloud}` WHERE date > %d AND ip != %s ORDER BY date ASC LIMIT %d",
				$last_date,
				BOTBLOCKER_EMPTY,
				BOTBLOCKER_CLOUD_RECORDS_BATCH
			), ARRAY_A);
			if (empty($suspicious_hits)) {
				break;
			}
			if ($batch_start_date === null) {
				$batch_start_date = $suspicious_hits[0]['date'];
			}
			$last_date = $batch_end_date = end($suspicious_hits)['date'];
			$ip_records = array_map(
				function ($hit) {
					return array('ip' => $hit['ip']);
				},
				$suspicious_hits
			);
			$compressed = gzencode(wp_json_encode($ip_records), 9);
			unset($suspicious_hits, $ip_records);
			$request_data['hits'][] = base64_encode($compressed);
			unset($compressed);
			if (count($request_data['hits']) >= BOTBLOCKER_CLOUD_REQUEST_SIZE) {
				$request_data = array_merge($request_data, $request_auth);
				$cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL, 'suspicious');
				if ($cloud === false) {
					$cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL, 'suspicious');
				}
				if ($cloud !== false) {
					// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
					// Should not be cached - it's a cron task for cleanup.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_hits_cloud}` WHERE date >= %d AND date <= %d", $batch_start_date, $batch_end_date));
				}
				$request_data     = array();
				$batch_start_date = null;
				$batch_end_date   = null;
			}
		}
		if (! empty($request_data)) {
			$request_data = array_merge($request_data, $request_auth);
			$cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL, 'suspicious');
			if ($cloud === false) {
				$cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL, 'suspicious');
			}
			if ($cloud !== false && $batch_start_date !== null && $batch_end_date !== null) {
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
				// Should not be cached - it's a cron task for cleanup.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_hits_cloud}` WHERE date >= %d AND date <= %d", $batch_start_date, $batch_end_date));
			}
			$request_data = array();
		}
	}

	private static function loadTaskDependencies(): void
	{
		require_once BOTBLOCKER_DIR . 'helpers-cron.php';
	}

	public static function runTask(string $hook): void
	{
		self::loadTaskDependencies();

		try {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			do_action($hook);
		} catch (\Throwable $e) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(sprintf('[BBCS] [Cron] Task %s failed: %s in %s:%d', $hook, $e->getMessage(), $e->getFile(), $e->getLine()));
		}
	}

	public static function fallbackRunner(): void
	{
		if (wp_doing_cron()) {
			return;
		}

		$last_check = get_transient('bbcs_cron_fallback_last_check');
		if ($last_check !== false && (time() - (int) $last_check) < 15 * MINUTE_IN_SECONDS) {
			return;
		}

		global $wpdb;
		$lock_key = 'bbcs_cron_fallback_lock';

		if (false === add_option($lock_key, time(), '', 'no')) {
			$lock_time = (int) get_option($lock_key);
			if ($lock_time > 0 && (time() - $lock_time) < 5 * MINUTE_IN_SECONDS) {
				return;
			}
			// CAS takeover: update only if option_value still equals the stale timestamp.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query($wpdb->prepare(
				"UPDATE `{$wpdb->options}` SET option_value = %s WHERE option_name = %s AND option_value = %d",
				time(),
				$lock_key,
				$lock_time
			));
			if (0 === $wpdb->rows_affected) {
				return;
			}
		}

		try {
			$current_time = time();
			$tasks        = self::getFallbackTasks();
			foreach ($tasks as $hook => $config) {
				$event = bbcs_get_scheduled_event($hook);
				if (! $event) {
					// Cron is missing - recreate it
					if (! empty($config['schedule'])) {
						wp_schedule_event(time() + 60, $config['schedule'], $hook);
					}
					continue;
				}
				// Simplified overdue check - if task is more than 1.5x interval overdue, run it
				$overdue_threshold = $event->timestamp + ($config['interval'] * 1.5);
				if ($current_time > $overdue_threshold) {
					if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log("[BBCS DEBUG] [Cron] Running overdue task: {$hook}");
					}
					// runTask() never throws, so a failing handler still gets
					// rescheduled below instead of staying permanently overdue.
					self::runTask($hook);

					wp_unschedule_event($event->timestamp, $hook);
					if (! empty($config['schedule'])) {
						wp_schedule_event(time() + 60, $config['schedule'], $hook);
					}
				}
			}

			// One-time task fallback
			$one_time = bbcs_get_scheduled_event('bbcs_one_time_task');
			if ($one_time && $one_time->timestamp < ($current_time - 600)) {
				if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log('[BBCS DEBUG] [Cron] Running overdue one-time task');
				}
				self::runTask('bbcs_one_time_task');
				wp_unschedule_event($one_time->timestamp, 'bbcs_one_time_task');
			}
		} finally {
			delete_option($lock_key);
			set_transient('bbcs_cron_fallback_last_check', time(), 15 * MINUTE_IN_SECONDS);
		}
	}
}

add_filter('cron_schedules', array('BotBlockerCron', 'registerIntervals'));
add_action('bbcs_daily_task', array('BotBlockerCron', 'dailyHandler'));
add_action('bbcs_hourly_task', array('BotBlockerCron', 'hourlyHandler'));
add_action('bbcs_weekly_task', array('BotBlockerCron', 'weeklyHandler'));
add_action('bbcs_telegram_weekly_report_task', array('BotBlockerCron', 'telegramWeeklyReportHandler'));
add_action('bbcs_one_time_task', array('BotBlockerCron', 'oneTimeHandler'));
add_action('wp_ajax_bbcs_get_cron_tasks', array('BotBlockerCron', 'getTasksList'));
add_action('wp_ajax_bbcs_run_cron_task', array('BotBlockerCron', 'handleRunCronTask'));
add_action('wp_ajax_bbcs_run_all_cron_tasks', array('BotBlockerCron', 'handleRunAllCronTasks'));
add_action('wp_ajax_bbcs_run_stale_cron_tasks', array('BotBlockerCron', 'handleRunStaleCronTasks'));
add_action('init', array('BotBlockerCron', 'fallbackRunner'));
add_action('bbcs_hourly_task', array('BotBlockerSummary', 'cronHandler'));
add_action('bbcs_summary_backfill', array('BotBlockerSummary', 'backfillHandler'));
add_action('bbcs_cleanup_hot_bans', array('BotBlockerCron', 'cleanupHotBansHandler'));
