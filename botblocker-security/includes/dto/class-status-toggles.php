<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_StatusToggles {
	/** @var int */
	public $early_init_checked;
	/** @var bool */
	public $early_init_available;
	/** @var bool */
	public $early_init_disabled;
	/** @var int */
	public $mu_checked;
	/** @var int */
	public $redis_checked;
	/** @var bool */
	public $redis_disabled;
	/** @var int */
	public $memcached_checked;
	/** @var bool */
	public $memcached_disabled;
	/** @var int */
	public $ptr_cache_checked;
	/** @var string */
	public $ptrcache_time_label;
	/** @var int */
	public $cache_ui_checked;
	/** @var string */
	public $cache_ui_duration_label;
}
