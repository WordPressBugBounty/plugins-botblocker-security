<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_ChangelogEntryData {
	/** @var string */
	public $ver;
	/** @var string */
	public $date;
	/** @var string */
	public $desc;

	public function __construct( string $ver, string $date, string $desc ) {
		$this->ver  = $ver;
		$this->date = $date;
		$this->desc = $desc;
	}
}
