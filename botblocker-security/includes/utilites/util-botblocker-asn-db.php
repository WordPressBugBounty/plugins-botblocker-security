<?php
if (!defined('ABSPATH')) exit;

if (!defined('BBCS_ASN_DB_FILE')) {
    define('BBCS_ASN_DB_FILE', 'asn_database.mmdb');
}
if (!defined('BBCS_ASN_DB_FOLDER')) {
    define('BBCS_ASN_DB_FOLDER', 'asn_database');
}
if (!defined('BBCS_ASN_DB_FRESHNESS_DAYS')) {
    define('BBCS_ASN_DB_FRESHNESS_DAYS', 7);
}
if (!defined('BBCS_ASN_DB_MAX_SIZE')) {
    define('BBCS_ASN_DB_MAX_SIZE', 200 * 1024 * 1024);
}
if (!defined('BBCS_ASN_DB_MIN_SIZE')) {
    define('BBCS_ASN_DB_MIN_SIZE', 100 * 1024);
}
if (!defined('BBCS_ASN_DB_LOCK_TTL')) {
    define('BBCS_ASN_DB_LOCK_TTL', 30 * MINUTE_IN_SECONDS);
}
if (!defined('BBCS_ASN_DB_OPTION_STATUS')) {
    define('BBCS_ASN_DB_OPTION_STATUS', 'bbcs_asn_db_status');
}


function bbcs_asn_db_dir(): string
{
    $base = bbcs_uploads_dir();
    if (!is_string($base) || $base === '') {
        return '';
    }
    $dir = trailingslashit($base) . BBCS_ASN_DB_FOLDER . '/';
    if (!is_dir($dir)) {
        if (!wp_mkdir_p($dir)) {
            return '';
        }
    }
    bbcs_asn_db_write_protection($dir);
    return $dir;
}

function bbcs_asn_db_write_protection(string $dir): void
{
    $index = $dir . 'index.php';
    if (!file_exists($index)) {
        @file_put_contents($index, "<?php\n");
    }
    $htaccess = $dir . '.htaccess';
    if (!file_exists($htaccess)) {
        $rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
        @file_put_contents($htaccess, $rules);
    }
    $webconfig = $dir . 'web.config';
    if (!file_exists($webconfig)) {
        $rules = "<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n";
        @file_put_contents($webconfig, $rules);
    }
}

function bbcs_asn_db_path(): string
{
    $dir = bbcs_asn_db_dir();
    if ($dir === '') {
        return '';
    }
    return $dir . BBCS_ASN_DB_FILE;
}

function bbcs_asn_db_meta_path(): string
{
    $dir = bbcs_asn_db_dir();
    if ($dir === '') {
        return '';
    }
    return $dir . BBCS_ASN_DB_FILE . '.meta.json';
}

function bbcs_asn_db_is_present(): bool
{
    $path = bbcs_asn_db_path();
    if ($path === '' || !file_exists($path)) {
        return false;
    }
    $size = filesize($path);
    return is_int($size) && $size >= BBCS_ASN_DB_MIN_SIZE;
}

function bbcs_asn_db_get_status(): array
{
    $defaults = [
        'state'           => 'absent',
        'version'         => '',
        'database_type'   => '',
        'build_epoch'     => 0,
        'sha256'          => '',
        'size'            => 0,
        'downloaded_at'   => 0,
        'last_attempt_at' => 0,
        'last_error'      => '',
        'source_url'      => '',
        'failures'        => 0,
    ];
    $stored = get_option(BBCS_ASN_DB_OPTION_STATUS, []);
    if (!is_array($stored)) {
        $stored = [];
    }
    return array_merge($defaults, $stored);
}

function bbcs_asn_db_set_status(array $patch): void
{
    $current = bbcs_asn_db_get_status();
    foreach ($patch as $k => $v) {
        $current[$k] = $v;
    }
    update_option(BBCS_ASN_DB_OPTION_STATUS, $current, false);
}

function bbcs_asn_db_acquire_lock(): bool
{
    if (get_transient('bbcs_asn_db_lock')) {
        return false;
    }
    set_transient('bbcs_asn_db_lock', (string) time(), BBCS_ASN_DB_LOCK_TTL);
    return true;
}

function bbcs_asn_db_release_lock(): void
{
    delete_transient('bbcs_asn_db_lock');
}

function bbcs_asn_db_schedule_download(string $reason = '', int $delay_seconds = 60): bool
{
    $delay = max(10, $delay_seconds);
    $when  = time() + $delay;
    if (wp_next_scheduled('bbcs_asn_db_download_event', [$reason]) !== false) {
        return false;
    }
    return (bool) wp_schedule_single_event($when, 'bbcs_asn_db_download_event', [$reason]);
}

