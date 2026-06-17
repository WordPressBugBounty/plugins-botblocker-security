<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?> <a href="#" id="bbcs_path_add" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Add new path', 'botblocker-security' ); ?>"><i class="fa-solid fa-plus"></i></a>
<a href="#" id="bbcs_path_import" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Import paths from JSON', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-upload"></i></a>
<a href="#" id="bbcs_path_export" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Export paths to JSON', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-download"></i></a>
<a href="#" id="bbcs_path_clear_all" class="btn btn-default btn-sm me-3" data-bs-toggle="tooltip"
	data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Remove all paths', 'botblocker-security' ); ?>"><i
		class="fa-regular fa-trash-can"></i></a>

<a href="#" id="bbcs_path_to_php" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Set paths to a PHP file', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-bolt-lightning"></i></a>
<!-- <a href="#" id="bbcs_path_to_mu" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php //esc_attr_e('Set paths to MU-plugin BotBlocker Mode', 'botblocker-security'); ?>"><i
		class="fa-solid fa-plug-circle-bolt"></i></a> -->
