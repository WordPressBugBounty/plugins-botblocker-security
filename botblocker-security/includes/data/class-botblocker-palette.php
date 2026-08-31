<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerPalette {

	/**
	 * Collects addon palette actions from all installed addons' ui.palette manifest data,
	 * merged with promo entries for addons that are not yet installed.
	 *
	 * @return array<int, array{ic: string, t: string, go: string, tab?: string, addon?: string, pro: bool}>
	 */
	public static function getAddonPaletteActions(): array {
		$actions    = array();
		$installed  = array();

		if ( class_exists( 'BotBlockerAddons' ) ) {
			$scanned = BotBlockerAddons::scanAll();
			$active  = BotBlockerAddons::getActive();

			foreach ( $scanned as $slug => $addon ) {
				$palette = $addon['ui']['palette'] ?? null;
				if ( ! $palette || empty( $palette['icon'] ) ) {
					continue;
				}

				$installed[] = $slug;

				$is_active = in_array( $slug, $active, true )
					&& ! empty( $addon['valid'] )
					&& BotBlockerAddons::isCompatible( $addon );

				$action = array(
					'ic'  => $palette['icon'],
					't'   => $palette['title'],
					'go'  => 'addons',
					'pro' => true,
				);

				if ( $is_active ) {
					$action['tab'] = $slug;
				} else {
					$action['addon'] = $slug;
				}

				$actions[] = $action;
			}
		}

		foreach ( self::getAddonPromoActions() as $promo ) {
			if ( in_array( $promo['addon'], $installed, true ) ) {
				continue;
			}
			$actions[] = $promo;
		}

		usort( $actions, function ( $a, $b ) {
			return ( $a['t'] ?? '' ) <=> ( $b['t'] ?? '' );
		} );

		return apply_filters( 'bbcs_palette_addon_actions', $actions );
	}

	/**
	 * Returns promo palette entries for addons that are not yet installed.
	 * Each entry drives an install prompt in the command palette.
	 *
	 * @return array<int, array{ic: string, t: string, go: string, addon: string, pro: bool}>
	 */
	public static function getAddonPromoActions(): array {
		$promos = array(
			array( 'ic' => 'speed',   't' => __( 'Early Init - before WordPress loads', 'botblocker-security' ),                   'go' => 'addons', 'addon' => 'bbcs-early-init', 'pro' => true ),
			array( 'ic' => 'shield',  't' => __( 'Security headers - HSTS, CSP, permissions policy', 'botblocker-security' ),      'go' => 'addons', 'addon' => 'bbcs-security-headers', 'pro' => true ),
			array( 'ic' => 'secret',  't' => __( 'Hide admin URL - custom login page', 'botblocker-security' ),                    'go' => 'addons', 'addon' => 'bbcs-hide-admin', 'pro' => true ),
			array( 'ic' => 'speed',   't' => __( 'Speed up - performance, PageSpeed', 'botblocker-security' ),                     'go' => 'addons', 'addon' => 'bbcs-speedup', 'pro' => true ),
			array( 'ic' => 'scan',    't' => __( 'Run malware scan - virus, infected files', 'botblocker-security' ),               'go' => 'addons', 'addon' => 'bbcs-malware', 'pro' => true ),
			array( 'ic' => 'https',   't' => __( 'HTTPS enforcement - redirect, mixed content fixer', 'botblocker-security' ),      'go' => 'addons', 'addon' => 'bbcs-https-protocol', 'pro' => true ),
			array( 'ic' => 'gauge',   't' => __( 'Behavioral analysis - smart spam scoring', 'botblocker-security' ),               'go' => 'addons', 'addon' => 'bbcs-behavior', 'pro' => true ),
			array( 'ic' => 'cookie2', 't' => __( 'Cookie consent alert - GDPR, privacy notice', 'botblocker-security' ),             'go' => 'addons', 'addon' => 'bbcs-cookie-alert', 'pro' => true ),
			array( 'ic' => 'scan',    't' => __( 'Run truth source scan - file integrity', 'botblocker-security' ),                   'go' => 'addons', 'addon' => 'bbcs-truth-source', 'pro' => true ),
			array( 'ic' => 'plug',    't' => __( 'XML-RPC tunnel - secure remote access, block exploits', 'botblocker-security' ),     'go' => 'addons', 'addon' => 'bbcs-xmlrpc-tunnel', 'pro' => true ),
		);
		return apply_filters( 'bbcs_palette_addon_promo_actions', $promos );
	}

	/**
	 * Generates the command palette data (ACTIONS, GROUPS, SECTIONS) for bbcs-multipage.js.
	 *
	 * All user-facing description strings use __() for translation.
	 * Injected via wp_add_inline_script on the multipage-js handle.
	 *
	 * GROUPS are automatically derived from BotBlockerSnav::getGlobalSearchIndex()
	 * so every setting is instantly searchable via ⌘K.
	 * Groups that route to 'addons' (PRO add-on settings) are excluded
	 * to keep the palette focused on core settings.
	 *
	 * @return array{actions: array, groups: array, sections: array, addLabels: array<string, string>}
	 */
	public static function getPaletteData(): array {
		$global_index = class_exists( 'BotBlockerSnav' ) ? BotBlockerSnav::getGlobalSearchIndex() : array();
		$auto_groups = array();

		foreach ( $global_index as $top_group ) {
			if ( empty( $top_group['tabs'] ) || ! is_array( $top_group['tabs'] ) ) {
				continue;
			}
			foreach ( $top_group['tabs'] as $tab ) {
				$tab_id  = $tab['tab'] ?? '';
				$go_slug = $tab['go'] ?? ( $top_group['go'] ?? '' );
				if ( empty( $go_slug ) ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( sprintf( 'BotBlocker Global Index: Missing required "go" property on tab "%s".', $tab_id ) );
					}
					continue;
				}

				if ( $go_slug === 'addons' ) {
					continue;
				}

				$ch = array();
				if ( ! empty( $tab['sg'] ) && is_array( $tab['sg'] ) ) {
					foreach ( $tab['sg'] as $sg ) {
						if ( ! empty( $sg['s'] ) && is_array( $sg['s'] ) ) {
							foreach ( $sg['s'] as $setting_item ) {
								$ch[] = $setting_item; // Tuple [ label, key ]
							}
						}
					}
				}

				$auto_groups[] = array(
					'ic'  => $top_group['ic'] ?? 'gear',
					't'   => $tab['t'],
					'go'  => $go_slug,
					'tab' => $tab_id,
					'ch'  => $ch,
				);
			}
		}

		return array(
			'actions' => array_merge(
				array(
					array( 'ic' => 'bolt',        't' => __( 'Run setup wizard - guide, onboarding, first steps', 'botblocker-security' ),           'go' => 'setup_wizard' ),
					array( 'ic' => 'home',        't' => __( 'Open dashboard - protection status, overview, main page', 'botblocker-security' ),      'go' => 'home' ),
					array( 'ic' => 'bot',         't' => __( 'Configure In-App Browser Mode - Instagram, LinkedIn, WhatsApp rescue', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'in-app' ),
					array( 'ic' => 'chart',       't' => __( 'Open reports & log - traffic, blocked, threats, visitor history', 'botblocker-security' ),              'go' => 'log' ),
					array( 'ic' => 'ddos',        't' => __( 'Configure DDoS resilience - attack mitigation, flood protection', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'advanced-protection' ),
					array( 'ic' => 'gauge',       't' => __( 'Configure rate limiting - spam, flood, throttle, behavioral', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'rate_limiting' ),
					array( 'ic' => 'bruteforce',  't' => __( 'Configure login brute-force protection - wp-login, lockout', 'botblocker-security' ),  'go' => 'settings', 'tab' => 'brute-force' ),
					array( 'ic' => 'payment',     't' => __( 'Configure payment gateways - Stripe, PayPal, checkout bypass', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'payment' ),
					array( 'ic' => '2fa',         't' => __( 'Set up two-factor authentication - 2FA, MFA, OTP, recovery', 'botblocker-security' ),  'go' => 'integrations', 'tab' => 'bbcs-2fa' ),
					array( 'ic' => 'captcha',     't' => __( 'Configure Captcha - human verification, challenge, reCaptcha', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'captcha' ),
					array( 'ic' => 'bot',         't' => __( 'Configure simple bot detection - crawler, scraper, user-agent', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'simple-detection' ),
					array( 'ic' => 'crown',       't' => __( 'Upgrade to PRO - premium, pricing, license, features', 'botblocker-security' ),        'go' => 'pro',  'pro' => true ),
					array( 'ic' => 'key',         't' => __( 'Get API key from cloud - token, license, activate', 'botblocker-security' ),           'go' => 'integrations', 'tab' => 'cloud' ),
					array( 'ic' => 'broom',       't' => __( 'Clear visitor cookies - reset tracking, privacy data', 'botblocker-security' ),        'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'memory',      't' => __( 'Clear object cache - flush, redis, memcached, purge', 'botblocker-security' ),         'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'link',        't' => __( 'Flush rewrite rules - URLs, permalinks, refresh', 'botblocker-security' ),             'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'ban',         't' => __( 'Block an IP address - ban, deny, blacklist', 'botblocker-security' ),                  'go' => 'rules',        'tab' => 'IPv4 List' ),
					array( 'ic' => 'flag',        't' => __( 'Add a white bot - allowlist, trusted, search engine', 'botblocker-security' ),         'go' => 'rules',        'tab' => 'Trusted Bots' ),
					array( 'ic' => 'upload',      't' => __( 'Import IP whitelist from file - allowlist, CSV, bulk add', 'botblocker-security' ),    'go' => 'rules',        'tab' => 'IPv4 List' ),
					array( 'ic' => 'ban',         't' => __( 'Import IP blacklist from file - blocklist, CSV, bulk block', 'botblocker-security' ),  'go' => 'rules',        'tab' => 'IPv4 List' ),
					array( 'ic' => 'import',      't' => __( 'Import rules from JSON - restore, migrate, transfer', 'botblocker-security' ),         'go' => 'rules',        'tab' => 'Rules' ),
					array( 'ic' => 'export',      't' => __( 'Export rules to JSON - backup, save, download', 'botblocker-security' ),               'go' => 'rules',        'tab' => 'Rules' ),
					array( 'ic' => 'crown',       't' => __( 'Cloud validation - PRO bot verification, AI detection', 'botblocker-security' ),       'go' => 'pro',  'pro' => true ),
					array( 'ic' => 'cloud-download', 't' => __( 'Update RU-Gov list - Russian IP ranges, RKN blocklist', 'botblocker-security' ),    'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'sync',        't' => __( 'Sync LLM providers - AI crawlers, ChatGPT, Claude bot list', 'botblocker-security' ),   'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'database',    't' => __( 'Update ASN database - geo, network, country data', 'botblocker-security' ),            'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'fix',         't' => __( 'Repair and optimize DB - fix tables, cleanup, speed up', 'botblocker-security' ),      'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'broom',       't' => __( 'Clear transients - cache, temporary data, cleanup', 'botblocker-security' ),           'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'broom',       't' => __( 'Clear all visitor data - wipe stats, reset counters', 'botblocker-security' ),         'go' => 'tools',        'tab' => 'Maintenance' ),
					array( 'ic' => 'reinstall',   't' => __( 'Reinstall database - reset, fresh start, rebuild tables', 'botblocker-security' ),     'go' => 'tools',        'tab' => 'Maintenance' ),
				),
				self::getAddonPaletteActions()
			),
			'groups' => $auto_groups,
			'sections' => array(
				array( 'ic' => 'home',        't' => __( 'Dashboard - home, overview, main page', 'botblocker-security' ),           'go' => 'home' ),
				array( 'ic' => 'heart',       't' => __( 'System Status - health, server, diagnostics', 'botblocker-security' ),     'go' => 'status' ),
				array( 'ic' => 'shieldCheck', 't' => __( 'Protection Settings - firewall, blocking, security', 'botblocker-security' ), 'go' => 'settings' ),
				array( 'ic' => 'gear',        't' => __( 'Advanced Settings - expert, fine-tune, deep config', 'botblocker-security' ), 'go' => 'settings' ),
				array( 'ic' => 'clock',       't' => __( 'Cron Tasks - scheduler, jobs, automation', 'botblocker-security' ),        'go' => 'settings' ),
				array( 'ic' => 'list',        't' => __( 'Rules - lists, blacklist, whitelist, IP, GEO', 'botblocker-security' ),    'go' => 'rules' ),
				array( 'ic' => 'plug',        't' => __( 'Integrations - API, services, cloud, connect', 'botblocker-security' ),    'go' => 'integrations' ),
				array( 'ic' => 'lock',        't' => __( 'Two-Factor Auth - 2FA, MFA, OTP, authentication', 'botblocker-security' ), 'go' => 'integrations' ),
				array( 'ic' => 'bot',         't' => __( 'In-App Browser Mode - Instagram, LinkedIn, WhatsApp rescue', 'botblocker-security' ), 'go' => 'settings', 'tab' => 'in-app' ),
				array( 'ic' => 'sliders',     't' => __( 'Tools - maintenance, utilities, repair, cleanup', 'botblocker-security' ), 'go' => 'tools' ),
				array( 'ic' => 'chart',       't' => __( 'Reports - log, analytics, history, visitor stats', 'botblocker-security' ),'go' => 'log' ),
				array( 'ic' => 'puzzle',      't' => __( 'Addons - extensions, plugins, marketplace, more features', 'botblocker-security' ), 'go' => 'addons' ),
				array( 'ic' => 'crown',       't' => __( 'PRO - cloud, upgrade, pricing, license, premium', 'botblocker-security' ), 'go' => 'pro' ),
				array( 'ic' => 'headset',     't' => __( 'About - help, support, docs, contact, information', 'botblocker-security' ), 'go' => 'support' ),
				array( 'ic' => 'star',       't' => __( 'Setup Guide - wizard, onboarding, getting started, first setup', 'botblocker-security' ), 'go' => 'status' ),
			),
			'addLabels' => array(
				'Rules'        => __( 'Add Rule', 'botblocker-security' ),
				'Paths'        => __( 'Add Path', 'botblocker-security' ),
				'Trusted Bots' => __( 'Add Bot', 'botblocker-security' ),
				'IPv4 List'    => __( 'Add IPv4', 'botblocker-security' ),
				'IPv6 List'    => __( 'Add IPv6', 'botblocker-security' ),
				'Proxy'        => __( 'Add Proxy', 'botblocker-security' ),
				'ASN'          => __( 'Add ASN', 'botblocker-security' ),
			),
		);
	}
}
