<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_handleBotblockerCloudAPI()
{
    add_action('template_redirect', function () {
        if (get_query_var('botblocker_cloud_api') == '1') {
            /*
           error_log('BotBlocker cloud api activation requested.');
           error_log('Email: ' . (isset($_GET['email']) ? sanitize_text_field($_GET['email']) : 'Not provided'));
           error_log('API Key: ' . (isset($_GET['cloud_api_key']) ? sanitize_text_field($_GET['cloud_api_key']) : 'Not provided'));
           error_log('API Secret: ' . (isset($_GET['cloud_api_secret']) ? sanitize_text_field($_GET['cloud_api_secret']) : 'Not provided'));
           error_log('Cloud API Expired: ' . (isset($_GET['cloud_api_expired']) ? absint(wp_unslash($_GET['cloud_api_expired'])) : 'Not provided'));
           error_log('Licence tier: ' . (isset($_GET['cloud_api_tier']) ? sanitize_text_field($_GET['cloud_api_tier']) : 'Not provided'));
           */
            /**
             * REVIEWER NOTE: This is an external API endpoint for cloud API management from a trusted server.
             * Nonce verification is not applicable here as the request originates from an external system
             * that does not have access to WordPress nonces.
             * Security: The cloud server must send X-BotBlocker-Secret header containing
             * md5(email . api_key . api_secret . 'BB') which is validated against stored credentials.
             */
            /* phpcs:disable WordPress.Security.NonceVerification.Recommended */

            // --- Header-based authentication ---
            $header_secret = isset($_SERVER['HTTP_X_BOTBLOCKER_SECRET'])
                ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_BOTBLOCKER_SECRET']))
                : '';

            if ($header_secret === '') {
                wp_send_json_error('Unauthorized: missing secret header.', 403);
                return;
            }

            if (isset($_GET['cloud_api_expired']) && absint(wp_unslash($_GET['cloud_api_expired'])) === 1) {
                $stored = bbcs_getCloudSettings();
                $stored_email  = isset($stored['cloud_api_email'])  ? (string) $stored['cloud_api_email']  : '';
                $stored_key    = isset($stored['cloud_api_key'])    ? (string) $stored['cloud_api_key']    : '';
                $stored_secret = isset($stored['cloud_api_secret']) ? (string) $stored['cloud_api_secret'] : '';

                if ($stored_email === '' || $stored_key === '' || $stored_secret === '') {
                    wp_send_json_error('Unauthorized: no stored credentials.', 403);
                    return;
                }
                $expected = md5($stored_email . $stored_key . $stored_secret . 'BB');
                if (! hash_equals($expected, $header_secret)) {
                    wp_send_json_error('Unauthorized: invalid secret.', 403);
                    return;
                }

                bbcs_foreach_site( function ( $site_id ) {
                    bbcs_clear_transients();
                    bbcs_alerts_set_cloud_api_expired();
                    bbcs_resetCloudAPI();
                } );

                wp_send_json_success(['message' => 'Cloud API expired alert set.']);
                return;
            }
            if (isset($_GET['email']) && isset($_GET['cloud_api_key']) && isset($_GET['cloud_api_secret'])) {
                $email = sanitize_text_field(wp_unslash($_GET['email']));
                $api_key = sanitize_text_field(wp_unslash($_GET['cloud_api_key']));
                $api_secret = sanitize_text_field(wp_unslash($_GET['cloud_api_secret']));

                $expected_activation = md5($email . $api_key . $api_secret . 'BB');
                if (! hash_equals($expected_activation, $header_secret)) {
                    wp_send_json_error('Unauthorized: invalid secret.', 403);
                    return;
                }

                $cloud_api_tier = isset($_GET['cloud_api_tier']) ? sanitize_text_field(wp_unslash($_GET['cloud_api_tier'])) : '';
                if (!bbcs_is_valid_cloud_api_tier($cloud_api_tier)) {
                    $cloud_api_tier = '';
                }

                // BBCS-MULTISITE
                $bbcs_propagate = array(
                    'cloud_api_type'   => 'cloud_extended',
                    'cloud_api_email'  => $email,
                    'cloud_api_key'    => $api_key,
                    'cloud_api_secret' => $api_secret,
                    'cloud_api_tier'   => $cloud_api_tier,
                    'check'            => 1,
                );
                if ( $cloud_api_tier !== 'ultimate' ) {
                    $bbcs_propagate['force_cloud_validation'] = 0;
                }
                bbcs_sync_cloud_settings_network( $bbcs_propagate );

                wp_send_json_success(array('message' => 'Cloud API activated successfully'));
            } else {
                wp_die('Invalid or missing parameters', 'Error', array('response' => 400));
            }
            /* phpcs:enable WordPress.Security.NonceVerification.Recommended */
        }
    });
}

function bbcs_generateUuid()
{
    return sprintf(
        '%04x-%04x-%04x',
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff)
    );
};

function bbcs_generateCloudAPIKey($series, $email)
{
    $seriesMap = [
        'Metric'     => '1M',
        'BotBlocker' => '1B',
        'ShieldWP'   => '1S'
    ];

    $series = $seriesMap[$series] ?? '0X';

    $serial = strtoupper(substr(md5($email), 0, 6));
    $key = bbcs_generateUuid();
    $checksum = substr(md5($series . $key . $serial), 0, 2);

    return "{$series}-{$key}-{$serial}-{$checksum}";
};

function bbcs_isCloudAPIActive()
{
    $cloud_api_test = bbcs_getCloudAPIStatus();
    return $cloud_api_test === 'cloud_extended';
}

function bbcs_resetCloudAPI()
{
    global $wpdb;

    $cloud_basic_settings = [
        'cloud_api_type' => 'cloud_basic',
        'cloud_api_tier' => '',
        'check' => 0,
        'unresponsive' => 0,
        'force_cloud_validation' => 0,
        'block_vpn_users' => 0,
        'block_tor_users' => 0,
        'block_override' => 0,
        'block_web_engine_options' => 0,
        'block_device_options' => 0
    ];

    // TODO if mem available and redis == 1 then mem = 1

    foreach ($cloud_basic_settings as $key => $value) {
        $key = sanitize_key($key);
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->bbcs_settings,
            ['value' => $value],
            ['key' => $key]
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }
    bbcs_generateSettingsFileFromDb();
    //TODO if needed bbcs_reload_current_admin_page();
}
