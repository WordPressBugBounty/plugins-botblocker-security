<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('BOTBLOCKER_PLUGIN_NAME', 'Cyber Secure BotBlocker'); // The name of the plugin
define('BOTBLOCKER_SHORT_NAME', 'BotBlocker'); // A shorter version of the plugin name
if(!defined('BOTBLOCKER_TABLE_PREFIX')) define('BOTBLOCKER_TABLE_PREFIX', 'bbcs_'); // The prefix used for database tables
define('BOTBLOCKER_PREFIX', 'bb_'); // The prefix used for settings and options

define('BOTBLOCKER_VERSION', '1.6.17'); // The version number of the plugin
define('BOTBLOCKER_DB_VERSION', '2.5.0'); // The database version of the plugin
define('BOTBLOCKER_WIZARD_ON_UPDATE', false); // Show setup wizard after plugin update
define('BOTBLOCKER_MODE_STABLE', 'stable');
define('BOTBLOCKER_MODE_DEV',    'dev');

// BBCS-MULTISITE: Site-specific values moved to dynamic functions in inc-botblocker-multisite.php:
// bbcs_current_site_url(), bbcs_current_site_clear(), bbcs_current_site_name(),
// bbcs_current_site_email(), bbcs_current_user_agent()

define('BOTBLOCKER_EXP_INF', 9999999999); // The maximum value for the expires field in the Unix timestamp format
if(!defined('BOTBLOCKER_EMPTY')) define('BOTBLOCKER_EMPTY', '-');
define('BOTBLOCKER_WP_CRON_ENABLED', defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? false : true);
define('BOTBLOCKER_CRON_SCHEDULE', '*/10 * * * *'); // The cron schedule for the plugin, default is every 10 minutes

// BBCS-MULTISITE: User agent moved to bbcs_current_user_agent() in inc-botblocker-multisite.php

define('BOTBLOCKER_SERVER', 'botblocker.top'); //TODO Parent server botblocker.top
define('BOTBLOCKER_CLOUD_API_ENDPOINT', 'https://' .BOTBLOCKER_SERVER . '/botblocker_cloud_api');
define('BOTBLOCKER_FEED_URL', 'https://' .BOTBLOCKER_SERVER . '/?feed=rss2'); // The URL of the BotBlocker feed
define('BOTBLOCKER_API_URL', 'https://api.' . BOTBLOCKER_SERVER . '/v2'); // The URL of the BotBlocker API
define('BOTBLOCKER_NEWS_URL', 'https://' . BOTBLOCKER_SERVER . '/blog'); // The URL of the BotBlocker BLOG
define('BOTBLOCKER_DOCS_URL', 'https://' . BOTBLOCKER_SERVER); // The URL of the BotBlocker DOCS /docs/ deprecated
define('BOTBLOCKER_PRICE_URL', 'https://' . BOTBLOCKER_SERVER . '/botblocker_get_products/'); // The URL of the BotBlocker products
define('BOTBLOCKER_ADDONS', 'https://' . BOTBLOCKER_SERVER . '/wp-content/plugins/bbcs-addons/master.json'); // The URL of the BotBlocker addons (stable)
define('BOTBLOCKER_ADDONS_DEV', 'https://' . BOTBLOCKER_SERVER . '/wp-content/plugins/bbcs-addons/dev/master.json'); // The URL of the BotBlocker addons (dev)
define('BOTBLOCKER_MATERIALS_URL', 'https://' . BOTBLOCKER_SERVER . '/wp-content/plugins/bbcs-materials/'); // The URL of the BotBlocker materials
define('BOTBLOCKER_JS_ADMIN', true); // Use JS in the admin panel (only for debug)

define('BOTBLOCKER_RESERVE_SERVER', 'globus.studio'); // Reserve server
define('BOTBLOCKER_API_GS_URL', 'https://api.'.BOTBLOCKER_RESERVE_SERVER.'/v2'); // The URL of the Globus Studio API
define('BOTBLOCKER_BASE_UPDATE', 'https://api.'.BOTBLOCKER_RESERVE_SERVER.'/info/botiptoday'); // The URL of the base updates
define('BOTBLOCKER_BASE_TOTAL', 'https://api.'.BOTBLOCKER_RESERVE_SERVER.'/info/botiptotal'); // The URL of the base updates

define('BOTBLOCKER_PARENT_IPS_URL', 'https://api.'.BOTBLOCKER_RESERVE_SERVER.'/pool'); 

define('BOTBLOCKER_API_GS_IPV6', BOTBLOCKER_API_GS_URL.'/ip?v=6&format=json'); // The URL of the Globus Studio API for IPv6 addresses

define('BOTBLOCKER_CLOUD_RECORDS_BATCH', 1000); // The number of records in a single batch for cloud requests
define('BOTBLOCKER_CLOUD_REQUEST_SIZE', 5); // The number of batches to send in a single request to the cloud
define('BOTBLOCKER_DISPLAY_NEWS', false); // Enable or disable News sidebar and dashboard widget
define('BOTBLOCKER_CACHE_NEWS', true); // Cache news, bot stats, prices
define('BOTBLOCKER_CACHE_NEWS_TIME', 21600); // 6 hours in seconds
define('BOTBLOCKER_CACHE_SIDEBAR_STATS', true); // Cache widgets stats
define('BOTBLOCKER_CACHE_SIDEBAR_STATS_TIME', 7200); // 2 hours in seconds
define('BOTBLOCKER_CACHE_REMAINING_HITS_TIME', DAY_IN_SECONDS); // Cache remaining hits for 1 day
define('BOTBLOCKER_CACHE_REMAINING_DAYS_TIME', DAY_IN_SECONDS); // Cache remaining days for 1 day

