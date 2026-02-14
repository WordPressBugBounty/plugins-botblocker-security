<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_check_empty_UA($useragent) 
{
    $useragent = trim($useragent);
    if ($useragent === null || $useragent === '' || $useragent === BOTBLOCKER_EMPTY || empty($useragent)) {
        return false;
    }
    if (preg_match('/^[\s\x00-\x1F\x7F]*$/', $useragent)) {
        return false;
    }
    return true;
}

function bbcs_check_request_method($method) 
{
    $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    // TODO  HEAD OPTIONS CONNECT TRACE
    $method = strtoupper(trim($method));

    if ($method === 'OPTIONS') { // WooCommerce wizard (v10.*)
        $referer = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'] ?? ''));
        $nonce = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'] ?? ''));

        if (!empty($referer) && !empty($nonce)) {
            $referer_base = untrailingslashit(explode('?', $referer, 2)[0]);
            $allowed_referer = untrailingslashit(admin_url('admin.php'));
            if ($referer_base === $allowed_referer) {
                return true;
            }
        }
        return false; 
    }
    
    if (in_array($method, $allowed_methods, true)) {
        return true;
    }
    return false;
}

function bbcs_check_empty_language($accept_lang, $lang) 
{
    if (empty($accept_lang) || $accept_lang === null || 
              $accept_lang === '' || $accept_lang === BOTBLOCKER_EMPTY || empty($lang) || $lang === null || $lang === '' || $lang === BOTBLOCKER_EMPTY) {
        return false; 
    }
    if (!preg_match('/^[a-z]{2}$/i', $lang)) {
        return false; 
    }
    return true; 
}

function bbcs_language_to_country_compare($country_code, $lang_code) 
{
    $country_lang_map = bbcs_CountrylanguageMap();
    $country_code = strtoupper($country_code);
    $lang_code = strtolower($lang_code);
    if (isset($country_lang_map[$country_code])) {
        return in_array($lang_code, $country_lang_map[$country_code]);
    }
    return false;
}
