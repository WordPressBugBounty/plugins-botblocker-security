<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installed add-ons merged with the market feed: install/update flags, compatibility, etc.
 * Built by BotBlockerAddonsMarket::buildContext().
 */
final class Botblocker_AddonsMarketContext {
	/** @var array<string,array<string,mixed>> */
	public $addons;
	/** @var array<int,string> */
	public $active;
	/** @var array<int,array<string,mixed>> */
	public $market;
	/** @var array<string,array<string,mixed>> */
	public $market_by_slug;
	/** @var bool */
	public $addons_locked;
	/** @var bool */
	public $has_cloud_api;
	/** @var int */
	public $updates_count;
	/** @var bool */
	public $addons_local_mode;

	public function __construct(
		array $addons,
		array $active,
		array $market,
		array $market_by_slug,
		bool $addons_locked,
		bool $has_cloud_api,
		int $updates_count,
		bool $addons_local_mode
	) {
		$this->addons            = $addons;
		$this->active            = $active;
		$this->market            = $market;
		$this->market_by_slug    = $market_by_slug;
		$this->addons_locked     = $addons_locked;
		$this->has_cloud_api     = $has_cloud_api;
		$this->updates_count     = $updates_count;
		$this->addons_local_mode = $addons_local_mode;
	}
}
