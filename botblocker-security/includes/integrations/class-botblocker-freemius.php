<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BotBlockerFreemius {

	private const ENABLED = false;
	private const DEMO_ENABLED = false;
	private const SDK_PATH = 'vendor/Freemius/2.13.4/standalone/autoloader.php';
	private static $instance = null;

	public static function boot(): bool {
		if ( ! self::isEnabled() ) {
			return false;
		}
		if ( self::$instance !== null ) {
			self::registerDemoHook();
			return true;
		}

		$config = self::getConfig();
		if ( null === $config || ! self::loadSdk() ) {
			return false;
		}

		try {
			self::$instance = \BotBlocker\Vendor\fs_dynamic_init( $config );
		} catch ( \Throwable $exception ) {
			self::$instance = null;
			return false;
		}

		if ( ! is_object( self::$instance ) ) {
			self::$instance = null;
			return false;
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'bbcs_freemius_loaded', self::$instance );
		}
		self::registerDemoHook();

		return true;
	}

	public static function instance() {
		return self::$instance;
	}

	public static function status(): array {
		if ( ! is_object( self::$instance ) ) {
			return array(
				'enabled'        => false,
				'license_status' => null,
				'plan'           => null,
				'trial'          => false,
				'expiration'     => null,
				'activated'      => false,
			);
		}

		$is_registered = self::$instance->is_registered();
		$plan          = self::$instance->get_plan();

		return array(
			'enabled'        => true,
			'license_status' => self::$instance->is_paying() ? 'paid' : ( $is_registered ? 'free' : null ),
			'plan'           => is_object( $plan ) && isset( $plan->name ) ? $plan->name : null,
			'trial'          => self::$instance->is_trial(),
			'expiration'     => null,
			'activated'      => $is_registered,
		);
	}

	public static function demoStatus( string $scenario = 'free' ): array {
		$states = array(
			'free'    => array( 'license_status' => 'free', 'entitlement' => 'free', 'plan' => 'Free', 'trial' => false, 'feature_access' => false ),
			'trial'   => array( 'license_status' => 'trial', 'entitlement' => 'trial', 'plan' => 'Demo Trial', 'trial' => true, 'feature_access' => true ),
			'paid'    => array( 'license_status' => 'paid', 'entitlement' => 'paid', 'plan' => 'Demo Pro', 'trial' => false, 'feature_access' => true ),
			'expired' => array( 'license_status' => 'expired', 'entitlement' => 'expired', 'plan' => 'Demo Pro', 'trial' => false, 'feature_access' => false ),
		);
		if ( ! isset( $states[ $scenario ] ) ) {
			$scenario = 'free';
		}

		return array_merge(
			array(
				'demo'       => true,
				'scenario'   => $scenario,
				'activated'  => 'free' !== $scenario,
				'expiration' => 'expired' === $scenario ? 'expired' : null,
			),
			$states[ $scenario ]
		);
	}

	public static function renderDemoPage(): void {
		if ( ! self::isDemoEnabled() || ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) ) {
			return;
		}

		$scenario = 'free';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only demo scenario selector, admin-gated above
		if ( isset( $_GET['bbcs_freemius_demo_scenario'] ) && is_string( $_GET['bbcs_freemius_demo_scenario'] ) ) {
			$scenario = sanitize_key( wp_unslash( $_GET['bbcs_freemius_demo_scenario'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$demo   = self::demoStatus( $scenario );
		$actual = self::status();
		$access = $demo['feature_access'] ? 'available' : 'locked';
		$loaded = class_exists( 'BotBlocker\\Vendor\\Freemius', false ) ? 'yes' : 'no';
		$base   = function_exists( 'admin_url' ) ? admin_url( 'admin.php?page=bbcs_freemius_demo' ) : '?page=bbcs_freemius_demo';

		echo '<div class="wrap">';
		echo '<h1>Freemius SDK demo</h1>';
		echo '<p>Local-only integration proof. No Freemius account or secret is connected.</p>';
		echo '<p>SDK loaded: <strong>' . esc_html( $loaded ) . '</strong> | Actual license: <strong>' . esc_html( (string) ( $actual['license_status'] ?? 'none' ) ) . '</strong></p>';
		echo '<h2>Simulated entitlement</h2><p>Scenario: <strong>' . esc_html( ucfirst( $demo['scenario'] ) ) . '</strong> | Feature access: <strong>' . esc_html( $access ) . '</strong></p>';
		echo '<p>';
		foreach ( array( 'free', 'trial', 'paid', 'expired' ) as $option ) {
			$url = add_query_arg( 'bbcs_freemius_demo_scenario', $option, $base );
			echo '<a class="button' . ( $option === $demo['scenario'] ? ' button-primary' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( ucfirst( $option ) ) . '</a> ';
		}
		echo '</p></div>';
	}

	public static function registerDemoPage(): void {
		if ( ! self::isDemoEnabled() || ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) ) {
			return;
		}

		add_submenu_page(
			'bbcs_dashboard',
			'Freemius Demo',
			'Freemius Demo',
			'manage_options',
			'bbcs_freemius_demo',
			array( __CLASS__, 'renderDemoPage' )
		);
	}

	private static function registerDemoHook(): void {
		if ( self::isDemoEnabled() && function_exists( 'add_action' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'registerDemoPage' ), 20 );
		}
	}

	private static function isDemoEnabled(): bool {
		$enabled = defined( 'BBCS_FREEMIUS_DEMO' ) ? (bool) constant( 'BBCS_FREEMIUS_DEMO' ) : self::DEMO_ENABLED;
		if ( function_exists( 'apply_filters' ) ) {
			$enabled = apply_filters( 'bbcs_freemius_demo_enabled', $enabled );
		}

		return (bool) $enabled;
	}

	private static function isEnabled(): bool {
		$enabled = self::isDemoEnabled() || ( defined( 'BBCS_FREEMIUS_ENABLED' ) ? (bool) constant( 'BBCS_FREEMIUS_ENABLED' ) : self::ENABLED );
		if ( function_exists( 'apply_filters' ) ) {
			$enabled = apply_filters( 'bbcs_freemius_enabled', $enabled );
		}

		return (bool) $enabled;
	}

	private static function getConfig() {
		$config = array(
			'id'                  => defined( 'BBCS_FREEMIUS_PRODUCT_ID' ) ? constant( 'BBCS_FREEMIUS_PRODUCT_ID' ) : null,
			'slug'                => 'botblocker-security',
			'type'                => 'plugin',
			'public_key'          => defined( 'BBCS_FREEMIUS_PUBLIC_KEY' ) ? constant( 'BBCS_FREEMIUS_PUBLIC_KEY' ) : null,
			'is_premium'          => false,
			'has_premium_version' => false,
			'has_paid_plans'      => false,
			'is_org_compliant'    => true,
			'anonymous_mode'      => true,
			'menu'                => array(),
		);
		if ( self::isDemoEnabled() ) {
			if ( ! is_numeric( $config['id'] ) ) {
				$config['id'] = 900001;
			} elseif ( (int) $config['id'] < 1 ) {
				$config['id'] = 900001;
			}
			if ( ! is_string( $config['public_key'] ) || '' === trim( $config['public_key'] ) ) {
				$config['public_key'] = 'pk_local_demo';
			}
		}
		if ( function_exists( 'apply_filters' ) ) {
			$config = apply_filters( 'bbcs_freemius_config', $config );
		}

		if ( ! is_array( $config ) || ! isset( $config['id'], $config['public_key'] ) || ! is_numeric( $config['id'] ) || (int) $config['id'] < 1 || ! is_string( $config['public_key'] ) || '' === trim( $config['public_key'] ) ) {
			return null;
		}

		return $config;
	}

	private static function loadSdk(): bool {
		$path = BOTBLOCKER_DIR . self::SDK_PATH;
		if ( ! file_exists( $path ) ) {
			return false;
		}

		require_once $path;
		return class_exists( 'BotBlocker\\Vendor\\Freemius', false );
	}
}