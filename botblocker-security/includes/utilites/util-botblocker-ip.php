<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_expandIPv6($ip)
{
    $ip = strstr($ip, '/', true) ?: $ip;
    $hex = unpack("H*hex", inet_pton($ip));
    $ip = substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);
    return $ip;
}

function bbcs_ipToNumeric($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return ip2long($ip);
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return (string) bbcs_gmp_import(inet_pton($ip));
    } else {
        return 0;
    }
}

function bbcs_ipv6_bin($ip) {
    return inet_pton($ip);
}

function bbcs_custom_gmp_import($data, $word_size = 1, $options = GMP_MSW_FIRST | GMP_BIG_ENDIAN)
{
    if ($word_size != 1) {
        throw new \Exception(esc_html("Unsupported word size: $word_size"));
    }

    $value = 0;
    $length = strlen($data);

    if ($options & GMP_MSW_FIRST) {
        if ($options & GMP_BIG_ENDIAN) {
            // MSB first
            for ($i = 0; $i < $length; $i++) {
                $value = ($value << 8) | ord($data[$i]);
            }
        } else {
            // MSB last
            for ($i = $length - 1; $i >= 0; $i--) {
                $value = ($value << 8) | ord($data[$i]);
            }
        }
    } else {
        if ($options & GMP_BIG_ENDIAN) {
            // LSB first
            for ($i = 0; $i < $length; $i++) {
                $value = ($value << 8) | ord($data[$length - 1 - $i]);
            }
        } else {
            // LSB last
            for ($i = $length - 1; $i >= 0; $i--) {
                $value = ($value << 8) | ord($data[$length - 1 - $i]);
            }
        }
    }

    return $value;
}

function bbcs_gmp_import($data)
{
    if (extension_loaded('gmp')) {
        return gmp_import($data);
    } else {
        return bbcs_custom_gmp_import($data);
    }
}

function bbcs_netMatch($network, $ip)
{
    $ip_arr = explode('/', $network);
    $network_long = ip2long($ip_arr[0]);
    $x = ip2long($ip_arr[1]);
    $mask =  long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
    $ip_long = ip2long($ip);
    return ($ip_long & $mask) == ($network_long & $mask);
}

function bbcs_isIpOrCidr($input) {
    $input = trim($input);

    if (strpos($input, '/') !== false) {
        $parts = explode('/', $input);
        if (filter_var($parts[0], FILTER_VALIDATE_IP)) {
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $prefix = intval($parts[1]);

                if (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return ($prefix >= 0 && $prefix <= 32) ? 'cidr' : 'invalid';
                } elseif (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    return ($prefix >= 0 && $prefix <= 128) ? 'cidr' : 'invalid';
                }
            }
        }
        return 'invalid';
    } 

    elseif (filter_var($input, FILTER_VALIDATE_IP)) {
        return 'ip';
    }
    
    return 'invalid';
}

function bbcs_IpRange($cidr)
{
    $range = array();
    $cidr = explode('/', trim($cidr));
    if (!isset($cidr[1])) {
        $range = array(0, 0, 0); 
    } elseif (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
        $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
    } elseif (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $addr_given_str = $cidr[0];
        $prefixlen = $cidr[1];
        $addr_given_bin = inet_pton($addr_given_str);
        $addr_given_hex = bin2hex($addr_given_bin);
        $addr_given_str = inet_ntop($addr_given_bin);
        $flexbits = 128 - $prefixlen;
        $addr_hex_first = $addr_given_hex;
        $addr_hex_last = $addr_given_hex;
        $pos = 31;
        while ($flexbits > 0) {
            $orig_first = substr($addr_hex_first, $pos, 1);
            $orig_last = substr($addr_hex_last, $pos, 1);
            $origval_first = hexdec($orig_first);
            $origval_last = hexdec($orig_last);
            $mask = 0xf << (min(4, $flexbits));
            $new_val_first = $origval_first & $mask;
            $new_val_last = $origval_last | (pow(2, min(4, $flexbits)) - 1);
            $new_first = dechex($new_val_first);
            $new_last = dechex($new_val_last);
            $addr_hex_first = substr_replace($addr_hex_first, $new_first, $pos, 1);
            $addr_hex_last = substr_replace($addr_hex_last, $new_last, $pos, 1);
            $flexbits -= 4;
            $pos -= 1;
        }

        $addr_bin_first = hex2bin($addr_hex_first);
        $addr_bin_last = hex2bin($addr_hex_last);
        $range[0] = inet_ntop($addr_bin_first);
        $range[1] = inet_ntop($addr_bin_last);
    } else {
        $range = array(0, 0, 0); 
    }
    return $range;
}

