<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Botblocker_Activator {

	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			self::activateNetworkWide();
			return;
		}

		self::activateSite( true );
		flush_rewrite_rules( true );
	}

	public static function activateNetworkWide(): void {
		if ( ! is_multisite() ) {
			return;
		}
		ob_start();
		try {
			$site_ids = BotBlockerMultisite::getAllSiteIds();
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				try {
					require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
					self::activateSite( false );
				} catch ( \Throwable $e ) {
					if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[BBCS DEBUG] [Activator] activation failed for site ' . $site_id . ': ' . $e->getMessage() );
					}
				} finally {
					restore_current_blog();
				}
			}
			require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
			if ( defined( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS' ) && BOTBLOCKER_INTEGRATE_MU_PLUGINS ) {
				BotBlockerInstall::installMuPlugin();
			}
			flush_rewrite_rules( true );
		} finally {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		}
	}

	private static function activateSite( bool $is_fresh_install_context ): void {
		$is_fresh_install = $is_fresh_install_context && ! BotBlockerInstall::tablesExist();
		BotBlockerInstall::checkInstall();

		if ( $is_fresh_install_context && defined( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS' ) && BOTBLOCKER_INTEGRATE_MU_PLUGINS ) {
			BotBlockerInstall::installMuPlugin();
		}

		BotBlockerCron::registerTasks();

		if ( class_exists( 'BotBlockerAsnDb' ) ) {
			BotBlockerAsnDb::scheduleDownload( 'activation' );
		}

		if ( class_exists( 'BotBlockerLlmSync' ) ) {
			BotBlockerLlmSync::scheduleSync( 'activation' );
		}

		BotBlockerCloudApiHooks::registerRewriteRules();

		if ( function_exists( 'bbcs_register_2fa_rewrite_rules' ) ) {
			bbcs_register_2fa_rewrite_rules();
		}
		if ( function_exists( 'bbcs_register_verify_rewrite_rules' ) ) {
			bbcs_register_verify_rewrite_rules();
		}

		if ( $is_fresh_install ) {
			set_transient( 'bbcs_just_activated', true, 60 );
			if ( $is_fresh_install_context ) {
				BotBlockerMultisite::updateOption( 'bbcs_activation_redirect', true );
			}
		}
	}
}
