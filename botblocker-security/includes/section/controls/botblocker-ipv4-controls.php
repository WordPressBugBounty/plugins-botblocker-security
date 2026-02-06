<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?> <a href="#" id="bbcs_ipv4_add" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Add new search engine or other white bot', 'botblocker-security'); ?>"><i
        class="fa-solid fa-plus"></i></a>
<a href="#" id="bbcs_ipv4_import" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Import search engines and other white bots from JSON', 'botblocker-security'); ?>"><i
        class="fa-solid fa-upload"></i></a>
<a href="#" id="bbcs_ipv4_export" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Export search engines and other white bots to JSON', 'botblocker-security'); ?>"><i
        class="fa-solid fa-download"></i></a>
<a href="#" id="bbcs_ipv4_clear_all" class="btn btn-default btn-sm me-3" data-bs-toggle="tooltip"
    data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Remove all search engines and other white bots', 'botblocker-security'); ?>"><i
        class="fa-regular fa-trash-can"></i></a>

<a href="#" id="bbcs_ipv4_to_php" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Set IPv4 rules to a PHP file', 'botblocker-security'); ?>"><i
        class="fa-solid fa-bolt-lightning"></i></a>
<!-- <a href="#" id="bbcs_ipv4_to_mu" class="btn btn-default btn-sm me-3" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php //esc_attr_e('Set search engines and other white bots to MU-plugin BotBlocker Mode', 'botblocker-security'); ?>"><i
        class="fa-solid fa-plug-circle-bolt"></i></a> -->
<a href="#" id="bbcs_ipv4_import_white" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Import white list IP from text file', 'botblocker-security'); ?>"><i
        class="fa-regular fa-flag"></i></a>
<a href="#" id="bbcs_ipv4_import_black" class="btn btn-default btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Import black list IP from text file', 'botblocker-security'); ?>"><i
        class="fa-solid fa-flag"></i></a>
<a href="<?php echo esc_url($BBCSA->files_IPv4); ?>" id="bbcs_ipv4_download_test" class="btn btn-default btn-sm"
    data-bs-toggle="tooltip" data-bs-placement="top"
    data-bs-original-title="<?php esc_attr_e('Download test file for import lists', 'botblocker-security'); ?>" download><i
        class="fa-regular fa-file-lines"></i></a>
