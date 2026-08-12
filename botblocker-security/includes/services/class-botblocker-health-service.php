<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\HealthItemData;

final class BotBlockerHealthService {

	/**
	 * Get the full list of health definitions (key, label, type).
	 * Must match the old render path exactly.
	 *
	 * @return array<int, array{key: string, label: string, type: string}>
	 */
	public static function getDefinitions(): array {
		return array(
			array( 'key' => 'bbcs_captcha_mode', 'label' => __( 'Captcha enabled', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_empty_ua', 'label' => __( 'Blocking empty User-Agent', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_simplebot_ua', 'label' => __( 'Blocking simple bot User-Agents', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_empty_lang', 'label' => __( 'Blocking empty language', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_nojs_users', 'label' => __( 'Blocking users without JavaScript', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_fake_ref', 'label' => __( 'Blocking fake referrers', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_ip_ptr_match', 'label' => __( 'Blocking PTR/DNS anomalies', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_proxy_users', 'label' => __( 'Blocking classic proxies', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_cf_users', 'label' => __( 'Blocking Cloudflare origin IPs', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_ipv6_users', 'label' => __( 'Blocking IPv6 connections', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_http10_users', 'label' => __( 'Blocking HTTP/1.0 users', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'hosting_block', 'label' => __( 'Blocking hosting providers', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'recaptcha_check', 'label' => __( 'reCaptcha v3 enabled', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_incognito_users', 'label' => __( 'Blocking incognito/private mode', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_adblocker_users', 'label' => __( 'Blocking AdBlock/uBlock users', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'block_simple_antidetect', 'label' => __( 'JS consistency checks', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'iframe_stop', 'label' => __( 'Iframe protection (clickjacking)', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'check_get_ref', 'label' => __( 'GET parameter referrer check', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'samesite', 'label' => __( 'Cookie SameSite policy (Lax/Strict)', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'salt', 'label' => __( 'Cookie salt set', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'cookie_lifetime', 'label' => __( 'Cookie lifetime >= 1 day', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'session_token_enabled', 'label' => __( 'Session token verification enabled', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'time_ban', 'label' => __( 'Time ban enabled', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'time_ban_2', 'label' => __( 'Secondary time ban enabled', 'botblocker-security' ), 'type' => 'core' ),
			array( 'key' => 'check', 'label' => __( 'Cloud validation', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'unresponsive', 'label' => __( 'Ban unresponsive clients', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'block_vpn_users', 'label' => __( 'Blocking VPN users', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'block_tor_users', 'label' => __( 'Blocking Tor users', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'block_override', 'label' => __( 'Override detection', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'block_web_engine_options', 'label' => __( 'Engine parameter checks', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'block_device_options', 'label' => __( 'Device API verification', 'botblocker-security' ), 'type' => 'cloud_extended' ),
			array( 'key' => 'utm_referrer', 'label' => __( 'UTM referrer processing', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'noarchive', 'label' => __( 'X-Robots-Tag: noarchive for blocked pages', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'utm_noindex', 'label' => __( 'X-Robots-Tag: noindex on UTM pages', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'get_browser_type', 'label' => __( 'Collect browser type', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'get_os_type', 'label' => __( 'Collect OS type', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'get_device_type', 'label' => __( 'Collect device type', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'daylight_saving_time', 'label' => __( 'Daylight saving time adjustment', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'block_incorrect_lang_users', 'label' => __( 'Geo IP / Language mismatch filtering', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'autosave_admin_ip', 'label' => __( 'Auto-save administrator IPs', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'email_notifications', 'label' => __( 'Email notifications', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'pusher_notifications', 'label' => __( 'Pusher notifications', 'botblocker-security' ), 'type' => 'neutral' ),
			array( 'key' => 'allow_self_ip_req', 'label' => __( 'Allow requests from server IP', 'botblocker-security' ), 'type' => 'negative' ),
		);
	}

	/**
	 * Check whether a given health setting is enabled.
	 * Must match the old render path exactly.
	 *
	 * @param string     $key      Setting key.
	 * @param object     $settings Settings object.
	 * @param bool       $recaptchaReady Whether reCAPTCHA keys are configured.
	 * @return bool
	 */
	public static function isEnabled( string $key, $settings, bool $recaptchaReady = false ): bool {
		$value = isset( $settings->$key ) ? $settings->$key : null;
		switch ( $key ) {
			case 'bbcs_captcha_mode':
				return $value != -1;
			case 'recaptcha_check':
				return $value == 1 && $recaptchaReady;
			case 'time_ban':
			case 'time_ban_2':
				return (int) $value > 0;
			case 'samesite':
				return in_array( (string) $value, array( 'Lax', 'Strict' ), true );
			case 'salt':
				return ! empty( $value );
			case 'cookie_lifetime':
				return (int) $value >= 86400;
			case 'hosting_block':
				return $value == 1;
			default:
				return $value == 1;
		}
	}

	/**
	 * Calculate the health score using the original formula.
	 * 11 core items → 75 pts, check (special) → 12.5 pts, 7 cloud items → 12.5 pts.
	 *
	 * @param object $settings Settings object.
	 * @param bool   $recaptchaReady Whether reCAPTCHA keys are configured.
	 * @return int
	 */
	public static function calculateHealthScore( $settings, bool $recaptchaReady ): int {
		$health = 0;

		// 11 core items: 75 pts total
		$core_items = array(
			'bbcs_captcha_mode' => function ( $v ) {
				return $v != -1; },
			'block_empty_ua'    => function ( $v ) {
				return $v == 1; },
			'block_empty_lang'  => function ( $v ) {
				return $v == 1; },
			'block_nojs_users'  => function ( $v ) {
				return $v == 1; },
			'block_simplebot_ua' => function ( $v ) {
				return $v == 1; },
			'block_fake_ref'    => function ( $v ) {
				return $v == 1; },
			'block_proxy_users' => function ( $v ) {
				return $v == 1; },
			'block_http10_users' => function ( $v ) {
				return $v == 1; },
			'recaptcha_check'   => function ( $v ) use ( $settings, $recaptchaReady ) {
				return $v == 1 && $recaptchaReady;
			},
			'time_ban'          => function ( $v ) {
				return (int) $v > 0; },
			'time_ban_2'        => function ( $v ) {
				return (int) $v > 0; },
		);

		$num_core       = count( $core_items );
		$weight_per_var = 75 / $num_core;

		foreach ( $core_items as $key => $isEnabled ) {
			$value = isset( $settings->$key ) ? $settings->$key : null;
			if ( $isEnabled( $value ) ) {
				$health += $weight_per_var;
			}
		}

		// Special: check gets 12.5 pts on its own
		if ( isset( $settings->check ) && $settings->check == 1 ) {
			$health += 12.5;
		}

		// 7 cloud items: 12.5 pts total
		$cloud_items = array(
			'unresponsive'             => function ( $v ) {
				return $v == 1; },
			'block_vpn_users'          => function ( $v ) {
				return $v == 1; },
			'block_tor_users'          => function ( $v ) {
				return $v == 1; },
			'block_override'           => function ( $v ) {
				return $v == 1; },
			'block_web_engine_options' => function ( $v ) {
				return $v == 1; },
			'block_device_options'     => function ( $v ) {
				return $v == 1; },
			'hosting_block'            => function ( $v ) {
				return $v == 1; },
		);

		$num_cloud          = count( $cloud_items );
		$weight_per_cloud   = 12.5 / $num_cloud;

		foreach ( $cloud_items as $key => $isEnabled ) {
			$value = isset( $settings->$key ) ? $settings->$key : null;
			if ( $isEnabled( $value ) ) {
				$health += $weight_per_cloud;
			}
		}

		return (int) round( min( 100, max( 0, $health ) ) );
	}

	public static function getChunkedHealthItems( $settings, bool $recaptchaReady ): array {
		$definitions = self::getDefinitions();

		$items = array();
		foreach ( $definitions as $def ) {
			$items[] = new HealthItemData( $def['key'], $def['label'], $def['type'], self::isEnabled( $def['key'], $settings, $recaptchaReady ) );
		}

		return array_chunk( $items, max( 1, (int) ceil( count( $items ) / 3 ) ) );
	}
}
