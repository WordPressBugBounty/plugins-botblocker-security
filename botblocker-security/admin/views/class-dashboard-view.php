<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Dashboard_View {
	/** @var Botblocker_DashboardViewModel */
	private $data;

	public function __construct( Botblocker_DashboardViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_DashboardViewModel $data ): void {
		$this->data = $data;
	}

	public function countries(): void {
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-countries-list.php';
	}

	// -- New UI section methods -- /

	public function hero(): void {
		$this->render_template( 'hero.php' );
	}

	public function kpi(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/shared/kpi-primary-grid.php';
		$renderer( $this->data, true );
	}

	public function quick_links(): void {
		$this->render_template( 'quick-links.php' );
	}

	public function activity(): void {
		$this->render_template( 'activity.php' );
	}

	public function health_status(): void {
		$this->render_template( 'health-status.php' );
	}

	public function modals(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/dashboard/secret.php';
		$renderer( $this->data );
	}

	private function render_template( string $file ): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/dashboard/' . $file;
		$renderer( $this->data );
	}
}
