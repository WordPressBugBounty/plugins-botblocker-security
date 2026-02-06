<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
function bbcs_addons_root_dir() {
    //return trailingslashit(BOTBLOCKER_DIR . 'addons');
    return BOTBLOCKER_ADDONS_DIR;
}

function bbcs_addons_root_url() {
    //return trailingslashit(BOTBLOCKER_URL . 'addons');
    return BOTBLOCKER_ADDONS_URL;
}

function bbcs_scan_addons() {
    $dir = bbcs_addons_root_dir();
    if (!is_dir($dir)) return [];
    $entries = array_diff(scandir($dir), ['.', '..']);
    $addons = [];
    foreach ($entries as $entry) {
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
        $headers = ['Name' => 'Plugin Name', 'Author' => 'Author', 'Description' => 'Description', 'Version' => 'Version'];
        $meta = file_exists($root) ? get_file_data($root, $headers) : ['Name' => '', 'Author' => '', 'Description' => '', 'Version' => ''];
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
        ];
    }
    return $addons;
}

function bbcs_get_active_addons() {
    $active = get_option('botblocker_active_addons', []);
    if (!is_array($active)) $active = [];
    $addons = bbcs_scan_addons();
    $active = array_values(array_filter($active, function($slug) use ($addons) { return isset($addons[$slug]) && $addons[$slug]['valid']; }));
    update_option('botblocker_active_addons', $active);
    return $active;
}

function bbcs_is_addon_active($slug) {
    $slug = sanitize_key($slug);
    return in_array($slug, bbcs_get_active_addons(), true);
}

function bbcs_include_active_addons() {
    $addons = bbcs_scan_addons();
    foreach (bbcs_get_active_addons() as $slug) {
        if (isset($addons[$slug]) && $addons[$slug]['valid']) {
            include_once $addons[$slug]['core'];
        }
    }

    $cloud_api_active = (function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive());
    if (!bbcs_is_addon_active('bbcs-early-init') || !$cloud_api_active) {
        if (function_exists('bbcs_early_init_disable_feature')) {
            bbcs_early_init_disable_feature();
        }
        return;
    } 

    if (function_exists('bbcs_early_init_check_consistency')) {
        bbcs_early_init_check_consistency();
    }
}
