<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_alerts_get_all(): array
{
	$alerts = [];

	$failed_alert_err = get_transient('bbcs_cloud_connection_failed_alert');
	if (!empty($failed_alert_err)) {
		$alerts[] = $failed_alert_err;
	}

	$missing_files_alert = get_transient('bbcs_missing_files_alert');
	if (!empty($missing_files_alert)) {
		$alerts[] = $missing_files_alert;
	}

	$cloud_api_expired_alert = get_transient('bbcs_cloud_api_expired_alert');
	if (!empty($cloud_api_expired_alert)) {
		$alerts[] = $cloud_api_expired_alert;
	}

	$cloud_api_hits_exhausted_alert = get_transient('bbcs_cloud_api_hits_exhausted_alert');
	if (!empty($cloud_api_hits_exhausted_alert)) {
		$alerts[] = $cloud_api_hits_exhausted_alert;
	}

	$addon_update_failed_alert = get_transient('bbcs_addon_update_failed_alert');
	if (!empty($addon_update_failed_alert)) {
		$alerts[] = $addon_update_failed_alert;
	}

	$addon_incompatible_alert = get_transient('bbcs_addon_incompatible_alert');
	if (!empty($addon_incompatible_alert)) {
		$alerts[] = $addon_incompatible_alert;
	}

	// Cache plugin compatibility warnings
	$cache_alert = bbcs_alerts_detect_cache_incompatibility();
	if (!empty($cache_alert)) {
		$alerts[] = $cache_alert;
	}

	return $alerts;
}

function bbcs_alerts_set_cloud_connection_failed(): void
{
	$alert = [
		'type'    => 'no_connection_bbcloud',
		'icon'    => 'fas fa-signal bg-success text-light',
		'title'   => __('No connection to BotBlocker Cloud', 'botblocker-security'),
    	'message' => gmdate('d/m/Y'),
	];

	set_transient('bbcs_cloud_connection_failed_alert', $alert, DAY_IN_SECONDS);
}


function bbcs_alerts_set_missing_files(): void
{
	$alert = [
		'type'    => 'missing_files',
		'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
		'title'   => __('Missing Files', 'botblocker-security'),
		'message' => __('Required files missing. Regenerated.', 'botblocker-security')
	];

	set_transient('bbcs_missing_files_alert', $alert, HOUR_IN_SECONDS);
}

function bbcs_alerts_set_cloud_api_expired($days_left = null): void
{
    /* translators: %d: number of days left before the cloud API expires. */
    $about_to_expire_message = __( 'Your cloud API will expire in %d days.', 'botblocker-security');
	$message = $days_left !== null ? sprintf( $about_to_expire_message, intval( $days_left ) ) : __( 'Your cloud API has expired. Please renew it.', 'botblocker-security');
	$alert = [
		'type'    => 'cloud_api_expired',
		'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
		'title'   => __( 'Cloud API Expired', 'botblocker-security'),
		'message' => $message
	];

	set_transient('bbcs_cloud_api_expired_alert', $alert, DAY_IN_SECONDS);
}

function bbcs_alerts_set_cloud_api_hits_exhausted($hits_left = null): void
{
    /* translators: %d: number of hits remaining before the cloud API is exhausted. */
    $low_hits_message = __( 'Your cloud API has fewer than %d hits remaining.', 'botblocker-security');
	$message = $hits_left !== null ? sprintf( $low_hits_message, intval( $hits_left ) ) : __( 'Your cloud API has no hits remaining. Please renew.', 'botblocker-security');
	$alert = [
		'type'    => 'cloud_api_hits_exhausted',
		'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
		'title'   => __( 'Cloud API Hits Exhausted', 'botblocker-security'),
		'message' => $message
	];

	set_transient('bbcs_cloud_api_hits_exhausted_alert', $alert, DAY_IN_SECONDS);
}

/**
 * Detect active cache plugins that may require manual server-level configuration.
 * Returns an alert array if an incompatible mode is detected, or null if OK.
 */
