<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_login_failed', array( 'BotBlockerLoginBruteForce', 'onFailedLogin' ), 10, 1 );
add_action( 'wp_login', array( 'BotBlockerLoginBruteForce', 'onSuccessLogin' ), 10, 1 );
