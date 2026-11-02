<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_SelfTest_View {
	/** @var Botblocker_SelfTestViewModel */
	private $data;

	public function __construct( Botblocker_SelfTestViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_SelfTestViewModel $data ): void {
		$this->data = $data;
	}

	public function body(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/selftest/body.php';
		$renderer( $this->data );
	}
}