function bbcs_alerts_detect_cache_incompatibility(): ?array
{
	if (!function_exists('is_plugin_active')) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$warnings = [];

	// WP Super Cache - Expert (mod_rewrite) mode bypasses PHP entirely
	if (defined('WPCACHEHOME') || is_plugin_active('wp-super-cache/wp-cache.php') || (is_multisite() && is_plugin_active_for_network('wp-super-cache/wp-cache.php'))) {
		$wpsc_config = defined('WPCACHEHOME') ? rtrim(WPCACHEHOME, '/') . '/wp-cache-config.php' : '';
		$is_mod_rewrite = false;
		if ($wpsc_config && file_exists($wpsc_config)) {
			$config_content = file_get_contents($wpsc_config);
			if ($config_content !== false && preg_match('/\$wp_cache_mod_rewrite\s*=\s*1/', $config_content)) {
				$is_mod_rewrite = true;
			}
		}
		if ($is_mod_rewrite) {
			$warnings[] = __( 'WP Super Cache (Expert/mod_rewrite mode) serves cached pages via .htaccess, bypassing PHP. Add a cookie-based exception. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}
	}

	// W3 Total Cache - Disk Enhanced with rewrite rules
	if (is_plugin_active('w3-total-cache/w3-total-cache.php') || (is_multisite() && is_plugin_active_for_network('w3-total-cache/w3-total-cache.php'))) {
		if (defined('W3TC_DIR')) {
			$warnings[] = __( 'W3 Total Cache detected. If using Disk Enhanced mode with rewrite rules, add a cookie-based server exception. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}
	}

	// Nginx FastCGI / Redis page cache (server-level)
	if (isset($_SERVER['SERVER_SOFTWARE']) && stripos(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])), 'nginx') !== false) {
		if (defined('WP_CACHE') && WP_CACHE) {
			$warnings[] = __( 'Nginx detected with WP_CACHE enabled. If using FastCGI Cache, ensure the BotBlocker cookie bypasses it. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
		}
	}

	// LiteSpeed server with LSCache
	if (is_plugin_active('litespeed-cache/litespeed-cache.php') || (is_multisite() && is_plugin_active_for_network('litespeed-cache/litespeed-cache.php'))) {
		// LSCWP respects X-LiteSpeed-Cache-Control: no-cache - auto-compatible
		// but still worth noting for users
	}

	// WP Rocket
	if (is_plugin_active('wp-rocket/wp-rocket.php') || (is_multisite() && is_plugin_active_for_network('wp-rocket/wp-rocket.php'))) {
		$warnings[] = __( 'WP Rocket detected. Add the BotBlocker cookie to the "Never Cache Cookies" list in WP Rocket settings. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
	}

	// Kinsta server-level cache
	if (defined('KINSTAMU_VERSION')) {
		$warnings[] = __( 'Kinsta hosting detected. Add the BotBlocker cookie as a cache bypass rule in the Kinsta dashboard. See CACHE-COMPATIBILITY.md.', 'botblocker-security' );
	}

	if (empty($warnings)) {
		return null;
	}

	return [
		'type'    => 'cache_compatibility',
		'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
		'title'   => __('Cache Plugin Compatibility', 'botblocker-security'),
		'message' => implode(' | ', $warnings)
	];
}

/**
 * @param array $failed_addons Array of ['slug'=>..., 'name'=>..., 'error'=>...].
 */
function bbcs_alerts_set_addon_update_failed(array $failed_addons): void
{
	$names = array_map(function($f) { return $f['name']; }, $failed_addons);
	$message = sprintf(
		/* translators: %s: comma-separated list of add-on names that failed to update. */
		__('Failed to auto-update add-on(s): %s. Please retry from the Add-ons page.', 'botblocker-security'),
		implode(', ', $names)
	);
	$alert = [
		'type'      => 'addon_update_failed',
		'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
		'title'     => __('Add-on Update Failed', 'botblocker-security'),
		'message'   => $message,
		'link'      => function_exists('bbcs_admin_page_url') ? bbcs_admin_page_url('bbcs_addons') : '',
		'link_text' => __('Go to Add-ons', 'botblocker-security'),
	];

	set_transient('bbcs_addon_update_failed_alert', $alert, DAY_IN_SECONDS);
}

/**
 * @param array $deactivated Array of ['name'=>..., 'requires_core'=>...].
 */
function bbcs_alerts_set_addon_incompatible(array $deactivated): void
{
	$lines = [];
	foreach ($deactivated as $item) {
		if (!empty($item['requires_core'])) {
			/* translators: 1: add-on name, 2: required BotBlocker version. */
			$lines[] = sprintf(__('%1$s (requires >= %2$s)', 'botblocker-security'), $item['name'], $item['requires_core']);
		} else {
			$lines[] = $item['name'];
		}
	}
	$alert = [
		'type'      => 'addon_incompatible',
		'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
		'title'     => __('Add-ons Deactivated', 'botblocker-security'),
		'message'   => sprintf(
			/* translators: %s: comma-separated list of deactivated add-on names with version requirements. */
			__('Incompatible add-ons were deactivated: %s. Please update BotBlocker.', 'botblocker-security'),
			implode(', ', $lines)
		),
		'link'      => function_exists('bbcs_admin_page_url') ? bbcs_admin_page_url('bbcs_addons') : '',
		'link_text' => __('View Add-ons', 'botblocker-security'),
	];

	set_transient('bbcs_addon_incompatible_alert', $alert, DAY_IN_SECONDS);
}
