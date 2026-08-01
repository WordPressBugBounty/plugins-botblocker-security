<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-botblocker-system-info-data.php';

function bbcs_get_system_info(): BotBlockerSystemInfoData {
	return BotBlockerSystemInfoData::getInstance();
}
