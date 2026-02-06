<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_codeList(int $code): array
{
    $codes = [
        0 => [
            "msg" => "Show check page <b>stop</b> for manual check",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        1 => [
            "msg" => "Cookie passed <b>auto</b> check",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        2 => [
            "msg" => "Check page passed click success",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        3 => [
            "msg" => "Allow cookies after local check <b>auto</b> pass",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        4 => [
            "msg" => "Allow by path or rule",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        5 => [
            "msg" => "Good bot <b>good</b> ip or ptr",
            "allow" => true,
            "count" => true,
            "searchbot" => true
        ],
        6 => [
            "msg" => "Block by rule or path",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        7 => [
            "msg" => "Fake bot <b>fake</b> ip or ptr",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        8 => [
            "msg" => "Wrong click ban time1 or time2",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        9 => [
            "msg" => "JavaScript check error",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],

        10 => [
            "msg" => "Fake browser detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        11 => [
            "msg" => "Simple bot detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        12 => [
            "msg" => "Self request hosting view",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        13 => [
            "msg" => "Geo or language block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        14 => [
            "msg" => "IPv6 block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        15 => [
            "msg" => "Connection failure vpn tor or proxy",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        16 => [
            "msg" => "Analytics or search engine",
            "allow" => true,
            "count" => true,
            "searchbot" => true
        ],
        17 => [
            "msg" => "Hosting detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        18 => [
            "msg" => "Unknown activities",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        19 => [
            "msg" => "Request reset by client",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        20 => [
            "msg" => "BotBlocker internal error",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        21 => [
            "msg" => "WordPress environment error",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        22 => [
            "msg" => "Cloud check error 500 or 404",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        23 => [
            "msg" => "BotBlocker inactive all pass",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        24 => [
            "msg" => "Fingerprint allow",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        25 => [
            "msg" => "Fingerprint block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        26 => [
            "msg" => "Many requests block brute force",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        27 => [
            "msg" => "Direct file access block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        28 => [
            "msg" => "REST or RPC block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        29 => [
            "msg" => "REST or RPC allow whitelist",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],

        30 => [
            "msg" => "Early block reason 0-9",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        40 => [
            "msg" => "MU block reason 0-9",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],

        50 => [
            "msg" => "Empty user agent",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        51 => [
            "msg" => "IPv6 user block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        52 => [
            "msg" => "Empty language header",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        53 => [
            "msg" => "HTTP 1.0 detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        54 => [
            "msg" => "Bot features in user agent",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        55 => [
            "msg" => "CloudFlare user detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        56 => [
            "msg" => "Classic proxy detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        57 => [
            "msg" => "Incorrect language header",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        58 => [
            "msg" => "Fake referrer detected",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        59 => [
            "msg" => "Wordpress administrator",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        60 => [
            "msg" => "IP equals PTR record",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],

        70 => [
            "msg" => "WordPress cron",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        71 => [
            "msg" => "IP rule allow",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        72 => [
            "msg" => "IP rule block",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        73 => [
            "msg" => "Wordpress heartbeat",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        74 => [
            "msg" => "Wordpress REST API",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        80 => [
            "msg" => "Captcha timeout",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
        90 => [
            "msg" => "CLI request detected",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        98 => [
            "msg" => "BotBlocker start by secret",
            "allow" => true,
            "count" => true,
            "searchbot" => false
        ],
        99 => [
            "msg" => "BotBlocker server access",
            "allow" => true,
            "count" => false,
            "searchbot" => false
        ],
        100 => [
            "msg" => "Unknown error",
            "allow" => false,
            "count" => true,
            "searchbot" => false
        ],
    ];

    return $codes[$code] ?? ["msg" => "Unknown code", "allow" => false, "count" => true];
}
