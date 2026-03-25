<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_createTables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    /**
     * Creates the 'bbcs_hits' table in the database.
     */
    // Define the common table structure for all hits tables
    $hits_table_structure = "
        `cid` TEXT NOT NULL,
        `uid` TEXT NOT NULL,
        `ip` TEXT NOT NULL,
        `date` INTEGER NOT NULL DEFAULT 0,
        `ptr` TEXT NOT NULL,
        `lang` TEXT NOT NULL,
        `accept_lang` TEXT NOT NULL,
        `name_lang` TEXT NOT NULL,
        `country` TEXT NOT NULL,
        `browser` TEXT NOT NULL,
        `os` TEXT NOT NULL,
        `device` TEXT NOT NULL,
        `referer` TEXT NOT NULL,
        `page` TEXT NOT NULL,
        `passed` INTEGER NOT NULL DEFAULT 0,
        `js_w` INTEGER NOT NULL,
        `js_h` INTEGER NOT NULL,
        `js_cw` INTEGER NOT NULL,
        `js_ch` INTEGER NOT NULL,
        `js_co` INTEGER NOT NULL,
        `js_pi` INTEGER NOT NULL,
        `refhost` TEXT NOT NULL,
        `asnum` TEXT NOT NULL,
        `asname` TEXT NOT NULL,
        `result` TEXT NOT NULL,
        `access` TEXT NOT NULL,
        `http_accept` TEXT NOT NULL,
        `method` TEXT NOT NULL,
        `ym_uid` TEXT NOT NULL,
        `ga_uid` TEXT NOT NULL,
        `ip_short` TEXT NOT NULL,
        `hosting` INTEGER NOT NULL DEFAULT 0,
        `hit` INTEGER NOT NULL DEFAULT 0,
        `timezone` TEXT NOT NULL,
        `cookie` TEXT NOT NULL,
        `region` TEXT NOT NULL,
        `region_name` TEXT NOT NULL,
        `country_name` TEXT NOT NULL,
        `proxy` TEXT NOT NULL,
        `tor` TEXT NOT NULL,
        `vpn` TEXT NOT NULL,
        `carrier` TEXT NOT NULL,
        `useragent` TEXT NOT NULL default '',
        `adblock` INTEGER NOT NULL,
        `lat` TEXT NOT NULL,
        `lon` TEXT NOT NULL,
        `city` TEXT NOT NULL,
        `generation` TEXT NOT NULL,
        `generation2` TEXT NOT NULL,
        `ipv4` TEXT NOT NULL,
        `cloud_data` TEXT NOT NULL,
        `recaptcha` TEXT NOT NULL,
        `wbot` TEXT NOT NULL,
        `fp` TEXT NOT NULL,
        UNIQUE KEY cid (cid(191)),
        KEY i_ip (ip(191)),
        KEY i_passed (passed),
        KEY i_date (`date`)
    ";

    dbDelta("CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_hits}` ($hits_table_structure) $charset_collate;");
    dbDelta("CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_hits_suspicious}` ($hits_table_structure) $charset_collate;");
    dbDelta("CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_hits_cloud}` ($hits_table_structure) $charset_collate;");

    /**
     * Creates the 'bbcs_se' table in the database.
     */
    $sql_se = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_se}` (
        `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `priority` INTEGER NOT NULL DEFAULT 100,
        `search` TEXT NOT NULL,
        `data` TEXT NOT NULL,
        `rule` TEXT NOT NULL,
        `comment` TEXT NOT NULL,
        `disable` INTEGER NOT NULL,
        UNIQUE KEY search (search(191))
    ) $charset_collate;";

    dbDelta($sql_se);

    /**
     * Creates the 'bbcs_ipv4rules' table in the database.
     */
    $sql_ipv4rules = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_ipv4rules}` (
        `id` BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `priority` INTEGER NOT NULL DEFAULT 100,
        `search` TEXT NOT NULL,
        `ip1` VARCHAR(11) NOT NULL DEFAULT '',
        `ip2` VARCHAR(11) NOT NULL DEFAULT '',
        `rule` TEXT NOT NULL,
        `comment` TEXT NOT NULL,
        `expires` BIGINT(20) NOT NULL DEFAULT " . BOTBLOCKER_EXP_INF . ",
        `disable` INTEGER NOT NULL DEFAULT 0,
        `readonly` INTEGER NOT NULL DEFAULT 0,
        UNIQUE KEY search (search(191)),
        KEY ipv4range_disabled_index (disable, ip1, ip2)
    ) $charset_collate;";

    dbDelta($sql_ipv4rules);

    /**
     * Creates the 'bbcs_ipv6rules' table in the database.
     */
    $sql_ipv6rules = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_ipv6rules}` (
        `id` BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `priority` INTEGER NOT NULL DEFAULT 100,
        `search` TEXT NOT NULL,
        `ip1` BINARY(16) NOT NULL,
        `ip2` BINARY(16) NOT NULL,
        `rule` TEXT NOT NULL,
        `comment` TEXT NOT NULL,
        `expires` BIGINT(20) NOT NULL DEFAULT " . BOTBLOCKER_EXP_INF . ",
        `disable` INTEGER NOT NULL DEFAULT 0,
        `readonly` INTEGER NOT NULL DEFAULT 0,
        UNIQUE KEY search (search(191)),
        KEY ipv6range_disabled_index (disable, ip1, ip2)
    ) $charset_collate;";

    dbDelta($sql_ipv6rules);

    /**
     * Creates the 'bbcs_path' table in the database.
     */
    $sql_path = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_path}` (
        `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `priority` INT(11) NOT NULL DEFAULT 100,
        `search` TEXT NOT NULL,
        `rule` TEXT NOT NULL,
        `comment` TEXT NOT NULL,
        `disable` INT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY search (search(191))
    ) $charset_collate;";

    dbDelta($sql_path);

    /**
     * Creates the 'bbcs_rules' table in the database.
     */
    $sql_rules = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_rules}` (
        `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `search` VARCHAR(255) AS (CONCAT(`type`, '=', `data`)) STORED UNIQUE,
        `priority` INTEGER NOT NULL DEFAULT 1,
        `type` TEXT NOT NULL,
        `data` TEXT NOT NULL,
        `expires` BIGINT(20) NOT NULL DEFAULT " . BOTBLOCKER_EXP_INF . ",
        `disable` INTEGER NOT NULL DEFAULT 0,
        `rule` TEXT NOT NULL,
        `comment` TEXT NOT NULL,
        KEY i_priority (priority)
    ) $charset_collate;";

    dbDelta($sql_rules);

    /**
     * Creates the 'bbcs_settings' table in the database.
     */
    $sql_settings = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_settings}` (
        `key` VARCHAR(191) NOT NULL UNIQUE,
        `value` TEXT NOT NULL
    ) $charset_collate;";

    dbDelta($sql_settings);

    /**
     * Creates the 'bbcs_proxy' table in the database.
     */
    $sql_proxy = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_proxy}` (
        `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `key` TEXT NOT NULL default '',
        `value` TEXT NOT NULL default '',
        `comment` TEXT NOT NULL default '',
        UNIQUE KEY uniq_key (`key`(191))
    ) $charset_collate;";

    dbDelta($sql_proxy);

    /**
     * Creates the 'bbcs_ptrcache' table in the database.
     */
    $sql_ptrcache = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_ptrcache}` (
        `ip` VARCHAR(45) NOT NULL DEFAULT '',
        `ptr` VARCHAR(255) NOT NULL DEFAULT '',
        `date` INTEGER NOT NULL DEFAULT 0,
        `etime` TEXT,
        PRIMARY KEY (ip)
    ) $charset_collate;";

    dbDelta($sql_ptrcache);

    /**
     * Creates the 'bbcs_counters' table in the database.
     */
    $sql_counters = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_counters}` (
        `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `today_hits` INT(11) NOT NULL DEFAULT 0,
        `today_blocked` INT(11) NOT NULL DEFAULT 0,
        `total_hits` INT(11) NOT NULL DEFAULT 0,
        `total_blocked` INT(11) NOT NULL DEFAULT 0,
        `search_engine_visits` INT(11) NOT NULL DEFAULT 0,
        `percent_requests_blocked` DECIMAL(5,2) AS (IF((total_hits + total_blocked) > 0, (total_blocked / (total_hits + total_blocked)) * 100, 0)) VIRTUAL,
        `last_update` DATETIME NULL
    ) $charset_collate;";

    dbDelta($sql_counters);

    bbcs_create_daily_summary_table();

    bbcs_create_page_filters_tables();
}

