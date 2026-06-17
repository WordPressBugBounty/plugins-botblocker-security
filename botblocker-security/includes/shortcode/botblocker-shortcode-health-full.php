<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_render_health_full_shortcode( $atts ): string {
	$BBCS = BotBlocker::getInstance();

	$defaults = array( 'cols' => 3 );
	$atts     = is_array( $atts ) ? $atts : array();
	$atts     = shortcode_atts( $defaults, $atts, 'bbcs_health_full' );
	$cols     = (int) $atts['cols'];
	if ( $cols < 1 ) {
		$cols = 1;
	} if ( $cols > 8 ) {
		$cols = 8;
	}

	if ( $BBCS->isDisabled ) {
		return '<div class="bbcs-health-full"><div class="bbcs-health-col"><span class="bbcs-health-list-item text-danger"><i class="fa-regular fa-circle-xmark"></i> ' . esc_html__( 'BotBlocker is disabled', 'botblocker-security' ) . '</span></div></div>';
	}

	$settings       = isset( $BBCS->settings ) ? $BBCS->settings : (object) array();
	$recaptchaReady = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );
	$cacheEnabled   = isset( $settings->cache_ui_data ) && (int) $settings->cache_ui_data === 1;
	$cacheKey       = 'bbcs_health_full_html_';
	if ( $cacheEnabled ) {
		$cached = get_transient( $cacheKey );
		if ( $cached !== false ) {
			return $cached;
		}
	}

	$rawItems = array(
		// core
		array(
			'key'   => 'bbcs_captcha_mode',
			'label' => __( 'CAPTCHA enabled', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_empty_ua',
			'label' => __( 'Blocking empty User-Agent', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_simplebot_ua',
			'label' => __( 'Blocking simple bot User-Agents', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_empty_lang',
			'label' => __( 'Blocking empty language', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_nojs_users',
			'label' => __( 'Blocking users without JavaScript', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_fake_ref',
			'label' => __( 'Blocking fake referrers', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_ip_ptr_match',
			'label' => __( 'Blocking PTR/DNS anomalies', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_proxy_users',
			'label' => __( 'Blocking classic proxies', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_cf_users',
			'label' => __( 'Blocking Cloudflare origin IPs', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_ipv6_users',
			'label' => __( 'Blocking IPv6 connections', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_http10_users',
			'label' => __( 'Blocking HTTP/1.0 users', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'hosting_block',
			'label' => __( 'Blocking hosting providers', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'recaptcha_check',
			'label' => __( 'reCAPTCHA v3 enabled', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_incognito_users',
			'label' => __( 'Blocking incognito/private mode', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_adblocker_users',
			'label' => __( 'Blocking AdBlock/uBlock users', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'block_simple_antidetect',
			'label' => __( 'JS consistency checks', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'iframe_stop',
			'label' => __( 'Iframe protection (clickjacking)', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'check_get_ref',
			'label' => __( 'GET parameter referrer check', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'samesite',
			'label' => __( 'Cookie SameSite policy (Lax/Strict)', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'salt',
			'label' => __( 'Cookie salt set', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'cookie_lifetime',
			'label' => __( 'Cookie lifetime >= 1 day', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'session_token_enabled',
			'label' => __( 'Session token verification enabled', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'time_ban',
			'label' => __( 'Time ban enabled', 'botblocker-security' ),
			'type'  => 'core',
		),
		array(
			'key'   => 'time_ban_2',
			'label' => __( 'Secondary time ban enabled', 'botblocker-security' ),
			'type'  => 'core',
		),
		// cloud extended
		array(
			'key'   => 'check',
			'label' => __( 'Cloud validation', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'unresponsive',
			'label' => __( 'Ban unresponsive clients', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'block_vpn_users',
			'label' => __( 'Blocking VPN users', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'block_tor_users',
			'label' => __( 'Blocking Tor users', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'block_override',
			'label' => __( 'Override detection', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'block_web_engine_options',
			'label' => __( 'Engine parameter checks', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		array(
			'key'   => 'block_device_options',
			'label' => __( 'Device API verification', 'botblocker-security' ),
			'type'  => BBCS_CLOUD_TYPE_EXTENDED,
		),
		// neutral
		array(
			'key'   => 'utm_referrer',
			'label' => __( 'UTM referrer processing', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'noarchive',
			'label' => __( 'X-Robots-Tag: noarchive for blocked pages', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'utm_noindex',
			'label' => __( 'X-Robots-Tag: noindex on UTM pages', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'get_browser_type',
			'label' => __( 'Collect browser type', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'get_os_type',
			'label' => __( 'Collect OS type', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'get_device_type',
			'label' => __( 'Collect device type', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'daylight_saving_time',
			'label' => __( 'Daylight saving time adjustment', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'block_incorrect_lang_users',
			'label' => __( 'Geo IP / Language mismatch filtering', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'autosave_admin_ip',
			'label' => __( 'Auto-save administrator IPs', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'email_notifications',
			'label' => __( 'Email notifications', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'telegram_notifications',
			'label' => __( 'Telegram notifications', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		array(
			'key'   => 'pusher_notifications',
			'label' => __( 'Pusher notifications', 'botblocker-security' ),
			'type'  => 'neutral',
		),
		// negative
		array(
			'key'   => 'allow_self_ip_req',
			'label' => __( 'Allow requests from server IP', 'botblocker-security' ),
			'type'  => 'negative',
		),
	);

	$isEnabled = function ( $key ) use ( $settings, $recaptchaReady ) {
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
	};

	$itemsHtml = array();
	foreach ( $rawItems as $it ) {
		$enabled = $isEnabled( $it['key'] );
		switch ( $it['type'] ) {
			case 'negative':
				if ( $enabled ) {
					$icon   = '<i class="fa-regular fa-circle-xmark"></i>';
					$cls    = 'text-warning';
					$suffix = ' (' . esc_html__( 'may reduce protection', 'botblocker-security' ) . ')';
				} else {
					$icon   = '<i class="fa-regular fa-circle-check"></i>';
					$cls    = 'text-success';
					$suffix = '';
				}
				break;
			case 'neutral':
				$icon   = $enabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
				$cls    = 'text-muted';
				$suffix = $enabled ? '' : ' (' . esc_html__( 'disabled', 'botblocker-security' ) . ')';
				break;
			default: // core/cloud_extended
				$icon   = $enabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
				$cls    = $enabled ? 'text-success' : 'text-danger';
				$suffix = $enabled ? '' : ' (' . esc_html__( 'disabled', 'botblocker-security' ) . ')';
		}
		$itemsHtml[] = '<span class="bbcs-health-list-item ' . esc_attr( $cls ) . '">' . $icon . ' ' . esc_html( $it['label'] ) . $suffix . '</span>';
	}

	$total     = count( $itemsHtml );
	$perColumn = max( 1, (int) ceil( $total / $cols ) );
	$columns   = array();
	for ( $i = 0; $i < $cols; $i++ ) {
		$slice = array_slice( $itemsHtml, $i * $perColumn, $perColumn );
		if ( ! $slice ) {
			break;
		}
		$columns[] = '<div class="bbcs-health-col">' . implode( '', $slice ) . '</div>';
	}

	$html  = '<div class="bbcs-health-full">';
	$html .= implode( '', $columns );
	$html .= '</div>';

	if ( $cacheEnabled ) {
		set_transient( $cacheKey, $html, isset( $settings->cache_ui_duration ) ? (int) $settings->cache_ui_duration : 300 );
	}

	return $html;
}
add_shortcode( 'bbcs_health_full', 'bbcs_render_health_full_shortcode' );
