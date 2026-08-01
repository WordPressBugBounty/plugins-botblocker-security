<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_ProComparisonRowData {
	/** @var string */
	public $feature;
	/** @var bool */
	public $free;
	/** @var bool */
	public $pro;

	public function __construct( string $feature, bool $free, bool $pro ) {
		$this->feature = $feature;
		$this->free    = $free;
		$this->pro     = $pro;
	}
}
