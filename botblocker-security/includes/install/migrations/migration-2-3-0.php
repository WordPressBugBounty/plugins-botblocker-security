<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_migration_2_3_0() {
    global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

    $search_key_exists = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_ipv4rules}` WHERE Key_name = 'search'"
    );
    $ipv4range_key_exists = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_ipv4rules}` WHERE Key_name = 'ipv4range_disabled_index'"
    );

    $alter_parts = [];
    if ( $search_key_exists ) {
        $alter_parts[] = "DROP KEY `search`";
    }
    if ( $ipv4range_key_exists ) {
        $alter_parts[] = "DROP KEY `ipv4range_disabled_index`";
    }
    $alter_parts[] = "MODIFY `ip1` VARCHAR(11) NOT NULL DEFAULT ''";
    $alter_parts[] = "MODIFY `ip2` VARCHAR(11) NOT NULL DEFAULT ''";
    $alter_parts[] = "ADD KEY `ipv4range_disabled_index` (`disable`, `ip1`, `ip2`)";
    $alter_parts[] = "ADD UNIQUE KEY `search` (`search`(191))";

    $wpdb->query(
        "ALTER TABLE `{$wpdb->bbcs_ipv4rules}`
            " . implode( ',
            ', $alter_parts )
    );

    $i_search_key_exists = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_rules}` WHERE Key_name = 'i_search'"
    );
    if ( $i_search_key_exists ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->bbcs_rules}` DROP KEY `i_search`" );
    }

    $wpdb->query( "ALTER TABLE `{$wpdb->bbcs_settings}` MODIFY `key` VARCHAR(191) NOT NULL" );

    $primary_key_exists = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_ptrcache}` WHERE Key_name = 'PRIMARY'"
    );

    if ( $primary_key_exists ) {
        $wpdb->query(
            "ALTER TABLE `{$wpdb->bbcs_ptrcache}`
                DROP PRIMARY KEY,
                MODIFY `ip`   VARCHAR(45)  NOT NULL DEFAULT '',
                MODIFY `ptr`  VARCHAR(255) NOT NULL DEFAULT '',
                MODIFY `date` INTEGER      NOT NULL DEFAULT 0,
                ADD PRIMARY KEY (`ip`)"
        );
    } else {
        $wpdb->query(
            "ALTER TABLE `{$wpdb->bbcs_ptrcache}`
                MODIFY `ip`   VARCHAR(45)  NOT NULL DEFAULT '',
                MODIFY `ptr`  VARCHAR(255) NOT NULL DEFAULT '',
                MODIFY `date` INTEGER      NOT NULL DEFAULT 0,
                ADD PRIMARY KEY (`ip`)"
        );
    }

    if ( ! empty( $wpdb->last_error ) ) {
        if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( '[BBCS DEBUG] [Migration] migration 2.3.0: DDL error - ' . $wpdb->last_error );
        }
    }

    $idx_category_exists = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_page_filters}` WHERE Key_name = 'idx_category'"
    );

    if ( $idx_category_exists ) {
        $wpdb->query(
            "ALTER TABLE `{$wpdb->bbcs_page_filters}`
                MODIFY `pattern` VARCHAR(191) NOT NULL,
                DROP KEY `idx_category`"
        );
    } else {
        $wpdb->query(
            "ALTER TABLE `{$wpdb->bbcs_page_filters}`
                MODIFY `pattern` VARCHAR(191) NOT NULL"
        );
    }

    $se_idx = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_se}` WHERE Key_name = 'search'"
    );
    if ( ! $se_idx ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->bbcs_se}` ADD UNIQUE KEY `search` (`search`(191))" );
    }

    $path_idx = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_path}` WHERE Key_name = 'search'"
    );
    if ( ! $path_idx ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->bbcs_path}` ADD UNIQUE KEY `search` (`search`(191))" );
    }

    $proxy_idx = $wpdb->get_var(
        "SHOW INDEX FROM `{$wpdb->bbcs_proxy}` WHERE Key_name = 'uniq_key'"
    );
    if ( ! $proxy_idx ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->bbcs_proxy}` ADD UNIQUE KEY `uniq_key` (`key`(191))" );
    }

    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 10, 'comment' => 'GoogleBot (Catch-all)' ],
        [ 'search' => 'Googlebot' ],
        [ '%d', '%s' ],
        [ '%s' ]
    );

    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 1, 'disable' => 0 ],
        [ 'search' => 'Google-InspectionTool' ],
        [ '%d', '%d' ],
        [ '%s' ]
    );

    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 1, 'disable' => 0 ],
        [ 'search' => 'Chrome-Lighthouse' ],
        [ '%d', '%d' ],
        [ '%s' ]
    );

    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 1, 'disable' => 0 ],
        [ 'search' => 'Mediapartners' ],
        [ '%d', '%d' ],
        [ '%s' ]
    );

    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'rule' => 'allow' ],
        [ 'search' => 'Baiduspider' ],
        [ '%s' ],
        [ '%s' ]
    );

    $new_search_engines = [
        ['priority' => 2,  'search' => 'Googlebot-Image',       'data' => '.googlebot.com .google.com',         'rule' => 'allow', 'comment' => 'Google Images',              'disable' => 0],
        ['priority' => 2,  'search' => 'Googlebot-Video',       'data' => '.googlebot.com .google.com',         'rule' => 'allow', 'comment' => 'Google Videos',              'disable' => 0],
        ['priority' => 2,  'search' => 'Googlebot-News',        'data' => '.googlebot.com .google.com',         'rule' => 'allow', 'comment' => 'Google News',                'disable' => 0],
        ['priority' => 2,  'search' => 'Storebot-Google',       'data' => '.google.com',                        'rule' => 'allow', 'comment' => 'Google Shopping',            'disable' => 0],

        ['priority' => 5,  'search' => 'GoogleOther-Image',     'data' => '.google.com',                        'rule' => 'allow', 'comment' => 'GoogleOther Images',         'disable' => 0],
        ['priority' => 5,  'search' => 'GoogleOther-Video',     'data' => '.google.com',                        'rule' => 'allow', 'comment' => 'GoogleOther Videos',         'disable' => 0],
        // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- bot allowlist domain, not remote resource loading
        ['priority' => 5,  'search' => 'Google-CloudVertexBot', 'data' => '.google.com .googleusercontent.com', 'rule' => 'allow', 'comment' => 'Vertex AI',                  'disable' => 0],

        ['priority' => 10, 'search' => 'GoogleOther',           'data' => '.google.com',                        'rule' => 'allow', 'comment' => 'GoogleOther (Catch-all)',    'disable' => 0],

        ['priority' => 31, 'search' => 'GPTBot',                'data' => '.openai.com',                        'rule' => 'allow', 'comment' => 'OpenAI GPT training crawler',      'disable' => 0],
        ['priority' => 32, 'search' => 'OAI-SearchBot',         'data' => '.openai.com',                        'rule' => 'allow', 'comment' => 'OpenAI search crawler',             'disable' => 0],
        ['priority' => 33, 'search' => 'ChatGPT-User',          'data' => '.openai.com',                        'rule' => 'allow', 'comment' => 'ChatGPT user-initiated requests',   'disable' => 0],
        ['priority' => 34, 'search' => 'ClaudeBot',             'data' => '.anthropic.com',                     'rule' => 'allow', 'comment' => 'Anthropic Claude training crawler', 'disable' => 0],
        ['priority' => 35, 'search' => 'Claude-User',           'data' => '.anthropic.com',                     'rule' => 'allow', 'comment' => 'Claude user-initiated requests',    'disable' => 0],
        ['priority' => 36, 'search' => 'Claude-SearchBot',      'data' => '.anthropic.com',                     'rule' => 'allow', 'comment' => 'Claude search crawler',             'disable' => 0],
    ];

    $value_placeholders = [];
    $value_args         = [];
    foreach ( $new_search_engines as $se ) {
        $value_placeholders[] = '(%d, %s, %s, %s, %s, %d)';
        $value_args[]         = $se['priority'];
        $value_args[]         = $se['search'];
        $value_args[]         = $se['data'];
        $value_args[]         = $se['rule'];
        $value_args[]         = $se['comment'];
        $value_args[]         = $se['disable'];
    }

    $sql = $wpdb->prepare(
        "INSERT IGNORE INTO `{$wpdb->bbcs_se}`
            (`priority`, `search`, `data`, `rule`, `comment`, `disable`)
         VALUES " . implode( ', ', $value_placeholders ),
        $value_args
    );
    $wpdb->query( $sql );

	BotBlockerDb::generateAllFiles();

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
