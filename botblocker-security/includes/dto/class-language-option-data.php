<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_LanguageOptionData {
	/** @var string */
	public $lang;
	/** @var string */
	public $flag;
	/** @var string */
	public $name;

	public function __construct( string $lang, string $flag, string $name ) {
		$this->lang = $lang;
		$this->flag = $flag;
		$this->name = $name;
	}
}
