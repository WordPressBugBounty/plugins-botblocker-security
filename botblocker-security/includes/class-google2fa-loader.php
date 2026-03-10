<?php
/**
 * Google2FA Conditional Version Loader
 * 
 * Loads the appropriate version of pragmarx/google2fa based on PHP version
 * Both versions are pre-installed, this just selects which one to use
 * PHP < 8.1: uses v8.0 from vendor/2FA/google2fa-v8
 * PHP >= 8.1: uses v9.0 from vendor/2FA/google2fa-v9
 */

namespace BotBlocker;

class Google2FA_Loader {
    
    private static $loaded_version = null;
    private static $google2fa_instance = null;
    
    /**
     * Get the appropriate Google2FA version based on PHP version
     */
    public static function get_version() {
        return version_compare(PHP_VERSION, '8.1.0', '>=') ? '9.0' : '8.0';
    }
    
    /**
     * Get the location of the appropriate Google2FA version
     */
    public static function get_path() {
        $base_dir = dirname(dirname(__DIR__)) . '/vendor/2FA';
        $base_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base_dir);
        
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            // PHP 8.1+ : Use google2fa v9.0
            return $base_dir . DIRECTORY_SEPARATOR . 'google2fa-v9';
        } else {
            // PHP < 8.1: Use google2fa v8.0
            return $base_dir . DIRECTORY_SEPARATOR . 'google2fa-v8';
        }
    }
    
    /**
     * Get autoload path for selected version
     */
    public static function get_autoload_path() {
        $base_path = self::get_path();
        
        // Normalize path separators
        $base_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base_path);
        
        // Try different possible autoload locations based on version
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            // V9.0 - cloned from git, has vendor inside
            $possible_paths = [
                $base_path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
            ];
        } else {
            // V8.0 - copied from composer, nested structure
            $possible_paths = [
                $base_path . DIRECTORY_SEPARATOR . 'pragmarx-google2fa' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
            ];
        }
        
        // Fallback to main vendor autoload
        $base_dir = dirname($base_path);
        $possible_paths[] = $base_dir . DIRECTORY_SEPARATOR . 'autoload.php';
        
        foreach ($possible_paths as $path) {
            $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (file_exists($normalized_path)) {
                return $normalized_path;
            }
        }
        
        return null;
    }
    
    /**
     * Initialize and load the appropriate Google2FA version
     */
    public static function init() {
        if (self::$loaded_version !== null) {
            return true; // Already initialized
        }
        
        $version = self::get_version();
        $autoload_path = self::get_autoload_path();
        
        if (!$autoload_path || !file_exists($autoload_path)) {
            // error_log('BotBlocker: Google2FA autoload not found. Path: ' . ($autoload_path ?: 'none'));
            return false;
        }
        
        try {
            require_once $autoload_path;
            self::$loaded_version = $version;
            return true;
        } catch (\Throwable $e) {
            // error_log('BotBlocker: Failed to load Google2FA: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Google2FA instance
     */
    public static function get_instance() {
        if (!self::init()) {
            return false;
        }
        
        if (self::$google2fa_instance !== null) {
            return self::$google2fa_instance;
        }
        
        $class = 'PragmaRX\Google2FA\Google2FA';
        
        if (!class_exists($class)) {
            // error_log('BotBlocker: Google2FA class not found: ' . $class);
            return false;
        }
        
        try {
            self::$google2fa_instance = new $class();
            return self::$google2fa_instance;
        } catch (\Throwable $e) {
            // error_log('BotBlocker: Failed to instantiate Google2FA: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if Google2FA is available and working
     */
    public static function is_available() {
        if (!self::init()) {
            return false;
        }
        
        $instance = self::get_instance();
        return $instance !== false;
    }
    
    /**
     * Get version information for debugging
     */
    public static function get_version_info() {
        return [
            'php_version' => PHP_VERSION,
            'selected_google2fa_version' => self::get_version(),
            'path' => self::get_path(),
            'autoload_path' => self::get_autoload_path(),
            'is_available' => self::is_available(),
            'loaded_version' => self::$loaded_version
        ];
    }
}
