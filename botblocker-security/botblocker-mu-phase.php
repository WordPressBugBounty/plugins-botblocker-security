<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/includes/mu/mu-botblocker-ip.php';
require_once __DIR__ . '/includes/mu/mu-botblocker-header.php';
require_once __DIR__ . '/includes/mu/mu-botblocker-db.php';
require_once __DIR__ . '/includes/mu/mu-botblocker-utils.php';

class BotBlockerMuPhase {

	use BotBlockerMuIP;
	use BotBlockerMuHeader;
	use BotBlockerMuDB;
	use BotBlockerMuUtils;

	private string $ip         = '';
	private string $ip_version = '';
	private array $dirs        = array();
	private string $protocol   = '';
	private array $settings    = array();

	public function __construct() {}

	public function run(): void {
		$this->load_directories();
		$this->load_settings();
		if ( $this->check_disabled_state() ) {
			return;
		}
		if ( $this->check_secret_parameter() ) {
			return;
		}
		if ( $this->is_wordpress_maintenance_request() ) {
			return;
		}
		$this->read_ip();
		$this->read_protocol();
		$res = $this->is_ip_blocked( $this->read_ip_rules(), $this->ip );
		if ( $res ) {
			$this->increment_blocked_hit();
			$this->show_denied_page();
		}
	}

	private function define_no_cache_constants(): void {
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

		$this->register_wpfc_no_cache_filter();
		$this->register_wp_rocket_no_cache_filter();
	}

	private function register_wpfc_no_cache_filter(): void {
		if ( defined( 'BBCS_WPFC_COMPAT' ) ) {
			return;
		}
		define( 'BBCS_WPFC_COMPAT', true );

		add_filter(
			'wpfc_buffer_callback_filter',
			static function ( $buffer, $type = '' ) {
				// Returning '' is only safe for the "html"/"cache" page paths;
				// for asset types (css, js, ...) WPFC writes it to disk as-is.
				if ( 'html' !== $type && 'cache' !== $type ) {
					return $buffer;
				}
				if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
					return '';
				}
				return $buffer;
			},
			1,
			2
		);
	}

	private function register_wp_rocket_no_cache_filter(): void {
		if ( defined( 'BBCS_WPROCKET_COMPAT' ) ) {
			return;
		}
		define( 'BBCS_WPROCKET_COMPAT', true );

		add_filter( 'do_rocket_generate_caching_files', '__return_false', PHP_INT_MAX );
	}

	private function show_denied_page(): void {
		$this->define_no_cache_constants();
		$this->send_no_cache_headers();
		$this->set_iframe_headers();
		$this->set_denied_headers();
		$this->set_denied_page();
		die();
	}

	private function set_denied_page(): void {
		$this->render_denied_page();
	}

	private function render_denied_page(): void {
		$plugin_url = $this->get_plugin_url();
		$info       = $this->ip;

		$allowed_html = array(
			'html'     => array(
				'dir'   => array(),
				'lang'  => array(),
				'style' => array(),
			),
			'head'     => array(),
			'meta'     => array(
				'charset' => array(),
				'name'    => array(),
				'content' => array(),
			),
			'title'    => array(),
			'body'     => array( 'style' => array() ),
			'div'      => array(
				'style' => array(),
				'class' => array(),
			),
			'p'        => array(
				'style' => array(),
				'class' => array(),
			),
			'h1'       => array(
				'style' => array(),
				'class' => array(),
			),
			'span'     => array(
				'style' => array(),
				'class' => array(),
			),
			'small'    => array(
				'style' => array(),
				'class' => array(),
			),
			'b'        => array(),
			'a'        => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
				'rel'    => array(),
				'style'  => array(),
			),
			'img'      => array(
				'src'    => array(),
				'alt'    => array(),
				'style'  => array(),
				'height' => array(),
				'width'  => array(),
				'class'  => array(),
			),
			'noscript' => array(),
			'header'   => array(
				'style' => array(),
				'class' => array(),
			),
			'footer'   => array(
				'style' => array(),
				'class' => array(),
			),
			'style'    => array(),
		);

		$html_template = '
