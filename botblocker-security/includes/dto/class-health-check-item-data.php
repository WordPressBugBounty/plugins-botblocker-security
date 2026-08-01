<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_HealthCheckItemData {
	/** @var string */
	public $label;
	/** @var bool */
	public $ok;
	/** @var bool */
	public $pro;

	public function __construct( string $label, bool $ok, bool $pro = false ) {
		$this->label = $label;
		$this->ok    = $ok;
		$this->pro   = $pro;
	}
}
