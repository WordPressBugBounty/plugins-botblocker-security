<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-addons-market.php';

class BotBlockerAjaxAddons {

	public static function handleLoadMarket(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$force = isset( $_POST['force'] ) && (string) wp_unslash( $_POST['force'] ) === '1';
		$payload = BotBlockerAddonsMarket::getAjaxPayload( $force );

		if ( ! class_exists( 'Botblocker_AddonsViewModel' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-addons-viewmodel.php';
		}

		$view_model = new Botblocker_AddonsViewModel();
		$payload['catalog_html'] = BotBlockerUI::render_market_catalog_html( $payload['market'], $view_model );

		wp_send_json_success( $payload );
	}
}
