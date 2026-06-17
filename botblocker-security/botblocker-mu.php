<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'BOTBLOCKER' ) ) {
	define( 'BOTBLOCKER', true );
}
if ( ! defined( 'BOTBLOCKER_EMPTY' ) ) {
	define( 'BOTBLOCKER_EMPTY', '-' );
}
if ( ! defined( 'BBCS_RULE_ALLOW' ) ) {
	define( 'BBCS_RULE_ALLOW', 'allow' );
}
if ( ! defined( 'BBCS_RULE_BLOCK' ) ) {
	define( 'BBCS_RULE_BLOCK', 'block' );
}
if ( ! defined( 'BBCS_RULE_GRAY' ) ) {
	define( 'BBCS_RULE_GRAY', 'gray' );
}
if ( ! defined( 'BBCS_RULE_DARK' ) ) {
	define( 'BBCS_RULE_DARK', 'dark' );
}
if ( ! defined( 'BOTBLOCKER_TABLE_PREFIX' ) ) {
	define( 'BOTBLOCKER_TABLE_PREFIX', 'bbcs_' );
}

require_once __DIR__ . '/includes/utilites/util-botblocker-data-file.php';
require_once __DIR__ . '/includes/database/inc-botblocker-tables.php';
require_once __DIR__ . '/botblocker-mu-phase.php';

class BotBlockerMu extends BotBlockerMuPhase {

	public function __construct() {
		parent::__construct();
	}
}
