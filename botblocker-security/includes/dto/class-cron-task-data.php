<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_CronTaskData {

	/** @var string Cron hook name (e.g. 'bbcs_daily_task') */
	public $hook;

	/** @var string Human-readable schedule (e.g. 'daily', 'hourly', 'single (600 s)') */
	public $schedule;

	/** @var int Interval in seconds */
	public $interval;

	/** @var string Translated label */
	public $label;

	/** @var int|null Next run timestamp, or null for unscheduled one-time events */
	public $next_run;

	/** @var string Status: 'active', 'pending', 'overdue' */
	public $status;

	/** @var string Type: 'recurring', 'one-time' */
	public $type;

	/** @var string|null ISO-8601 formatted next run date, or null */
	public $next_run_display;

	/** @var float Progress percentage 0-100 */
	public $progress = 0.0;

	/** @var int Time remaining in seconds until next run */
	public $time_remaining = 0;
}
