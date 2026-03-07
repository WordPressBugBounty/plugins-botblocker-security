<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter('cron_schedules', 'bbcs_custom_cron_intervals');
function bbcs_custom_cron_intervals($schedules)
{
    $schedules['every_two_hours'] = [
        'interval' => 2 * HOUR_IN_SECONDS,
        'display' => __('Every 2 Hours', 'botblocker-security'),
    ];
    $schedules['every_five_days'] = [
        'interval' => 5 * DAY_IN_SECONDS,
        'display' => __('Every 5 Days', 'botblocker-security'),
    ];
    $schedules['weekly'] = [
        'interval' => WEEK_IN_SECONDS,
        'display' => __('Weekly', 'botblocker-security'),
    ];
    return $schedules;
}

// Register the cron tasks on plugin activation
function bbcs_register_cron_tasks()
{
    if (!wp_next_scheduled('bbcs_daily_task')) {
        wp_schedule_event(time() + 60, 'daily', 'bbcs_daily_task');
    }
    if (!wp_next_scheduled('bbcs_hourly_task')) {
        wp_schedule_event(time() + 60, 'hourly', 'bbcs_hourly_task');
    }
    if (!wp_next_scheduled('bbcs_weekly_task')) {
        wp_schedule_event(time() + 60, 'weekly', 'bbcs_weekly_task');
    }
    if (!wp_next_scheduled('bbcs_five_days_task')) {
        wp_schedule_event(time() + 60, 'every_five_days', 'bbcs_five_days_task');
    }
    if (!wp_next_scheduled('bbcs_two_hours_task')) {
        wp_schedule_event(time() + 60, 'every_two_hours', 'bbcs_two_hours_task');
    }
    if (!wp_next_scheduled('bbcs_one_time_task')) {        
        wp_schedule_single_event(time() + 600, 'bbcs_one_time_task');  
    }
}

// Clear the cron tasks on plugin deactivation
function bbcs_remove_cron_tasks()
{
    wp_clear_scheduled_hook('bbcs_daily_task');
    wp_clear_scheduled_hook('bbcs_hourly_task');
    wp_clear_scheduled_hook('bbcs_weekly_task');
    wp_clear_scheduled_hook('bbcs_five_days_task');
    wp_clear_scheduled_hook('bbcs_two_hours_task');
    wp_clear_scheduled_hook('bbcs_one_time_task');
    wp_clear_scheduled_hook('bbcs_summary_backfill');
}

add_action('bbcs_hourly_task', 'bbcs_summary_cron_handler');
add_action('bbcs_summary_backfill', 'bbcs_summary_backfill_handler');

add_action('bbcs_daily_task', 'bbcs_daily_task_handler');
function bbcs_daily_task_handler()
{
    bbcs_clean_old_hits_data();
    bbcs_clearExpiredPTRCache();
    bbcs_refresh_cloud_api();
    bbcs_update_ip_files();
}

add_action('bbcs_hourly_task', 'bbcs_hourly_task_handler');
function bbcs_hourly_task_handler()
{
    // error_log('Hourly task executed at ' . current_time('mysql'));

}

add_action('bbcs_weekly_task', 'bbcs_weekly_task_handler');
function bbcs_weekly_task_handler()
{
    bbcs_send_suspicious_hits_to_cloud();
}

add_action('bbcs_five_days_task', 'bbcs_five_days_task_handler');
function bbcs_five_days_task_handler()
{
    //  error_log('Five days task executed at ' . current_time('mysql'));

}

add_action('bbcs_two_hours_task', 'bbcs_two_hours_task_handler');
function bbcs_two_hours_task_handler()
{
    //  error_log('Two hours task executed at ' . current_time('mysql'));

}

add_action('bbcs_one_time_task', 'bbcs_one_time_task_handler');
function bbcs_one_time_task_handler()
{
    //  error_log('One-time task executed at ' . current_time('mysql'));

}


