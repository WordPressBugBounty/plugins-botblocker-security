<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_About_View {
	/** @var Botblocker_AboutViewModel */
	private $data;

	public function __construct( Botblocker_AboutViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_AboutViewModel $data ): void {
		$this->data = $data;
	}

	public function body(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/about/body.php';
		$renderer( $this->data );
	}
}
