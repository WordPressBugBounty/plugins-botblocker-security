<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_get_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    global $wpdb;

    $start  = isset( $_POST['start'] )  ? absint( wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint( wp_unslash( $_POST['draw'] ) )   : 0;
    $search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $records_total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}` WHERE 1 = %d", 1 )
    );

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $records_filtered = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}`
                 WHERE CAST(asnum AS CHAR) LIKE %s
                    OR asname LIKE %s
                    OR `rule` LIKE %s
                    OR comment LIKE %s",
                $like, $like, $like, $like
            )
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, asnum, asname, `rule`, comment, disable
                 FROM `{$wpdb->bbcs_asn}`
                 WHERE CAST(asnum AS CHAR) LIKE %s
                    OR asname LIKE %s
                    OR `rule` LIKE %s
                    OR comment LIKE %s
                 ORDER BY priority ASC
                 LIMIT %d, %d",
                $like, $like, $like, $like, $start, $length
            ),
            ARRAY_A
        );
    } else {
        $records_filtered = $records_total;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, priority, asnum, asname, `rule`, comment, disable
                 FROM `{$wpdb->bbcs_asn}`
                 WHERE 1 = %d
                 ORDER BY priority ASC
                 LIMIT %d, %d",
                1, $start, $length
            ),
            ARRAY_A
        );
    }

    $data = array();
    foreach ( (array) $results as $row ) {
        $data[] = array(
            'id'       => $row['id'],
            'priority' => $row['priority'],
            'asnum'    => $row['asnum'],
            'asname'   => $row['asname'],
            'rule'     => $row['rule'],
            'comment'  => $row['comment'],
            'disable'  => $row['disable'],
        );
    }

    wp_send_json( array(
        'draw'            => $draw,
        'recordsTotal'    => $records_total,
        'recordsFiltered' => $records_filtered,
        'data'            => $data,
    ) );
}
add_action( 'wp_ajax_bbcs_get_asn', 'bbcs_get_asn_callback' );

