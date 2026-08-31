<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-loader.php';

require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-activator.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-deactivator.php';

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-shortcodes.php';
BotBlockerShortcodes::register();

require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-stats.php';
require_once BOTBLOCKER_DIR . 'includes/cron/class-botblocker-cron.php';
