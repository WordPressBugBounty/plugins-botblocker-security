<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BotBlockerSystemInfoData {

	private static $instance;

	public static function getInstance(): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}
	public function __wakeup() {
		throw new \RuntimeException( 'Unserialize is not allowed.' );
	}

	public $os;
	public $web;
	public $db_version;
	public $php;
	public $wp;
	public $bb_version;
	public $memory;
	public $max_exec;
	public $post_max;
	public $upload_max;

	private function __construct() {
		global $wpdb;

		$this->os         = php_uname( 's' ) . ' ' . php_uname( 'r' );
		$this->web        = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$this->db_version = $wpdb->db_version();
		$this->php        = phpversion();
		$this->wp         = get_bloginfo( 'version' );
		$this->bb_version = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '';
		$this->memory     = ini_get( 'memory_limit' );
		$this->max_exec   = ini_get( 'max_execution_time' );
		$this->post_max   = ini_get( 'post_max_size' );
		$this->upload_max = ini_get( 'upload_max_filesize' );
	}
}
