<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerProFeatures {

	public static function getProFeatures(): array {
		return array(
			__( 'Cloud bot detection (live signatures)', 'botblocker-security' ),
			__( 'Behavioral & AI traffic analysis', 'botblocker-security' ),
			__( 'VPN, Tor and proxy blocking', 'botblocker-security' ),
			__( 'Hide login URL & admin path', 'botblocker-security' ),
			__( 'Security headers management (HSTS, CSP, X-Frame, Permissions-Policy)', 'botblocker-security' ),
			__( 'Early initialization - block before WordPress loads', 'botblocker-security' ),
			__( 'WordPress speed optimizations', 'botblocker-security' ),
			__( 'Threat intelligence & zero-day botnet feeds', 'botblocker-security' ),
			__( 'Daily signature updates (5M+ patterns)', 'botblocker-security' ),
			__( 'Auto-update of PTR and User-Agent databases', 'botblocker-security' ),
			__( 'Advanced reporting, analytics & forensics', 'botblocker-security' ),
			__( 'Custom security rules engine', 'botblocker-security' ),
			__( 'SEO bots whitelist management', 'botblocker-security' ),
			__( 'All premium add-ons included', 'botblocker-security' ),
			__( 'Priority support & emergency help (24h)', 'botblocker-security' ),
		);
	}

	public static function getProComparison(): array {
		return array(
			array(
				'feature' => __( 'Simple bot detection (UA, headers, JS, language)', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'IP, ASN, GEO and proxy rules', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Built-in Captcha', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Logs & basic reports', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'reCaptcha, Redis, Memcached integrations', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Two-Factor Authentication (2FA)', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Cloud bot verification (live)', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Behavioral & AI analysis', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'VPN / Tor / proxy blocking', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Hide login URL & admin path', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Security headers management', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Early initialization (pre-WordPress block)', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'WordPress speed optimizations', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Daily signature updates & threat feeds', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Premium add-ons access', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Advanced analytics & forensics', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Priority support (24h response)', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
		);
	}
}
