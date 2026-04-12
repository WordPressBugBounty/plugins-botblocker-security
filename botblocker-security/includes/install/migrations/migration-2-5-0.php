<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_migration_2_5_0() {
    global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    bbcs_create_asn_table();
    bbcs_insertDefaultAsn();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, `search`, `data` FROM `{$wpdb->bbcs_se}` WHERE `data` LIKE %s",
            '%asn:8075%'
        ),
        ARRAY_A
    );

    foreach ( $rows as $row ) {
        $tokens = preg_split( '/\s+/', trim( $row['data'] ) );
        $tokens = array_filter( $tokens, function ( $t ) {
            return $t !== 'asn:8075';
        } );
        $new_data = implode( ' ', $tokens );
        $wpdb->update(
            $wpdb->bbcs_se,
            array( 'data' => $new_data ),
            array( 'id' => $row['id'] ),
            array( '%s' ),
            array( '%d' )
        );
    }

    bbcs_generateAllFilesFromDb();

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
