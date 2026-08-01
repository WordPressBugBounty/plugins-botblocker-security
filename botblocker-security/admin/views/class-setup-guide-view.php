<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_SetupGuide_View {
	/** @var Botblocker_SetupGuideViewModel */
	private $data;

	public function __construct( Botblocker_SetupGuideViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_SetupGuideViewModel $data ): void {
		$this->data = $data;
	}

	public function wizard_modal(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-guide/one-click-modal.php';
		$renderer( $this->data );
	}

	public function status_pagehead(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-guide/status.php';
		$renderer( $this->data, 'pagehead' );
	}

	public function status(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-guide/status.php';
		$renderer( $this->data, 'content' );
	}
}
