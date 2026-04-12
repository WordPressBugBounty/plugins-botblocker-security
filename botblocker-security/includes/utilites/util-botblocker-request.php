<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BotBlockerWpRequest {

    public static function send_to_cloud($data, $url, $endpoint = '')
    {
        $base = untrailingslashit($url);
        $path = 'botblocker';
        if (!empty($endpoint)) {
            $path .= '/' . ltrim($endpoint, '/');
        }
        $fullURL = $base . '/' . $path;

        $timeout = 5;
        if (class_exists('BotBlocker') && method_exists('BotBlocker', 'getInstance')) {
            $inst = BotBlocker::getInstance();
            if (isset($inst->settings->cloud_api_timeout)) {
                $timeout = max(1, min(30, (int) $inst->settings->cloud_api_timeout));
            }
        }

        $args = [
            'method'      => 'POST',
            'timeout'     => $timeout,
            'redirection' => 0,
            'httpversion' => '1.1',
            'headers'     => [
                'Content-Type'  => 'application/json; charset=utf-8',
                'Referer'       => bbcs_current_site_url(), //BBCS-MULTISITE
                'User-Agent'    => bbcs_current_user_agent(), //BBCS-MULTISITE
            ],
            'body'        => wp_json_encode($data),
        ];

        $response = wp_remote_post($fullURL, $args);

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if (BBCS_DEBUG === true) {
        //    $formattedParams = $fullURL . '-' . $http_code . "\r\n" . print_r($data, true) . "\r\n" . 'Response:' . "\r\n" . $body . "\r\n\r\n";
        //    error_log($formattedParams);
        }

        if ($http_code === 200 && !empty($body) && self::is_json($body)) {
            delete_transient('bbcs_cloud_connection_failed_alert');
            return json_decode(trim($body), true);
        }

        bbcs_alerts_set_cloud_connection_failed();
        return false;
    }

    public static function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function ip2c($ip)
    {
        $url = 'https://ip2c.org/?ip=' . rawurlencode((string) $ip);
        $args = [
            'method'      => 'GET',
            'timeout'     => 3,
            'redirection' => 0,
            'httpversion' => '1.1',
            'headers'     => [
                'User-Agent' => function_exists('bbcs_current_user_agent') ? bbcs_current_user_agent() : 'BotBlocker/IP2C', //BBCS-MULTISITE
            ],
        ];
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            return false;
        }
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($http_code === 200 && !empty($body)) {
            $reply_ip2c = explode(';', trim($body));
            if (isset($reply_ip2c[0]) && $reply_ip2c[0] === '1' && isset($reply_ip2c[1])) {
                return mb_strtoupper($reply_ip2c[1]);
            }
        }
        return false;
    }
}
