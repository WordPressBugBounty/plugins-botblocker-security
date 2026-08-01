<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_AddonTabData {
	/** @var string */
	public $slug;
	/** @var string */
	public $name;
	/** @var string */
	public $icon_image = '';

	public function __construct( string $slug, string $name ) {
		$this->slug = $slug;
		$this->name = $name;
	}

	public function withIconImage( string $url ): self {
		$this->icon_image = $url;
		return $this;
	}
}
