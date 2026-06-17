<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><a href="#" id="bbcs_proxy_add" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Add new proxy', 'botblocker-security' ); ?>"><i class="fa-solid fa-plus"></i></a>
<a href="#" id="bbcs_proxy_import" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Import proxies from JSON', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-upload"></i></a>
<a href="#" id="bbcs_proxy_export" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Export proxies to JSON', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-download"></i></a>
<a href="#" id="bbcs_proxy_clear_all" class="btn btn-default btn-sm me-3" data-bs-toggle="tooltip"
	data-bs-placement="top" data-bs-original-title="<?php esc_attr_e( 'Remove all proxies', 'botblocker-security' ); ?>"><i
		class="fa-regular fa-trash-can"></i></a>

<a href="#" id="bbcs_proxy_to_php" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php esc_attr_e( 'Set proxies to a PHP file', 'botblocker-security' ); ?>"><i
		class="fa-solid fa-bolt-lightning"></i></a>
<!-- <a href="#" id="bbcs_proxy_to_mu" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
	data-bs-original-title="<?php //esc_attr_e('Set proxies to MU-plugin BotBlocker Mode', 'botblocker-security'); ?>"><i
		class="fa-solid fa-plug-circle-bolt"></i></a> -->
