<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!defined('BOTBLOCKER')) define('BOTBLOCKER', true);
if(!defined('BOTBLOCKER_EMPTY')) define('BOTBLOCKER_EMPTY', '-');
if(!defined('BOTBLOCKER_TABLE_PREFIX')) define('BOTBLOCKER_TABLE_PREFIX', 'bbcs_');

require_once __DIR__ . '/includes/inc-botblocker-tables.php';
require_once __DIR__ . '/botblocker-mu-phase.php';

class BotBlockerMu extends BotBlockerMuPhase
{
    public function __construct()
	{
		parent::__construct();
	}
}