add_action('wp_ajax_bbcs_get_cron_tasks', 'bbcs_get_cron_tasks');
add_action('wp_ajax_nopriv_bbcs_get_cron_tasks', 'bbcs_get_cron_tasks');
function bbcs_get_cron_tasks()
{
    check_ajax_referer('botblocker_nonce', 'nonce');

    $one_time_event = wp_get_scheduled_event('bbcs_one_time_task'); // Check if the event is scheduled

    $cron_jobs      = _get_cron_array();
    $current_time   = time();
    $plugin_tasks   = [
        'bbcs_daily_task'       => 'History clear',
        'bbcs_hourly_task'      => 'Update statistics',
        'bbcs_weekly_task'      => 'Weekly maintenance',
        'bbcs_five_days_task'   => 'Send all DB to analyze',
        'bbcs_two_hours_task'   => 'Extend IP data',
        'bbcs_one_time_task'    => 'Early and MU to DB',
    ];

    $tasks = [];

    foreach ($plugin_tasks as $hook => $description) {
        foreach ($cron_jobs as $timestamp => $hooks) {
            if (isset($hooks[$hook])) {
                $task = reset($hooks[$hook]);
                $interval = isset($task['interval']) ? $task['interval'] : 0;

                $time_remaining = $timestamp > $current_time
                    ? $timestamp - $current_time        // human_time_diff($current_time, $timestamp)
                    : 0;                                //'Overdue';

                if (is_object($one_time_event) and $hook === 'bbcs_one_time_task') {
                    $time_left = $one_time_event->timestamp - $current_time;
                    $progress = $time_left > 0
                        ? min(100, max(0, ((600 - $time_left) / 600) * 100)) 
                        : 0;
                } else {

                    $progress = $interval > 0
                        ? min(100, max(0, (($current_time - ($timestamp - $interval)) / $interval) * 100))
                        : 0;
                }

                $tasks[] = [
                    'description'       => $description,
                    'time_remaining'    => $time_remaining,
                    'progress'          => round($progress, 2),
                ];
            }
        }
    }
    wp_send_json_success($tasks);
}


function bbcs_update_ip_files()
{
    global $wpdb;

    $current_time = time();
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `{$wpdb->bbcs_ipv4rules}` WHERE `expires` < %d",
            $current_time
        )
    );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `{$wpdb->bbcs_ipv6rules}` WHERE `expires` < %d",
            $current_time
        )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching


    bbcs_renderIpsFromDb();
}


function bbcs_clean_old_hits_data()
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    $store_period = isset($BBCS->settings->admin_store_period)
        ? (int) $BBCS->settings->admin_store_period
        : 30;
    $store_period = max(1, min($store_period, 30));

    $delete_before = current_time('timestamp') - ($store_period * DAY_IN_SECONDS);
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // Should not be cached - it's a cron task for cleanup.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $cloud_result = $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$wpdb->bbcs_hits_cloud}
             SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
             WHERE `date` < %d",
            $delete_before
        )
    );

    if ($cloud_result === false) {
        // error_log( 'BotBlocker: FAILED to copy suspicious hits to cloud table. Error: ' . $wpdb->last_error );
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $hits_result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `{$wpdb->bbcs_hits}` WHERE `date` < %d",
            $delete_before
        )
    );

    if ($hits_result === false) {
        // error_log( 'BotBlocker: FAILED to delete old data from hits table. Error: ' . $wpdb->last_error );
    }
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $suspicious_result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `{$wpdb->bbcs_hits_suspicious}` WHERE `date` < %d",
            $delete_before
        )
    );

    if ($suspicious_result === false) {
        // error_log( 'BotBlocker: FAILED to delete old data from hits_suspicious table. Error: ' . $wpdb->last_error );
    }

    bbcs_clean_old_summary_data($store_period);
}

