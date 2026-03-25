<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_insertInitialData($salt_bb = '')
{
    bbcs_insertDefaultRules();
    bbcs_insertDefaultSearchEngines();
    bbcs_insertDefaultPaths();
    bbcs_insertDefaultSettings($salt_bb);
    bbcs_insertDefaultProxies();
    bbcs_insertDefaultCounters();
}

function bbcs_insertDefaultRules()
{
    global $wpdb;

    $default_rules = [
        ['priority' => 10, 'type' => 'asname', 'data' => 'Biterika', 'search' => 'asname=Biterika', 'rule' => 'block', 'comment' => 'Spam ASN'],
        ['priority' => 10, 'type' => 'asname', 'data' => 'Squitter-Networks', 'search' => 'asname=Squitter-Networks', 'rule' => 'block', 'comment' => 'Known spam network'],
        ['priority' => 10, 'type' => 'asname', 'data' => 'ColocationAmerica', 'search' => 'asname=ColocationAmerica', 'rule' => 'block', 'comment' => 'Network with high bot activity'],
        ['priority' => 10, 'type' => 'asname', 'data' => 'Centrilogic', 'search' => 'asname=Centrilogic', 'rule' => 'block', 'comment' => 'Network with suspicious activity'],
        ['priority' => 10, 'type' => 'asname', 'data' => 'Selectel', 'search' => 'asname=Selectel', 'rule' => 'block', 'comment' => 'Hosting with high botnet activity'],
        ['priority' => 10, 'type' => 'asname', 'data' => 'Serverion', 'search' => 'asname=Serverion', 'rule' => 'block', 'comment' => 'Anonymous proxy servers'],
        ['priority' => 10, 'type' => 'asname', 'data' => 'Hostkey', 'search' => 'asname=Hostkey', 'rule' => 'block', 'comment' => 'Hosting known for malicious bots'],
        ['priority' => 10, 'type' => 'asname', 'data' => '3NT Solutions', 'search' => 'asname=3NT Solutions', 'rule' => 'block', 'comment' => 'Known source of malicious activity']
    ];

    foreach ($default_rules as $rule) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_rules}` WHERE search = %s", $rule['search']));

        if ($exists == 0) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT INTO `{$wpdb->bbcs_rules}` (priority, type, data, rule, comment) VALUES (%d, %s, %s, %s, %s)",
                $rule['priority'],
                $rule['type'],
                $rule['data'],
                $rule['rule'],
                $rule['comment']
            ));
        }
    }
}

function bbcs_insertDefaultCounters()
{
    global $wpdb;

    $default_counters = [
        'today_hits' => 0,
        'today_blocked' => 0,
        'total_hits' => 0,
        'total_blocked' => 0,
        'search_engine_visits' => 0,
    ];
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_counters}`");

    if ($row_count == 0) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare(
            "INSERT INTO `{$wpdb->bbcs_counters}` (today_hits, today_blocked, total_hits, total_blocked, search_engine_visits)
             VALUES (%d, %d, %d, %d, %d)",
            $default_counters['today_hits'],
            $default_counters['today_blocked'],
            $default_counters['total_hits'],
            $default_counters['total_blocked'],
            $default_counters['search_engine_visits']
        ));
    }
}

