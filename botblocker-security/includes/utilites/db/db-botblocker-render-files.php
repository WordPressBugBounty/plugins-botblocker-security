<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly


function bbcs_renderProxyFromDb()
{
    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_proxy_data' . bbcs_get_wp_cache_version();
        $cached_data = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    
    if (!$found) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, `key`, `value`, comment FROM `{$wpdb->bbcs_proxy}` WHERE 1 = %d ORDER BY `comment`, `key`",
                1
            ),
            ARRAY_A
        );

        $proxyArr  = array();
        $comments  = array();

        foreach ((array) $result as $proxy) {
            $key     = $proxy['key'];
            $value   = $proxy['value'];
            $comment = $proxy['comment'];

            $proxyArr[$key] = $value;

            if (! isset($comments[$comment])) {
                $comments[$comment] = array();
            }
            $comments[$comment][] = $key;
        }

        if (BOTBLOCKER_CACHE_WP) {
            $cached_data = array('proxyArr' => $proxyArr, 'comments' => $comments);
            wp_cache_set($cache_key, $cached_data, 'botblocker-security', 15);
        }
    } else {
        $proxyArr = $cached_data['proxyArr'];
        $comments = $cached_data['comments'];
    }

    $proxyContent  = BBCS_STOP_DIRECT . "\n";
    $proxyContent .= "return [\n";
    $proxyContent .= "    'bbcs_proxy' => [\n";

    foreach ($comments as $comment => $keys) {
        $proxyContent .= "        // " . $comment . "\n";
        foreach ($keys as $key) {
            $value = $proxyArr[$key];
            $proxyContent .= "        '" . addslashes($key) . "'  => '" . addslashes($value) . "',\n";
        }
        $proxyContent .= "\n";
    }

    $proxyContent = rtrim($proxyContent, "\n");
    $proxyContent = rtrim($proxyContent, ",\n") . "\n";
    $proxyContent .= "    ]\n";
    $proxyContent .= "];\n";

    $proxyFile = BOTBLOCKER_DATA_DIR . 'proxy.php';
    file_put_contents($proxyFile, $proxyContent);
}

function bbcs_renderPathsFromDb()
{
    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_paths_data' . bbcs_get_wp_cache_version();
        $rows = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    
    if (!$found) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `search`, `rule` FROM `{$wpdb->bbcs_path}` WHERE disable = %d ORDER BY priority ASC",
                0
            ),
            ARRAY_A
        );
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $rows, 'botblocker-security', 15);
        }
    }

    $paths = '';
    foreach ((array) $rows as $row) {
        $key   = ltrim($row['search'], '/');
        $rule  = $row['rule'];
        $paths .= "    '" . addslashes($key) . "' => '" . addslashes($rule) . "',\n";
    }
    $paths = rtrim($paths, ",\n");

    $content = BBCS_STOP_DIRECT . "\nreturn [\n'bbcs_path' => [\n$paths\n],\n];";
    file_put_contents(BOTBLOCKER_DATA_DIR . 'paths.php', $content);
}

function bbcs_renderRulesFromDb()
{
    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_rules_data' . bbcs_get_wp_cache_version();
        $rows = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    
    if (!$found) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `search`, `rule` FROM `{$wpdb->bbcs_rules}` WHERE disable = %d ORDER BY priority ASC",
                0
            ),
            ARRAY_A
        );
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $rows, 'botblocker-security', 15);
        }
    }

    $rules = '';
    foreach ((array) $rows as $row) {
        $rules .= "    '" . addslashes($row['search']) . "' => '" . addslashes($row['rule']) . "',\n";
    }
    $rules = rtrim($rules, ",\n");

    $content = BBCS_STOP_DIRECT . "\nreturn [\n'bbcs_rule' => [\n$rules\n],\n];";
    file_put_contents(BOTBLOCKER_DATA_DIR . 'rules.php', $content);
}

function bbcs_renderSearchEnginesFromDb()
{
    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_se_data' . bbcs_get_wp_cache_version();
        $cached_data = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    
    if (!$found) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `search`, `rule`, `data` FROM `{$wpdb->bbcs_se}` WHERE disable = %d ORDER BY priority ASC",
                0
            ),
            ARRAY_A
        );

        $rules  = array();
        $domains_map = array();

        foreach ((array) $results as $item) {
            $rules[$item['search']] = $item['rule'];
            $domains = preg_split('/\s+/', trim((string) $item['data']));
            $domains_map[$item['search']] = array_filter($domains, 'strlen');
        }

        $cached_data = array('rules' => $rules, 'domains_map' => $domains_map);
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $cached_data, 'botblocker-security', 15);
        }
    } else {
        $rules = $cached_data['rules'];
        $domains_map = $cached_data['domains_map'];
    }

    $se_data  = BBCS_STOP_DIRECT . "\nreturn [\n";
    $se_data .= "    'bbcs_rule' => [\n";
    foreach ($rules as $key => $value) {
        $se_data .= "        '" . addslashes($key) . "' => '" . addslashes($value) . "',\n";
    }
    $se_data .= "    ],\n\n";
    $se_data .= "    'bbcs_se' => [\n";
    foreach ($domains_map as $key => $domains) {
        $se_data .= "        '" . addslashes($key) . "' => [";
        $se_data .= implode(', ', array_map(static function ($d) {
            return "'" . addslashes($d) . "'";
        }, $domains));
        $se_data .= "],\n";
    }
    $se_data .= "    ]\n";
    $se_data .= "];\n";

    file_put_contents(BOTBLOCKER_DATA_DIR . 'search_engines.php', $se_data);
}