function bbcs_asn_db_endpoint_urls(): array
{
    $urls = [];
    if (defined('BOTBLOCKER_API_URL')) {
        $urls[] = untrailingslashit(BOTBLOCKER_API_URL) . '/botblocker?fetch_asn_database=1';
    }
    if (defined('BOTBLOCKER_API_GS_URL')) {
        $urls[] = untrailingslashit(BOTBLOCKER_API_GS_URL) . '/botblocker?fetch_asn_database=1';
    }
    return $urls;
}

function bbcs_asn_db_validate_file(string $path): array
{
    if (!file_exists($path)) {
        return ['ok' => false, 'error' => 'file_missing'];
    }
    $size = filesize($path);
    if (!is_int($size) || $size < BBCS_ASN_DB_MIN_SIZE) {
        return ['ok' => false, 'error' => 'file_too_small'];
    }
    if ($size > BBCS_ASN_DB_MAX_SIZE) {
        return ['ok' => false, 'error' => 'file_too_large'];
    }

    $marker = "\xAB\xCD\xEFMaxMind.com";
    $tail_len = min($size, 200 * 1024);
    $fp = @fopen($path, 'rb');
    if (!$fp) {
        return ['ok' => false, 'error' => 'file_unreadable'];
    }
    @fseek($fp, -$tail_len, SEEK_END);
    $tail = (string) @fread($fp, $tail_len);
    @fclose($fp);
    if (strpos($tail, $marker) === false) {
        return ['ok' => false, 'error' => 'invalid_marker'];
    }

    $reader_class = 'BotBlocker\\Vendor\\MaxMind\\Db\\Reader';
    if (!class_exists($reader_class)) {
        bbcs_asn_db_load_reader();
    }
    if (!class_exists($reader_class)) {
        return ['ok' => false, 'error' => 'reader_unavailable'];
    }

    try {
        $reader = new $reader_class($path);
        $metadata = $reader->metadata();
        $db_type = isset($metadata->databaseType) ? (string) $metadata->databaseType : '';
        $build_epoch = isset($metadata->buildEpoch) ? (int) $metadata->buildEpoch : 0;
        $probe = $reader->get('8.8.8.8');
        $reader->close();
        if ($probe !== null && !is_array($probe) && !is_scalar($probe)) {
            return ['ok' => false, 'error' => 'probe_failed'];
        }
        return [
            'ok'            => true,
            'database_type' => $db_type,
            'build_epoch'   => $build_epoch,
            'size'          => $size,
        ];
    } catch (\Throwable $e) {
        return ['ok' => false, 'error' => 'reader_exception:' . $e->getMessage()];
    }
}

function bbcs_asn_db_load_reader(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $base = trailingslashit(BOTBLOCKER_DIR) . 'vendor/MaxMindDb/';
    if (!is_dir($base)) {
        return;
    }
    $version_dirs = glob($base . '*', GLOB_ONLYDIR);
    if (!$version_dirs) {
        return;
    }
    usort($version_dirs, static function ($a, $b) {
        return version_compare(basename($b), basename($a));
    });
    foreach ($version_dirs as $dir) {
        $autoloader = $dir . '/standalone/autoloader.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
            $loaded = true;
            return;
        }
    }
}

