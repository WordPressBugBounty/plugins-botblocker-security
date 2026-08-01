<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Integrations_View {
	/** @var Botblocker_IntegrationsViewModel */
	private $data;

	public function __construct( Botblocker_IntegrationsViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_IntegrationsViewModel $data ): void {
		$this->data = $data;
	}

	public function getData(): ?Botblocker_IntegrationsViewModel {
		return $this->data;
	}

	public function integrations_content(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/integrations/integrations-content.php';
		$renderer( $this->data );
	}
}
