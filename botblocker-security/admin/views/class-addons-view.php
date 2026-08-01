<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Addons_View {
	/** @var Botblocker_AddonsViewModel */
	private $data;

	public function __construct( Botblocker_AddonsViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_AddonsViewModel $data ): void {
		$this->data = $data;
	}

	public function getData(): ?Botblocker_AddonsViewModel {
		return $this->data;
	}

	public function updates_count(): int {
		return $this->data->updates_count;
	}

	public function addons_content(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/addons/addons-content.php';
		$renderer( $this, $this->data );
	}

	public function upload_section(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/addons/upload-section.php';
		$renderer( $this->data );
	}

	public function locked_notice(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/addons/locked-notice.php';
		$renderer( $this->data );
	}

	public function render_addon_settings( string $slug ): void {
		$addons = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::scanAll() : array();
		$addon_data   = $addons[ $slug ] ?? array();
		$settings_path = $addon_data['settings'] ?? '';

		if ( $settings_path && file_exists( $settings_path ) ) {
			include $settings_path;
		}
	}
}
