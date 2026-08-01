<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Reports_View {
	/** @var Botblocker_ReportsViewModel */
	private $data;

	public function __construct( Botblocker_ReportsViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_ReportsViewModel $data ): void {
		$this->data = $data;
	}

	public function body(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/reports/body.php';
		$renderer( $this->data );
	}

	public function modals(): void {
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-hits-add-rule.php';
	}
}
