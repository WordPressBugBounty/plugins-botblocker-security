<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-cron.php';
include_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-cloud-api.php';
include_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-addon.php';
include_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-settings.php';
include_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-login-brutforce.php';
include_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-payment.php';
