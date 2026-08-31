<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAjaxSecretLinks {

	public static function handleRegenerateSecretLinks(): void {
		$bbcs_action = 'secret_links_regenerate';
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [AJAX] ' . $bbcs_action . ' cap check FAILED' );
			}
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'botblocker-security' ) ) );
		}

		$salt_bb = BotBlockerInstall::createSaltFile( true );
		if ( ! is_string( $salt_bb ) || $salt_bb === '' ) {
			wp_send_json_error( array( 'message' => __( 'Salt is unavailable.', 'botblocker-security' ) ) );
		}

		global $wpdb;
		$links = BotBlockerSeedData::generateSecretLinks( $salt_bb );
		foreach ( $links as $key => $value ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom settings table, write-through
			$wpdb->replace( $wpdb->bbcs_settings, array( 'key' => $key, 'value' => $value ), array( '%s', '%s' ) );
		}

		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::resetHealthTransients();

		// Refresh the in-memory settings AND the mirrored properties the
		// consumers use (rules-trait/mailer read $this->action_*, not the
		// settings object): initialize() copies only once, at boot.
		$settings_file = BotBlockerMultisite::getDataDir() . 'settings.php';
		if ( is_file( $settings_file ) ) {
			$bb = BotBlocker::getInstance();
			$bb->settings->load( $settings_file );
			foreach ( $links as $key => $value ) {
				if ( property_exists( $bb, $key ) ) {
					$bb->{$key} = $value;
				}
			}
		}

		wp_send_json_success(
			array(
				'message' => __( 'Security action links regenerated. Old links no longer work.', 'botblocker-security' ),
				'disable' => BotBlockerMailer::getDisableUrl(),
				'off'     => BotBlockerMailer::getOffUrl(),
				'on'      => BotBlockerMailer::getOnUrl(),
			)
		);
	}
}
