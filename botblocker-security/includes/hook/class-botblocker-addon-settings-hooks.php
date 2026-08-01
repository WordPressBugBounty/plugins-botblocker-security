<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAddonSettingsHooks {

	public static function handleSave(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
		}
		check_admin_referer( 'save_botblocker_addon_settings', 'botblocker_addon_settings_nonce' );

		if ( class_exists( 'BotBlockerAddons' ) ) {
			BotBlockerAddons::saveSettingsFromPost( $_POST );
		}
		flush_rewrite_rules( true );

		BBCS_Toastify::flash( __( 'Add-on settings saved.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );

		$anchor = isset( $_POST['bbcs_anchor'] ) ? sanitize_key( wp_unslash( $_POST['bbcs_anchor'] ) ) : '';
		$url    = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		if ( $anchor !== '' ) {
			$url .= '#' . $anchor;
		}
		wp_safe_redirect( $url );
		exit;
	}
}

add_action( 'admin_post_save_botblocker_addon_settings', array( 'BotBlockerAddonSettingsHooks', 'handleSave' ) );
