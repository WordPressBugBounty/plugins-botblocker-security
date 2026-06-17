<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerPaymentHooks {

	public static function maybeAutoEnable(): bool {
		if ( ! class_exists( 'BotBlockerPaymentData' ) ) {
			return false;
		}

		if ( method_exists( 'BotBlockerMultisite', 'getOption' ) ) {
			$already = (int) BotBlockerMultisite::getOption( 'bbcs_payment_autoenabled_done', 0 );
			if ( $already === 1 ) {
				return false;
			}
		}

		if ( ! BotBlockerPaymentData::detectEcommerce() ) {
			return false;
		}

		global $wpdb;
		if ( ! isset( $wpdb->bbcs_settings ) ) {
			return false;
		}

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$current = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
				'payment_bypass_enable'
			)
		);

		if ( (int) $current !== 1 ) {
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$wpdb->bbcs_settings,
				array(
					'key'   => 'payment_bypass_enable',
					'value' => '1',
				),
				array( '%s', '%s' )
			);

	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$log_existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
					'payment_bypass_log'
				)
			);
			if ( $log_existing === null ) {
	            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->replace(
					$wpdb->bbcs_settings,
					array(
						'key'   => 'payment_bypass_log',
						'value' => '1',
					),
					array( '%s', '%s' )
				);
			}

			if ( class_exists( 'BotBlockerFileRenderer' ) ) {
				BotBlockerFileRenderer::generateSettingsFile();
			}

			if ( method_exists( 'BotBlockerMultisite', 'updateOption' ) ) {
				BotBlockerMultisite::updateOption( 'bbcs_payment_bypass_autoenabled_at', time() );
			}
		}

		if ( method_exists( 'BotBlockerMultisite', 'updateOption' ) ) {
			BotBlockerMultisite::updateOption( 'bbcs_payment_autoenabled_done', 1 );
		}

		return true;
	}

	public static function onPluginActivated( string $plugin, bool $network_wide = false ): void {
		unset( $plugin, $network_wide );
		self::maybeAutoEnable();
	}

	public static function onUpgraderComplete( $upgrader, $hook_extra ): void {
		unset( $upgrader );
		if ( ! is_array( $hook_extra ) ) {
			return;
		}
		$type   = isset( $hook_extra['type'] ) ? (string) $hook_extra['type'] : '';
		$action = isset( $hook_extra['action'] ) ? (string) $hook_extra['action'] : '';
		if ( $type !== 'plugin' || $action !== 'install' ) {
			return;
		}
		self::maybeAutoEnable();
	}

	public static function adminInitCheck(): void {
		if ( ! is_admin() ) {
			return;
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return;
		}
		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return;
		}
		if ( ! method_exists( 'BotBlockerMultisite', 'getOption' ) ) {
			return;
		}

		$marker = (int) BotBlockerMultisite::getOption( 'bbcs_payment_autodetect_lastrun', 0 );
		if ( $marker > 0 && ( time() - $marker ) < DAY_IN_SECONDS ) {
			return;
		}
		if ( method_exists( 'BotBlockerMultisite', 'updateOption' ) ) {
			BotBlockerMultisite::updateOption( 'bbcs_payment_autodetect_lastrun', time() );
		}

		self::maybeAutoEnable();
	}
}

add_action( 'activated_plugin', array( 'BotBlockerPaymentHooks', 'onPluginActivated' ), 10, 2 );
add_action( 'upgrader_process_complete', array( 'BotBlockerPaymentHooks', 'onUpgraderComplete' ), 20, 2 );
add_action( 'admin_init', array( 'BotBlockerPaymentHooks', 'adminInitCheck' ), 99 );
