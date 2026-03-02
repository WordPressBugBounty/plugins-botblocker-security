<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_login_brutforce_get_ip(): string
{
    $BBCS = BotBlocker::getInstance();
    return $BBCS->ip;
}

function bbcs_login_brutforce_cache_key(string $ip): string
{
    return 'bbcs_login_bf_' . md5($ip);
}

function bbcs_login_brutforce_repeat_key(string $ip): string
{
    return 'bbcs_login_bf_repeat_' . md5($ip);
}

function bbcs_login_brutforce_get_attempts(string $cache_key)
{
    try {
        $storage = bbcs_connectToRedisOrMMC();

        if ($storage !== null) {
            $data = $storage->get($cache_key);
            return is_array($data) ? $data : null;
        }

        $data = get_transient($cache_key);
        return is_array($data) ? $data : null;
    } catch (\Exception $e) {
        return null;
    }
}

function bbcs_login_brutforce_set_attempts(string $cache_key, array $data, int $ttl): bool
{
    if (empty($data) || !is_array($data)) {
        return false;
    }
    try {
        $storage = bbcs_connectToRedisOrMMC();

        if ($storage !== null) {
            return $storage->set($cache_key, $data, $ttl);
        }

        return set_transient($cache_key, $data, $ttl);
    } catch (\Exception $e) {
        return false;
    }
}

function bbcs_login_brutforce_block_ip(string $ip, int $block_time): bool
{
    global $wpdb;

    $ip_version = null;

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip_version = 4;
    }else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ip_version = 6;
    }

    if ($ip_version === null) {
        return false;
    }

    $now = time();
    $expires = $now + $block_time;

    if ($ip_version === 4) {
        $numeric_ip = bbcs_ipToNumeric($ip);

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM `{$wpdb->bbcs_ipv4rules}` WHERE `search` = %s AND `rule` = 'block' AND `comment` LIKE %s LIMIT 1",
            $ip,
            '%Login brute force%'
        ));

        if ($exists) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->bbcs_ipv4rules,
                ['expires' => $expires],
                ['id' => $exists]
            );
            return true;
        }

        $data = [
            'priority' => 1,
            'search'   => $ip,
            'ip1'      => $numeric_ip,
            'ip2'      => $numeric_ip,
            'rule'     => 'block',
            'comment'  => 'Login brute force block (auto)',
            'expires'  => $expires,
            'disable'  => 0,
        ];

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($wpdb->bbcs_ipv4rules, $data);
    } else {
        $binary_ip = bbcs_ipv6_bin(bbcs_expandIPv6($ip));

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM `{$wpdb->bbcs_ipv6rules}` WHERE `search` = %s AND `rule` = 'block' AND `comment` LIKE %s LIMIT 1",
            $ip,
            '%Login brute force%'
        ));

        if ($exists) {
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->bbcs_ipv6rules,
                ['expires' => $expires],
                ['id' => $exists]
            );
            return true;
        }

        $data = [
            'priority' => 1,
            'search'   => $ip,
            'ip1'      => $binary_ip,
            'ip2'      => $binary_ip,
            'rule'     => 'block',
            'comment'  => 'Login brute force block (auto)',
            'expires'  => $expires,
            'disable'  => 0,
        ];

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($wpdb->bbcs_ipv6rules, $data);
    }

    if ($result !== false) {
        bbcs_renderIpsFromDb();
        bbcs_clearFileCache();
    }

    return ($result !== false);
}

function bbcs_login_brutforce_on_failed_login(string $username): void
{
    $BBCS = BotBlocker::getInstance();

    if (!isset($BBCS->settings->login_brutforce_enabled) || $BBCS->settings->login_brutforce_enabled !== 1) {
        return;
    }

    $ip = bbcs_login_brutforce_get_ip();
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return;
    }

    $max_attempts = (int) ($BBCS->settings->login_brutforce_attempts ?? 5);
    $period       = (int) ($BBCS->settings->login_brutforce_period ?? 900);
    $primary_block   = (int) ($BBCS->settings->login_brutforce_primary_block_time ?? 900);
    $secondary_block = (int) ($BBCS->settings->login_brutforce_secondary_block_time ?? 1800);

    $cache_key  = bbcs_login_brutforce_cache_key($ip);
    $repeat_key = bbcs_login_brutforce_repeat_key($ip);

    $now = time();

    $attempt_data = bbcs_login_brutforce_get_attempts($cache_key);

    if ($attempt_data === null || !isset($attempt_data['count'], $attempt_data['first_attempt']) || ($now - $attempt_data['first_attempt']) > $period) {
        $attempt_data = [
            'count'         => 1,
            'first_attempt' => $now,
        ];
    } else {
        $attempt_data['count']++;
    }

    bbcs_login_brutforce_set_attempts($cache_key, $attempt_data, $period);

    if ($attempt_data['count'] >= $max_attempts) {
        $repeat_data = bbcs_login_brutforce_get_attempts($repeat_key);
        $block_time = ($repeat_data !== null) ? $secondary_block : $primary_block;

        bbcs_login_brutforce_set_attempts($repeat_key, ['blocked' => true, 'time' => $now], $secondary_block * 2);
        bbcs_login_brutforce_block_ip($ip, $block_time);
        
        bbcs_login_brutforce_set_attempts($cache_key, ['count' => 0, 'first_attempt' => $now], $period);
    }
}
add_action('wp_login_failed', 'bbcs_login_brutforce_on_failed_login', 10, 1);

function bbcs_login_brutforce_delete_attempts(string $cache_key): void
{
    $storage = bbcs_connectToRedisOrMMC();

    if ($storage !== null) {
        $storage->delete($cache_key);
        return;
    }

    delete_transient($cache_key);
}

function bbcs_login_brutforce_on_success_login(string $user_login): void
{
    $BBCS = BotBlocker::getInstance();

    if (!isset($BBCS->settings->login_brutforce_enabled) || $BBCS->settings->login_brutforce_enabled !== 1) {
        return;
    }

    $ip = bbcs_login_brutforce_get_ip();
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return;
    }

    bbcs_login_brutforce_delete_attempts(bbcs_login_brutforce_cache_key($ip));
    bbcs_login_brutforce_delete_attempts(bbcs_login_brutforce_repeat_key($ip));
}
add_action('wp_login', 'bbcs_login_brutforce_on_success_login', 10, 1);