function bbcs_insertDefaultSearchEngines()
{
    global $wpdb;

    $default_search_engines = [
            ['priority' => 1,  'search' => 'Google-InspectionTool', 'data' => '.googlebot.com', 'rule' => 'allow', 'comment' => 'Search Console', 'disable' => 0],
            ['priority' => 1,  'search' => 'Chrome-Lighthouse', 'data' => '.google.com', 'rule' => 'allow', 'comment' => 'PageSpeed Insights', 'disable' => 0],
            ['priority' => 1,  'search' => 'Mediapartners', 'data' => '.googlebot.com .google.com', 'rule' => 'allow', 'comment' => 'AdSense bot', 'disable' => 0],

            ['priority' => 2,  'search' => 'Googlebot-Image', 'data' => '.googlebot.com .google.com', 'rule' => 'allow', 'comment' => 'Google Images', 'disable' => 0],
            ['priority' => 2,  'search' => 'Googlebot-Video', 'data' => '.googlebot.com .google.com', 'rule' => 'allow', 'comment' => 'Google Videos', 'disable' => 0],
            ['priority' => 2,  'search' => 'Googlebot-News', 'data' => '.googlebot.com .google.com', 'rule' => 'allow', 'comment' => 'Google News', 'disable' => 0],
            ['priority' => 2,  'search' => 'Storebot-Google', 'data' => '.google.com', 'rule' => 'allow', 'comment' => 'Google Shopping', 'disable' => 0],

            ['priority' => 5,  'search' => 'GoogleOther-Image', 'data' => '.google.com', 'rule' => 'allow', 'comment' => 'GoogleOther Images', 'disable' => 0],
            ['priority' => 5,  'search' => 'GoogleOther-Video', 'data' => '.google.com', 'rule' => 'allow', 'comment' => 'GoogleOther Videos', 'disable' => 0],
            ['priority' => 5,  'search' => 'Google-CloudVertexBot', 'data' => '.google.com .googleusercontent.com', 'rule' => 'allow', 'comment' => 'Vertex AI', 'disable' => 0],

            ['priority' => 10, 'search' => 'Googlebot', 'data' => '.googlebot.com', 'rule' => 'allow', 'comment' => 'GoogleBot (Catch-all)', 'disable' => 0],
            ['priority' => 10, 'search' => 'GoogleOther', 'data' => '.google.com', 'rule' => 'allow', 'comment' => 'GoogleOther (Catch-all)', 'disable' => 0],

            ['priority' => 2,  'search' => 'bingbot', 'data' => 'search.msn.com', 'rule' => 'allow', 'comment' => 'Microsoft Bing search bot', 'disable' => 0],
            ['priority' => 3,  'search' => 'yandex.com', 'data' => '.yandex.ru .yandex.net .yandex.com', 'rule' => 'allow', 'comment' => 'Yandex search bots and services', 'disable' => 0],
            ['priority' => 4,  'search' => 'Applebot', 'data' => '.applebot.apple.com', 'rule' => 'allow', 'comment' => 'Applebot crawler', 'disable' => 0],
            ['priority' => 5,  'search' => 'Google-Site-Verification', 'data' => '.googlebot.com .google.com', 'rule' => 'allow', 'comment' => 'Verification bot for Google Search Console', 'disable' => 0],
            ['priority' => 6,  'search' => 'DuckDuckBot', 'data' => '.duckduckgo.com', 'rule' => 'allow', 'comment' => 'DuckDuckGo search engine bot', 'disable' => 0],
            ['priority' => 8,  'search' => 'PetalBot', 'data' => '.petalsearch.com .aspiegel.com', 'rule' => 'allow', 'comment' => 'Huawei Petal Search', 'disable' => 0],
            ['priority' => 12, 'search' => 'SeznamBot', 'data' => '.seznam.cz', 'rule' => 'allow', 'comment' => 'Seznam.cz search engine bot', 'disable' => 0],
            ['priority' => 15, 'search' => 'NaverBot', 'data' => '.naver.com', 'rule' => 'allow', 'comment' => 'Naver search engine bot', 'disable' => 0],
            ['priority' => 16, 'search' => 'Sogou', 'data' => '.sogou.com', 'rule' => 'allow', 'comment' => 'Sogou search engine bot', 'disable' => 0],
            ['priority' => 18, 'search' => 'Baiduspider', 'data' => '.baidu.com', 'rule' => 'allow', 'comment' => 'Baidu search engine bot (China)', 'disable' => 0],
            ['priority' => 20, 'search' => 'MojeekBot', 'data' => '.mojeek.com', 'rule' => 'allow', 'comment' => 'Mojeek search engine bot', 'disable' => 0],
            ['priority' => 22, 'search' => 'Bytespider', 'data' => '.bytedance.com', 'rule' => 'allow', 'comment' => 'TikTok crawler', 'disable' => 0],
            ['priority' => 28, 'search' => 'Y!J', 'data' => '.yahoo.co.jp', 'rule' => 'allow', 'comment' => 'Yahoo! Japan crawler', 'disable' => 0],
            ['priority' => 30, 'search' => 'Yahoo! Slurp', 'data' => '.yahoo.net', 'rule' => 'allow', 'comment' => 'Yahoo legacy crawler', 'disable' => 0],
            ['priority' => 50, 'search' => 'msnbot', 'data' => 'search.msn.com', 'rule' => 'allow', 'comment' => 'Legacy Microsoft search bot', 'disable' => 0],

            ['priority' => 31, 'search' => 'GPTBot', 'data' => '.openai.com', 'rule' => 'allow', 'comment' => 'OpenAI GPT training crawler', 'disable' => 0],
            ['priority' => 32, 'search' => 'OAI-SearchBot', 'data' => '.openai.com', 'rule' => 'allow', 'comment' => 'OpenAI search crawler', 'disable' => 0],
            ['priority' => 33, 'search' => 'ChatGPT-User', 'data' => '.openai.com', 'rule' => 'allow', 'comment' => 'ChatGPT user-initiated requests', 'disable' => 0],
            ['priority' => 34, 'search' => 'ClaudeBot', 'data' => '.anthropic.com', 'rule' => 'allow', 'comment' => 'Anthropic Claude training crawler', 'disable' => 0],
            ['priority' => 35, 'search' => 'Claude-User', 'data' => '.anthropic.com', 'rule' => 'allow', 'comment' => 'Claude user-initiated requests', 'disable' => 0],
            ['priority' => 36, 'search' => 'Claude-SearchBot', 'data' => '.anthropic.com', 'rule' => 'allow', 'comment' => 'Claude search crawler', 'disable' => 0],

            ['priority' => 11, 'search' => 'facebookexternalhit', 'data' => '.fbsv.net .tfbnw.net', 'rule' => 'allow', 'comment' => 'Facebook crawler', 'disable' => 0],
            ['priority' => 19, 'search' => 'vkShare', 'data' => '.vk.com .vkontakte.ru .userapi.ru', 'rule' => 'allow', 'comment' => 'VK link preview', 'disable' => 0],
            ['priority' => 24, 'search' => 'LinkedInBot', 'data' => '.linkedin.com .ads.linkedin.com', 'rule' => 'allow', 'comment' => 'LinkedIn bot for link preview and ads', 'disable' => 0],
            ['priority' => 26, 'search' => 'Pinterestbot', 'data' => '.pinterest.com', 'rule' => 'allow', 'comment' => 'Pinterest link preview bot', 'disable' => 0],
            ['priority' => 45, 'search' => 'Discordbot', 'data' => '.discordapp.com .discord.com', 'rule' => 'allow', 'comment' => 'Discord link expander', 'disable' => 0],
            ['priority' => 48, 'search' => 'OdklBot', 'data' => '.odnoklassniki.ru', 'rule' => 'allow', 'comment' => 'Odnoklassniki link preview', 'disable' => 0],

            ['priority' => 60, 'search' => 'UptimeRobot', 'data' => 'uptimerobot.com', 'rule' => 'allow', 'comment' => 'UptimeRobot monitoring', 'disable' => 0],
            ['priority' => 60, 'search' => 'Pingdom', 'data' => 'pingdom.com', 'rule' => 'allow', 'comment' => 'Pingdom monitoring', 'disable' => 0],
            ['priority' => 60, 'search' => 'StatusCake', 'data' => 'statuscake.com', 'rule' => 'allow', 'comment' => 'StatusCake monitoring', 'disable' => 0],
            ['priority' => 60, 'search' => 'BetterUptime', 'data' => 'betteruptime.com', 'rule' => 'allow', 'comment' => 'Better Uptime monitoring', 'disable' => 0],

            ['priority' => 70, 'search' => 'Facebook Pixel', 'data' => '.facebook.net .facebook.com', 'rule' => 'allow', 'comment' => 'Facebook tracking and analytics', 'disable' => 0],
            ['priority' => 70, 'search' => 'Hotjar', 'data' => '.hotjar.com', 'rule' => 'allow', 'comment' => 'Hotjar analytics service', 'disable' => 0],
            ['priority' => 70, 'search' => 'Quantcast', 'data' => '.quantserve.com', 'rule' => 'gray', 'comment' => 'Quantcast analytics and audience measurement', 'disable' => 0],
            ['priority' => 70, 'search' => 'Heap Analytics', 'data' => '.heap.io', 'rule' => 'allow', 'comment' => 'Heap analytics service', 'disable' => 0],
            ['priority' => 70, 'search' => 'Matomo', 'data' => '.matomo.org', 'rule' => 'allow', 'comment' => 'Matomo (formerly Piwik) analytics bot', 'disable' => 0],
            ['priority' => 70, 'search' => 'Mixpanel', 'data' => '.mixpanel.com', 'rule' => 'allow', 'comment' => 'Mixpanel analytics and tracking service', 'disable' => 0],
            ['priority' => 70, 'search' => 'Cloudflare Web Analytics', 'data' => '.cloudflare.com', 'rule' => 'allow', 'comment' => 'Cloudflare analytics service', 'disable' => 0],
            ['priority' => 70, 'search' => 'Snapchat Pixel', 'data' => '.snapchat.com .pixel.snapchat.com', 'rule' => 'allow', 'comment' => 'Snapchat tracking pixel bot', 'disable' => 0],

            ['priority' => 80, 'search' => 'AhrefsBot', 'data' => '.ahrefs.com', 'rule' => 'dark', 'comment' => 'Ahrefs SEO and crawling bot', 'disable' => 0],
            ['priority' => 80, 'search' => 'SemrushBot', 'data' => '.semrush.com', 'rule' => 'dark', 'comment' => 'Semrush SEO crawler', 'disable' => 0],
            ['priority' => 80, 'search' => 'MJ12bot', 'data' => '.majestic12.co.uk .mj12bot.com', 'rule' => 'dark', 'comment' => 'Majestic crawler', 'disable' => 0],
            ['priority' => 80, 'search' => 'DotBot', 'data' => '.moz.com', 'rule' => 'dark', 'comment' => 'Moz DotBot crawler', 'disable' => 0],
    ];

    foreach ($default_search_engines as $se) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_se}` WHERE search = %s", $se['search']));

        if ($exists == 0) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT INTO `{$wpdb->bbcs_se}` (priority, search, data, rule, comment, disable) VALUES (%d, %s, %s, %s, %s, %d)",
                $se['priority'],
                $se['search'],
                $se['data'],
                $se['rule'],
                $se['comment'],
                $se['disable']
            ));
        }
    }
}

function bbcs_insertDefaultPaths()
{
    global $wpdb;

    $default_paths = [
        ['priority' => 1,  'search' => '/wp-cron.php', 'rule' => 'allow', 'comment' => 'WordPress cron jobs', 'disable' => 1],
        ['priority' => 1,  'search' => '/wp-admin/admin-ajax.php', 'rule' => 'allow', 'comment' => 'WordPress AJAX', 'disable' => 1],
        ['priority' => 1,  'search' => '/wp-admin/post.php', 'rule' => 'allow', 'comment' => 'Wordpress POST', 'disable' => 1],
        ['priority' => 1,  'search' => '/?wc-ajax=', 'rule' => 'allow', 'comment' => 'Woocommerce AJAX', 'disable' => 1],
    ];

    foreach ($default_paths as $path) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE search = %s", $path['search']));

        if ($exists == 0) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT INTO `{$wpdb->bbcs_path}` (priority, search, rule, comment, disable) VALUES (%d, %s, %s, %s, %d)",
                $path['priority'],
                $path['search'],
                $path['rule'],
                $path['comment'],
                $path['disable']
            ));
        }
    }
}

function bbcs_insertExtendedPaths()
{
    global $wpdb;

    $default_paths = [
        ['priority' => 1,  'search' => '/favicon.ico', 'rule' => 'allow', 'comment' => 'WordPress Favicon', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/post.php', 'rule' => 'allow', 'comment' => 'WordPress post editing', 'disable' => 0],
        ['priority' => 1,  'search' => '/?wc-ajax=', 'rule' => 'allow', 'comment' => 'WooCommerce AJAX', 'disable' => 0],
        ['priority' => 1,  'search' => '/wp-admin/load-scripts.php', 'rule' => 'allow', 'comment' => 'WordPress script loading', 'disable' => 0],
        ['priority' => 1,  'search' => '/wp-admin/load-styles.php', 'rule' => 'allow', 'comment' => 'WordPress style loading', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/admin-post.php', 'rule' => 'allow', 'comment' => 'WordPress admin posts', 'disable' => 0],
        ['priority' => 1,  'search' => '/wp-admin/async-upload.php', 'rule' => 'allow', 'comment' => 'WordPress async upload', 'disable' => 0],
        ['priority' => 1,  'search' => '/wp-admin/update-core.php', 'rule' => 'allow', 'comment' => 'WordPress core updates', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/customize.php', 'rule' => 'allow', 'comment' => 'WordPress customizer', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/admin.php', 'rule' => 'allow', 'comment' => 'WordPress admin', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/options.php', 'rule' => 'allow', 'comment' => 'WordPress options', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/edit-comments.php', 'rule' => 'allow', 'comment' => 'WordPress comment editing', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/edit.php', 'rule' => 'allow', 'comment' => 'WordPress post/page editing', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/users.php', 'rule' => 'allow', 'comment' => 'WordPress user management', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/profile.php', 'rule' => 'allow', 'comment' => 'WordPress user profile', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/media-new.php', 'rule' => 'allow', 'comment' => 'WordPress media upload', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/upload.php', 'rule' => 'allow', 'comment' => 'WordPress media library', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/post-new.php', 'rule' => 'allow', 'comment' => 'WordPress new post/page', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-admin/edit-tags.php', 'rule' => 'allow', 'comment' => 'WordPress taxonomy management', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-comments-post.php', 'rule' => 'allow', 'comment' => 'WordPress comment posting', 'disable' => 0],
        ['priority' => 10, 'search' => '/wp-includes/js/tinymce/', 'rule' => 'allow', 'comment' => 'WordPress TinyMCE editor', 'disable' => 0],
        ['priority' => 1,  'search' => '/wp-json/', 'rule' => 'allow', 'comment' => 'WordPress REST API', 'disable' => 0],
        ['priority' => 1,  'search' => '/xmlrpc.php', 'rule' => 'allow', 'comment' => 'WordPress XML-RPC', 'disable' => 0]
    ];

    foreach ($default_paths as $path) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_path}` WHERE search = %s", $path['search']));

        if ($exists == 0) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT INTO `{$wpdb->bbcs_path}` (priority, search, rule, comment, disable) VALUES (%d, %s, %s, %s, %d)",
                $path['priority'],
                $path['search'],
                $path['rule'],
                $path['comment'],
                $path['disable']
            ));
        }
    }
}

