<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BotBlockerFreemius {

	private const ENABLED = true;
	private const SDK_PATH = 'vendor/Freemius/2.13.4/standalone/autoloader.php';
	private const CONNECT_CHANGES = array( 'activated', 'upgraded', 'changed', 'extended' );
	private const DISCONNECT_CHANGES = array( 'cancelled', 'expired', 'downgraded' );
	const CONNECT_HOOK = 'bbcs_freemius_connect';
	private const KEY_PREFIX = 'sk_';
	private static $instance = null;

	public static function boot(): bool {
		if ( ! self::isEnabled() ) {
			return false;
		}
		if ( self::$instance !== null ) {
			return true;
		}

		$config = self::getConfig();
		if ( null === $config || ! self::loadSdk() ) {
			return false;
		}

		try {
			self::$instance = fs_dynamic_init( $config );
		} catch ( \Throwable $exception ) {
			self::$instance = null;
			return false;
		}

		if ( ! is_object( self::$instance ) ) {
			self::$instance = null;
			return false;
		}

		self::$instance->add_action( 'after_license_change', array( self::class, 'onLicenseChange' ) );
		self::$instance->add_action( 'after_license_deactivation', array( self::class, 'disconnectLicense' ) );
		self::$instance->add_action( 'after_license_activation', array( self::class, 'scheduleConnect' ) );
		self::$instance->add_filter( 'show_deactivation_feedback_form', '__return_false' );
		self::$instance->add_filter( 'show_deactivation_subscription_cancellation', '__return_false' );
		add_action( self::CONNECT_HOOK, array( self::class, 'connectLicense' ) );
		$basename = self::$instance->get_plugin_basename();
		add_filter( 'plugin_action_links_' . $basename, array( self::class, 'removeActionLinks' ), 11 );
		add_filter( 'network_admin_plugin_action_links_' . $basename, array( self::class, 'removeActionLinks' ), 11 );

		if ( function_exists( 'do_action' ) ) {
			do_action( 'bbcs_freemius_loaded', self::$instance );
		}

		return true;
	}

	public static function instance() {
		return self::$instance;
	}

	public static function setInstance( $instance ): void {
		self::$instance = $instance;
	}

	public static function reset(): void {
		self::$instance = null;
	}

	public static function removeActionLinks( $links ) {
		if ( ! is_array( $links ) || ! is_object( self::$instance ) ) {
			return $links;
		}
		$affix = self::$instance->get_unique_affix();
		unset( $links[ 'activate-license ' . $affix ], $links[ 'opt-in-or-opt-out ' . $affix ] );

		return $links;
	}

	public static function onLicenseChange( $plan_change ): void {
		if ( in_array( (string) $plan_change, self::CONNECT_CHANGES, true ) ) {
			self::connectLicense();
		} elseif ( in_array( (string) $plan_change, self::DISCONNECT_CHANGES, true ) ) {
			self::disconnectLicense();
		}
	}

	public static function scheduleConnect(): void {
		if ( false === wp_next_scheduled( self::CONNECT_HOOK ) ) {
			wp_schedule_single_event( time(), self::CONNECT_HOOK );
		}
	}

	public static function disconnectLicense(): void {
		require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-cloud-api-hooks.php';
		BotBlockerCloudApiHooks::disconnect();
	}

	public static function checkoutUrl( int $plan_id, string $billing_cycle ): string {
		if ( ! is_object( self::$instance ) ) {
			return '';
		}

		return (string) self::$instance->checkout_url( $billing_cycle, false, array( 'plan_id' => $plan_id, 'licenses' => 1 ) );
	}

	public static function hasLicense(): bool {
		return is_object( self::$instance ) && self::$instance->has_active_valid_license();
	}

	public static function accountUrl(): string {
		if ( ! is_object( self::$instance ) ) {
			return '';
		}

		return (string) self::$instance->get_account_url();
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

	public static function connectLicense(): void {
		self::connect();
	}

	public static function ownsKey( string $key ): bool {
		return is_object( self::$instance ) && 0 === strpos( $key, self::KEY_PREFIX );
	}

	public static function activateKey( string $key ): ?string {
		$result = self::$instance->activate_migrated_license( $key );
		if ( ! is_array( $result ) || empty( $result['success'] ) ) {
			if ( is_array( $result ) && ! empty( $result['error'] ) && is_string( $result['error'] ) ) {
				return sanitize_text_field( $result['error'] );
			}
			return __( 'License activation failed. Try again later.', 'botblocker-security' );
		}

		wp_unschedule_hook( self::CONNECT_HOOK );
		$error = self::connect();
		if ( false === $error ) {
			return __( 'License activation failed. Try again later.', 'botblocker-security' );
		}
		return $error;
	}

	private static function connect() {
		if ( ! is_object( self::$instance ) || ! self::$instance->has_active_valid_license() ) {
			return false;
		}
		$license = self::$instance->_get_license();
		if ( ! is_object( $license ) || empty( $license->secret_key ) || empty( $license->id ) ) {
			return false;
		}

		require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-cloud-api-hooks.php';
		return BotBlockerCloudApiHooks::connectKey( (string) $license->secret_key, (int) $license->id );
	}

	private static function isEnabled(): bool {
		$enabled = defined( 'BBCS_FREEMIUS_ENABLED' ) ? (bool) constant( 'BBCS_FREEMIUS_ENABLED' ) : self::ENABLED;
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
			'has_paid_plans'      => true,
			'is_org_compliant'    => true,
			'anonymous_mode'      => true,
			'menu'                => array(
				'slug'        => 'bbcs_dashboard',
				'account'     => true,
				'pricing'     => false,
				'contact'     => false,
				'support'     => false,
				'affiliation' => false,
				'addons'      => false,
			),
		);
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
		return class_exists( 'Freemius', false );
	}
}