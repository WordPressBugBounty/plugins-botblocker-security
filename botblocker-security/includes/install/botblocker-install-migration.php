<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_get_migrations_registry() {
    return [
        '2.2.0' => [
            'file'     => 'migration-2-2-0.php',
            'callback' => 'bbcs_migration_2_2_0',
        ],
        '2.3.0' => [
            'file'     => 'migration-2-3-0.php',
            'callback' => 'bbcs_migration_2_3_0',
        ],
        '2.4.0' => [
            'file'     => 'migration-2-4-0.php',
            'callback' => 'bbcs_migration_2_4_0',
        ],
        '2.5.0' => [
            'file'     => 'migration-2-5-0.php',
            'callback' => 'bbcs_migration_2_5_0',
        ],
        '2.6.0' => [
            'file'     => 'migration-2-6-0.php',
            'callback' => 'bbcs_migration_2_6_0',
        ],
    ];
}

function bbcs_load_migration( $entry ) {
    $dir = dirname( __FILE__ ) . '/migrations/';
    $path = $dir . $entry['file'];
    if ( ! file_exists( $path ) ) {
        return false;
    }
    require_once $path;
    return is_callable( $entry['callback'] );
}

function bbcs_maybe_upgrade_db() {
    wp_cache_delete( 'bbcs_db_version', 'options' );
    $installed = get_option( 'bbcs_db_version', '0' );

    if ( version_compare( $installed, '2.2.0', '>=' ) && ! bbcs_summary_table_ready() ) {
        $registry = bbcs_get_migrations_registry();
        if ( isset( $registry['2.2.0'] ) && bbcs_load_migration( $registry['2.2.0'] ) ) {
            call_user_func( $registry['2.2.0']['callback'] );
        }
    }

    if ( version_compare( $installed, BOTBLOCKER_DB_VERSION, '>=' ) ) {
        return;
    }

    $migrations = bbcs_get_migrations_registry();

    $is_existing = ( $installed !== '0' || bbcs_tablesExist() );
    if ( $is_existing ) {
        foreach ( $migrations as $version => $entry ) {
            if ( version_compare( $installed, $version, '<' ) ) {
                if ( bbcs_load_migration( $entry ) ) {
                    call_user_func( $entry['callback'] );
                }
            }
        }
    }

    update_option( 'bbcs_db_version', BOTBLOCKER_DB_VERSION, true );
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