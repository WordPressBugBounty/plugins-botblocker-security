<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_AlertItemData {
	/** @var string */
	public $title;
	/** @var string */
	public $message;
	/** @var string */
	public $type;
	/** @var string */
	public $icon;
	/** @var string */
	public $link;
	/** @var string */
	public $link_text;

	public function __construct( string $title, string $message, string $type = '', string $icon = '', string $link = '', string $link_text = '' ) {
		$this->title     = $title;
		$this->message   = $message;
		$this->type      = $type;
		$this->icon      = $icon;
		$this->link      = $link;
		$this->link_text = $link_text;
	}
}
