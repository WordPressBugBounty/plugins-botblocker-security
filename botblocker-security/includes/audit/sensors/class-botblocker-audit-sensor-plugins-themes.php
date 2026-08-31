<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorPluginsThemes {

	public static function register(): void {
		add_action( 'activated_plugin', array( self::class, 'onActivatedPlugin' ), 10, 2 );
		add_action( 'deactivated_plugin', array( self::class, 'onDeactivatedPlugin' ), 10, 2 );
		add_action( 'deleted_plugin', array( self::class, 'onDeletedPlugin' ), 10, 2 );
		add_action( 'deleted_theme', array( self::class, 'onDeletedTheme' ), 10, 2 );
		add_action( 'switch_theme', array( self::class, 'onSwitchTheme' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( self::class, 'onUpgraderComplete' ), 10, 2 );
	}

	public static function onActivatedPlugin( $plugin, $network_wide ): void {
		unset( $network_wide );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::PLUGIN_ACTIVATED,
			array(
				'data' => array(
					'plugin' => (string) $plugin,
				),
				'dedup' => (string) $plugin,
			)
		);
	}

	public static function onDeactivatedPlugin( $plugin, $network_wide ): void {
		unset( $network_wide );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::PLUGIN_DEACTIVATED,
			array(
				'data' => array(
					'plugin' => (string) $plugin,
				),
				'dedup' => (string) $plugin,
			)
		);
	}

	public static function onDeletedPlugin( $plugin_file, $deleted ): void {
		if ( ! $deleted ) {
			return;
		}
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::PLUGIN_DELETED,
			array(
				'data' => array(
					'plugin' => (string) $plugin_file,
				),
				'dedup' => (string) $plugin_file,
			)
		);
	}

	public static function onDeletedTheme( $stylesheet, $deleted ): void {
		if ( ! $deleted ) {
			return;
		}
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::THEME_DELETED,
			array(
				'data' => array(
					'theme' => (string) $stylesheet,
				),
				'dedup' => (string) $stylesheet,
			)
		);
	}

	public static function onSwitchTheme( $new_name, $new_theme, $old_theme ): void {
		unset( $new_name, $new_theme );
		$old = is_object( $old_theme ) && isset( $old_theme->stylesheet ) ? (string) $old_theme->stylesheet : '';
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::THEME_SWITCHED,
			array(
				'data' => array(
					'from' => $old,
					'to'   => (string) get_stylesheet(),
				),
			)
		);
	}

	public static function onUpgraderComplete( $upgrader, $hook_extra ): void {
		if ( ! is_array( $hook_extra ) ) {
			return;
		}

		if ( wp_doing_cron() ) {
			return;
		}

		$action = isset( $hook_extra['action'] ) ? (string) $hook_extra['action'] : '';
		$type   = isset( $hook_extra['type'] ) ? (string) $hook_extra['type'] : '';

		if ( $action === 'install' && $type === 'plugin' ) {
			$plugin = self::pluginSlug( $upgrader, $hook_extra );
			if ( $plugin !== '' ) {
				BotBlockerAuditLogger::record(
					BotBlockerAuditEvents::PLUGIN_INSTALLED,
					array(
						'object_type' => 'plugin',
						'data'        => array(
							'plugin' => $plugin,
						),
						'dedup'       => $plugin,
					)
				);
			}
			return;
		}

		if ( $action === 'install' && $type === 'theme' ) {
			$theme = self::themeSlug( $upgrader, $hook_extra );
			if ( $theme !== '' ) {
				BotBlockerAuditLogger::record(
					BotBlockerAuditEvents::THEME_INSTALLED,
					array(
						'object_type' => 'theme',
						'data'        => array(
							'theme' => $theme,
						),
						'dedup'       => $theme,
					)
				);
			}
			return;
		}

		if ( $action === 'update' && $type === 'plugin' && ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
			foreach ( $hook_extra['plugins'] as $plugin ) {
				BotBlockerAuditLogger::record(
					BotBlockerAuditEvents::PLUGIN_UPDATED,
					array(
						'object_type' => 'plugin',
						'data'        => array(
							'plugin' => (string) $plugin,
						),
						'dedup'       => (string) $plugin,
					)
				);
			}
			return;
		}

		if ( $action === 'update' && $type === 'theme' && ! empty( $hook_extra['themes'] ) && is_array( $hook_extra['themes'] ) ) {
			foreach ( $hook_extra['themes'] as $theme ) {
				BotBlockerAuditLogger::record(
					BotBlockerAuditEvents::THEME_UPDATED,
					array(
						'object_type' => 'theme',
						'data'        => array(
							'theme' => (string) $theme,
						),
						'dedup'       => (string) $theme,
					)
				);
			}
			return;
		}

		if ( $action === 'update' && $type === 'core' ) {
			BotBlockerAuditLogger::record(
				BotBlockerAuditEvents::CORE_UPGRADED,
				array(
					'object_type' => 'core',
					'data'        => array(
						'type' => 'core',
					),
					'dedup'       => 'core',
				)
			);
		}
	}

	/**
	 * @param mixed                $upgrader
	 * @param array<string, mixed> $hook_extra
	 */
	private static function pluginSlug( $upgrader, array $hook_extra ): string {
		if ( ! empty( $hook_extra['plugin'] ) && is_string( $hook_extra['plugin'] ) ) {
			return $hook_extra['plugin'];
		}
		if ( is_object( $upgrader ) && method_exists( $upgrader, 'plugin_info' ) ) {
			$info = $upgrader->plugin_info();
			return is_string( $info ) ? $info : '';
		}
		return '';
	}

	/**
	 * @param mixed                $upgrader
	 * @param array<string, mixed> $hook_extra
	 */
	private static function themeSlug( $upgrader, array $hook_extra ): string {
		if ( ! empty( $hook_extra['theme'] ) && is_string( $hook_extra['theme'] ) ) {
			return $hook_extra['theme'];
		}
		if ( is_object( $upgrader ) && method_exists( $upgrader, 'theme_info' ) ) {
			$info = $upgrader->theme_info();
			if ( is_object( $info ) && isset( $info->stylesheet ) ) {
				return (string) $info->stylesheet;
			}
		}
		return '';
	}
}
