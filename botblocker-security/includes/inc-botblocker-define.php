<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'BOTBLOCKER_PLUGIN_NAME', 'BotBlocker Security' ); // The name of the plugin
define( 'BOTBLOCKER_SHORT_NAME', 'BotBlocker' ); // A shorter version of the plugin name
if ( ! defined( 'BOTBLOCKER_TABLE_PREFIX' ) ) {
	define( 'BOTBLOCKER_TABLE_PREFIX', 'bbcs_' ); // The prefix used for database tables
}
define( 'BOTBLOCKER_PREFIX', 'bb_' ); // The prefix for cache keys (Redis/Memcached) and secret-link values

define( 'BOTBLOCKER_VERSION', '1.7.5' );
// The version number of the plugin
define( 'BOTBLOCKER_DB_VERSION', '2.11.0' ); // The database version of the plugin
define( 'BOTBLOCKER_WIZARD_ON_UPDATE', false ); // Show setup wizard after plugin update
define( 'BOTBLOCKER_MODE_STABLE', 'stable' );
define( 'BOTBLOCKER_MODE_DEV', 'dev' );
define( 'BOTBLOCKER_MODE_LOCAL', 'local' );

$bbcs_is_dev = file_exists( BOTBLOCKER_DIR . '.bbcdev' );

if ( ! defined( 'BOTBLOCKER_MODE' ) ) {
	define( 'BOTBLOCKER_MODE', $bbcs_is_dev ? BOTBLOCKER_MODE_DEV : BOTBLOCKER_MODE_STABLE );
}

