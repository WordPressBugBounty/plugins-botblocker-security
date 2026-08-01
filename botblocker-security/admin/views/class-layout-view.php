<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Layout_View {
	/** @var Botblocker_DashboardViewModel|Botblocker_SetupGuideViewModel|Botblocker_SettingsViewModel|Botblocker_CloudApiViewModel|Botblocker_ToolsViewModel|Botblocker_IntegrationsViewModel|Botblocker_RulesViewModel|Botblocker_ReportsViewModel|Botblocker_AddonsViewModel|Botblocker_AboutViewModel */
	private $data;

	/** @var bool Whether to use the new shell templates (plughead + rail). */
	private $use_new_shell = false;

	public function __construct( object $data, bool $use_new_shell = false ) {
		$this->data          = $data;
		$this->use_new_shell = $use_new_shell;
	}

	public function setData( object $data ): void {
		$this->data = $data;
	}

	public function header(): void {
		if ( $this->use_new_shell ) {
			$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/header/plughead.php';
			$renderer( $this->data->header );
			return;
		}
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/header/header.php';
		$renderer( $this->data->header );
	}

	public function sidebar(): void {
		if ( $this->use_new_shell ) {
			$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/sidebar/rail.php';
			$renderer( $this->data->sidebar );
			return;
		}
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/sidebar/sidebar.php';
		$renderer( $this->data->sidebar );
	}

	public function icons_sprite(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/icons-sprite.php';
		$renderer();
	}

	public function command_palette(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/command-palette.php';
		$renderer();
	}

	private function render_template( string $file ): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/' . $file;
		$renderer( $this->data );
	}
}
