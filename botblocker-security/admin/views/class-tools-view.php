<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Tools_View {
	/** @var Botblocker_ToolsViewModel */
	private $data;

	public function __construct( Botblocker_ToolsViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_ToolsViewModel $data ): void {
		$this->data = $data;
	}

	public function getData(): ?Botblocker_ToolsViewModel {
		return $this->data;
	}

	public function tools_content(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/tools/tools-content.php';
		$renderer( $this, $this->data );
	}

	/**
	 * Render native bbcs-modal-* confirmation dialogs for the tools page.
	 * These use the design system from bbcs.css (not Bootstrap).
	 */
	public function modals(): void {
		$base = BOTBLOCKER_DIR . 'admin/templates/tools/modals/';
		$modals = array(
			'salt-clear.php',
			'log-clear.php',
			'db-repair.php',
			'hits-clear.php',
			'transient-clear.php',
			'rewrite-rules.php',
			'object-cache.php',
		);
		foreach ( $modals as $file ) {
			$path = $base . $file;
			if ( file_exists( $path ) ) {
				$renderer = require $path;
				$renderer();
			}
		}
	}
}
