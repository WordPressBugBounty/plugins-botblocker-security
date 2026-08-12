<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_AddonMarketCardData {
	/** @var string */
	public $slug = '';
	/** @var string */
	public $name = '';
	/** @var string */
	public $icon = '';
	/** @var string */
	public $description = '';
	/** @var bool */
	public $is_active = false;
	/** @var bool */
	public $is_installed = false;
	/** @var bool */
	public $show_pro_badge = false;
	/** @var bool */
	public $is_incompatible = false;
	/** @var bool */
	public $show_local_incompatible = false;
	/** @var bool */
	public $is_footer_incompatible = false;
	/** @var bool */
	public $has_update = false;
	/** @var bool */
	public $is_broken = false;
	/** @var bool */
	public $show_toggle = false;
	/** @var bool */
	public $show_install = false;
	/** @var string */
	public $version = '';
	/** @var string */
	public $remote_ver = '';
	/** @var string */
	public $settings_link = '';
	/** @var string */
	public $install_url = '';
	/** @var string */
	public $update_url = '';
	/** @var string */
	public $update_requires_core = '';
	/** @var bool */
	public $footer_left_full = false;

	public static function from_installed_market(
		Botblocker_AddonMarketItemData $item,
		?Botblocker_AddonInstalledItemData $local,
		string $addons_url
	): self {
		$card                     = new self();
		$card->slug               = $item->slug;
		$card->name               = $item->name !== '' ? $item->name : $item->slug;
		$card->icon               = $item->icon;
		$card->description        = (string) apply_filters( 'bbcs_addon_description', $item->description, $item->slug );
		$card->is_active          = $item->is_active;
		$card->is_installed       = true;
		$card->is_incompatible         = $item->is_incompatible;
		$card->show_local_incompatible = $local && ( $local->incompatible || $local->incompatible_remote );
		$card->is_footer_incompatible  = $card->is_incompatible || $card->show_local_incompatible;
		$card->has_update         = $local && $local->update_avail;
		$card->is_broken          = $local && $local->broken;
		$card->show_toggle        = $local && ! $local->broken && ! $local->incompatible && ! $local->incompatible_remote && ! $item->is_incompatible;
		$card->version            = $local ? $local->version : '';
		$card->remote_ver         = $item->remote_ver;
		$card->settings_link      = ( $item->is_active && $addons_url !== '' ) ? $addons_url . '#' . $item->slug : '';
		$card->update_url         = $item->url;
		$card->update_requires_core = $item->requires_core;

		return $card;
	}

	public static function from_local_installed(
		Botblocker_AddonInstalledItemData $addon,
		string $addons_url
	): self {
		$card                = new self();
		$card->slug          = $addon->slug;
		$card->name          = $addon->name !== '' ? $addon->name : $addon->slug;
		$card->icon          = $addon->icon;
		$card->description   = $addon->description;
		$card->is_active     = $addon->is_active;
		$card->is_installed  = true;
		$card->show_local_incompatible = $addon->incompatible;
		$card->is_footer_incompatible  = $addon->incompatible || $addon->incompatible_remote;
		$card->has_update    = $addon->update_avail;
		$card->is_broken     = $addon->broken;
		$card->show_toggle   = ! $addon->broken && ! $addon->incompatible && ! $addon->incompatible_remote;
		$card->version       = $addon->version;
		$card->settings_link = ( $addon->is_active && $addon->has_settings && $addons_url !== '' ) ? $addons_url . '#' . $addon->slug : '';
		$card->update_url    = $addon->update_url;
		$card->update_requires_core = $addon->update_requires_core;

		return $card;
	}

	public static function from_available_market( Botblocker_AddonMarketItemData $item ): self {
		$card                  = new self();
		$card->slug            = $item->slug;
		$card->name            = $item->name !== '' ? $item->name : $item->slug;
		$card->icon            = $item->icon;
		$card->description     = (string) apply_filters( 'bbcs_addon_description', $item->description, $item->slug );
		$card->is_active       = $item->is_active;
		$card->is_installed    = false;
		$card->show_pro_badge  = $item->show_installed;
		$card->is_incompatible = $item->is_incompatible;
		$card->show_install    = ! $item->is_incompatible;
		$card->remote_ver      = $item->remote_ver;
		$card->install_url     = $item->url;
		$card->footer_left_full = true;

		return $card;
	}
}
