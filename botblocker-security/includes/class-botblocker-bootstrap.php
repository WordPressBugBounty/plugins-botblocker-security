<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BotBlocker bootstrap: version guards, shield startup, AJAX check,
 * security headers, multisite site lifecycle, meta/preconnect hooks.
 * Migrated from botblocker-security.php (S-44).
 */
class BotBlockerBootstrap {

	/**
	 * Wire every bootstrap hook. Returns false when the environment fails the
	 * minimum PHP/WordPress checks (the caller must stop loading the plugin).
	 */
	public static function register(): bool {
		// Check minimum requirements (PHP)
		if ( version_compare( phpversion(), '7.4.0', '<' ) ) {
			add_action( 'admin_notices', array( self::class, 'minimumPhpVersionNotice' ) );
			return false;
		}

		// Check minimum requirements (WordPress)
		if ( version_compare( $GLOBALS['wp_version'], '5.1', '<' ) ) {
			add_action( 'admin_notices', array( self::class, 'minimumWpVersionNotice' ) );
			return false;
		}

		// Guard: fix nonce_user_logged_out uid at 0 for unauthenticated AJAX requests.
		// Without this, a third-party plugin hooking the filter could return a different
		// uid during nonce creation vs verification, causing nopriv nonce mismatch.
		add_filter( 'nonce_user_logged_out', '__return_zero', 0 );

		add_action( 'wp_ajax_bbcs_botblocker_check', array( self::class, 'ajaxCheck' ) );
		add_action( 'wp_ajax_nopriv_bbcs_botblocker_check', array( self::class, 'ajaxCheck' ) );

		add_action( 'plugins_loaded', array( self::class, 'runShield' ), -9998 );

		add_action( 'wp_initialize_site', array( self::class, 'onWpInitializeSite' ), 200 );
		add_action( 'wp_uninitialize_site', array( self::class, 'onWpUninitializeSite' ), 1 );

		add_action( 'wp_head', array( self::class, 'addGoogleFontsPreconnect' ) );
		add_filter( 'plugin_row_meta', array( self::class, 'pluginRowMeta' ), 10, 2 );

		// Multisite: flag sites map for regeneration when site URLs change.
		if ( is_multisite() ) {
			add_action(
				'update_option_siteurl',
				function () {
					update_site_option( 'bbcs_sites_map_dirty', 1 );
				}
			);

			add_action(
				'wp_update_site',
				function ( $new_site, $old_site ) {
					if ( $new_site->domain !== $old_site->domain || $new_site->path !== $old_site->path ) {
						update_site_option( 'bbcs_sites_map_dirty', 1 );
					}
				},
				10,
				2
			);
		}

		return true;
	}

