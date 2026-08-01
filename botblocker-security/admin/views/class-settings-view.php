<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Settings_View {
	/** @var Botblocker_SettingsViewModel */
	private $data;

	public function __construct( Botblocker_SettingsViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_SettingsViewModel $data ): void {
		$this->data = $data;
	}

	public function getData(): ?Botblocker_SettingsViewModel {
		return $this->data;
	}

	public function page_head(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/settings/page-head.php';
		$renderer( $this->data );
	}

	public function preset_card(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/settings/preset-card.php';
		$renderer( $this->data );
	}

	public function settings_content(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/settings/settings-content.php';
		$renderer( $this->data );
	}

	public function save_settings(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/settings/save-settings.php';
		$renderer( $this->data );
	}

	public function wizard_modal(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-guide/one-click-modal.php';
		// Build a lightweight data object compatible with the modal.
		$modal_data = (object) array(
			'has_pro'       => $this->data->has_pro,
			'cloud_api_url' => $this->data->urls->cloud_api,
			'wizard_url'    => $this->data->urls->wizard,
		);
		$renderer( $modal_data );
	}

	// Legacy main_settings() and advanced_settings() were removed - see roadmap/ui/refactor-rule-violations.md V-10.
}
