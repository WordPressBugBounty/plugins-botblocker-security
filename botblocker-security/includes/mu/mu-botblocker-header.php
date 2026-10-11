<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerMuHeader {

	protected function send_no_cache_headers(): void {
		if ( $this->header_can_send() ) {
			$this->emit_header( 'Pragma: no-cache' );
			$this->emit_header( 'Expires: Thu, 10 Aug 2000 06:00:00 GMT' );
			$this->emit_header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			$this->emit_header( 'X-LiteSpeed-Cache-Control: no-cache' );
			$this->emit_header( 'Service-Worker-Allowed: /' );
		}
	}

	protected function set_iframe_headers(): void {
		$this->emit_header( 'X-Frame-Options: SAMEORIGIN' );
	}

	protected function set_denied_headers(): void {
		$this->emit_header( 'X-Robots-Tag: noindex, noarchive' );
		$this->emit_header( $this->protocol . ' 403 Forbidden' );
		$this->emit_header( 'Status: ' . '403 Forbidden' );
	}

	protected function header_can_send(): bool {
		return ! headers_sent();
	}

	protected function emit_header( string $header_line ): void {
		header( $header_line );
	}
}