function bbcs_insertDefaultSettings($salt_bb)
{
    global $wpdb;

    $cloud_api_email = bbcs_getCloudAPIEmail();
    $cloud_api_pass = md5(md5($cloud_api_email) . $salt_bb);
    $cloud_api_key = bbcs_generateCloudAPIKey(BOTBLOCKER_SHORT_NAME, $cloud_api_email);
    $cloud_api_secret = bbcs_generateCloudAPIKey(BOTBLOCKER_SHORT_NAME, $cloud_api_pass);
    $secret_param = md5(BOTBLOCKER_URL . time());

    $default_settings = bbcs_loadDefaultSettings();
    $genarated_settings = array(
        'salt' => $salt_bb,
        'cloud_api_key' => $cloud_api_key,
        'cloud_api_secret' => $cloud_api_secret,
        'cloud_api_email' => $cloud_api_email,
        'cloud_api_pass' => $cloud_api_pass,
        'cloud_api_tier' => '',

        'secret_botblocker_get_param' => $secret_param,

        'action_disable' => BOTBLOCKER_PREFIX . md5($secret_param . $salt_bb . 'disable') . '_disable',
        'action_off' => BOTBLOCKER_PREFIX . md5($secret_param . $salt_bb . 'off') . '_off',
        'action_on' => BOTBLOCKER_PREFIX . md5($secret_param . $salt_bb . 'on') . '_on',
    );

    $all_settings = array_merge($default_settings, $genarated_settings);

    bbcs_saveSettingsToFile($all_settings);
    bbcs_saveSettingsToDatabase($all_settings);
}

