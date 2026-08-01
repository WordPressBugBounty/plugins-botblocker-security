<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TabItem {
	/** @var string */
	public $id;
	/** @var string */
	public $href;
	/** @var bool */
	public $active;
	/** @var string */
	public $class;
	/** @var string */
	public $item_class;
	/** @var string */
	public $label;
	/** @var string */
	public $icon;
	/** @var string */
	public $icon_image = '';

	public function __construct(
		string $id = '',
		string $href = '',
		bool $active = false,
		string $class = '',
		string $item_class = '',
		string $label = '',
		string $icon = ''
	) {
		$this->id         = $id;
		$this->href       = $href !== '' ? $href : '#' . $id;
		$this->active     = $active;
		$this->class      = $class;
		$this->item_class = $item_class;
		$this->label      = $label;
		$this->icon       = $icon;
	}

	public function withIconImage( string $url ): self {
		$this->icon_image = $url;
		return $this;
	}
}
