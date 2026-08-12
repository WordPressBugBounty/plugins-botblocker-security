<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_AddonMarketItemData {
	/** @var string */
	public $name;
	/** @var string */
	public $slug;
	/** @var string */
	public $icon;
	/** @var string */
	public $remote_ver;
	/** @var string */
	public $description;
	/** @var string */
	public $url;
	/** @var string */
	public $requires_core;
	/** @var bool */
	public $is_active;
	/** @var bool */
	public $show_installed;
	/** @var bool */
	public $is_incompatible;
	/** @var bool */
	public $is_installed;

	public function __construct( array $raw ) {
		$this->name           = (string) ( $raw['name'] ?? $raw['slug'] ?? '' );
		$this->slug           = (string) ( $raw['slug'] ?? '' );
		$this->icon           = (string) ( $raw['icon'] ?? '' );
		$this->remote_ver     = (string) ( $raw['remote_ver'] ?? '' );
		$this->description    = (string) ( $raw['description'] ?? '' );
		$this->url            = (string) ( $raw['url'] ?? '' );
		$this->requires_core  = (string) ( $raw['requires_core'] ?? '' );
		$this->is_active      = (bool) ( $raw['is_active'] ?? false );
		$this->show_installed = (bool) ( $raw['show_installed'] ?? false );
		$this->is_incompatible = (bool) ( $raw['is_incompatible'] ?? false );
		$this->is_installed    = (bool) ( $raw['is_installed'] ?? false );
	}
}
