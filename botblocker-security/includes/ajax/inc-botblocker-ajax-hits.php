<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Helper function to handle the common logic for retrieving bot blocker hits.
 *
 * @since 1.0.0
 *
 * @param string $where Additional WHERE conditions for the query.
 * @return void
 */
function bbcs_get_botblocker_hits( $where ) {
    /**
     * REVIEWER NOTE:
     * - Table names are sanitized identifiers; $wpdb->prepare() cannot bind identifiers.
     * - `$where` is built internally (no direct user input). Extra search term is sanitized with esc_like() and placeholders.
     * - UNION + subquery force us to keep the SQL in variables; WPCS treats this as “NotPrepared” even though VALUES are bound.
     * - Suppression is scoped only around those lines.
     */
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    global $wpdb;
    $BBCS = BotBlocker::getInstance();

    $start  = isset( $_POST['start'] )  ? absint( wp_unslash( $_POST['start'] ) )  : 0;
    $length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 10;
    $draw   = isset( $_POST['draw'] )   ? absint( wp_unslash( $_POST['draw'] ) )   : 0;

    $search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

    $gmt_offset = isset( $BBCS->settings->admin_gmt_offset ) ? (float) $BBCS->settings->admin_gmt_offset : 0;

    if ( $search !== '' ) {
        $like            = '%' . $wpdb->esc_like( $search ) . '%';
        $search_condition = $wpdb->prepare(
            "(ip LIKE %s OR ptr LIKE %s OR lang LIKE %s OR useragent LIKE %s OR country LIKE %s OR referer LIKE %s OR page LIKE %s)",
            $like, $like, $like, $like, $like, $like, $like
        );
        $where = trim( $where );
        if ( $where === '' ) {
            $where = 'WHERE ' . $search_condition;
        } else {
            $where .= ' AND ' . $search_condition;
        }
    }

    $union_query = "
        SELECT date, cid, ip, ptr, asnum, asname, lang, name_lang, country_name, useragent,
               js_w, js_h, js_cw, js_ch, js_co, js_pi, adblock, country, referer, page,
               passed, result, method, browser, os, device
        FROM (
            SELECT * FROM `{$wpdb->bbcs_hits}`
            UNION ALL
            SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
        ) AS combined_hits
        {$where}
    ";

    $total_query = "SELECT COUNT(*) FROM ({$union_query}) AS total_count";
    // REVIEWER NOTE: Query string is dynamically built from sanitized input data.
    // $where is constructed internally by the plugin and all user input is sanitized 
    // (esc_like, absint, sanitize_text_field) and values are prepared where possible. 
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $total       = (int) $wpdb->get_var( $total_query );

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = wp_cache_get('bbcs_ajax_hits_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_hits' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5(wp_json_encode([
            'where' => $where,
            'start' => $start,
            'length' => $length,
            'search' => $search
        ]));
        $results = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }

    if ($found === false) {
        $query = "{$union_query} ORDER BY date DESC LIMIT {$start}, {$length}";
        // REVIEWER NOTE: Query string is dynamically built from sanitized input data.
        // $where is constructed internally by the plugin and all user input is sanitized 
        // (esc_like, absint, sanitize_text_field) and values are prepared where possible. 
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results( $query, ARRAY_A );
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $results, 'botblocker-security', 15);
        }
    }

    $data = [];
    foreach ( (array) $results as $row ) {
        $datetime = new \DateTime( "@{$row['date']}", new \DateTimeZone( 'UTC' ) );

        if ( $gmt_offset !== 0.0 ) {
            $hours    = floor( abs( $gmt_offset ) );
            $minutes  = ( abs( $gmt_offset ) * 60 ) % 60;
            $interval = new \DateInterval( 'PT' . $hours . 'H' . $minutes . 'M' );
            if ( $gmt_offset > 0 ) {
                $datetime->add( $interval );
            } else {
                $datetime->sub( $interval );
            }
        }

        $data[] = [
            'datetime' => [
                'm'    => $row['method'],
                'date' => $datetime->format( 'Y-m-d' ),
                'time' => $datetime->format( 'H:i:s' ),
            ],
            'ip_info' => [ 'ip' => $row['ip'], 'ptr' => $row['ptr'] ],
            'as_info' => [ 'asnum' => $row['asnum'], 'asname' => $row['asname'] ],
            'c_info'  => [
                'c'  => $row['country'],
                'cn' => $row['country_name'],
                'l'  => $row['lang'],
                'ln' => $row['name_lang'],
            ],
            'u_info' => [
                'br' => $row['browser'],
                'os' => $row['os'],
                'd'  => $row['device'],
                'ua' => $row['useragent'],
            ],
            'p_info' => [ 'p' => $row['page'], 'r' => $row['referer'] ],
            'js_info' => [
                'js_w'  => $row['js_w']  !== '0' ? $row['js_w']  : BOTBLOCKER_EMPTY,
                'js_h'  => $row['js_h']  !== '0' ? $row['js_h']  : BOTBLOCKER_EMPTY,
                'js_cw' => $row['js_cw'] !== '0' ? $row['js_cw'] : BOTBLOCKER_EMPTY,
                'js_ch' => $row['js_ch'] !== '0' ? $row['js_ch'] : BOTBLOCKER_EMPTY,
                'js_co' => $row['js_co'] !== '0' ? $row['js_co'] : BOTBLOCKER_EMPTY,
                'js_pi' => $row['js_pi'] !== '0' ? $row['js_pi'] : BOTBLOCKER_EMPTY,
                'ad'    => $row['adblock'] !== '0' ? $row['adblock'] : BOTBLOCKER_EMPTY,
                'cid'   => $row['cid'] !== '' ? $row['cid'] : BOTBLOCKER_EMPTY,
            ],
            'r_info' => [
                'passed' => $row['passed'],
                'pi'     => bbcs_codeList( $row['passed'] )['msg'],
                'result' => $row['result'],
            ],
        ];
    }

    wp_send_json( [
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $total,
        'data'            => $data,
    ] );
}