<html dir="ltr" lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="author" content="BotBlocker project by GLOBUS.studio" />
  <meta name="referrer" content="unsafe-url" />
  <meta name="robots" content="noindex" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <link rel="icon" href="data:,">
  <title>BotBlocker security plugin</title>
  <style>
		html,
		body {
			height: 100%;
			margin: 0;
			padding: 0;
			font-family: Arial, sans-serif;
			background-color: #ffffff;
		}

		body {
			display: flex;
			flex-direction: column;
		}

		.header {
			height: 85px;
			background-color: #f0f5f7;
			box-shadow: 0px 3px 7px 3px rgba(0, 0, 0, 0.1);
			display: flex;
			justify-content: center;
			align-items: center;
		}

		.logo {
			height: 65px;
		}

		.content {
			flex-grow: 1;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			padding: 20px;
		}
		.footer {
			height: 50px;
			background-color: #f0f5f7;
			box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: row;
			flex-wrap: wrap;
			align-content: center;
			font-size: 13px;
		}

		.footer small a {
			text-decoration: none;
			color: #2f2f2f;
			margin: 0 10px;
		}

		.info {
			display: flex;
			text-align: center;
			font-size: 20px;
			flex-direction: column;
			flex-wrap: nowrap;
			align-content: center;
			justify-content: center;
			align-items: center;
		}

		.botblocker-btn-success {
			border: 1px solid transparent;
			background: #7785ef;
			color: #ffffff;
			font-size: 16px;
			line-height: 15px;
			padding: 10px 15px;
			text-decoration: none;
			text-shadow: none;
			border-radius: 5px;
			box-shadow: none;
			transition: 0.25s;
			display: block;
			margin: 0 auto;
			font-weight: 600;
		}

		.botblocker-btn-success:hover {
			background-color: #bfc7ff;
		}

		.botblocker-btn-color {
			cursor: pointer;
			padding: 14px 14px;
			text-decoration: none;
			display: inline-block;
			width: 16px;
			height: 16px;
			border-radius: 80px;
		}

		.botblocker-btn-color:hover {
			width: 16px;
			height: 16px;
		}

		.block1 {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			align-content: center;
			justify-content: center;
			align-items: center;
		}

		.block2 {
			display: flex;
			align-content: center;
			justify-content: center;
			align-items: center;
			flex-wrap: wrap;
		}

		h2 {
			text-align: center;
		}

		.user-data {
			display: flex;
			padding-top: 14px;
			font-size: 10px;
			font-weight: 600;
			flex-direction: column;
			flex-wrap: nowrap;
			align-content: center;
			justify-content: center;
			align-items: center;
		}

		.con-center {
			text-align: center;
			padding-top: 3px;
		}
  </style>
</head>
<body>
  <header class="header">
    <img src="' . esc_url( $plugin_url . '/admin/img/logo-small-transparent.webp' ) . '" alt="BotBlocker WordPress Plugin" class="logo">
  </header>

  <div class="content">
    <noscript>
      <h1 style="color:#bd2426;">Please enable JavaScript and reload the page</h1>
    </noscript>

    <div class="bbcs-icon">
      <img src="' . esc_url( $plugin_url . '/public/icons/security.svg' ) . '" alt="BotBlocker WordPress Plugin" class="logo">
    </div>
    <br />

    <div class="container">
      <h1 class="info">Sorry, your request has been denied.</h1>
      <p class="info">Error Code: 403</p>
    </div>

    <div class="user-data">
      <span>Your IP: ' . esc_html( $info ) . '</span>
    </div>
  </div>

  <footer class="footer">
    <small><a href="https://botblocker.top/" title="BotBlocker plugin for WordPress" target="_blank">Protected by <b>BotBlocker</b> plugin</a></small>
    <small><a href="https://globus.studio" title="Project by GLOBUS.studio" target="_blank">BotBlocker is <b>GLOBUS.studio</b> project</a></small>
  </footer>
</body>
</html>';

		echo '<!DOCTYPE html>';
		echo wp_kses( $html_template, $allowed_html );
	}
}
