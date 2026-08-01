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

		if ( $this->is_new_ui_mode() ) {
			$this->load_new_ui_wizard();
			return;
		}

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

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'bbcs_setup_wizard' ) {
			return;
		}

		if ( ! BotBlockerMultisite::getOption( 'bbcs_activation_redirect', false ) ) {
			return;
		}

		if ( BotBlockerMultisite::getOption( 'bbcs_activation_prevent_redirect' ) ) {
			BotBlockerMultisite::deleteOption( 'bbcs_activation_redirect' );
			return;
		}

		$wizard_completed = BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );
		$initial_version  = BotBlockerMultisite::getOption( 'bbcs_initial_version', '' );
		$current_version  = BOTBLOCKER_VERSION;
		$wizard_on_update = defined( 'BOTBLOCKER_WIZARD_ON_UPDATE' ) && BOTBLOCKER_WIZARD_ON_UPDATE;

		$needs_wizard = ! $wizard_completed;
		$needs_wizard = $needs_wizard || ( $wizard_completed && $initial_version !== $current_version && $wizard_on_update );

		if ( ! $needs_wizard ) {
			BotBlockerMultisite::deleteOption( 'bbcs_activation_redirect' );
			return;
		}

		// On single site or per-site activation: redirect immediately from plugins.php.
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_activation_request = isset( $_GET['activate'] ) || isset( $_GET['activate-multi'] );
		$is_plugins_page       = 'plugins.php' === $pagenow && $is_activation_request;

		// On multisite subsites the admin may never visit plugins.php, so
		// redirect from any admin page when the flag is still present.
		if ( $is_plugins_page || is_multisite() ) {
			BotBlockerMultisite::deleteOption( 'bbcs_activation_redirect' );
			wp_safe_redirect( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' ) );
			exit;
		}
	}

	private function load_new_ui_wizard(): void {
		if ( ! $this->should_setup_wizard_load() ) {
			return;
		}
		$this->render_new_ui_wizard();
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		flush();
		exit;
	}

	private function load_setup_wizard(): void {
		if ( ! $this->should_setup_wizard_load() ) {
			return;
		}

		$this->setup_wizard_header();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		flush();
		exit;
	}

	private function is_new_ui_mode(): bool {
		return defined( 'BBCS_NEW_ADMIN_UI' ) && BBCS_NEW_ADMIN_UI;
	}

	private function render_new_ui_wizard(): void {
		$wizard_completed = BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );
		if ( $wizard_completed ) {
			// Lightweight completed page - no external CSS/JS/Fonts, no SVG sprite.
			$renderer = require BOTBLOCKER_DIR . 'admin/templates/setup-wizard/completed.php';
			$renderer( array(
				'dashboard_url' => BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_dashboard' ),
				'settings_url'  => BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_settings' ),
				'reset_nonce'   => wp_create_nonce( 'bbcs-wizard-admin-nonce' ),
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
			) );
			return;
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS] render_new_ui_wizard(): rendering wizard HTML.' );
		}

		$vm_file = BOTBLOCKER_DIR . 'includes/viewmodels/class-setup-wizard-viewmodel.php';
		require_once $vm_file;

		$view_file = BOTBLOCKER_DIR . 'admin/views/class-setup-wizard-view.php';
		require_once $view_file;

		$vm   = new Botblocker_SetupWizard_ViewModel();

		$view = new Botblocker_SetupWizard_View( $vm );

		$this->new_ui_wizard_header( $vm );

		$this->new_ui_wizard_body( $view, $vm );

		$this->new_ui_wizard_footer();
	}

	private function new_ui_wizard_header( ?Botblocker_SetupWizard_ViewModel $vm = null ): void {
		$admin_dir_url  = BOTBLOCKER_URL . 'admin/';
		$plugin_version = BOTBLOCKER_VERSION;
		$dashboard_url  = $vm ? $vm->dashboard_url : BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_dashboard' );
		$reports_url    = $vm ? $vm->reports_url : BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_reports' );
		$current_ip     = $vm ? $vm->current_ip : '';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php esc_html_e( 'BotBlocker Security &rsaquo; Setup Wizard', 'botblocker-security' ); ?></title>
			<link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', $plugin_version, $admin_dir_url . 'css/bbcs-tokens.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', $plugin_version, $admin_dir_url . 'css/bbcs.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', $plugin_version, $admin_dir_url . 'css/all.min.css' ) ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', $plugin_version, $admin_dir_url . 'css/bbcs-setup-wizard-new.css' ) ); ?>">
			<script>
				var bbcs_setup_wizard_vars = 
				<?php
				echo wp_json_encode(
					array(
						'ajax_url'           => admin_url( 'admin-ajax.php' ),
						'nonce'              => wp_create_nonce( 'bbcs-wizard-admin-nonce' ),
						'plugin_version'     => $plugin_version,
						'public_url'         => BOTBLOCKER_URL . 'public/',
						'current_user_email' => wp_get_current_user()->user_email,
						'dashboard_url'      => $dashboard_url,
						'reports_url'        => $reports_url,
						'current_ip'         => $current_ip,
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
			<script src="<?php echo esc_url( add_query_arg( 'ver', $plugin_version, $admin_dir_url . 'js/bbcs-js/bbcs-setup-wizard.js' ) ); ?>"></script>
		</head>
		<body class="botblocker-security-setup-wizard">
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	private function new_ui_wizard_body( Botblocker_SetupWizard_View $view, Botblocker_SetupWizard_ViewModel $vm ): void {
		$icons_file = BOTBLOCKER_DIR . 'admin/templates/shared/icons-sprite.php';
		$icons = require $icons_file;
		$icons();
		?>
		<div class="bbcs-app">
			<?php
			$header_file = BOTBLOCKER_DIR . 'admin/templates/setup-wizard/header.php';
			$header_renderer = require $header_file;
			$header_renderer( $vm );
			?>
			<div class="bbcs-content">
				<div class="bbcs-scroll">
					<div class="bbcs-wrap">
						<div class="bbcs-wiz" id="bbcs-wizard">

							<div class="bbcs-wizmeter" id="bbcs-wizmeter">
								<span class="bbcs-wizdot is-active" data-dot="0">1</span><span class="bbcs-wizline" data-line="0"></span>
								<span class="bbcs-wizdot" data-dot="1">2</span><span class="bbcs-wizline" data-line="1"></span>
								<span class="bbcs-wizdot" data-dot="2">3</span><span class="bbcs-wizline" data-line="2"></span>
								<span class="bbcs-wizdot" data-dot="3">4</span><span class="bbcs-wizline" data-line="3"></span>
								<span class="bbcs-wizdot" data-dot="4">5</span><span class="bbcs-wizline" data-line="4"></span>
								<span class="bbcs-wizdot" data-dot="5">6</span><span class="bbcs-wizline" data-line="5"></span>
								<span class="bbcs-wizdot" data-dot="6">7</span><span class="bbcs-wizline" data-line="6"></span>
								<span class="bbcs-wizdot" data-dot="7">8</span>
							</div>

							<?php
							$view->welcome();
							?>
							<?php $view->presets(); ?>
							<?php $view->compatibility(); ?>
							<?php $view->exclusions(); ?>
							<?php $view->captcha(); ?>
							<?php $view->init_mode(); ?>
							<?php $view->cache(); ?>
							<?php $view->finish(); ?>

						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function new_ui_wizard_footer(): void {
		?>
		</body>
		</html>
		<?php
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
						'current_ip'         => BotBlockerIp::getCurrentIp(),
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

}