/**
 * AJAX handler for retrieving bot blocker hits data.
 */
function bbcs_get_botblocker_hits_callback() {
    $where = "WHERE " . BBCS_SQL_PAGE_NOT_LIKE . " AND method = 'GET'";
    $where .= bbcs_getIPNotLikeSQL();
    bbcs_get_botblocker_hits($where);
}
add_action('wp_ajax_bbcs_get_botblocker_hits', 'bbcs_get_botblocker_hits_callback');

/**
 * AJAX handler for retrieving bot blocker admin hits data.
 */
function bbcs_get_botblocker_admin_hits_callback() {
    $where = "WHERE " . BBCS_SQL_PAGE_LIKE_ADMIN . " AND " . BBCS_SQL_PAGE_NOT_LIKE_WP . " AND method = 'GET'";
    bbcs_get_botblocker_hits($where);
}
add_action('wp_ajax_bbcs_get_botblocker_admin_hits', 'bbcs_get_botblocker_admin_hits_callback');

/**
 * AJAX handler for retrieving other bot blocker hits data.
 */
function bbcs_get_botblocker_other_hits_callback() {
    $where = "WHERE " . BBCS_SQL_PAGE_NOT_LIKE_ADMIN . " AND " . BBCS_SQL_PAGE_LIKE_WP . " AND method = 'GET'";
    bbcs_get_botblocker_hits($where);
}
add_action('wp_ajax_bbcs_get_botblocker_other_hits', 'bbcs_get_botblocker_other_hits_callback');

/**
 * AJAX handler for retrieving all bot blocker hits data.
 */
function bbcs_get_botblocker_all_hits_callback() {
    $where = '';
    bbcs_get_botblocker_hits($where);
}
add_action('wp_ajax_bbcs_get_botblocker_all_hits', 'bbcs_get_botblocker_all_hits_callback');

function bbcs_get_botblocker_hits_data_for_modal_callback() {
    check_ajax_referer( 'botblocker_nonce', 'nonce' );
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    if ( empty( $_POST['cid'] ) ) {
        wp_send_json_error( 'CID is not set or empty' );
    }

    $cid = sanitize_text_field( wp_unslash( $_POST['cid'] ) );

    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_version = 1;
        $cache_version = wp_cache_get('bbcs_ajax_hits_cache_version', 'botblocker-security') ?: 1;
        $cache_key = 'bbcs_hits_modal' . bbcs_get_wp_cache_version() . $cache_version . '_' . md5($cid);
        $results = false;
        $results = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT ip, ptr, useragent, asname, asnum, country_name, name_lang, referer
                FROM (
                    SELECT * FROM `{$wpdb->bbcs_hits}`
                    UNION ALL
                    SELECT * FROM `{$wpdb->bbcs_hits_suspicious}`
                ) AS combined_hits
                WHERE cid = %s
                ORDER BY date DESC
                ",
                $cid
            ),
            ARRAY_A
        );
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $results, 'botblocker-security', 15);
        }
    }

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_bbcs_get_botblocker_hits_data_for_modal', 'bbcs_get_botblocker_hits_data_for_modal_callback' );

function bbcs_detectIPType($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return 'IPv4';
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return 'IPv6';
    } else {
        return 'invalid';
    }
}

function bbcs_hit_to_rule_callback()
{
    check_ajax_referer('botblocker_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    if (
        !isset($_POST['type']) || empty($_POST['type']) ||
        !isset($_POST['data']) || empty($_POST['data'])
    ) {
        wp_send_json_error('Type or data is not set or empty');
        return;
    }

    $type = sanitize_text_field(wp_unslash($_POST['type']));
    if ($type == 'ip') {
        $ip = sanitize_text_field(wp_unslash($_POST['data']));
        $typeIP = bbcs_detectIPType($ip);

        if($typeIP === 'invalid') {
            wp_send_json_error('Invalid IP address provided');
            return;
        }

        if ($typeIP === 'IPv4') {
            // For IPv4
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            do_action('wp_ajax_bbcs_create_ipv4_rule');
        } else{
            // For IPv6
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            do_action('wp_ajax_bbcs_create_ipv6_rule');
        }

    } else{
        // For other types
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        do_action('wp_ajax_bbcs_create_rule');
    }    
}
add_action('wp_ajax_bbcs_hit_to_rule', 'bbcs_hit_to_rule_callback');
