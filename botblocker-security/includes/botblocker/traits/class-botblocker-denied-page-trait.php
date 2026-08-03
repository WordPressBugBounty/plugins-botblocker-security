<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerDeniedPageTrait {

	public function render_denied_page() {
		if ( $this->should_show_denied_page === true ) {
			require_once BOTBLOCKER_DIR . 'public/class-botblocker-captcha-renderer.php';
			$title_string               = $this->ip . ' ' . gmdate( 'd.m.Y H:i:s', $this->time );
			$this->template_data_denied = array(
				'extra_data'   => $this->ip,
				'h1_title'     => BotBlockerCaptchaRenderer::t( 'Please enable JavaScript and reload the page' ),
				'message'      => BotBlockerCaptchaRenderer::t( 'Sorry, your request has been denied' ),
				'denied_title' => BotBlockerCaptchaRenderer::t( 'BotBlocker security plugin' ) . $title_string,
				'denied_data'  => $this->denied_data,
			);
			$this->load_denied_template();
			$this->process_die();
		}
	}

	private function create_denied_inline_assets(): array {
		return array(
			'styles'           => array(
				BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'css/template.css' ),
			),
			'scripts'          => array(),
			'external_scripts' => array(),
		);
	}

	private function load_denied_template(): void {
		$template_path = BOTBLOCKER_DIR . 'public/templates/denied-page.php';
		if ( file_exists( $template_path ) ) {
			$data                  = $this->template_data_denied;
			$data['inline_assets'] = $this->create_denied_inline_assets();
			include $template_path;
		}
	}
}
