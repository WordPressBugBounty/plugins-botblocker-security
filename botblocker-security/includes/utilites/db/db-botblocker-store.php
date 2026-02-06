<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

function bbcs_storeData($reason_message = null, $reason = null)
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    if ( 
        (BotBlockerWpUtility::is_admin_area_page($BBCS->save_page) && $BBCS->settings->botblocker_log_admin !== 1) || 
        (BotBlockerWpUtility::is_wordpress_system_page($BBCS->save_page) && $BBCS->settings->botblocker_log_wp !== 1) ||
        (BotBlockerWpUtility::is_botblocker_page() && $BBCS->settings->botblocker_log_bbcs !== 1) 
    ) return;

    $result = $reason_message ?? $BBCS->result_of_action ?? BOTBLOCKER_EMPTY;
    $BBCS->result_of_action = sanitize_text_field($result);
    
    if (is_numeric($reason)) {
        $BBCS->reason_for_action = (int)$reason;
    }

    $reason = is_numeric($reason) ? (int)$reason : null;
    $code = bbcs_codeList($reason);
    $table_name = $wpdb->bbcs_hits_suspicious;
    if ($code['allow']) {
        $table_name = $wpdb->bbcs_hits;
    }

    $execution_time = round(microtime(true) - $BBCS->time, 3);
    $query_data = [
        'cid' => sanitize_text_field($BBCS->cid ?? BOTBLOCKER_EMPTY),
        'date' => $BBCS->time,
        'ip' => sanitize_text_field($BBCS->ip ?? BOTBLOCKER_EMPTY),
        'ptr' => sanitize_text_field($BBCS->ptr ?? BOTBLOCKER_EMPTY),
        'useragent' => sanitize_text_field($BBCS->useragent ?? BOTBLOCKER_EMPTY),
        'uid' => sanitize_text_field($BBCS->uid ?? BOTBLOCKER_EMPTY),
        'country' => sanitize_text_field($BBCS->country ?? BOTBLOCKER_EMPTY),
        'country_name' => sanitize_text_field($BBCS->country_name ?? BOTBLOCKER_EMPTY),
        'referer' => sanitize_text_field($BBCS->save_referer ?? BOTBLOCKER_EMPTY),
        'page' => sanitize_text_field($BBCS->save_page ?? BOTBLOCKER_EMPTY),
        'lang' => sanitize_text_field($BBCS->lang ?? BOTBLOCKER_EMPTY), 
        'accept_lang' => sanitize_text_field($BBCS->accept_lang ?? BOTBLOCKER_EMPTY), 
        'name_lang' => sanitize_text_field($BBCS->name_lang ?? BOTBLOCKER_EMPTY),
        'generation' => $execution_time,
        'passed' => sanitize_text_field($BBCS->reason_for_action ?? BOTBLOCKER_EMPTY),
        'recaptcha' => sanitize_text_field($BBCS->recaptcha ?? BOTBLOCKER_EMPTY),
        'js_w' => sanitize_text_field($BBCS->js_w ?? BOTBLOCKER_EMPTY),
        'js_h' => sanitize_text_field($BBCS->js_h ?? BOTBLOCKER_EMPTY),
        'js_cw' => sanitize_text_field($BBCS->js_cw ?? BOTBLOCKER_EMPTY),
        'js_ch' => sanitize_text_field($BBCS->js_ch ?? BOTBLOCKER_EMPTY),
        'js_co' => sanitize_text_field($BBCS->js_co ?? BOTBLOCKER_EMPTY),
        'js_pi' => sanitize_text_field($BBCS->js_pi ?? BOTBLOCKER_EMPTY),
        'refhost' => sanitize_text_field($BBCS->refhost ?? BOTBLOCKER_EMPTY),
        'adblock' => sanitize_text_field($BBCS->post_adblocker_foundlock ?? BOTBLOCKER_EMPTY),
        'asnum' => sanitize_text_field($BBCS->asnum ?? BOTBLOCKER_EMPTY),
        'asname' => sanitize_text_field($BBCS->asname ?? BOTBLOCKER_EMPTY),
        'result' => $BBCS->result_of_action,
        'http_accept' => sanitize_text_field($BBCS->http_accept ?? BOTBLOCKER_EMPTY),
        'method' => sanitize_text_field($BBCS->request_method ?? BOTBLOCKER_EMPTY),
        'ym_uid' => sanitize_text_field($BBCS->ym_uid ?? BOTBLOCKER_EMPTY),
        'ga_uid' => sanitize_text_field($BBCS->ga_uid ?? BOTBLOCKER_EMPTY),
        'ip_short' => sanitize_text_field($BBCS->ip_short ?? BOTBLOCKER_EMPTY),
        'hosting' => sanitize_text_field($BBCS->hosting ?? BOTBLOCKER_EMPTY),
        'hit' => sanitize_text_field($BBCS->cookie_hits_counter ?? BOTBLOCKER_EMPTY),
        'browser' => sanitize_text_field($BBCS->browser ?? BOTBLOCKER_EMPTY),
        'os' => sanitize_text_field($BBCS->os ?? BOTBLOCKER_EMPTY),
        'device' => sanitize_text_field($BBCS->device ?? BOTBLOCKER_EMPTY),
        'fp' => sanitize_text_field($BBCS->post_antidetect_scope ?? BOTBLOCKER_EMPTY),
        'wbot' => sanitize_text_field($BBCS->white_bot ?? BOTBLOCKER_EMPTY)
    ];

    /**
     * REVIEWER NOTE: 
     * - custom table operations require direct database queries.
     * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant). $wpdb->prepare() cannot bind identifiers.
     * - no user input is interpolated into this query.
     */
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE cid = %s", $BBCS->cid));
    if ($existing > 0) {
        $wpdb->update($table_name, $query_data, ['cid' => $BBCS->cid]);
    } else {
        $wpdb->insert($table_name, $query_data);
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

function bbcs_local_check_result($status, $msg, $cookie)
{
     global $wpdb;
     $BBCS = BotBlocker::getInstance();

    $safe_msg = sanitize_text_field( (string) $msg );

    if ( $status === 'cookie' ) {
        $payload = array( 'cookie' => (string) $cookie );
        $passed  = 1;
        if ( $safe_msg === 'ALLOW By rule: timezone=' . $BBCS->post_timezone ) {
            $passed = 4;
        }
        $result  = $safe_msg;
    } else {
        $passed = 9;
        $result = $safe_msg;

        if ( $safe_msg === 'BLOCK By rule: timezone=' . $BBCS->post_timezone ) {
            $safe_msg = $BBCS->js_error_message; 
            $passed   = 6;
        }

        $payload = array( 'error' => $safe_msg );
    }
   
     if ($BBCS->settings->botblocker_log_tests == 1) {
         $data_fields = [
             'ipv4'        => !empty($BBCS->post_ipv4_value) ? $BBCS->post_ipv4_value : BOTBLOCKER_EMPTY,
             'generation2' => round(microtime(true) - $BBCS->post_start_time, 4),
             'recaptcha'   => !empty($BBCS->post_recaptcha_score) ? $BBCS->post_recaptcha_score : BOTBLOCKER_EMPTY,
             'js_w'        => !empty($BBCS->post_width) ? $BBCS->post_width : BOTBLOCKER_EMPTY,
             'js_h'        => !empty($BBCS->post_height) ? $BBCS->post_height : BOTBLOCKER_EMPTY,
             'js_cw'       => !empty($BBCS->post_client_width) ? $BBCS->post_client_width : BOTBLOCKER_EMPTY,
             'js_ch'       => !empty($BBCS->post_client_height) ? $BBCS->post_client_height : BOTBLOCKER_EMPTY,
             'js_co'       => !empty($BBCS->post_color_depth) ? $BBCS->post_color_depth : BOTBLOCKER_EMPTY,
             'js_pi'       => !empty($BBCS->post_pixel_depth) ? $BBCS->post_pixel_depth : BOTBLOCKER_EMPTY,
             'adblock'     => !empty($BBCS->post_adblocker_found) ? $BBCS->post_adblocker_found : BOTBLOCKER_EMPTY,
             'timezone'    => !empty($BBCS->post_timezone) ? $BBCS->post_timezone : BOTBLOCKER_EMPTY,
             'passed'      => !empty($passed) ? $passed : BOTBLOCKER_EMPTY,
             'result'      => $result != 'gray' ? $result : BOTBLOCKER_EMPTY,
             'cloud_data'    => !empty($BBCS->cloud_data)
                 ? wp_json_encode($BBCS->cloud_data, JSON_UNESCAPED_UNICODE)
                 : BOTBLOCKER_EMPTY,
             'fp'          => !empty($BBCS->post_antidetect_scope)
                 ? wp_json_encode($BBCS->post_antidetect_scope, JSON_UNESCAPED_UNICODE)
                 : BOTBLOCKER_EMPTY,
         ];
 
         foreach ($data_fields as $key => $value) {
             if (empty($value)) {
                 $data_fields[$key] = BOTBLOCKER_EMPTY;
             }
         }
 
         $data = array_map('esc_sql', $data_fields);

         /**
          * REVIEWER NOTE:
          * - custom table operations require direct database queries.
          * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
          * - no user input is interpolated into this query.
          */
         // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
         $update = $wpdb->update(
             $wpdb->bbcs_hits,
             $data,
             array('cid' => esc_sql($BBCS->cid))
         );
         
         if ($wpdb->rows_affected == 0) {
             $wpdb->update(
                 $wpdb->bbcs_hits_suspicious,
                 $data,
                 array('cid' => esc_sql($BBCS->cid))
             );
         }
         // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        // bbcs_process_hit($passed);
     }
 
     return $payload;
}
