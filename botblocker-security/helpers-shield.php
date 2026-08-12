<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-define.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-compat.php';
require_once BOTBLOCKER_DIR . 'includes/facade/class-botblocker-core-facade.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-multisite.php';

require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-upload.php';
require_once BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
require_once BOTBLOCKER_DIR . 'languages/locale_and_language_codes.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-env.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-codes.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-reports.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-payment-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-ui.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-alerts.php';
