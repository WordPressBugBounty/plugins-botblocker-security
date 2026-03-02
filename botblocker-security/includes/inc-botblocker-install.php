<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once BOTBLOCKER_DIR . 'includes/install/botblocker-install-tables.php';
include_once BOTBLOCKER_DIR . 'includes/install/botblocker-install-ips.php';
include_once BOTBLOCKER_DIR . 'includes/install/botblocker-install-data.php';
include_once BOTBLOCKER_DIR . 'includes/install/botblocker-install-files.php';

function bbcs_check_install()
{
	$upload_dir = bbcs_get_protected_upload_dir();
	if ($upload_dir === null) {
		$upload_dir = bbcs_create_protected_upload_dir();
	}

	if (!defined('BOTBLOCKER_UPLOADS_DIR')) {
		define('BOTBLOCKER_UPLOADS_DIR', $upload_dir);
	}
	if (!defined('BOTBLOCKER_DATA_DIR')) {
		define('BOTBLOCKER_DATA_DIR', BOTBLOCKER_UPLOADS_DIR . 'data/');
	}
	if (!defined('BOTBLOCKER_ADDONS_DIR')) {
		define('BOTBLOCKER_ADDONS_DIR', BOTBLOCKER_UPLOADS_DIR . 'addons/');
	}
	if (!defined('BOTBLOCKER_ADDONS_URL')) {
		define('BOTBLOCKER_ADDONS_URL', bbcs_get_protected_upload_dir(true) . 'addons/');
	}	

    if (!bbcs_tablesExist()) {
        bbcs_createTables();
        bbcs_init_db_and_files();
    }
}

function bbcs_init_db_and_files(){
    bbcs_addServerIPs();
    bbcs_addAdminIPs();
    bbcs_fetchAndStoreParentIPs();
    bbcs_insertInitialData(bbcs_createSaltFile(true));
    bbcs_generateAllFilesFromDb();
    bbcs_generateSettingsFileFromDb();
    bbcs_invalidate_wp_cache();
}

/*
function bbcs_getCloudAPIEmail()
{
    $user = wp_get_current_user();
    if (is_user_logged_in()) {
        $cloud_api_email = $user->user_email;
        if (empty($cloud_api_email)) {
            $cloud_api_email = get_option('admin_email');
        }
    } else {
        $cloud_api_email = '{email}';
    }
    return $cloud_api_email;
}
*/

function bbcs_getCloudAPIEmail(): string {
	$default = '{email}';
	$email   = $default;

	if ( function_exists( 'is_user_logged_in' ) && function_exists( 'wp_get_current_user' ) && is_user_logged_in() ) {
		$user = wp_get_current_user();
		if ( $user && $user->ID && ! empty( $user->user_email ) ) {
			$email = $user->user_email;
		}
	}

	if ( ( $email === $default || $email === '' ) ) {
		$opt = get_option( 'admin_email' );
		if ( is_string( $opt ) && $opt !== '' ) {
			$email = $opt;
		}
	}

	if ( ( $email === $default || $email === '' ) && function_exists( 'is_multisite' ) && is_multisite() ) {
		$opt = get_site_option( 'admin_email' );
		if ( is_string( $opt ) && $opt !== '' ) {
			$email = $opt;
		}
	}

	if ( ( $email === $default || $email === '' ) && function_exists( 'get_users' ) && ! wp_installing() ) {
		$admins = get_users( [
			'role__in' => [ 'administrator' ],
			'number'   => 1,
			'orderby'  => 'ID',
			'order'    => 'ASC',
			'fields'   => [ 'user_email' ],
		] );
		if ( ! empty( $admins ) && ! empty( $admins[0]->user_email ) ) {
			$email = $admins[0]->user_email;
		}
	}

	$email = sanitize_email( (string) $email );
	return is_email( $email ) ? $email : $default;
}

function bbcs_saveSettingsToFile($settings)
{
    $settingsFile = BOTBLOCKER_DATA_DIR . 'settings.php';
    $settingsContent = BBCS_STOP_DIRECT . "\nreturn " . bbcs_php_export( $settings, 0, true ) . ";\n";
    file_put_contents($settingsFile, $settingsContent);
    bbcs_clearFileCache();
}

function bbcs_saveSettingsToDatabase($settings)
{
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $existing = $wpdb->get_col("SELECT `key` FROM `{$wpdb->bbcs_settings}`");
    $existing_keys = array_flip($existing);

    foreach ($settings as $setting_key => $setting_value) {
        $val = is_array($setting_value) ? wp_json_encode($setting_value) : $setting_value;
        if (isset($existing_keys[$setting_key])) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->bbcs_settings,
                ['value' => $val],
                ['key' => $setting_key],
                ['%s'],
                ['%s']
            );
        } else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert(
                $wpdb->bbcs_settings,
                ['key' => $setting_key, 'value' => $val],
                ['%s', '%s']
            );
        }
    }
}

function bbcs_php_export( $value, int $indent = 0, bool $top_array_keyword = false ): string {
	$pad  = str_repeat( '    ', $indent );
	$pad2 = str_repeat( '    ', $indent + 1 );

	$export_string = static function ( string $s ): string {
		$s = str_replace(
			["\\",   "'",   "\r",  "\n",  "\t",  "\v",  "\e",  "\f",  "\0",  '$'],
			["\\\\", "\\'", '\r',  '\n',  '\t',  '\v',  '\e',  '\f',  '\0', '\$'],
			$s
		);
		return "'" . $s . "'";
	};

	if ( is_null( $value ) ) {
		return 'null';
	}

	if ( is_bool( $value ) ) {
		return $value ? 'true' : 'false';
	}

	if ( is_int( $value ) ) {
		return (string) $value;
	}

	if ( is_float( $value ) ) {
		$repr = rtrim( rtrim( number_format( $value, 12, '.', '' ), '0' ), '.' );
		return $repr === '' ? '0' : $repr;
	}

	if ( is_string( $value ) ) {
		return $export_string( $value );
	}

	if ( is_array( $value ) ) {
		if ( empty( $value ) ) {
			return $top_array_keyword ? "array ()" : "array ()";
		}

		$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );

		$out = $top_array_keyword ? "array (\n" : "[\n"; 
		foreach ( $value as $k => $v ) {
			if ( $is_list ) {
				$out .= $pad2 . bbcs_php_export( $v, $indent + 1, false ) . ",\n";
			} else {
				$key = is_int( $k ) ? (string) $k : $export_string( (string) $k );
				$out .= $pad2 . $key . ' => ' . bbcs_php_export( $v, $indent + 1, false ) . ",\n";
			}
		}
		$out .= $pad . ( $top_array_keyword ? ")" : "]" );
		return $out;
	}

	return $export_string( (string) $value );
}