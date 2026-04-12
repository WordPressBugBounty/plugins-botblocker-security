<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once BOTBLOCKER_DIR . 'includes/utilites/db/db-botblocker-store.php';
include_once BOTBLOCKER_DIR . 'includes/utilites/db/db-botblocker-render-files.php';

function bbcs_storePTRrule() {
    $BBCS = BotBlocker::getInstance();
    global $wpdb;

    if ($BBCS->ip_version != 4 && $BBCS->ip_version != 6) {
        return false;
    }
    
    if($BBCS->ip_version == 4) {
        // REVIEWER NOTE: custom table operations require direct database queries.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing_entry = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv4rules}` WHERE search = %s",
                $BBCS->ip_short
            )
        );
    } else {
        // REVIEWER NOTE: custom table operations require direct database queries.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing_entry = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->bbcs_ipv6rules}` WHERE search = %s",
                $BBCS->ip_short
            )
        );
    }

    if ($existing_entry > 0) {
        return true; 
    } 

    $ips = bbcs_IpRange($BBCS->ip_short);

    if (isset($ips[2]) && $ips[2] === 0) {
        return false;
    }

    $data = array(
        'priority' => '10',
        'search' => sanitize_text_field($BBCS->ip_short),
        'rule' => 'allow',
        'comment' => sanitize_text_field($BBCS->useragent . ' (ip: ' . $BBCS->ip . ')'),
        'expires' => ($BBCS->time + 7776000), // TODO
    );

    if($BBCS->ip_version == 4) {
        $data['ip1'] = bbcs_ipToNumeric($ips[0]);
        $data['ip2'] = bbcs_ipToNumeric($ips[1]);
    } else {
        $data['ip1'] = bbcs_ipv6_bin(bbcs_expandIPv6($ips[0]));
        $data['ip2'] = bbcs_ipv6_bin(bbcs_expandIPv6($ips[1]));
    }

    /**
	 * REVIEWER NOTE: 
	 * - custom table operations require direct database queries.
     * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
     * - no user input is interpolated into this query.
     */
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    if($BBCS->ip_version == 4) {
        $result = $wpdb->insert($wpdb->bbcs_ipv4rules, $data);
    } else {
        $result = $wpdb->insert($wpdb->bbcs_ipv6rules, $data);
    }
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return ($result !== false);
}

function bbcs_toggleBBpower($state)
{
    global $wpdb;

    /**
     * REVIEWER NOTE:
     * - custom table operations require direct database queries.
     * - the table name is built from trusted parts and sanitized with sanitize_key() ($wpdb->prefix + constant).
     * - no user input is interpolated into this query.
     */
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $updated = $wpdb->update(
        $wpdb->bbcs_settings,
        ['value' => $state],
        ['key' => 'disable'],
        ['%d'],
        ['%s']
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    if ($updated !== false) {
        bbcs_generateSettingsFileFromDb();
    } else {
        if (BBCS_DEBUG == true) {
       // error_log('Error change security state of BotBlocker');
        }
    }
}

function bbcs_getIPNotLikeSQL()
{
    $BBCS = BotBlocker::getInstance();
    if (empty($BBCS->self_ips) || !is_array($BBCS->self_ips)) {
        return ['', []];
    }
    $ips = [];
    foreach ($BBCS->self_ips as $ip => $status) {
        if ($status === 'allow') {
            $ips[] = $ip;
        }
    }
    if (empty($ips)) {
        return ['', []];
    }
    $placeholders = implode(', ', array_fill(0, count($ips), '%s'));
    $fragment = " AND ip NOT IN ( $placeholders )";
    return [$fragment, $ips];
}

function bbcs_generateAllFilesFromDb(): bool
{
    bbcs_renderRulesFromDb();
    bbcs_renderPathsFromDb();
    bbcs_renderSearchEnginesFromDb();
    bbcs_renderAsnFromDb();
    bbcs_renderIpsFromDb();
    bbcs_renderProxyFromDb(); 

    bbcs_clearFileCache();
    return true;
}