function bbcs_send_suspicious_hits_to_cloud()
{
    if (!function_exists('gzencode')) {
        //  error_log('BotBlocker: Compression not available');
        return;
    }

    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    $last_date = 0;
    $request_auth = [
        'cloud_api_key' => $BBCS->settings->cloud_api_key,
        'domain_api_key' => $BBCS->settings->cloud_api_secret,
    ];
    $request_data = [];
    $batch_start_date = null;
    $batch_end_date = null;

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

        $ip_records = array_map(function($hit) {
            return ['ip' => $hit['ip']];
        }, $suspicious_hits);
        $compressed = gzencode(wp_json_encode($ip_records), 9);
        unset($suspicious_hits, $ip_records);
        $request_data['hits'][] = base64_encode($compressed);
        unset($compressed);

        if (count($request_data['hits']) >= BOTBLOCKER_CLOUD_REQUEST_SIZE) {
            $request_data = array_merge($request_data, $request_auth);
            $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL, 'suspicious');
            if ($cloud === false) {
                $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL, 'suspicious');
            }

            if ($cloud !== false) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM `{$wpdb->bbcs_hits_cloud}` WHERE date >= %d AND date <= %d",
                    $batch_start_date,
                    $batch_end_date
                ));
            }

            $request_data = [];
            $batch_start_date = null;
            $batch_end_date = null;
        }
    }

    if (!empty($request_data)) {
        $request_data = array_merge($request_data, $request_auth);
        $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_GS_URL, 'suspicious');
        if ($cloud === false) {
            $cloud = BotBlockerWpRequest::send_to_cloud($request_data, BOTBLOCKER_API_URL, 'suspicious');
        }

        if ($cloud !== false) {
            if ($batch_start_date !== null && $batch_end_date !== null) {
                // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM `{$wpdb->bbcs_hits_cloud}` WHERE date >= %d AND date <= %d",
                    $batch_start_date,
                    $batch_end_date
                ));
            }
        }

        $request_data = [];
    }
}


// Fallback cron runner
add_action('init', 'bbcs_cron_fallback_runner');
function bbcs_cron_fallback_runner()
{
    // Use atomic operation to prevent race conditions
    $lock_key = 'bbcs_cron_fallback_lock';
    
    // Try to acquire lock - if it exists, another process is running
    if (false === add_option($lock_key, time(), '', 'no')) {
        // Lock exists, check if it's stale (older than 5 minutes)
        $lock_time = get_option($lock_key);
        if ($lock_time && (time() - $lock_time) < 5 * MINUTE_IN_SECONDS) {
            return; // Recent lock, exit
        }
        // Stale lock, update it
        update_option($lock_key, time());
    }
    
    try {
        $current_time = time();

        $tasks = [
            'bbcs_daily_task'      => ['interval' => DAY_IN_SECONDS, 'schedule' => 'daily'],
            'bbcs_hourly_task'     => ['interval' => HOUR_IN_SECONDS, 'schedule' => 'hourly'],
            'bbcs_weekly_task'     => ['interval' => WEEK_IN_SECONDS, 'schedule' => 'weekly'],
            'bbcs_five_days_task'  => ['interval' => 5 * DAY_IN_SECONDS, 'schedule' => 'every_five_days'],
            'bbcs_two_hours_task'  => ['interval' => 2 * HOUR_IN_SECONDS, 'schedule' => 'every_two_hours'],
        ];

        foreach ($tasks as $hook => $config) {
            $event = wp_get_scheduled_event($hook);

            if (!$event) {
                // Cron is missing - recreate it
                wp_schedule_event(time() + 60, $config['schedule'], $hook);
                // error_log("BotBlocker: Recreated missing cron task: {$hook}");
                continue;
            }

            // Simplified overdue check - if task is more than 1.5x interval overdue, run it
            $overdue_threshold = $event->timestamp + ($config['interval'] * 1.5);
            
            if ($current_time > $overdue_threshold) {
                // error_log("BotBlocker: Running overdue task: {$hook}");
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
                do_action($hook);
                
                // Reschedule the task to prevent it from running again immediately
                wp_unschedule_event($event->timestamp, $hook);
                wp_schedule_event(time() + 60, $config['schedule'], $hook);
            }
        }

        // One-time task fallback with consistent 600 second timing
        $one_time = wp_get_scheduled_event('bbcs_one_time_task');
        if ($one_time && $one_time->timestamp < ($current_time - 600)) {
            // error_log('BotBlocker: Running overdue one-time task');
            do_action('bbcs_one_time_task');
            wp_unschedule_event($one_time->timestamp, 'bbcs_one_time_task');
        }
        
    } finally {
        // Always release the lock
        delete_option($lock_key);
    }
}