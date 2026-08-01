<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Botblocker_Deactivator {

	public static function deactivate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			self::deactivateNetworkWide();
			return;
		}

		if ( class_exists( 'BotBlockerAddons' ) ) {
			BotBlockerAddons::deactivateAll();
		}

		if ( defined( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS' ) && BOTBLOCKER_INTEGRATE_MU_PLUGINS ) {
			BotBlockerInstall::uninstallMuPlugin();
		}

		BotBlockerCron::removeTasks();

		flush_rewrite_rules( true );
	}

	public static function deactivateNetworkWide(): void {
		if ( ! is_multisite() ) {
			return;
		}
		$site_ids = BotBlockerMultisite::getAllSiteIds();
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			try {
				require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
				if ( class_exists( 'BotBlockerAddons' ) ) {
					BotBlockerAddons::deactivateAll();
				}
				BotBlockerCron::removeTasks();
			} finally {
				restore_current_blog();
			}
		}
		require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
		if ( defined( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS' ) && BOTBLOCKER_INTEGRATE_MU_PLUGINS ) {
			BotBlockerInstall::uninstallMuPlugin();
		}
	}
}