function bbcs_asn_db_download(string $reason = ''): bool
{
    if (!bbcs_asn_db_acquire_lock()) {
        return false;
    }

    $now = time();
    $status = bbcs_asn_db_get_status();
    $status['last_attempt_at'] = $now;
    $status['last_reason'] = $reason;
    bbcs_asn_db_set_status($status);

    $dir = bbcs_asn_db_dir();
    if ($dir === '') {
        bbcs_asn_db_record_failure('uploads_dir_unavailable', '');
        bbcs_asn_db_release_lock();
        return false;
    }

    $final_path = $dir . BBCS_ASN_DB_FILE;
    $tmp_path   = $dir . BBCS_ASN_DB_FILE . '.download-' . wp_generate_password(8, false, false);

    $urls = bbcs_asn_db_endpoint_urls();
    if (empty($urls)) {
        bbcs_asn_db_record_failure('no_endpoints', '');
        bbcs_asn_db_release_lock();
        return false;
    }

    $last_error = 'unknown';
    $last_url   = '';

    foreach ($urls as $url) {
        $last_url = $url;
        if (file_exists($tmp_path)) {
            @unlink($tmp_path);
        }

        $download_result = BotBlockerWpRequest::download_to_file($url, $tmp_path);
        if (!is_array($download_result) || empty($download_result['ok'])) {
            $last_error = isset($download_result['error']) ? (string) $download_result['error'] : 'http_failed';
            if (file_exists($tmp_path)) {
                @unlink($tmp_path);
            }
            continue;
        }

        $validation = bbcs_asn_db_validate_file($tmp_path);
        if (empty($validation['ok'])) {
            $last_error = isset($validation['error']) ? (string) $validation['error'] : 'validation_failed';
            if (file_exists($tmp_path)) {
                @unlink($tmp_path);
            }
            continue;
        }

        $sha256 = hash_file('sha256', $tmp_path);

        if (file_exists($final_path)) {
            @unlink($final_path);
        }
        if (!@rename($tmp_path, $final_path)) {
            if (!@copy($tmp_path, $final_path)) {
                $last_error = 'swap_failed';
                @unlink($tmp_path);
                continue;
            }
            @unlink($tmp_path);
        }
        @chmod($final_path, 0644);

        $meta = [
            'database_type' => $validation['database_type'],
            'build_epoch'   => $validation['build_epoch'],
            'size'          => $validation['size'],
            'sha256'        => $sha256,
            'downloaded_at' => $now,
            'source_url'    => $url,
        ];
        @file_put_contents($dir . BBCS_ASN_DB_FILE . '.meta.json', wp_json_encode($meta));

        bbcs_asn_db_set_status([
            'state'         => 'ok',
            'database_type' => $validation['database_type'],
            'build_epoch'   => $validation['build_epoch'],
            'size'          => $validation['size'],
            'sha256'        => $sha256,
            'downloaded_at' => $now,
            'source_url'    => $url,
            'last_error'    => '',
            'failures'      => 0,
        ]);
        delete_transient('bbcs_asn_db_failed_alert');
        bbcs_asn_db_release_lock();
        bbcs_asn_db_reset_reader();

        return true;
    }

    bbcs_asn_db_record_failure($last_error, $last_url);
    if (file_exists($tmp_path)) {
        @unlink($tmp_path);
    }
    bbcs_asn_db_release_lock();
    return false;
}

function bbcs_asn_db_record_failure(string $error, string $url): void
{
    $status = bbcs_asn_db_get_status();
    $failures = (int) ($status['failures'] ?? 0) + 1;
    bbcs_asn_db_set_status([
        'state'      => bbcs_asn_db_is_present() ? 'stale' : 'error',
        'last_error' => $error,
        'source_url' => $url,
        'failures'   => $failures,
    ]);
    if (function_exists('bbcs_alerts_set_asn_db_failed')) {
        bbcs_alerts_set_asn_db_failed($error);
    }
    $backoff = min(6, $failures);
    $delay = max(HOUR_IN_SECONDS, $backoff * HOUR_IN_SECONDS);
    if ($failures < 7) {
        wp_schedule_single_event(time() + $delay, 'bbcs_asn_db_download_event', ['retry']);
    }
}

add_action('bbcs_asn_db_download_event', 'bbcs_asn_db_download', 10, 1);

function bbcs_asn_db_freshness_handler(): void
{
    $status = bbcs_asn_db_get_status();
    if (!bbcs_asn_db_is_present()) {
        bbcs_asn_db_schedule_download('cron-missing');
        return;
    }
    $age = time() - (int) ($status['downloaded_at'] ?? 0);
    if ($age > BBCS_ASN_DB_FRESHNESS_DAYS * DAY_IN_SECONDS) {
        bbcs_asn_db_schedule_download('cron-stale');
    }
}
add_action('bbcs_asn_db_freshness_task', 'bbcs_asn_db_freshness_handler');

function bbcs_asn_db_download_event_scheduled(): bool
{
    $crons = _get_cron_array();
    if (!is_array($crons)) {
        return false;
    }
    foreach ($crons as $events) {
        if (is_array($events) && isset($events['bbcs_asn_db_download_event'])) {
            return true;
        }
    }
    return false;
}

function bbcs_asn_db_self_heal(): void
{
    if (defined('DOING_CRON') && DOING_CRON) {
        return;
    }
    if (get_transient('bbcs_asn_db_self_heal_throttle')) {
        return;
    }
    set_transient('bbcs_asn_db_self_heal_throttle', '1', HOUR_IN_SECONDS);

    if (function_exists('bbcs_register_cron_tasks') && !wp_next_scheduled('bbcs_asn_db_freshness_task')) {
        bbcs_register_cron_tasks();
    }

    if (bbcs_asn_db_is_present()) {
        return;
    }
    if (bbcs_asn_db_download_event_scheduled()) {
        return;
    }
    bbcs_asn_db_schedule_download('runtime-missing', 60);
}
add_action('init', 'bbcs_asn_db_self_heal', 20);