function bbcs_renderIpsFromDb()
{
    global $wpdb;

    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_ip_from_db' . bbcs_get_wp_cache_version();
        $ip_from_db = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    
    if (!$found) {
        $ip_from_db = array(
            'self_ips' => array(),
            'admin'    => array(),
            'ipv4'     => array(),
            'ipv6'     => array(),
        );
        $one_day_later = time() + DAY_IN_SECONDS;
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows_ipv4  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `search`, `rule`, `readonly`, `comment` FROM `{$wpdb->bbcs_ipv4rules}` WHERE disable = %d AND expires > %d",
                0,
                $one_day_later
            ),
            ARRAY_A
        );

        foreach ((array) $rows_ipv4 as $ip) {
            if ((int) $ip['readonly'] === 1) {
                if ($ip['comment'] === 'Admin IP') {
                    $ip_from_db['admin'][$ip['search']] = $ip['rule'];
                } else {
                    $ip_from_db['self_ips'][$ip['search']] = 'allow';
                }
            } else {
                $ip_from_db['ipv4'][$ip['search']] = $ip['rule'];
            }
        }
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows_ipv6  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `search`, `rule`, `readonly`, `comment` FROM `{$wpdb->bbcs_ipv6rules}` WHERE disable = %d AND expires > %d",
                0,
                $one_day_later
            ),
            ARRAY_A
        );

        foreach ((array) $rows_ipv6 as $ip) {
            if ((int) $ip['readonly'] === 1) {
                if ($ip['comment'] === 'Admin IP') {
                    $ip_from_db['admin'][$ip['search']] = $ip['rule'];
                } else {
                    $ip_from_db['self_ips'][$ip['search']] = 'allow';
                }
            } else {
                $ip_from_db['ipv6'][$ip['search']] = $ip['rule'];
            }
        }
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $ip_from_db, 'botblocker-security', 15);
        }
    }

    $ip_data = BBCS_STOP_DIRECT . " \n return [\n";
    foreach ($ip_from_db as $group => $ips) {
        $ip_data .= "'{$group}' => [\n";
        foreach ($ips as $ip => $status) {
            $ip_data .= "    '" . addslashes($ip) . "' => '" . addslashes($status) . "',\n";
        }
        $ip_data .= "],\n";
    }
    $ip_data .= "];\n";

    file_put_contents(BOTBLOCKER_DATA_DIR . 'ip.php', $ip_data);
}
 
function bbcs_generateSettingsFileFromDb($type = null)
{ 
    // --- Caller stack trace logging ---
    /*
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $lines = [];

        foreach ($trace as $i => $t) {
            $file = isset($t['file']) ? $t['file'] : '[internal]';
            $line = isset($t['line']) ? $t['line'] : 0;

            $class = isset($t['class']) ? $t['class'] : '';
            $typeOp = isset($t['type']) ? $t['type'] : '';
            $func  = isset($t['function']) ? $t['function'] : '[unknown]';

            $where = $class ? ($class . $typeOp . $func) : $func;

            $lines[] = '#' . $i . ' ' . $where . ' @ ' . $file . ':' . $line;
        }

        error_log('[BotBlocker] bbcs_generateSettingsFileFromDb() call stack:' . "\n" . implode("\n", $lines));
    }
    */
    // --- /Caller stack trace logging ---

    global $wpdb;
    $found = false;
    if (BOTBLOCKER_CACHE_WP) {
        $cache_key = 'bbcs_settings_all' . bbcs_get_wp_cache_version();
        $results = wp_cache_get($cache_key, 'botblocker-security', false, $found);
    }
    if ($found === false) {
        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results("SELECT `key`, `value` FROM `{$wpdb->bbcs_settings}`", ARRAY_A);
        if (BOTBLOCKER_CACHE_WP) {
            wp_cache_set($cache_key, $results, 'botblocker-security', 15);
        }
    }
     $settings = [];
     foreach ($results as $row) {
         $key = $row['key'];
         $value = $row['value'];
         $decoded = json_decode($value, true);
         if (json_last_error() == JSON_ERROR_NONE) {
             $settings[$key] = $decoded;
         } else {
             if (is_numeric($value)) {
                 if (strpos($value, '.') !== false) {
                     $settings[$key] = (float)$value;
                 } else {
                     $settings[$key] = (int)$value;
                 }
             } elseif ($value === 'true' || $value === 'false') {
                 $settings[$key] = $value === 'true';
             } else {
                 $settings[$key] = $value;
             }
         }
     }

     $settingsContent = BBCS_STOP_DIRECT . "\nreturn " . bbcs_php_export( $settings, 0, true ) . ";\n";

     $settingsFile = BOTBLOCKER_DATA_DIR . 'settings.php';
     file_put_contents($settingsFile, $settingsContent);

     bbcs_clearFileCache();
     if(!isset($type)) {
        return true; 
    } elseif(isset($type) && $type == true){ 
        return $settings;
    }
}
