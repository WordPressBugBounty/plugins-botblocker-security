<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_maybe_upgrade_db() {
    $installed = get_option( 'bbcs_db_version', '0' );

    if ( version_compare( $installed, '2.2.0', '>=' ) && ! bbcs_summary_table_ready() ) {
        bbcs_migration_2_2_0();
    }

    if ( version_compare( $installed, BOTBLOCKER_DB_VERSION, '>=' ) ) {
        return;
    }

    $migrations = [
        '2.2.0' => 'bbcs_migration_2_2_0',
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
