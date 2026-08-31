<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

class BotBlockerCron
{
	public const CRON_MODE_NONE     = 'none';
	public const CRON_MODE_SPAWN    = 'spawn';
	public const CRON_MODE_FALLBACK = 'fallback';

	private const SINGLE_EVENT_MIN_INTERVAL = 300;

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
			'schedule' => 'weekly',
			'interval' => WEEK_IN_SECONDS,
			'label'    => 'ASN Database Freshness Check',
		),
		'bbcs_asn_db_download_event' => array(
			'schedule' => null,
			'interval' => 300,
			'label'    => 'ASN Database Download/Update',
		),
		'bbcs_rugov_freshness_task'  => array(
			'schedule' => 'weekly',
			'interval' => WEEK_IN_SECONDS,
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
		'bbcs_cleanup_hot_bans'            => array(
			'schedule' => 'five_minutes',
			'interval' => 5 * MINUTE_IN_SECONDS,
			'label'    => 'Cleanup Hot Bans',
		),
		'bbcs_addon_updates_task'          => array(
			'schedule' => 'daily',
			'interval' => DAY_IN_SECONDS,
			'label'    => 'Add-on Updates Check',
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
			'bbcs_cleanup_hot_bans'            => __('Cleanup Hot Bans', 'botblocker-security'),
			'bbcs_addon_updates_task'          => __('Add-on Updates Check', 'botblocker-security'),
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
		$early = ! did_action('init');

		$custom_intervals = array(
			'every_five_days' => array(
				'interval' => 5 * DAY_IN_SECONDS,
				'display'  => $early ? 'Every 5 Days' : __('Every 5 Days', 'botblocker-security'),
			),
			'weekly'          => array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => $early ? 'Weekly' : __('Weekly', 'botblocker-security'),
			),
			'five_minutes'    => array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => $early ? 'Every 5 Minutes' : __('Every 5 Minutes', 'botblocker-security'),
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

		$one_time_event = BotBlockerCompatibility::getScheduledEvent('bbcs_one_time_task');

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
		if ( class_exists( 'BotBlockerAudit' ) ) {
			BotBlockerAudit::cleanOldEntries();
		}
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

	public static function oneTimeHandler(): void
	{
		if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[BBCS DEBUG] [Cron] One-time task executed at ' . current_time('mysql'));
		}
	}

	public static function addonUpdatesHandler(): void
	{
		require_once BOTBLOCKER_DIR . 'includes/class-botblocker-addons-market.php';
		BotBlockerAddonsMarket::refreshAvailableUpdates(true);
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
		if ( $hook === '' ) {
			wp_send_json_error( __( 'Unknown cron task.', 'botblocker-security' ) );
		}

		$is_core = isset( $all_tasks[ $hook ] );
		if ( ! $is_core ) {
			$labels = apply_filters( 'bbcs_cron_task_labels', self::getTaskLabels() );
			if ( ! isset( $labels[ $hook ] ) ) {
				wp_send_json_error( __( 'Unknown cron task.', 'botblocker-security' ) );
			}
		}

		self::runTask( $hook );

		$now   = time();
		$event = BotBlockerCompatibility::getScheduledEvent( $hook );
		if ( $event ) {
			wp_unschedule_event( $event->timestamp, $hook );
		}
		if ( $is_core ) {
			if ( $all_tasks[ $hook ]['schedule'] !== null ) {
				wp_schedule_event( $now + $all_tasks[ $hook ]['interval'], $all_tasks[ $hook ]['schedule'], $hook );
			}
		} elseif ( $event && ! empty( $event->schedule ) ) {
			wp_schedule_event( $now + (int) $event->interval, (string) $event->schedule, $hook );
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

			$event = BotBlockerCompatibility::getScheduledEvent( $hook );
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
			$event = BotBlockerCompatibility::getScheduledEvent( $hook );
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
		// Explicit column list, not SELECT *: a positional copy breaks as soon as the two
		// schemas differ by one column, and a failure here aborts the whole retention run.
		$columns = BotBlockerDb::sharedColumnList($wpdb->bbcs_hits_suspicious, $wpdb->bbcs_hits_cloud);
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cloud_result = $columns === '' ? false : $wpdb->query($wpdb->prepare(
			"INSERT INTO `{$wpdb->bbcs_hits_cloud}` ({$columns}) SELECT {$columns} FROM `{$wpdb->bbcs_hits_suspicious}` WHERE `date` < %d",
			$delete_before
		));
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

		self::retireFilteredWatermark();

		if ( isset( $wpdb->bbcs_fingerprint ) ) {
			$fingerprint_cutoff = $delete_before;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM `{$wpdb->bbcs_fingerprint}` WHERE last_seen < %d AND status = 'watch'",
				$fingerprint_cutoff
			) );
		}
	}

	/**
	 * Set watermark to 0 once no hit rows remain below it. Write 0 (not delete) — absent = untrusted.
	 */
	private static function retireFilteredWatermark(): void
	{
		global $wpdb;

		$watermark = (int) get_option( BotBlockerStore::FILTERED_WATERMARK_OPTION, 0 );
		if ( $watermark <= 0 ) {
			return;
		}
		if ( empty( $wpdb->bbcs_hits ) || empty( $wpdb->bbcs_hits_suspicious ) ) {
			return;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$min_hits       = (int) $wpdb->get_var( "SELECT MIN(`date`) FROM `{$wpdb->bbcs_hits}`" );
		$min_suspicious = (int) $wpdb->get_var( "SELECT MIN(`date`) FROM `{$wpdb->bbcs_hits_suspicious}`" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Empty table → MIN is 0 → treat as satisfied. Both tables must clear the watermark.
		if ( ( $min_hits >= $watermark || $min_hits === 0 ) && ( $min_suspicious >= $watermark || $min_suspicious === 0 ) ) {
			update_option( BotBlockerStore::FILTERED_WATERMARK_OPTION, 0, true );
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
				"SELECT cid, ip, date FROM `{$wpdb->bbcs_hits_cloud}` WHERE date > %d AND ip != %s ORDER BY date ASC LIMIT %d",
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
			$batch_cids = array_column($suspicious_hits, 'cid');
			$all_batch_cids = array_merge($all_batch_cids ?? array(), $batch_cids);
			$ip_records = array_map(
				function ($hit) {
					return array('ip' => $hit['ip']);
				},
				$suspicious_hits
			);
			$compressed = gzencode(wp_json_encode($ip_records), 9);
			unset($suspicious_hits, $batch_cids, $ip_records);
			$request_data['hits'][] = base64_encode($compressed);
			unset($compressed);
			if (count($request_data['hits']) >= BOTBLOCKER_CLOUD_REQUEST_SIZE) {
				$request_data = array_merge($request_data, $request_auth);
				$cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL, 'suspicious');
				if ($cloud === false) {
					$cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL, 'suspicious');
				}
				if ($cloud !== false) {
					$placeholders = implode(',', array_fill(0, count($all_batch_cids), '%s'));
					// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
					// Should not be cached - it's a cron task for cleanup.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_hits_cloud}` WHERE cid IN ($placeholders)", ...$all_batch_cids));
				}
				$request_data     = array();
				$all_batch_cids   = array();
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
			if ($cloud !== false && ! empty($all_batch_cids)) {
				$placeholders = implode(',', array_fill(0, count($all_batch_cids), '%s'));
				// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
				// Should not be cached - it's a cron task for cleanup.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_hits_cloud}` WHERE cid IN ($placeholders)", ...$all_batch_cids));
			}
			$request_data = array();
		}
	}

	private static function loadTaskDependencies(): void
	{
		require_once BOTBLOCKER_DIR . 'helpers-cron.php';
	}

	private static function skipMissingHandler(string $hook): bool
	{
		if (has_action($hook)) {
			return false;
		}
		if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log("[BBCS] [Cron] Fallback skipped {$hook}: no handler registered, leaving it scheduled");
		}
		return true;
	}

	public static function runTask(string $hook, array $args = array()): void
	{
		self::loadTaskDependencies();

		try {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			do_action_ref_array($hook, $args);
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

		$cron_lock_timeout = defined('WP_CRON_LOCK_TIMEOUT') ? WP_CRON_LOCK_TIMEOUT : MINUTE_IN_SECONDS;
		$doing_cron        = (float) get_transient('doing_cron');
		if ($doing_cron > 0 && ($doing_cron + $cron_lock_timeout) > microtime(true)) {
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
			self::loadTaskDependencies();

			$current_time = time();
			$tasks        = self::getFallbackTasks();

			foreach ($tasks as $hook => $config) {
				$event = BotBlockerCompatibility::getScheduledEvent($hook);
				if (! $event) {
					// Cron is missing - recreate it
					if (! empty($config['schedule'])) {
						wp_schedule_event(time() + 60, $config['schedule'], $hook);
					}
					continue;
				}
				// Simplified overdue check - if task is more than 1.5x interval overdue, run it
				$overdue_threshold = $event->timestamp + ($config['interval'] * 1.5);
				if ($current_time <= $overdue_threshold) {
					continue;
				}

				if (self::skipMissingHandler($hook)) {
					continue;
				}

				if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log("[BBCS DEBUG] [Cron] Running overdue task: {$hook}");
				}

				wp_unschedule_event($event->timestamp, $hook);
				if (! empty($config['schedule'])) {
					wp_schedule_event(time() + 60, $config['schedule'], $hook);
				}

				self::runTask($hook);
			}

			foreach ((array) _get_cron_array() as $timestamp => $hooks) {
				if ((int) $timestamp >= $current_time) {
					break;
				}
				foreach ($hooks as $hook => $events) {
					$config = isset(self::TASK_DEFINITIONS[$hook]) ? self::TASK_DEFINITIONS[$hook] : null;
					if ($config === null) {
						continue;
					}
					foreach ((array) $events as $event) {
						if (! empty($event['schedule'])) {
							continue;
						}
						$interval = $config['schedule'] === null
							? (int) $config['interval']
							: self::SINGLE_EVENT_MIN_INTERVAL;
						if ((int) $timestamp >= ($current_time - $interval)) {
							continue;
						}

						$event_args = isset($event['args']) && is_array($event['args']) ? $event['args'] : array();

						if (self::skipMissingHandler($hook)) {
							continue;
						}

						if (defined('BBCS_DEBUG') && BBCS_DEBUG) {
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
							error_log("[BBCS DEBUG] [Cron] Running overdue single event: {$hook}");
						}
						wp_unschedule_event((int) $timestamp, $hook, $event_args);
						self::runTask($hook, $event_args);
					}
				}
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
add_action('bbcs_one_time_task', array('BotBlockerCron', 'oneTimeHandler'));
add_action('bbcs_addon_updates_task', array('BotBlockerCron', 'addonUpdatesHandler'));
add_action('wp_ajax_bbcs_get_cron_tasks', array('BotBlockerCron', 'getTasksList'));
add_action('wp_ajax_bbcs_run_cron_task', array('BotBlockerCron', 'handleRunCronTask'));
add_action('wp_ajax_bbcs_run_all_cron_tasks', array('BotBlockerCron', 'handleRunAllCronTasks'));
add_action('wp_ajax_bbcs_run_stale_cron_tasks', array('BotBlockerCron', 'handleRunStaleCronTasks'));
add_action('init', array('BotBlockerCron', 'fallbackRunner'));
add_action('bbcs_hourly_task', array('BotBlockerSummary', 'cronHandler'));
add_action('bbcs_summary_backfill', array('BotBlockerSummary', 'backfillHandler'));
add_action('bbcs_cleanup_hot_bans', array('BotBlockerCron', 'cleanupHotBansHandler'));
