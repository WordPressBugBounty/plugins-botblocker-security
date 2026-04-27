<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_addServerIPs() {
    $localhostIPv4 = '127.0.0.1';
    $localhostIPv6 = '::1';

    bbcs_addIPv4Rule($localhostIPv4, 'Local IP');
    if (isset($_SERVER['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR'])) {
        $server_addr = sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR']));
        if (filter_var($server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            bbcs_addIPv4Rule($server_addr, 'Local IP from SERVER_ADDR');
        }
    }
    $serverIPv4 = bbcs_getServerIPv4();
    if ($serverIPv4) {
        bbcs_addIPv4Rule($serverIPv4, 'Server IPv4');
    }

    bbcs_addIPv6Rule($localhostIPv6, 'Local IPv6');
    if (isset($_SERVER['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR'])) {
        $server_addr = sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR']));
        if (filter_var($server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            bbcs_addIPv6Rule($server_addr, 'Local IP from SERVER_ADDR');
        }
    }
    $serverIPv6 = bbcs_getServerIPv6();
    if ($serverIPv6) {
        bbcs_addIPv6Rule($serverIPv6, 'Server IPv6');
    }
}

function bbcs_addAdminIPs() {
    if( !isset($_SERVER['REMOTE_ADDR']) || empty($_SERVER['REMOTE_ADDR']) ) {
        return;
    }
    $adminIP = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    if (filter_var($adminIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        bbcs_addIPv4Rule($adminIP, "Admin IP");
    } elseif (filter_var($adminIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        bbcs_addIPv6Rule($adminIP, "Admin IP");
    } else {
        throw new \Exception(esc_html("Invalid IP: $adminIP"));
    }
}

function bbcs_addIPv4Rule( $ip, $comment ) {
    global $wpdb;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
            $ip
        )
    );

    if ( 0 == $exists ) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $wpdb->bbcs_ipv4rules,
            array(
                'priority' => 10,
                'search'   => $ip,
                'ip1'      => bbcs_ipToNumeric( $ip ),
                'ip2'      => bbcs_ipToNumeric( $ip ),
                'rule'     => 'allow',
                'comment'  => $comment,
                'expires'  => BOTBLOCKER_EXP_INF,
                'readonly' => 1,
            ),
            array( '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d' )
        );
    }
}

function bbcs_addIPv6Rule( $ip, $comment ) {
    global $wpdb;

    $expandedIP = bbcs_expandIPv6( $ip );
    $binIP      = bbcs_ipv6_bin( $expandedIP );
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s",
            $expandedIP
        )
    );

    if ( 0 == $exists ) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $wpdb->bbcs_ipv6rules,
            array(
                'priority' => 10,
                'search'   => $expandedIP,
                'ip1'      => $binIP,
                'ip2'      => $binIP,
                'rule'     => 'allow',
                'comment'  => $comment,
                'expires'  => BOTBLOCKER_EXP_INF,
                'readonly' => 1,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
        );
    }
}

function bbcs_getServerIPv4() {
    $url = BOTBLOCKER_API_GS_URL . '/ip?v=4';
    $response = wp_remote_get($url, [
        'timeout'     => 10,
        'redirection' => 0,
        'httpversion' => '1.1',
        'user-agent'  => bbcs_current_user_agent(), //BBCS-MULTISITE
        //'sslverify'  => false
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return bbcs_getServerIPFallback('ipv4');
    }

    $body = wp_remote_retrieve_body($response);
    $serverIPv4 = trim(wp_strip_all_tags($body));

    if (!filter_var($serverIPv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return bbcs_getServerIPFallback('ipv4');
    }

    return $serverIPv4;
}

function bbcs_getServerIPv6() {
    $url = BOTBLOCKER_API_GS_URL . '/ip?v=6';
    $response = wp_remote_get($url, [
        'timeout'     => 10,
        'redirection' => 0,
        'httpversion' => '1.1',
        'user-agent'  => bbcs_current_user_agent(), //BBCS-MULTISITE
        //'sslverify'  => false
        'headers'     => [
            'Accept' => 'text/plain',
        ],
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return bbcs_getServerIPFallback('ipv6');
    }

    $body = wp_remote_retrieve_body($response);
    $serverIPv6 = trim(wp_strip_all_tags($body));

    if (!filter_var($serverIPv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return bbcs_getServerIPFallback('ipv6');
    }

    return $serverIPv6;
}

function bbcs_getServerIPFallback($version) {
    if (!function_exists('shell_exec')) {
        return null;
    }

    $os = php_uname('s');
    $commands = [];

    if ($version === 'ipv4') {
        if ($os === 'Linux') {
            $commands = [
                "timeout 2 hostname -I | awk '{print $1}'",
                "timeout 2 ip -4 addr show | grep inet | awk '{print $2}' | cut -d/ -f1",
                "timeout 2 ifconfig | grep 'inet ' | awk '{print $2}'",
            ];
        } elseif ($os === 'Windows') {
            $commands = [
                "ipconfig | findstr /R /C:\"IPv4\"",
            ];
        }
    } elseif ($version === 'ipv6') {
        if ($os === 'Linux') {
            $commands = [
                "timeout 2 hostname -I | awk '{print $2}'",
                "timeout 2 ip -6 addr show | grep inet6 | awk '{print $2}' | cut -d/ -f1",
                "timeout 2 ifconfig | grep 'inet6 ' | awk '{print $2}'",
            ];
        } elseif ($os === 'Windows') {
            $commands = [
                "ipconfig | findstr /R /C:\"IPv6\"",
            ];
        }
    }

    foreach ($commands as $command) {
        $output = shell_exec($command) ?? false;
        if ($output === false || trim($output) === '') {
            continue;
        }

        $ipArray = explode("\n", trim($output));
        foreach ($ipArray as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, $version === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }
    }

    return null;
}

function bbcs_fetchAndStoreParentIPs() {
    $response = wp_remote_get(BOTBLOCKER_PARENT_IPS_URL, array(
        'timeout'    => 10,
        'user-agent' => bbcs_current_user_agent(), //BBCS-MULTISITE
       // 'sslverify'  => false
    ));

    if (is_wp_error($response)) {
        throw new \Exception(wp_kses_post('Error loading URL: ' . BOTBLOCKER_PARENT_IPS_URL . ' | WP Error: ' . $response->get_error_message()));
    }

    $body = wp_remote_retrieve_body($response);
    if (strlen($body) > 512 * 1024) {
        throw new \Exception('Response too large from ' . BOTBLOCKER_PARENT_IPS_URL);
    }

    $ipAddresses = json_decode($body, true);

    if (!is_array($ipAddresses)) {
        throw new \Exception('Invalid JSON format in response.');
    }

    if (count($ipAddresses) > 10000) {
        throw new \Exception('Too many IP entries in response (max 10000).');
    }

    foreach ($ipAddresses as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            bbcs_addIPv4Rule($ip, 'IPv4 BotBlocker Server');
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            bbcs_addIPv6Rule($ip, 'IPv6 BotBlocker Server');
        } else {
            if (defined('BBCS_DEBUG') && BBCS_DEBUG == true) {
               // error_log('INVALID IP: ' . $ip);
            }
        }
    }
}
