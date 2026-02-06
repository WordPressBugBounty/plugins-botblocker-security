<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
return [
    'bbcs_proxy' => [
        // CloudFlare IPv4
        '173.245.48.0/20'  => 'HTTP_CF_CONNECTING_IP',
        '103.21.244.0/22'  => 'HTTP_CF_CONNECTING_IP',
        '103.22.200.0/22'  => 'HTTP_CF_CONNECTING_IP',
        '103.31.4.0/22'    => 'HTTP_CF_CONNECTING_IP',
        '141.101.64.0/18'  => 'HTTP_CF_CONNECTING_IP',
        '108.162.192.0/18' => 'HTTP_CF_CONNECTING_IP',
        '190.93.240.0/20'  => 'HTTP_CF_CONNECTING_IP',
        '188.114.96.0/20'  => 'HTTP_CF_CONNECTING_IP',
        '197.234.240.0/22' => 'HTTP_CF_CONNECTING_IP',
        '198.41.128.0/17'  => 'HTTP_CF_CONNECTING_IP',
        '162.158.0.0/15'   => 'HTTP_CF_CONNECTING_IP',
        '104.16.0.0/13'    => 'HTTP_CF_CONNECTING_IP',
        '104.24.0.0/14'    => 'HTTP_CF_CONNECTING_IP',
        '172.64.0.0/13'    => 'HTTP_CF_CONNECTING_IP',
        '131.0.72.0/22'    => 'HTTP_CF_CONNECTING_IP',

        // CloudFlare IPv6
        '2400:cb00::/32'   => 'HTTP_CF_CONNECTING_IP',
        '2606:4700::/32'   => 'HTTP_CF_CONNECTING_IP',
        '2803:f800::/32'   => 'HTTP_CF_CONNECTING_IP',
        '2405:b500::/32'   => 'HTTP_CF_CONNECTING_IP',
        '2405:8100::/32'   => 'HTTP_CF_CONNECTING_IP',
        '2a06:98c0::/29'   => 'HTTP_CF_CONNECTING_IP',
        '2c0f:f248::/32'   => 'HTTP_CF_CONNECTING_IP',

        // AWS (Amazon Web Services)
        '54.239.0.0/16'    => 'HTTP_X_FORWARDED_FOR',
        '54.240.0.0/12'    => 'HTTP_X_FORWARDED_FOR',
        '204.246.164.0/22' => 'HTTP_X_FORWARDED_FOR',
        '205.251.192.0/19' => 'HTTP_X_FORWARDED_FOR',

        // Google Cloud
        '35.190.0.0/17'    => 'HTTP_X_FORWARDED_FOR',
        '64.233.160.0/19'  => 'HTTP_X_FORWARDED_FOR',
        '66.102.0.0/20'    => 'HTTP_X_FORWARDED_FOR',
        '66.249.80.0/20'   => 'HTTP_X_FORWARDED_FOR',
        '72.14.192.0/18'   => 'HTTP_X_FORWARDED_FOR',
        '74.125.0.0/16'    => 'HTTP_X_FORWARDED_FOR',
    ]
];
