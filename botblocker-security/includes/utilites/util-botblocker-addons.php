<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
function bbcs_addons_root_dir() {
    return bbcs_addons_dir();
}

function bbcs_addons_root_url() {
    return bbcs_addons_url();
}

function bbcs_get_addons_url() {
    if (defined('BOTBLOCKER_MODE') && BOTBLOCKER_MODE === BOTBLOCKER_MODE_DEV) {
        return defined('BOTBLOCKER_ADDONS_DEV') ? BOTBLOCKER_ADDONS_DEV : '';
    }
    return defined('BOTBLOCKER_ADDONS') ? BOTBLOCKER_ADDONS : '';
}

function bbcs_scan_addons() {
    $dir = bbcs_addons_root_dir();
    if (!is_dir($dir)) return [];
    $entries = array_diff(scandir($dir), ['.', '..']);
    $addons = [];
    foreach ($entries as $entry) {
        if (preg_match('/_bbcs_bak$/', $entry)) continue;
        $slug = sanitize_key($entry);
        $base = $dir . $slug . DIRECTORY_SEPARATOR;
        if (!is_dir($base)) continue;
        $root = $base . $slug . '.php';
        $core = $base . 'inc' . DIRECTORY_SEPARATOR . $slug . '-core.php';
        $settings = $base . 'inc' . DIRECTORY_SEPARATOR . $slug . '-settings.php';
        $iconSvg = $base . $slug . '.svg';
        $iconPng = $base . $slug . '.png';
        $readme = $base . 'readme.txt';
        $iconPath = file_exists($iconSvg) ? $iconSvg : (file_exists($iconPng) ? $iconPng : '');
        $iconUrl = $iconPath ? bbcs_addons_root_url() . $slug . '/' . basename($iconPath) : '';
        $valid = file_exists($root) && file_exists($core) && file_exists($settings) && $iconPath && file_exists($readme);
        $headers = ['Name' => 'Plugin Name', 'Author' => 'Author', 'Description' => 'Description', 'Version' => 'Version', 'RequiresCore' => 'Requires-Core'];
        $meta = file_exists($root) ? get_file_data($root, $headers) : ['Name' => '', 'Author' => '', 'Description' => '', 'Version' => '', 'RequiresCore' => ''];
        $addons[$slug] = [
            'slug' => $slug,
            'base' => $base,
            'root' => $root,
            'core' => $core,
            'settings' => $settings,
            'icon' => $iconUrl,
            'valid' => $valid,
            'name' => isset($meta['Name']) ? $meta['Name'] : $slug,
            'author' => isset($meta['Author']) ? $meta['Author'] : '',
            'description' => isset($meta['Description']) ? $meta['Description'] : '',
            'version' => isset($meta['Version']) ? $meta['Version'] : '',
            'requires_core' => isset($meta['RequiresCore']) ? $meta['RequiresCore'] : '',
        ];
    }
    return $addons;
}

function bbcs_get_active_addons() {
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active)) return [];
    return array_values($active);
}

function bbcs_is_addon_active($slug) {
    $slug = sanitize_key($slug);
    return in_array($slug, bbcs_get_active_addons(), true);
}

function bbcs_is_addon_compatible(array $addon, string $core_version = ''): bool {
    if (empty($addon['requires_core'])) return false;
    $version = $core_version !== '' ? $core_version : BOTBLOCKER_VERSION;
    return version_compare($version, $addon['requires_core'], '>=');
}

function bbcs_addon_file_requires_core(string $slug): string {
    $root = trailingslashit(bbcs_addons_dir()) . $slug . DIRECTORY_SEPARATOR . $slug . '.php';
    if (!file_exists($root)) return '';
    $meta = get_file_data($root, ['RequiresCore' => 'Requires-Core']);
    return $meta['RequiresCore'] ?? '';
}

function bbcs_fetch_market_addons(): array {
    $url = bbcs_get_addons_url();
    if (empty($url)) return [];

    $res = wp_remote_get($url, ['timeout' => 10]);
    if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
        $json = json_decode(wp_remote_retrieve_body($res), true);
        if (is_array($json) && isset($json['addons']) && is_array($json['addons'])) {
            return $json['addons'];
        }
    }

    return [];
}

