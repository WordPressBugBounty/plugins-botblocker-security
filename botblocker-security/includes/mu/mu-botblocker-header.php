<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerMuHeader {

	private function send_no_cache_headers(): void
	{
		if (!headers_sent()) {
			header('Pragma: no-cache');
			header('Expires: Thu, 10 Aug 2000 06:00:00 GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Service-Worker-Allowed: /');
		}
	}

	private function set_iframe_headers(): void {
		header('X-Frame-Options: SAMEORIGIN');
	}

	private function set_denied_headers(): void {
		header('X-Robots-Tag: noindex, noarchive');
		header($this->protocol . ' 403 Forbidden');
		header('Status: ' . '403 Forbidden');
	}    

}