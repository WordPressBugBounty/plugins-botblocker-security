<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_SetupWizard_View {

	/** @var Botblocker_SetupWizard_ViewModel */
	private $data;

	public function __construct( Botblocker_SetupWizard_ViewModel $data ) {
		$this->data = $data;
	}

	public function welcome(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/welcome.php';
		$renderer( $this->data );
	}

	public function presets(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/presets.php';
		$renderer( $this->data );
	}

	public function compatibility(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/compatibility.php';
		$renderer( $this->data );
	}

	public function exclusions(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/exclusions.php';
		$renderer( $this->data );
	}

	public function captcha(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/captcha.php';
		$renderer( $this->data );
	}

	public function init_mode(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/init-mode.php';
		$renderer( $this->data );
	}

	public function cache(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/cache.php';
		$renderer( $this->data );
	}

	public function finish(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/finish.php';
		$renderer( $this->data );
	}
}
