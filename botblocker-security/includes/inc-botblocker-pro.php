<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

include_once BOTBLOCKER_DIR . 'includes/cloud/botblocker-cloud-bb.php';
include_once BOTBLOCKER_DIR . 'includes/cloud/botblocker-presets.php';

function bbcs_getCloudSettings()
{
    global $wpdb;

    try {
        $BBCS = BotBlocker::getInstance();
        if ($BBCS && $BBCS->settings) {
            $settings = [
                'cloud_api_type' => $BBCS->settings->cloud_api_type ?? null,
                'cloud_api_key' => $BBCS->settings->cloud_api_key ?? null,
                'cloud_api_secret' => $BBCS->settings->cloud_api_secret ?? null,
                'cloud_api_email' => $BBCS->settings->cloud_api_email ?? null,
                'cloud_api_tier' => $BBCS->settings->cloud_api_tier ?? null,
            ];
            if (array_filter($settings)) {
                return $settings;
            }
        }
    } catch (Exception $e) {}

    $transient_key = 'bbcs_cloud_api_status_transient';
    $cached_settings = get_transient($transient_key);
    if ($cached_settings !== false && is_array($cached_settings)) {
        return $cached_settings;
    }

    $setting_keys = ['cloud_api_type', 'cloud_api_key', 'cloud_api_secret', 'cloud_api_email', 'cloud_api_tier'];
    $settings = [];
    
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    foreach ($setting_keys as $key) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $value = $wpdb->get_var($wpdb->prepare("SELECT value FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", $key));
        $settings[$key] = $value;
    }

    set_transient($transient_key, $settings, 10 * MINUTE_IN_SECONDS);

    return $settings;
}

function bbcs_getCloudSetting($setting_key)
{
    $settings = bbcs_getCloudSettings();
    return $settings[$setting_key] ?? null;
}

function bbcs_getCloudAPIType()
{
    $cloud_api_type = bbcs_getCloudSetting('cloud_api_type');

    if (!empty($cloud_api_type)) {
        return $cloud_api_type;
    } else {
        return 'Unknown';
    }
}

function bbcs_getCloudAPIKey()
{
    $cloud_api_key = bbcs_getCloudSetting('cloud_api_key');

    if (!empty($cloud_api_key)) {
        return $cloud_api_key;
    } else {
        return 'Unknown';
    }
}

function bbcs_getCloudAPISecret()
{
    $cloud_api_secret = bbcs_getCloudSetting('cloud_api_secret');

    if (!empty($cloud_api_secret)) {
        return $cloud_api_secret;
    } else {
        return 'Unknown';
    }
}

function bbcs_getCloudAPIStatus()
{
    $cloud_api_type = bbcs_getCloudAPIType();
    $cloud_api_key  = bbcs_getCloudAPIKey();

    $status = null;

    if ($cloud_api_type === 'cloud_extended') {
        if (preg_match('/^[1M|1B|1S]-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[A-Z0-9]{6}-[0-9a-f]{2}$/', $cloud_api_key)) {
            $status = null;
        } else {
            $status = 'cloud_extended';
        }
    } elseif ($cloud_api_type === 'cloud_basic') {
        if (preg_match('/^[1M|1B|1S]-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[A-Z0-9]{6}-[0-9a-f]{2}$/', $cloud_api_key)) {
            $status = 'cloud_basic';
        } else {
            $status = null;
        }
    }

    return $status;
}

function bbcs_is_valid_cloud_api_tier($tier)
{
    return in_array($tier, ['premium', 'pro', 'ultimate'], true);
}

function bbcs_getCloudAPITier()
{
    $tier = bbcs_getCloudSetting('cloud_api_tier');
    if (!empty($tier) && bbcs_is_valid_cloud_api_tier($tier)) {
        return $tier;
    }
    return '';
}

function bbcs_isCloudAPIUltimate()
{
    return bbcs_isCloudAPIActive() && bbcs_getCloudAPITier() === 'ultimate';
}

function bbcs_get_remaining_days()
{
    return get_transient('bbcs_remaining_days');
}

function bbcs_set_remaining_days($days)
{
    return set_transient('bbcs_remaining_days', $days, BOTBLOCKER_CACHE_REMAINING_HITS_TIME);
}

function bbcs_get_remaining_hits()
{
    return get_transient('bbcs_remaining_hits');
}

function bbcs_set_remaining_hits($hits)
{
    return set_transient('bbcs_remaining_hits', $hits, BOTBLOCKER_CACHE_REMAINING_HITS_TIME);
}

function bbcs_check_cloud_api_expiry()
{
    $remaining_days = bbcs_get_remaining_days();
    $remaining_hits = bbcs_get_remaining_hits();

    if ($remaining_days === false || $remaining_hits === false) {
        return;
    }

    $remaining_days = (int)$remaining_days;
    $remaining_hits = (int)$remaining_hits;

    $expired = false;
    $warnings = [];
    foreach (bbcs_get_cloud_api_expiry_warning_days() as $threshold) {
        if ($remaining_days == $threshold) {
            bbcs_alerts_set_cloud_api_expired($threshold == 0 ? null : $threshold);
            $expired = $threshold == 0;
            /* translators: %d: number of days until the cloud API expires. */
            $warnings[] = $expired ? __('Your cloud API has expired.', 'botblocker-security') : sprintf(__('Your cloud API will expire in %d days.', 'botblocker-security'), $threshold);
            break;
        }
    }
    foreach (bbcs_get_cloud_api_hits_warning_days() as $threshold) {
        if ($remaining_hits <= $threshold) {
            bbcs_alerts_set_cloud_api_hits_exhausted($threshold == 0 ? null : $threshold);
            if (! $expired) {
                $expired = $threshold == 0;
                /* translators: %d: number of hits remaining before the cloud API is exhausted. */
                $warnings[] = $expired ? __('Your cloud API has no hits remaining.', 'botblocker-security') : sprintf(__('Your cloud API has less than %d hits remaining.', 'botblocker-security'), $threshold);
                break;
            }
        }
    }
    if (!empty($warnings)) {
        bbcs_send_expiration_email(implode(' ', $warnings), $expired);
    }
}

function bbcs_get_cloud_api_hits_warning_days()
{
    return [0, 100, 1000];
}

function bbcs_get_cloud_api_expiry_warning_days()
{
    return [0, 1, 3, 7];
}