function bbcs_get_asn_details_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    global $wpdb;
    $id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM `{$wpdb->bbcs_asn}` WHERE id = %d", $id ),
        ARRAY_A
    );

    if ( $row ) {
        wp_send_json_success( $row );
    } else {
        wp_send_json_error( __( 'ASN rule not found.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_get_asn_details', 'bbcs_get_asn_details_callback' );

function bbcs_create_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    /* phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required = [ 'asnum', 'rule' ];
    foreach ( $required as $field ) {
        if ( ! isset( $_POST[ $field ] ) || $_POST[ $field ] === '' ) {
            wp_send_json_error( sprintf( __( 'Field \'%s\' is required.', 'botblocker-security' ), $field ) );
            return;
        }
    }

    global $wpdb;

    $asnum = absint( wp_unslash( $_POST['asnum'] ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}` WHERE asnum = %d",
        $asnum
    ) );

    if ( $exists ) {
        wp_send_json_error( __( 'ASN already exists.', 'botblocker-security' ) );
        return;
    }

    $data = array(
        'priority' => isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 50,
        'asnum'    => $asnum,
        'asname'   => isset( $_POST['asname'] ) ? sanitize_text_field( wp_unslash( $_POST['asname'] ) ) : '',
        'rule'     => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
        'comment'  => isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
        'disable'  => 0,
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->insert( $wpdb->bbcs_asn, $data );

    if ( $result !== false ) {
        bbcs_renderAsnFromDb();
        bbcs_clearFileCache();
        wp_send_json_success( __( 'ASN rule created successfully.', 'botblocker-security' ) );
    } else {
        wp_send_json_error( __( 'Failed to create ASN rule.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_create_asn', 'bbcs_create_asn_callback' );

function bbcs_update_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    /* phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
    $required = [ 'id', 'asnum', 'rule' ];
    foreach ( $required as $field ) {
        if ( ! isset( $_POST[ $field ] ) || $_POST[ $field ] === '' ) {
            wp_send_json_error( sprintf( __( 'Field \'%s\' is required.', 'botblocker-security' ), $field ) );
            return;
        }
    }

    global $wpdb;

    $id   = absint( wp_unslash( $_POST['id'] ) );
    $data = array(
        'priority' => isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 50,
        'asnum'    => absint( wp_unslash( $_POST['asnum'] ) ),
        'asname'   => isset( $_POST['asname'] ) ? sanitize_text_field( wp_unslash( $_POST['asname'] ) ) : '',
        'rule'     => sanitize_text_field( wp_unslash( $_POST['rule'] ) ),
        'comment'  => isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '',
    );
    /* phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated */

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update( $wpdb->bbcs_asn, $data, array( 'id' => $id ) );

    if ( $result !== false ) {
        bbcs_renderAsnFromDb();
        bbcs_clearFileCache();
        wp_send_json_success( __( 'ASN rule updated successfully.', 'botblocker-security' ) );
    } else {
        wp_send_json_error( __( 'Failed to update ASN rule.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_update_asn', 'bbcs_update_asn_callback' );

function bbcs_delete_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
        wp_send_json_error( __( 'ID is required for deletion.', 'botblocker-security' ) );
        return;
    }

    global $wpdb;
    $id = absint( wp_unslash( $_POST['id'] ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->delete( $wpdb->bbcs_asn, array( 'id' => $id ) );

    if ( $result !== false ) {
        bbcs_renderAsnFromDb();
        bbcs_clearFileCache();
        wp_send_json_success( __( 'ASN rule deleted successfully.', 'botblocker-security' ) );
    } else {
        wp_send_json_error( __( 'Failed to delete ASN rule.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_delete_asn', 'bbcs_delete_asn_callback' );

function bbcs_toggle_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    global $wpdb;
    $id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE `{$wpdb->bbcs_asn}` SET disable = 1 - disable WHERE id = %d",
            $id
        )
    );

    if ( false !== $result ) {
        bbcs_renderAsnFromDb();
        bbcs_clearFileCache();
        wp_send_json_success( __( 'ASN rule toggled successfully.', 'botblocker-security' ) );
    } else {
        wp_send_json_error( __( 'Failed to toggle ASN rule.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_toggle_asn', 'bbcs_toggle_asn_callback' );

function bbcs_export_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results( "SELECT * FROM `{$wpdb->bbcs_asn}`", ARRAY_A );
    wp_send_json_success( $rows );
}
add_action( 'wp_ajax_bbcs_export_asn', 'bbcs_export_asn_callback' );

function bbcs_import_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    if ( ! isset( $_POST['asn_rules'] ) || empty( $_POST['asn_rules'] ) ) {
        wp_send_json_error( __( 'ASN data is required for import.', 'botblocker-security' ) );
        return;
    }

    global $wpdb;
    $items = json_decode( sanitize_textarea_field( wp_unslash( $_POST['asn_rules'] ) ), true );

    if ( ! is_array( $items ) ) {
        wp_send_json_error( __( 'Invalid JSON format.', 'botblocker-security' ) );
        return;
    }

    $imported = 0;
    $skipped  = 0;
    foreach ( $items as $item ) {
        $asnum = isset( $item['asnum'] ) ? absint( $item['asnum'] ) : 0;
        if ( ! $asnum ) { $skipped++; continue; }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$wpdb->bbcs_asn}` WHERE asnum = %d",
            $asnum
        ) );

        if ( $exists == 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->insert( $wpdb->bbcs_asn, array(
                'priority' => isset( $item['priority'] ) ? absint( $item['priority'] ) : 50,
                'asnum'    => $asnum,
                'asname'   => isset( $item['asname'] ) ? sanitize_text_field( $item['asname'] ) : '',
                'rule'     => isset( $item['rule'] ) ? sanitize_text_field( $item['rule'] ) : 'block',
                'comment'  => isset( $item['comment'] ) ? sanitize_textarea_field( $item['comment'] ) : '',
                'disable'  => isset( $item['disable'] ) ? absint( $item['disable'] ) : 0,
            ) );
            if ( $result !== false ) { $imported++; }
        } else {
            $skipped++;
        }
    }

    bbcs_renderAsnFromDb();
    bbcs_clearFileCache();

    wp_send_json_success( array( 'imported' => $imported, 'skipped' => $skipped ) );
}
add_action( 'wp_ajax_bbcs_import_asn', 'bbcs_import_asn_callback' );

function bbcs_clear_all_asn_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $result = $wpdb->query( "TRUNCATE TABLE `{$wpdb->bbcs_asn}`" );

    if ( $result !== false ) {
        bbcs_renderAsnFromDb();
        bbcs_clearFileCache();
        wp_send_json_success( __( 'All ASN rules have been cleared.', 'botblocker-security' ) );
    } else {
        wp_send_json_error( __( 'Failed to clear ASN rules.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_clear_all_asn', 'bbcs_clear_all_asn_callback' );

function bbcs_asn_to_php_callback(): void {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if ( ! current_user_can( bbcs_can_manage() ) ) { wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) ); }
    try {
        bbcs_renderAsnFromDb();
        wp_send_json_success( __( 'ASN rules file generated successfully.', 'botblocker-security' ) );
    } catch ( \Throwable $e ) {
        wp_send_json_error( __( 'Failed to generate ASN rules file.', 'botblocker-security' ) );
    }
}
add_action( 'wp_ajax_bbcs_asn_to_php', 'bbcs_asn_to_php_callback' );
