<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once BOTBLOCKER_DIR . 'includes/inc-botblocker-ajax.php';
include_once BOTBLOCKER_DIR . 'includes/inc-botblocker-hook.php';
include_once BOTBLOCKER_DIR . 'includes/inc-botblocker-shortcode.php';

include_once BOTBLOCKER_DIR . 'includes/inc-botblocker-pro.php';
include_once BOTBLOCKER_DIR . 'includes/inc-botblocker-check.php';
include_once BOTBLOCKER_DIR . 'includes/inc-botblocker-counters.php';

include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-mail.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-ip.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-cache.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-user.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-request.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-asn-db.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-summary.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-stat.php';

include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-wp.php'; 

include_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-addons.php';

require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-support.php';