function bbcs_auto_update_addons(string $core_version = ''): array {
    $result = ['updated' => [], 'failed' => []];
    $version = $core_version !== '' ? $core_version : BOTBLOCKER_VERSION;

    $market = bbcs_fetch_market_addons();
    if (empty($market)) return $result;

    $addons = bbcs_scan_addons();
    if (empty($addons)) return $result;

    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!WP_Filesystem()) {
        return $result;
    }

    $marketBySlug = [];
    foreach ($market as $item) {
        if (!empty($item['url'])) {
            $slug = preg_replace('/\.zip$/', '', basename((string) wp_parse_url($item['url'], PHP_URL_PATH)));
            $marketBySlug[$slug] = $item;
        }
    }

    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active)) $active = [];
    $active_changed = false;
    $reactivate = [];

    foreach ($addons as $slug => $addon) {
        if (!isset($marketBySlug[$slug])) continue;
        $remote = $marketBySlug[$slug];
        $remoteVer = $remote['version'] ?? '';
        $localVer  = $addon['version'] ?? '';

        if (!$remoteVer || !$localVer) continue;
        if (!version_compare($remoteVer, $localVer, '>')) continue;

        $url = $remote['url'] ?? '';
        if (empty($url) || !function_exists('bbcs_is_allowed_addon_url') || !bbcs_is_allowed_addon_url($url)) continue;

        if (!empty($remote['requires_core']) && version_compare($version, $remote['requires_core'], '<')) continue;

        // Download BEFORE touching existing files
        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            $result['failed'][] = ['slug' => $slug, 'name' => $addon['name'] ?: $slug, 'error' => $tmp->get_error_message()];
            continue;
        }

        $wasActive = in_array($slug, $active, true);
        if ($wasActive) {
            $active = array_values(array_diff($active, [$slug]));
            $active_changed = true;
        }

        $dest = trailingslashit(bbcs_addons_dir());
        if (!is_dir($dest)) wp_mkdir_p($dest);
        $folder = $dest . $slug;
        $backup = $dest . $slug . '_bbcs_bak';

        // Backup existing folder before replacing
        if (is_dir($backup)) bbcs_rrmdir($backup);
        $backed_up = false;
        if (is_dir($folder)) {
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
            $backed_up = @rename($folder, $backup);
            if (!$backed_up) {
                if (file_exists($tmp)) wp_delete_file($tmp);
                if ($wasActive) { $active[] = $slug; }
                $result['failed'][] = ['slug' => $slug, 'name' => $addon['name'] ?: $slug, 'error' => 'Failed to backup existing add-on'];
                continue;
            }
        }

        $unz = unzip_file($tmp, $dest);
        if (file_exists($tmp)) wp_delete_file($tmp);

        if (is_wp_error($unz)) {
            // Restore backup on unzip failure
            if ($backed_up) {
                if (is_dir($folder)) bbcs_rrmdir($folder);
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
                @rename($backup, $folder);
            }
            if ($wasActive) { $active[] = $slug; }
            $result['failed'][] = ['slug' => $slug, 'name' => $addon['name'] ?: $slug, 'error' => $unz->get_error_message()];
            continue;
        }

        // Verify actual requires_core from extracted files
        $actual_req = bbcs_addon_file_requires_core($slug);
        if (!empty($actual_req) && version_compare($version, $actual_req, '<')) {
            // New version incompatible - restore old version
            if (is_dir($folder)) bbcs_rrmdir($folder);
            if ($backed_up) {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
                @rename($backup, $folder);
            }
            if ($wasActive) { $active[] = $slug; }
            continue;
        }

        // Success - remove backup
        if ($backed_up && is_dir($backup)) {
            bbcs_rrmdir($backup);
        }

        $result['updated'][] = $slug;
        if ($wasActive || empty($addon['requires_core'])) {
            $reactivate[] = $slug;
        }
    }

    if (!empty($reactivate)) {
        $updated_addons = bbcs_scan_addons();
        foreach ($reactivate as $slug) {
            if (isset($updated_addons[$slug]) && $updated_addons[$slug]['valid'] && bbcs_is_addon_compatible($updated_addons[$slug], $version)) {
                $active[] = $slug;
            }
        }
        $active = array_values(array_unique($active));
        $active_changed = true;
    }

    if ($active_changed) {
        update_option('botblocker_active_addons', $active);
    }

    return $result;
}

function bbcs_include_active_addons() {
    $addons = bbcs_scan_addons();
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active)) $active = [];

    $incompatible = [];
    $incompatible_slugs = [];
    $loaded = [];

    foreach ($active as $slug) {
        if (!isset($addons[$slug]) || !$addons[$slug]['valid']) continue;

        if (!bbcs_is_addon_compatible($addons[$slug])) {
            $incompatible[] = [
                'name'          => $addons[$slug]['name'] ?: $slug,
                'requires_core' => $addons[$slug]['requires_core'],
            ];
            $incompatible_slugs[] = $slug;
            continue;
        }

        include_once $addons[$slug]['core'];
        $loaded[] = $slug;
    }

    if (!empty($incompatible)) {
        $new_active = array_values(array_filter($active, function($s) use ($addons) {
            return isset($addons[$s]) && $addons[$s]['valid'] && bbcs_is_addon_compatible($addons[$s]);
        }));
        update_option('botblocker_active_addons', $new_active);
        bbcs_alerts_set_addon_incompatible($incompatible);

        // include early init core for wp-config cleanup
        if (in_array('bbcs-early-init', $incompatible_slugs, true) && isset($addons['bbcs-early-init']['core'])) {
            include_once $addons['bbcs-early-init']['core'];
        }
    }

    $cloud_api_active = (function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive());
    $early_init_loaded = in_array('bbcs-early-init', $loaded, true);

    if ( $early_init_loaded && is_multisite() && get_site_option( 'bbcs_sites_map_dirty' ) ) {
        if ( function_exists( 'bbcs_generateSitesMapFile' ) ) {
            bbcs_generateSitesMapFile( true );
        }
    }

    if (!$early_init_loaded || !$cloud_api_active) {
        if ($early_init_loaded && is_multisite() && function_exists('bbcs_early_init_check_consistency')) {
            bbcs_early_init_check_consistency();
        } elseif (function_exists('bbcs_early_init_disable_feature')) {
            bbcs_early_init_disable_feature();
        }
        return;
    }

    if (function_exists('bbcs_early_init_check_consistency')) {
        bbcs_early_init_check_consistency();
    }

    if (function_exists('bbcs_early_init_maybe_restore_wp_config')) {
        bbcs_early_init_maybe_restore_wp_config();
    }
}
