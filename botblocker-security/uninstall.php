<?php
declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	http_response_code( 404 );
	echo '404 Not Found';
	exit;
}

if ( ! defined( 'BOTBLOCKER' ) ) {
	define( 'BOTBLOCKER', true );
}
define( 'BOTBLOCKER_DIR', plugin_dir_path( __FILE__ ) );

if (
	! file_exists( BOTBLOCKER_DIR . 'includes/inc-botblocker-define.php' )
	|| ! file_exists( BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php' )
	|| ! file_exists( BOTBLOCKER_DIR . 'includes/install/class-botblocker-install.php' )
) {
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Uninstall] Required files missing, aborting.' );
	}
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-define.php';
require_once BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install.php';
require_once BOTBLOCKER_DIR . 'includes/cron/class-botblocker-cron.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-uninstaller.php';

Botblocker_Uninstaller::uninstall();
