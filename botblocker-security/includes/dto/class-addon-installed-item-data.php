<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_AddonInstalledItemData {
	/** @var string */
	public $name;
	/** @var string */
	public $slug;
	/** @var string */
	public $icon;
	/** @var string */
	public $version;
	/** @var string */
	public $description;
	/** @var bool */
	public $is_active;
	/** @var bool */
	public $has_settings;
	/** @var bool */
	public $update_avail;
	/** @var bool */
	public $incompatible;
	/** @var bool */
	public $incompatible_remote;
	/** @var bool */
	public $broken;
	/** @var string */
	public $update_url;
	/** @var string */
	public $update_requires_core;

	public function __construct( string $slug, array $raw ) {
		$this->name                = (string) ( $raw['name'] ?: $slug );
		$this->slug                = $slug;
		$this->icon                = (string) ( $raw['icon'] ?? '' );
		$this->version             = (string) ( $raw['version'] ?? '' );
		$this->description         = (string) ( $raw['description'] ?? '' );
		$this->is_active           = (bool) ( $raw['is_active'] ?? false );
		$this->has_settings        = (bool) ( $raw['has_settings'] ?? false );
		$this->update_avail        = (bool) ( $raw['update_avail'] ?? false );
		$this->incompatible        = (bool) ( $raw['incompatible'] ?? false );
		$this->incompatible_remote = (bool) ( $raw['incompatible_remote'] ?? false );
		$this->broken              = (bool) ( $raw['broken'] ?? false );
		$this->update_url          = (string) ( $raw['update_url'] ?? '' );
		$this->update_requires_core = (string) ( $raw['update_requires_core'] ?? '' );
	}
}
