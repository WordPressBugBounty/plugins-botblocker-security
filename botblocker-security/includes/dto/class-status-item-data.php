<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_StatusItemData {
	/** @var string */
	public $label;
	/** @var bool */
	public $ok;
	/** @var bool */
	public $warn;
	/** @var bool */
	public $pro;
	/** @var string Machine key for mapping to settings tabs */
	public $key = '';

	public function __construct( string $label, bool $ok, bool $warn = false, bool $pro = false ) {
		$this->label = $label;
		$this->ok    = $ok;
		$this->warn  = $warn;
		$this->pro   = $pro;
	}
}
