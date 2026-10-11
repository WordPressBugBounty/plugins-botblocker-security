<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_ProStatusOptionData {
	/** @var string */
	public $key;
	/** @var string */
	public $label;
	/** @var string */
	public $tooltip;
	/** @var string BotBlockerPro::TIER_PRO or BotBlockerPro::TIER_ULTIMATE */
	public $tier;
	/** @var string 'on'|'off'|'locked' */
	public $status;
	/** @var string */
	public $section_url;

	public function __construct( string $key, string $label, string $tooltip, string $tier, string $status, string $section_url ) {
		$this->key         = $key;
		$this->label       = $label;
		$this->tooltip     = $tooltip;
		$this->tier        = $tier;
		$this->status      = $status;
		$this->section_url = $section_url;
	}
}
