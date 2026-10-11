<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerAdminSettingsTrait {

	public $empty = '';
	public $botblockerUrl = '';
	public $version = '';
	public $custom_avatar = '';
	public $dirs_public = '';
	public $dirs_languages = '';
	public $dirs_includes = '';
	public $dirs_admin = '';
	public $dirs_data = '';
	public $dirs_vendor = '';
	public $pages_dashboard = '';
	public $pages_settings = '';
	public $pages_integrations = '';
	public $pages_reports = '';
	public $pages_rules = '';
	public $pages_tools = '';
	public $pages_cloud_api = '';
	public $pages_setup = '';
	public $pages_about = '';
	public $pages_addons = '';
	public $pages_wizard = '';
	public $files_IPv4 = '';
	public $files_IPv6 = '';

	private function load_settings(): void {
		$this->load_core_settings();
		$this->load_dirs();
		$this->load_pages();
		$this->load_files();
		$this->load_derived_settings();
	}

	private function load_core_settings(): void {
		$this->empty         = BOTBLOCKER_EMPTY;
		$this->botblockerUrl = BOTBLOCKER_URL;
		$this->version       = BOTBLOCKER_VERSION;
	}

	private function load_dirs(): void {
		$this->dirs_public    = BOTBLOCKER_DIR . 'public/';
		$this->dirs_languages = BOTBLOCKER_DIR . 'languages/';
		$this->dirs_includes  = BOTBLOCKER_DIR . 'includes/';
		$this->dirs_admin     = BOTBLOCKER_DIR . 'admin/';
		$this->dirs_data      = BOTBLOCKER_DIR . 'data/';
		$this->dirs_vendor    = BOTBLOCKER_DIR . 'vendor/';
	}

	private function load_pages(): void {
		$this->pages_dashboard    = BotBlockerMultisite::getAdminPageUrl( 'bbcs_dashboard' );
		$this->pages_settings     = BotBlockerMultisite::getAdminPageUrl( 'bbcs_settings' );
		$this->pages_integrations = BotBlockerMultisite::getAdminPageUrl( 'bbcs_integrations' );
		$this->pages_reports      = BotBlockerMultisite::getAdminPageUrl( 'bbcs_reports' );
		$this->pages_rules        = BotBlockerMultisite::getAdminPageUrl( 'bbcs_rules' );
		$this->pages_tools        = BotBlockerMultisite::getAdminPageUrl( 'bbcs_tools' );
		$this->pages_cloud_api    = BotBlockerMultisite::getAdminPageUrl( 'bbcs_cloud_api' );
		$this->pages_setup        = BotBlockerMultisite::getAdminPageUrl( 'bbcs_setup_guide' );
		$this->pages_about        = BotBlockerMultisite::getAdminPageUrl( 'bbcs_about' );
		$this->pages_addons       = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		$this->pages_wizard       = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' );
	}

	private function load_files(): void {
		$this->files_IPv4 = $this->botblockerUrl . 'data/BotBlocker-test-IPv4-list.txt';
		$this->files_IPv6 = $this->botblockerUrl . 'data/BotBlocker-test-IPv6-list.txt';
	}

	private function load_derived_settings(): void {
		$this->custom_avatar = $this->botblockerUrl . 'admin/img/avatar.png';
	}
}
