<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAjaxRulesStats {

	public static function handleRefreshStats(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-rules-viewmodel.php';

		$vm       = new Botblocker_RulesViewModel();
		$counts   = $vm->table_counts;

		wp_send_json_success( $counts );
	}
}