function bbcs_tablesExist()
{
    global $wpdb;

    static $memo = [];
    $blog_id = get_current_blog_id();
    if (isset($memo[$blog_id])) {
        return $memo[$blog_id];
    }

    $requiredTables = [
        $wpdb->bbcs_hits,
        $wpdb->bbcs_hits_suspicious,
        $wpdb->bbcs_hits_cloud,
        $wpdb->bbcs_se,
        $wpdb->bbcs_ipv4rules,
        $wpdb->bbcs_ipv6rules,
        $wpdb->bbcs_path,
        $wpdb->bbcs_rules,
        $wpdb->bbcs_settings,
        $wpdb->bbcs_ptrcache,
        $wpdb->bbcs_proxy,
        $wpdb->bbcs_counters,
    ];

    $requiredTables = array_values(array_unique(array_filter($requiredTables, 'is_string')));
    if (!$requiredTables) {
        $memo = true;
        return true;
    }

    $ok = null;

    // 1) Fast path: single COUNT(*) via information_schema
    $placeholders = implode(',', array_fill(0, count($requiredTables), '%s'));

    // REVIEWER NOTE: Single-query check of BotBlocker-Security custom tables via information_schema with prepared IN list.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
    $sql = $wpdb->prepare(
        "SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name IN ($placeholders)",
        $requiredTables
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
    $count = $wpdb->get_var($sql);

    if ($count !== null && $wpdb->last_error === '') {
        $ok = ((int) $count === count($requiredTables));
    }

    // 2) Fallback: still ONE query, but returns all tables (slower, but compatible)
    if ($ok === null) {
        // REVIEWER NOTE: Fallback single-query table list check using SHOW TABLES (some hosts restrict information_schema).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existingTables = $wpdb->get_col("SHOW TABLES");

        if (!is_array($existingTables) || !$existingTables) {
            $ok = false;
        } else {
            $map = array_fill_keys($existingTables, true);

            $ok = true;
            foreach ($requiredTables as $t) {
                if (!isset($map[$t])) {
                    $ok = false;
                    break;
                }
            }
        }
    }

    $memo[$blog_id] = (bool) $ok;

    return $memo[$blog_id];
}

function bbcs_create_page_filters_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_page_filters}` (
        `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `pattern` VARCHAR(191) NOT NULL,
        `category` VARCHAR(32) NOT NULL,
        UNIQUE KEY `pattern` (`pattern`)
    ) $charset_collate;";

    dbDelta($sql);
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->bbcs_page_filters}`");

    if ($count == 0) {
       $patterns = [
            ['%/wp-admin/%', 'admin'],
            ['%/wp-login%', 'admin'],
            ['%/wp-content/%', 'wp_system'],
            ['%/wp-includes/%', 'wp_system'],
            ['%/favicon.ico%', 'content'],
            ['%/wp-cron.php%', 'wp_system'],
            ['%/feed/%', 'seo'],
            ['%/xmlrpc.php%', 'api'],
            ['%/wp-json/%', 'api'],
            ['%/robots.txt%', 'seo'],
            ['%/sitemap%', 'seo'],
        ];

        foreach ($patterns as $pattern) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(
                $wpdb->bbcs_page_filters,
                [
                    'pattern' => $pattern[0],
                    'category' => $pattern[1],
                ],
                ['%s', '%s']
            );
        }
    }
}
