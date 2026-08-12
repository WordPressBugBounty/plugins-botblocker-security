<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'helpers-shield.php';

if ( is_admin() || wp_doing_cron() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once BOTBLOCKER_DIR . 'helpers-admin-deferred.php';
}
