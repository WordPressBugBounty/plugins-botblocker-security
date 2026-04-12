<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!defined('GMP_MSW_FIRST')) {
    define('GMP_MSW_FIRST', 1);
}
if (!defined('GMP_LSW_FIRST')) {
    define('GMP_LSW_FIRST', 2);
}
if (!defined('GMP_BIG_ENDIAN')) {
    define('GMP_BIG_ENDIAN', 4);
}
if (!defined('GMP_LITTLE_ENDIAN')) {
    define('GMP_LITTLE_ENDIAN', 8);
}

class BotBlockerEnv
{

    public static function check_favicon()
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $root = get_home_path();

        return file_exists($root . 'favicon.ico');
    }

    public static function check_curl()
    {
        return extension_loaded('curl') ? 1 : 0;
    }

    public static function check_zip()
    {
        return extension_loaded('zip') ? 1 : 0;
    }

    public static function check_gd()
    {
        return extension_loaded('gd') ? 1 : 0;
    }

    public static function check_gmp()
    {
        return extension_loaded('gmp') ? 1 : 0;
    }

    public static function check_bcmath()
    {
        return extension_loaded('bcmath') ? 1 : 0;
    }

    public static function check_mbstring()
    {
        return extension_loaded('mbstring') ? 1 : 0;
    }

    public static function check_iconv()
    {
        return extension_loaded('iconv') ? 1 : 0;
    }

    public static function check_xml()
    {
        return extension_loaded('xml') ? 1 : 0;
    }

    public static function check_gd_func()
    {
        return function_exists('imagecreatetruecolor') ? 1 : 0;
    }

    public static function check_json()
    {
        return function_exists('json_encode') ? 1 : 0;
    }

    public static function check_openssl()
    {
        return (extension_loaded('openssl') && function_exists('openssl_encrypt') && function_exists('openssl_decrypt')) ? 1 : 0;
    }

    public static function prefly_check()
    {
        return [
            'curl' => self::check_curl(),
            'zip' => self::check_zip(),
            'gd' => self::check_gd(),
            'gmp' => self::check_gmp(),
            'bcmath' => self::check_bcmath(),
            'mbstring' => self::check_mbstring(),
            'iconv' => self::check_iconv(),
            'xml' => self::check_xml(),
            'gd_func' => self::check_gd_func(),
            'json' => self::check_json(),
            'openssl' => self::check_openssl()
        ];
    }

    public static function format_gmt_offset($offset)
    {
        $sign = ($offset >= 0) ? '+' : '-';
        $hours = floor(abs($offset));
        $minutes = (abs($offset) - $hours) * 60;
        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }

    public static function is_shell_exec_available()
    {
        $functionExists = function_exists('shell_exec');
        $disabled_functions = explode(',', ini_get('disable_functions'));
        $functionEnabledInIni = !in_array('shell_exec', $disabled_functions);
        $executionTest = null;
        if (function_exists('shell_exec')) {
            $executionTest = shell_exec('echo test');
        }
        return $functionExists && $functionEnabledInIni && $executionTest === "test\n";
    }
}
