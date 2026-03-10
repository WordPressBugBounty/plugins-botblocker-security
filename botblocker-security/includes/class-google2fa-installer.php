<?php
/**
 * Google2FA Composer Installer Script
 * 
 * Automatically selects and installs the appropriate version of google2fa
 * based on the PHP version
 */

namespace BotBlocker\Scripts;

class GoogleTwoFAInstaller {
    
    /**
     * Install the appropriate version of google2fa
     */
    public static function installVersion() {
        $php_version = PHP_VERSION;
        $php_version_id = PHP_VERSION_ID;
        
        // Parse base version (8.0, 8.1, etc.)
        $base_version = floor($php_version_id / 100) / 100;
                
        if ($php_version_id >= 80100) {
            $version = '^9.0';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only script, no HTML output
            echo "PHP {$php_version} detected. Installing pragmarx/google2fa {$version}...\n";
        } else {
            $version = '^8.0';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only script, no HTML output
            echo "PHP {$php_version} detected. Installing pragmarx/google2fa {$version}...\n";
        }
        
        // Store version info for runtime use
        $version_file = dirname(__DIR__) . '/includes/.google2fa-version';
        file_put_contents($version_file, $version);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only script, no HTML output
        echo "Google2FA version {$version} configured for PHP {$php_version}\n";
        
        return 0;
    }
    
    /**
     * Get current required version based on PHP runtime version
     */
    public static function getCurrentRequiredVersion() {
        if (PHP_VERSION_ID >= 80100) {
            return '^9.0';
        }
        return '^8.0';
    }
}
