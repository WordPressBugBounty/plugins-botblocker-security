<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerToolsHooks {

	public static function handleSave(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
		}
		check_admin_referer( 'save_botblocker_tools', 'botblocker_tools_nonce' );

		flush_rewrite_rules( true );

		BBCS_Toastify::flash( __( 'Tools settings saved.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_TOOLS );

		$anchor = isset( $_POST['bbcs_anchor'] ) ? sanitize_key( wp_unslash( $_POST['bbcs_anchor'] ) ) : '';
		$url    = BotBlockerMultisite::getAdminPageUrl( 'bbcs_tools' );
		if ( $anchor !== '' ) {
			$url .= '#' . $anchor;
		}
		wp_safe_redirect( $url );
		exit;
	}
}

add_action( 'admin_post_save_botblocker_tools', array( 'BotBlockerToolsHooks', 'handleSave' ) );
