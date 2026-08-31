<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-define.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-compatibility.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-geo.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-multisite.php';

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-uploads.php';
require_once BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-env.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data-codes.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data-reports.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-payment-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-ui.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-alerts.php';

require_once BOTBLOCKER_DIR . 'includes/utilites/2FA/class-botblocker-two-factor-auth.php';
BotBlockerTwoFactorAuth::bootstrap();
