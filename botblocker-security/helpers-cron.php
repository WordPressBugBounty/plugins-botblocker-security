<?php
/**
 * Classes reachable from scheduled-task handlers.
 *
 * These are not needed to serve a page, so the bootstrap does not load them on
 * front-end requests. They must, however, be present whenever a cron hook fires -
 * including BotBlockerCron::fallbackRunner(), which fires those hooks outside
 * wp-cron on ordinary front-end requests. Loaded by helpers-admin.php (admin and
 * wp-cron) and on demand by the fallback runner.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-cloud-api-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-summary.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-llm-sync.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-tls-fingerprints-sync.php';
