<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerBlockPageTrait {

	public function render_block_page() {
		if ( $this->should_show_block_page === true ) {
			$title_string              = $this->ip . ' ' . gmdate( 'd.m.Y H:i:s', $this->time );
			$this->template_data_block = array(
				'extra_data'  => $this->ip,
				'h1_title'    => __( 'Please enable JavaScript and reload the page', 'botblocker-security' ),
				//'message' => __('Sorry, your request has been blocked', 'botblocker-security'),
				'block_title' => __( 'BotBlocker security plugin', 'botblocker-security' ) . $title_string,
				'block_data'  => $this->block_data,
			);
			$this->prepare_block_js_data();
			$this->load_block_template();
			$this->process_die();
		}
	}

	private function prepare_block_js_data(): void {
		$wait                = isset( $this->block_wait_seconds ) ? (int) $this->block_wait_seconds : 0;
		$reason_view         = ( defined( 'BBCS_BLOCK_REASON_VIEW' ) && BBCS_BLOCK_REASON_VIEW );
		$this->block_js_data = array(
			'hasCountdown'  => ( $wait > 0 ),
			'waitSeconds'   => $wait,
			'accessBlocked' => __( 'Access has been blocked', 'botblocker-security' ),
			'secondsLeft'   => __( 'Seconds remaining until unlock:', 'botblocker-security' ),
			'reasonView'    => $reason_view,
			'reasonText'    => $reason_view ? $this->block_data : '',
		);
	}

	private function create_block_inline_assets(): array {
		return array(
			'styles'           => array(
				BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'css/template.css' ),
			),
			'scripts'          => array(
				'var bbcsBlockData = ' . BotBlockerSecurityPageAssets::json( $this->block_js_data ) . ';',
				BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'js/block.js' ),
			),
			'external_scripts' => array(),
		);
	}

	private function load_block_template(): void {
		$template_path = BOTBLOCKER_DIR . 'public/templates/block-page.php';
		if ( file_exists( $template_path ) ) {
			$data                  = $this->template_data_block;
			$data['inline_assets'] = $this->create_block_inline_assets();
			include $template_path;
		}
	}
}
