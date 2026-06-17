<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$BBCSA = isset( $BBCSA ) ? $BBCSA : ( class_exists( 'Botblocker_Admin' ) ? Botblocker_Admin::getInstance() : null );
?> <a href="#" id="bbcs_ipv4_add" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Add white bot or search engine', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-plus"></i></a>
<a href="#" id="bbcs_ipv4_import" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Import white bots from JSON', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-upload"></i></a>
<a href="#" id="bbcs_ipv4_export" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Export white bots to JSON', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-download"></i></a>
<a href="#" id="bbcs_ipv4_clear_all" class="btn btn-default btn-sm me-3" data-bs-toggle="tooltip"
	data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Remove all white bots', 'botblocker-security' ); ?>"><i
		class="fa-regular fa-trash-can"></i></a>

<a href="#" id="bbcs_ipv4_to_php" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Save IPv4 rules to PHP file', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-bolt-lightning"></i></a>
<!-- <a href="#" id="bbcs_ipv4_to_mu" class="btn btn-default btn-sm me-3" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php //esc_attr_e('Set search engines and other white bots to MU-plugin BotBlocker Mode', 'botblocker-security'); ?>"><i
		class="fa-solid fa-plug-circle-bolt"></i></a> -->
<a href="#" id="bbcs_ipv4_import_white" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Import whitelist IPs from file', 'botblocker-security' ); ?>"><i
		class="fa-regular fa-flag"></i></a>
<a href="#" id="bbcs_ipv4_import_black" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Import blacklist IPs from file', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-flag"></i></a>
<a href="<?php echo esc_url( $BBCSA->files_IPv4 ); ?>" id="bbcs_ipv4_download_test" class="btn btn-default btn-sm"
	data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Download sample import file', 'botblocker-security' ); ?>" download><i
		class="fa-regular fa-file-lines"></i></a>