	public static function minimumPhpVersionNotice(): void {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'BotBlocker requires PHP 7.4 or higher.', 'botblocker-security' ) . '</p></div>';
	}

	public static function minimumWpVersionNotice(): void {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'BotBlocker requires WordPress 5.1 or later.', 'botblocker-security' ) . '</p></div>';
	}

	/**
	 * Checks if the request is an AJAX request and performs logic.
	 *
	 * This function is responsible for checking if the current request is an AJAX request and performing the necessary bot blocking logic.
	 * It prevents automated bots from accessing or submitting data through AJAX requests.
	 *
	 * @return void
	 */
	public static function ajaxCheck(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );

		// Rate-limit: max 100 check-requests per IP per hour.
		/*
		if ( ! BotBlockerIp::rateLimit( 'bbcs_botblocker_check', 100, HOUR_IN_SECONDS ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'botblocker-security' ) ), 429 );
		}
		*/

		/* Include the BotBlocker main class file */
		require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker.php';
		BotBlockerAddons::includePreRunAddons();
		$botBlocker = new BotBlocker();
		$botBlocker->init_visitor_pages();
		$botBlocker->initialize();
		wp_die();
	}

	/**
	 * Sends the security headers built by the bbcs-security-headers addon.
	 *
	 * Called by BotBlockerHeaderTrait::finalize_allowed_headers() - the plugin is
	 * the only component that writes addon headers to the browser. Security pages
	 * (check/block/denied, AJAX verification) never reach this function.
	 *
	 * @since 1.6.21
	 *
	 * @param array<string,string> $headers Header-name => header-value pairs.
	 * @return void
	 */
	public static function sendSecurityHeaders( array $headers ): void {
		if ( headers_sent() ) {
			return;
		}
		foreach ( $headers as $name => $value ) {
			if ( ! is_string( $name ) || '' === $name ) {
				continue;
			}
			// Strip CR/LF/NUL to prevent header injection.
			$safe_value = str_replace( array( "\r", "\n", "\0" ), '', (string) $value );
			header( "{$name}: {$safe_value}", true );
		}
	}

	/**
	 * Runs the BotBlocker plugin.
	 *
	 * This function initializes the plugin and its admin functionality.
	 *
	 * @return void
	 */
	public static function runShield(): void {
		/* Check installation and create tables if necessary (for corrupted installations) */
		BotBlockerInstall::checkInstall();

		/* Redeploy bundled addons on core version change - stale uploads copies fatal on removed plugin functions */
		try {
			BotBlockerAddons::maybeRedeployBuiltins();
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a broken redeploy must never white-screen boot
			error_log( '[BBCS] [Addons] maybeRedeployBuiltins threw: ' . $e->getMessage() );
		}

		/* Include the BotBlocker main interface class file */
		$plugin = new BotBlockerSecurity();
		BotBlockerAddons::includePreRunAddons();
		$plugin->run();

		/* After run(): isEnabled() needs its settings, so anything during run() fires before a sensor exists. */
		require_once BOTBLOCKER_DIR . 'includes/audit/class-botblocker-audit.php';
		BotBlockerAudit::init();

		/* Include active addons after database is ready */
		BotBlockerAddons::includeAll();

		if ( is_admin() ) {
			// Admin UI, menu registration, plugin action links
			$bbcs_admin = Botblocker_Admin::getInstance();
			add_action( 'admin_menu', array( $bbcs_admin, 'add_admin_menu' ) );
			$bbcs_admin->run();

			// Notification system
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-toastify.php';
			BBCS_Toastify::init();

			// Deactivation feedback modal
			BotBlockerDeactivationFeedback::register();

			// Setup Wizard
			require_once BOTBLOCKER_DIR . 'admin/class-botblocker-setup-wizard.php';
			$bbcs_wizard = new BotBlocker_SetupWizard();
			$bbcs_wizard->hooks();
		}
	}

	//BBCS-MULTISITE
	public static function onWpInitializeSite( $new_site ): void {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active_for_network( BOTBLOCKER_BASENAME ) ) {
			return;
		}
		switch_to_blog( (int) $new_site->blog_id );
		try {
			require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
			Botblocker_Activator::activate();
		} finally {
			restore_current_blog();
		}
		require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
	}

	//BBCS-MULTISITE
	public static function onWpUninitializeSite( $old_site ): void {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active_for_network( BOTBLOCKER_BASENAME ) ) {
			return;
		}

		switch_to_blog( (int) $old_site->blog_id );
		try {
			require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';
			if ( class_exists( 'BotBlockerAddons' ) ) {
				BotBlockerAddons::deactivateAll();
			}
			BotBlockerCron::removeTasks();
		} finally {
			restore_current_blog();
		}
		require BOTBLOCKER_DIR . 'includes/database/inc-botblocker-tables.php';

		if ( defined( 'BOTBLOCKER_INTEGRATE_MU_PLUGINS' ) && BOTBLOCKER_INTEGRATE_MU_PLUGINS && method_exists( 'BotBlockerInstall', 'uninstallMuPlugin' ) ) {
			BotBlockerInstall::uninstallMuPlugin();
		}

		update_site_option( 'bbcs_sites_map_dirty', 1 );
	}

	/* Add preconnect for Google Fonts and Google Charts*/
	public static function addGoogleFontsPreconnect(): void {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	}

	/**
	 * Add custom links to plugin meta row
	 */
	public static function pluginRowMeta( array $links, string $file ): array {
		if ( BOTBLOCKER_BASENAME === $file ) {
			$row_meta = array(
				'docs'  => '<a href="https://botblocker.top/docs/" target="_blank">' . esc_html__( 'Docs and FAQs', 'botblocker-security' ) . '</a>',
				'video' => '<a href="https://globus.studio/contact-us-to-develop-agency-solutions/" target="_blank">' . esc_html__( 'Hire Developers', 'botblocker-security' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}
		return $links;
	}
}
