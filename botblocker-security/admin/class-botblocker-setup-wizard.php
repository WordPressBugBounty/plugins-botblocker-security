<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/class-botblocker-setup-wizard-ajax.php';
require_once __DIR__ . '/class-botblocker-setup-wizard-renderer.php';

class BotBlocker_SetupWizard {

	use BotBlocker_SetupWizardAjaxTrait;

	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'load_wizard' ) );
		add_action( 'admin_init', array( $this, 'redirect_after_activation' ), 9999 );

		add_action( 'wp_ajax_bbcs_wizard_save_preset', array( $this, 'ajax_save_preset' ) );
		add_action( 'wp_ajax_bbcs_wizard_compatibility_test', array( $this, 'ajax_compatibility_test' ) );
		add_action( 'wp_ajax_bbcs_wizard_save_exclusions', array( $this, 'ajax_save_exclusions' ) );
		add_action( 'wp_ajax_bbcs_wizard_save_captcha', array( $this, 'ajax_save_captcha' ) );
		add_action( 'wp_ajax_bbcs_wizard_save_init_mode', array( $this, 'ajax_save_init_mode' ) );
		add_action( 'wp_ajax_bbcs_wizard_check_cache', array( $this, 'ajax_check_cache' ) );
		add_action( 'wp_ajax_bbcs_wizard_save_cache', array( $this, 'ajax_save_cache' ) );
		add_action( 'wp_ajax_bbcs_wizard_save_notifications', array( $this, 'ajax_save_notifications' ) );
		add_action( 'wp_ajax_bbcs_wizard_complete', array( $this, 'ajax_complete_wizard' ) );
		add_action( 'wp_ajax_bbcs_wizard_test_attack', array( $this, 'ajax_test_attack' ) );
		add_action( 'wp_ajax_bbcs_wizard_save_ux', array( $this, 'ajax_save_ux' ) );
		add_action( 'wp_ajax_bbcs_wizard_reset', array( $this, 'ajax_reset_wizard' ) );
	}

	public static function get_site_url(): string {
		$BBCSA = Botblocker_Admin::getInstance();
		return $BBCSA->pages_wizard;
	}

	public function load_wizard(): void {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'bbcs_setup_wizard' ) {
			return;
		}

		if ( is_multisite() && is_network_admin() ) {
			wp_safe_redirect( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' ) );
			exit;
		}

		set_current_screen();
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_head', 'wp_admin_bar_header' );
		$this->load_setup_wizard();
	}

	public function redirect_after_activation(): void {

		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( is_network_admin() ) {
			return;
		}

		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_activation_request = isset( $_GET['activate'] ) || isset( $_GET['activate-multi'] );
		if ( 'plugins.php' !== $pagenow || ! $is_activation_request ) {
			return;
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return;
		}

		if ( ! BotBlockerMultisite::getOption( 'bbcs_activation_redirect', false ) ) {
			return;
		}

		BotBlockerMultisite::deleteOption( 'bbcs_activation_redirect' );

		if ( BotBlockerMultisite::getOption( 'bbcs_activation_prevent_redirect' ) ) {
			return;
		}

		$wizard_completed = BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );
		$initial_version  = BotBlockerMultisite::getOption( 'bbcs_initial_version', '' );
		$current_version  = BOTBLOCKER_VERSION;
		$wizard_on_update = defined( 'BOTBLOCKER_WIZARD_ON_UPDATE' ) && BOTBLOCKER_WIZARD_ON_UPDATE;

		if ( $wizard_completed && $initial_version === $current_version ) {
			return;
		}

		if ( $wizard_completed && $initial_version !== $current_version && ! $wizard_on_update ) {
			return;
		}

		wp_safe_redirect( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' ) );
		exit;
	}

	private function load_setup_wizard(): void {
		if ( ! $this->should_setup_wizard_load() ) {
			return;
		}

		$this->setup_wizard_header();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function should_setup_wizard_load(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'bbcs_setup_wizard' ) {
			return false;
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return false;
		}

		return true;
	}

	public function setup_wizard_header(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript 
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php esc_html_e( 'BotBlocker Security &rsaquo; Setup Wizard', 'botblocker-security' ); ?></title>
			
			<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'css/bootstrap/bootstrap.min.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'css/theme.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'css/default.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'css/all.min.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'css/botblocker-setup-wizard.css' ) ); ?>">
			
			<script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.min.js' ) ); ?>"></script>
			<script>
				var bbcs_setup_wizard_vars = 
				<?php
				echo wp_json_encode(
					array(
						'ajax_url'           => admin_url( 'admin-ajax.php' ),
						'nonce'              => wp_create_nonce( 'bbcs-wizard-admin-nonce' ),
						'plugin_version'     => BOTBLOCKER_VERSION,
						'public_url'         => BOTBLOCKER_URL . 'public/',
						'current_user_email' => wp_get_current_user()->user_email,
						'dashboard_url'      => BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_dashboard' ),
						'reports_url'        => BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_reports' ),
						'current_ip'         => $this->get_current_ip(),
						'site_url'           => home_url( '/' ),
						'i18n'               => array(
							'confirm_apply_defaults' => __( 'Are you sure? We will apply the default settings (Balanced).', 'botblocker-security' ),
							'auto_fix_stub'          => __( 'Automatic fix applied (stub). Moving on.', 'botblocker-security' ),
							'error_prefix'           => __( 'Error: ', 'botblocker-security' ),
							'unknown_error'          => __( 'Unknown error', 'botblocker-security' ),
							'ajax_error'             => __( 'AJAX error', 'botblocker-security' ),
							'ajax_error_compat'      => __( 'AJAX error during compatibility test', 'botblocker-security' ),
							'ajax_error_captcha'     => __( 'AJAX error during captcha save', 'botblocker-security' ),
							'ajax_error_init'        => __( 'AJAX error during init mode save', 'botblocker-security' ),
							'ajax_error_cache'       => __( 'AJAX error during cache save', 'botblocker-security' ),
							'test_failed_prefix'     => __( 'Test failed: ', 'botblocker-security' ),
							'saving'                 => __( 'Saving...', 'botblocker-security' ),
							'apply_preset'           => __( 'Apply preset', 'botblocker-security' ),
						),
					)
				);
				?>
												;
			</script>
			<script src="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'js/bootstrap/bootstrap.bundle.min.js' ) ); ?>"></script>
			<script src="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, plugin_dir_url( __FILE__ ) . 'js/botblocker-setup-wizard.js' ) ); ?>"></script>
		</head>
		<body class="botblocker-security-setup-wizard">
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	public function setup_wizard_content(): void {
		$renderer = new BotBlocker_SetupWizardRenderer();
		$renderer->render_wizard_content();
	}

	public function setup_wizard_footer(): void {
		?>
		</body>
		</html>
		<?php
	}

	private function get_current_ip(): string {
		$raw = '';
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( strpos( $raw, ',' ) !== false ) {
			$raw = trim( explode( ',', $raw )[0] );
		}
		return sanitize_text_field( $raw );
	}

}