if ( ! defined( 'BOTBLOCKER_ADDONS_MODE' ) ) {
	$bbcs_addons_mode = BOTBLOCKER_MODE_STABLE;
	$bbcs_addons_marker = BOTBLOCKER_DIR . '.bbcaddons';

	if ( file_exists( $bbcs_addons_marker ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local marker file, read before WP_Filesystem exists.
		$bbcs_marker_raw = strtolower( trim( (string) file_get_contents( $bbcs_addons_marker ) ) );
		if ( in_array( $bbcs_marker_raw, array( BOTBLOCKER_MODE_LOCAL, BOTBLOCKER_MODE_DEV, BOTBLOCKER_MODE_STABLE ), true ) ) {
			$bbcs_addons_mode = $bbcs_marker_raw;
		}
		unset( $bbcs_marker_raw );
	}

	define( 'BOTBLOCKER_ADDONS_MODE', $bbcs_addons_mode );
	unset( $bbcs_addons_marker, $bbcs_addons_mode );
}

// BBCS-MULTISITE: Site-specific values moved to dynamic functions in inc-botblocker-multisite.php:
// BotBlockerMultisite::getCurrentSiteUrl(), BotBlockerMultisite::getCurrentSiteClear(), BotBlockerMultisite::getCurrentSiteName(),
// BotBlockerMultisite::getCurrentSiteEmail(), BotBlockerMultisite::getCurrentUserAgent()

define( 'BOTBLOCKER_EXP_INF', 9999999999 ); // The maximum value for the expires field in the Unix timestamp format
define( 'BOTBLOCKER_UID_MAX_LEN', 64 ); // Max visitor uid length adopted from the main cookie; oversized uids flow into header/cookie names (nginx 502)
define( 'BOTBLOCKER_SELF_CALL_WINDOW', 120 ); // Replay window (seconds) for the X-BotBlocker-Self self-call proof header
define( 'BOTBLOCKER_HONEYPOT_NAME_WINDOW', 60 ); // Accepted staleness (seconds) for a filled honeypot field name rendered by a non-JS client

define( 'BBCS_GLOBAL_LANGUAGES', array( 'en', 'es', 'fr', 'zh', 'ar', 'pt' ) );

if ( ! defined( 'BOTBLOCKER_EMPTY' ) ) {
	define( 'BOTBLOCKER_EMPTY', '-' );
}

if ( ! defined( 'BBCS_RULE_ALLOW' ) ) {
	define( 'BBCS_RULE_ALLOW', 'allow' );
}
if ( ! defined( 'BBCS_RULE_BLOCK' ) ) {
	define( 'BBCS_RULE_BLOCK', 'block' );
}
if ( ! defined( 'BBCS_RULE_GRAY' ) ) {
	define( 'BBCS_RULE_GRAY', 'gray' );
}
if ( ! defined( 'BBCS_RULE_DARK' ) ) {
	define( 'BBCS_RULE_DARK', 'dark' );
}
// Internal-only sign: a rule that resolves to "does not apply to this visitor".
// Never stored as a rule; produced by effective_sign() to neutralize DARK for verified visitors.
if ( ! defined( 'BBCS_RULE_NOOP' ) ) {
	define( 'BBCS_RULE_NOOP', 'noop' );
}

if ( ! defined( 'BBCS_LOCAL_RESULT_ERROR' ) ) {
	define( 'BBCS_LOCAL_RESULT_ERROR', 'error' );
}
if ( ! defined( 'BBCS_LOCAL_RESULT_COOKIE' ) ) {
	define( 'BBCS_LOCAL_RESULT_COOKIE', 'cookie' );
}

if ( ! defined( 'BBCS_ADDON_ACTION_ALLOW' ) ) {
	define( 'BBCS_ADDON_ACTION_ALLOW', 'allow' );
}
if ( ! defined( 'BBCS_ADDON_ACTION_BYPASS' ) ) {
	define( 'BBCS_ADDON_ACTION_BYPASS', 'bypass' );
}
if ( ! defined( 'BBCS_ADDON_ACTION_BLOCK' ) ) {
	define( 'BBCS_ADDON_ACTION_BLOCK', 'block' );
}
if ( ! defined( 'BBCS_ADDON_ACTION_CAPTCHA' ) ) {
	define( 'BBCS_ADDON_ACTION_CAPTCHA', 'captcha' );
}
if ( ! defined( 'BBCS_ADDON_ACTION_REDIRECT' ) ) {
	define( 'BBCS_ADDON_ACTION_REDIRECT', 'redirect' );
}
if ( ! defined( 'BBCS_ADDON_ACTION_LOG_ONLY' ) ) {
	define( 'BBCS_ADDON_ACTION_LOG_ONLY', 'log_only' );
}

if ( ! defined( 'BBCS_CLOUD_STATUS_GOOD' ) ) {
	define( 'BBCS_CLOUD_STATUS_GOOD', 'good' );
}
if ( ! defined( 'BBCS_CLOUD_STATUS_BAD' ) ) {
	define( 'BBCS_CLOUD_STATUS_BAD', 'bad' );
}
if ( ! defined( 'BBCS_CLOUD_STATUS_GRAY' ) ) {
	define( 'BBCS_CLOUD_STATUS_GRAY', 'gray' );
}
if ( ! defined( 'BBCS_CLOUD_STATUS_UNKNOWN' ) ) {
	define( 'BBCS_CLOUD_STATUS_UNKNOWN', 'unknown' );
}

if ( ! defined( 'BBCS_CLOUD_TYPE_EXTENDED' ) ) {
	define( 'BBCS_CLOUD_TYPE_EXTENDED', 'cloud_extended' );
}
if ( ! defined( 'BBCS_CLOUD_TYPE_BASIC' ) ) {
	define( 'BBCS_CLOUD_TYPE_BASIC', 'cloud_basic' );
}

if ( ! defined( 'BBCS_CLOUD_TIER_PREMIUM' ) ) {
	define( 'BBCS_CLOUD_TIER_PREMIUM', 'premium' );
}
if ( ! defined( 'BBCS_CLOUD_TIER_PRO' ) ) {
	define( 'BBCS_CLOUD_TIER_PRO', 'pro' );
}
if ( ! defined( 'BBCS_CLOUD_TIER_ULTIMATE' ) ) {
	define( 'BBCS_CLOUD_TIER_ULTIMATE', 'ultimate' );
}

if ( ! defined( 'BBCS_IP_TYPE_IP' ) ) {
	define( 'BBCS_IP_TYPE_IP', 'ip' );
}
if ( ! defined( 'BBCS_IP_TYPE_CIDR' ) ) {
	define( 'BBCS_IP_TYPE_CIDR', 'cidr' );
}
if ( ! defined( 'BBCS_IP_TYPE_INVALID' ) ) {
	define( 'BBCS_IP_TYPE_INVALID', 'invalid' );
}
define( 'BOTBLOCKER_WP_CRON_ENABLED', defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? false : true );
define( 'BOTBLOCKER_CRON_SCHEDULE', '*/10 * * * *' ); // The cron schedule for the plugin, default is every 10 minutes

if ( ! defined( 'BBCS_CRON_MODE_NONE' ) ) {
	define( 'BBCS_CRON_MODE_NONE', 'none' );
}
if ( ! defined( 'BBCS_CRON_MODE_SPAWN' ) ) {
	define( 'BBCS_CRON_MODE_SPAWN', 'spawn' );
}
if ( ! defined( 'BBCS_CRON_MODE_FALLBACK' ) ) {
	define( 'BBCS_CRON_MODE_FALLBACK', 'fallback' );
}

// BBCS-MULTISITE: User agent moved to BotBlockerMultisite::getCurrentUserAgent() in inc-botblocker-multisite.php

define( 'BOTBLOCKER_SERVER', 'botblocker.top' ); //TODO Parent server botblocker.top
define( 'BOTBLOCKER_CLOUD_API_ENDPOINT', 'https://' . BOTBLOCKER_SERVER . '/botblocker_cloud_api' );
define( 'BOTBLOCKER_FEED_URL', 'https://' . BOTBLOCKER_SERVER . '/?feed=rss2' ); // The URL of the BotBlocker feed
define( 'BOTBLOCKER_API_URL', 'https://api.' . BOTBLOCKER_SERVER . '/v2' ); // The URL of the BotBlocker API
define( 'BOTBLOCKER_NEWS_URL', 'https://' . BOTBLOCKER_SERVER . '/blog' ); // The URL of the BotBlocker BLOG
define( 'BOTBLOCKER_DOCS_URL', 'https://' . BOTBLOCKER_SERVER ); // The URL of the BotBlocker DOCS /docs/ deprecated
define( 'BOTBLOCKER_PRICE_URL', 'https://' . BOTBLOCKER_SERVER . '/botblocker_get_products/' ); // The URL of the BotBlocker products
define( 'BOTBLOCKER_ADDONS', 'https://' . BOTBLOCKER_SERVER . '/wp-content/plugins/bbcs-addons/master.json' ); // The URL of the BotBlocker addons (stable)
define( 'BOTBLOCKER_ADDONS_DEV', 'https://' . BOTBLOCKER_SERVER . '/wp-content/plugins/bbcs-addons/dev/master.json' ); // The URL of the BotBlocker addons (dev)
define( 'BOTBLOCKER_MATERIALS_URL', 'https://' . BOTBLOCKER_SERVER . '/wp-content/plugins/bbcs-materials/' ); // The URL of the BotBlocker materials
define( 'BOTBLOCKER_JS_ADMIN', true ); // Use JS in the admin panel (only for debug)

define( 'BOTBLOCKER_RESERVE_SERVER', 'globus.studio' ); // Reserve server
define( 'BOTBLOCKER_API_GS_URL', 'https://api.' . BOTBLOCKER_RESERVE_SERVER . '/v2' ); // The URL of the Globus Studio API
define( 'BOTBLOCKER_BASE_UPDATE', 'https://api.' . BOTBLOCKER_RESERVE_SERVER . '/info/botiptoday' ); // The URL of the base updates
define( 'BOTBLOCKER_BASE_TOTAL', 'https://api.' . BOTBLOCKER_RESERVE_SERVER . '/info/botiptotal' ); // The URL of the base updates

define( 'BOTBLOCKER_PARENT_IPS_URL', 'https://api.' . BOTBLOCKER_SERVER . '/pool' );
define( 'BOTBLOCKER_PARENT_IPS_GS_URL', 'https://api.' . BOTBLOCKER_RESERVE_SERVER . '/pool' );

define( 'BOTBLOCKER_API_GS_IPV6', BOTBLOCKER_API_GS_URL . '/ip?v=6&format=json' ); // The URL of the Globus Studio API for IPv6 addresses
define( 'BOTBLOCKER_API_IPV6', BOTBLOCKER_API_URL . '/ip?v=6&format=json' ); // The URL of the BotBlocker API for IPv6 addresses

define( 'BOTBLOCKER_CLOUD_RECORDS_BATCH', 1000 ); // The number of records in a single batch for cloud requests
define( 'BOTBLOCKER_CLOUD_REQUEST_SIZE', 5 ); // The number of batches to send in a single request to the cloud
define( 'BOTBLOCKER_DISPLAY_NEWS', false ); // Enable or disable News sidebar and dashboard widget
define( 'BOTBLOCKER_CACHE_NEWS', true ); // Cache news, bot stats, prices
define( 'BOTBLOCKER_CACHE_NEWS_TIME', 21600 ); // 6 hours in seconds
define( 'BOTBLOCKER_CACHE_SIDEBAR_STATS', true ); // Cache widgets stats
define( 'BOTBLOCKER_CACHE_SIDEBAR_STATS_TIME', 7200 ); // 2 hours in seconds
define( 'BOTBLOCKER_CACHE_REMAINING_HITS_TIME', DAY_IN_SECONDS ); // Cache remaining hits for 1 day
define( 'BOTBLOCKER_CACHE_REMAINING_DAYS_TIME', DAY_IN_SECONDS ); // Cache remaining days for 1 day

define( 'BOTBLOCKER_WIDGETS', true ); // A constant to indicate that the plugin includes dashboard widgets

define( 'BOTBLOCKER_CAPTCHA_MODE_BUTTON', 0 );              // Button - "I am not a robot"
define( 'BOTBLOCKER_CAPTCHA_MODE_COLOR_BUTTONS', 1 );       // Color Buttons
define( 'BOTBLOCKER_CAPTCHA_MODE_IMAGE', 2 );               // BotBlocker Image Captcha
define( 'BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2_BUTTON', 3 ); // reCAPTCHA v2 - "I am not a robot"
define( 'BOTBLOCKER_CAPTCHA_MODE_RECAPTCHA_V2', 4 );        // reCAPTCHA v2
define( 'BOTBLOCKER_CAPTCHA_MODE_SHAPE', 5 );               // Dynamic Shape Captcha
define( 'BOTBLOCKER_CAPTCHA_MODE_DIGIT', 6 );               // Dynamic Digit Captcha
define( 'BOTBLOCKER_CAPTCHA_MODE_HOLD', 7 );                // Hold Button Captcha
define( 'BOTBLOCKER_CAPTCHA_MODE_DEFAULT', 8 );             // Silent Auto-Verify (no user interaction)
define( 'BOTBLOCKER_CAPTCHA_MODE_SILENT', 8 );              // Silent Auto-Verify (no user interaction)

define( 'BOTBLOCKER_ENVATO_URL', 'https://codecanyon.net/item/botblocker/99999999' );                 // The URL of the Envato page for BotBlocker
define( 'BOTBLOCKER_WORDPRESS_URL', 'https://wordpress.org/plugins/botblocker-security/' );           // The URL of the WordPress page for BotBlocker
define( 'BOTBLOCKER_MAILTO_LINK', 'mailto:admin@botblocker.top' );                                    // The Link for email of support
define( 'BOTBLOCKER_TELEGRAM_SUPPORT', 'https://t.me/GLOBUSstudio' );                                 // Support in Telegram
define( 'BOTBLOCKER_SUPPORT_FORUM', 'https://wordpress.org/support/plugin/botblocker-security/' );    // Support Forum in wordpress.org

// -- Debug / Diagnostics --------------------------------------
// All debug flags below are OFF in production builds.
// Enable when troubleshooting: CAPTCHA failures, request logs,
// block reasons, cache operations, or fatal error recovery.

// Append CAPTCHA diagnostic codes to ban comments (TD, TT, DM, HM, RM, NM)
define( 'BBCS_CAPTCHA_DIAG', $bbcs_is_dev );

// Master debug switch — enables error_log() calls across the plugin
define( 'BBCS_DEBUG', $bbcs_is_dev );
// Log cache operations to error_log (requires BBCS_DEBUG = true)
define( 'BBCS_CACHE_DEBUG', $bbcs_is_dev );
// Show raw termination data before wp_die() — extreme debug only
define( 'BBCS_DIE_MESSAGE', $bbcs_is_dev );
// Expose internal block reason on the block page — testing only, NOT for production
define( 'BBCS_BLOCK_REASON_VIEW', $bbcs_is_dev );
// Emergency recovery: force-render page even on fatal errors (hive mode)
define( 'BBCS_FATAL_ERROR_HIVE', $bbcs_is_dev );
// Write errors to wp-content/debug.log (requires BBCS_DEBUG = true)
define( 'BBCS_LOG_TO_DEBUG', $bbcs_is_dev );
// Halt execution on fatal errors (off = graceful recovery attempt)
define( 'BBCS_ERROR_EXIT', false );

define(
	'BBCS_STOP_DIRECT',
	'<?php
// If this file is called directly, abort.
if (!defined(\'ABSPATH\') || (!defined(\'WPINC\') && !defined(\'BOTBLOCKER\'))) {
    exit;
}'
);

define( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS', true );

// =====================================================================
// Visitor IP / Country / ASN / Hosting resolution pipeline
// =====================================================================
// BOTBLOCKER_VISITOR_IP_SOURCE controls how country/ASN/hosting data is
// resolved for each visitor.
//
//   'local'  - New pipeline (default). Order:
//                1) local MMDB (asn_database.mmdb) -> country + asn + as_name
//                2) SxGeo (Sypex Geo)              -> country only
//                                                    (used while MMDB is
//                                                     missing or downloading)
//                3a) BOTBLOCKER_VISITOR_IP_STRICT = true  -> stop here.
//                                                    No outbound visitor
//                                                    lookups at all.
//                3b) BOTBLOCKER_VISITOR_IP_STRICT = false -> continue:
//                4) cloud (PRO only fills 'hosting'; basic+extended may
//                                                    fill country/asn)
//                5) ip2c HTTP fallback (IPv4 only; ip2c.org does not
//                    support IPv6 - returns "WRONG INPUT" for v6)
//
//   'cloud'  - Legacy behavior. Cloud first (basic + extended), then SxGeo
//              + ip2c if cloud failed. Use this to fully revert to the
//              pre-MMDB pipeline.
//
// BOTBLOCKER_VISITOR_IP_STRICT (bool, default false):
//   true   - hard cutoff after the local lookup (steps 1-2). No cloud,
//            no ip2c. Use on isolated networks or to eliminate any
//            outbound visitor-resolution traffic.
//   false  - allow cloud (PRO) and ip2c fallbacks per the order above.
//
// Hosting detection ('hosting_block' setting) is PRO-only and is sourced
// exclusively from the cloud_extended response. The local MMDB does not
// expose a hosting flag.
// =====================================================================
if ( ! defined( 'BOTBLOCKER_VISITOR_IP_SOURCE' ) ) {
	define( 'BOTBLOCKER_VISITOR_IP_SOURCE', 'local' );
}
if ( ! defined( 'BOTBLOCKER_VISITOR_IP_STRICT' ) ) {
	define( 'BOTBLOCKER_VISITOR_IP_STRICT', false );
}
