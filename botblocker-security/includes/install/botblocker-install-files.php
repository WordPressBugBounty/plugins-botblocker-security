<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_createSaltFile($return_salt_bb = false)
{
    $saltFilePath = bbcs_data_dir() . 'salt.php'; 

    if (!file_exists($saltFilePath) || $return_salt_bb === true) {
        $host_key = md5(get_option('siteurl'));
        $salt_bb = bin2hex(random_bytes(12));
        $salt_ps = bin2hex(random_bytes(12));
        $salt_pz = time();

        $salt_data = [
            'host_key' => $host_key,
            'salt_bb' => $salt_bb,
            'salt_ps' => $salt_ps,
            'salt_pz' => $salt_pz
        ];

        /**
         * REVIEWER NOTE: This operation is not intended for debugging purposes. The following code generates a salt.php file
         * to cache plugin salt data, thereby reducing the frequency of database queries and enhancing overall performance.
         * No sensitive or user data is exposed by this process.
         */
         /* phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export */
        $fileContent = "<?php\nreturn " . var_export($salt_data, true) . ";\n";

        file_put_contents($saltFilePath, $fileContent);
        bbcs_clearFileCache();
        
        return $salt_bb;
    } 
    
    return false;
}

function bbcs_installMuPlugin() {
	global $wp_filesystem;

	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}
	$plugin_relative_file = plugin_basename( BOTBLOCKER_DIR . 'botblocker-mu.php' );
 
	$mu_plugin_content  = "<?php\n";
	$mu_plugin_content .= "/*\n";
	$mu_plugin_content .= "Plugin Name: BotBlocker (MU Loader)\n";
	$mu_plugin_content .= "Description: Loads BotBlocker early as an MU plugin.\n";
	$mu_plugin_content .= "Author: GLOBUS.studio\n";
	$mu_plugin_content .= "Version: " . BOTBLOCKER_VERSION . "\n";
	$mu_plugin_content .= "*/\n\n";
	$mu_plugin_content .= "if ( defined('WP_PLUGIN_DIR') && file_exists( WP_PLUGIN_DIR . '/" . $plugin_relative_file . "' ) ) {\n";
	$mu_plugin_content .= "    require_once WP_PLUGIN_DIR . '/" . $plugin_relative_file . "';\n";
	$mu_plugin_content .= "    if ( class_exists( 'BotBlockerMu' ) ) {\n";
	$mu_plugin_content .= "        \$botBlocker = new BotBlockerMu();\n";
	$mu_plugin_content .= "        \$botBlocker->run();\n";
	$mu_plugin_content .= "    }\n";
	$mu_plugin_content .= "}\n";

	$mu_plugins_dir  = WPMU_PLUGIN_DIR;
	$mu_plugin_file  = trailingslashit( $mu_plugins_dir ) . 'botblocker-mu-plugin.php';

	if ( ! $wp_filesystem->is_dir( $mu_plugins_dir ) ) {
		$wp_filesystem->mkdir( $mu_plugins_dir, defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755 );
	}

	$mode = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
	$wp_filesystem->put_contents( $mu_plugin_file, $mu_plugin_content, $mode );
	bbcs_clearFileCache();
}

function bbcs_uninstallMuPlugin() {
	$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'botblocker-mu-plugin.php';

	if ( file_exists( $mu_plugin_file ) ) {
		wp_delete_file( $mu_plugin_file );
		clearstatcache( true );
	}
}

function bbcs_removeWpConfigEarlyInitCode() {
	$wp_config_file = ABSPATH . 'wp-config.php';

	if ( ! file_exists( $wp_config_file ) ) {
		return false;
	}

	$config_contents = file_get_contents( $wp_config_file );
	$marker_start    = '/* BBCS Start */';
	$marker_end      = '/* BBCS End */';
	$start_position  = strpos( $config_contents, $marker_start );
	$end_position    = strpos( $config_contents, $marker_end );

	if ( $start_position === false || $end_position === false ) {
		return false;
	}

	$end_position += strlen( $marker_end );

	$new_config_contents = substr( $config_contents, 0, $start_position ) . substr( $config_contents, $end_position );
	file_put_contents( $wp_config_file, $new_config_contents );
	bbcs_clearFileCache();

	return true;
}

function bbcs_deleteRuleFiles()
{
    $files = [
        bbcs_data_dir() . 'search_engines.php',
        bbcs_data_dir() . 'paths.php',
        bbcs_data_dir() . 'rules.php',
        bbcs_data_dir() . 'ip.php',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            wp_delete_file($file);
            clearstatcache(true);
        }
    }
}
