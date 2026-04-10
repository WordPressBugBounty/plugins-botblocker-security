<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_maybe_upgrade_db() {
    wp_cache_delete( 'bbcs_db_version', 'options' );
    $installed = get_option( 'bbcs_db_version', '0' );

    if ( version_compare( $installed, '2.2.0', '>=' ) && ! bbcs_summary_table_ready() ) {
        bbcs_migration_2_2_0();
    }

    if ( version_compare( $installed, BOTBLOCKER_DB_VERSION, '>=' ) ) {
        return;
    }

    $migrations = [
        '2.2.0' => 'bbcs_migration_2_2_0',
        '2.3.0' => 'bbcs_migration_2_3_0',
        '2.4.0' => 'bbcs_migration_2_4_0',
    ];

    $is_existing = ( $installed !== '0' || bbcs_tablesExist() );
    if ( $is_existing ) {
        foreach ( $migrations as $version => $callback ) {
            if ( version_compare( $installed, $version, '<' ) && is_callable( $callback ) ) {
                call_user_func( $callback );
            }
        }
    }

    update_option( 'bbcs_db_version', BOTBLOCKER_DB_VERSION, true );
}

function bbcs_migration_2_2_0() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    bbcs_create_daily_summary_table();

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $tier_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->bbcs_settings} WHERE `key` = %s",
        'cloud_api_tier'
    ) );
    if ( $tier_count === 0 ) {
        $wpdb->insert(
            $wpdb->bbcs_settings,
            [
                'key'   => 'cloud_api_tier',
                'value' => '',
            ],
            [ '%s', '%s' ]
        );
    }

    if (!empty($wpdb->bbcs_self_ips)) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("DROP VIEW IF EXISTS `{$wpdb->bbcs_self_ips}`");
    }

    $has_data = (bool) $wpdb->get_var( "SELECT 1 FROM `{$wpdb->bbcs_hits}` LIMIT 1" );
    if ( $has_data && ! wp_next_scheduled( 'bbcs_summary_backfill' ) ) {
        wp_schedule_single_event( time() + 30, 'bbcs_summary_backfill' );
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

function bbcs_create_daily_summary_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->bbcs_daily_summary}` (
        `date_key` date NOT NULL,
        `metric` varchar(32) NOT NULL,
        `dim_key` varchar(128) NOT NULL DEFAULT '',
        `val` bigint NOT NULL DEFAULT 0,
        PRIMARY KEY  (date_key,metric,dim_key),
        KEY idx_metric_date (metric,date_key)
    ) $charset_collate;";

    dbDelta( $sql );
}

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

    // Googlebot: priority 1 -> 10
    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 10, 'comment' => 'GoogleBot (Catch-all)' ],
        [ 'search' => 'Googlebot' ],
        [ '%d', '%s' ],
        [ '%s' ]
    );

    // Google-InspectionTool: priority 7 -> 1, disable 1 -> 0
    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 1, 'disable' => 0 ],
        [ 'search' => 'Google-InspectionTool' ],
        [ '%d', '%d' ],
        [ '%s' ]
    );

    // Chrome-Lighthouse: priority 9 -> 1, disable 1 -> 0
    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 1, 'disable' => 0 ],
        [ 'search' => 'Chrome-Lighthouse' ],
        [ '%d', '%d' ],
        [ '%s' ]
    );

    // Mediapartners: priority 10 -> 1, disable 1 -> 0
    $wpdb->update(
        $wpdb->bbcs_se,
        [ 'priority' => 1, 'disable' => 0 ],
        [ 'search' => 'Mediapartners' ],
        [ '%d', '%d' ],
        [ '%s' ]
    );

    // Baiduspider: rule 'dark' -> 'allow'
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

	bbcs_generateAllFilesFromDb();

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
}

function bbcs_migration_2_4_0() {
    global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

    // INSERT new bots that were missing from the initial data set.
    $new_search_engines = array(
        array( 'priority' => 25, 'search' => 'Mail.RU_Bot',     'data' => '.mail.ru .smailru.net',                      'rule' => 'allow', 'comment' => 'Mail.ru crawler',                         'disable' => 0 ),
        array( 'priority' => 27, 'search' => 'TelegramBot',     'data' => 'asn:62041 asn:59930 asn:62014 asn:44907',    'rule' => 'allow', 'comment' => 'Telegram link preview (ASN-verified)',     'disable' => 0 ),
        array( 'priority' => 29, 'search' => 'Twitterbot',      'data' => '.twttr.com',                                  'rule' => 'allow', 'comment' => 'Twitter/X link preview',                  'disable' => 0 ),
        array( 'priority' => 40, 'search' => 'Slackbot',        'data' => '.slack.com',                                  'rule' => 'allow', 'comment' => 'Slack link expander',                     'disable' => 0 ),
        array( 'priority' => 42, 'search' => 'WhatsApp',        'data' => '.whatsapp.net .whatsapp.com',                 'rule' => 'allow', 'comment' => 'WhatsApp link preview',                   'disable' => 0 ),
        array( 'priority' => 44, 'search' => 'SkypeUriPreview', 'data' => '.skype.com',                                  'rule' => 'allow', 'comment' => 'Skype link preview',                      'disable' => 0 ),
    );

    $value_placeholders = array();
    $value_args         = array();
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

    // UPDATE existing entries: append asn: tokens to domain-only data fields.
    // Uses CONCAT only when the token is not already present to make it idempotent.
    $asn_updates = array(
        array( 'search' => 'Googlebot',  'token' => 'asn:15169' ),
        array( 'search' => 'bingbot',    'token' => 'asn:8075'  ),
        array( 'search' => 'msnbot',     'token' => 'asn:8075'  ),
        array( 'search' => 'yandex.com', 'token' => 'asn:13238' ),
    );

    foreach ( $asn_updates as $upd ) {
        // Check current data - skip if token already present.
        $current_data = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `data` FROM `{$wpdb->bbcs_se}` WHERE `search` = %s",
                $upd['search']
            )
        );

        if ( $current_data === null ) {
            continue; // Row does not exist - INSERT IGNORE above handles new rows.
        }

        // Idempotent: only append if not already in the data string.
        $tokens = preg_split( '/\s+/', trim( (string) $current_data ) );
        if ( ! in_array( $upd['token'], $tokens, true ) ) {
            $new_data = trim( $current_data ) . ' ' . $upd['token'];
            $wpdb->update(
                $wpdb->bbcs_se,
                array( 'data' => $new_data ),
                array( 'search' => $upd['search'] ),
                array( '%s' ),
                array( '%s' )
            );
        }
    }

    bbcs_generateAllFilesFromDb();

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
}