<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include the file for handling hits.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-hits.php';

// Include the file for handling rules.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-rules.php';

// Include the file for handling IPv4 rules.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-rules-ipv4.php';

// Include the file for handling IPv6 rules.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-rules-ipv6.php';

// Include the file for handling paths.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-path.php';

// Include the file for handling white bots.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-white-bots.php';

// Include the file for handling proxies.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-proxy.php';

// Include the file with common ajax functions.
include_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-common.php';
