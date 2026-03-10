<?php
/**
 * Google2FA Dual Version Installer
 * 
 * Installs both ^8.0 and ^9.0 versions of pragmarx/google2fa
 * to enable conditional loading based on PHP version
 */

namespace BotBlocker\Scripts;

class GoogleTwoFADualInstaller {
    
    private static $base_dir = null;
    private static $v8_dir = null;
    private static $v9_dir = null;
    
    /**
     * Install both versions of Google2FA
     */
    public static function installBothVersions() {
        self::initializePaths();
        
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
        echo "\n=== BotBlocker Google2FA Dual Version Installer ===\n";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
        echo 'PHP Version: ' . PHP_VERSION . "\n";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
        echo 'PHP Version ID: ' . PHP_VERSION_ID . "\n\n";
        
        // Install v8.0 (already handled by composer require)
        echo "[1/3] Setting up Google2FA v8.0 directory...\n";
        self::setupV8Directory();
        
        // Install v9.0 separately
        echo "[2/3] Installing Google2FA v9.0 (for PHP 8.1+)...\n";
        self::installV9Version();
        
        // Create version info file
        echo "[3/3] Creating version information file...\n";
        self::createVersionInfo();
        
        echo "\n✓ Installation complete!\n";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
        echo '- V8.0 (PHP < 8.1): ' . self::$v8_dir . "\n";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
        echo '- V9.0 (PHP >= 8.1): ' . self::$v9_dir . "\n";
        echo "\nBoth versions are now available for conditional loading.\n";
        echo "====================================================\n\n";
    }
    
    /**
     * Initialize directory paths
     */
    private static function initializePaths() {
        // This file is in includes/, vendor/2FA is at plugin root
        // __FILE__ = /path/to/botblocker-security/includes/class-google2fa-dual-installer.php
        // dirname(__FILE__) = /path/to/botblocker-security/includes
        // dirname(dirname(__FILE__)) = /path/to/botblocker-security
        $plugin_root = dirname( dirname( __FILE__ ) );
        
        self::$base_dir = $plugin_root . '/vendor/2FA';
        self::$v8_dir   = self::$base_dir . '/google2fa-v8';
        self::$v9_dir   = self::$base_dir . '/google2fa-v9';
    }
    
    /**
     * Setup v8.0 directory structure
     */
    private static function setupV8Directory() {
        // First, check if the directory already exists with correct content
        if ( file_exists( self::$v8_dir . '/pragmarx-google2fa/composer.json' ) ) {
            echo "  ✓ V8.0 already exists\n";
            return;
        }
        
        // Create the directory
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir,WordPress.PHP.NoSilencedErrors.Discouraged -- WP_Filesystem unavailable in Composer CLI context
        @mkdir( self::$v8_dir, 0755, true );
        
        // Look for v8.0 in standard composer locations
        $pragmarx_dir = self::$base_dir . '/pragmarx/google2fa';
        
        if ( file_exists( $pragmarx_dir ) ) {
            // Copy the installation to v8 subdirectory
            self::recursiveCopy( $pragmarx_dir, self::$v8_dir . '/pragmarx-google2fa' );
            echo "  ✓ V8.0 copied from composer vendor\n";
        } else {
            echo "  ⚠ V8.0 not found in vendor, but directory created\n";
        }
    }
    
    /**
     * Install v9.0 version using git clone or download
     */
    private static function installV9Version() {
        if ( file_exists( self::$v9_dir ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
            echo '  ✓ V9.0 already exists at: ' . self::$v9_dir . "\n";
            return;
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir,WordPress.PHP.NoSilencedErrors.Discouraged -- WP_Filesystem unavailable in Composer CLI context
        @mkdir( self::$v9_dir, 0755, true );
        
        // Try to clone from GitHub
        $git_url = 'https://github.com/antonioribeiro/google2fa.git';
        $cmd = sprintf(
            'cd "%s" && git clone --branch v9.0.0 %s google2fa-v9 2>&1',
            escapeshellarg( self::$base_dir ),
            escapeshellarg( $git_url )
        );
        
        // Check if git is available
        $output = shell_exec( $cmd ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
        
        if ( file_exists( self::$v9_dir . '/google2fa-v9' ) ||
            file_exists( self::$v9_dir . '/src' ) ) {
            echo "  ✓ V9.0 installed via git clone\n";
            return;
        }
        
        // Fallback: Try composer in separate process
        echo "  ℹ Attempting alternative installation method...\n";
        self::installV9Fallback();
    }
    
    /**
     * Fallback method to install v9.0
     */
    private static function installV9Fallback() {
        // Create a temporary composer.json for v9.0
        $temp_composer = self::$v9_dir . '/composer.json';
        
        $composer_json = array(
            'require' => array(
                'pragmarx/google2fa' => '^9.0',
            ),
            'config'  => array(
                'vendor-dir' => 'vendor',
            ),
        );
        
        file_put_contents( $temp_composer, json_encode( $composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
        
        // Run composer install in the v9 directory
        $cmd = sprintf(
            'cd "%s" && composer install --no-dev --prefer-dist 2>&1',
            escapeshellarg( self::$v9_dir )
        );
        
        $output = shell_exec( $cmd ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
        
        if ( file_exists( self::$v9_dir . '/vendor/pragmarx/google2fa' ) ) {
            echo "  ✓ V9.0 installed via composer\n";
        } else {
            echo "  ⚠ Could not install V9.0 automatically. You may need to install manually.\n";
            echo "  Installation guide:\n";
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only installer script, no HTML output
            echo '    1. cd ' . self::$v9_dir . "\n";
            echo "    2. composer install\n";
        }
    }
    
    /**
     * Create version information file
     */
    private static function createVersionInfo() {
        $info = array(
            'timestamp'           => gmdate( 'Y-m-d H:i:s' ),
            'php_version'         => PHP_VERSION,
            'php_version_id'      => PHP_VERSION_ID,
            'v8_location'         => self::$v8_dir,
            'v9_location'         => self::$v9_dir,
            'v8_available'        => file_exists( self::$v8_dir ),
            'v9_available'        => file_exists( self::$v9_dir ),
            'recommended_version' => PHP_VERSION_ID >= 80100 ? '9.0' : '8.0',
        );
        
        $version_file = dirname( __DIR__ ) . '/.google2fa-versions.json';
        file_put_contents( $version_file, json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
        echo "  ✓ Version info saved to: .google2fa-versions.json\n";
    }
    
    /**
     * Recursively copy directory
     */
    private static function recursiveCopy( $src, $dst ) {
        $dir = opendir( $src );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir,WordPress.PHP.NoSilencedErrors.Discouraged -- WP_Filesystem unavailable in Composer CLI context
        @mkdir( $dst, 0755, true );
        
        if ( ! $dir ) {
            return false;
        }
        
        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( '.' !== $file && '..' !== $file ) {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                
                if ( is_dir( $src_file ) ) {
                    self::recursiveCopy( $src_file, $dst_file );
                } else {
                    copy( $src_file, $dst_file );
                }
            }
        }
        
        closedir( $dir );
        return true;
    }
    
    /**
     * Get version info
     */
    public static function getVersionInfo() {
        $version_file = dirname( __DIR__ ) . '/.google2fa-versions.json';
        if ( file_exists( $version_file ) ) {
            return json_decode( file_get_contents( $version_file ), true );
        }
        return null;
    }
}