<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_CloudApi_View {
	/** @var Botblocker_CloudApiViewModel */
	private $data;

	public function __construct( Botblocker_CloudApiViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_CloudApiViewModel $data ): void {
		$this->data = $data;
	}

	public function status(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/cloud-api/status.php';
		$renderer( $this->data );
	}
}