function bbcs_asn_db_open(bool $reset = false)
{
    static $reader = null;
    if ($reset) {
        if (is_object($reader) && method_exists($reader, 'close')) {
            try { $reader->close(); } catch (\Throwable $e) {}
        }
        $reader = null;
        return null;
    }
    if ($reader !== null) {
        return $reader ?: null;
    }
    if (!bbcs_asn_db_is_present()) {
        $reader = false;
        return null;
    }
    bbcs_asn_db_load_reader();
    $reader_class = 'BotBlocker\\Vendor\\MaxMind\\Db\\Reader';
    if (!class_exists($reader_class)) {
        $reader = false;
        return null;
    }
    try {
        $reader = new $reader_class(bbcs_asn_db_path());
    } catch (\Throwable $e) {
        $reader = false;
        return null;
    }
    return $reader;
}

function bbcs_asn_db_reset_reader(): void
{
    bbcs_asn_db_open(true);
}

function bbcs_asn_db_lookup(string $ip): ?array
{
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return null;
    }
    $reader = bbcs_asn_db_open();
    if ($reader === null) {
        return null;
    }
    $record = null;
    $prefix_len = 0;
    try {
        if (method_exists($reader, 'getWithPrefixLen')) {
            $result = $reader->getWithPrefixLen($ip);
            if (is_array($result) && array_key_exists(0, $result)) {
                $record = $result[0];
                $prefix_len = isset($result[1]) ? (int) $result[1] : 0;
            }
        } else {
            $record = $reader->get($ip);
        }
    } catch (\Throwable $e) {
        return null;
    }
    if (!is_array($record) || empty($record)) {
        return null;
    }
    $country_code   = isset($record['country'])        ? (string) $record['country']        : '';
    $country_name   = isset($record['country_name'])   ? (string) $record['country_name']   : '';
    $continent_code = isset($record['continent'])      ? (string) $record['continent']      : '';
    $continent_name = isset($record['continent_name']) ? (string) $record['continent_name'] : '';
    $asn_raw        = $record['asn'] ?? '';
    $as_name        = isset($record['as_name'])        ? (string) $record['as_name']        : '';
    $as_domain      = isset($record['as_domain'])      ? (string) $record['as_domain']      : '';
    $asn = '';
    if ($asn_raw !== '' && $asn_raw !== null) {
        $asn = preg_replace('/[^0-9]/', '', (string) $asn_raw);
    }
    $cidr = '';
    if ($prefix_len > 0) {
        $network_ip = bbcs_asn_db_network_address($ip, $prefix_len);
        if ($network_ip !== '') {
            $cidr = $network_ip . '/' . $prefix_len;
        }
    }
    return [
        'country'        => $country_code !== '' ? $country_code : BOTBLOCKER_EMPTY,
        'country_name'   => $country_name,
        'continent'      => $continent_name !== '' ? $continent_name : $continent_code,
        'continent_code' => $continent_code,
        'asn'            => $asn !== '' ? $asn : BOTBLOCKER_EMPTY,
        'as_name'        => $as_name !== '' ? $as_name : BOTBLOCKER_EMPTY,
        'as_domain'      => $as_domain,
        'cidr'           => $cidr !== '' ? $cidr : BOTBLOCKER_EMPTY,
    ];
}

function bbcs_asn_db_network_address(string $ip, int $prefix_len): string
{
    $packed = @inet_pton($ip);
    if ($packed === false) {
        return '';
    }
    $bits = strlen($packed) * 8;
    if ($prefix_len < 0 || $prefix_len > $bits) {
        return '';
    }
    $bytes = (int) floor($prefix_len / 8);
    $remainder = $prefix_len % 8;
    $masked = substr($packed, 0, $bytes);
    if ($remainder > 0 && $bytes < strlen($packed)) {
        $mask = chr((0xFF << (8 - $remainder)) & 0xFF);
        $masked .= $packed[$bytes] & $mask;
    }
    $masked = str_pad($masked, strlen($packed), "\x00");
    $addr = @inet_ntop($masked);
    return is_string($addr) ? $addr : '';
}

function bbcs_asn_db_is_downloading(): bool
{
    if (get_transient('bbcs_asn_db_lock')) {
        return true;
    }
    $hooks = ['bbcs_asn_db_download_event'];
    foreach ($hooks as $hook) {
        $crons = _get_cron_array();
        if (!is_array($crons)) {
            return false;
        }
        foreach ($crons as $events) {
            if (is_array($events) && isset($events[$hook])) {
                return true;
            }
        }
    }
    return false;
}
