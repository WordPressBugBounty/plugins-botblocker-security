<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_Rules_View {
	/** @var Botblocker_RulesViewModel */
	private $data;

	public function __construct( Botblocker_RulesViewModel $data ) {
		$this->data = $data;
	}

	public function setData( Botblocker_RulesViewModel $data ): void {
		$this->data = $data;
	}

	/** @return Botblocker_RulesViewModel */
	public function getData(): Botblocker_RulesViewModel {
		return $this->data;
	}

	public function body(): void {
		$renderer = require BOTBLOCKER_DIR . 'admin/templates/rules/body.php';
		$renderer( $this->data );
	}

	public function modals(): void {
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-add.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv4-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv4-add.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv6-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv6-add.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-path-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-path-add.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-white-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-white-add.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-proxy-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-proxy-add.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-asn-edit.php';
		require BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-asn-add.php';
	}
}
