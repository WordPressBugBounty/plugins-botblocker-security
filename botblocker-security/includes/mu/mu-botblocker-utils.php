<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerMuUtils {

	//BBCS-MULTISITE
	private function load_directories(): void {
		$uploads_base = '';
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload = wp_upload_dir();
			if ( is_array( $upload ) && empty( $upload['error'] ) && ! empty( $upload['basedir'] ) ) {
				$uploads_base = $upload['basedir'];
			}
		}

		if ( '' === $uploads_base ) {
			if ( defined( 'WP_CONTENT_DIR' ) ) {
				$uploads_base = rtrim( WP_CONTENT_DIR, "/\\" ) . '/uploads';
			} elseif ( defined( 'ABSPATH' ) ) {
				$uploads_base = rtrim( ABSPATH, "/\\" ) . '/wp-content/uploads';
			} else {
				$uploads_base = rtrim( dirname( __DIR__, 4 ), "/\\" ) . '/wp-content/uploads';
			}

			if ( function_exists( 'is_multisite' ) && is_multisite()
				&& function_exists( 'get_current_blog_id' ) ) {
				$blog_id = get_current_blog_id();
				if ( $blog_id > 1 ) {
					$uploads_base .= '/sites/' . $blog_id;
				}
			}
		}
		$uploads_base = str_replace( '\\', '/', $uploads_base );
		$bot_data_dir = rtrim( $uploads_base, '/' ) . '/botblocker/data/';

		$this->dirs = array(
			'data' => $bot_data_dir
		);
	}

	private function load_settings(): void {
		$this->settings = [];
		$settings_file = $this->dirs['data'] . 'settings.php';
		if (file_exists($settings_file) && is_readable($settings_file)) {
			$loaded = include $settings_file;
			if (is_array($loaded)) {
				$this->settings = $loaded;
			}
		}
	}

	private function check_disabled_state(): bool {
		return isset($this->settings['disable']) && $this->settings['disable'] === 1;
	}

	private function check_secret_parameter(): bool
	{
		$param = $this->settings['secret_botblocker_get_param'] ?? '';
		$empty_marker = defined('BOTBLOCKER_EMPTY') ? BOTBLOCKER_EMPTY : '-';
		// REVIEWER NOTE: This is not a form, but a secret GET param for internal BotBlocker plugin control
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ($param !== '' && $param !== $empty_marker) {
			$cookie_set = isset($_COOKIE[$param]);
			if (isset($_GET[$param]) || $cookie_set) {
				$valid_values = array(
					$this->settings['action_disable'] ?? '',
					$this->settings['action_off'] ?? '',
					$this->settings['action_on'] ?? ''
				);
				if (isset($_GET[$param]) && in_array(sanitize_text_field( wp_unslash( $_GET[$param] ) ), $valid_values, true)) {
					return true;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return false;
	}

	private function read_protocol() : void {
		$this->protocol = isset($_SERVER['SERVER_PROTOCOL'])
			? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) )
			: 'HTTP/1.0';
	}

	private function get_plugin_url(): string {
		if (defined('WP_PLUGIN_DIR') && defined('WP_PLUGIN_URL')) {
			$rel = str_replace('\\', '/', trim(str_replace(WP_PLUGIN_DIR, '', __DIR__), '/'));
			$segments = explode('/', $rel);
			$plugin_root = $segments[0] ?? $rel;
			return rtrim(WP_PLUGIN_URL, '/') . '/' . $plugin_root;
		}
		$docRootRaw = isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : '';
		$docRoot = rtrim(str_replace('\\', '/', $docRootRaw), '/');
		$docRoot = preg_replace('~[^A-Za-z0-9/_\.\-]~', '', $docRoot);
		$pluginAbs = str_replace('\\', '/', __DIR__);
		$relPath = '';
		if ($docRoot !== '' && substr($pluginAbs, 0, strlen($docRoot)) === $docRoot) {
			$relPath = substr($pluginAbs, strlen($docRoot));
		} elseif (defined('ABSPATH')) {
			$abs = str_replace('\\', '/', ABSPATH);
			$relPath = '/' . ltrim(str_replace($abs, '', $pluginAbs), '/');
		} else {
			$relPath = $pluginAbs;
		}

		$hostRaw = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : 'localhost';
		$host = function_exists('sanitize_text_field') ? sanitize_text_field($hostRaw) : preg_replace('~[^A-Za-z0-9\.\-:_]~', '', $hostRaw);
		$httpsRaw = isset($_SERVER['HTTPS']) ? sanitize_text_field(wp_unslash($_SERVER['HTTPS'])) : '';
		$portRaw = isset($_SERVER['SERVER_PORT']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_PORT'])) : '';
		$https = (!empty($httpsRaw) && strtolower((string)$httpsRaw) !== 'off') || (string)$portRaw === '443';
		$scheme = $https ? 'https' : 'http';

		return $scheme . '://' . $host . rtrim('/' . ltrim($relPath, '/'), '/');
	}

}
