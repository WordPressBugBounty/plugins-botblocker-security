<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/traits/trait-botblocker-stats-core.php';
require_once __DIR__ . '/traits/trait-botblocker-stats-charts.php';
require_once __DIR__ . '/traits/trait-botblocker-stats-top.php';
require_once __DIR__ . '/traits/trait-botblocker-stats-map.php';
require_once __DIR__ . '/traits/trait-botblocker-stats-logs.php';
require_once __DIR__ . '/traits/trait-botblocker-stats-blocked.php';

class BotBlockerStats {
	use BotBlockerStatsCoreTrait;
	use BotBlockerStatsChartsTrait;
	use BotBlockerStatsTopTrait;
	use BotBlockerStatsMapTrait;
	use BotBlockerStatsLogsTrait;
	use BotBlockerStatsBlockedTrait;
}

