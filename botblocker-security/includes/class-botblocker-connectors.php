<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerConnectors {

	const ID = 'botblocker';

	public static function register(): void {
		add_action(
			'wp_connectors_init',
			function ( $registry ) {
				if ( class_exists( 'BotBlocker' ) ) {
					$BBCS = BotBlocker::getInstance();
					if ( isset( $BBCS->settings->bbcs_wp_connectors_enabled ) && (int) $BBCS->settings->bbcs_wp_connectors_enabled !== 1 ) {
						return;
					}
				}
				self::registerWpConnector( $registry );
			}
		);
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueWpConnectorAssets' ) );
	}

	public static function proUrl(): string {
		if ( method_exists( 'BotBlockerMultisite', 'getSiteAdminPageUrl' ) ) {
			return BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_cloud_api' );
		}

		return admin_url( 'admin.php?page=bbcs_cloud_api' );
	}

	public static function isCloudApiActive(): bool {
		return class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
	}

	public static function registerWpConnector( $registry ): void {
		if ( ! is_object( $registry ) || ! method_exists( $registry, 'register' ) ) {
			return;
		}

		if ( method_exists( $registry, 'is_registered' ) && $registry->is_registered( self::ID ) ) {
			return;
		}

		$registry->register(
			self::ID,
			array(
				'name'           => __( 'BotBlocker Security', 'botblocker-security' ),
				'description'    => __( 'BotBlocker is a powerful, universal security plugin with proactive protection for WordPress.', 'botblocker-security' ),
				'logo_url'       => BOTBLOCKER_URL . 'admin/img/icon-128x128.png',
				'type'           => 'security',
				'plugin'         => array(
					'file'      => BOTBLOCKER_BASENAME,
					'is_active' => static function (): bool {
						return defined( 'BOTBLOCKER' ) && BOTBLOCKER;
					},
				),
				'authentication' => array(
					'method'          => 'none',
					'credentials_url' => self::proUrl(),
				),
			)
		);
	}

	public static function enqueueWpConnectorAssets( string $hook_suffix ): void {
		global $pagenow;

		if ( ! function_exists( 'wp_register_script_module' ) || ! function_exists( 'wp_enqueue_script_module' ) ) {
			return;
		}

		$screen             = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_connectors_page = 'options-connectors.php' === $pagenow;
		if ( ! $is_connectors_page && $screen && false !== strpos( (string) $screen->id, 'options-connectors' ) ) {
			$is_connectors_page = true;
		}
		if ( ! $is_connectors_page && is_string( $hook_suffix ) && false !== strpos( $hook_suffix, 'options-connectors' ) ) {
			$is_connectors_page = true;
		}

		if ( ! $is_connectors_page ) {
			return;
		}

		$data = array(
			'proUrl'           => self::proUrl(),
			'isCloudApiActive' => self::isCloudApiActive(),
			'pluginFile'       => BOTBLOCKER_BASENAME,
		);

		wp_register_script( 'botblocker-connectors-data', '', array(), BOTBLOCKER_VERSION, false );
		wp_add_inline_script(
			'botblocker-connectors-data',
			'window.bbcsConnector = ' . wp_json_encode( $data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) . ';'
		);
		wp_enqueue_script( 'botblocker-connectors-data' );

		BotBlockerCompatibility::registerScriptModule(
			'botblocker-connectors',
			BOTBLOCKER_URL . 'admin/js/bbcs-js/bbcs-connectors.js',
			array(
				array(
					'id'     => '@wordpress/connectors',
					'import' => 'static',
				),
			),
			BOTBLOCKER_VERSION
		);
		BotBlockerCompatibility::enqueueScriptModule( 'botblocker-connectors' );
	}
}
