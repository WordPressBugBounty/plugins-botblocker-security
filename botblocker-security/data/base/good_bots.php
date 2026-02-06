<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return [
    'bbcs_good_bots' => [
        // Major search engines
        'Googlebot'                 => ['.googlebot.com'], // Google main indexer
        'GoogleOther'               => ['.google.com'], // Other Google crawlers

        'bingbot'                   => ['.search.msn.com'], // Bing indexer

        'Applebot'                  => ['.applebot.apple.com'], // Applebot (Siri/Spotlight)
        'DuckDuckBot'               => ['.duckduckgo.com'], // DuckDuckGo
        'Baiduspider'               => ['.baidu.com'], // Baidu
        'YandexBot'                 => ['yandex.ru', 'yandex.net', 'yandex.com'], // Yandex
        'Mail.RU_Bot'               => ['mail.ru', 'smailru.net'], // Mail.ru indexers
        'SeznamBot'                 => ['.seznam.cz'], // Seznam
        'NaverBot'                  => ['.naver.com'], // Naver
        'Sogou'                     => ['.sogou.com'], // Sogou
        'MojeekBot'                 => ['.mojeek.com'], // Mojeek
        'PetalBot'                  => ['.petalsearch.com', '.aspiegel.com'], // Petal Search
        'Bytespider'                => ['.bytedance.com'], // TikTok crawler
        'Yahoo! Slurp'              => ['.yahoo.net'], // Yahoo legacy
        'Y!J'                       => ['.yahoo.co.jp'], // Yahoo! Japan

        // Social networks and link preview bots
        'facebookexternalhit'       => ['.fbsv.net', '66.220.149.', '31.13.', '2a03:2880:'], // Facebook crawler
        'vkShare'                   => ['.vk.com', '.vkontakte.ru', '.go.mail.ru', '.userapi.ru'], // VK
        'OdklBot'                   => ['.odnoklassniki.ru'], // Odnoklassniki
        'TelegramBot'               => ['149.154.', '91.108.'], // Telegram link preview IP ranges
        'Twitterbot'                => ['.twttr.com', '199.16.15'], // Twitter
        'Pinterestbot'              => ['.pinterest.com'], // Pinterest
        'LinkedInBot'               => ['.linkedin.com'], // LinkedIn
        'Slackbot'                  => ['.slack.com'], // Slack link expander
        'Discordbot'                => ['.discordapp.com', '.discord.com'], // Discord
        'WhatsApp'                  => ['.whatsapp.net', '.whatsapp.com'], // WhatsApp
        'SkypeUriPreview'           => ['.skype.com'], // Skype

        // Uptime and monitoring
        'uptimerobot'               => ['uptimerobot.com'], // UptimeRobot
        'pingdom'                   => ['pingdom.com'], // Pingdom
        'StatusCake'                => ['statuscake.com'], // StatusCake
        'BetterUptime'              => ['betteruptime.com'], // Better Uptime

        // Well-known SEO crawlers (optional allow-list)
        'AhrefsBot'                 => ['.ahrefs.com'], // Ahrefs
        'SemrushBot'                => ['.semrush.com'], // Semrush
        'MJ12bot'                   => ['.majestic12.co.uk', '.mj12bot.com'], // Majestic
        'DotBot'                    => ['.moz.com'], // Moz Link Explorer
    ],
];