function bbcs_getPTR($ip, $time, $ttl)
{
    $BBCS = BotBlocker::getInstance();
    global $wpdb;

    $storage = bbcs_connectToRedisOrMMC();
    if ($storage !== null && $BBCS->settings->ptr_cache_in_db) {
        if ($BBCS->settings->memcached_enable == 1) {
            $cache_key = $BBCS->settings->memcached_prefix .BOTBLOCKER_SITE_CLEAR.'_PTR_'. md5($ip);
        } elseif ($BBCS->settings->redis_enable == 1) {
            $cache_key = $BBCS->settings->redis_prefix .BOTBLOCKER_SITE_CLEAR.'_PTR_'. md5($ip);
        }
        $cached_response = $storage->get($cache_key);
        if (is_array($cached_response) && isset($cached_response['ptr'])) {
            return $cached_response['ptr'];
        }
    }

    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // PTR is being cached in DB only if setting is enabled.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $get_ptr = $wpdb->get_row($wpdb->prepare("SELECT ptr, date FROM `{$wpdb->bbcs_ptrcache}` WHERE ip = %s", $ip), ARRAY_A);
    
    if (isset($get_ptr['ptr']) && $BBCS->settings->ptr_cache_in_db) {
        if ($storage !== null) {
            $storage->set($cache_key, ['ptr' => $get_ptr['ptr']], $ttl * 60);
        }
        return $get_ptr['ptr'];
    } else {
        $ptr = trim(preg_replace("/[^0-9a-z-.:]/", "", mb_strtolower(gethostbyaddr($ip), 'UTF-8')));
        if ($BBCS->settings->ptr_cache_in_db) {
            $time = $time + ($ttl * 60);
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT INTO `{$wpdb->bbcs_ptrcache}` (ip, ptr, date, etime) VALUES (%s, %s, %d, %s)
                ON DUPLICATE KEY UPDATE ptr = VALUES(ptr), date = VALUES(date), etime = VALUES(etime)",
                $ip, $ptr, $time, $ttl
            ));
        }
        if ($storage !== null && $BBCS->settings->ptr_cache_in_db) {
            $storage->set($cache_key, ['ptr' => $ptr], $ttl * 60);
        }
        return $ptr;
    }
}

function bbcs_clearExpiredPTRCache()
{
    global $wpdb;
    $BBCS = BotBlocker::getInstance();
    $time_limit = time() - $BBCS->settings->ptrcache_time;
    // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->bbcs_ptrcache}` WHERE date < %d", $time_limit));
}

function bbcs_testWhiteBot($ip, $ptr_ok, $time, $ttl)
{
    if (in_array('.', $ptr_ok)) {
        return true;
    } else {
        $ptr = bbcs_getPTR($ip, $time, $ttl); 
        if ($ptr === false) {
            $result = array();
        } else {
            $result = @dns_get_record($ptr, DNS_A + DNS_AAAA);
            if (!is_array($result)) {
                $result = array();
            }
        }
        $ip2 = array(); 
        if ($ptr == $ip) $ip2[] = $ip;
        foreach ($result as $line) {
            if (isset($line['ipv6'])) {
                $ip2[] = bbcs_expandIPv6($line['ipv6']);
            }
            if (isset($line['ip'])) {
                $ip2[] = $line['ip'];
            }
        }
        $test_ptr = 0;
        foreach ($ptr_ok as $ptr_line) {
            if ($ptr_line == '.') {
                $test_ptr = 1;
                break;
            }
            if (stripos($ptr, $ptr_line, 0) !== false) {
                $test_ptr = 1;
                break;
            }
        }
        if (in_array($ip, $ip2) and $test_ptr == 1) {
            return true;
        } else {
            return false;
        }
    }
}

function bbcs_normalizeIP($ip, $ip_version) {
    if ($ip_version == 4 || strpos($ip, ':') === false) {
        return $ip;
    }

    if ($ip_version == 6 && strpos($ip, ':') !== false) {
        $binary = @inet_pton($ip);
        if ($binary !== false) {
            return inet_ntop($binary);
        }
    }
    return $ip;
}