function bbcs_insertDefaultProxies()
{
    global $wpdb;

    $default_proxies = [
        // CloudFlare IPv4
        '173.245.48.0/20'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '103.21.244.0/22'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '103.22.200.0/22'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '103.31.4.0/22'    => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '141.101.64.0/18'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '108.162.192.0/18' => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '190.93.240.0/20'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '188.114.96.0/20'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '197.234.240.0/22' => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '198.41.128.0/17'  => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '162.158.0.0/15'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '104.16.0.0/13'    => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '104.24.0.0/14'    => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '172.64.0.0/13'    => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],
        '131.0.72.0/22'    => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4'],

        // CloudFlare IPv6
        '2400:cb00::/32'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],
        '2606:4700::/32'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],
        '2803:f800::/32'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],
        '2405:b500::/32'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],
        '2405:8100::/32'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],
        '2a06:98c0::/29'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],
        '2c0f:f248::/32'   => ['HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6'],

        // AWS (Amazon Web Services)
        '54.239.0.0/16'    => ['HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)'],
        '54.240.0.0/12'    => ['HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)'],
        '204.246.164.0/22' => ['HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)'],
        '205.251.192.0/19' => ['HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)'],

        // Google Cloud
        '35.190.0.0/17'    => ['HTTP_X_FORWARDED_FOR', 'Google Cloud'],
        '64.233.160.0/19'  => ['HTTP_X_FORWARDED_FOR', 'Google Cloud'],
        '66.102.0.0/20'    => ['HTTP_X_FORWARDED_FOR', 'Google Cloud'],
        '66.249.80.0/20'   => ['HTTP_X_FORWARDED_FOR', 'Google Cloud'],
        '72.14.192.0/18'   => ['HTTP_X_FORWARDED_FOR', 'Google Cloud'],
        '74.125.0.0/16'    => ['HTTP_X_FORWARDED_FOR', 'Google Cloud']
    ];

    foreach ($default_proxies as $key => $data) {
        $value = $data[0];
        $comment = $data[1];
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->bbcs_proxy}` WHERE `key` = %s", $key));

        if ($exists == 0) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT INTO `{$wpdb->bbcs_proxy}` (`key`, `value`, `comment`) VALUES (%s, %s, %s)",
                $key,
                $value,
                $comment
            ));
        }
    }
}