define('BOTBLOCKER_WIDGETS', true); // A constant to indicate that the plugin includes dashboard widgets

define('BOTBLOCKER_CAPTCHA_MODE_DEFAULT', 8); // Silent Auto-Verify (no user interaction)
define('BOTBLOCKER_CAPTCHA_MODE_SILENT', 8);  // Silent Auto-Verify (no user interaction)

define('BOTBLOCKER_ENVATO_URL', 'https://codecanyon.net/item/botblocker/99999999'); 				// The URL of the Envato page for BotBlocker
define('BOTBLOCKER_WORDPRESS_URL', 'https://wordpress.org/plugins/botblocker-security/'); 			// The URL of the WordPress page for BotBlocker
define('BOTBLOCKER_MAILTO_LINK', 'mailto:admin@botblocker.top'); 									// The Link for email of support
define('BOTBLOCKER_TELEGRAM_SUPPORT', 'https://t.me/GLOBUSstudio'); 								// Support in Telegram
define('BOTBLOCKER_SUPPORT_FORUM', 'https://wordpress.org/support/plugin/botblocker-security/');	// Support Forum in wordpress.org

// Enable CAPTCHA diagnostic reason codes in ban comments (TD, TT, DM, HM, RM, NM)
define('BBCS_CAPTCHA_DIAG', false);

// USE logger for requests
define('BBCS_DEBUG', false);  
// Enable logging for Redis and Memcached caches (only works when BBCS_DEBUG is true)
define('BBCS_CACHE_DEBUG', false);  
// PRINT HIVE AND STOP if DIE (ONLY FOR DEBUG)              
define('BBCS_DIE_MESSAGE', false);  
// ONLY FOR DEBUG        
define('BBCS_BLOCK_REASON_VIEW', true);    
// ONLY FOR DEBUG  
define('BBCS_FATAL_ERROR_HIVE', true);  
// If true, errors will be logged to debug.log
define('BBCS_LOG_TO_DEBUG', true);
// Stop execution on fatal errors
define('BBCS_ERROR_EXIT', false);
// 
define('BOTBLOCKER_MODE', BOTBLOCKER_MODE_DEV); 

define('BBCS_STOP_DIRECT','<?php
// If this file is called directly, abort.
if (!defined(\'ABSPATH\') || (!defined(\'WPINC\') && !defined(\'BOTBLOCKER\'))) {
    exit;
}');

define('BBCS_SQL_PAGE_NOT_LIKE', 
"(page NOT LIKE '%/wp-admin/%'
AND page NOT LIKE '%/wp-content/%'
AND page NOT LIKE '%/wp-login%'
AND page NOT LIKE '%/wp-includes/%'
AND page NOT LIKE '%/favicon.ico%'
AND page NOT LIKE '%/wp-cron.php%'
AND page NOT LIKE '%/feed/%'
AND page NOT LIKE '%/xmlrpc.php%'
AND page NOT LIKE '%/wp-json/%'
AND page NOT LIKE '%/robots.txt%'
AND page NOT LIKE '%/sitemap%')");

define('BBCS_SQL_PAGE_LIKE_ADMIN', 
"(page LIKE '%/wp-admin/%'
OR page LIKE '%/wp-login%')");

define('BBCS_SQL_PAGE_NOT_LIKE_ADMIN', 
"(page NOT LIKE '%/wp-admin/%'
AND page NOT LIKE '%/wp-login%')");

define('BBCS_SQL_PAGE_LIKE_WP', 
"(page LIKE '%/wp-content/%'
OR page LIKE '%/wp-includes/%'
OR page LIKE '%/favicon.ico%'
OR page LIKE '%/wp-cron.php%'
OR page LIKE '%/feed/%'
OR page LIKE '%/xmlrpc.php%'
OR page LIKE '%/wp-json/%'
OR page LIKE '%/robots.txt%'
OR page LIKE '%/sitemap%')");

define('BBCS_SQL_PAGE_NOT_LIKE_WP', 
"(page NOT LIKE '%/wp-content/%'
AND page NOT LIKE '%/wp-includes/%'
AND page NOT LIKE '%/favicon.ico%'
AND page NOT LIKE '%/wp-cron.php%'
AND page NOT LIKE '%/feed/%'
AND page NOT LIKE '%/xmlrpc.php%'
AND page NOT LIKE '%/wp-json/%'
AND page NOT LIKE '%/robots.txt%'
AND page NOT LIKE '%/sitemap%')");

define('BOTBLOCKER_INTEGRATE_MU_PLUGINS', true);

function bbcs_full_domain_with_underscores($url)
{
    $host = wp_parse_url($url, PHP_URL_HOST);
    return str_replace('.', '_', $host);
}

// Ensure that the HTTP_HOST is set correctly if SERVER_NAME is available
// This is useful in cases where the server does not set HTTP_HOST but does set SERVER_NAME.
if ( empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['SERVER_NAME'] ) ) {
	$bbcs_port = '';
	if (
		! empty( $_SERVER['SERVER_PORT'] )
		&& ! in_array( $_SERVER['SERVER_PORT'], array( '80', '443' ), true )
	) {
		$bbcs_port = ':' . sanitize_text_field(wp_unslash($_SERVER['SERVER_PORT']));
	}

	$_SERVER['HTTP_HOST'] = sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) . $bbcs_port;
}
