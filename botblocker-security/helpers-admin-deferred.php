<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data-time.php';
require_once BOTBLOCKER_DIR . 'includes/pro/class-botblocker-settings-presets.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BOTBLOCKER_DIR . 'includes/utilites/2FA/class-bbcs-2fa-cli.php';
	WP_CLI::add_command( 'bbcs 2fa', 'BBCS_2FA_CLI' );
}
