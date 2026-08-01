<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HealthItemData {
	/** @var string */
	public $key;
	/** @var string */
	public $label;
	/** @var string */
	public $type;
	/** @var bool */
	public $enabled;

	public function __construct( string $key, string $label, string $type, bool $enabled ) {
		$this->key     = $key;
		$this->label   = $label;
		$this->type    = $type;
		$this->enabled = $enabled;
	}
}
